<?php

namespace App\Http\Controllers;

use App\Models\CommunityMembership;
use App\Models\CommunityRole;
use App\Services\Community\CommunityBladeCatalog;
use App\Services\Community\CommunityContext;
use App\Services\Community\CommunityPermissionService;
use App\Services\Community\CommunityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityContextController extends Controller
{
    public function __construct(
        private CommunityContext $context,
        private CommunityResolver $resolver,
        private CommunityPermissionService $permissions
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->context->authPayload($request->user()));
    }

    public function switch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'community_id' => ['required', 'integer'],
        ]);

        $this->resolver->switch($request->user(), (int) $validated['community_id']);

        return response()->json($this->context->authPayload($request->user()->fresh()));
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($this->permissions->can('community.manage'), 403);

        $community = $this->context->community();
        abort_unless($community, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon_path' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $community->only(['name', 'config']);
        $config = $community->config ?? [];
        $config['icon_path'] = $validated['icon_path'] ?? null;
        unset($config['icon_url']);

        $community->update([
            'name' => $validated['name'],
            'config' => $config,
        ]);

        $this->audit('community.updated', null, $before, $community->fresh()->only(['name', 'config']));

        return response()->json($this->context->authPayload($request->user()->fresh()));
    }

    public function roles(Request $request): JsonResponse
    {
        abort_unless($this->permissions->can('role.manage'), 403);

        $community = $this->context->community();
        abort_unless($community, 404);

        $roles = $community->roles()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CommunityRole $role) => $this->rolePayload($role));

        return response()->json($roles);
    }

    public function capabilities(): JsonResponse
    {
        abort_unless($this->permissions->can('role.manage'), 403);

        return response()->json([
            'groups' => CommunityBladeCatalog::groups(),
        ]);
    }

    public function createRole(Request $request): JsonResponse
    {
        abort_unless($this->permissions->can('role.manage'), 403);

        $community = $this->context->community();
        abort_unless($community, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capabilities' => ['sometimes', 'array'],
            'capabilities.*' => ['string', 'max:120', Rule::in(CommunityBladeCatalog::keys())],
        ]);

        $sortOrder = ((int) $community->roles()->max('sort_order')) + 10;
        $role = $community->roles()->create([
            'key' => 'role_'.Str::lower((string) Str::ulid()),
            'name' => $validated['name'],
            'sort_order' => $sortOrder,
            'capabilities' => $validated['capabilities'] ?? [],
            'scopes' => [],
            'is_system' => false,
        ]);

        $this->audit('role.created', null, null, $role->only(['name', 'capabilities']));

        return response()->json($this->rolePayload($role), 201);
    }

    public function updateRole(Request $request, CommunityRole $role): JsonResponse
    {
        abort_unless($this->permissions->can('role.manage'), 403);
        abort_unless((int) $role->community_id === (int) $this->context->communityId(), 404);
        abort_if($role->key === 'admin', 422, '管理者ロールは編集できません。');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capabilities' => ['sometimes', 'array'],
            'capabilities.*' => ['string', 'max:120', Rule::in(CommunityBladeCatalog::keys())],
        ]);

        $before = $role->only(['name', 'capabilities']);
        $role->update([
            'name' => $validated['name'],
            'capabilities' => $validated['capabilities'] ?? $role->capabilities ?? [],
        ]);

        $this->audit('role.updated', null, $before, $role->fresh()->only(['name', 'capabilities']));

        return response()->json($this->rolePayload($role->fresh()));
    }

    public function deleteRole(CommunityRole $role): JsonResponse
    {
        abort_unless($this->permissions->can('role.manage'), 403);
        abort_unless((int) $role->community_id === (int) $this->context->communityId(), 404);
        abort_if($role->key === 'admin', 422, '管理者ロールは削除できません。');
        abort_if($role->memberships()->exists(), 422, 'メンバーが所属しているロールは削除できません。');

        $before = $role->only(['name', 'capabilities', 'scopes']);
        $role->delete();

        $this->audit('role.deleted', null, $before, null);

        return response()->json(['deleted' => true]);
    }

    public function syncRoleMembers(Request $request, CommunityRole $role): JsonResponse
    {
        abort_unless($this->permissions->can('role.manage'), 403);
        abort_unless((int) $role->community_id === (int) $this->context->communityId(), 404);

        $validated = $request->validate([
            'user_ids' => ['present', 'array'],
            'user_ids.*' => ['integer'],
        ]);

        $communityId = (int) $this->context->communityId();
        $userIds = array_values(array_unique(array_map('intval', $validated['user_ids'])));
        $memberRole = CommunityRole::where('community_id', $communityId)->where('key', 'member')->first();

        DB::transaction(function () use ($role, $communityId, $userIds, $memberRole) {
            $currentIds = DB::table('community_user')
                ->where('community_id', $communityId)
                ->where('community_role_id', $role->id)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            // Assign selected users to this role (create membership if missing).
            foreach ($userIds as $userId) {
                $existing = DB::table('community_user')
                    ->where('community_id', $communityId)
                    ->where('user_id', $userId)
                    ->first();

                if ($existing) {
                    if ((int) $existing->community_role_id !== (int) $role->id) {
                        DB::table('community_user')->where('id', $existing->id)
                            ->update(['community_role_id' => $role->id, 'updated_at' => now()]);
                    }
                } else {
                    DB::table('community_user')->insert([
                        'community_id' => $communityId,
                        'user_id' => $userId,
                        'community_role_id' => $role->id,
                        'scope' => 'internal',
                        'is_default' => true,
                        'last_active_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Users removed from this role fall back to the base member role
            // (no fallback when editing the member role itself — nothing below it).
            $removed = array_values(array_diff($currentIds, $userIds));
            if ($removed !== [] && $memberRole && $role->key !== 'member') {
                DB::table('community_user')
                    ->where('community_id', $communityId)
                    ->whereIn('user_id', $removed)
                    ->update(['community_role_id' => $memberRole->id, 'updated_at' => now()]);
            }

            // Never leave the community without an admin.
            $adminRoleIds = CommunityRole::where('community_id', $communityId)->where('key', 'admin')->pluck('id');
            $adminCount = DB::table('community_user')
                ->where('community_id', $communityId)
                ->whereIn('community_role_id', $adminRoleIds)
                ->count();
            abort_if($adminCount < 1, 422, '管理者ロールには最低1名のメンバーが必要です。');
        });

        $this->audit('role.members_updated', null, null, ['role' => $role->key, 'user_ids' => $userIds]);

        return response()->json($this->rolePayload($role->fresh()));
    }

    private function rolePayload(CommunityRole $role): array
    {
        $role->loadCount('memberships')->load(['memberships.user:id,name,icon_path,icon_bg']);

        return [
            'id' => $role->id,
            'key' => $role->key,
            'name' => $role->name,
            'sort_order' => $role->sort_order,
            'capabilities' => $role->capabilities ?? [],
            'is_system' => $role->is_system,
            'memberships_count' => $role->memberships_count,
            'members' => $role->memberships
                ->map(fn (CommunityMembership $membership) => $membership->user)
                ->filter()
                ->values(),
        ];
    }

    public function updateMembership(Request $request, CommunityMembership $membership): JsonResponse
    {
        abort_unless($this->permissions->can('user.manage'), 403);
        abort_unless((int) $membership->community_id === (int) $this->context->communityId(), 404);

        $validated = $request->validate([
            'community_role_id' => [
                'required',
                'integer',
                Rule::exists('community_roles', 'id')->where('community_id', $this->context->communityId()),
            ],
            'scope' => ['required', Rule::in([
                CommunityMembership::SCOPE_INTERNAL,
                CommunityMembership::SCOPE_PARTNER,
                CommunityMembership::SCOPE_REGISTERED,
                CommunityMembership::SCOPE_EXTERNAL,
            ])],
        ]);

        $targetRole = CommunityRole::where('community_id', $this->context->communityId())
            ->where('id', $validated['community_role_id'])
            ->firstOrFail();

        if ($membership->role?->key === 'admin' && $targetRole->key !== 'admin') {
            $adminCount = CommunityMembership::query()
                ->where('community_id', $this->context->communityId())
                ->whereHas('role', fn ($query) => $query->where('key', 'admin'))
                ->count();

            abort_if($adminCount <= 1, 422, '管理者ロールには最低1名のメンバーが必要です。');
        }

        $before = $membership->only(['community_role_id', 'scope']);
        $membership->update($validated);

        $this->audit('membership.updated', $membership->user_id, $before, $membership->fresh()->only(['community_role_id', 'scope']));

        return response()->json($membership->fresh(['user:id,name,icon_path,icon_bg', 'role']));
    }

    private function audit(string $action, ?int $targetUserId, ?array $before, ?array $after): void
    {
        $community = $this->context->community();

        if (!$community) {
            return;
        }

        $community->memberships()->getModel()->newQuery()->getConnection()->table('community_membership_audit_logs')->insert([
            'community_id' => $community->id,
            'user_id' => $targetUserId,
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'before' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
