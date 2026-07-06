<?php

namespace App\Http\Controllers;

use App\Models\shiftType;
use App\Services\Community\CommunityPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD for community shift types. Shift types are community-scoped (the
 * BelongsToCommunity trait auto-scopes reads and stamps community_id on create),
 * and are assignable to roles via CommunityContextController::syncRoleShiftTypes.
 * Gated on community.manage (admin-level community configuration).
 *
 * Each type carries a fixed `category` (system meaning — drives payroll/attendance
 * /leave logic) chosen from shiftType::categoryCatalog(). The `hours` value
 * disambiguates the hourly categories (annual_leave_hourly / holiday_work).
 */
class ShiftTypeController extends Controller
{
    public function __construct(private CommunityPermissionService $permissions)
    {
    }

    /** The fixed, admin-assignable category catalog (value/label/hours). */
    public function categories(): JsonResponse
    {
        abort_unless($this->permissions->can('community.manage'), 403);

        return response()->json(shiftType::categoryCatalog());
    }

    public function index(): JsonResponse
    {
        abort_unless($this->permissions->can('community.manage'), 403);

        return response()->json(
            shiftType::where('deleted_flag', 0)->orderBy('id')->get(['id', 'name', 'abbreviation', 'value', 'full_day', 'category', 'hours', 'active'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($this->permissions->can('community.manage'), 403);

        $validated = $this->validatePayload($request, required: true);

        // community_id is stamped by the BelongsToCommunity creating hook.
        $shiftType = shiftType::create($validated + ['deleted_flag' => 0]);

        return response()->json($shiftType, 201);
    }

    public function update(Request $request, shiftType $shiftType): JsonResponse
    {
        abort_unless($this->permissions->can('community.manage'), 403);

        $validated = $this->validatePayload($request, required: false);

        $shiftType->update($validated);

        return response()->json($shiftType);
    }

    public function destroy(shiftType $shiftType): JsonResponse
    {
        abort_unless($this->permissions->can('community.manage'), 403);

        $shiftType->update(['deleted_flag' => 1]);

        return response()->json(['ok' => true]);
    }

    /**
     * Validate the create/update payload. `category` is required from the fixed
     * catalog; `hours` is required only for the hourly categories and forced to
     * null for any other category. `$required` is false on update so a partial
     * patch is allowed, but any field present must still be valid.
     */
    private function validatePayload(Request $request, bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';

        $validated = $request->validate([
            'name' => [$presence, 'string', 'max:100'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'value' => ['nullable', 'integer'],
            'full_day' => ['nullable', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'category' => [$presence, Rule::in(shiftType::categoryKeys())],
            'hours' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => in_array($request->input('category'), shiftType::HOURLY_CATEGORIES, true)),
            ],
        ]);

        // hours is only meaningful for the hourly categories; clear it otherwise.
        if (array_key_exists('category', $validated)
            && !in_array($validated['category'], shiftType::HOURLY_CATEGORIES, true)) {
            $validated['hours'] = null;
        }

        return $validated;
    }
}
