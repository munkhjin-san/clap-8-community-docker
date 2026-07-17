<?php

namespace App\Http\Controllers;

use App\Models\CalendarRecord;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Services\ZoomApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminZoomAccountController extends Controller
{
    public function __construct(private readonly ZoomApiService $zoomApi) {}

    public function index(): JsonResponse
    {
        $this->authorizeAdmin();

        $accounts = ZoomAccount::query()
            ->orderBy('slot')
            ->get()
            ->map(fn (ZoomAccount $account) => $account->adminPayload());

        return response()->json($accounts);
    }

    public function update(Request $request, ZoomAccount $zoomAccount): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatedAccount($request, $zoomAccount);

        $this->zoomApi->forgetToken($zoomAccount);
        $zoomAccount->update($validated);

        return response()->json($zoomAccount->fresh()->adminPayload());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validatedAccount($request);
        $maxSlot = ZoomAccount::query()->max('slot');
        $validated['slot'] = $maxSlot === null ? 0 : ((int) $maxSlot) + 1;
        $zoomAccount = ZoomAccount::query()->create($validated);

        return response()->json($zoomAccount->adminPayload(), 201);
    }

    public function destroy(ZoomAccount $zoomAccount): JsonResponse
    {
        $this->authorizeAdmin();

        $isUsed = CalendarRecord::withTrashed()->where('zoom_value', $zoomAccount->slot)->exists();
        if ($isUsed) {
            throw ValidationException::withMessages([
                'message' => '使用履歴があるWeb会議設定は削除できません。無効にしてください。',
            ]);
        }

        $this->zoomApi->forgetToken($zoomAccount);
        $zoomAccount->delete();

        return response()->json(['message' => 'Web会議設定を削除しました。']);
    }

    private function validatedAccount(Request $request, ?ZoomAccount $zoomAccount = null): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'host_email' => ['required', 'email', 'max:255'],
            'host_password' => ['nullable', 'string', 'max:255'],
            'account_id' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
            'webhook_secret' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', Rule::in([true, false, 0, 1, '0', '1'])],
        ]);

        foreach (['host_password', 'client_secret', 'webhook_secret'] as $secretField) {
            if (! filled($validated[$secretField] ?? null)) {
                unset($validated[$secretField]);
            }
        }

        $willBeActive = filter_var($validated['active'], FILTER_VALIDATE_BOOL);
        $requiredConfiguration = [
            'host_email' => $validated['host_email'] ?? $zoomAccount?->host_email,
            'account_id' => $validated['account_id'] ?? $zoomAccount?->account_id,
            'client_id' => $validated['client_id'] ?? $zoomAccount?->client_id,
            'client_secret' => $validated['client_secret'] ?? $zoomAccount?->client_secret,
        ];

        if ($willBeActive && collect($requiredConfiguration)->contains(fn ($value) => ! filled($value))) {
            throw ValidationException::withMessages([
                'message' => '有効にするにはOAuth認証情報とホストメールを設定してください。',
            ]);
        }

        $validated['active'] = $willBeActive;

        return $validated;
    }

    public function test(Request $request, ZoomAccount $zoomAccount): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'host_email' => ['nullable', 'email', 'max:255'],
            'account_id' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
        ]);
        $draftAccount = $zoomAccount->replicate();
        $draftAccount->id = $zoomAccount->id;

        foreach (['host_email', 'account_id', 'client_id', 'client_secret'] as $field) {
            if (filled($validated[$field] ?? null)) {
                $draftAccount->{$field} = $validated[$field];
            }
        }

        return response()->json([
            'message' => 'Zoom APIへの接続に成功しました。',
            'meeting' => $this->zoomApi->testConnection($draftAccount, false),
        ]);
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');

        // Double-account dropped + admin gate via community role (was hardcoded ADMIN_USER_IDS).
        abort_unless($user->isAdmin(), 403, '管理者権限がありません。');
    }
}
