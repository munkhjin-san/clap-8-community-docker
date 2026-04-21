<template>
    <Teleport to="body">
        <Modal v-if="route.name == 'today-comments'" :loader="commentLoading == 0" size="large" @close="router.push({name: 'members'})" custom-class="!bg-[var(--bg2)]">
            <template #title>
                みんなのひとこと
            </template>
            <template #content>
                <DailyMemberMessages
                    v-if="commentLoading > 0"
                    :members="dailyMessageUserList" 
                    from="today-comments"
                    @refresh="refreshDailyMessageUser"
                    @create="createComment"
                />

                <div v-if="commentLoading > 0 && (!dailyMessageUserList || !dailyMessageUserList.length)">
                    <p>まだ投稿はありません。最初のひとことを追加してください。</p>
                </div>
                
            </template>
        </Modal>
    </Teleport>
</template>
<script setup lang="ts">
import { DailyMessageUser } from '@/interface/globalInterface';
import Modal from '../Global/Modal.vue';
import { useRoute, useRouter } from 'vue-router';
import { onMounted, ref } from 'vue';
import { useApi } from '@/composables/api';
import DailyMemberMessages from '../Global/DailyMemberMessages.vue';

const commentLoading = ref(0)
const dailyMessageUserList = ref<DailyMessageUser[]>([])
const router = useRouter()
const route = useRoute()
const api = useApi()
onMounted(() => {
    getTodayComments()
})
const getTodayComments = async () => {
    const response = await api.get('/get_today_comments')
    if (response) {
        // Sort by custom_field_data_records' updated_at descending (newest first)
        dailyMessageUserList.value = response.sort((a: DailyMessageUser, b: DailyMessageUser) => {
            const aTime = (a.custom_field_data_records?.[0] as any)?.updated_at
            const bTime = (b.custom_field_data_records?.[0] as any)?.updated_at
            if (!aTime || !bTime) return 0
            return new Date(bTime).getTime() - new Date(aTime).getTime()
        })

    }
    commentLoading.value++
}
const refreshDailyMessageUser = (data: DailyMessageUser) => {
    const find = dailyMessageUserList.value.findIndex(user => user.id === data.id)
    if (find !== -1) {
        dailyMessageUserList.value[find] = data
    } else {
        dailyMessageUserList.value.push(data)
    }
}
const createComment = async (comment: string) => {
    if (!comment.trim()) return

    const response = await api.post('/create_comment', { comment })
    if (response) {
        getTodayComments()
    }
}
</script>