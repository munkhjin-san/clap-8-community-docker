<?php

namespace App\Services;

use App\Models\User;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\ProjectRecord;
use App\Models\taskRecord;
use Illuminate\Support\Collection;
use Carbon\Carbon;
final class ProjectNotifyService
{
    public function __construct(
        private readonly BoardControllerProxy $boardController, // thin proxy around your real controller method
    ) {
    }

    /**
     * Send per-manager private board updates for a project.
     */
    public function notifyManagersAboutPeriod(ProjectRecord $project, string $pmName, string $period, array $rows, User $overrideUser, ?int $boardId = null): void
    {
        if (empty($rows)) {
            return; // Nothing to notify about
        }
        $periodDate = Carbon::parse($period)->format('Y-m');
        $url = rtrim(config('app.url'), '/') . "/project/{$project->id}/finance";
        $url .= "?period={$periodDate}";
        // Build the message once, unless you truly need per-manager variations.
        $message = $this->generateMessage($project->name, $pmName, $periodDate, $rows, $url);

        $payload = [
            'record_id'         => $boardId,
            'override_user_id'  => $overrideUser->id,
            'message'           => $message,
            'override_user'     => $overrideUser,
        ];

        $this->boardController->chatAdd($payload);
        $this->createTask($overrideUser->id, $boardId, $message);
    }

    private function generateMessage(
        string $projectName,
        string $pmName,
        string $period,
        array $rows,
        string $url,
        string $deadline = ''
    ): string {
        $deadline = now()->endOfMonth()->format('Y/m/d');
        // Expecting rows like: ['売上', '3740000', '5124247', '37.011951871658']
        $row = $rows[0]; // assume only one row for now

        $label     = $row['metric_label'] ?? '';
        $plan      = isset($row['plan']) ? number_format((float)$row['plan']) : '';
        $actual    = isset($row['actual']) ? number_format((float)$row['actual']) : '';
        $variance  = isset($row['variance']) ? round((float)$row['variance'], 2) : 0;

        return sprintf(
            "[To:%s:]\n".
            "【本メッセージは自動送信されています】\n".
            "[%s] %s の損益にて\n".
            "計画比 %+.2f%% の差異が発生しています。\n\n".
            "%s\n".
            "損益計画: %s\n".
            "実績: %s\n\n".
            "期限：%sまで\n".
            "対応：損益アプリの【コメント】欄に、原因・影響・対応策などを入力してください。\n\n".
            "%s",
            $pmName,
            $projectName,
            $period,
            $variance,
            $label,
            $plan,
            $actual,
            $deadline,
            $url
        );
    }
    public function createTask($override_user_id, $board_id, $message){
        $mention_users = boardToUser::where('record_id', $board_id)->whereNot('user_id', 610)->pluck('user_id')->toArray();
        $override_user = User::select('id', 'name', 'icon_path', 'icon_bg')
                             ->findOrFail($override_user_id);
        $eom = Carbon::now()->endOfMonth()->format('Y-m-d');
        $task = taskRecord::create([
            "user_id" => $override_user_id,
            "updated_user" => $override_user_id,
            "end_at" => $eom,
            "remarks" => $message,
            "board_id" => $board_id,
        ]);
        $task->executors()->sync($mention_users);
        return $task;
    }

}
