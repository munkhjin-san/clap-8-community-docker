<?php

namespace App\Services;

use App\Models\ProjectKintoneContractUpdateNotification;
use App\Models\ProjectRecord;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class KintoneContractUpdateNotificationService
{
    public function __construct(
        private readonly BadgeService $badgeService,
    ) {
    }

    private const RECORD_EVENT_TYPES = [
        'ADD_RECORD',
        'UPDATE_RECORD',
        'UPDATE_STATUS',
    ];

    public function processWebhook(array $payload): void
    {
        $type = $payload['type'] ?? null;
        $notificationId = $payload['id'] ?? null;
        $appId = isset($payload['app']['id']) ? (int) $payload['app']['id'] : null;
        $recordId = $this->resolveRecordId($payload, $type);
        $departmentName = $this->resolveDepartmentName($payload, $type);

        if ($departmentName === null || $departmentName === '') {
            Log::info('Kintone contract webhook skipped: department name is empty', [
                'notification_id' => $notificationId,
                'type' => $type,
                'record_id' => $recordId,
            ]);

            return;
        }

        $project = ProjectRecord::query()
            ->where('name', $departmentName)
            ->first();

        if ($project === null) {
            Log::info('Kintone contract webhook skipped: project not found', [
                'notification_id' => $notificationId,
                'type' => $type,
                'department_name' => $departmentName,
                'record_id' => $recordId,
            ]);

            return;
        }

        $targetUserIds = $this->resolveTargetUserIds($project);

        if ($targetUserIds === []) {
            Log::info('Kintone contract webhook skipped: no target users', [
                'notification_id' => $notificationId,
                'project_id' => $project->id,
            ]);

            return;
        }

        $now = now();

        $created = false;

        foreach ($targetUserIds as $targetUserId) {
            $notification = ProjectKintoneContractUpdateNotification::firstOrCreate(
                [
                    'notification_id' => $notificationId,
                    'target_user_id' => $targetUserId,
                ],
                [
                    'type' => $type,
                    'app_id' => $appId,
                    'record_id' => $recordId,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if ($notification->wasRecentlyCreated) {
                $created = true;
            }
        }

        if ($created) {
            $this->badgeService->invalidateBadgeSummaryCache();
        }
    }

    private function resolveRecordId(array $payload, ?string $type): ?int
    {
        if (in_array($type, self::RECORD_EVENT_TYPES, true)) {
            $recordId = data_get($payload, 'record.$id.value');

            return $recordId !== null ? (int) $recordId : null;
        }

        if ($type === 'DELETE_RECORD') {
            $recordId = $payload['recordId'] ?? null;

            return $recordId !== null ? (int) $recordId : null;
        }

        return null;
    }

    private function resolveDepartmentName(array $payload, ?string $type): ?string
    {
        if (! in_array($type, self::RECORD_EVENT_TYPES, true)) {
            return null;
        }

        $departmentName = data_get($payload, 'record.部門.value');

        return is_string($departmentName) ? $departmentName : null;
    }

    /**
     * @return list<int>
     */
    private function resolveTargetUserIds(ProjectRecord $project): array
    {
        $managerIds = $project->manager()
            ->where('retire', 0)
            ->where('on_leave', 0)
            ->pluck('users.id')
            ->all();

        $executiveIds = User::query()
            ->where('retire', 0)
            ->where('on_leave', 0)
            ->whereNotNull('position_id')
            ->where('position_id', '<', 6)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($managerIds, $executiveIds)));
    }
}
