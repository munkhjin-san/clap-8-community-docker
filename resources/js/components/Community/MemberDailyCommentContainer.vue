<template>
    <Teleport to="body">
        <Modal v-if="route.name == 'today-comments'" :loader="commentLoading == 0" size="large" @close="router.push({name: 'members'})" custom-class="!bg-[var(--bg2)]">
            <template #title>
                みんなのひとこと
            </template>
            <template #content>
                <DailyMemberMessages
                    v-if="dailyMessageUserList && dailyMessageUserList.length"
                    :members="dailyMessageUserList" 
                    @refresh="refreshDailyMessageUser"
                />

                <div v-if="commentLoading > 0 && (!dailyMessageUserList || !dailyMessageUserList.length)">
                    <p>現在データはありません</p>
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
        dailyMessageUserList.value = response

    }
    commentLoading.value++
}
const refreshDailyMessageUser = (data: DailyMessageUser) => {
    const find = dailyMessageUserList.value.findIndex(user => user.id === data.id)
    console.log('find', find)
    console.log('data', data)
    if (find !== -1) {
        dailyMessageUserList.value[find] = data
    } else {
        dailyMessageUserList.value.push(data)
    }
}
</script>