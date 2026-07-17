<?php

namespace App\Http\Controllers;

use App\Models\CalendarFacility;
use App\Models\CalendarRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCalendarFacilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'type' => ['nullable', Rule::in([CalendarFacility::TYPE_ROOM, CalendarFacility::TYPE_CAR])],
        ]);
        $facilities = CalendarFacility::query()
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('slot')
            ->get()
            ->map(fn (CalendarFacility $facility) => $facility->adminPayload());

        return response()->json($facilities);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatedFacility($request, true);
        $maxSlot = CalendarFacility::query()->where('type', $validated['type'])->max('slot');
        $validated['slot'] = $maxSlot === null ? 0 : ((int) $maxSlot) + 1;
        $facility = CalendarFacility::query()->create($validated);

        return response()->json($facility->adminPayload(), 201);
    }

    public function update(Request $request, CalendarFacility $calendarFacility): JsonResponse
    {
        $this->authorizeAdmin();

        $calendarFacility->update($this->validatedFacility($request));

        return response()->json($calendarFacility->fresh()->adminPayload());
    }

    public function destroy(CalendarFacility $calendarFacility): JsonResponse
    {
        $this->authorizeAdmin();

        $column = $calendarFacility->type === CalendarFacility::TYPE_ROOM
            ? 'qualified_institution'
            : 'qualified_car';
        if (CalendarRecord::withTrashed()->where($column, $calendarFacility->slot)->exists()) {
            throw ValidationException::withMessages([
                'message' => '使用履歴がある設備は削除できません。利用状態を無効にしてください。',
            ]);
        }

        $calendarFacility->delete();

        return response()->json(['message' => '設備を削除しました。']);
    }

    private function validatedFacility(Request $request, bool $includeType = false): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:100'],
            'active' => ['required', Rule::in([true, false, 0, 1, '0', '1'])],
        ];
        if ($includeType) {
            $rules['type'] = ['required', Rule::in([CalendarFacility::TYPE_ROOM, CalendarFacility::TYPE_CAR])];
        }

        $validated = $request->validate($rules);
        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOL);

        return $validated;
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        $sub = $user->linked()
            ->where('main_id', Auth::id())
            ->wherePivot('active', 1)
            ->first();
        $activeUserId = (int) ($sub?->id ?? $user->id);

        abort_unless(in_array($activeUserId, User::ADMIN_USER_IDS, true), 403, '管理者権限がありません。');
    }
}
