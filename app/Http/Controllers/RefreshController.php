<?php

namespace App\Http\Controllers;

use App\Services\RefreshService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RefreshController extends Controller
{
    public function __construct(
        private RefreshService $refreshService,
    ) {
    }
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function indexPosts(Request $request)
    {
        return response()->json(
            $this->refreshService->getApplicationData((array) ($request->status ?? []))
        );
    }

    public function approvePost(Request $request, string $id)
    {
        return response()->json(
            $this->refreshService->approveApplication(
                (int) $id,
                (int) Auth::id(),
                (bool) $request->boolean('force_use_remaining'),
            )
        );
    }

    public function destroyPost(string $id)
    {
        return response()->json(
            $this->refreshService->deleteApplication((int) $id)
        );
    }

    public function confirmPendingUsage(Request $request, string $id)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        return response()->json(
            $this->refreshService->confirmPendingUsage(
                (int) $id,
                (int) $validated['amount'],
                (int) Auth::id(),
            )
        );
    }

    public function kintoneRecords()
    {
        return response()->json($this->refreshService->fetchKintoneRows());
    }

    public function syncKintone()
    {
        return response()->json($this->refreshService->syncFromKintone());
    }

    public function indexManagement(Request $request)
    {
        return response()->json(
            $this->refreshService->getManagementData($request->integer('year') ?: null)
        );
    }

    public function mySummary()
    {
        $userId = (int) Auth::id();

        abort_if($userId <= 0, Response::HTTP_FORBIDDEN);

        return response()->json(
            $this->refreshService->getUserSummary($userId)
        );
    }

    public function userHistory(string $id)
    {
        $actor = $this->active_user();
        $targetUserId = (int) $id;
        $positionId = $actor?->position_id;
        $isPrivileged = (is_numeric($positionId) && (int) $positionId <= 6)
            || in_array((int) ($actor?->id ?? 0), [608, 610], true);

        abort_unless(
            $actor && ($isPrivileged || (int) $actor->id === $targetUserId),
            Response::HTTP_FORBIDDEN
        );

        return response()->json(
            $this->refreshService->getUserHistory($targetUserId)
        );
    }

    public function storeManagementGrant(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'grant_type' => 'required|in:annual,adjustment',
            'grant_date' => 'required|date',
            'amount' => 'required|integer|min:1',
            'registration_status' => 'required|in:draft,ready,hold',
            'judgement_note' => 'nullable|string',
            'annual_base_amount' => 'nullable|integer|min:0',
            'attendance_status' => 'nullable|string|max:100',
            'leave_status' => 'nullable|string|max:100',
            'service_years' => 'nullable|integer|min:0',
        ]);

        return response()->json(
            $this->refreshService->saveManagementGrant($validated, (int) Auth::id())
        );
    }

    public function destroyManagementReview(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'grant_year' => 'required|integer|min:2000|max:2100',
        ]);

        return response()->json(
            $this->refreshService->deleteManagementReview(
                (int) $validated['user_id'],
                (int) $validated['grant_year']
            )
        );
    }

    public function confirmLeaveReview(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'grant_year' => 'required|integer|min:2000|max:2100',
        ]);

        return response()->json(
            $this->refreshService->confirmLeaveReview(
                (int) $validated['user_id'],
                (int) $validated['grant_year'],
                (int) Auth::id(),
            )
        );
    }
}
