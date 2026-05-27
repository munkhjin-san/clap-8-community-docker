<template>
    <section v-if="hasPrivilage" class="mt-6 bg-[var(--bg3)] px-4 py-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] pb-4">
            <div>
                <h2 class="text-[16px] font-semibold text-[var(--font1)]">緊急連絡履歴</h2>
            </div>
            <button
                class="rounded-full border border-[var(--line)] px-3 py-1 text-[12px] text-[var(--font1)] transition hover:bg-[var(--bg3)]"
                type="button"
                @click="getEmergencyContacts"
            >
                再読み込み
            </button>
        </div>

        <div v-if="loading" class="py-8 text-center text-[13px] text-[gray]">履歴を読み込み中です。</div>

        <div v-else-if="list.length === 0" class="py-8 text-center text-[13px] text-[gray]">
            緊急連絡の履歴はまだありません。
        </div>

        <div v-else class="mt-4 overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0 text-left text-[13px]">
                <thead>
                    <tr>
                        <th class="border-b border-[var(--line)] px-4 py-3 font-medium text-[gray]">送信日時</th>
                        <th class="border-b border-[var(--line)] px-4 py-3 font-medium text-[gray]">内容</th>
                        <th class="border-b border-[var(--line)] px-4 py-3 font-medium text-[gray]">コメント</th>
                        <th class="border-b border-[var(--line)] px-4 py-3 font-medium text-[gray]">ステータス</th>
                        <th class="border-b border-[var(--line)] px-4 py-3 text-right font-medium text-[gray]">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in list" :key="item.id" class="align-middle">
                        <td class="align-middle border-b border-[var(--line)] px-4 py-4 whitespace-nowrap text-[var(--font1)]">
                            {{ formatDate(item.created_at) }}
                        </td>
                        <td class="align-middle border-b border-[var(--line)] px-4 py-4 text-[var(--font1)]">
                            <p class="max-w-[560px] whitespace-pre-wrap break-words leading-6">{{ item.content }}</p>
                        </td>
                        <td class="align-middle border-b border-[var(--line)] px-4 py-4">
                            <button
                                class="inline-flex items-center gap-2 rounded-full border border-[var(--line)] px-3 py-2 text-[12px] text-[var(--font1)] transition hover:bg-[var(--bg3)]"
                                type="button"
                                @click="openCommentModal(item)"
                            >
                                <svg class="h-[15px] w-[18px] fill-current" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 32">
                                    <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path>
                                    <path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                </svg>
                                <span class="leading-none">{{ item.actions_count ?? 0 }}</span>
                            </button>
                        </td>
                        <td class="align-middle border-b border-[var(--line)] px-4 py-4">
                            <span :class="statusClass(item.status)" class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                {{ statusLabel(item.status) }}
                            </span>
                        </td>
                        <td class="align-middle border-b border-[var(--line)] px-4 py-4 text-right">
                            <button
                                v-if="item.status !== 'complete'"
                                class="rounded-full bg-[var(--main)] px-3 py-1 text-[12px] transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                :disabled="updatingId === item.id"
                                @click="updateStatus(item.id, 'complete')"
                            >
                                {{ updatingId === item.id ? '更新中...' : '完了にする' }}
                            </button>
                            <span v-else class="text-[12px] text-[gray]">完了</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmergencyContactCommentModal
            :contact="selectedContact"
            :comments="comments"
            :loading="commentsLoading"
            :comment-text="commentText"
            :sending-comment="sendingComment"
            @close="closeCommentModal"
            @submit="submitComment"
            @update:comment-text="commentText = $event"
        />
    </section>
    <section v-else class="mt-6 bg-[var(--bg3)] px-4 py-5 text-center text-[13px] text-[gray] shadow-sm">
        アクセス権限がありません。
    </section>
</template>

<script setup lang="ts">
import EmergencyContactCommentModal from '@/components/Support/EmergencyContactCommentModal.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import type { EmergencyContactAction, EmergencyContactRecord, EmergencyContactStatus } from '@/interface/supportInterface';
import { useAuthUserStore } from '@/store/auth';
import { DateTime } from 'luxon';
import { computed, onMounted, ref } from 'vue';

const api = useApi();
const { ping, toast } = useDialog();

const list = ref<EmergencyContactRecord[]>([]);
const loading = ref(false);
const updatingId = ref<number | null>(null);
const selectedContact = ref<EmergencyContactRecord | null>(null);
const comments = ref<EmergencyContactAction[]>([]);
const commentsLoading = ref(false);
const commentText = ref('');
const sendingComment = ref(false);
const auth = useAuthUserStore()

onMounted(() => {
    getEmergencyContacts();
});

const hasPrivilage = computed(() => {
    return auth.isAdmin || auth.isBoss
});

const getEmergencyContacts = async () => {
    loading.value = true;

    try {
        const response = await api.get('/get_emergency_contacts');
        list.value = Array.isArray(response) ? response as EmergencyContactRecord[] : [];
    } catch (error) {
        console.error(error);
        ping('緊急連絡履歴の取得に失敗しました。');
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (id: number, status: EmergencyContactStatus) => {
    updatingId.value = id;

    try {
        const updated = await api.post('/update_emergency_contact_status', { id, status });
        if (!updated) {
            return;
        }

        list.value = list.value.map((item) => (item.id === id ? updated as EmergencyContactRecord : item));
        toast('ステータスを更新しました。');
    } catch (error) {
        console.error(error);
        ping('ステータスの更新に失敗しました。');
    } finally {
        updatingId.value = null;
    }
};

const openCommentModal = async (item: EmergencyContactRecord) => {
    selectedContact.value = item;
    commentText.value = '';
    await loadComments(item.id);
};

const closeCommentModal = () => {
    selectedContact.value = null;
    comments.value = [];
    commentText.value = '';
    commentsLoading.value = false;
    sendingComment.value = false;
};

const loadComments = async (emergencyContactId: number) => {
    commentsLoading.value = true;

    try {
        const response = await api.get('/get_emergency_contact_actions', { emergency_contact_id: emergencyContactId });
        comments.value = Array.isArray(response) ? response as EmergencyContactAction[] : [];
    } catch (error) {
        console.error(error);
        ping('コメントの取得に失敗しました。');
    } finally {
        commentsLoading.value = false;
    }
};

const submitComment = async () => {
    if (!selectedContact.value || sendingComment.value) {
        return;
    }

    const text = commentText.value.trim();
    if (text === '') {
        ping('コメントを入力してください。');
        return;
    }

    sendingComment.value = true;

    try {
        const response = await api.post('/add_emergency_contact_action', {
            emergency_contact_id: selectedContact.value.id,
            text,
        });

        if (!response) {
            return;
        }

        const createdComment = response as EmergencyContactAction;
        comments.value.push(createdComment);
        commentText.value = '';
        toast('コメントを追加しました。');
        applyCommentCount(selectedContact.value.id, comments.value.length);
    } catch (error) {
        console.error(error);
        ping('コメントの追加に失敗しました。');
    } finally {
        sendingComment.value = false;
    }
};

const applyCommentCount = (id: number, count: number) => {
    list.value = list.value.map((item) => (item.id === id ? { ...item, actions_count: count } : item));

    if (selectedContact.value?.id === id) {
        selectedContact.value = {
            ...selectedContact.value,
            actions_count: count,
        };
    }
};

const formatDate = (value: string) => {
    const date = DateTime.fromISO(value);
    return date.isValid ? date.toFormat('yyyy/M/d HH:mm') : value;
};

const statusLabel = (status: EmergencyContactStatus) => {
    return status === 'complete' ? '完了' : '対応中';
};

const statusClass = (status: EmergencyContactStatus) => {
    return status === 'complete'
        ? 'bg-emerald-50 text-emerald-700'
        : 'bg-amber-50 text-amber-700';
};
</script>