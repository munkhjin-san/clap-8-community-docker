<?php

namespace App\Services;

use App\Models\ProjectAccount;
use App\Models\ProjectRecord;
use App\Support\CoATemplates;
use Illuminate\Support\Facades\DB;

class CoAInstaller
{
    /**
     * Install default chart of accounts for a project if not already present.
     */
    public function installForProject(ProjectRecord $project, bool $force = false): void
    {
        if (! $force && ProjectAccount::where('project_record_id', $project->id)->exists()) {
            return;
        }

        $this->syncForProject($project, $force);
    }

    /**
     * Sync template accounts into a project (optionally overwrite existing fields).
     */
    public function syncForProject(ProjectRecord $project, bool $overwrite = false): void
    {
        DB::transaction(function () use ($project, $overwrite) {
            $order = 1;
            foreach (CoATemplates::jpPL() as $node) {
                $order = $this->createNode($project, $node, null, '/', 0, $order, $overwrite);
            }
        });
    }

    private function createNode(
        ProjectRecord $project,
        array $node,
        ?ProjectAccount $parent,
        string $parentPath,
        int $depth,
        int $order,
        bool $overwrite
    ): int {
        $path = rtrim($parentPath, '/') . '/' . $node['code'] . '/';

        $attributes = [
            'name'       => $node['name'],
            'parent_id'  => $parent?->id,
            'path'       => $path,
            'depth'      => $depth,
            'is_postable'=> $node['is_postable'] ?? true,
            'is_active'  => true,
            'sort_order' => $order,
            'is_formula' => $node['is_formula'] ?? false,
            'formula'    => $node['formula'] ?? null,
        ];

        $account = $overwrite
            ? ProjectAccount::updateOrCreate(
                [
                    'project_record_id' => $project->id,
                    'code'              => $node['code'],
                ],
                $attributes
            )
            : ProjectAccount::firstOrCreate(
                [
                    'project_record_id' => $project->id,
                    'code'              => $node['code'],
                ],
                $attributes
            );

        $order++;

        foreach ($node['children'] ?? [] as $child) {
            $order = $this->createNode($project, $child, $account, $path, $depth + 1, $order, $overwrite);
        }

        return $order;
    }
}
