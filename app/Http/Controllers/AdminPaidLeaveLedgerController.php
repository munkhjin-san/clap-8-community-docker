<?php

namespace App\Http\Controllers;

use App\Models\PaidLeaveAccount;
use App\Services\PaidLeaveLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminPaidLeaveLedgerController extends Controller
{
    public function __construct(private PaidLeaveLedgerService $paidLeaveLedger)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'users' => $this->paidLeaveLedger->adminLedgerUsers($data['search'] ?? null),
        ]);
    }

    public function show(PaidLeaveAccount $account)
    {
        $this->authorizeAdmin();

        return response()->json($this->paidLeaveLedger->adminLedgerHistory($account));
    }

    public function storeAdjustment(PaidLeaveAccount $account, Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'amount_days' => ['required', 'numeric', 'min:-365', 'max:365', 'not_in:0'],
            'adjusted_on' => ['required', 'date'],
            'paid_leave_grant_id' => ['sometimes', 'nullable', 'integer'],
            'adjustment_type' => ['sometimes', 'nullable', 'string', Rule::in(['manual', 'manual_deduction', 'manual_restore'])],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        return response()->json($this->paidLeaveLedger->createManualAdjustment(
            $account,
            (float) $data['amount_days'],
            $data['adjusted_on'],
            $data['note'] ?? null,
            $this->activeUserId(),
            $data['paid_leave_grant_id'] ?? null,
            $data['adjustment_type'] ?? 'manual'
        ));
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403, '管理者権限がありません。');
    }

    private function activeUserId(): int
    {
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        return (int) $user->id;
    }
}
