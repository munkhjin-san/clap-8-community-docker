import { User } from '@/interface/globalInterface'
import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

type CommunityRole = {
    id: number
    key: string
    name: string
    capabilities: string[]
    scopes?: string[]
}
type CommunitySummary = {
    id: number
    name: string
    slug: string
    status: string
    config?: {
        icon_path?: string | null
        [key: string]: unknown
    }
    scope: 'internal' | 'partner' | 'registered' | 'external'
    is_default: boolean
    role: CommunityRole | null
}
type AuthUser = User & {
    active_community?: CommunitySummary | null
    active_membership?: any
    communities?: CommunitySummary[]
    community_scope?: CommunitySummary['scope']
    community_role?: CommunityRole | null
    community_capabilities?: string[]
}

export const useAuthUserStore = defineStore('authUser', () => {
    const name = ref<string>('')
    const id = ref<number | null>(null)
    const user = ref<AuthUser>({} as AuthUser)
    const isPartner = ref<boolean>(false)
    const isRegistered = ref<boolean>(false)
    const isOnLeave = ref<boolean>(false)
    const communities = ref<CommunitySummary[]>([])
    const activeCommunity = ref<CommunitySummary | null>(null)
    const communityScope = ref<CommunitySummary['scope'] | null>(null)
    const communityRole = ref<CommunityRole | null>(null)
    const communityCapabilities = ref<string[]>([])
    const accountChooserAccounts = ref<User[]>([])

    function setUser(payload: any) {
        id.value = payload?.id ?? null
        name.value = payload?.name ?? ''
        // Merge so a PARTIAL user payload (profile / settings / weather / sign
        // endpoints) doesn't drop fields already on the user.
        user.value = { ...((user.value as any) ?? {}), ...(payload ?? {}) }

        // Community context (role / capabilities / scope) is owned by the
        // authoritative auth payload — Root bootstrap (`auth_user`) and the
        // /community_context endpoints. Partial user payloads omit those flattened
        // keys, so we must only re-hydrate when the payload actually carries them;
        // otherwise we'd reset capabilities to [] and the side menu would vanish.
        const carriesCommunityContext = !!payload && (
            'community_capabilities' in payload ||
            'community_role' in payload ||
            'active_membership' in payload ||
            'communities' in payload
        )
        if (carriesCommunityContext) {
            communities.value = payload.communities ?? communities.value
            activeCommunity.value = payload.active_community
                ?? communities.value.find((community) => community.id === payload?.active_membership?.community_id)
                ?? activeCommunity.value
            communityScope.value = payload.community_scope ?? payload?.active_membership?.scope ?? communityScope.value
            communityRole.value = payload.community_role ?? payload?.active_membership?.role ?? communityRole.value
            communityCapabilities.value = payload.community_capabilities ?? communityRole.value?.capabilities ?? []
        }

        isPartner.value = communityScope.value === 'partner' || payload?.partner_flag == 1
        isRegistered.value = communityScope.value === 'registered' || payload?.position_id == 15
        if (payload && 'on_leave' in payload) {
            isOnLeave.value = payload.on_leave
        }
    }

    function setFooterView(payload: boolean) {
        if (!user.value) return
            ; (user.value as any).footer_view = payload
    }

    async function setActiveUser(payload: number) {
        const response = await axios
            .post('/account_chooser/switch', { user_id: payload })
            .then((res) => res.data)
        setUser(response?.user)
        accountChooserAccounts.value = response?.accounts ?? []
    }

    async function switchCommunity(communityId: number) {
        const response = await axios
            .patch('/community_context/switch', { community_id: communityId })
            .then((res) => res.data)
        applyCommunityPayload(response)
    }

    async function updateActiveCommunity(payload: { name: string; icon_path?: string | null }) {
        const response = await axios
            .patch('/community_context', payload)
            .then((res) => res.data)
        applyCommunityPayload(response)
    }

    async function createCommunity(payload: { name: string; icon_path?: string | null }) {
        // Backend creates the community, seeds default roles, assigns the caller as
        // admin, then switches context and returns the new community's auth payload.
        const response = await axios
            .post('/community_context', payload)
            .then((res) => res.data)
        applyCommunityPayload(response)
    }

    async function refreshCommunityContext() {
        const response = await axios
            .get('/community_context')
            .then((res) => res.data)
        applyCommunityPayload(response)
    }

    function applyCommunityPayload(response: any) {
        communities.value = response?.communities ?? []
        activeCommunity.value = response?.active_community ?? null
        communityScope.value = response?.community_scope ?? null
        communityRole.value = response?.community_role ?? null
        communityCapabilities.value = response?.community_capabilities ?? []
        if (user.value) {
            user.value = {
                ...user.value,
                communities: communities.value,
                active_community: activeCommunity.value,
                active_membership: response?.active_membership,
                community_scope: communityScope.value ?? undefined,
                community_role: communityRole.value,
                community_capabilities: communityCapabilities.value,
            }
        }
        isPartner.value = communityScope.value === 'partner'
        isRegistered.value = communityScope.value === 'registered'
    }

    async function loadAccountChooserAccounts() {
        const response = await axios.get('/account_chooser/accounts').then((res) => res.data)
        accountChooserAccounts.value = response?.accounts ?? []
    }

    const activeUser = computed((): AuthUser => {
        return user.value
    })

    // INTERNAL ONLY — raw capability check with NO admin bypass. Used to build
    // isAdmin/isBoss/isPM/hasPrivilage below. NOT exported: components must use
    // can() (admin super-role bypass) for permission gates. (BE User::hasCapability
    // is bypass-inclusive; do not confuse the two.)
    const hasRawCapability = (capability: string): boolean => communityCapabilities.value.includes(capability)

    const isAdmin = computed((): boolean => communityRole.value?.key === 'admin' || hasRawCapability('admin.access'))

    // True only inside the glowd community (Community::DEFAULT_SLUG). Gates
    // glowd-exclusive features (グラウドナイン / リフレッシュ / 各種届出, …) that are
    // not part of the generic community feature set. slug is DB-unique, so this
    // is an exact, reliable match.
    const isGlowdCommunity = computed((): boolean => activeCommunity.value?.slug === 'glowd')

    // Capability check with admin super-role bypass. Use for app-access and action capabilities.
    const can = (capability: string): boolean => isAdmin.value || communityCapabilities.value.includes(capability)

    const hasPrivilage = computed((): boolean => {
        const positionId = activeUser.value?.position_id
        return hasRawCapability('project.approve') || hasRawCapability('project.manage_assigned') || (typeof positionId === 'number' && positionId <= 6) || isAdmin.value
    })

    const isBoss = computed(() => communityRole.value?.key === 'board' || hasRawCapability('project.approve') || (activeUser.value && activeUser.value.position_id && activeUser.value.position_id < 6))

    const isPM = computed(() => communityRole.value?.key === 'pm' || hasRawCapability('project.manage_assigned') || (activeUser.value && activeUser.value.position_id == 6))

    const isMentor = computed(() => activeUser.value && (activeUser.value.general_position && activeUser.value.general_position !== '一般社員') || isBoss.value)

    const isEmployee = computed(() => activeUser.value && (activeUser.value.positions && (activeUser.value.positions.sort_flag > 6 && activeUser.value.positions.sort_flag <= 14)))
    return {
        name,
        id,
        user,
        isPartner,
        isRegistered,
        isOnLeave,
        communities,
        activeCommunity,
        communityScope,
        communityRole,
        communityCapabilities,
        accountChooserAccounts,
        setUser,
        setFooterView,
        setActiveUser,
        switchCommunity,
        updateActiveCommunity,
        createCommunity,
        refreshCommunityContext,
        loadAccountChooserAccounts,
        activeUser,
        can,
        hasPrivilage,
        isAdmin,
        isGlowdCommunity,
        isBoss,
        isPM,
        isMentor,
        isEmployee
    }
})
