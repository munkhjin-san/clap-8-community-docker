<?php

namespace App\Http\Controllers;

use App\Models\ProjectAccount;
use App\Models\ProjectPlanAmount;
use App\Models\ProjectPlanLock;
use App\Models\ProjectPlanScenario;
use App\Models\ProjectPlanYear;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Services\CoAInstaller;
use App\Exports\PlanTemplateExport;
use App\Imports\PlanTemplateImport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProjectPlanController extends Controller
{
    public function __construct(private CoAInstaller $coaInstaller)
    {
    }
    private function activeUser(): User
    {
        return Auth::user();
    }
    /**
     * Load plan grid: periods (12 months), accounts, and existing amounts.
     */
    public function grid(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'fiscal_year'  => ['required', 'integer'],
            'start_month'  => ['sometimes', 'integer', 'min:1', 'max:12'],
            'scenario_id'  => ['sometimes', 'nullable', 'integer'],
            'scenario_code'=> ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $fiscalYear = $data['fiscal_year'];
        $startMonth = $data['start_month'] ?? 3;

        $planYear = $this->ensurePlanYear($fiscalYear, $startMonth);
        $this->coaInstaller->installForProject($project);

        $scenarioId = $this->resolveScenario($project, $data['scenario_id'] ?? null, $data['scenario_code'] ?? null);
        $scenarioKey = $scenarioId ?? 0;

        $accounts = ProjectAccount::query()
            ->where('project_record_id', $project->id)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'path', 'depth', 'is_postable', 'is_formula', 'formula', 'is_active', 'parent_id']);

        $periods = DB::table('project_v_plan_months')
            ->where('plan_year_id', $planYear->id)
            ->orderBy('period_index')
            ->get();

        $amountRows = ProjectPlanAmount::query()
            ->where('project_record_id', $project->id)
            ->where('project_plan_year_id', $planYear->id)
            ->when($scenarioId, fn($q) => $q->where('project_plan_scenario_id', $scenarioId))
            ->when(is_null($scenarioId), fn($q) => $q->whereNull('project_plan_scenario_id'))
            ->get(['project_account_id', 'period_index', 'amount']);

        $amounts = [];
        foreach ($amountRows as $row) {
            $amounts[$row->period_index][$row->project_account_id] = (float) $row->amount;
        }

        $lockKeys = $scenarioKey === 0 ? [0] : [$scenarioKey, 0];
        $lock = ProjectPlanLock::query()
            ->where('project_record_id', $project->id)
            ->where('project_plan_year_id', $planYear->id)
            ->whereIn('scenario_key', $lockKeys)
            ->where('is_locked', 1)
            ->orderByRaw('scenario_key = ? desc', [$scenarioKey])
            ->first();

        return response()->json([
            'plan_year_id' => $planYear->id,
            'scenario_id'  => $scenarioId,
            'periods'      => $periods,
            'accounts'     => $accounts,
            'amounts'      => $amounts,
            'lock'         => $lock ? [
                'is_locked' => (bool) $lock->is_locked,
                'locked_by_user_id' => $lock->locked_by_user_id,
                'locked_at' => optional($lock->locked_at)->toDateTimeString(),
            ] : [
                'is_locked' => false,
                'locked_by_user_id' => null,
                'locked_at' => null,
            ],
        ]);
    }

    /**
     * Save plan amounts (batch upsert).
     */
    public function save(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'plan_year_id' => ['nullable', 'integer', 'exists:project_plan_years,id'],
            'fiscal_year'  => ['required_without:plan_year_id', 'integer'],
            'start_month'  => ['required_without:plan_year_id', 'integer', 'min:1', 'max:12'],
            'scenario_id'  => ['sometimes', 'nullable', 'integer'],
            'scenario_code'=> ['sometimes', 'nullable', 'string', 'max:50'],
            'months'       => ['sometimes', 'array'],
            'months.*.period_index' => ['required', 'integer', 'min:1', 'max:12'],
            'months.*.account_id'   => ['required', 'integer'],
            'months.*.amount'       => ['nullable', 'numeric'],
        ]);

        $planYear = $data['plan_year_id']
            ? ProjectPlanYear::findOrFail($data['plan_year_id'])
            : $this->ensurePlanYear($data['fiscal_year'], $data['start_month']);

        $this->coaInstaller->installForProject($project);

        $scenarioId = $this->resolveScenario($project, $data['scenario_id'] ?? null, $data['scenario_code'] ?? null);
        $scenarioKey = $scenarioId ?? 0;
        $this->assertNotLocked($project->id, $planYear->id, $scenarioKey);

        $validAccountIds = ProjectAccount::where('project_record_id', $project->id)->pluck('id')->all();
        $validAccountSet = array_flip($validAccountIds);

        $rows = [];
        foreach ($data['months'] as $row) {
            $accountId = (int) $row['account_id'];
            $periodIdx = (int) $row['period_index'];

            if (!isset($validAccountSet[$accountId])) {
                abort(422, 'Invalid account_id for this project: ' . $accountId);
            }

            $amount = $row['amount'] ?? 0;
            $rows[] = [
                'project_record_id'        => $project->id,
                'project_plan_year_id'     => $planYear->id,
                'project_account_id'       => $accountId,
                'project_plan_scenario_id' => $scenarioId,
                'scenario_key'             => $scenarioId ?? 0,
                'period_index'             => $periodIdx,
                'amount'                   => $amount,
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }

        DB::transaction(function () use ($project, $planYear, $scenarioId, $scenarioKey, $rows) {
            DB::table('project_plan_amounts')
                ->where('project_record_id', $project->id)
                ->where('project_plan_year_id', $planYear->id)
                ->where('scenario_key', $scenarioKey)
                ->delete();

            if (count($rows)) {
                DB::table('project_plan_amounts')->insert($rows);
            }
        });

        return response()->json(['status' => 'ok', 'updated' => count($rows)]);
    }

    /**
     * List scenarios for a project.
     */
    public function scenarios(ProjectRecord $project)
    {
        $scenarios = ProjectPlanScenario::query()
            ->where('project_record_id', $project->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'weight', 'is_default', 'sort_order']);

        return response()->json($scenarios);
    }

    /**
     * Create a scenario (optionally provide code; defaults to slugified name).
     */
    public function scenarioStore(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:191'],
            'code'   => ['sometimes', 'nullable', 'string', 'max:191'],
            'weight' => ['sometimes', 'numeric'],
        ]);

        $code = $data['code'] ?? Str::slug($data['name'], '_');

        $exists = ProjectPlanScenario::where('project_record_id', $project->id)
            ->where('code', $code)
            ->exists();
        abort_if($exists, 422, '同じコードのシナリオが既に存在します。');

        $scenario = ProjectPlanScenario::create([
            'project_record_id' => $project->id,
            'name'              => $data['name'],
            'code'              => $code,
            'weight'            => $data['weight'] ?? 1.0,
            'is_default'        => false,
            'sort_order'        => 0,
        ]);

        return response()->json(['id' => $scenario->id], 201);
    }

    /**
     * Update a scenario.
     */
    public function scenarioUpdate(ProjectRecord $project, ProjectPlanScenario $scenario, Request $request)
    {
        abort_if($scenario->project_record_id !== $project->id, 404);

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:191'],
            'code'      => ['sometimes', 'string', 'max:191'],
            'weight'    => ['sometimes', 'numeric'],
            'is_default'=> ['sometimes', 'boolean'],
        ]);

        if (isset($data['code'])) {
            $exists = ProjectPlanScenario::where('project_record_id', $project->id)
                ->where('code', $data['code'])
                ->where('id', '!=', $scenario->id)
                ->exists();
            abort_if($exists, 422, '同じコードのシナリオが既に存在します。');
        }

        DB::transaction(function () use ($project, $scenario, $data) {
            if (!empty($data['is_default'])) {
                ProjectPlanScenario::where('project_record_id', $project->id)->update(['is_default' => false]);
            }
            $scenario->update($data);
        });

        return response()->json(['status' => 'ok']);
    }

    /**
     * Delete a scenario (amounts will null the FK because of nullOnDelete).
     */
    public function scenarioDestroy(ProjectRecord $project, ProjectPlanScenario $scenario)
    {
        abort_if($scenario->project_record_id !== $project->id, 404);
        $scenario->delete();
        return response()->json(['status' => 'ok']);
    }

    /**
     * List accounts for a project.
     */
    public function accounts(ProjectRecord $project)
    {
        $accounts = ProjectAccount::query()
            ->where('project_record_id', $project->id)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id','code','name','path','depth','is_postable','is_formula','formula','is_active','parent_id','sort_order']);

        return response()->json($accounts);
    }

    /**
     * Create an account under the project (optional parent).
     */
    public function accountStore(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'parent_id'   => ['sometimes', 'nullable', 'integer'],
            'code'        => ['required', 'string', 'max:191'],
            'name'        => ['required', 'string', 'max:191'],
            'is_postable' => ['required', 'boolean'],
            'is_formula'  => ['sometimes', 'boolean'],
            'formula'     => ['sometimes', 'nullable', 'string'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ]);

        $parent = null;
        if (!empty($data['parent_id'])) {
            $parent = ProjectAccount::where('project_record_id', $project->id)->where('id', $data['parent_id'])->first();
            abort_if(! $parent, 422, '親科目が見つかりません。');
        }

        $exists = ProjectAccount::where('project_record_id', $project->id)->where('code', $data['code'])->exists();
        abort_if($exists, 422, '同じコードの科目が既に存在します。');

        $path = rtrim($parent?->path ?? '/', '/') . '/' . $data['code'] . '/';
        $depth = ($parent?->depth ?? -1) + 1;
        $sort = $data['sort_order'] ?? (ProjectAccount::where('project_record_id', $project->id)->max('sort_order') + 1);
        $isFormula = !empty($data['is_formula']);
        if ($isFormula) {
            $data['is_postable'] = false;
        }

        $acct = ProjectAccount::create([
            'project_record_id' => $project->id,
            'parent_id'         => $parent?->id,
            'code'              => $data['code'],
            'name'              => $data['name'],
            'path'              => $path,
            'depth'             => $depth,
            'is_postable'       => $data['is_postable'],
            'is_formula'        => $isFormula,
            'formula'           => $isFormula ? ($data['formula'] ?? null) : null,
            'is_active'         => true,
            'sort_order'        => $sort,
        ]);

        return response()->json(['id' => $acct->id], 201);
    }

    /**
     * Update basic fields (no moves/recode for safety).
     */
    public function accountUpdate(ProjectRecord $project, ProjectAccount $account, Request $request)
    {
        abort_if($account->project_record_id !== $project->id, 404);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'is_active'   => ['sometimes', 'boolean'],
            'is_postable' => ['sometimes', 'boolean'],
            'is_formula'  => ['sometimes', 'boolean'],
            'formula'     => ['sometimes', 'nullable', 'string'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ]);

        if (!empty($data['is_formula'])) {
            $data['is_postable'] = false;
        }

        $account->update($data);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Delete an account (cascades to plan amounts).
     */
    public function accountDestroy(ProjectRecord $project, ProjectAccount $account)
    {
        abort_if($account->project_record_id !== $project->id, 404);
        $account->delete();
        return response()->json(['status' => 'ok']);
    }

    /**
     * Sync template accounts into an existing project (add missing entries).
     */
    public function syncTemplate(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'overwrite' => ['sometimes', 'boolean'],
        ]);

        $before = ProjectAccount::where('project_record_id', $project->id)->count();
        $this->coaInstaller->syncForProject($project, (bool) ($data['overwrite'] ?? false));
        $after = ProjectAccount::where('project_record_id', $project->id)->count();

        return response()->json([
            'ok' => true,
            'added' => max(0, $after - $before),
        ]);
    }

    /**
     * Download a plan template for Excel input (accounts x 12 months).
     */
    public function downloadTemplate(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'fiscal_year'  => ['required', 'integer'],
            'start_month'  => ['required', 'integer', 'min:1', 'max:12'],
            'scenario_id'  => ['sometimes', 'nullable', 'integer'],
            'scenario_code'=> ['sometimes', 'nullable', 'string'],
        ]);

        $planYear = $this->ensurePlanYear($data['fiscal_year'], $data['start_month']);
        $this->coaInstaller->installForProject($project);
        $scenarioId = $this->resolveScenario($project, $data['scenario_id'] ?? null, $data['scenario_code'] ?? null);
        // scenarioId is unused for template generation but accepted for symmetry

        $periods = DB::table('project_v_plan_months')
            ->where('plan_year_id', $planYear->id)
            ->orderBy('period_index')
            ->get()
            ->map(fn($p) => [
                'label' => sprintf('%04d-%02d', $p->calendar_year, $p->calendar_month),
                'period_index' => (int) $p->period_index,
            ])->values()->all();

        $accounts = ProjectAccount::query()
            ->where('project_record_id', $project->id)
            ->where('is_postable', 1)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id','code','name'])
            ->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name])
            ->all();

        $export = new PlanTemplateExport($project->name ?? 'project', $accounts, $periods);

        $filename = ($project->name ?? 'project') . "_{$data['fiscal_year']}_template.xlsx";
        return Excel::download($export, $filename);
    }

    /**
     * Upload plan amounts from Excel template.
     */
    public function uploadTemplate(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'file'        => ['required','file','mimes:xlsx,xls'],
            'fiscal_year' => ['required', 'integer'],
            'start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'scenario_id' => ['sometimes', 'nullable', 'integer'],
            'scenario_code'=> ['sometimes', 'nullable', 'string'],
            'dry_run'     => ['sometimes', 'boolean'],
        ]);

        $planYear = $this->ensurePlanYear($data['fiscal_year'], $data['start_month']);
        $this->coaInstaller->installForProject($project);
        $scenarioId = $this->resolveScenario($project, $data['scenario_id'] ?? null, $data['scenario_code'] ?? null);
        $scenarioKey = $scenarioId ?? 0;

        if (! $request->boolean('dry_run')) {
            $this->assertNotLocked($project->id, $planYear->id, $scenarioKey);
        }

        $periods = DB::table('project_v_plan_months')
            ->where('plan_year_id', $planYear->id)
            ->orderBy('period_index')
            ->get()
            ->map(fn($p) => [
                'label' => sprintf('%04d-%02d', $p->calendar_year, $p->calendar_month),
                'period_index' => (int) $p->period_index,
            ])->values()->all();

        $import = new PlanTemplateImport(
            $project->id,
            $planYear->id,
            $scenarioId,
            $periods,
            $request->boolean('dry_run')
        );

        Excel::import($import, $data['file']);

        return response()->json(['ok' => true, 'summary' => $import->summary]);
    }

    public function lock(ProjectRecord $project, Request $request)
    {
        $data = $request->validate([
            'plan_year_id' => ['nullable', 'integer', 'exists:project_plan_years,id'],
            'fiscal_year'  => ['required_without:plan_year_id', 'integer'],
            'start_month'  => ['required_without:plan_year_id', 'integer', 'min:1', 'max:12'],
            'scenario_id'  => ['sometimes', 'nullable', 'integer'],
            'scenario_code'=> ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $planYear = $data['plan_year_id']
            ? ProjectPlanYear::findOrFail($data['plan_year_id'])
            : $this->ensurePlanYear($data['fiscal_year'], $data['start_month']);

        $scenarioId = $this->resolveScenario($project, $data['scenario_id'] ?? null, $data['scenario_code'] ?? null);
        $scenarioKey = $scenarioId ?? 0;

        $lock = ProjectPlanLock::updateOrCreate(
            [
                'project_record_id' => $project->id,
                'project_plan_year_id' => $planYear->id,
                'scenario_key' => $scenarioKey,
            ],
            [
                'project_plan_scenario_id' => $scenarioId,
                'is_locked' => true,
                'locked_by_user_id' => Auth::id(),
                'locked_at' => now(),
            ]
        );

        return response()->json(['ok' => true, 'lock' => [
            'is_locked' => (bool) $lock->is_locked,
            'locked_by_user_id' => $lock->locked_by_user_id,
            'locked_at' => optional($lock->locked_at)->toDateTimeString(),
        ]]);
    }

    public function unlock(ProjectRecord $project, Request $request)
    {
        $user = $this->activeUser();
        abort_unless($user->isAdmin(), 403, '解除権限がありません。');

        $data = $request->validate([
            'plan_year_id' => ['nullable', 'integer', 'exists:project_plan_years,id'],
            'fiscal_year'  => ['required_without:plan_year_id', 'integer'],
            'start_month'  => ['required_without:plan_year_id', 'integer', 'min:1', 'max:12'],
            'scenario_id'  => ['sometimes', 'nullable', 'integer'],
            'scenario_code'=> ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $planYear = $data['plan_year_id']
            ? ProjectPlanYear::findOrFail($data['plan_year_id'])
            : $this->ensurePlanYear($data['fiscal_year'], $data['start_month']);

        $scenarioId = $this->resolveScenario($project, $data['scenario_id'] ?? null, $data['scenario_code'] ?? null);
        $scenarioKey = $scenarioId ?? 0;

        ProjectPlanLock::updateOrCreate(
            [
                'project_record_id' => $project->id,
                'project_plan_year_id' => $planYear->id,
                'scenario_key' => $scenarioKey,
            ],
            [
                'project_plan_scenario_id' => $scenarioId,
                'is_locked' => false,
            ]
        );

        return response()->json(['ok' => true]);
    }

    private function assertNotLocked(int $projectId, int $planYearId, int $scenarioKey): void
    {
        $lockKeys = $scenarioKey === 0 ? [0] : [$scenarioKey, 0];
        $lock = ProjectPlanLock::query()
            ->where('project_record_id', $projectId)
            ->where('project_plan_year_id', $planYearId)
            ->whereIn('scenario_key', $lockKeys)
            ->where('is_locked', 1)
            ->orderByRaw('scenario_key = ? desc', [$scenarioKey])
            ->first();

        if ($lock && !Auth::user()?->isAdmin()) {
            abort(403, '確定済みのため編集できません。');
        }
    }

    private function ensurePlanYear(int $fiscalYear, int $startMonth): ProjectPlanYear
    {
        $startMonth = max(1, min(12, $startMonth));
        // Treat the entered fiscal_year as the start year of the plan window
        $startYear = $fiscalYear;

        $code = sprintf('FY%d-M%02d', $fiscalYear, $startMonth); // include start month to keep code unique per fiscal window

        return ProjectPlanYear::updateOrCreate(
            [
                'fiscal_year' => $fiscalYear,
                'start_month' => $startMonth,
            ],
            [
                'code'      => $code,
                'name'      => $code,
                'starts_on' => Carbon::create($startYear, $startMonth, 1)->toDateString(),
                'months'    => 12,
            ]
        );
    }

    private function resolveScenario(ProjectRecord $project, ?int $scenarioId, ?string $scenarioCode): ?int
    {
        if ($scenarioId) {
            $exists = ProjectPlanScenario::where('project_record_id', $project->id)
                ->where('id', $scenarioId)
                ->exists();
            abort_unless($exists, 404, 'シナリオが見つかりません。');
            return $scenarioId;
        }

        if ($scenarioCode) {
            $scenario = ProjectPlanScenario::firstOrCreate(
                ['project_record_id' => $project->id, 'code' => $scenarioCode],
                ['name' => $scenarioCode, 'is_default' => false, 'weight' => 1.0, 'sort_order' => 0]
            );
            return $scenario->id;
        }

        return null;
    }
}
