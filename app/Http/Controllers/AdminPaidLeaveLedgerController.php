<?php

namespace App\Http\Controllers;

use App\Models\PaidLeaveAccount;
use App\Services\PaidLeaveLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        return response()->json($this->paidLeaveLedger->createManualAdjustment(
            $account,
            (float) $data['amount_days'],
            $data['adjusted_on'],
            $data['note'] ?? null,
            $this->activeUserId()
        ));
    }

    private function authorizeAdmin(): void
    {
        abort_unless(in_array($this->activeUserId(), [608, 610], true), 403, '管理者権限がありません。');
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
