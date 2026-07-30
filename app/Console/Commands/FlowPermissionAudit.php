<?php

namespace App\Console\Commands;

use App\Models\FlowDefinition;
use App\Models\User;
use App\Services\FlowService;
use Illuminate\Console\Command;

/**
 * Read-only preview of the app-permission rule change (first-match-wins -> most-specific-tier-wins).
 *
 * Safe to run anywhere, including production: it only reads, and it re-implements the OLD rule
 * locally so both outcomes can be compared without deploying anything. Run it BEFORE shipping the
 * change to see exactly who would be affected.
 *
 *   php artisan flow:permission-audit
 *   php artisan flow:permission-audit --app=37
 */
class FlowPermissionAudit extends Command
{
    protected $signature = 'flow:permission-audit {--app= : limit to one flow definition id}';

    protected $description = 'Preview which users change app permissions under the specificity rule (read-only)';

    /** Mirrors FlowService::SUBJECT_RANK — kept here so the command stands alone. */
    private const RANK = [
        'everyone' => 0,
        'position' => 1, 'project_member' => 1, 'project_manager' => 1, 'project_director' => 1,
        'creator_project_manager' => 1, 'field_project_manager' => 1,
        'creator' => 2, 'user' => 2, 'field' => 2,
    ];

    public function handle(FlowService $flow): int
    {
        $perms = ['view', 'add', 'edit', 'delete', 'manage', 'import', 'export', 'bulk'];

        $defs = FlowDefinition::with('appPermissions')
            ->when($this->option('app'), fn ($q) => $q->whereKey($this->option('app')))
            ->get();
        $users = User::where('retire', 0)->where('id', '>', 105)->get();

        $this->info(sprintf('scanning %d app(s) x %d user(s) — read-only', $defs->count(), $users->count()));

        $rows = [];
        $multi = 0;
        foreach ($defs as $def) {
            foreach ($users as $user) {
                $matching = $def->appPermissions->filter(
                    fn ($r) => $flow->matchesSubject($r->subject_type, $r->subject_id, $user, $def)
                );
                // only a user matching 2+ rows can possibly resolve differently
                if ($matching->count() < 2) {
                    continue;
                }
                $multi++;

                $old = $this->firstMatch($matching, $perms);
                $new = $this->byTier($matching, $perms);
                $gained = array_values(array_filter($perms, fn ($p) => ! $old[$p] && $new[$p]));
                $lost = array_values(array_filter($perms, fn ($p) => $old[$p] && ! $new[$p]));
                if (! $gained && ! $lost) {
                    continue;
                }
                $rows[] = [
                    $def->id,
                    mb_strimwidth($def->name, 0, 24, '…'),
                    mb_strimwidth($user->name, 0, 20, '…'),
                    $gained ? '+'.implode(',', $gained) : '',
                    $lost ? '-'.implode(',', $lost) : '',
                ];
            }
        }

        $this->line(sprintf('users matching 2+ rows: %d', $multi));

        if (! $rows) {
            $this->info('no user changes permissions under the new rule — safe to deploy as-is');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d user/app pair(s) would change:', count($rows)));
        $this->table(['app', 'name', 'user', 'gains', 'loses'], $rows);
        $this->warn('rows under "loses" are the ones to review: an individual row now REPLACES that');
        $this->warn('person\'s role row, so an unchecked box there removes what the role granted.');

        return self::SUCCESS;
    }

    /** The rule being replaced: the first matching row won outright. */
    private function firstMatch($matching, array $perms): array
    {
        $out = array_fill_keys($perms, false);
        $first = $matching->first();
        if ($first) {
            foreach ($perms as $p) {
                $out[$p] = (bool) $first->{'can_'.$p};
            }
        }

        return $out;
    }

    /** The new rule: most specific tier wins, rows inside it union. */
    private function byTier($matching, array $perms): array
    {
        $best = -1;
        $winning = [];
        foreach ($matching as $row) {
            $rank = self::RANK[$row->subject_type] ?? 1;
            if ($rank > $best) {
                $best = $rank;
                $winning = [$row];
            } elseif ($rank === $best) {
                $winning[] = $row;
            }
        }
        $out = array_fill_keys($perms, false);
        foreach ($winning as $row) {
            foreach ($perms as $p) {
                $out[$p] = $out[$p] || (bool) $row->{'can_'.$p};
            }
        }

        return $out;
    }
}
