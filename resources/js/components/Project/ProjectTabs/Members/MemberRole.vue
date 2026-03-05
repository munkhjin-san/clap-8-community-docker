<template>
    <div class="h-full bg-[var(--background-color)] px-[20px] pb-[20px]">
        <div v-if="roles.length">
            <div class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-[12px] pt-[10px]">
                <RoleItem
                    v-for="role in roles"
                    :key="role.id"
                    :role="role"
                    @edit="editRole"
                    @delete="deleteRole"
                />
            </div>
        </div>
        <div v-else>役割が設定されていません。</div>
        <FloatButton v-if="auth.isBoss || auth.isAdmin || isManager" @action="() => { editData = null; createWindow = true }">
            <template #icon>
                <AddIcon />
            </template>
        </FloatButton>
        <Teleport to="body">
            <RoleCreate 
                :editData="editData"
                v-if="createWindow"
                @saved="onSaved"
                @close="() => { createWindow = false; editData = null }"
            />
        </Teleport>
    </div>
</template>
<script setup lang="ts">
import AddIcon from '@/components/Form/AddIcon.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useProject } from '@/composables/project';
import { MemberRole } from '@/interface/projectInterface';
import { computed, ref } from 'vue';
import RoleCreate from './Role/RoleCreate.vue';
import RoleItem from './Role/RoleItem.vue';
import { useAuthUserStore } from '@/store/auth';

const { memberData, selectedProject, isManager } = useProject()

const api = useApi()
const { ask } = useDialog()

const editData = ref<MemberRole | null>(null)
const createWindow = ref(false)
const auth = useAuthUserStore()

const roles = computed(() => {
    return selectedProject.value?.member_roles ?? []
})

const onSaved = (role: MemberRole) => {
    const project = selectedProject.value
    if (!project) return

    if (!Array.isArray(project.member_roles)) {
        project.member_roles = []
    }
    const index = project.member_roles.findIndex(r => r.id === role.id)
    if (index >= 0) {
        project.member_roles[index] = role
    } else {
        project.member_roles.unshift(role)
    }
}

const editRole = (role: MemberRole) => {
    editData.value = role
    createWindow.value = true
}

const deleteRole = async (role: MemberRole) => {
    const confirmed = await ask('この役割を削除しますか？')
    if (!confirmed.value) return

    await api.del('/project_delete_member_role', { id: role.id }, {
        toast: '役割を削除しました。'
    })

    const project = selectedProject.value
    if (!project?.member_roles) return
    project.member_roles = project.member_roles.filter(r => r.id !== role.id)
}
</script>