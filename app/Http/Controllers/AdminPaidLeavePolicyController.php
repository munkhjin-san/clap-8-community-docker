<?php

namespace App\Http\Controllers;

use App\Models\PaidLeaveGrantRule;
use App\Models\PaidLeavePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPaidLeavePolicyController extends Controller
{
    private const POLICY_NAME = 'default';

    private const DEFAULT_RULES = [
        ['service_months' => 6, 'legal_min_days' => 10, 'grant_days' => 10, 'label' => '6ヶ月'],
        ['service_months' => 18, 'legal_min_days' => 11, 'grant_days' => 11, 'label' => '1年6ヶ月'],
        ['service_months' => 30, 'legal_min_days' => 12, 'grant_days' => 12, 'label' => '2年6ヶ月'],
        ['service_months' => 42, 'legal_min_days' => 14, 'grant_days' => 14, 'label' => '3年6ヶ月'],
        ['service_months' => 54, 'legal_min_days' => 16, 'grant_days' => 16, 'label' => '4年6ヶ月'],
        ['service_months' => 66, 'legal_min_days' => 18, 'grant_days' => 18, 'label' => '5年6ヶ月'],
        ['service_months' => 78, 'legal_min_days' => 20, 'grant_days' => 20, 'label' => '6年6ヶ月以上'],
    ];

    public function index()
    {
        $this->authorizeAdmin();

        return response()->json($this->policyPayload($this->policy()));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'active' => ['sometimes', 'boolean'],
            'effective_from' => ['sometimes', 'nullable', 'date'],
            'first_grant_after_months' => ['required', 'integer', 'min:0', 'max:240'],
            'annual_grant_interval_months' => ['required', 'integer', 'min:1', 'max:60'],
            'expires_after_months' => ['required', 'integer', 'min:1', 'max:120'],
            'minimum_attendance_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'carryover_enabled' => ['sometimes', 'boolean'],
            'hourly_leave_enabled' => ['sometimes', 'boolean'],
            'hourly_deduction_unit_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'minutes_per_leave_day' => ['required', 'integer', 'min:1', 'max:1440'],
            'max_hourly_leave_days_per_year' => ['required', 'numeric', 'min:0', 'max:365'],
            'allow_negative_balance' => ['sometimes', 'boolean'],
            'memo' => ['sometimes', 'nullable', 'string'],
        ]);

        $policy = $this->policy();
        $policy->fill([
            ...$data,
            'updated_by_user_id' => $this->activeUserId(),
        ])->save();

        return response()->json($this->policyPayload($policy->fresh('rules')));
    }

    public function storeRule(Request $request)
    {
        $this->authorizeAdmin();

        $policy = $this->policy();
        $data = $this->validateRule($request);

        $exists = PaidLeaveGrantRule::query()
            ->where('paid_leave_policy_id', $policy->id)
            ->where('service_months', $data['service_months'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'service_months' => '同じ勤続月数のルールが既にあります。',
            ]);
        }

        $rule = PaidLeaveGrantRule::create([
            ...$data,
            'paid_leave_policy_id' => $policy->id,
            'created_by_user_id' => $this->activeUserId(),
            'updated_by_user_id' => $this->activeUserId(),
        ]);

        return response()->json($this->policyPayload($policy->fresh('rules')), 201);
    }

    public function updateRule(PaidLeaveGrantRule $rule, Request $request)
    {
        $this->authorizeAdmin();
        $this->assertRuleBelongsToPolicy($rule);

        $data = $this->validateRule($request, $rule->id);
        $rule->fill([
            ...$data,
            'updated_by_user_id' => $this->activeUserId(),
        ])->save();

        return response()->json($this->policyPayload($this->policy()->fresh('rules')));
    }

    public function destroyRule(PaidLeaveGrantRule $rule)
    {
        $this->authorizeAdmin();
        $this->assertRuleBelongsToPolicy($rule);

        $rule->delete();

        return response()->json($this->policyPayload($this->policy()->fresh('rules')));
    }

    private function validateRule(Request $request, ?int $exceptRuleId = null): array
    {
        $data = $request->validate([
            'service_months' => ['required', 'integer', 'min:0', 'max:600'],
            'legal_min_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'grant_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'memo' => ['sometimes', 'nullable', 'string'],
        ]);

        if ((float) $data['grant_days'] < (float) $data['legal_min_days']) {
            throw ValidationException::withMessages([
                'grant_days' => '付与日数は法定最低日数以上にしてください。',
            ]);
        }

        $policy = $this->policy();
        $duplicate = PaidLeaveGrantRule::query()
            ->where('paid_leave_policy_id', $policy->id)
            ->where('service_months', $data['service_months'])
            ->when($exceptRuleId, fn ($query) => $query->where('id', '!=', $exceptRuleId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'service_months' => '同じ勤続月数のルールが既にあります。',
            ]);
        }

        $data['label'] = $data['label'] ?? $this->serviceMonthsLabel((int) $data['service_months']);
        $data['active'] = $data['active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? (int) $data['service_months'];

        return $data;
    }

    private function policy(): PaidLeavePolicy
    {
        return DB::transaction(function () {
            $policy = PaidLeavePolicy::firstOrCreate(
                ['name' => self::POLICY_NAME],
                [
                    'active' => true,
                    'first_grant_after_months' => 6,
                    'annual_grant_interval_months' => 12,
                    'expires_after_months' => 24,
                    'minimum_attendance_rate' => 80,
                    'carryover_enabled' => true,
                    'hourly_leave_enabled' => true,
                    'hourly_deduction_unit_minutes' => 60,
                    'minutes_per_leave_day' => 480,
                    'max_hourly_leave_days_per_year' => 5,
                    'allow_negative_balance' => false,
                    'created_by_user_id' => $this->activeUserId(),
                    'updated_by_user_id' => $this->activeUserId(),
                ],
            );

            if (! $policy->rules()->exists()) {
                foreach (self::DEFAULT_RULES as $index => $rule) {
                    $policy->rules()->create([
                        ...$rule,
                        'active' => true,
                        'sort_order' => $index + 1,
                        'created_by_user_id' => $this->activeUserId(),
                        'updated_by_user_id' => $this->activeUserId(),
                    ]);
                }
            }

            return $policy->fresh('rules');
        });
    }

    private function assertRuleBelongsToPolicy(PaidLeaveGrantRule $rule): void
    {
        abort_unless((int) $rule->paid_leave_policy_id === (int) $this->policy()->id, 404, '有休付与ルールが見つかりません。');
    }

    private function policyPayload(PaidLeavePolicy $policy): array
    {
        $rules = $policy->rules->sortBy([
            ['sort_order', 'asc'],
            ['service_months', 'asc'],
        ])->values();

        return [
            'policy' => [
                'id' => $policy->id,
                'name' => $policy->name,
                'active' => (bool) $policy->active,
                'effective_from' => optional($policy->effective_from)->toDateString(),
                'first_grant_after_months' => $policy->first_grant_after_months,
                'annual_grant_interval_months' => $policy->annual_grant_interval_months,
                'expires_after_months' => $policy->expires_after_months,
                'minimum_attendance_rate' => $policy->minimum_attendance_rate,
                'carryover_enabled' => (bool) $policy->carryover_enabled,
                'hourly_leave_enabled' => (bool) $policy->hourly_leave_enabled,
                'hourly_deduction_unit_minutes' => $policy->hourly_deduction_unit_minutes,
                'minutes_per_leave_day' => $policy->minutes_per_leave_day,
                'max_hourly_leave_days_per_year' => $policy->max_hourly_leave_days_per_year,
                'allow_negative_balance' => (bool) $policy->allow_negative_balance,
                'memo' => $policy->memo,
            ],
            'rules' => $rules->map(fn (PaidLeaveGrantRule $rule) => $this->rulePayload($rule))->values(),
            'summary' => [
                'rule_count' => $rules->count(),
                'active_rule_count' => $rules->where('active', true)->count(),
                'max_grant_days' => $rules->max('grant_days') ?? 0,
                'legal_minimum_days_at_6_months' => collect(self::DEFAULT_RULES)->firstWhere('service_months', 6)['legal_min_days'],
            ],
        ];
    }

    private function rulePayload(PaidLeaveGrantRule $rule): array
    {
        return [
            'id' => $rule->id,
            'service_months' => $rule->service_months,
            'label' => $rule->label ?: $this->serviceMonthsLabel((int) $rule->service_months),
            'legal_min_days' => $rule->legal_min_days,
            'grant_days' => $rule->grant_days,
            'active' => (bool) $rule->active,
            'sort_order' => $rule->sort_order,
            'memo' => $rule->memo,
        ];
    }

    private function serviceMonthsLabel(int $months): string
    {
        if ($months < 12) {
            return "{$months}ヶ月";
        }

        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        return $remainingMonths === 0
            ? "{$years}年"
            : "{$years}年{$remainingMonths}ヶ月";
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
