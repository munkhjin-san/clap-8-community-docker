<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>グラリンピックランクイング</p>
        </template>
        <template #content>
            <div>
                <!-- <div class="px-[10px] py-[8px] flex items-center gap-[10px]" v-for="(record, index) in ranking" :key="record.user.id">
                    <div class="mr-[20px]">{{ index + 1 }}.</div>
                    <div class="flex items-center gap-[10px] flex-wrap">
                        <UserPanel :user="record.user" with-name disable-instant/>
                        <div class="text-[14px]">（{{ `🔥 ${amountOfMoneyParser(record.sum_calories)} kcal、 ${record.post_count}件` }}）</div>
                    </div>                        
                </div> -->
                <table>
                    <thead>
                        <tr>
                            <th>順位</th>
                            <th>メンバー</th>
                            <th>合計カロリー</th>
                            <th>投稿数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(record, index) in ranking" :key="record.user.id">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <UserPanel :user="record.user" with-name disable-instant/>
                            </td>
                            <td>{{ `🔥 ${amountOfMoneyParser(record.sum_calories)} kcal` }}</td>
                            <td>{{ record.post_count }}件</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { TopEntryUser } from '@/interface/postInterface';
import Modal from '../Global/Modal.vue';
import { amountOfMoneyParser } from '@/utils/tools';
import UserPanel from '../Global/UserPanel.vue';


const props = defineProps<{
    ranking: TopEntryUser[]
}>()

const emit = defineEmits<{
    close: []
}>()
</script>
<style scoped>
table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px 15px;
    border-bottom: 1px solid var(--calendarBorder);
    text-align: left;
}

</style>