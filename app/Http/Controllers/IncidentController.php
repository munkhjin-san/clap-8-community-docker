<?php

namespace App\Http\Controllers;

use App\Ai\Agents\IncidentAdvisor;
use App\Ai\Agents\IncidentConclusionAdvisor;
use App\Exports\IncidentData;
use App\Models\FileRecord;
use App\Models\Incident;
use App\Models\IncidentAdvice;
use App\Models\IncidentAssignee;
use App\Models\IncidentCandidate;
use App\Models\IncidentCategory;
use App\Models\IncidentPunishment;
use App\Models\IncidentReport;
use App\Models\IncidentStatus;
use App\Models\ProjectRecord;
use App\Models\TimecardMissingOccurrence;
use App\Models\User;
use App\Models\UserReadHistory;
use App\Services\IncidentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Maatwebsite\Excel\Facades\Excel;

class IncidentController extends Controller
{
    public function __construct(private IncidentService $incidentService)
    {
    }

    private function active_user()
    {
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();

        if ($sub) {
            return $sub;
        }

        return Auth::user();
    }

    private function ensureCanManageIncidentSettings(): void
    {
        abort_unless($this->incidentService->canManageIncidentAdministration($this->active_user()), 403);
    }

    public function getIncidents(Request $request)
    {
        $activeUser = $this->active_user();

        if (!$this->incidentService->canViewIncidentList($activeUser)) {
            abort(403);
        }

        $query = $this->incidentService->incidentListQuery($activeUser);
        $this->incidentService->applyIncidentFilters($query, $request);

        return $this->incidentService->orderIncidentList($query)
            ->with([
                    'readHistories' => function ($readQuery) use ($activeUser) {
                        $readQuery->where('user_id', $activeUser->id);
                },
            ])
            ->withCount([
                'logs as unread_update_logs_count' => function ($logQuery) use ($activeUser) {
                    $logQuery
                        ->where(function ($ownerQuery) use ($activeUser) {
                            $ownerQuery->whereNull('update_logs.user_id')
                                ->orWhere('update_logs.user_id', '!=', $activeUser->id);
                        })
                        ->whereRaw(
                            'update_logs.created_at > COALESCE((
                            SELECT user_read_histories.last_read_at
                            FROM user_read_histories
                            WHERE user_read_histories.readable_type = ?
                                AND user_read_histories.readable_id = update_logs.loggable_id
                                AND user_read_histories.user_id = ?
                            LIMIT 1
                        ), ?)',
                            [Incident::class, $activeUser->id, '1970-01-01 00:00:00'],
                        );
                },
            ])
            ->paginate((int) $request->input('per_page', 50));
    }

    public function exportIncidentCsv(Request $request)
    {
        $activeUser = $this->active_user();

        if (!$this->incidentService->canManageIncidentAdministration($activeUser)) {
            abort(403);
        }

        $query = $this->incidentService->incidentListQuery($activeUser);
        $this->incidentService->applyIncidentFilters($query, $request);

        $incidents = $this->incidentService->orderIncidentList($query)
            ->with([
                'comments.user',
                'logs.user',
            ])
            ->get();

        $headers = [
            'ID',
            '発生日',
            '報告日',
            '作成日',
            '更新日',
            '当事者',
            '報告者',
            'プロジェクト',
            'PM',
            '区分',
            'ステータス',
            '懲罰区分',
            'ポイント',
            'リスクレベル',
            '損害レベル',
            '現在の担当者',
            '関係者',
            '概要',
            '発生場所',
            '原因',
            '再発防止策',
            '再発防止策の実施状況',
            '是正対応',
            'メモ',
            '指導日',
            '指導内容',
            '委員会メンバー',
            '委員会決定日',
            '委員会決定',
            '損害額',
            '支払先',
            '費用詳細',
            '顛末コメント',
            'コメント数',
            'コメント内容',
            '対応ログ',
            '添付ファイル',
            'AIアドバイス数',
            'AI総括数',
            '更新履歴数',
            '非公開メモ',
        ];

        $rows = $incidents->map(function (Incident $incident) {
            $latestReport = $incident->reports
                ->sortByDesc(fn ($report) => (($report->step ?? 0) * 1000000) + $report->id)
                ->first();

            $currentAssignees = $incident->status === '完了'
                ? collect()
                : collect($latestReport?->assignees ?? [])
                    ->map(fn ($assignee) => $assignee->user?->name)
                    ->filter();

            if ($currentAssignees->isEmpty() && $incident->status !== '完了') {
                $currentAssignees = collect($incident->projectRecord?->manager ?? [])
                    ->map(fn ($manager) => $manager->name)
                    ->filter();
            }

            return [
                $incident->id,
                $this->incidentService->formatIncidentExportDate($incident->occurred_date),
                $this->incidentService->formatIncidentExportDate($incident->reported_date),
                $this->incidentService->formatIncidentExportDateTime($incident->created_at),
                $this->incidentService->formatIncidentExportDateTime($incident->updated_at),
                $incident->causedByUser?->name,
                $incident->reportedByUser?->name,
                $incident->projectRecord?->name,
                collect($incident->projectRecord?->manager ?? [])->pluck('name')->filter()->join('、'),
                $incident->category?->name,
                $incident->status ?: '未設定',
                $incident->punishment?->name,
                ((int) ($incident->risk_level ?? 0)) * ((int) ($incident->severity_level ?? 0)),
                $incident->risk_level,
                $incident->severity_level,
                $currentAssignees->join('、'),
                $incident->related_parties,
                $incident->description,
                $incident->occured_location,
                $incident->reason,
                $incident->prevention,
                $incident->prevention_apply_status,
                $incident->resolution,
                $incident->memo,
                $this->incidentService->formatIncidentExportDate($incident->instruction_date),
                $incident->instruction,
                $incident->committee_members,
                $this->incidentService->formatIncidentExportDate($incident->committee_decision_date),
                $incident->committee_decision,
                $incident->amount_of_damage,
                $incident->payee,
                $incident->expense_details,
                $incident->aftermath_comment,
                $incident->comments_count,
                $incident->comments->map(function ($comment) {
                    return collect([
                        $this->incidentService->formatIncidentExportDateTime($comment->created_at),
                        $comment->user?->name ?: '不明',
                        $comment->content,
                    ])->filter(fn ($value) => $value !== null && $value !== '')->join(' ');
                })->join("\n"),
                $incident->reports->map(function ($report) {
                    $assignees = collect($report->assignees ?? [])
                        ->map(fn ($assignee) => $assignee->user?->name)
                        ->filter()
                        ->join('、');

                    return collect([
                        'Step '.($report->step ?: '-'),
                        '依頼: '.($report->request ?: '-'),
                        '担当: '.($assignees ?: '-'),
                        '報告: '.($report->report ?: '-'),
                        '完了: '.$this->incidentService->formatIncidentExportDateTime($report->completed_at),
                    ])->join(' / ');
                })->join("\n"),
                $incident->files->pluck('name')->filter()->join("\n"),
                $incident->advices->where('type', 'resolution')->count(),
                $incident->advices->where('type', 'conclusion')->count(),
                $incident->logs->count(),
                $incident->private_notes,
            ];
        })->toArray();

        return Excel::download(new IncidentData($rows, $headers), 'incidents.xlsx');
    }

    public function getIncidentPage(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $activeUser = $this->active_user();

        if (!$this->incidentService->canViewIncidentList($activeUser)) {
            abort(403);
        }

        $perPage = (int) ($validated['per_page'] ?? 50);
        $query = $this->incidentService->incidentListQuery($activeUser);
        $this->incidentService->applyIncidentFilters($query, $request);

        $ids = $this->incidentService->orderIncidentList($query)
            ->pluck('id')
            ->values();
        $index = $ids->search((int) $validated['id']);

        if ($index === false) {
            abort(404);
        }

        return response()->json([
            'id' => (int) $validated['id'],
            'page' => (int) floor($index / $perPage) + 1,
        ]);
    }

    public function getIncidentOptions()
    {
        $activeUser = $this->active_user();
        $canManage = $this->incidentService->canManageIncidentAdministration($activeUser);
        $canView = $this->incidentService->canViewIncidentList($activeUser);
        $filterQuery = $this->incidentService->incidentListQuery($activeUser);
        $filterUserIds = (clone $filterQuery)->pluck('caused_by')
            ->merge((clone $filterQuery)->pluck('reported_by'))
            ->filter()
            ->unique()
            ->values();
        $filterProjectIds = (clone $filterQuery)->pluck('project_record_id')
            ->filter()
            ->unique()
            ->values();
        $filterStatuses = (clone $filterQuery)
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values();

        return response()->json([
            'categories' => IncidentCategory::query()
                ->select('id', 'name', 'description', 'sort_order')
                ->orderByRaw('sort_order is null')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'punishments' => IncidentPunishment::query()
                ->select('id', 'name', 'description', 'sort_order')
                ->orderByRaw('sort_order is null')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'users' => User::query()
                ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id')
                ->where('retire', 0)
                ->where('hide_flag', 0)
                ->where('id', '>', 105)
                ->whereNotNull('position_id')
                ->orderBy('position_id')
                ->get(),
            'projects' => ProjectRecord::query()
                ->select('id', 'name', 'date_start', 'date_end', 'category')
                ->orderByRaw(
                    'exists (select 1 from project_members where project_members.project_id = project_records.id and project_members.user_id = ?) desc',
                    [$activeUser->id]
                )
                ->orderByDesc('created_at')
                ->get(),
            'filter_users' => User::query()
                ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id')
                ->whereIn('id', $filterUserIds)
                ->get(),
            'filter_projects' => ProjectRecord::query()
                ->select('id', 'name', 'date_start', 'date_end', 'category')
                ->whereIn('id', $filterProjectIds)
                ->orderByDesc('created_at')
                ->get(),
            'statuses' => $this->incidentService->incidentStatusNames($filterStatuses),
            'can_manage' => $canManage,
            'can_view' => $canView,
        ]);
    }

    public function getIncidentSettings()
    {
        $this->ensureCanManageIncidentSettings();

        return response()->json([
            'categories' => $this->incidentService->incidentCategoryList(),
            'statuses' => $this->incidentService->incidentStatusList(),
            'punishments' => $this->incidentService->incidentPunishmentList(),
        ]);
    }

    public function createIncidentCategory(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = IncidentCategory::create([
            ...$validated,
            'sort_order' => $this->incidentService->nextSortOrder(IncidentCategory::query()),
        ]);

        return response()->json($category);
    }

    public function updateIncidentCategory(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:incident_categories,name,' . $request->input('id')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = IncidentCategory::findOrFail($validated['id']);
        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($category);
    }

    public function deleteIncidentCategory(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_categories,id'],
        ]);

        $category = IncidentCategory::findOrFail($validated['id']);
        abort_if($category->incidents()->exists(), 422, '利用中の区分は削除できません。');
        $category->delete();

        return response()->json(['deleted' => true]);
    }

    public function reorderIncidentCategories(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:incident_categories,id'],
        ]);

        $this->incidentService->persistSortOrder(IncidentCategory::class, $validated['ids']);

        return response()->json(['updated' => true]);
    }

    public function createIncidentStatus(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_statuses,name'],
        ]);

        $status = IncidentStatus::create([
            'name' => $validated['name'],
            'sort_order' => $this->incidentService->nextSortOrder(IncidentStatus::query()),
        ]);

        return response()->json($status);
    }

    public function updateIncidentStatus(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_statuses,id'],
            'name' => ['required', 'string', 'max:255', 'unique:incident_statuses,name,' . $request->input('id')],
        ]);

        $status = IncidentStatus::findOrFail($validated['id']);
        DB::transaction(function () use ($status, $validated) {
            $oldName = $status->name;
            $status->update(['name' => $validated['name']]);

            if ($oldName !== $validated['name']) {
                Incident::where('status', $oldName)->update(['status' => $validated['name']]);
            }
        });

        return response()->json($status->fresh());
    }

    public function deleteIncidentStatus(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_statuses,id'],
        ]);

        $status = IncidentStatus::findOrFail($validated['id']);
        abort_if(Incident::where('status', $status->name)->exists(), 422, '利用中のステータスは削除できません。');
        $status->delete();

        return response()->json(['deleted' => true]);
    }

    public function reorderIncidentStatuses(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:incident_statuses,id'],
        ]);

        $this->incidentService->persistSortOrder(IncidentStatus::class, $validated['ids']);

        return response()->json(['updated' => true]);
    }

    public function createIncidentPunishment(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_punishments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $punishment = IncidentPunishment::create([
            ...$validated,
            'sort_order' => $this->incidentService->nextSortOrder(IncidentPunishment::query()),
        ]);

        return response()->json($punishment);
    }

    public function updateIncidentPunishment(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_punishments,id'],
            'name' => ['required', 'string', 'max:255', 'unique:incident_punishments,name,' . $request->input('id')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $punishment = IncidentPunishment::findOrFail($validated['id']);
        $punishment->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($punishment);
    }

    public function deleteIncidentPunishment(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_punishments,id'],
        ]);

        $punishment = IncidentPunishment::findOrFail($validated['id']);
        abort_if($punishment->incidents()->exists(), 422, '利用中の懲罰区分は削除できません。');
        $punishment->delete();

        return response()->json(['deleted' => true]);
    }

    public function reorderIncidentPunishments(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:incident_punishments,id'],
        ]);

        $this->incidentService->persistSortOrder(IncidentPunishment::class, $validated['ids']);

        return response()->json(['updated' => true]);
    }

    public function getIncidentLogs(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
            'read_before' => ['sometimes', 'nullable', 'date'],
        ]);

        $incident = Incident::findOrFail($validated['id']);
        $activeUser = $this->active_user();

        if (
            !$this->incidentService->canAccessIncident($incident, $activeUser)
            || !$this->incidentService->canViewIncidentHistory($activeUser)
        ) {
            abort(403);
        }

        $readBefore = array_key_exists('read_before', $validated)
            ? ($validated['read_before'] ? Carbon::parse($validated['read_before']) : null)
            : optional($incident->readHistories()->where('user_id', $activeUser->id)->first())->last_read_at;

        return $this->incidentService->resolveIncidentLogDisplayValues($incident->logs()->get(), $readBefore);
    }

    public function markIncidentRead(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
        ]);

        $activeUser = $this->active_user();
        $incident = Incident::findOrFail($validated['id']);

        if (!$this->incidentService->canAccessIncident($incident, $activeUser)) {
            abort(403);
        }

        $readHistory = UserReadHistory::updateOrCreate(
            [
                'readable_type' => Incident::class,
                'readable_id' => $incident->id,
                'user_id' => $activeUser->id,
            ],
            [
                'last_read_at' => now(),
            ],
        );

        return response()->json([
            'last_read_at' => $readHistory->last_read_at?->toDateTimeString(),
        ]);
    }

    public function createIncidentAdvice(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => ['required', 'integer', 'exists:incidents,id'],
            'type' => ['required', 'string', 'in:resolution,conclusion'],
            'content' => ['required', 'string'],
        ]);

        $activeUser = $this->active_user();
        $incident = Incident::findOrFail($validated['incident_id']);

        if (!$this->incidentService->canAccessIncident($incident, $activeUser) || !$this->incidentService->canViewIncidentHistory($activeUser)) {
            abort(403);
        }
        if ($validated['type'] === 'conclusion' && (!$this->incidentService->canManageIncidentAdministration($activeUser) || !$this->incidentService->isCompletedIncident($incident))) {
            abort(403);
        }

        $advice = IncidentAdvice::create([
            'incident_id' => $incident->id,
            'type' => $validated['type'],
            'content' => $validated['content'],
            'created_by' => $activeUser->id,
        ])->load('creator');

        return response()->json([
            'advice' => $advice,
            'created' => true,
        ]);
    }

    public function deleteIncidentAdvice(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_advice,id'],
        ]);

        $activeUser = $this->active_user();
        $advice = IncidentAdvice::with('incident')->findOrFail($validated['id']);
        $incident = $advice->incident;

        if (
            !$incident
            || !$this->incidentService->canAccessIncident($incident, $activeUser)
            || !$this->incidentService->canViewIncidentHistory($activeUser)
            || ($advice->type === 'conclusion' && !$this->incidentService->canManageIncidentAdministration($activeUser))
        ) {
            abort(403);
        }

        $advice->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    public function getIncidentAdvices(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => ['required', 'integer', 'exists:incidents,id'],
            'type' => ['sometimes', 'nullable', 'string', 'in:resolution,conclusion'],
        ]);

        $activeUser = $this->active_user();
        $incident = Incident::findOrFail($validated['incident_id']);

        if (!$this->incidentService->canAccessIncident($incident, $activeUser) || !$this->incidentService->canViewIncidentHistory($activeUser)) {
            abort(403);
        }
        if (($validated['type'] ?? null) === 'conclusion' && !$this->incidentService->canManageIncidentAdministration($activeUser)) {
            abort(403);
        }

        return response()->json([
            'advices' => $incident->advices()
                ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
                ->get(),
        ]);
    }
    public function incidentRelatedMentionableUsers(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => ['required', 'integer', 'exists:incidents,id'],
        ]);

        $activeUser = $this->active_user();
        $incident = Incident::findOrFail($validated['incident_id']);
        $userBank = collect();
        if ($incident->reportedByUser) {
            $userBank->push($incident->reportedByUser);
        }
        if ($incident->causedByUser) {
            $userBank->push($incident->causedByUser);  
        }

        if ($incident->reports) {
            foreach ($incident->reports as $report) {
                if ($report->assignees) {
                    foreach ($report->assignees as $assignee) {
                        $userBank->push($assignee->user);
                    }
                }
            }
        }
        $permanentMembers = User::where('position_id', '<', 6)->orWhere('name', '経営管理本部')->select('id', 'name', 'icon_bg','icon_path', 'position_id')->get();
        $userBank = $userBank->merge($permanentMembers);
        if($incident->projectRecord && $incident->projectRecord->manager){
            foreach($incident->projectRecord->manager as $manager){
                $userBank->push($manager);
            }
        }

        return response()->json($userBank->unique('id')->values());
    }
    public function streamIncidentAdvice(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => ['required', 'integer', 'exists:incidents,id'],
            'type' => ['sometimes', 'nullable', 'string', 'in:resolution,conclusion'],
        ]);

        $activeUser = $this->active_user();
        $incident = Incident::with([
            'reportedByUser',
            'causedByUser',
            'category',
            'punishment',
            'projectRecord.manager',
            'reports.assignees.user',
            'comments.user',
        ])->findOrFail($validated['incident_id']);

        if (!$this->incidentService->canAccessIncident($incident, $activeUser) || !$this->incidentService->canViewIncidentHistory($activeUser)) {
            abort(403);
        }

        $type = $validated['type'] ?? 'resolution';
        if ($type === 'conclusion' && (!$this->incidentService->canManageIncidentAdministration($activeUser) || !$this->incidentService->isCompletedIncident($incident))) {
            abort(403);
        }

        $prompt = $this->buildIncidentAdvicePrompt($incident, $type);
        $agent = $type === 'conclusion'
            ? IncidentConclusionAdvisor::make()
            : IncidentAdvisor::make();

        return $agent->stream($prompt, timeout: 120)
            ->then(function (StreamedAgentResponse $response) use ($incident, $activeUser, $type) {
                $content = trim($response->text ?? '');

                if ($content === '') {
                    return;
                }

                IncidentAdvice::create([
                    'incident_id' => $incident->id,
                    'type' => $type,
                    'content' => $content,
                    'created_by' => $activeUser->id,
                ]);
            });
    }

    private function buildIncidentAdvicePrompt(Incident $incident, string $type): string
    {
        $purpose = $type === 'conclusion'
            ? '完了済みインシデントの振り返りと要約'
            : '解決方針と再発防止策の提案';

        return collect([
            "目的: {$purpose}",
            '以下のインシデント情報をもとに、入力されていない項目は推測で補わず、確認すべき点として扱ってください。',
            '',
            '## インシデント情報',
            'ステータス: '.($incident->status ?: '未設定'),
            '発生日: '.($incident->occurred_date ?: '未設定'),
            '報告日: '.($incident->reported_date ?: '未設定'),
            '区分: '.($incident->category?->name ?: '未設定'),
            '懲罰区分: '.($incident->punishment?->name ?: '未設定'),
            '当事者: '.($incident->causedByUser?->name ?: '未設定'),
            '報告者: '.($incident->reportedByUser?->name ?: '未設定'),
            'プロジェクト: '.($incident->projectRecord?->name ?: '未設定'),
            '関係者: '.($incident->related_parties ?: '未設定'),
            '概要: '.($incident->description ?: '未入力'),
            '発生場所: '.($incident->occured_location ?: '未入力'),
            '原因: '.($incident->reason ?: '未入力'),
            '再発防止策: '.($incident->prevention ?: '未入力'),
            '再発防止策の実施状況: '.($incident->prevention_apply_status ?: '未入力'),
            '是正対応: '.($incident->resolution ?: '未入力'),
            'メモ: '.($incident->memo ?: '未入力'),
            '事後コメント: '.($incident->aftermath_comment ?: '未入力'),
            '対応履歴: '.$incident->reports->map(function ($report) {
                $assignees = $report->assignees
                    ->map(fn ($assignee) => $assignee->user?->name)
                    ->filter()
                    ->join('、');
                $assigneeReports = $report->assignees
                    ->map(function ($assignee) {
                        return collect([
                            '担当者: '.($assignee->user?->name ?: '不明'),
                            '対応内容: '.($assignee->report ?: '未入力'),
                            '完了日時: '.($assignee->completed_at ?: '未完了'),
                        ])->join(' / ');
                    })
                    ->join(' | ');

                return collect([
                    'step '.($report->step ?: '-'),
                    '依頼: '.($report->request ?: '未入力'),
                    '報告: '.($report->report ?: '未入力'),
                    '担当: '.($assignees ?: '未設定'),
                    '担当者対応: '.($assigneeReports ?: '未入力'),
                    '完了: '.($report->completed_at ?: '未完了'),
                ])->join(' / ');
            })->join("\n"),
            'コメント: '.$incident->comments->map(function ($comment) {
                return ($comment->created_at?->format('Y-m-d H:i') ?: '-')
                    .' '
                    .($comment->user?->name ?: '不明')
                    .': '
                    .str($comment->content ?? '')->limit(300);
            })->join("\n"),
        ])->join("\n");
    }

    /**
     * Record a reviewer's decision on a dashboard incident candidate:
     *  - create_incident: link the formal incident created from the candidate.
     *  - dismiss: mark "not an incident" with a mandatory reason.
     * Either way the decision is stamped and appended to the candidate's update log.
     */
    public function decideIncidentCandidate(Request $request)
    {
        $activeUser = $this->active_user();

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'exists:incident_candidates,id'],
            'decision' => ['required', 'string', 'in:create_incident,dismiss'],
            'reason' => ['required_if:decision,dismiss', 'nullable', 'string', 'max:2000'],
            'resulting_incident_id' => ['required_if:decision,create_incident', 'nullable', 'integer', 'exists:incidents,id'],
        ]);

        $candidate = IncidentCandidate::findOrFail($validated['candidate_id']);

        if (!$this->canDecideIncidentCandidate($activeUser, $candidate)) {
            abort(403);
        }

        if ($candidate->status !== IncidentCandidate::STATUS_PENDING) {
            return response()->json(['message' => 'この項目はすでに処理済みです。'], 422);
        }

        $now = Carbon::now();
        $decision = $validated['decision'];

        DB::transaction(function () use ($candidate, $decision, $validated, $activeUser, $now) {
            if ($decision === 'dismiss') {
                $candidate->update([
                    'status' => IncidentCandidate::STATUS_DISMISSED,
                    'decision_reason' => $validated['reason'],
                    'decided_by' => $activeUser->id,
                    'decided_at' => $now,
                ]);

                $candidate->logs()->create([
                    'user_id' => $activeUser->id,
                    'action' => 'dismissed',
                    'note' => $validated['reason'],
                ]);
            } else {
                $candidate->update([
                    'status' => IncidentCandidate::STATUS_INCIDENT_CREATED,
                    'resulting_incident_id' => $validated['resulting_incident_id'],
                    'decided_by' => $activeUser->id,
                    'decided_at' => $now,
                ]);

                $candidate->logs()->create([
                    'user_id' => $activeUser->id,
                    'action' => 'incident_created',
                    'changes' => ['resulting_incident_id' => $validated['resulting_incident_id']],
                ]);
            }

            // Resolve the underlying missed-report rows once a streak candidate is decided.
            if ($candidate->source_type === IncidentCandidate::SOURCE_DAILY_REPORT_STREAK) {
                $occurrenceIds = $candidate->context['occurrence_ids'] ?? [];

                if (!empty($occurrenceIds)) {
                    TimecardMissingOccurrence::whereIn('id', $occurrenceIds)
                        ->whereNull('resolved_at')
                        ->update(['resolved_at' => $now]);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'candidate' => $candidate->fresh(['subject', 'project', 'decidedByUser']),
        ]);
    }

    private function canDecideIncidentCandidate(User $user, IncidentCandidate $candidate): bool
    {
        // Directors/executives and admins oversee everything.
        if ($this->incidentService->canManageIncidentAdministration($user)) {
            return true;
        }

        // PMs may only decide pm-audience candidates for projects they manage.
        if ($candidate->audience !== IncidentCandidate::AUDIENCE_PM) {
            return false;
        }

        if ($user->position_id != 6 || !$candidate->project_record_id) {
            return false;
        }

        return ProjectRecord::where('id', $candidate->project_record_id)
            ->whereHas('manager', fn ($managerQuery) => $managerQuery->where('users.id', $user->id))
            ->exists();
    }

    /**
     * Mark dismissed incident candidates as reviewed by the active user
     * (admin oversight read-tracking, mirrors the auto-approved daily report pattern).
     */
    public function markIncidentCandidatesRead(Request $request)
    {
        $activeUser = $this->active_user();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach (array_unique($validated['ids']) as $id) {
            UserReadHistory::updateOrCreate(
                [
                    'readable_type' => IncidentCandidate::class,
                    'readable_id' => (int) $id,
                    'user_id' => $activeUser->id,
                ],
                [
                    'last_read_at' => now(),
                ],
            );
        }

        return response()->json(['ok' => true]);
    }

    public function createIncidentRecord(Request $request)
    {
        $activeUser = $this->active_user();

        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string'],
            'description' => ['required', 'string'],
            'reported_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'caused_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'incident_category_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_categories,id'],
            'incident_punishment_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_punishments,id'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'prevention' => ['sometimes', 'nullable', 'string'],
            'prevention_apply_status' => ['sometimes', 'nullable', 'string'],
            'instruction' => ['sometimes', 'nullable', 'string'],
            'resolution' => ['sometimes', 'nullable', 'string'],
            'occured_location' => ['sometimes', 'nullable', 'string'],
            'memo' => ['sometimes', 'nullable', 'string'],
            'aftermath_comment' => ['sometimes', 'nullable', 'string'],
            'occurred_date' => ['required', 'date'],
            'reported_date' => ['required', 'date'],
            'instruction_date' => ['sometimes', 'nullable', 'date'],
            'related_parties' => ['sometimes', 'nullable', 'string'],
            'project_record_id' => ['sometimes', 'nullable', 'integer', 'exists:project_records,id'],
            'status' => ['sometimes', 'nullable', 'string'],
            'amount_of_damage' => ['sometimes', 'nullable', 'numeric'],
            'payee' => ['sometimes', 'nullable', 'string'],
            'expense_details' => ['sometimes', 'nullable', 'string'],
            'risk_level' => ['sometimes', 'nullable', 'integer'],
            'severity_level' => ['sometimes', 'nullable', 'integer'],
            'private_notes' => ['sometimes', 'nullable', 'string'],
            'committee_members' => ['sometimes', 'nullable', 'string'],
            'committee_decision' => ['sometimes', 'nullable', 'string'],
            'committee_decision_date' => ['sometimes', 'nullable', 'date'],
            'file_ids' => ['sometimes', 'array'],
            'file_ids.*' => ['integer', 'distinct', 'exists:file_records,id'],
            'assignment_request' => ['sometimes', 'nullable', 'string'],
            'assignee_ids' => ['sometimes', 'array', 'min:1'],
            'assignee_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $fileIds = $validated['file_ids'] ?? [];
        $assignmentRequest = $validated['assignment_request'] ?? null;
        $assigneeIds = $validated['assignee_ids'] ?? null;
        $reportedBy = $validated['reported_by'] ?? null;
        unset($validated['file_ids']);
        unset($validated['assignment_request'], $validated['assignee_ids']);
        unset($validated['reported_by']);

        if (!$this->incidentService->canCreateIncidentRecord($activeUser) || $this->incidentService->hasDisallowedIncidentFields($activeUser, array_keys($validated))) {
            abort(403);
        }

        if ($assigneeIds !== null && !$this->incidentService->canManageIncidentWorkflow($activeUser)) {
            abort(403);
        }

        $createdIncident = DB::transaction(function () use ($validated, $fileIds, $assignmentRequest, $assigneeIds, $reportedBy, $activeUser) {
            $incident = Incident::create([
                ...$validated,
                'reported_by' => $reportedBy ?? $activeUser->id,
                'status' => $validated['status'] ?? '処分未決定',
            ]);

            if (!empty($fileIds)) {
                $incident->files()->syncWithPivotValues($fileIds, [
                    'attachable_type' => Incident::class,
                    'collection' => 'attachments',
                ]);
            }

            $this->incidentService->createInitialIncidentReport($incident, $activeUser, $assigneeIds, $assignmentRequest);

            $changes = collect($validated)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => ['old' => null, 'new' => $value])
                ->all();

            if (!empty($fileIds)) {
                $changes['files'] = ['old' => [], 'new' => $fileIds];
            }
            if (!array_key_exists('status', $changes) && $incident->status) {
                $changes['status'] = ['old' => null, 'new' => $incident->status];
            }
            if (!empty($assigneeIds)) {
                $changes['incident_assignees'] = ['old' => [], 'new' => array_values($assigneeIds)];
            }

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => 'created',
                'changes' => $changes,
            ]);

            return $this->incidentService->incidentQuery(true, $activeUser->id)->whereKey($incident->id)->firstOrFail();
        });

        return response()->json([
            'incident' => $createdIncident,
            'created' => true,
        ]);
    }

    public function updateIncidentRecord(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
            'title' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'required', 'string'],
            'reported_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'caused_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'incident_category_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_categories,id'],
            'incident_punishment_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_punishments,id'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'prevention' => ['sometimes', 'nullable', 'string'],
            'prevention_apply_status' => ['sometimes', 'nullable', 'string'],
            'instruction' => ['sometimes', 'nullable', 'string'],
            'resolution' => ['sometimes', 'nullable', 'string'],
            'occured_location' => ['sometimes', 'nullable', 'string'],
            'memo' => ['sometimes', 'nullable', 'string'],
            'aftermath_comment' => ['sometimes', 'nullable', 'string'],
            'occurred_date' => ['sometimes', 'required', 'date'],
            'reported_date' => ['sometimes', 'required', 'date'],
            'instruction_date' => ['sometimes', 'nullable', 'date'],
            'related_parties' => ['sometimes', 'nullable', 'string'],
            'project_record_id' => ['sometimes', 'nullable', 'integer', 'exists:project_records,id'],
            'status' => ['sometimes', 'nullable', 'string'],
            'amount_of_damage' => ['sometimes', 'nullable', 'numeric'],
            'payee' => ['sometimes', 'nullable', 'string'],
            'expense_details' => ['sometimes', 'nullable', 'string'],
            'risk_level' => ['sometimes', 'nullable', 'integer'],
            'severity_level' => ['sometimes', 'nullable', 'integer'],
            'private_notes' => ['sometimes', 'nullable', 'string'],
            'committee_members' => ['sometimes', 'nullable', 'string'],
            'committee_decision' => ['sometimes', 'nullable', 'string'],
            'committee_decision_date' => ['sometimes', 'nullable', 'date'],
            'file_ids' => ['sometimes', 'array'],
            'file_ids.*' => ['integer', 'distinct', 'exists:file_records,id'],
        ]);

        $incidentId = $validated['id'];
        unset($validated['id']);
        $fileIds = $validated['file_ids'] ?? null;
        unset($validated['file_ids']);
        $activeUser = $this->active_user();

        if (empty($validated) && $fileIds === null) {
            return response()->json([
                'incident' => $this->incidentService->incidentQuery(true, $activeUser->id)->whereKey($incidentId)->firstOrFail(),
                'updated' => false,
            ]);
        }

        $updatedIncident = DB::transaction(function () use ($incidentId, $validated, $fileIds, $activeUser) {
            $incident = Incident::lockForUpdate()->findOrFail($incidentId);

            if (!$this->incidentService->canAccessIncident($incident, $activeUser)) {
                abort(403);
            }

            if (
                !$this->incidentService->canEditIncidentRecord($activeUser, $incident)
                || $this->incidentService->hasDisallowedIncidentFields($activeUser, array_keys($validated), $incident)
            ) {
                abort(403);
            }

            $changes = [];

            foreach ($validated as $field => $newValue) {
                $oldValue = $incident->getAttribute($field);

                if ($oldValue == $newValue) {
                    continue;
                }

                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];

                $incident->setAttribute($field, $newValue);
            }

            if ($fileIds !== null) {
                $currentFileIds = $incident->files()->pluck('file_records.id')->sort()->values()->all();
                $nextFileIds = collect($fileIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($currentFileIds !== $nextFileIds) {
                    $changes['files'] = [
                        'old' => $currentFileIds,
                        'new' => $nextFileIds,
                    ];
                }
            }

            if (empty($changes)) {
                return $this->incidentService->incidentQuery(true, $activeUser->id)->whereKey($incidentId)->firstOrFail();
            }

            $incident->save();

            if ($fileIds !== null) {
                $incident->files()->syncWithPivotValues($fileIds, [
                    'attachable_type' => Incident::class,
                    'collection' => 'attachments',
                ]);
            }

            $singleField = count($changes) === 1 ? array_key_first($changes) : null;

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => $singleField === 'status' ? 'status_changed' : 'updated',
                'field' => $singleField,
                'old_value' => $singleField ? $changes[$singleField]['old'] : null,
                'new_value' => $singleField ? $changes[$singleField]['new'] : null,
                'changes' => $changes,
            ]);

            return $this->incidentService->incidentQuery(true, $activeUser->id)->whereKey($incident->id)->firstOrFail();
        });

        return response()->json([
            'incident' => $updatedIncident,
            'updated' => true,
        ]);
    }

    public function deleteIncidentRecord(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
        ]);

        $activeUser = $this->active_user();

        DB::transaction(function () use ($validated, $activeUser) {
            $incident = Incident::lockForUpdate()->findOrFail($validated['id']);

            if (
                !$this->incidentService->canAccessIncident($incident, $activeUser)
                || !$this->incidentService->canDeleteIncidentRecord($activeUser, $incident)
            ) {
                abort(403);
            }

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => 'deleted',
                'changes' => [
                    'deleted_at' => [
                        'old' => null,
                        'new' => now()->toDateTimeString(),
                    ],
                ],
            ]);

            $incident->delete();
        });

        return response()->json([
            'deleted' => true,
            'id' => $validated['id'],
        ]);
    }

    public function saveIncidentAssigneeReport(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_assignees,id'],
            'report' => ['sometimes', 'nullable', 'string'],
        ]);

        $activeUser = $this->active_user();

        $incident = DB::transaction(function () use ($validated, $activeUser) {
            $assignee = IncidentAssignee::query()
                ->with('incidentReport.incident')
                ->lockForUpdate()
                ->findOrFail($validated['id']);
            $incident = $assignee->incidentReport->incident;

            if (!$this->incidentService->canUpdateIncidentAssigneeReport($activeUser, $assignee, $incident)) {
                abort(403);
            }

            $assignee->update([
                'report' => $validated['report'] ?? null,
            ]);

            return $this->incidentService->incidentQuery(true, $activeUser->id)->whereKey($incident->id)->firstOrFail();
        });

        return response()->json([
            'incident' => $incident,
            'saved' => true,
        ]);
    }

    public function completeIncidentAssigneeReport(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_assignees,id'],
            'report' => ['sometimes', 'nullable', 'string'],
        ]);

        $activeUser = $this->active_user();

        $incident = DB::transaction(function () use ($validated, $activeUser) {
            $assignee = IncidentAssignee::query()
                ->with('incidentReport.incident')
                ->lockForUpdate()
                ->findOrFail($validated['id']);
            $incident = $assignee->incidentReport->incident;

            if (!$this->incidentService->canUpdateIncidentAssigneeReport($activeUser, $assignee, $incident)) {
                abort(403);
            }

            $reportText = trim((string) ($validated['report'] ?? $assignee->report ?? ''));

            if ($reportText === '') {
                throw ValidationException::withMessages([
                    'report' => '対応内容を入力してください。',
                ]);
            }

            $assignee->update([
                'report' => $reportText,
                'completed_at' => $assignee->completed_at ?? now(),
            ]);

            $this->incidentService->markIncidentReportCompleteIfReady($assignee->incidentReport);

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => 'updated',
                'field' => 'incident_assignee_report',
                'changes' => [
                    'incident_assignee_report' => [
                        'old' => null,
                        'new' => 'completed',
                    ],
                ],
            ]);

            return $this->incidentService->incidentQuery(true, $activeUser->id)->whereKey($incident->id)->firstOrFail();
        });

        return response()->json([
            'incident' => $incident,
            'completed' => true,
        ]);
    }

    public function createIncidentReportAssignment(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => ['required', 'integer', 'exists:incidents,id'],
            'request' => ['sometimes', 'nullable', 'string'],
            'assignee_ids' => ['required', 'array', 'min:1'],
            'assignee_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $activeUser = $this->active_user();

        $incident = DB::transaction(function () use ($validated, $activeUser) {
            $incident = Incident::query()
                ->with('reports.assignees')
                ->lockForUpdate()
                ->findOrFail($validated['incident_id']);

            if (!$this->incidentService->canAccessIncident($incident, $activeUser) || $this->incidentService->isCompletedIncident($incident)) {
                abort(403);
            }

            $latestReport = $this->incidentService->latestIncidentReport($incident);

            if ($latestReport) {
                if (
                    !$this->incidentService->isIncidentReportComplete($latestReport)
                    || !$this->incidentService->canAdvanceIncidentReport($activeUser, $latestReport)
                ) {
                    abort(403);
                }
            } elseif (!$this->incidentService->canManageIncidentWorkflow($activeUser)) {
                abort(403);
            }

            $report = $incident->reports()->create([
                'step' => ($latestReport?->step ?? 0) + 1,
                'request' => $validated['request'] ?? null,
                'created_by' => $activeUser->id,
            ]);

            $this->incidentService->createIncidentAssignees($report, $validated['assignee_ids']);

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => 'updated',
                'field' => 'incident_assignees',
                'changes' => [
                    'incident_assignees' => [
                        'old' => [],
                        'new' => array_values($validated['assignee_ids']),
                    ],
                ],
            ]);

            return $this->incidentService->incidentQuery(true, $activeUser->id)->whereKey($incident->id)->firstOrFail();
        });

        return response()->json([
            'incident' => $incident,
            'created' => true,
        ]);
    }

}
