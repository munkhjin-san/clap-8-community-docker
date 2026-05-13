<template>
    <div class="support-content text-[14px] px-4 under960:px-1 relative min-h-full">
        <div class="support-content-inner">
            <div class="flex items-start justify-between gap-4 mb-5 under960:flex-col">
                <div class="mb-5 w-[240px] under960:w-full">
                    <PostSearchBar
                        @searchStart="searchRecords"
                        :searching="searching"
                        className="newChatMemberSearch"
                        customPlaceHolder="更新情報を検索"
                    />
                </div>
                <div class="flex flex-wrap gap-2 justify-end under960:justify-start">
                    <button
                        v-for="option in visibleCategoryOptions"
                        :key="option.value"
                        :class="[
                            'px-3 py-1 text-[12px]',
                            selectedCategory === option.value
                                ? 'bg-[var(--primary-color)] text-[var(--background-color)]'
                                : 'bg-[var(--bg3)] text-[var(--primary-color)]'
                        ]"
                        @click="selectedCategory = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>

           
            <div v-if="loading" class="absolute w-full h-full flex items-center justify-center inset-0">
                <div class="spinner-mini mx-auto my-5"></div>
            </div>
            

            <div class="flex flex-col gap-6">
                <SystemUpdateRecordItem
                    v-for="record in records"
                    :key="record.id"
                    :record="record"
                    :expanded="isExpanded(record)"
                    :isAdmin="auth.isAdmin"
                    @toggle="toggleRecord"
                    @edit="openEdit"
                    @delete="deleteRecord"
                />

                <div v-if="!records.length" class="text-[gray] leading-[1.8] py-6">
                    <p>{{ keyword ? '検索結果はありません。' : '現在表示できる情報はありません。' }}</p>
                </div>
            </div>
            <div v-if="pagination.last_page > 1" class="mt-5">
                <PostSearchPager         
                    :possiblePage="pagination.last_page"
                    :activePath="pagination.current_page"
                    @setNavi="(index) => loadRecords(pagination.current_page + index)"
                    @setActivePage="loadRecords"
                />
            </div>
            <SystemUpdateCreate
                v-if="showCreate"
                :editTarget="editTarget"
                @close="handleCreateClose"
            />

            <FloatButton
                v-if="auth.isAdmin"
                @action="openCreate"
                title="更新情報を作成する"
                class="fixed"
            >
                <template #icon>
                    <AddIcon size="15" />
                </template>
            </FloatButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import PostSearchPager from '@/components/Post/PostSearchPager.vue';
import {
    SystemUpdateCategory,
    SystemUpdateRecord,
} from '@/interface/supportInterface';
import SystemUpdateCreate from './SystemUpdate/SystemUpdateCreate.vue';
import SystemUpdateRecordItem from './SystemUpdate/SystemUpdateRecordItem.vue';
import { categoryOptions } from './SystemUpdate/options';

const api = useApi();
const auth = useAuthUserStore();
const loading = ref(false);
const records = ref<SystemUpdateRecord[]>([]);
const showCreate = ref(false);
const editTarget = ref<SystemUpdateRecord | null>(null);
const selectedCategory = ref<SystemUpdateCategory | 'all'>('all');
const expandedRecordIds = ref<number[]>([]);
const keyword = ref('');
const searching = ref(0);
const pagination = ref({
    current_page: 1,
    last_page: 0,
    total: 0,
});
let latestRequestId = 0;

const visibleCategoryOptions = [
    { value: 'all' as const, label: 'すべて' },
    ...categoryOptions,
];

const isExpanded = (record: SystemUpdateRecord) => {
    return !!record.id && expandedRecordIds.value.includes(record.id);
};

const toggleRecord = (record: SystemUpdateRecord) => {
    if (!record.id) return;
    if (expandedRecordIds.value.includes(record.id)) {
        expandedRecordIds.value = expandedRecordIds.value.filter((id) => id !== record.id);
    } else {
        expandedRecordIds.value = [...expandedRecordIds.value, record.id];
    }
};

const loadRecords = async (page = 1) => {
    const requestId = ++latestRequestId;
    loading.value = true;
    if (keyword.value) {
        searching.value = 1;
    }
    try {
        const response = await api.get('/system_updates', {
            page,
            per_page: 10,
            category: selectedCategory.value,
            keyword: keyword.value,
        }, {
            cancel: true,
        });

        if (requestId === latestRequestId && response) {
            records.value = response.data ?? [];
            pagination.value = {
                current_page: response.current_page ?? 1,
                last_page: response.last_page ?? 0,
                total: response.total ?? 0,
            };
            expandedRecordIds.value = [];
        }
    } finally {
        if (requestId === latestRequestId) {
            loading.value = false;
            searching.value = 0;
        }
    }
};

const searchRecords = (word: string) => {
    keyword.value = word;
    loadRecords(1);
};

const openCreate = () => {
    editTarget.value = null;
    showCreate.value = true;
};

const openEdit = (record: SystemUpdateRecord) => {
    editTarget.value = record;
    showCreate.value = true;
};

const handleCreateClose = async (refreshNeeded: boolean) => {
    const refreshPage = editTarget.value ? pagination.value.current_page : 1;
    showCreate.value = false;
    editTarget.value = null;
    if (refreshNeeded) {
        await loadRecords(refreshPage);
    }
};

const deleteRecord = async (record: SystemUpdateRecord) => {
    await api.post('/system_update_delete', { id: record.id }, {
        ask: '本当に削除しますか？',
        toast: '削除しました。',
    });
    const targetPage = records.value.length === 1
        ? Math.max(pagination.value.current_page - 1, 1)
        : pagination.value.current_page;
    await loadRecords(targetPage);
};

watch(selectedCategory, () => {
    loadRecords(1);
});

onMounted(loadRecords);
</script>
