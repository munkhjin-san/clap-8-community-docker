<template>
    <UsersModal @close="emit('close')">
        <template #title>
            <p class="font-semibold">アクセス権限</p>
        </template>
        <template #content>
            <div class="flex flex-col">
                <div v-if="owner" class="mb-4">
                    <p class="form-title mb-4">所有者</p>
                    <div class="flex items-center p-[10px] hover:bg-[var(--bg3)]">
                        <UserPanel :withName="true" :user="owner" size="30"/>
                    </div>
                </div>
                <div class="mb-4">
                    <p class="form-title mb-4">アクセス可能メンバー</p>
                    <div v-for="user in acsMembers" class="flex items-center p-[10px] hover:bg-[var(--bg3)]">
                        <UserPanel :user="user" size="30" with-name disable-instant/>
                    </div>  
                </div>
                <div class="opacity-80" v-if="auth.id === owner?.id">
                    <p class="form-title mb-4">アクセス不可能メンバー</p>
                    <div v-for="user in unacsMembers" class="flex items-center p-[10px] hover:bg-[var(--bg3)]">
                        <UserPanel :user="user" size="30" with-name disable-instant/>
                    </div>  
                </div>                      
            </div>
        </template>
    </UsersModal>
</template>
<script setup lang="ts">
import UsersModal from '@/components/Global/UsersModal.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import { TaskUser, User } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
const auth = useAuthUserStore()
const emit = defineEmits<{
    (e: 'close'):void
}>()
const props = defineProps<{
    acsMembers: (User | TaskUser)[]
    unacsMembers: (User | TaskUser)[]
    owner?: User | TaskUser | null
}>()
</script>