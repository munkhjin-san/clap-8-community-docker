<template>
    <div>
        <Transition name="modalFade">
            <div class="member-loader" v-if="initialLoader == 0">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="no-comment-text" v-if="initialLoader > 0 && !contacts.length">現在データはありません。</div>
        <ContactViewToggle v-if="!responsive.mobile" style="position: fixed" :type="viewType" @action="setViewType"/>
        <FloatButton :style="{position: 'fixed', bottom: auth.user?.footer_view && responsive.mobile ? '65px' : '20px'}" @action="openBatchModal">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>
        <div class="flex gap-[10px] flex-wrap mb-[20px] ml-[20px]">
            <label :class="['text-[13px] bg-[var(--background-color)] select-none text-[var(--primary-color)] px-[8px] py-[5px] cursor-pointer', {'!bg-[var(--primary-color)] !text-[var(--background-color)]': type.id && selectedTypes.includes(type.id)}]" v-for="type in contactTypes">
                <input v-model="selectedTypes" type="checkbox" class="hidden" :value="type.id"/>
                {{ type.title }}
            </label>
        </div>
        <div v-if="batchNotifications.length" class="mb-[20px] ml-[20px] max-w-3xl space-y-3">
            <div v-for="notification in batchNotifications" :key="notification.id" class="bg-[var(--background-color)] p-4 border-l-4 border-[var(--primary-color)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-[var(--primary-color)]">{{ notification.title }}</p>
                        <p class="text-[13px] text-[gray] mt-1 leading-5">{{ notification.message }}</p>
                    </div>
                    <button class="text-xs text-white bg-[var(--primary-button)] p-1 underline whitespace-nowrap" @click="markBatchNotificationRead(notification.id)">
                        確認しました
                    </button>
                </div>
            </div>
        </div>
        <div v-if="trackedBatches.length" class="mb-[20px] ml-[20px] max-w-3xl space-y-3">
            <div v-for="batch in trackedBatches" :key="batch.id" class="bg-[var(--background-color)] p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-[gray]">名刺の取り込み状況 #{{ batch.id }}</p>
                        <p class="text-base font-semibold text-[var(--primary-color)]">{{ batchStatusLabel(batch) }}</p>
                        <p class="text-xs text-[gray] mt-1">
                            {{ batchStatusGuide(batch) }}
                        </p>
                        <p class="text-[11px] text-[gray] leading-normal" v-if="batch.duration_seconds !== undefined && batch.duration_seconds !== null">
                            経過時間: {{ formatDuration(batch.duration_seconds) }}
                        </p>
                        <p class="text-[11px] text-[gray] leading-normal" v-if="batch.scan_requested_at">
                            スキャン開始: {{ formatDate(batch.scan_requested_at) }}
                        </p>
                        <p class="text-[11px] text-[gray] leading-normal" v-if="batch.enrich_requested_at">
                            情報収集開始: {{ formatDate(batch.enrich_requested_at) }}
                        </p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="text-[gray]">完了 {{ batch.counts?.completed ?? 0 }} / {{ batch.counts?.total ?? 0 }}</p>
                        <p v-if="batch.error" class="text-xs text-red-400 mt-1 max-w-[220px] truncate">{{ batch.error }}</p>
                    </div>
                </div>
                <ul class="mt-3 space-y-1 max-h-48 overflow-y-auto pr-1">
                    <li v-for="item in batch.items" :key="item.id" class="flex justify-between text-sm text-[gray] gap-4">
                        <span class="truncate">{{ item.original_filename }}</span>
                        <span class="flex items-center gap-2">
                            <span :class="batchItemStatusClass(item.status)">{{ batchItemStatusLabel(item.status) }}</span>
                            <span v-if="item.needs_review" class="text-xs text-yellow-400">重複確認</span>
                        </span>
                    </li>
                </ul>
                <div v-if="isTerminalBatchStatus(batch.status)" class="mt-3 text-right">
                    <button class="text-xs text-white bg-[var(--primary-button)] p-1" @click="clearBatchTracking(batch.id)">表示を閉じる</button>
                </div>
            </div>
        </div>
        <div class="mb-[20px] ml-[20px] flex gap-3">
            <CommandButton :buttons="commandButtons" />
        </div>
        <div>            
            <GridLayout 
                v-if="viewType == 'grid'" 
                :contacts="contactList"
                :viewer-id="viewerId"
                @reload="getContacts"
                @open-memo="openPrivateMemo"
            />
            <TableLayout 
                v-if="viewType == 'table'" 
                :contacts="contactList"
                @open-memo="openPrivateMemo"
            />
        </div>
        <Transition name="modalFade">
            <BatchCreation
                v-if="batchWindow" 
                @files-selected="fileupload"
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
            :memo="memoDraft"
            :saving="memoSaving"
            :viewer-id="viewerId"
            @close="closeMemoModal"
            @save="handleMemoSave"
        />
    </div>
</template>
<script setup lang="ts">
import { useResponsive } from '@/store/responsive';
import FloatButton from '@/components/Global/FloatButton.vue';
import ContactCreate from './ContactCreate.vue';
import { ref, computed, onMounted, nextTick } from 'vue';
import { BatchPayload, Collaborator, ContactBatchNotificationSummary, ContactBatchSummary, ContactRecord, ContactType, DuplicateSummary } from '@/interface/contactInterface';
import GridLayout from './Grid/GridLayout.vue';
import TableLayout from './Table/TableLayout.vue';
import { useRoute, useRouter } from 'vue-router';
import ContactViewToggle from './ContactViewToggle.vue';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import BatchCreation from './BatchCreation.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useDialog } from '@/composables/dialog';
import DuplicateReviewModal from './DuplicateReviewModal.vue';
import PrivateMemoModal from './PrivateMemoModal.vue';



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
const memoDraft = ref('');
const memoSaving = ref(false);
const viewerId = computed(() => auth.user?.id ?? null);

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
        dialog.ping('メモを保存するにはコンタクトを登録してください。');
        return;
    }

    const sourceContact =
        contacts.value.find(existing => existing.id === contact.id) ?? contact;

    if (!sourceContact.collaborators) {
        sourceContact.collaborators = [];
    }

    memoTarget.value = sourceContact;
    const existingMemo =
        sourceContact.collaborators?.find(collab => collab.id === viewerId.value)?.pivot?.private_memo ?? '';
    memoDraft.value = existingMemo;
    memoModalOpen.value = true;
};

const closeMemoModal = () => {
    memoModalOpen.value = false;
    memoTarget.value = null;
    memoDraft.value = '';
};

const handleMemoSave = async (memoValue: string) => {
    if (!memoTarget.value || memoSaving.value || memoTarget.value.id == null) {
        return;
    }
    memoDraft.value = memoValue;
    memoSaving.value = true;

    try {
        const response = await api.post('/contact_private_memo', {
            contact_id: memoTarget.value.id,
            private_memo: memoDraft.value,
        }, {
            toast: 'メモを保存しました。',
        });

        const viewer = viewerId.value;
        if (viewer && memoTarget.value.id !== null) {
            const contactIndex = contacts.value.findIndex(contact => contact.id === memoTarget.value?.id);
            const target = contactIndex !== -1 ? contacts.value[contactIndex] : memoTarget.value;

            if (target) {
                if (!target.collaborators) {
                    target.collaborators = [];
                }
                const existingCollaborator = target.collaborators.find(collab => collab.id === viewer);
                const memoRole = response?.role ?? (existingCollaborator?.pivot?.role ?? 'viewer');
                if (existingCollaborator) {
                    existingCollaborator.pivot.private_memo = memoDraft.value;
                    existingCollaborator.pivot.role = memoRole;
                    existingCollaborator.pivot.updated_at = new Date().toISOString();
                } else if (auth.user && auth.user.id) {
                    const collaborator: Collaborator = {
                        ...auth.user,
                        pivot: {
                            role: memoRole,
                            private_memo: memoDraft.value,
                            created_at: new Date().toISOString(),
                            updated_at: new Date().toISOString(),
                        },
                    }
                    target.collaborators.push(collaborator);
                }
            }
        }

        closeMemoModal();
    } finally {
        memoSaving.value = false;
    }
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

const batchStatusLabel = (batch: ContactBatchSummary) => {
    return batchStatusMessages[batch.status] ?? '処理状況を確認しています。';
};

const batchStatusGuide = (batch: ContactBatchSummary) => {
    return batchStatusGuides[batch.status] ?? 'このまま別の作業をしても大丈夫です。';
};

const batchItemStatusLabel = (status: string) => {
    return batchItemStatusMessages[status] ?? status;
};

const batchItemStatusClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'text-green-600';
        case 'failed':
            return 'text-red-500';
        case 'enriching':
            return 'text-purple-600';
        case 'scanning':
            return 'text-blue-600';
        case 'scanned':
            return 'text-indigo-600';
        default:
            return 'text-gray-600';
    }
};

const duplicateButtonLabel = computed(() => {
    if (duplicateLoading.value) return '重複レビュー (更新中)';
    return `重複レビュー (${duplicates.value.length})`;
});

const commandButtons = computed(() => [
    {
        title: '取り込み状況を更新',
        action: () => refreshBatchArea(),
    },
    {
        title: duplicateButtonLabel.value,
        action: () => openDuplicateModal(),
    },
]);

const setViewType = () => {
    viewType.value = viewType.value === 'grid' ? 'table' : 'grid';
    localStorage.setItem('contactViewType', viewType.value);
};

const formatDate = (iso: string) => {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return iso;
    return `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
};

const formatDuration = (durationSeconds: number | null | undefined) => {
    if (durationSeconds == null || Number.isNaN(durationSeconds)) return '-';

    if (durationSeconds < 60) {
        return `${durationSeconds}秒`;
    }

    const hours = Math.floor(durationSeconds / 3600);
    const minutes = Math.floor((durationSeconds % 3600) / 60);
    const seconds = durationSeconds % 60;

    if (hours > 0) {
        return `${hours}時間${minutes}分${seconds}秒`;
    }

    return `${minutes}分${seconds}秒`;
};

const openBatchModal = () => {
    batchWindow.value = true;
};

const upsertBatchData = (batch: ContactBatchSummary) => {
    const index = trackedBatchData.value.findIndex(entry => entry.id === batch.id);

    if (index === -1) {
        trackedBatchData.value.push(batch);
        return;
    }

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

const getTypes = async () => {
    contactTypes.value = await api.get('/get_contact_types');
};

const getContacts = async () => {
    contacts.value = await api.get('/contact_list');
    initialLoader.value++;
};

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
    if (payload.type == null) {
        dialog.ping('コンタクト種類を選択してください。');
        return;
    }
    payload.files.forEach(file => formData.append('images[]', file));
    formData.append('type_id', payload.type.toString())
    if (payload.p_type) {
        formData.append('p_type', payload.p_type)
    }
    
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

const deleteContact = async (id: number) => {
    await api.del('/contact_item', { id }, {
        ask: 'コンタクトを削除しますか。',
        toast: 'コンタクトを削除しました。',
    });
    await getContacts();
    await loadDuplicates();
    router.push({ name: 'tab2' });
};

const closeCreate = async (flag: boolean) => {
    createWindow.value = false;
    editData.value = null;
    if (flag) {
        await getContacts();
        await loadDuplicates();
    }
};

const activeContact = computed(() => {
    const contactId = Number(route.params.contactId);
    return contactId
        ? contacts.value.find(contact => contact.id === contactId) || null
        : null;
});

const normalizedKeyword = computed(() => props.keyword?.toLowerCase?.() ?? '');

const contactList = computed(() => {
    const keyword = normalizedKeyword.value;
    if (!keyword && !selectedTypes.value.length) return contacts.value;
    return contacts.value.filter(contact => {
        const matchedCategory = selectedTypes.value.length
            ? (contact.contact_type_id && selectedTypes.value.includes(contact.contact_type_id))
            : true;
        const matchedKey = keyword
            ? Object.values(contact).some(val =>
                String(val ?? '').toLowerCase().includes(keyword)
            )
            : true;
        return matchedCategory && matchedKey;
    });
});

onMounted(() => {
    if (!responsive.mobile) {
        viewType.value = localStorage.getItem('contactViewType') || 'grid';
    }
    getContacts();
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

const closeDuplicateModal = () => {
    duplicateModalOpen.value = false;
};

const resolveDuplicate = async ({ contactId, action, targetId }: { contactId: number; action: 'keep' | 'merge'; targetId?: number | null }) => {
    if (!contactId) {
        return;
    }

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
