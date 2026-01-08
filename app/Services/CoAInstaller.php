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
    public function installForProject(ProjectRecord $project): void
    {
        if (ProjectAccount::where('project_record_id', $project->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($project) {
            $order = 1;
            foreach (CoATemplates::jpPL() as $node) {
            $order = $this->createNode($project, $node, null, '/', 0, $order);
        }
    });
}

private function createNode(
    ProjectRecord $project,
    array $node,
    ?ProjectAccount $parent,
    string $parentPath,
    int $depth,
    int $order
): int {
    $path = rtrim($parentPath, '/') . '/' . $node['code'] . '/';

    $account = ProjectAccount::updateOrCreate(
        [
            'project_record_id' => $project->id,
            'code'              => $node['code'],
        ],
        [
            'name'       => $node['name'],
            'parent_id'  => $parent?->id,
            'path'       => $path,
            'depth'      => $depth,
            'is_postable'=> $node['is_postable'] ?? true,
            'is_active'  => true,
            'sort_order' => $order,
            'is_formula' => $node['is_formula'] ?? false,
            'formula' => $node['formula'] ?? null,
        ]
    );

        $order++;

        foreach ($node['children'] ?? [] as $child) {
            $order = $this->createNode($project, $child, $account, $path, $depth + 1, $order);
        }

        return $order;
    }
}
