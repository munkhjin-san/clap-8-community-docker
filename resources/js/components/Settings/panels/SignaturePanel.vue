<template>
    <div class="settings-panel settings-panel--full">
        <UserSignature v-if="user" :user="user" @reload="updateSignature"/>
    </div>
</template>

<script setup>
import UserSignature from '../../Profile/UserEditComps/UserSignature.vue'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useDialog } from '@/composables/dialog'
import { useSettingsUser } from '../useSettings'

    const router = useRouter()
    const auth = useAuthUserStore()
    const { toast } = useDialog()
    const { updateUser } = useSettingsUser()

    const user = computed(() => auth.user)

    const updateSignature = () => {
        toast('保存しました。')
        updateUser()
        router.push({ name: 'settings' })
    }
</script>
