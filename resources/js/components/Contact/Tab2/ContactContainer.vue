<template>
    <div class="text-[var(--primary-color)]">
        <Transition name="modalFade">
            <div class="member-loader" v-if="initialLoader == 0">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>

        <div class="no-comment-text" v-if="initialLoader > 0 && !contacts.length">現在データはありません。</div>

        <FloatButton :style="{position: 'fixed', bottom: auth.user?.footer_view && responsive.mobile ? '65px' : '20px'}" @action="openBatchModal" title="名刺を一括取り込み">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>

        <!-- Batch notifications -->
        <div v-if="batchNotifications.length" class="mb-[20px] mx-[20px] max-w-3xl space-y-[12px]">
            <div v-for="notification in batchNotifications" :key="notification.id" class="bg-[var(--message-background)] border border-solid border-[var(--normalBorder)] p-[14px]">
                <div class="flex items-start justify-between gap-[14px]">
                    <div class="flex items-start gap-[10px] min-w-0">
                        <span class="mt-[5px] flex-none w-[8px] h-[8px] rounded-full bg-green-500"></span>
                        <div class="min-w-0">
                            <p class="text-[13px] font-bold text-[var(--primary-color)]">{{ notification.title }}</p>
                            <p class="text-[12.5px] text-[gray] mt-[2px] leading-[1.6]">{{ notification.message }}</p>
                        </div>
                    </div>
                    <button @click="markBatchNotificationRead(notification.id)" class="flex-none h-[30px] px-[12px] text-[12px] bg-[var(--kebab-bg1)] text-[var(--primary-color)] border border-solid border-[var(--normalBorder)] cursor-pointer hover:border-[var(--formBorder)] whitespace-nowrap">
                        確認しました
                    </button>
                </div>
            </div>
        </div>

        <!-- Batch import tracking (compact one-liner) -->
        <div v-if="trackedBatches.length" class="mb-[16px] mx-[20px] max-w-3xl space-y-[8px]">
            <div v-for="batch in trackedBatches" :key="batch.id" class="flex items-center gap-[10px] bg-[var(--message-background)] border border-solid border-[var(--normalBorder)] py-[9px] px-[12px]">
                <span class="flex-none flex items-center justify-center w-[14px] h-[14px]">
                    <span v-if="isTerminalBatchStatus(batch.status)" class="inline-block w-[9px] h-[9px] rounded-full" :style="{ background: batchDotColor(batch.status) }"></span>
                    <span v-else class="inline-block w-[13px] h-[13px] rounded-full border-2 border-solid animate-spin" :style="{ borderColor: batchDotColor(batch.status), borderTopColor: 'transparent' }"></span>
                </span>
                <span class="flex-1 min-w-0 text-[13px] text-[var(--primary-color)] truncate">{{ batchLineLabel(batch) }}</span>
                <button @click="clearBatchTracking(batch.id)" title="閉じる" class="flex-none w-[24px] h-[24px] flex items-center justify-center text-[gray] hover:bg-[var(--soft-bg)] hover:text-[var(--primary-color)]">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
        </div>

        <!-- Toolbar: scope + export -->
        <div class="mx-[20px] flex items-center gap-[14px] flex-wrap mb-[16px]">
            <div class="fc-scope">
                <button
                    v-for="s in scopeOptions"
                    :key="s.key"
                    @click="scope = s.key"
                    :class="['fc-scope-btn', scope === s.key ? 'on' : '']"
                >{{ s.label }}</button>
            </div>
            <div class="flex-1"></div>
            <button
                @click="exportCsv"
                title="公開情報をCSV出力"
                class="h-[40px] px-[15px] flex items-center gap-[8px] bg-[var(--message-background)] text-[gray] border border-[var(--normalBorder)] text-[13.5px] cursor-pointer transition-colors hover:border-[var(--formBorder)] hover:text-[var(--primary-color)]"
            >
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                CSV出力
            </button>
        </div>

        <!-- Filter row: type + tag dropdowns + quick chips -->
        <div class="mx-[20px] flex items-center gap-[8px] flex-wrap mb-[12px]">
            <span class="text-[12px] text-[gray] mr-[2px]">絞り込み</span>
            <MultiSelectDropdown
                v-model="selectedTypes"
                :options="typeOptions"
                label="種類"
                search-placeholder="種類を検索"
                total-label="種類"
            />
            <div class="w-px h-[22px] bg-[var(--normalBorder)] mx-[2px]"></div>
            <span class="text-[12px] text-[gray]">よく使う種類</span>
            <button
                v-for="chip in quickChips"
                :key="chip.id"
                @click="toggleType(chip.id)"
                :class="[
                    'inline-flex items-center gap-[7px] px-[12px] py-[7px] text-[13px] font-medium cursor-pointer whitespace-nowrap transition-colors border',
                    selectedTypes.includes(chip.id)
                        ? 'border-[var(--formBorder)] bg-[var(--kebab-bg1)] text-[var(--primary-color)]'
                        : 'border-[var(--normalBorder)] bg-[var(--message-background)] text-[gray]'
                ]"
            >
                {{ chip.title }}
                <span class="inline-flex items-center justify-center min-w-[18px] h-[17px] px-[5px] text-[11px] font-bold bg-[var(--secondary-background)] text-[var(--primary-color)]">{{ chip.count }}</span>
            </button>
        </div>

        <!-- Active filter pills -->
        <div v-if="hasAnyFilter" class="mx-[20px] flex items-center gap-[8px] flex-wrap mb-[12px]">
            <span class="text-[12px] text-[gray]">適用中</span>
            <button
                v-for="pill in activePills"
                :key="pill.key"
                @click="pill.onRemove"
                class="h-[30px] pl-[11px] pr-[7px] inline-flex items-center gap-[7px] bg-[var(--kebab-bg1)] text-[var(--primary-color)] border border-[var(--formBorder)] text-[12.5px] cursor-pointer"
            >
                {{ pill.label }}
                <span class="inline-flex w-[17px] h-[17px] items-center justify-center bg-[var(--secondary-background)]">
                    <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </span>
            </button>
            <button @click="clearAllFilters" class="h-[30px] px-[11px] bg-transparent border-none text-[gray] text-[12.5px] cursor-pointer hover:text-[var(--primary-color)]">すべてクリア</button>
        </div>

        <!-- Sub-toolbar: refresh + duplicate + sort + count + view toggle -->
        <div class="mx-[20px] flex items-center gap-[10px] mb-[16px] pb-[16px] border-b border-[var(--panel-separate)] flex-wrap">
            <button
                @click="refreshBatchArea"
                class="h-[36px] px-[14px] flex items-center gap-[7px] bg-[var(--message-background)] text-[gray] border border-[var(--normalBorder)] text-[13px] cursor-pointer transition-colors hover:border-[var(--formBorder)] hover:text-[var(--primary-color)]"
            >
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7M21 4v4h-4"/></svg>
                取り込み状況を更新
            </button>
            <button
                @click="openDuplicateModal"
                class="h-[36px] px-[14px] flex items-center gap-[8px] bg-[var(--message-background)] text-[gray] border border-[var(--normalBorder)] text-[13px] cursor-pointer transition-colors hover:border-[var(--formBorder)] hover:text-[var(--primary-color)]"
            >
                重複レビュー
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-[5px] bg-[var(--kebab-bg1)] text-[gray] text-[11px] font-bold">{{ duplicateLoading ? '…' : duplicates.length }}</span>
            </button>
            <div class="flex-1"></div>
            <div class="relative flex items-center">
                <select v-model="sort" class="h-[36px] pl-[15px] pr-[30px] border border-[var(--normalBorder)] text-[13px] cursor-pointer outline-none appearance-none">
                    <option value="name">氏名順</option>
                    <option value="dept">部署順</option>
                    <option value="date">登録日時（新しい順）</option>
                </select>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="gray" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute right-[10px] pointer-events-none"><path d="M6 9l6 6 6-6"/></svg>
            </div>
            <span class="text-[13px] text-[gray]"><span class="text-[var(--primary-color)] font-bold">{{ totalCount }}</span> 件</span>
            <div v-if="!responsive.mobile" class="fc-viewtoggle">
                <button @click="selectView('grid')" :class="viewToggleClass('grid')" title="グリッド表示">
                    <Grid size="13"/>
                </button>
                <button @click="selectView('table')" :class="viewToggleClass('table')" title="テーブル表示">
                    <List size="13"/>
                </button>
            </div>
        </div>

        <!-- List -->
        <div>
            <GridLayout
                v-if="viewType == 'grid'"
                :contacts="pagedList"
                :viewer-id="viewerId"
                @reload="getContacts"
                @open-memo="openPrivateMemo"
            />
            <TableLayout
                v-if="viewType == 'table'"
                :contacts="pagedList"
                @open-memo="openPrivateMemo"
            />
            <div v-if="initialLoader > 0 && contacts.length > 0 && totalCount === 0" class="flex flex-col items-center justify-center py-[90px] text-[gray] gap-[14px]">
                <div class="text-[15px]">該当するコンタクトが見つかりません</div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalCount > 0" class="mx-[20px] mt-[20px] pb-5 flex items-center justify-center gap-[14px]">
            <div class="flex items-center gap-[4px]">
                <button @click="prevPage" :disabled="currentPage <= 1" :class="pagerArrowClass(currentPage > 1)" title="前へ">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </button>
                <template v-for="(item, i) in pageItems" :key="i">
                    <span v-if="item === 'gap'" class="w-[32px] h-[32px] flex items-center justify-center text-[gray] text-[13px] select-none">…</span>
                    <button v-else @click="goToPage(item)" :class="pageNumClass(item === currentPage)">{{ item }}</button>
                </template>
                <button @click="nextPage" :disabled="currentPage >= totalPages" :class="pagerArrowClass(currentPage < totalPages)" title="次へ">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </button>
            </div>
        </div>

        <Transition name="modalFade">
            <BatchCreation
                v-if="batchWindow"
                @files-selected="fileupload"
                @done="onImmediateScanDone"
                @close="batchWindow = false"
                :isProcessing="uploadingBatch"
                :contact-types="contactTypes"
            />
        </Transition>
        <Transition name="modalFade">
            <ContactCreate
                v-if="createWindow"
                :edit-data="editData"
                :contact-types="contactTypes"
                @close="(flag) => closeCreate(flag)"
            />
        </Transition>
        <router-view v-slot="{ Component }">
            <transition name="modalFade">
                <component
                    v-if="activeContact && !editData"
                    :is="Component"
                    :contact="activeContact"
                    :related="contacts"
                    @closeCreate="(flag: boolean) => closeCreate(flag)"
                    @edit="(contact:ContactRecord) => { editData = contact; createWindow = true}"
                    @delete="(id:number) => {deleteContact(id)}"
                ></component>
            </transition>
        </router-view>
        <DuplicateReviewModal
            :open="duplicateModalOpen"
            :duplicates="duplicates"
            :loading="duplicateLoading"
            :resolving-id="duplicateResolving"
            @close="closeDuplicateModal"
            @resolve="resolveDuplicate"
            @open-candidate="openCandidateDetails"
        />
        <PrivateMemoModal
            :open="memoModalOpen"
            :contact="memoTarget"
            @close="closeMemoModal"
        />
    </div>
</template>
<script setup lang="ts">
import { useResponsive } from '@/store/responsive';
import FloatButton from '@/components/Global/FloatButton.vue';
import ContactCreate from './ContactCreate.vue';
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { BatchPayload, ContactBatchNotificationSummary, ContactBatchSummary, ContactRecord, ContactType, DuplicateSummary } from '@/interface/contactInterface';
import GridLayout from './Grid/GridLayout.vue';
import TableLayout from './Table/TableLayout.vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import BatchCreation from './BatchCreation.vue';
import { useDialog } from '@/composables/dialog';
import DuplicateReviewModal from './DuplicateReviewModal.vue';
import PrivateMemoModal from './PrivateMemoModal.vue';
import MultiSelectDropdown from './Filters/MultiSelectDropdown.vue';
import { mkConfig, generateCsv, download } from 'export-to-csv';
import Grid from '@/components/Icons/Grid.vue';
import List from '@/components/Icons/List.vue';

const props = defineProps<{
    keyword: string
    container: HTMLElement | null
}>();

const router = useRouter();
const responsive = useResponsive();
const auth = useAuthUserStore();
const badge = useBadgeStore();
const dialog = useDialog();
const api = useApi();

const editData = ref<ContactRecord | null>(null);
const createWindow = ref(false);
const contacts = ref<ContactRecord[]>([]);
const initialLoader = ref(0);
const viewType = ref('grid');
const contactTypes = ref<ContactType[]>([]);
const selectedTypes = ref<number[]>([]);
const scope = ref<'all' | 'personal'>('personal');
const sort = ref('name');
const page = ref(1);
const pageSize = ref(50);
const route = useRoute();

const batchWindow = ref(false);
const uploadingBatch = ref(false);
const trackedBatchData = ref<ContactBatchSummary[]>([]);
const batchNotifications = ref<ContactBatchNotificationSummary[]>([]);
const duplicates = ref<DuplicateSummary[]>([]);
const duplicateModalOpen = ref(false);
const duplicateLoading = ref(false);
const duplicateResolving = ref<number | null>(null);
const memoModalOpen = ref(false);
const memoTarget = ref<ContactRecord | null>(null);
const viewerId = computed(() => auth.user?.id ?? null);
const meId = computed<number | null>(() => auth.activeUser?.id ?? auth.user?.id ?? null);

const scopeOptions: { key: 'all' | 'personal'; label: string }[] = [
    { key: 'all', label: 'すべて' },
    { key: 'personal', label: '個人' },
];

const isTerminalBatchStatus = (status: string) => ['completed', 'failed'].includes(status);

const trackedBatches = computed(() => {
    return [...trackedBatchData.value].sort((left, right) => {
        const leftRank = isTerminalBatchStatus(left.status) ? 1 : 0;
        const rightRank = isTerminalBatchStatus(right.status) ? 1 : 0;
        if (leftRank !== rightRank) {
            return leftRank - rightRank;
        }
        return right.id - left.id;
    });
});

const openCandidateDetails = (id: number) => {
    if (!id) return;
    duplicateModalOpen.value = false;
    router.push({ name: 'contactDetail', params: { contactId: id } });
};

const openPrivateMemo = (contact: ContactRecord) => {
    if (!viewerId.value) {
        dialog.ping('メモを利用するにはログインしてください。');
        return;
    }
    if (!contact.id) {
        dialog.ping('メモを利用するにはコンタクトを登録してください。');
        return;
    }
    // The modal fetches/adds/deletes the per-user memo log itself.
    memoTarget.value = contacts.value.find(existing => existing.id === contact.id) ?? contact;
    memoModalOpen.value = true;
};

const closeMemoModal = () => {
    memoModalOpen.value = false;
    memoTarget.value = null;
};

const batchStatusMessages: Record<string, string> = {
    queued: '受付が完了しました。',
    scanning: '名刺を読み取り中です。',
    scanned: '読み取り結果を確認しています。',
    enriching: '登録内容を仕上げています。',
    completed: '取り込みが完了しました。',
    failed: '取り込みを完了できませんでした。',
};

const batchStatusGuides: Record<string, string> = {
    queued: '順番に処理します。このまま別の作業をして大丈夫です。',
    scanning: '完了通知は最大15分ほど遅れる場合があります。この画面を閉じても大丈夫です。',
    scanned: '登録前の最終確認中です。完了通知は最大15分ほど遅れる場合があります。',
    enriching: '会社情報を補足しています。このまま待たなくても大丈夫です。',
    completed: '下のコンタクト一覧に反映されています。内容をご確認ください。',
    failed: '画像が読みにくい場合があります。画像を確認して、もう一度お試しください。',
};

const batchItemStatusMessages: Record<string, string> = {
    queued: '待機中',
    scanning: '解析中',
    scanned: '解析完了',
    enriching: '情報収集中',
    completed: '登録済み',
    failed: '失敗',
};

const batchStatusLabel = (batch: ContactBatchSummary) => batchStatusMessages[batch.status] ?? '処理状況を確認しています。';
const batchStatusGuide = (batch: ContactBatchSummary) => batchStatusGuides[batch.status] ?? 'このまま別の作業をしても大丈夫です。';
const batchItemStatusLabel = (status: string) => batchItemStatusMessages[status] ?? status;

const batchLineLabel = (batch: ContactBatchSummary) => {
    const total = batch.counts?.total ?? 0;
    const done = batch.counts?.completed ?? 0;
    switch (batch.status) {
        case 'completed': return `${total}件の取り込みが完了しました。`;
        case 'failed': return batch.error || `取り込みに失敗しました。（${total}件）`;
        default: return `${total}件を処理中です。完了したら通知します。（${done}/${total}）`;
    }
};
const batchDotColor = (status: string) => {
    switch (status) {
        case 'completed': return '#22c55e';
        case 'failed': return '#ef4444';
        case 'enriching': return '#a855f7';
        case 'scanning':
        case 'scanned': return '#3b82f6';
        default: return '#9ca3af';
    }
};
const batchProgress = (batch: ContactBatchSummary) => {
    const total = batch.counts?.total ?? 0;
    const done = batch.counts?.completed ?? 0;
    if (!total) return isTerminalBatchStatus(batch.status) ? 100 : 8;
    return Math.min(100, Math.round((done / total) * 100));
};
const batchItemBadgeClass = (status: string) => {
    const base = 'inline-flex items-center px-[7px] py-[1px] text-[11px] whitespace-nowrap ';
    switch (status) {
        case 'completed': return base + 'bg-green-500/15 text-green-600';
        case 'failed': return base + 'bg-red-500/15 text-red-500';
        case 'enriching': return base + 'bg-purple-500/15 text-purple-600';
        case 'scanning':
        case 'scanned': return base + 'bg-blue-500/15 text-blue-600';
        default: return base + 'bg-[var(--inactive-background)] text-[gray]';
    }
};

const selectView = (v: 'grid' | 'table') => {
    viewType.value = v;
    localStorage.setItem('contactViewType', v);
};
const viewToggleClass = (v: 'grid' | 'table') => ['fc-vt-btn', viewType.value === v ? 'on' : ''];

const formatDate = (iso: string) => {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return iso;
    return `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
};

const formatDuration = (durationSeconds: number | null | undefined) => {
    if (durationSeconds == null || Number.isNaN(durationSeconds)) return '-';
    if (durationSeconds < 60) return `${durationSeconds}秒`;
    const hours = Math.floor(durationSeconds / 3600);
    const minutes = Math.floor((durationSeconds % 3600) / 60);
    const seconds = durationSeconds % 60;
    if (hours > 0) return `${hours}時間${minutes}分${seconds}秒`;
    return `${minutes}分${seconds}秒`;
};

const openBatchModal = () => { batchWindow.value = true; };
const openCreate = () => { editData.value = null; createWindow.value = true; };

const upsertBatchData = (batch: ContactBatchSummary) => {
    const index = trackedBatchData.value.findIndex(entry => entry.id === batch.id);
    if (index === -1) { trackedBatchData.value.push(batch); return; }
    trackedBatchData.value[index] = batch;
};

const clearBatchTracking = async (batchId: number) => {
    await api.post(`/contact_batches/${batchId}/dismiss`, {}, { silent: true });
    trackedBatchData.value = trackedBatchData.value.filter(batch => batch.id !== batchId);
    batchNotifications.value = batchNotifications.value.filter(notification => notification.batch?.id !== batchId);
    await badge.getbadgeSummary();
};

const loadRecentBatches = async () => {
    const data = await api.get('/contact_batches', null, { silent: true });
    trackedBatchData.value = Array.isArray(data) ? data : [];
};

const loadBatchNotifications = async () => {
    const data = await api.get('/contact_batch_notifications', null, { silent: true });
    batchNotifications.value = Array.isArray(data) ? data : [];
};

const markBatchNotificationRead = async (notificationId: number) => {
    await api.post(`/contact_batch_notifications/${notificationId}/read`, {}, { silent: true });
    batchNotifications.value = batchNotifications.value.filter(notification => notification.id !== notificationId);
    await badge.getbadgeSummary();
};

const refreshBatchArea = async () => {
    await Promise.all([loadRecentBatches(), loadBatchNotifications(), badge.getbadgeSummary(), getContacts(), loadDuplicates()]);
};

const getTypes = async () => { contactTypes.value = await api.get('/get_contact_types'); };

const getContacts = async () => {
    contacts.value = await api.get('/contact_list');
    initialLoader.value++;
};

// Company enrichment runs in the background (dispatchAfterResponse) after a
// contact is created, so a freshly loaded list can still show enrichment_status
// === 'pending'. Poll a few times until the job flips it to completed/failed so
// the "取得中" spinner clears on its own without a manual refresh.
const ENRICH_POLL_MAX = 10;
const ENRICH_POLL_INTERVAL = 2500;
let enrichPollTimer: ReturnType<typeof setTimeout> | null = null;

const hasPendingEnrichment = () =>
    contacts.value.some(c => c.enrichment_status === 'pending' && !c.data);

const stopEnrichmentPolling = () => {
    if (enrichPollTimer) { clearTimeout(enrichPollTimer); enrichPollTimer = null; }
};

const startEnrichmentPolling = () => {
    stopEnrichmentPolling();
    if (!hasPendingEnrichment()) return;
    let attempts = 0;
    const tick = async () => {
        attempts++;
        await getContacts();
        if (hasPendingEnrichment() && attempts < ENRICH_POLL_MAX) {
            enrichPollTimer = setTimeout(tick, ENRICH_POLL_INTERVAL);
        } else {
            stopEnrichmentPolling();
        }
    };
    enrichPollTimer = setTimeout(tick, ENRICH_POLL_INTERVAL);
};

onUnmounted(stopEnrichmentPolling);

const loadDuplicates = async () => {
    duplicateLoading.value = true;
    try {
        const data = await api.get('/contact_duplicates', null, { silent: true });
        const items: DuplicateSummary[] = data?.duplicates ?? [];
        duplicates.value = items;
    } finally {
        duplicateLoading.value = false;
    }
};

const fileupload = async (payload: BatchPayload) => {
    if (!payload.files.length) return;
    if (payload.files.length > 30) {
        dialog.ping('一度にアップロードできるのは最大30件までです。');
        return;
    }

    const formData = new FormData();
    if (!payload.types.length) {
        dialog.ping('コンタクト種類を選択してください。');
        return;
    }
    payload.files.forEach(file => formData.append('images[]', file));
    payload.types.forEach(t => formData.append('types[]', t));

    const data = await api.post('/scan_batch_cards', formData, {
        toast: '名刺の取り込みを受け付けました。完了通知は最大15分ほど遅れる場合があります。',
        loadingRef: uploadingBatch,
    }, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (data) {
        batchWindow.value = false;
        upsertBatchData(data);
        await Promise.all([loadDuplicates(), loadRecentBatches()]);
    }
    const el = props.container
    if (!el) return
    await nextTick();
    requestAnimationFrame(() => el.scrollTo({ top: 0, behavior: 'smooth' }));
};

const onImmediateScanDone = async () => {
    batchWindow.value = false;
    await Promise.all([getContacts(), loadDuplicates()]);
    startEnrichmentPolling();
    const el = props.container;
    if (!el) return;
    await nextTick();
    requestAnimationFrame(() => el.scrollTo({ top: 0, behavior: 'smooth' }));
};

const deleteContact = async (id: number) => {
    await api.del('/contact_item', { id }, {
        ask: 'コンタクトを削除しますか。',
        toast: 'コンタクトを削除しました。',
    });
    await getContacts();
    await loadDuplicates();
    router.push({ name: 'contact' });
};

const closeCreate = async (flag: boolean) => {
    createWindow.value = false;
    editData.value = null;
    if (flag) {
        await getContacts();
        await loadDuplicates();
        startEnrichmentPolling();
    }
};

const activeContact = computed(() => {
    const contactId = Number(route.params.contactId);
    return contactId
        ? contacts.value.find(contact => contact.id === contactId) || null
        : null;
});

// --- Filtering / faceting / sorting / pagination (client-side over the full list) ---
const normalizedKeyword = computed(() => props.keyword?.toLowerCase?.() ?? '');

// personal = contacts I created or collaborate on.
const isMine = (c: ContactRecord) => {
    const me = meId.value;
    if (!me) return false;
    return c.creator?.id === me || (c.collaborators ?? []).some(x => x.id === me);
};
const matchScope = (c: ContactRecord) =>
    scope.value === 'all' ? true : isMine(c);
const matchSearch = (c: ContactRecord) => {
    const q = normalizedKeyword.value;
    if (!q) return true;
    return [c.name, c.company_name, c.department, c.email, c.name_kana, c.company_name_kana]
        .some(v => String(v ?? '').toLowerCase().includes(q));
};
const matchType = (c: ContactRecord) =>
    !selectedTypes.value.length || (c.types ?? []).some(t => t.id != null && selectedTypes.value.includes(t.id));

const baseList = computed(() => contacts.value.filter(c => matchScope(c) && matchSearch(c)));

const typeCountMap = computed(() => {
    const m: Record<number, number> = {};
    for (const c of baseList.value) {
        for (const t of (c.types ?? [])) {
            if (t.id != null) m[t.id] = (m[t.id] ?? 0) + 1;
        }
    }
    return m;
});

const filteredRows = computed(() => baseList.value.filter(c => matchType(c)));

const sortedRows = computed(() => {
    const rows = [...filteredRows.value];
    if (sort.value === 'name') {
        rows.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'ja'));
    } else if (sort.value === 'dept') {
        rows.sort((a, b) =>
            (b.department ? 1 : 0) - (a.department ? 1 : 0)
            || (a.department || '').localeCompare(b.department || '', 'ja')
            || (a.name || '').localeCompare(b.name || '', 'ja'));
    } else if (sort.value === 'date') {
        rows.sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || '')));
    }
    return rows;
});

const totalCount = computed(() => sortedRows.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalCount.value / pageSize.value)));
const currentPage = computed(() => Math.min(Math.max(1, page.value), totalPages.value));
const pagedList = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    return sortedRows.value.slice(start, start + pageSize.value);
});

const typeOptions = computed(() =>
    contactTypes.value
        .filter(t => t.id != null)
        .map(t => ({ value: t.id as number, label: t.title, count: typeCountMap.value[t.id as number] ?? 0 })));

const quickChips = computed(() =>
    contactTypes.value
        .filter(t => t.id != null)
        .map(t => ({ id: t.id as number, title: t.title, count: typeCountMap.value[t.id as number] ?? 0 }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 4));

const toggleType = (id: number) => {
    selectedTypes.value = selectedTypes.value.includes(id)
        ? selectedTypes.value.filter(x => x !== id)
        : [...selectedTypes.value, id];
};
const hasAnyFilter = computed(() => selectedTypes.value.length > 0);
const activePills = computed(() => {
    const pills: { key: string; label: string; onRemove: () => void }[] = [];
    for (const id of selectedTypes.value) {
        const t = contactTypes.value.find(x => x.id === id);
        pills.push({ key: 'type-' + id, label: '種類: ' + (t?.title ?? id), onRemove: () => toggleType(id) });
    }
    return pills;
});
const clearAllFilters = () => {
    selectedTypes.value = [];
};

const prevPage = () => { page.value = Math.max(1, currentPage.value - 1); };
const nextPage = () => { page.value = Math.min(totalPages.value, currentPage.value + 1); };
const goToPage = (p: number) => { page.value = Math.min(totalPages.value, Math.max(1, p)); };

// Windowed page list: all pages when few, otherwise 1 … around-current … last.
const pageItems = computed<(number | 'gap')[]>(() => {
    const total = totalPages.value;
    const cur = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const items: (number | 'gap')[] = [1];
    const start = Math.max(2, cur - 1);
    const end = Math.min(total - 1, cur + 1);
    if (start > 2) items.push('gap');
    for (let p = start; p <= end; p++) items.push(p);
    if (end < total - 1) items.push('gap');
    items.push(total);
    return items;
});

const pagerArrowClass = (enabled: boolean) => [
    'w-[32px] h-[32px] flex items-center justify-center border border-[var(--normalBorder)] bg-[var(--inactive-background)] transition-colors',
    enabled ? 'text-[var(--primary-color)] cursor-pointer hover:border-[var(--formBorder)]' : 'text-[gray] opacity-40 cursor-default'
];
const pageNumClass = (active: boolean) => [
    'min-w-[32px] h-[32px] px-[6px] flex items-center justify-center border text-[13px] transition-colors cursor-pointer',
    active
        ? 'border-[var(--primary-color)] bg-[var(--primary-color)] text-[var(--background-color)] font-bold'
        : 'border-[var(--normalBorder)] bg-[var(--inactive-background)] text-[var(--primary-color)] hover:border-[var(--formBorder)]'
];

// Reset to first page whenever the result set changes.
watch([() => props.keyword, scope, selectedTypes, sort, pageSize], () => {
    page.value = 1;
}, { deep: true });

const exportCsv = () => {
    const rows = sortedRows.value;
    if (!rows.length) {
        dialog.ping('出力できるコンタクトがありません。');
        return;
    }
    // Public information only — private memos and internal notes are intentionally excluded.
    const data = rows.map(c => ({
        '氏名': c.name ?? '',
        '会社名': c.company_name ?? '',
        '部署': c.department ?? '',
        '種類': (c.types ?? []).map(t => t.title).join(' / '),
        'メールアドレス': c.email ?? '',
        '電話番号': c.phone ?? '',
        '住所': c.address ?? '',
        '登録日時': c.created_at ?? '',
    }));
    const config = mkConfig({
        useKeysAsHeaders: true,
        useBom: true,
        filename: 'contacts_' + new Date().toISOString().slice(0, 10),
    });
    const csv = generateCsv(config)(data);
    download(config)(csv);
    dialog.toast('公開情報をCSVに出力しました。');
};

onMounted(() => {
    if (!responsive.mobile) {
        viewType.value = localStorage.getItem('contactViewType') || 'grid';
    }
    getContacts().then(startEnrichmentPolling);
    getTypes();
    loadRecentBatches();
    loadBatchNotifications();
    loadDuplicates();
});

const openDuplicateModal = async () => {
    await loadDuplicates();
    if (!duplicates.value.length) {
        dialog.toast('現在、確認が必要な重複データはありません。');
        return;
    }
    duplicateModalOpen.value = true;
};

const closeDuplicateModal = () => { duplicateModalOpen.value = false; };

const resolveDuplicate = async ({ contactId, action, targetId }: { contactId: number; action: 'keep' | 'merge'; targetId?: number | null }) => {
    if (!contactId) return;

    if (action === 'merge' && !targetId) {
        dialog.ping('統合先のコンタクトを選択してください。');
        return;
    }

    duplicateResolving.value = contactId;
    try {
        await api.post(`/contact_duplicates/${contactId}`, {
            action,
            target_id: action === 'merge' ? targetId : null,
        }, {
            toast: action === 'merge' ? '既存コンタクトに統合しました。' : '重複フラグを解除しました。',
        });

        await Promise.all([getContacts(), loadDuplicates()]);
        await loadRecentBatches();

        if (!duplicates.value.length) {
            duplicateModalOpen.value = false;
        }
    } finally {
        duplicateResolving.value = null;
    }
};
</script>

<style scoped>
/* Tailwind preflight is disabled app-wide, so border-width utilities need an
   explicit border-style to render. */
[class~="border"],
[class~="border-2"] { border-style: solid; }
[class~="border-t"] { border-top-style: solid; }
[class~="border-b"] { border-bottom-style: solid; }
[class*="border"] { box-sizing: border-box !important; }
.fc-viewtoggle { display: inline-flex; flex-shrink: 0; height: 29px; border: 1px solid var(--formBorder); }
.fc-vt-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 100%; border: none; background: var(--background-color); cursor: pointer; padding: 0; }
.fc-vt-btn + .fc-vt-btn { border-left: 1px solid var(--formBorder); }
.fc-vt-btn :deep(svg) { fill: gray; }
.fc-vt-btn.on { background: var(--primary-button, var(--primary-color)); }
/* --primary-button is dark in both themes (#000 / #4b4b4b), so the active glyph is white in both */
.fc-vt-btn.on :deep(svg) { fill: #fff; }
/* Scope switch — same segmented control, but text buttons */
.fc-scope { display: inline-flex; flex-shrink: 0; border: 1px solid var(--formBorder); }
.fc-scope-btn { display: inline-flex; align-items: center; justify-content: center; height: 34px; padding: 0 16px; border: none; background: var(--background-color); color: gray; font-size: 13px; font-weight: 500; cursor: pointer; white-space: nowrap; }
.fc-scope-btn + .fc-scope-btn { border-left: 1px solid var(--formBorder); }
.fc-scope-btn.on { background: var(--primary-button, var(--primary-color)); color: #fff; }
</style>
