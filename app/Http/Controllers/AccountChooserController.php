<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Community\CommunityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountChooserController extends Controller
{
    public const COOKIE_NAME = 'remembered_account_ids';
    public const SESSION_KEY = 'remembered_account_ids';
    private const COOKIE_MINUTES = 60 * 24 * 180;

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'accounts' => $this->rememberedAccounts($request),
            'active_user_id' => Auth::id(),
        ]);
    }

    public function remember(Request $request): JsonResponse
    {
        $this->queueRememberedAccountsCookie($request, $this->rememberedIds($request));

        return $this->index($request);
    }

    public function switch(Request $request, CommunityResolver $communityResolver): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $targetId = (int) $validated['user_id'];
        abort_unless(in_array($targetId, $this->rememberedIds($request), true), 403);

        $target = User::findOrFail($targetId);

        Auth::login($target);
        $request->session()->regenerate();
        $communityResolver->resolveFor($target);
        $this->queueRememberedAccountsCookie($request, $this->rememberedIds($request, $targetId));

        return response()->json([
            'user' => $target->fresh(),
            'accounts' => $this->rememberedAccounts($request, $targetId),
        ]);
    }

    /**
     * @return array<int, array{id:int,name:?string,icon_path:?string,icon_bg:?string}>
     */
    private function rememberedAccounts(Request $request, ?int $extraId = null): array
    {
        $ids = $this->rememberedIds($request, $extraId);

        $order = array_flip($ids);

        return User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'icon_path', 'icon_bg'])
            ->sortBy(fn (User $user) => $order[$user->id] ?? PHP_INT_MAX)
            ->values()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,


                'icon_path' => $user->icon_path,
                'icon_bg' => $user->icon_bg,
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function rememberedIds(Request $request, ?int $extraId = null): array
    {
        $cookieValue = $request->cookie(self::COOKIE_NAME, '[]');
        $ids = is_array($cookieValue) ? $cookieValue : json_decode((string) $cookieValue, true);
        $ids = is_array($ids) ? $ids : [];
        $ids = array_merge($ids, (array) $request->session()->get(self::SESSION_KEY, []));

        if (Auth::id()) {
            $ids[] = (int) Auth::id();
        }

        if ($extraId) {
            $ids[] = $extraId;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * @param array<int, int> $ids
     */
    private function queueRememberedAccountsCookie(Request $request, array $ids): void
    {
        $request->session()->put(self::SESSION_KEY, array_values(array_unique($ids)));

        cookie()->queue(cookie(
            self::COOKIE_NAME,
            json_encode(array_values(array_unique($ids))),
            self::COOKIE_MINUTES,
            null,
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));
    }
}
