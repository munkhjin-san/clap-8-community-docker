<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBankAccount;
use App\Models\EmployeeBankAccountAccessLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 管理画面 > アカウント の振込口座 CRUD。
 *
 * 方針（決定済み）:
 *  - 管理者のみ。本人には見せない／編集させない（本人は自分の口座を知っている）。
 *  - 退職しても消さない。
 *
 * 平文の番号を返すのは reveal() だけで、そこは必ずログを残す。一覧・単票は下4桁の伏せ字しか
 * 返さない（200人の一覧で200回復号しないためでもあり、そもそも一覧に平文を流す理由がない）。
 */
class AdminBankAccountController extends Controller
{
    /** 一覧：口座の登録状況と伏せ字だけ。平文は絶対に含めない。 */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $rows = EmployeeBankAccount::query()
            ->with(['user:id,name,retire', 'updatedBy'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->get()
            ->map(fn ($a) => $this->present($a));

        return response()->json(['accounts' => $rows]);
    }

    /** 単票（該当ユーザーの口座）。未登録なら null。 */
    public function show(Request $request, $userId): JsonResponse
    {
        $this->authorizeAdmin();
        $account = EmployeeBankAccount::with('updatedBy')->where('user_id', (int) $userId)->first();

        return response()->json(['account' => $account ? $this->present($account) : null]);
    }

    /** 登録／更新。1人1件なので updateOrCreate。 */
    public function upsert(Request $request, $userId): JsonResponse
    {
        $this->authorizeAdmin();
        $actor = $this->activeUserId();
        $user = User::findOrFail((int) $userId);

        $data = $request->validate([
            'account_holder' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_holder_kana' => 'nullable|string|max:255',
            // 口座番号は先頭0が意味を持つので数値化しない。数字とハイフンのみ許可。
            'account_number' => 'nullable|string|max:32|regex:/\A[0-9\-]+\z/',
        ], [
            'account_number.regex' => '口座番号は数字とハイフンで入力してください。',
        ]);

        $existing = EmployeeBankAccount::where('user_id', $user->id)->first();

        // 空で送られた番号は「変更しない」。フォームは平文を持っていないので、他の項目だけ直す
        // 操作で番号が消えてしまうのを防ぐ（フロー側のパスワードフィールドと同じ約束）。
        if (! array_key_exists('account_number', $data) || $data['account_number'] === null || $data['account_number'] === '') {
            unset($data['account_number']);
        }

        $account = EmployeeBankAccount::updateOrCreate(
            ['user_id' => $user->id],
            $data + [
                'updated_by_user_id' => $actor,
                'created_by_user_id' => $existing?->created_by_user_id ?? $actor,
            ]
        );

        EmployeeBankAccountAccessLog::record($actor, $user->id, $existing ? 'update' : 'create');

        return response()->json(['account' => $this->present($account->fresh('updatedBy'))]);
    }

    /** 削除（論理削除）。番号ごと消える扱いだが行は残る。 */
    public function destroy(Request $request, $userId): JsonResponse
    {
        $this->authorizeAdmin();
        $actor = $this->activeUserId();
        $account = EmployeeBankAccount::where('user_id', (int) $userId)->firstOrFail();
        $account->delete();
        EmployeeBankAccountAccessLog::record($actor, (int) $userId, 'delete');

        return response()->json(['deleted' => true]);
    }

    /**
     * 平文の番号を1件返す。押した事実を必ず記録する。
     *
     * ここだけが平文の出口。フロー側の「表示」と同じ考え方で、ページに載せっぱなしにせず
     * 押されたときだけ取得する。
     */
    public function reveal(Request $request, $userId): JsonResponse
    {
        $this->authorizeAdmin();
        $actor = $this->activeUserId();
        $account = EmployeeBankAccount::where('user_id', (int) $userId)->firstOrFail();

        EmployeeBankAccountAccessLog::record($actor, (int) $userId, 'reveal');

        return response()->json(['account_number' => $account->account_number]);
    }

    /** 最近の操作ログ（対象ユーザー単位）。 */
    public function logs(Request $request, $userId): JsonResponse
    {
        $this->authorizeAdmin();

        $rows = EmployeeBankAccountAccessLog::with('actor')
            ->where('target_user_id', (int) $userId)
            ->orderByDesc('id')->limit(20)->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'action' => $l->action,
                'actor' => $l->actor?->name,
                'created_at' => $l->created_at,
            ]);

        return response()->json(['logs' => $rows]);
    }

    /** API に出す形。番号は伏せ字のみ。 */
    private function present(EmployeeBankAccount $a): array
    {
        return [
            'user_id' => $a->user_id,
            'user_name' => $a->user?->name,
            'account_holder' => $a->account_holder,
            'bank_name' => $a->bank_name,
            'branch_name' => $a->branch_name,
            'account_holder_kana' => $a->account_holder_kana,
            // 平文は present() では絶対に触らない（reveal() 専用）
            'account_number_masked' => $a->maskedNumber(),
            'has_number' => $a->account_number_last4 !== null,
            'updated_by' => $a->updatedBy?->name,
            'updated_at' => $a->updated_at,
        ];
    }

    /** User::isAdmin() を使う（ハードコードした ID 配列を各コントローラに散らさない）。 */
    private function authorizeAdmin(): void
    {
        abort_unless($this->activeUser()->isAdmin(), 403, '管理者権限がありません。');
    }

    private function activeUser(): User
    {
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        $sub = $user->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();

        return $sub ?: $user;
    }

    private function activeUserId(): int
    {
        return (int) $this->activeUser()->id;
    }
}
