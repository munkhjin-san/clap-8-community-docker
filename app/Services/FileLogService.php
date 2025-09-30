<?php

namespace App\Services;

use App\Models\DriveActivityLog;
use App\Models\DriveNode;

class FileLogService
{
    public function log(array $attributes): DriveActivityLog
    {
        $payload = array_merge([
            'user_id' => auth()->id(),
            'client_ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'referer' => request()?->headers->get('referer'),
            'occurred_at' => now(),
        ], $attributes);

        return DriveActivityLog::create($payload);
    }

    public function logNode(DriveNode $node, string $action, array $attributes = []): DriveActivityLog
    {
        $base = [
            'item_id'   => (string) $node->id,
            'item_type' => $node->type,
            'item_name' => $node->name,
            'project_id'=> $node->project_id,
            'action'    => $action,
        ];

        return $this->log(array_merge($base, $attributes));
    }

    public function logDeleted(string $itemId, string $itemType, string $itemName, ?int $projectId, array $attributes = []): DriveActivityLog
    {
        $base = [
            'item_id'   => $itemId,
            'item_type' => $itemType,
            'item_name' => $itemName,
            'project_id'=> $projectId,
            'action'    => 'deleted',
        ];

        return $this->log(array_merge($base, $attributes));
    }
}
