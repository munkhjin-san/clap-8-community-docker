<?php

namespace App\Http\Middleware;

use App\Services\Community\CommunityPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend counterpart of the SideMenu/route-guard app gating: rejects API calls
 * to an app's controllers when the active role lacks that app's blade. Maps a
 * controller to its app blade so every endpoint of the app is covered without
 * reorganizing routes. Admin bypasses via can(). Built-in apps (board,
 * dashboard) and admin/community controllers are intentionally unmapped.
 */
class EnforceAppBlade
{
    private const CONTROLLER_BLADE = [
        'CalendarController' => 'app.schedule',
        'ContentController' => 'app.learning',
        'LessonController' => 'app.learning',
        'LessonExamController' => 'app.learning',
        'ContactController' => 'app.contact',
        'AssetController' => 'app.asset',
        'AssetCategoryController' => 'app.asset',
        'SupportController' => 'app.support',
        'CustomFormController' => 'app.form',
        'CustomfieldController' => 'app.form',
        'WorkController' => 'app.timesheet',
        'PostController' => 'app.post',
        'ProjectController' => 'app.project',
        'IncidentController' => 'app.project',
        'ProjectPlanController' => 'app.project',
        'ProjectManagementController' => 'app.project',
        'ProjectProfitPlanController' => 'app.project',
    ];

    public function __construct(private CommunityPermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $action = $request->route()?->getActionName();

        if ($action && str_contains($action, '@')) {
            $controller = class_basename(explode('@', $action)[0]);
            $blade = self::CONTROLLER_BLADE[$controller] ?? null;

            if ($blade && !$this->permissions->can($blade, $request->user())) {
                abort(403, 'この機能へのアクセス権限がありません。');
            }
        }

        return $next($request);
    }
}
