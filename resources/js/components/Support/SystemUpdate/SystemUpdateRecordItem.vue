<template>
    <article class="border border-solid rounded border-[var(--calendarBorder)] p-[18px]">
        <div class="flex justify-between gap-5 under960:flex-col">
            <div class="flex flex-col w-full">
                <div class="flex flex-wrap gap-2 text-[12px] text-[gray] items-center">
                    <span class="rounded bg-[var(--bg3)] px-2 py-1 text-[var(--primary-color)]">{{ categoryLabel }}</span>
                    <span v-if="!record.is_published" class="rounded bg-[var(--bg3)] px-2 py-1 text-[var(--primary-color)]">下書き</span>
                    <span v-if="record.created_at" class="text-[12px] text-[gray] ml-auto">{{ DateParser(record.created_at) }}</span>
                </div>
                <div class="text-[16px] my-4"><span class="w-2 h-2 inline-block bg-[tomato] rounded-full mr-2" v-if="!isRead && mustCheck"></span>{{ record.title }}</div>
                <p v-if="record.summary" :class="{'line-clamp-2': !expanded}" class="whitespace-break-spaces leading-[1.7]" v-html="urlCheck(record.summary)"></p>
            </div>
            <div class="flex shrink-0 items-start gap-2">
                <ItemMenu
                    v-if="isAdmin"
                    :items="[
                        { title: '編集する', action: () => emit('edit', record) },
                        { title: '削除する', action: () => emit('delete', record) }
                    ]"
                />
            </div>
        </div>

        <div v-if="expanded && record.details.length" class="mt-[18px] flex flex-col gap-[10px]">
            <SystemUpdateDetailItem
                v-for="detail in record.details"
                :key="detail.id ?? `${record.id}-${detail.sort_order}`"
                :detail="detail"
            />
        </div>

        <button class="jump-link mt-3" @click="emit('toggle', record)">
            {{ expanded ? '閉じる' : '続きを表示する' }}
        </button>
        <div title="既読にする" v-if="mustCheck" @click="markAsRead" class="cursor-pointer mt-5 flex items-center gap-2 bg-[var(--bg3)] px-3 py-2 text-[12px] text-[var(--primary-color)] rounded-full w-fit">
            <svg :class="{'reactOn': reacting}" version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :fill="isRead ? 'var(--primary-color)' : 'var(--check-inactive)'">
                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
            </svg>
            <Transition name="switchState">
                <span v-if="isRead" class="text-[12px] text-[var(--primary-color)]">既読</span>
            </Transition>
        </div>
    </article>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { SystemUpdateRecord } from '@/interface/supportInterface';
import { categoryOptions, labelFromOptions, statusOptions } from './options';
import SystemUpdateDetailItem from './SystemUpdateDetailItem.vue';
import { DateParser, urlCheck } from '@/utils/tools';
import { useAuthUserStore } from '@/store/auth';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
import { useDashboardStore } from '@/store/dashboard';

const props = defineProps<{
    record: SystemUpdateRecord;
    expanded: boolean;
    isAdmin: boolean;
}>();

const emit = defineEmits<{
    toggle: [record: SystemUpdateRecord];
    edit: [record: SystemUpdateRecord];
    delete: [record: SystemUpdateRecord];
}>();

const isRead = ref(props.record.checked_by_user ?? false);
const auth = useAuthUserStore();
const reacting = ref(false);
const { toast, ping } = useDialog();
const api = useApi()
const sending = ref(false);
const { getBatchDashboardData } = useDashboardStore();
const mustCheck = computed(() => {
    const targetPositions = [
        6, //執行役員,
        16, //プロジェクトリーダー,
        11, //正社員,
        12 //契約社員
    ];
    return props.record.must_read && auth.user.position_id && targetPositions.includes(auth.user.position_id);
})

const categoryLabel = computed(() => labelFromOptions(categoryOptions, props.record.category));
const statusLabel = computed(() => labelFromOptions(statusOptions, props.record.status));

const markAsRead = async() => {
    if(sending.value) return;
    if(isRead.value) {
        toast('既に既読になっています。');
        return;
    };
    reacting.value = true;
    sending.value = true;
    isRead.value = true;
    setTimeout(() => {
        reacting.value = false;
    }, 500);
    try {
        await api.post('/system_update_check', { record_id: props.record.id });
        toast('既読にしました。');
        getBatchDashboardData(['systemUpdates']);
    } catch (error) {
        isRead.value = false;
        toast('既読の更新に失敗しました。再度お試しください。');
    } finally {
        sending.value = false;
    }
};
</script>

<style scoped>
.switchState-enter-active,
.switchState-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.switchState-enter-from {
    opacity: 0;
    transform: translateY(-6px);
}
.switchState-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
