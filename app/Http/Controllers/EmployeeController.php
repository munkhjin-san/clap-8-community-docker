<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\EmployeeChangeApplication;
use App\Models\FileRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function storeChangeApplication(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', Rule::in(ApplicationType::values())],
            'detail' => ['required', 'array'],
        ]);

        if ((int) $validated['user_id'] !== Auth::id()) {
            abort(403);
        }

        $type = $validated['type'];
        $detail = $validated['detail'];

        $this->validateDetail($type, $detail);

        return DB::transaction(function () use ($validated, $type, $detail) {
            $application = EmployeeChangeApplication::create([
                'user_id' => $validated['user_id'],
                'submitted_by' => Auth::id(),
                'type' => $type,
                'status' => ApplicationStatus::Submitted,
                'effective_date' => $this->effectiveDate($type, $detail),
            ]);

            if (in_array($type, ApplicationType::profileChangeValues(), true)) {
                $application->profileDetail()->create($this->profileDetailPayload($type, $detail));
            } elseif ($type === ApplicationType::LeaveRequest->value) {
                $application->leaveDetail()->create($this->leaveDetailPayload($detail));
            } elseif ($type === ApplicationType::CommuteChange->value) {
                $application->commuteDetail()->create($this->commuteDetailPayload($detail));
            }

            foreach ($this->fileIds($type, $detail) as $fileId) {
                $file = FileRecord::findOrFail($fileId);
                $application->fileAttachments()->firstOrCreate([
                    'file_id' => $file->id,
                    'collection' => $this->fileCollection($type),
                ]);
            }

            return $application->load(['profileDetail', 'leaveDetail', 'commuteDetail', 'files']);
        });
    }

    public function indexChangeApplications(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(array_merge(['all'], ApplicationStatus::values()))],
            'type' => ['nullable', 'string', Rule::in(array_merge(['all'], ApplicationType::values()))],
        ]);

        $applications = EmployeeChangeApplication::query()
            ->with([
                'user:id,name,icon_path,icon_bg,position_id',
                'submittedBy:id,name,icon_path,icon_bg',
                'reviewedBy:id,name,icon_path,icon_bg',
                'profileDetail',
                'leaveDetail',
                'commuteDetail',
                'files',
            ])
            ->when(($validated['status'] ?? 'all') !== 'all', function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->when(($validated['type'] ?? 'all') !== 'all', function ($query) use ($validated) {
                $query->where('type', $validated['type']);
            })
            ->latest()
            ->paginate(30);

        return response()->json($applications);
    }

    public function myChangeApplications(Request $request)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $applications = EmployeeChangeApplication::query()
            ->with([
                'reviewedBy:id,name,icon_path,icon_bg',
                'profileDetail',
                'leaveDetail',
                'commuteDetail',
                'files',
            ])
            ->where('user_id', Auth::id())
            ->latest()
            ->limit($validated['limit'] ?? 50)
            ->get();

        return response()->json($applications);
    }

    public function showChangeApplication(EmployeeChangeApplication $application)
    {
        $this->ensureAdmin();

        return response()->json(
            $application->load([
                'user:id,name,icon_path,icon_bg,position_id',
                'submittedBy:id,name,icon_path,icon_bg',
                'reviewedBy:id,name,icon_path,icon_bg',
                'profileDetail',
                'leaveDetail',
                'commuteDetail',
                'files',
            ])
        );
    }

    public function reviewChangeApplication(Request $request, EmployeeChangeApplication $application)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'status' => ['required', Rule::in(ApplicationStatus::reviewableValues())],
            'review_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->update([
            'status' => $validated['status'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_comment' => $validated['review_comment'] ?? null,
        ]);

        return response()->json(
            $application->load([
                'user:id,name,icon_path,icon_bg,position_id',
                'submittedBy:id,name,icon_path,icon_bg',
                'reviewedBy:id,name,icon_path,icon_bg',
                'profileDetail',
                'leaveDetail',
                'commuteDetail',
                'files',
            ])
        );
    }

    private function validateDetail(string $type, array $detail): void
    {
        $rules = match ($type) {
            ApplicationType::NameChange->value => [
                'reason' => ['required', 'string', 'max:1000'],
                'effective_date' => ['required', 'date'],
                'last_name' => ['required', 'string', 'max:100'],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name_kana' => ['required', 'string', 'max:100'],
                'first_name_kana' => ['required', 'string', 'max:100'],
                'resident_card_file_ids' => ['sometimes', 'array', 'max:1'],
                'resident_card_file_ids.*' => ['integer', 'exists:file_records,id'],
                'share_with_pm' => ['required', 'accepted'],
            ],
            ApplicationType::AddressChange->value => [
                'effective_date' => ['required', 'date'],
                'address' => ['required', 'string', 'max:1000'],
                'resident_card_file_ids' => ['sometimes', 'array', 'max:1'],
                'resident_card_file_ids.*' => ['integer', 'exists:file_records,id'],
                'share_with_pm' => ['required', 'accepted'],
            ],
            ApplicationType::DependentChange->value => [
                'mode' => ['required', Rule::in(['add', 'remove'])],
                'detail' => ['required', 'array'],
            ],
            ApplicationType::WorkLocationChange->value => [
                'work_location' => ['required', 'string', 'max:100'],
                'effective_date' => ['required', 'date'],
                'route' => ['required', 'string', 'max:1000'],
                'monthly_pass_amount' => ['nullable', 'string', 'max:100'],
                'one_way_distance' => ['nullable', 'string', 'max:100'],
                'share_with_pm' => ['required', 'accepted'],
            ],
            ApplicationType::LeaveRequest->value => [
                'mode' => ['required', Rule::in(['illness', 'childbirth_childcare'])],
                'detail' => ['required', 'array'],
            ],
            ApplicationType::CommuteChange->value => [
                'mode' => ['required', Rule::in(['public_transportation', 'car', 'bicycle', 'walking'])],
                'detail' => ['required', 'array'],
            ],
        };

        validator($detail, $rules)->validate();

        if ($type === ApplicationType::DependentChange->value) {
            $this->validateDependentDetail($detail);
        } elseif ($type === ApplicationType::LeaveRequest->value) {
            $this->validateLeaveDetail($detail);
        } elseif ($type === ApplicationType::CommuteChange->value) {
            $this->validateCommuteDetail($detail);
        }
    }

    private function validateDependentDetail(array $detail): void
    {
        $mode = Arr::get($detail, 'mode');
        $payload = Arr::get($detail, 'detail', []);

        $rules = $mode === 'add'
            ? [
                'effective_date' => ['required', 'date'],
                'relationship' => ['required', 'string', 'max:100'],
                'annual_income' => ['required', 'string', 'max:100'],
                'reason' => ['required', 'string', 'max:1000'],
                'name' => ['required', 'string', 'max:100'],
                'name_kana' => ['required', 'string', 'max:100'],
                'birth_date' => ['required', 'date'],
                'gender' => ['required', 'string', 'max:100'],
                'address' => ['required', 'string', 'max:1000'],
                'retired_on' => ['nullable', 'date'],
            ]
            : [
                'effective_date' => ['required', 'date'],
                'reason' => ['required', 'string', 'max:1000'],
                'name' => ['required', 'string', 'max:100'],
                'name_kana' => ['required', 'string', 'max:100'],
                'birth_date' => ['required', 'date'],
                'employment_on' => ['nullable', 'date'],
            ];

        validator($payload, $rules)->validate();
    }

    private function validateLeaveDetail(array $detail): void
    {
        $mode = Arr::get($detail, 'mode');
        $payload = Arr::get($detail, 'detail', []);

        $rules = $mode === 'illness'
            ? [
                'illness_name' => ['required', 'string', 'max:100'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
            ]
            : [
                'expected_birth_date' => ['required', 'date'],
                'maternity_leave_start' => ['required', 'date'],
                'maternity_leave_end' => ['required', 'date'],
                'childcare_leave_start' => ['required', 'date'],
                'childcare_leave_end' => ['required', 'date'],
            ];

        validator($payload, $rules)->validate();
    }

    private function validateCommuteDetail(array $detail): void
    {
        $mode = Arr::get($detail, 'mode');
        $payload = Arr::get($detail, 'detail', []);

        $rules = match ($mode) {
            'public_transportation' => [
                'effective_date' => ['required', 'date'],
                'route' => ['required', 'string', 'max:1000'],
                'pass_amount' => ['required', 'string', 'max:100'],
                'other_amount' => ['nullable', 'string', 'max:100'],
                'share_with_pm' => ['required', 'accepted'],
            ],
            'car' => [
                'effective_date' => ['required', 'date'],
                'car_type' => ['required', 'string', 'max:100'],
                'one_way_distance' => ['required', 'string', 'max:100'],
                'share_with_pm' => ['required', 'accepted'],
            ],
            'bicycle' => [
                'effective_date' => ['required', 'date'],
                'route' => ['required', 'string', 'max:1000'],
                'pass_amount' => ['required', 'string', 'max:100'],
                'other_amount' => ['nullable', 'string', 'max:100'],
                'parking_amount' => ['nullable', 'string', 'max:100'],
                'share_with_pm' => ['required', 'accepted'],
            ],
            'walking' => [],
            default => throw ValidationException::withMessages(['mode' => '通勤方法を選択してください。']),
        };

        validator($payload, $rules)->validate();
    }

    private function profileDetailPayload(string $type, array $detail): array
    {
        if ($type === ApplicationType::NameChange->value) {
            return [
                'change_type' => $type,
                'effective_date' => Arr::get($detail, 'effective_date'),
                'reason' => Arr::get($detail, 'reason'),
                'last_name' => Arr::get($detail, 'last_name'),
                'first_name' => Arr::get($detail, 'first_name'),
                'last_name_kana' => Arr::get($detail, 'last_name_kana'),
                'first_name_kana' => Arr::get($detail, 'first_name_kana'),
            ];
        }

        if ($type === ApplicationType::AddressChange->value) {
            return [
                'change_type' => $type,
                'effective_date' => Arr::get($detail, 'effective_date'),
                'address' => Arr::get($detail, 'address'),
            ];
        }

        if ($type === ApplicationType::DependentChange->value) {
            $payload = Arr::get($detail, 'detail', []);
            return [
                'change_type' => $type,
                'dependent_action' => Arr::get($detail, 'mode'),
                'effective_date' => Arr::get($payload, 'effective_date'),
                'reason' => Arr::get($payload, 'reason'),
                'relationship' => Arr::get($payload, 'relationship'),
                'annual_income' => Arr::get($payload, 'annual_income'),
                'dependent_name' => Arr::get($payload, 'name'),
                'dependent_name_kana' => Arr::get($payload, 'name_kana'),
                'birth_date' => Arr::get($payload, 'birth_date'),
                'gender' => Arr::get($payload, 'gender'),
                'dependent_address' => Arr::get($payload, 'address'),
                'retired_on' => Arr::get($payload, 'retired_on'),
                'employment_on' => Arr::get($payload, 'employment_on'),
            ];
        }

        return [
            'change_type' => $type,
            'effective_date' => Arr::get($detail, 'effective_date'),
            'work_location' => Arr::get($detail, 'work_location'),
            'route' => Arr::get($detail, 'route'),
            'monthly_pass_amount' => Arr::get($detail, 'monthly_pass_amount'),
            'one_way_distance' => Arr::get($detail, 'one_way_distance'),
        ];
    }

    private function leaveDetailPayload(array $detail): array
    {
        $payload = Arr::get($detail, 'detail', []);

        return [
            'leave_type' => Arr::get($detail, 'mode'),
            'illness_name' => Arr::get($payload, 'illness_name'),
            'start_date' => Arr::get($payload, 'start_date'),
            'end_date' => Arr::get($payload, 'end_date'),
            'expected_birth_date' => Arr::get($payload, 'expected_birth_date'),
            'maternity_leave_start' => Arr::get($payload, 'maternity_leave_start'),
            'maternity_leave_end' => Arr::get($payload, 'maternity_leave_end'),
            'childcare_leave_start' => Arr::get($payload, 'childcare_leave_start'),
            'childcare_leave_end' => Arr::get($payload, 'childcare_leave_end'),
        ];
    }

    private function commuteDetailPayload(array $detail): array
    {
        $payload = Arr::get($detail, 'detail', []);

        return [
            'commute_type' => Arr::get($detail, 'mode'),
            'effective_date' => Arr::get($payload, 'effective_date'),
            'route' => Arr::get($payload, 'route'),
            'pass_amount' => Arr::get($payload, 'pass_amount'),
            'other_amount' => Arr::get($payload, 'other_amount'),
            'parking_amount' => Arr::get($payload, 'parking_amount'),
            'one_way_distance' => Arr::get($payload, 'one_way_distance'),
            'car_type' => Arr::get($payload, 'car_type'),
        ];
    }

    private function effectiveDate(string $type, array $detail): ?string
    {
        return match ($type) {
            ApplicationType::DependentChange->value,
            ApplicationType::LeaveRequest->value,
            ApplicationType::CommuteChange->value => Arr::get($detail, 'detail.effective_date')
                ?? Arr::get($detail, 'detail.start_date')
                ?? Arr::get($detail, 'detail.expected_birth_date'),
            default => Arr::get($detail, 'effective_date'),
        };
    }

    private function fileIds(string $type, array $detail): array
    {
        if (!in_array($type, [ApplicationType::NameChange->value, ApplicationType::AddressChange->value], true)) {
            return [];
        }

        return Arr::get($detail, 'resident_card_file_ids', []);
    }

    private function fileCollection(string $type): string
    {
        return match ($type) {
            ApplicationType::NameChange->value, ApplicationType::AddressChange->value => 'resident_card',
            default => 'attachments',
        };
    }
    private function active_user()
    {
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();

        return $sub ?: Auth::user();
    }

    private function ensureAdmin(): void
    {
        $user = $this->active_user();
        abort_unless(in_array((int) $user->id, [608, 610], true), Response::HTTP_FORBIDDEN);
    }
}
