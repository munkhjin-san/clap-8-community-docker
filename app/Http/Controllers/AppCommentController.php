<?php

namespace App\Http\Controllers;

use App\Models\AppComment;
use App\Models\FlowRecord;
use App\Models\Incident;
use App\Models\messageFile;
use App\Models\User;
use App\Services\FlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AppCommentController extends Controller
{
    private const COMMENTABLE_TYPES = [
        'incident' => Incident::class,
        'flow_record' => FlowRecord::class,
    ];


    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
        ]);

        $commentable = $this->resolveCommentable($validated['type'], (int) $validated['id']);
        $this->authorizeCommentable($commentable);

        return $commentable->comments()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
            'content' => ['required', 'string'],
            'attached_temp_files' => ['sometimes', 'array'],
            'attached_temp_files.*.id' => ['required_with:attached_temp_files', 'integer', 'exists:message_files,id'],
        ]);

        $commentable = $this->resolveCommentable($validated['type'], (int) $validated['id']);
        $this->authorizeCommentable($commentable);
        $activeUser = Auth::user();

        $comment = $commentable->comments()->create([
            'user_id' => $activeUser->id,
            'content' => $validated['content'],
            'mentioned_user_ids' => $this->mentionedUserIds($validated['content']),
        ]);

        foreach ($validated['attached_temp_files'] ?? [] as $item) {
            $this->attachTempFile($comment, (int) $item['id']);
        }

        return $comment->load(['user', 'files']);
    }

    public function mentionableUsers()
    {
        return User::query()
            ->select('id', 'name', 'icon_path', 'icon_bg')
            ->where('retire', 0)
            ->where('hide_flag', 0)
            ->whereNot('id', Auth::user()->id)
            ->orderBy('name')
            ->get();
    }

    private function resolveCommentable(string $type, int $id): Model
    {
        $model = self::COMMENTABLE_TYPES[$type] ?? null;

        if (!$model) {
            abort(404);
        }

        return $model::findOrFail($id);
    }

    private function authorizeCommentable(Model $commentable): void
    {
        $user = Auth::user();
        if ($commentable instanceof Incident && !$this->canAccessIncident($commentable, $user)) {
            abort(403);
        }
        if ($commentable instanceof FlowRecord) {
            $commentable->loadMissing(['definition.appPermissions', 'definition.recordPermissionSets']);
            abort_unless(app(FlowService::class)->recordPermissions($user, $commentable, $commentable->definition)['view'], 403);
        }
    }

    private function canAccessIncident(Incident $incident, User $user): bool
    {
        $isPM = $user->isPM();
        $isBoss = $user->isBoss();
        $isAdmin = $user->isAdmin();

        if ($isBoss || $isAdmin) {
            return true;
        }

        if (
            $incident->caused_by === $user->id
            || $incident->reported_by === $user->id
        ) {
            return true;
        }

        if ($this->hasActiveIncidentAssignment($incident, $user)) {
            return true;
        }

        if ($isPM) {
            return $incident->projectRecord()
                ->whereHas('manager', function ($managerQuery) use ($user) {
                    $managerQuery->where('users.id', $user->id);
                })
                ->exists();
        }

        return false;
    }

    private function hasActiveIncidentAssignment(Incident $incident, User $user): bool
    {
        if ($incident->status === '完了') {
            return false;
        }

        $incident->loadMissing('reports.assignees');
        $latestReport = $incident->reports
            ->sort(fn ($a, $b) => [$b->step, $b->id] <=> [$a->step, $a->id])
            ->first();

        if (!$latestReport) {
            return false;
        }

        return $latestReport->assignees->contains(fn ($assignee) => $assignee->user_id === $user->id);
    }

    private function mentionedUserIds(string $content): array
    {
        preg_match_all('/\[To:(.*?)\:\]/', $content, $matches);
        $names = array_values(array_unique($matches[1] ?? []));

        if (empty($names)) {
            return [];
        }

        return User::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->values()
            ->all();
    }

    private function attachTempFile(AppComment $comment, int $fileId): void
    {
        $file = messageFile::findOrFail($fileId);
        $srcPath = "{$file->id}.{$file->extension}";
        $tempPath = storage_path("app/temp_upload/{$srcPath}");

        if (!file_exists($tempPath)) {
            return;
        }

        $path = "app_comment_files/{$comment->id}";
        File::isDirectory(storage_path("app/{$path}")) or File::makeDirectory(storage_path("app/{$path}"), 0755, true, true);

        $destPath = "{$file->id}_{$file->user_id}.{$file->extension}";
        Storage::disk('local')->move("temp_upload/{$srcPath}", "{$path}/{$destPath}");

        $file->update(['app_comment_id' => $comment->id]);
    }
}
