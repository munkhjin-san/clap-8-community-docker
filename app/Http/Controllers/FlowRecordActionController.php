<?php

namespace App\Http\Controllers;

use App\Models\FlowRecord;
use App\Services\FlowRecordActionService;
use App\Services\FlowService;
use App\Support\FlowRecordActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * カスタムボタン（アプリの「ツール」で作る実行ボタン）の入口。
 *
 * FlowController から分けてある：あちらはアプリ・レコード・ファイルの汎用的な出入り口で、
 * こちらは外部システムを叩く処理の窓口。増えるのはハンドラの方（FlowRecordActions）なので、
 * 混ぜておくと片方の都合でもう片方が触られ続けることになる。
 */
class FlowRecordActionController extends Controller
{
    public function __construct(
        private readonly FlowService $flowService,
        private readonly FlowRecordActionService $actions,
    ) {}

    /**
     * 実行。受け取るのはレコードIDとボタンID（flow_app_tools）だけ。
     *
     * どこを呼ぶかは設定ではなくコード側の登録済みハンドラが決めるので、宛先は一切入ってこない。
     * 返すのは結果だけ——画面は実行後にレコードを読み直すので、ここで詳細を組み直す必要はない。
     */
    public function run(Request $request): JsonResponse
    {
        $user = $this->active_user();
        $data = $request->validate([
            'record_id' => 'required|integer|exists:flow_records,id',
            'tool_id' => 'required|integer',
        ]);

        $record = FlowRecord::with(['definition.fields', 'definition.appPermissions', 'definition.recordPermissionSets', 'definition.tools', 'values'])
            ->findOrFail($data['record_id']);

        // 実行は書き込みなので、閲覧だけの人は入口で止める（押せる人の判定はサービス側でもう一度）
        abort_unless($this->flowService->recordPermissions($user, $record, $record->definition)['view'], 403);

        $result = $this->actions->run($user, $record, (int) $data['tool_id']);

        return response()->json([
            'message' => $result['message'],
            'written' => $result['written'],
        ]);
    }

    /** 設定画面用：コードに登録されているカスタムボタンの処理一覧。 */
    public function catalog(): JsonResponse
    {
        return response()->json(['actions' => FlowRecordActions::catalog()]);
    }

    // Double-account dropped (community_logic): act as the authenticated user only.
    private function active_user()
    {
        return Auth::user();
    }
}
