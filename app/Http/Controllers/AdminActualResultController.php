<?php

namespace App\Http\Controllers;

use App\Models\FreeeCredential;
use App\Services\ActualResultPersistenceService;
use App\Services\Freee\FreeeActualResultService;
use App\Services\Freee\FreeeJournalPostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AdminActualResultController extends Controller
{
    public function show(Request $request, ActualResultPersistenceService $actualResults)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $payload = $actualResults->payloadForMonth($data['month']);

        return response()->json($payload ?? [
            'exists' => false,
            'month' => $data['month'],
            'message' => 'この月の実績データはまだ保存されていません。',
        ]);
    }

    /**
     * freee会計から対象月の損益計算書を取り込み、実績を作る。
     */
    public function syncFromFreee(
        Request $request,
        FreeeActualResultService $freee,
        ActualResultPersistenceService $actualResults
    ) {
        $this->authorizeAdmin();

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'overwrite_confirmed' => ['nullable', 'boolean'],
        ]);

        $existingReport = $actualResults->reportForMonth($data['month']);

        if ($existingReport !== null && ! ($data['overwrite_confirmed'] ?? false)) {
            throw ValidationException::withMessages([
                'overwrite_confirmed' => 'この月には保存済みの実績があります。上書きの確認が必要です。',
            ]);
        }

        $result = $freee->calculateForMonth($this->connectedFreeeCredential(), $data['month']);

        return response()->json(
            $actualResults->saveSyncedResult($result, $data['month'], $this->activeUserId())
        );
    }

    /**
     * 計算済みの積立金をfreeeへ振替伝票として送る。
     *
     * dry_run=true なら送信内容を返すだけ。実送信でも (対象月, 種類) 単位で
     * 登録済み伝票を更新するため、何度押しても二重計上にはならない。
     */
    public function postToFreee(Request $request, FreeeJournalPostService $journals)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'dry_run' => ['nullable', 'boolean'],
            'buckets' => ['nullable', 'array'],
            'buckets.*' => [Rule::in(array_merge(
                array_keys(FreeeJournalPostService::BUCKET_ACCOUNTS),
                [FreeeJournalPostService::BONUS_ACCRUAL_BUCKET]
            ))],
        ]);

        return response()->json($journals->postForMonth(
            $this->connectedFreeeCredential(),
            $data['month'],
            filter_var($data['dry_run'] ?? true, FILTER_VALIDATE_BOOL),
            $this->activeUserId(),
            $data['buckets'] ?? [],
        ));
    }

    /**
     * 取込に使う連携済みfreee設定。未連携なら操作の前で止める。
     */
    private function connectedFreeeCredential(): FreeeCredential
    {
        $credential = FreeeCredential::query()
            ->where('active', true)
            ->where('status', FreeeCredential::STATUS_CONNECTED)
            ->orderBy('id')
            ->first();

        if (! $credential) {
            throw ValidationException::withMessages([
                'message' => '連携済みのfreee設定がありません。先に管理画面のfreeeタブで認可してください。',
            ]);
        }

        return $credential;
    }

    public function export(Request $request, ActualResultPersistenceService $actualResults)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $csv = $actualResults->exportCsv($data['month']);
        $fileName = "actual-results-{$data['month']}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    private function authorizeAdmin(): void
    {
        // Community-aware admin gate (was hardcoded actual_result_admin_user_ids).
        // The env-configured ids remain an optional break-glass fallback.
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');
        abort_unless(
            $user->isAdmin()
                || in_array((int) $user->id, config('access.actual_result_admin_user_ids', []), true),
            403,
            '管理者権限がありません。'
        );
    }

    private function activeUserId(): int
    {
        // Double-account dropped (community_logic): the authenticated user only.
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        return (int) $user->id;
    }
}
