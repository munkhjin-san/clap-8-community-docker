<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentAssignee;
use App\Models\IncidentCategory;
use App\Models\IncidentPunishment;
use App\Models\IncidentReport;
use App\Models\IncidentStatus;
use App\Models\FileRecord;
use App\Models\ProjectRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function incidentQuery(bool $withDetail = true, ?int $readUserId = null)
    {
        $columns = [
            'id',
            'title',
            'description',
            'caused_by',
            'incident_category_id',
            'project_record_id',
            'status',
            'occurred_date',
            'created_at',
        ];

        if ($withDetail) {
            $columns = array_merge($columns, [
                'reported_by',
                'reported_date',
                'incident_punishment_id',
                'reason',
                'prevention',
                'prevention_apply_status',
                'instruction',
                'resolution',
                'occured_location',
                'memo',
                'aftermath_comment',
                'instruction_date',
                'related_parties',
                'amount_of_damage',
                'payee',
                'expense_details',
                'risk_level',
                'severity_level',
                'private_notes',
                'committee_members',
                'committee_decision',
                'committee_decision_date',
                'updated_at',
            ]);
        }

        return Incident::query()
            ->select(array_values(array_unique($columns)))
            ->when($readUserId, function ($query) use ($readUserId) {
                $query->selectSub(function ($readQuery) use ($readUserId) {
                    $readQuery->from('user_read_histories')
                        ->select('last_read_at')
                        ->whereColumn('readable_id', 'incidents.id')
                        ->where('readable_type', Incident::class)
                        ->where('user_id', $readUserId)
                        ->limit(1);
                }, 'last_read_at');
            })
            ->withCount([
                'comments',
                'comments as unread_comments_count' => function ($commentQuery) use ($readUserId) {
                    if (!$readUserId) {
                        $commentQuery->whereRaw('1 = 0');
                        return;
                    }

                    $commentQuery
                        ->whereRaw('incidents.created_at >= ?', ['2026-05-01 00:00:00'])
                        ->where(function ($ownerQuery) use ($readUserId) {
                            $ownerQuery->whereNull('app_comments.user_id')
                                ->orWhere('app_comments.user_id', '!=', $readUserId);
                        })
                        ->whereRaw(
                            'app_comments.created_at > COALESCE((
                            SELECT user_read_histories.last_read_at
                            FROM user_read_histories
                            WHERE user_read_histories.readable_type = ?
                                AND user_read_histories.readable_id = incidents.id
                                AND user_read_histories.user_id = ?
                            LIMIT 1
                        ), ?)',
                            [Incident::class, $readUserId, '1970-01-01 00:00:00'],
                        );
                },
            ])
            ->with([
                'reportedByUser',
                'causedByUser',
                'category',
                'punishment',
                'projectRecord:id,name,date_start,date_end,category',
                'projectRecord.manager',
                'reports',
                'advices',
                'files',
            ]);
    }

    public function canAccessIncident(Incident $incident, User $user): bool
    {
        $isPM = $user->position_id == 6;

        if ($this->canManageIncidentAdministration($user)) {
            return true;
        }

        if ($incident->caused_by === $user->id || $incident->reported_by === $user->id) {
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

    public function canManageIncidentAdministration(User $user): bool
    {
        return ($user->position_id && $user->position_id < 6) || in_array($user->id, [608, 610], true);
    }

    public function canViewIncidentHistory(User $user): bool
    {
        return $this->canManageIncidentAdministration($user) || $user->position_id == 6;
    }

    public function canManageIncidentWorkflow(User $user): bool
    {
        return $this->canManageIncidentAdministration($user) || $user->position_id == 6;
    }

    public function isCompletedIncident(Incident $incident): bool
    {
        return $incident->status === '完了';
    }

    public function canCreateIncidentRecord(User $user): bool
    {
        return (bool) $user->id;
    }

    public function canEditIncidentRecord(User $user, Incident $incident): bool
    {
        if ($this->canManageIncidentAdministration($user)) {
            return true;
        }

        if ($user->position_id == 6) {
            return true;
        }

        return $this->canSelfManagePendingIncident($user, $incident);
    }

    public function canDeleteIncidentRecord(User $user, Incident $incident): bool
    {
        return $this->canManageIncidentAdministration($user)
            || $this->canSelfManagePendingIncident($user, $incident);
    }

    public function canSelfManagePendingIncident(User $user, Incident $incident): bool
    {
        return $incident->reported_by === $user->id && $this->isPendingIncident($incident);
    }

    public function isPendingIncident(Incident $incident): bool
    {
        return $incident->status === null || $incident->status === '処分未決定';
    }

    public function createInitialIncidentReport(
        Incident $incident,
        User $activeUser,
        ?array $assigneeIds = null,
        ?string $request = null
    ): void {
        $userIds = $assigneeIds;

        if ($userIds === null) {
            if (!$incident->project_record_id) {
                return;
            }

            $project = ProjectRecord::query()
                ->whereKey($incident->project_record_id)
                ->first();

            $userIds = $project
                ? $project->manager()
                    ->pluck('users.id')
                    ->unique()
                    ->values()
                    ->all()
                : [];
        } else {
            $userIds = collect($userIds)->unique()->values()->all();
        }

        if (empty($userIds)) {
            return;
        }

        $report = $incident->reports()->create([
            'step' => 1,
            'request' => $request,
            'created_by' => $activeUser->id,
        ]);

        $this->createIncidentAssignees($report, $userIds);
    }

    public function createIncidentAssignees(IncidentReport $report, array $userIds): void
    {
        $now = now();
        $rows = collect($userIds)
            ->unique()
            ->values()
            ->map(fn ($userId) => [
                'incident_report_id' => $report->id,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if (!empty($rows)) {
            IncidentAssignee::insert($rows);
        }
    }

    public function latestIncidentReport(Incident $incident): ?IncidentReport
    {
        $reports = $incident->relationLoaded('reports')
            ? $incident->reports
            : $incident->reports()->with('assignees')->get();

        return $reports
            ->sort(fn ($a, $b) => [$b->step, $b->id] <=> [$a->step, $a->id])
            ->first();
    }

    public function isIncidentReportComplete(IncidentReport $report): bool
    {
        $assignees = $report->relationLoaded('assignees')
            ? $report->assignees
            : $report->assignees()->get();

        return $assignees->isNotEmpty()
            && $assignees->every(fn (IncidentAssignee $assignee) => $assignee->completed_at !== null);
    }

    public function markIncidentReportCompleteIfReady(IncidentReport $report): void
    {
        $report->loadMissing('assignees');

        if (!$this->isIncidentReportComplete($report)) {
            return;
        }

        if (!$report->completed_at) {
            $report->update(['completed_at' => now()]);
        }
    }

    public function canAdvanceIncidentReport(User $user, IncidentReport $report): bool
    {
        if ($this->canManageIncidentWorkflow($user)) {
            return true;
        }

        $assignees = $report->relationLoaded('assignees')
            ? $report->assignees
            : $report->assignees()->get();

        return $assignees->contains(fn (IncidentAssignee $assignee) => $assignee->user_id === $user->id);
    }

    public function canUpdateIncidentAssigneeReport(User $user, IncidentAssignee $assignee, Incident $incident): bool
    {
        return !$this->isCompletedIncident($incident)
            && $assignee->user_id === $user->id
            && $this->isLatestIncidentReportAssignee($incident, $assignee);
    }

    public function isLatestIncidentReportAssignee(Incident $incident, IncidentAssignee $assignee): bool
    {
        $latestReport = $this->latestIncidentReport($incident->loadMissing('reports.assignees'));

        return $latestReport && $latestReport->id === $assignee->incident_report_id;
    }

    public function hasActiveIncidentAssignment(Incident $incident, User $user): bool
    {
        if ($this->isCompletedIncident($incident)) {
            return false;
        }

        $latestReport = $this->latestIncidentReport($incident->loadMissing('reports.assignees'));

        if (!$latestReport) {
            return false;
        }

        return $latestReport->assignees->contains(fn (IncidentAssignee $assignee) => $assignee->user_id === $user->id);
    }

    public function activeIncidentAssignmentQuery(User $user)
    {
        return Incident::query()
            ->where(function ($statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhere('status', '!=', '完了');
            })
            ->whereExists(function ($assignmentQuery) use ($user) {
                $assignmentQuery->select(DB::raw(1))
                    ->from('incident_reports')
                    ->join('incident_assignees', 'incident_assignees.incident_report_id', '=', 'incident_reports.id')
                    ->whereColumn('incident_reports.incident_id', 'incidents.id')
                    ->where('incident_assignees.user_id', $user->id)
                    ->whereRaw('incident_reports.step = (
                        select max(latest_incident_reports.step)
                        from incident_reports as latest_incident_reports
                        where latest_incident_reports.incident_id = incidents.id
                    )');
            });
    }

    public function orWhereActiveIncidentAssignee($query, User $user): void
    {
        $query->orWhereExists(function ($assignmentQuery) use ($user) {
            $assignmentQuery->select(DB::raw(1))
                ->from('incident_reports')
                ->join('incident_assignees', 'incident_assignees.incident_report_id', '=', 'incident_reports.id')
                ->whereColumn('incident_reports.incident_id', 'incidents.id')
                ->where('incident_assignees.user_id', $user->id)
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('incidents.status')
                        ->orWhere('incidents.status', '!=', '完了');
                })
                ->whereRaw('incident_reports.step = (
                    select max(latest_incident_reports.step)
                    from incident_reports as latest_incident_reports
                    where latest_incident_reports.incident_id = incidents.id
                )');
        });
    }

    public function incidentStaffFields(): array
    {
        return [
            'occurred_date',
            'reported_date',
            'incident_category_id',
            'caused_by',
            'project_record_id',
            'related_parties',
            'description',
            'occured_location',
            'reason',
        ];
    }

    public function incidentManagerFields(): array
    {
        return array_merge($this->incidentStaffFields(), [
            'prevention',
            'prevention_apply_status',
            'resolution',
            'memo',
            'amount_of_damage',
            'payee',
            'expense_details',
        ]);
    }

    public function incidentFullFields(): array
    {
        return [
            'title',
            'description',
            'reported_by',
            'caused_by',
            'incident_category_id',
            'incident_punishment_id',
            'reason',
            'prevention',
            'prevention_apply_status',
            'instruction',
            'resolution',
            'occured_location',
            'memo',
            'aftermath_comment',
            'occurred_date',
            'reported_date',
            'instruction_date',
            'related_parties',
            'project_record_id',
            'status',
            'amount_of_damage',
            'payee',
            'expense_details',
            'risk_level',
            'severity_level',
            'private_notes',
            'committee_members',
            'committee_decision',
            'committee_decision_date',
        ];
    }

    public function allowedIncidentFields(User $user, ?Incident $incident = null): array
    {
        if ($this->canManageIncidentAdministration($user)) {
            return $this->incidentFullFields();
        }

        if ($user->position_id == 6) {
            return $this->incidentManagerFields();
        }

        return $this->incidentStaffFields();
    }

    public function hasDisallowedIncidentFields(User $user, array $fields, ?Incident $incident = null): bool
    {
        return !empty(array_diff($fields, $this->allowedIncidentFields($user, $incident)));
    }

    public function canViewIncidentList(User $user): bool
    {
        if ($this->canManageIncidentAdministration($user)) {
            return true;
        }

        if (
            Incident::query()
                ->where(function ($query) use ($user) {
                    $query->where('caused_by', $user->id)
                        ->orWhere('reported_by', $user->id);
                })
                ->exists()
        ) {
            return true;
        }

        if ($this->activeIncidentAssignmentQuery($user)->exists()) {
            return true;
        }

        return $this->managesIncidentProject($user);
    }

    public function managesIncidentProject(User $user): bool
    {
        if ($user->position_id != 6) {
            return false;
        }

        return ProjectRecord::query()
            ->whereHas('manager', function ($managerQuery) use ($user) {
                $managerQuery->where('users.id', $user->id);
            })
            ->exists();
    }

    public function incidentListQuery(User $user)
    {
        $isPM = $user->position_id == 6;
        $query = $this->incidentQuery(true, $user->id);

        if (!$this->canManageIncidentAdministration($user)) {
            $query->where(function ($scopeQuery) use ($user, $isPM) {
                $scopeQuery
                    ->where('caused_by', $user->id)
                    ->orWhere('reported_by', $user->id);

                $this->orWhereActiveIncidentAssignee($scopeQuery, $user);

                if ($isPM) {
                    $scopeQuery->orWhereHas('projectRecord.manager', function ($managerQuery) use ($user) {
                        $managerQuery->where('users.id', $user->id);
                    });
                }
            });
        }

        return $query;
    }

    public function applyIncidentFilters($query, Request $request): void
    {
        $keyword = trim((string) $request->input('keyword', ''));

        if ($keyword !== '') {
            $query->where(function ($keywordQuery) use ($keyword) {
                $keywordQuery->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('reason', 'like', "%{$keyword}%")
                    ->orWhere('prevention', 'like', "%{$keyword}%")
                    ->orWhere('prevention_apply_status', 'like', "%{$keyword}%")
                    ->orWhere('instruction', 'like', "%{$keyword}%")
                    ->orWhere('resolution', 'like', "%{$keyword}%")
                    ->orWhere('occured_location', 'like', "%{$keyword}%")
                    ->orWhere('memo', 'like', "%{$keyword}%")
                    ->orWhere('aftermath_comment', 'like', "%{$keyword}%")
                    ->orWhere('related_parties', 'like', "%{$keyword}%")
                    ->orWhere('payee', 'like', "%{$keyword}%")
                    ->orWhere('expense_details', 'like', "%{$keyword}%")
                    ->orWhere('status', 'like', "%{$keyword}%")
                    ->orWhereHas('causedByUser', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('reportedByUser', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('projectRecord', function ($projectQuery) use ($keyword) {
                        $projectQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                        $categoryQuery->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        $this->applyWhereInFilter($query, 'caused_by', $request->input('caused_by'));
        $this->applyWhereInFilter($query, 'reported_by', $request->input('reported_by'));
        $this->applyWhereInFilter($query, 'project_record_id', $request->input('project_record_id'));
        $this->applyWhereInFilter($query, 'incident_category_id', $request->input('incident_category_id'));
        $this->applyWhereInFilter($query, 'status', $request->input('status'));

        if ($request->filled('occurred_from')) {
            $query->whereDate('occurred_date', '>=', $request->input('occurred_from'));
        }

        if ($request->filled('occurred_to')) {
            $query->whereDate('occurred_date', '<=', $request->input('occurred_to'));
        }

        if ($request->filled('point_value')) {
            $operators = [
                'gt' => '>',
                'gte' => '>=',
                'eq' => '=',
                'lte' => '<=',
                'lt' => '<',
            ];
            $operator = $operators[$request->input('point_operator', 'gte')] ?? '>=';

            $query->whereRaw(
                "(COALESCE(risk_level, 0) * COALESCE(severity_level, 0)) {$operator} ?",
                [(int) $request->input('point_value')]
            );
        }
    }

    public function applyWhereInFilter($query, string $column, mixed $values): void
    {
        $values = collect(is_array($values) ? $values : ($values !== null && $values !== '' ? [$values] : []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values();

        if ($values->isNotEmpty()) {
            $query->whereIn($column, $values->all());
        }
    }

    public function orderIncidentList($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function incidentCategoryList()
    {
        return IncidentCategory::query()
            ->select('id', 'name', 'description', 'sort_order')
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function incidentStatusList()
    {
        return IncidentStatus::query()
            ->select('id', 'name', 'sort_order')
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function incidentPunishmentList()
    {
        return IncidentPunishment::query()
            ->select('id', 'name', 'description', 'sort_order')
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function incidentStatusNames($fallbackStatuses)
    {
        $statuses = $this->incidentStatusList()->pluck('name')->values();

        return $statuses->isNotEmpty() ? $statuses : $fallbackStatuses;
    }

    public function resolveIncidentLogDisplayValues($logs, $readBefore = null)
    {
        $relationshipFields = [
            'caused_by' => ['model' => User::class, 'label' => 'name'],
            'reported_by' => ['model' => User::class, 'label' => 'name'],
            'project_record_id' => ['model' => ProjectRecord::class, 'label' => 'name'],
            'incident_category_id' => ['model' => IncidentCategory::class, 'label' => 'name'],
            'incident_punishment_id' => ['model' => IncidentPunishment::class, 'label' => 'name'],
            'files' => ['model' => FileRecord::class, 'label' => 'name'],
        ];

        $idsByField = [];
        foreach ($logs as $log) {
            foreach (($log->changes ?? []) as $field => $change) {
                if (!isset($relationshipFields[$field])) {
                    continue;
                }

                foreach (['old', 'new'] as $key) {
                    $value = $change[$key] ?? null;
                    if (is_array($value)) {
                        foreach ($value as $id) {
                            if ($id !== null && $id !== '') {
                                $idsByField[$field][] = (int) $id;
                            }
                        }
                    } elseif ($value !== null && $value !== '') {
                        $idsByField[$field][] = (int) $value;
                    }
                }
            }
        }

        $labelsByField = [];
        foreach ($idsByField as $field => $ids) {
            $config = $relationshipFields[$field];
            $labelsByField[$field] = $config['model']::query()
                ->whereIn('id', array_values(array_unique($ids)))
                ->pluck($config['label'], 'id')
                ->all();
        }

        return $logs->map(function ($log) use ($labelsByField, $readBefore) {
            $displayChanges = [];

            foreach (($log->changes ?? []) as $field => $change) {
                $displayChanges[$field] = [
                    'old' => $change['old'] ?? null,
                    'new' => $change['new'] ?? null,
                    'display_old' => $this->resolveIncidentLogDisplayValue($field, $change['old'] ?? null, $labelsByField),
                    'display_new' => $this->resolveIncidentLogDisplayValue($field, $change['new'] ?? null, $labelsByField),
                ];
            }

            $log->setAttribute('display_changes', $displayChanges);
            $log->setAttribute('is_unread', $readBefore === null || $log->created_at->gt($readBefore));

            return $log;
        });
    }

    public function resolveIncidentLogDisplayValue(string $field, mixed $value, array $labelsByField): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!isset($labelsByField[$field])) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($id) => $labelsByField[$field][(int) $id] ?? $id)
                ->values()
                ->all();
        }

        return $labelsByField[$field][(int) $value] ?? $value;
    }

    public function nextSortOrder($query): int
    {
        $maxSort = (clone $query)->max('sort_order');

        return is_numeric($maxSort) ? ((int) $maxSort + 1) : 1;
    }

    public function persistSortOrder(string $modelClass, array $ids): void
    {
        DB::transaction(function () use ($modelClass, $ids) {
            foreach (array_values($ids) as $index => $id) {
                $modelClass::where('id', $id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });
    }

    public function formatIncidentExportDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function formatIncidentExportDateTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i');
    }
}
