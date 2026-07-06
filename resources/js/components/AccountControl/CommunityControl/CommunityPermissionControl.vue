<template>
    <div class="admin-window perm-window">

        <div v-if="!canManageRoles" class="perm-empty">
            <p>権限編集のアクセス権限ありません。</p>
        </div>
        <div v-else-if="rolesLoading" class="perm-empty">
            <p>権限を読み込み中</p>
        </div>

        <div v-else class="perm-layout">
            <!-- left: role list -->
            <aside class="perm-roles">
                <div class="perm-roles-head">
                    <span>ロール</span>
                </div>
                <ul class="perm-role-list">
                    <li
                        v-for="role in roles"
                        :key="role.id"
                        :class="['perm-role-item', { active: role.id === selectedRoleId }]"
                        @click="selectedRoleId = role.id"
                    >
                        <div class="perm-role-main">
                            <span class="perm-role-name">{{ role.name }}</span>
                            <span class="perm-role-meta">
                                <span v-if="isProtectedRole(role)" class="perm-lock">固定</span>
                                <span class="perm-count">{{ role.memberships_count }}人</span>
                            </span>
                        </div>
                        <div class="perm-role-bar">
                            <span class="perm-role-bar-fill" :style="{ width: grantRatio(role) + '%' }"></span>
                        </div>
                    </li>
                </ul>
                <FloatButton title="ロールを追加" @action="openRoleModal(null)">
                    <template #icon><AddIcon size="14"/></template>
                </FloatButton>
            </aside>

            <!-- right: selected role detail -->
            <section v-if="selectedRole" class="perm-detail">
                <header class="perm-detail-head">
                    <div class="perm-detail-title">
                        <span v-if="isProtectedRole(selectedRole)" class="perm-detail-name-fixed">{{ selectedRole.name }}</span>
                        <input
                            v-else
                            v-model="roleDraftNames[selectedRole.id]"
                            class="perm-detail-name"
                            maxlength="255"
                            @blur="saveRoleName(selectedRole)"
                            @keydown.enter.prevent="saveRoleName(selectedRole)"
                        >
                        <button
                            v-if="selectedRole.memberships_count > 0"
                            type="button"
                            class="perm-members"
                            :title="`${selectedRole.memberships_count}人のメンバーを表示`"
                            @click="openMembers(selectedRole)"
                        >
                            <span
                                v-for="member in selectedRole.members.slice(0, 3)"
                                :key="member.id"
                                class="perm-avatar"
                            >
                                <UserPanel :user="member" size="15" imgClass="userNormalIcon" :disable-instant="true"/>
                            </span>
                            <span v-if="selectedRole.memberships_count > 3" class="perm-members-more">
                                …(+{{ selectedRole.memberships_count - 3 }})
                            </span>
                        </button>
                        <span v-else class="perm-detail-sub">メンバーなし</span>
                    </div>
                    <div class="perm-detail-buttons">
                        <button
                            type="button"
                            class="perm-edit"
                            title="ロール名・メンバーを編集"
                            @click="openRoleModal(selectedRole)"
                        >
                            <Edit fill="currentColor" size="16"/>
                        </button>
                        <button
                            v-if="!isProtectedRole(selectedRole)"
                            type="button"
                            class="perm-delete"
                            :disabled="selectedRole.memberships_count > 0 || roleSavingId === selectedRole.id"
                            :title="selectedRole.memberships_count > 0 ? '所属メンバーがいるロールは削除できません' : 'ロール削除'"
                            @click="deleteRole(selectedRole)"
                        >
                            <Trash fill="currentColor" size="16"/>
                        </button>
                    </div>
                </header>

                <div v-if="isProtectedRole(selectedRole)" class="perm-fixed-banner">
                    管理者は全ての権限を持つ固定ロールです。編集はできません。
                </div>

                <div v-for="group in capabilityGroups" :key="group.key" class="perm-section">
                    <h4 class="perm-section-title">{{ group.name }}</h4>

                    <!-- app capabilities: toggle tiles -->
                    <div v-if="group.key === 'apps'" class="perm-tiles">
                        <button
                            v-for="capability in group.capabilities"
                            :key="capability.key"
                            type="button"
                            :class="['perm-tile', { on: isOn(capability.key), locked: isProtectedRole(selectedRole) }]"
                            :disabled="isProtectedRole(selectedRole) || roleSavingId === selectedRole.id"
                            :title="capability.description"
                            @click="toggleCapability(capability.key)"
                        >
                            <span class="perm-tile-check" aria-hidden="true">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32" fill="currentColor">
                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                </svg>
                            </span>
                            <span class="perm-tile-name">{{ capability.name }}</span>
                        </button>
                    </div>

                    <!-- action capabilities: labeled switches -->
                    <div v-else class="perm-actions">
                        <label
                            v-for="capability in group.capabilities"
                            :key="capability.key"
                            :class="['perm-action', { disabled: isProtectedRole(selectedRole) }]"
                        >
                            <div class="perm-action-text">
                                <strong>{{ capability.name }}</strong>
                                <span v-if="capability.description">{{ capability.description }}</span>
                            </div>
                            <input
                                type="checkbox"
                                class="perm-switch"
                                :checked="isOn(capability.key)"
                                :disabled="isProtectedRole(selectedRole) || roleSavingId === selectedRole.id"
                                @change="toggleCapability(capability.key)"
                            >
                        </label>
                    </div>
                </div>

                <!-- shift types: per-role selectable set (catalog CRUD lives in 勤怠管理 › シフト種別) -->
                <div class="perm-section">
                    <h4 class="perm-section-title">シフト種別</h4>
                    <div class="perm-tiles">
                        <button
                            v-for="st in shiftTypes"
                            :key="st.id"
                            type="button"
                            :class="['perm-tile', { on: isShiftOn(st.id), locked: isProtectedRole(selectedRole) }]"
                            :disabled="isProtectedRole(selectedRole) || roleSavingId === selectedRole.id"
                            :title="st.name"
                            @click="toggleShiftType(st.id)"
                        >
                            <span class="perm-tile-check" aria-hidden="true">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32" fill="currentColor"><path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </span>
                            <span class="perm-tile-name">{{ st.abbreviation || st.name }}</span>
                        </button>
                        <p v-if="!shiftTypes.length" class="perm-shift-empty">シフト種別がありません。「勤怠管理 › シフト種別」から追加してください。</p>
                    </div>
                </div>
            </section>
        </div>

        <CommunityRoleEditModal
            v-if="roleModalOpen"
            :role="roleModalTarget"
            @saved="onRoleSaved"
            @close="roleModalOpen = false"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useAuthUserStore } from '@/store/auth';
import { useMessageUsers } from '@/store/messageUsers';
import UserPanel from '@/components/Global/UserPanel.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import CommunityRoleEditModal from './CommunityRoleEditModal.vue';
import type { User } from '@/interface/globalInterface';
import Edit from '@/components/Icons/Edit.vue';
import Trash from '@/components/Icons/Trash.vue';

type CommunityRole = {
    id: number
    key: string
    name: string
    sort_order?: number
    capabilities: string[]
    shift_type_ids: number[]
    is_system?: boolean
    memberships_count: number
    members: User[]
}

type CapabilityItem = { key: string; name: string; description?: string; kind?: string }
type CapabilityGroup = { key: string; name: string; description?: string; capabilities: CapabilityItem[] }
type ShiftType = { id: number; name: string; abbreviation?: string | null; value?: number | null; full_day?: boolean | null }

const auth = useAuthUserStore()
const api = useApi()
const messageUsers = useMessageUsers()
const { ask, ping, toast } = useDialog()

const openMembers = (role: CommunityRole) => {
    if(!role.members?.length) return
    messageUsers.setMessageUsers({ active: true, userList: role.members, title: `${role.name} のメンバー` })
}

const rolesLoading = ref(false)
const roleSavingId = ref<number | null>(null)
const roles = ref<CommunityRole[]>([])
const roleDraftNames = ref<Record<number, string>>({})
const capabilityGroups = ref<CapabilityGroup[]>([])
const selectedRoleId = ref<number | null>(null)
const roleModalOpen = ref(false)
const roleModalTarget = ref<CommunityRole | null>(null)

// Shift types: the community catalog (for per-role assignment tiles). The
// catalog CRUD itself lives in WorkControl › シフト種別 (ShiftTypeManager.vue).
const shiftTypes = ref<ShiftType[]>([])

// null target = create mode; a role = edit mode. Same modal handles both.
const openRoleModal = (role: CommunityRole | null) => {
    roleModalTarget.value = role
    roleModalOpen.value = true
}

const onRoleSaved = async (updated: CommunityRole, created: boolean) => {
    roleModalOpen.value = false
    // Members moved between roles affect every role's count, so reload all.
    await loadPermissionSettings()
    if(created && updated?.id) selectedRoleId.value = updated.id
    await auth.refreshCommunityContext()
}

const canManageRoles = computed(() => auth.isAdmin)
const isProtectedRole = (role: CommunityRole): boolean => role.key === 'admin'

const selectedRole = computed<CommunityRole | null>(
    () => roles.value.find(role => role.id === selectedRoleId.value) ?? null
)
const totalCapabilities = computed(() => capabilityGroups.value.reduce((sum, group) => sum + group.capabilities.length, 0))

const grantRatio = (role: CommunityRole): number => {
    if(isProtectedRole(role)) return 100
    if(!totalCapabilities.value) return 0
    return Math.round((role.capabilities.length / totalCapabilities.value) * 100)
}

const isOn = (capability: string): boolean => {
    const role = selectedRole.value
    if(!role) return false
    return isProtectedRole(role) || role.capabilities.includes(capability)
}

const syncRoleDraftNames = () => {
    roleDraftNames.value = Object.fromEntries(roles.value.map(role => [role.id, role.name]))
}

const loadPermissionSettings = async () => {
    if(!canManageRoles.value || rolesLoading.value) return
    rolesLoading.value = true
    try {
        const [roleResponse, bladeResponse, shiftResponse] = await Promise.all([
            api.get('/community_context/roles'),
            api.get('/community_context/capabilities'),
            api.get('/community_context/shift_types'),
        ])
        roles.value = ((roleResponse ?? []) as CommunityRole[])
            .map(role => ({
                ...role,
                capabilities: role.capabilities ?? [],
                shift_type_ids: role.shift_type_ids ?? [],
                members: role.members ?? [],
                memberships_count: role.memberships_count ?? 0,
            }))
            .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
        syncRoleDraftNames()
        capabilityGroups.value = (bladeResponse?.groups ?? []) as CapabilityGroup[]
        shiftTypes.value = (shiftResponse ?? []) as ShiftType[]
        if(!selectedRole.value){
            // default to the first editable (non-admin) role, else the first role
            selectedRoleId.value = (roles.value.find(role => !isProtectedRole(role)) ?? roles.value[0])?.id ?? null
        }
    } finally {
        rolesLoading.value = false
    }
}

const saveRoleName = async (role: CommunityRole) => {
    if(isProtectedRole(role) || roleSavingId.value === role.id) return
    const nextName = roleDraftNames.value[role.id]?.trim()
    if(!nextName){
        roleDraftNames.value[role.id] = role.name
        ping('ロール名を入力してください。')
        return
    }
    if(nextName === role.name) return

    roleSavingId.value = role.id
    try {
        const updated = await updateRole(role, { name: nextName, capabilities: role.capabilities })
        roles.value = roles.value.map(item => item.id === role.id ? updated : item)
        syncRoleDraftNames()
        toast('ロール名を保存しました。')
    } finally {
        roleSavingId.value = null
    }
}

const deleteRole = async (role: CommunityRole) => {
    if(isProtectedRole(role)) return
    if(role.memberships_count > 0){
        ping('所属メンバーがいるロールは削除できません。')
        return
    }
    const confirmed = await ask(`${role.name} を削除しますか？`)
    if(!confirmed.value) return

    roleSavingId.value = role.id
    try {
        await api.del(`/community_context/roles/${role.id}`)
        roles.value = roles.value.filter(item => item.id !== role.id)
        syncRoleDraftNames()
        if(selectedRoleId.value === role.id){
            selectedRoleId.value = (roles.value.find(item => !isProtectedRole(item)) ?? roles.value[0])?.id ?? null
        }
        toast('ロールを削除しました。')
    } finally {
        roleSavingId.value = null
    }
}

const updateRole = async (
    role: CommunityRole,
    payload: Pick<CommunityRole, 'name'> & Partial<Pick<CommunityRole, 'capabilities'>>
): Promise<CommunityRole> => {
    const updated = await api.patch(`/community_context/roles/${role.id}`, payload) as CommunityRole
    return {
        ...updated,
        capabilities: updated.capabilities ?? [],
        shift_type_ids: updated.shift_type_ids ?? role.shift_type_ids ?? [],
        members: updated.members ?? role.members ?? [],
        memberships_count: updated.memberships_count ?? role.memberships_count ?? 0,
    }
}

const toggleCapability = async (capability: string) => {
    const role = selectedRole.value
    if(!role || isProtectedRole(role) || roleSavingId.value === role.id) return

    const has = role.capabilities.includes(capability)
    const nextCapabilities = has
        ? role.capabilities.filter(item => item !== capability)
        : Array.from(new Set([...role.capabilities, capability]))

    // optimistic update
    const previous = role.capabilities
    roles.value = roles.value.map(item => item.id === role.id ? { ...item, capabilities: nextCapabilities } : item)

    roleSavingId.value = role.id
    try {
        const updated = await updateRole(role, { name: role.name, capabilities: nextCapabilities })
        roles.value = roles.value.map(item => item.id === role.id ? updated : item)
        await auth.refreshCommunityContext()
    } catch (error) {
        roles.value = roles.value.map(item => item.id === role.id ? { ...item, capabilities: previous } : item)
        throw error
    } finally {
        roleSavingId.value = null
    }
}

// ----- Per-role shift-type assignment -----
const isShiftOn = (id: number): boolean => {
    const role = selectedRole.value
    if(!role) return false
    return isProtectedRole(role) || (role.shift_type_ids ?? []).includes(id)
}

const toggleShiftType = async (id: number) => {
    const role = selectedRole.value
    if(!role || isProtectedRole(role) || roleSavingId.value === role.id) return

    const current = role.shift_type_ids ?? []
    const next = current.includes(id)
        ? current.filter(item => item !== id)
        : Array.from(new Set([...current, id]))

    const previous = current
    roles.value = roles.value.map(item => item.id === role.id ? { ...item, shift_type_ids: next } : item)

    roleSavingId.value = role.id
    try {
        const res = await api.patch(`/community_context/roles/${role.id}/shift-types`, { shift_type_ids: next }) as { shift_type_ids: number[] }
        const confirmed = res?.shift_type_ids ?? next
        roles.value = roles.value.map(item => item.id === role.id ? { ...item, shift_type_ids: confirmed } : item)
    } catch (error) {
        roles.value = roles.value.map(item => item.id === role.id ? { ...item, shift_type_ids: previous } : item)
        throw error
    } finally {
        roleSavingId.value = null
    }
}


onMounted(loadPermissionSettings)
watch(canManageRoles, (canManage) => { if(canManage) void loadPermissionSettings() })
</script>

<style scoped>
.perm-window{
    padding: 20px;
    gap: 14px;
    display: flex;
    flex-direction: column;
    min-height: 0;
    width: auto;
    flex: 1;
    position: relative;
}
.perm-head h2{ margin: 0; font-size: 20px; font-weight: 700; }
.perm-head p{ margin: 6px 0 0; color: gray; font-size: 13px; }
.perm-empty{
    min-height: 220px; display: flex; align-items: center; justify-content: center;
    color: gray; font-size: 13px;
}

/* two-pane layout */
.perm-layout{
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 16px;
    min-height: 0;
    flex: 1;
}

/* left rail */
.perm-roles{
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: solid thin var(--formBorder);
    border-radius: 12px;
    background: var(--background-color);
    position: relative;
}
.perm-roles-head{
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; border-bottom: solid thin var(--formBorder);
    font-size: 12px; font-weight: 700; color: gray;
}
.perm-role-list{ list-style: none; margin: 0; padding: 6px; overflow: auto; }
.perm-role-item{
    padding: 9px 10px; border-radius: 9px; cursor: pointer; margin-bottom: 10px;
}
.perm-role-item:hover{ background: var(--bg3); }
.perm-role-item.active{ background: var(--primary-color); }
.perm-role-main{ display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.perm-role-name{ font-size: 14px; font-weight: 600; }
.perm-role-item.active .perm-role-name,
.perm-role-item.active .perm-count{ color: var(--background-color); }
.perm-role-meta{ display: flex; align-items: center; gap: 6px; }
.perm-lock{ font-size: 10px; color: var(--background-color); background: rgba(0,0,0,0.28); padding: 1px 6px; border-radius: 999px; }
.perm-role-item:not(.active) .perm-lock{ color: gray; background: var(--bg3); }
.perm-count{ font-size: 11px; color: gray; }
.perm-role-bar{ height: 3px; border-radius: 999px; background: var(--bg3); margin-top: 7px; overflow: hidden; }
.perm-role-item.active .perm-role-bar{ background: rgba(255,255,255,0.35); }
.perm-role-bar-fill{ display: block; height: 100%; background: var(--primary-color); }
.perm-role-item.active .perm-role-bar-fill{ background: var(--background-color); }

/* right detail */
.perm-detail{
    border: solid thin var(--formBorder);
    border-radius: 12px;
    background: var(--background-color);
    padding: 18px 20px;
    overflow: auto;
    min-height: 0;
}
.perm-detail-head{
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    padding-bottom: 14px; border-bottom: solid thin var(--formBorder); margin-bottom: 16px;
}
.perm-detail-title{ display: flex; flex-direction: column; gap: 4px; }
.perm-detail-name{
    font-size: 18px; font-weight: 700; border: none; border-bottom: solid 2px transparent;
    background: transparent; color: var(--primary-color); padding: 2px 0; max-width: 320px;
}
.perm-detail-name:focus{ outline: none; border-bottom-color: var(--primary-color); }
.perm-detail-name-fixed{ font-size: 18px; font-weight: 700; }
.perm-detail-sub{ font-size: 12px; color: gray; }
.perm-members{
    display: inline-flex; align-items: center; gap: 6px;
    border: none; background: transparent; cursor: pointer; padding: 2px 0;
}
.perm-avatar{
    width: 15px; height: 15px; border-radius: 50%; flex: 0 0 auto;
    margin-left: -7px; box-shadow: 0 0 0 2px var(--background-color);
    border-radius: 50%; overflow: hidden;
}   
.perm-avatar:first-child{ margin-left: 0; }
.perm-members-more{ font-size: 12px; color: gray; margin-left: 2px; }
.perm-members:hover .perm-members-more{ color: var(--primary-color); }
.perm-detail-buttons{ display: flex; gap: 8px; flex: 0 0 auto; }
.perm-edit{
    height: 30px; border: solid thin var(--primary-color); border-radius: 7px;
    background: var(--primary-color); color: var(--background-color); cursor: pointer;
    font-size: 12px; padding: 0 14px; font-weight: 600;
}
.perm-delete{
    height: 30px; border: solid thin var(--formBorder); border-radius: 7px;
    background: var(--background-color); color: var(--primary-color); cursor: pointer;
    font-size: 12px; padding: 0 12px;
}
.perm-delete:disabled{ opacity: 0.4; cursor: default; }
.perm-fixed-banner{
    font-size: 12px; color: gray; background: var(--bg3);
    border-radius: 8px; padding: 10px 12px; margin-bottom: 16px;
}

.perm-section{ margin-bottom: 22px; }
.perm-section-title{
    margin: 0 0 10px; font-size: 12px; font-weight: 700; color: gray; letter-spacing: 0.04em;
}

/* app tiles */
.perm-tiles{
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
}
.perm-tile{
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 50px; cursor: pointer;
    border: solid thin var(--formBorder); background: var(--background-color);
    color: var(--primary-color); font-size: 13px; text-align: left;
    transition: background 0.12s, border-color 0.12s;
}
.perm-tile:hover:not(:disabled){ border-color: var(--primary-color); }
.perm-tile .perm-tile-check{
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px; border-radius: 999px; flex: 0 0 auto;
    border: solid thin var(--formBorder); color: transparent; font-size: 12px;
}
.perm-tile.on{ background: var(--bg3); border-color: var(--primary-color); }
.perm-tile.on .perm-tile-check{ background: var(--primary-color); border-color: var(--primary-color); color: var(--background-color); }
.perm-tile.on .perm-tile-name{ font-weight: 600; }
.perm-tile.locked{ cursor: default; opacity: 0.85; }
.perm-tile:disabled{ cursor: default; }

/* action switches */
.perm-actions{ display: flex; flex-direction: column; gap: 2px; }
.perm-action{
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 11px 4px; border-bottom: solid thin var(--formBorder); cursor: pointer;
}
.perm-action:last-child{ border-bottom: none; }
.perm-action.disabled{ cursor: default; }
.perm-action-text{ display: flex; flex-direction: column; gap: 2px; }
.perm-action-text strong{ font-size: 13px; }
.perm-action-text span{ font-size: 11px; color: gray; line-height: 1.4; }

/* toggle switch */
.perm-switch{
    appearance: none; -webkit-appearance: none;
    width: 38px; height: 22px; border-radius: 999px; background: var(--formBorder);
    position: relative; cursor: pointer; flex: 0 0 auto; transition: background 0.15s;
}
.perm-switch::after{
    content: ''; position: absolute; top: 2px; left: 2px;
    width: 18px; height: 18px; border-radius: 50%; background: var(--background-color);
    transition: transform 0.15s;
}
.perm-switch:checked{ background: var(--primary-color); }
.perm-switch:checked::after{ transform: translateX(16px); }
.perm-switch:disabled{ opacity: 0.5; cursor: default; }

@media screen and (max-width: 900px){
    .perm-layout{ grid-template-columns: 1fr; }
    .perm-window{ padding: 12px; }
}

/* ----- shift types (per-role assignment tiles reuse .perm-tile) ----- */
.perm-shift-empty {
    font-size: 12px;
    color: var(--text-secondary, #888);
    margin: 4px 0;
}
</style>
