<?php

namespace App\Services;

use App\Models\AppComment;
use App\Models\FlowDefinition;
use App\Models\FlowNotification;
use App\Models\FlowNotificationPref;
use App\Models\FlowRecord;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Flow の通知バッジ (per-app bell) のイベント書き込みと既読管理。
 *
 * Events are written per recipient at the moment they happen (comment / new record /
 * status change) — the portal badge is then a single indexed count. Read rules differ
 * by type: new_record & status_change clear when the record is opened; comment clears
 * only after the comment tab has actually been viewed (FE calls markCommentsRead).
 * Own actions never notify. Per-user per-app overrides live in flow_notification_prefs
 * (sparse rows = deviations from PREF_DEFAULTS; new_record is opt-IN, the rest opt-OUT).
 */
class FlowNotificationService
{
    /**
     * Pref keys and their DEFAULT state (sparse rows in flow_notification_prefs store only the
     * deviations). `new_record` is opt-IN: on a busy app every added record would otherwise ping
     * everyone who can see the app, which is the fastest way to make people mute the whole thing.
     * The other three are about something the user is already involved in, so they default on.
     */
    public const PREF_DEFAULTS = [
        'comment_own' => true,
        'comment_participated' => true,
        'new_record' => false,
        'status_change' => true,
        // a duty someone assigned you by name — the one event you would not want to miss
        'pending_action' => true,
    ];

    public const PREFS = ['comment_own', 'comment_participated', 'new_record', 'status_change', 'pending_action'];

    public function __construct(private FlowService $flowService) {}

    /* ---------------------------------------------------------------- events */

    /** A comment landed on a record → notify the creator + past commenters (minus the actor). */
    public function notifyComment(FlowRecord $record, User $actor, AppComment $comment): void
    {
        $def = $record->definition;

        // recipient => pref key that governs them (creator wins when they also commented)
        $recipients = [];
        foreach ($this->pastCommenterIds($record, $comment->id) as $uid) {
            $recipients[$uid] = 'comment_participated';
        }
        if ($record->created_by) {
            $recipients[(int) $record->created_by] = 'comment_own';
        }
        unset($recipients[(int) $actor->id]);
        if (! $recipients) {
            return;
        }

        $recipients = $this->filterByPrefs($def->id, $recipients);
        // comment recipients are a handful — verify each can still view the record
        $users = User::whereIn('id', array_keys($recipients))->where('retire', 0)->get();
        $ids = $users->filter(fn ($u) => $this->flowService->recordPermissions($u, $record, $def)['view'])
            ->pluck('id')->all();

        $this->insert($ids, $def->id, $record->id, 'comment', $actor->id, ['comment_id' => $comment->id]);
    }

    /** A record was created → notify everyone who can view the app (minus the actor, per prefs). */
    public function notifyNewRecord(FlowDefinition $definition, FlowRecord $record, User $actor): void
    {
        $ids = $this->newRecordRecipientIds($definition, $actor);
        $this->insert($ids, $definition->id, $record->id, 'new_record', $actor->id);
    }

    /** CSV import → ONE grouped event per recipient (never one per row). No record link. */
    public function notifyImport(FlowDefinition $definition, User $actor, int $count): void
    {
        if ($count < 1) {
            return;
        }
        $ids = $this->newRecordRecipientIds($definition, $actor);
        $this->insert($ids, $definition->id, null, 'new_record', $actor->id, ['count' => $count]);
    }

    /** A record's status moved → notify its creator (minus the actor, per prefs). */
    public function notifyStatusChange(FlowRecord $record, User $actor, ?string $from, ?string $to): void
    {
        $creator = (int) ($record->created_by ?? 0);
        if (! $creator || $creator === (int) $actor->id) {
            return;
        }
        $recipients = $this->filterByPrefs($record->flow_definition_id, [$creator => 'status_change']);
        $this->insert(array_keys($recipients), $record->flow_definition_id, $record->id, 'status_change', $actor->id, ['from' => $from, 'to' => $to]);
    }

    /**
     * The record now sits on a status whose actions name specific people → tell them it is theirs.
     *
     * Mirrors the 対応待ち counter exactly (hasExplicitPendingAction): an action with 押せる人 left
     * blank is pressable by anyone and therefore nobody's duty, so it notifies no one.
     *
     * Idempotent, because it also runs on edits: whoever is still responsible keeps the row they
     * already have (and its read state), anyone no longer responsible loses theirs. That matters for
     * `field`-type eligibility, where editing a ユーザー field changes who is on the hook.
     */
    public function syncPendingAction(FlowRecord $record, ?User $actor = null): void
    {
        $definition = $record->relationLoaded('definition') ? $record->definition : $record->definition;
        if (! $definition || ! $definition->use_status_flow || ! $definition->is_active) {
            $this->withdrawPendingAction($record);

            return;
        }

        $responsible = $this->pendingActionRecipientIds($record, $definition, $actor);

        // drop rows for anyone who is no longer responsible (status moved on, or eligibility changed)
        FlowNotification::where('flow_record_id', $record->id)
            ->where('type', 'pending_action')
            ->when($responsible, fn ($q) => $q->whereNotIn('user_id', $responsible))
            ->delete();

        if (! $responsible) {
            return;
        }
        // keep existing rows (and their read state) so an unrelated edit does not re-flag everyone
        $already = FlowNotification::where('flow_record_id', $record->id)
            ->where('type', 'pending_action')
            ->whereIn('user_id', $responsible)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $fresh = array_values(array_diff($responsible, $already));
        $this->insert($fresh, $definition->id, $record->id, 'pending_action', (int) ($actor->id ?? 0), [
            'status' => $record->relationLoaded('currentStatus') ? $record->currentStatus?->name : null,
        ]);
    }

    /** The duty is over (record moved on, deleted, or the app stopped using statuses). */
    public function withdrawPendingAction(FlowRecord $record): void
    {
        FlowNotification::where('flow_record_id', $record->id)
            ->where('type', 'pending_action')
            ->delete();
    }

    /** Users explicitly named by an action on the current status, who may view the record. */
    private function pendingActionRecipientIds(FlowRecord $record, FlowDefinition $definition, ?User $actor): array
    {
        $definition->loadMissing(['statuses', 'statusActions', 'recordPermissionSets', 'appPermissions', 'fields']);
        $record->loadMissing(['values', 'currentStatus']);

        $ids = $this->newRecipientCandidates($definition)
            ->filter(fn ($u) => (int) $u->id !== (int) ($actor->id ?? 0)
                && $this->flowService->hasExplicitPendingAction($u, $record)
                && $this->flowService->recordPermissions($u, $record, $definition)['view'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_keys($this->filterByPrefs($definition->id, array_fill_keys($ids, 'pending_action')));
    }

    /* ---------------------------------------------------------------- reads */

    /**
     * Record opened → new_record / status_change / pending_action events for it are considered seen.
     * pending_action is marked read but NOT removed: the duty outlives the glance, and the row is
     * withdrawn only when the record actually moves on. The live 対応待ち counter stays the
     * authority on "you still have to act".
     */
    public function markRecordOpened(User $user, FlowRecord $record): void
    {
        FlowNotification::where('user_id', $user->id)
            ->where('flow_record_id', $record->id)
            ->whereIn('type', ['new_record', 'status_change', 'pending_action'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Record list opened → grouped import events (no single record to open) are considered seen;
     * the user just looked at the list the imported rows live in.
     */
    public function markImportSeen(User $user, FlowDefinition $definition): void
    {
        FlowNotification::where('user_id', $user->id)
            ->where('flow_definition_id', $definition->id)
            ->where('type', 'new_record')
            ->whereNull('flow_record_id')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Comment tab actually viewed (FE fires after it has been visible a few seconds). */
    public function markCommentsRead(User $user, FlowRecord $record): void
    {
        FlowNotification::where('user_id', $user->id)
            ->where('flow_record_id', $record->id)
            ->where('type', 'comment')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Unread badge totals for the portal: [flow_definition_id => count]. */
    public function unreadCounts(User $user): array
    {
        return FlowNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->selectRaw('flow_definition_id, COUNT(*) as c')
            ->groupBy('flow_definition_id')
            ->pluck('c', 'flow_definition_id')
            ->all();
    }

    /** Unread comment-event count for one record (drives the comment-tab badge). */
    public function unreadCommentCount(User $user, FlowRecord $record): int
    {
        return FlowNotification::where('user_id', $user->id)
            ->where('flow_record_id', $record->id)
            ->where('type', 'comment')
            ->whereNull('read_at')
            ->count();
    }

    /* ---------------------------------------------------------------- prefs */

    /** The user's resolved prefs for an app (PREF_DEFAULTS, overridden by stored rows). */
    public function prefsFor(User $user, int $definitionId): array
    {
        $prefs = self::PREF_DEFAULTS;
        $rows = FlowNotificationPref::where('user_id', $user->id)
            ->where('flow_definition_id', $definitionId)
            ->pluck('enabled', 'pref');
        foreach ($rows as $key => $enabled) {
            if (array_key_exists($key, $prefs)) {
                $prefs[$key] = (bool) $enabled;
            }
        }

        return $prefs;
    }

    public function savePref(User $user, int $definitionId, string $pref, bool $enabled): void
    {
        abort_unless(in_array($pref, self::PREFS, true), 422);
        FlowNotificationPref::updateOrCreate(
            ['user_id' => $user->id, 'flow_definition_id' => $definitionId, 'pref' => $pref],
            ['enabled' => $enabled],
        );
    }

    /* ---------------------------------------------------------------- internals */

    /** Distinct authors of the record's earlier comments (excluding the new comment itself). */
    private function pastCommenterIds(FlowRecord $record, int $excludeCommentId): array
    {
        return AppComment::where('commentable_type', FlowRecord::class)
            ->where('commentable_id', $record->id)
            ->where('id', '!=', $excludeCommentId)
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Everyone who may view the app (minus the actor), minus users who switched the
     * new_record pref off. Company-scale loop: permission rows are evaluated in memory
     * per candidate user, then one bulk insert downstream.
     */
    private function newRecipientCandidates(FlowDefinition $definition): Collection
    {
        return User::where('retire', 0)->where('hide_flag', 0)
            ->select('id', 'name', 'position_id')
            ->get();
    }

    private function newRecordRecipientIds(FlowDefinition $definition, User $actor): array
    {
        $definition->loadMissing('appPermissions');
        $viewers = $this->newRecipientCandidates($definition)
            ->filter(fn ($u) => (int) $u->id !== (int) $actor->id
                && $this->flowService->effectiveAppPermissions($u, $definition)['view'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $recipients = $this->filterByPrefs($definition->id, array_fill_keys($viewers, 'new_record'));

        return array_keys($recipients);
    }

    /** Drop recipients whose stored pref row disables the governing key. */
    private function filterByPrefs(int $definitionId, array $recipientPrefKeys): array
    {
        if (! $recipientPrefKeys) {
            return [];
        }
        // Read every stored row (not just the disabled ones): with per-key defaults, "no row" can
        // mean either notify or don't, so each recipient resolves to explicit-setting ?? default.
        $rows = FlowNotificationPref::where('flow_definition_id', $definitionId)
            ->whereIn('user_id', array_keys($recipientPrefKeys))
            ->get(['user_id', 'pref', 'enabled']);
        $explicit = [];
        foreach ($rows as $row) {
            $explicit[(int) $row->user_id][$row->pref] = (bool) $row->enabled;
        }

        foreach ($recipientPrefKeys as $userId => $pref) {
            $on = $explicit[(int) $userId][$pref] ?? (self::PREF_DEFAULTS[$pref] ?? true);
            if (! $on) {
                unset($recipientPrefKeys[$userId]);
            }
        }

        return $recipientPrefKeys;
    }

    private function insert(array $userIds, int $definitionId, ?int $recordId, string $type, int $actorId, ?array $meta = null): void
    {
        if (! $userIds) {
            return;
        }
        $now = now();
        $rows = array_map(fn ($uid) => [
            'user_id' => $uid,
            'flow_definition_id' => $definitionId,
            'flow_record_id' => $recordId,
            'type' => $type,
            'actor_id' => $actorId,
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'read_at' => null,
            'created_at' => $now,
        ], array_values(array_unique($userIds)));

        DB::table('flow_notifications')->insert($rows);
    }
}
