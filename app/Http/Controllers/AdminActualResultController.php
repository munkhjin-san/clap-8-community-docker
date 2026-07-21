<?php

namespace App\Http\Controllers;

use App\Models\ActualResultDepartment;
use App\Services\ActualResultCsvService;
use App\Services\ActualResultPersistenceService;
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

    public function calculate(
        Request $request,
        ActualResultCsvService $calculator,
        ActualResultPersistenceService $actualResults
    ) {
        $this->authorizeAdmin();

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'month' => ['nullable', 'date_format:Y-m'],
            'overwrite_confirmed' => ['nullable', 'boolean'],
            'discard_manual_edits' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['month'])) {
            $existingReport = $actualResults->reportForMonth($data['month']);

            if ($existingReport !== null && ! ($data['overwrite_confirmed'] ?? false)) {
                throw ValidationException::withMessages([
                    'overwrite_confirmed' => 'この月には保存済みの実績があります。上書きの確認が必要です。',
                ]);
            }

            $hasManualEdits = $existingReport?->departments->contains(
                fn (ActualResultDepartment $department) => $department->manual_adjusted
            ) ?? false;

            if ($hasManualEdits && ! ($data['discard_manual_edits'] ?? false)) {
                throw ValidationException::withMessages([
                    'discard_manual_edits' => '手動編集済みの部門があります。編集内容を破棄する確認が必要です。',
                ]);
            }
        }

        $result = $calculator->calculateFromUploadedFile($data['file']);

        if (! empty($data['month'])) {
            $csvMonth = $result['file']['calculation_month'] ?? null;

            if ($csvMonth !== null && $csvMonth !== $data['month']) {
                throw ValidationException::withMessages([
                    'month' => "選択月（{$data['month']}）とCSVの対象月（{$csvMonth}）が一致しません。",
                ]);
            }

            $result = $actualResults->saveUploadedResult($result, $data['file'], $data['month'], $this->activeUserId());
        }

        return response()->json($result);
    }

    public function accountOptions(Request $request, ActualResultPersistenceService $actualResults)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        return response()->json([
            'options' => $actualResults->accountOptions($data['month']),
        ]);
    }

    public function editHistories(Request $request, ActualResultPersistenceService $actualResults)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'department_id' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'histories' => $actualResults->editHistories($data['month'], $data['department_id'] ?? null),
        ]);
    }

    public function updateDepartmentAccount(
        Request $request,
        ActualResultDepartment $department,
        ActualResultPersistenceService $actualResults
    ) {
        $this->authorizeAdmin();

        $data = $request->validate([
            'account_key' => ['nullable', 'string'],
            'delete' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
            'account' => ['required_unless:delete,true', 'array'],
            'account.account_code' => ['nullable', 'string', 'max:64'],
            'account.account_name' => ['required_unless:delete,true', 'string', 'max:255'],
            'account.category' => ['nullable', Rule::in(['sales', 'expense'])],
            'account.bucket' => ['nullable', 'string', 'max:80'],
            'account.bucket_label' => ['nullable', 'string', 'max:255'],
            'account.amount' => ['nullable', 'numeric'],
            'account.debit' => ['nullable', 'numeric'],
            'account.credit' => ['nullable', 'numeric'],
            'account.balance' => ['nullable', 'numeric'],
            'account.ending_balance' => ['nullable', 'numeric'],
        ]);

        return response()->json($actualResults->updateDepartmentAccount($department, $data, $this->activeUserId()));
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
        abort_unless(
            in_array($this->activeUserId(), config('access.actual_result_admin_user_ids', []), true),
            403,
            '管理者権限がありません。'
        );
    }

    private function activeUserId(): int
    {
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        $sub = $user->linked()
            ->where('main_id', Auth::id())
            ->wherePivot('active', 1)
            ->first();

        return (int) ($sub?->id ?? $user->id);
    }
}
