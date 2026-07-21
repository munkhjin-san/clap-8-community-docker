<template>
    <Transition name="modalFade">
        <Modal v-if="open" @close="emit('close')">
            <template #title>
                <p>非公開メモ</p>
            </template>
            <template #content>
                <div class="space-y-6">
                    <div class="!box-border bg-[var(--bg2)] p-4 text-sm">
                        <p>{{ contactLabel }}</p>
                        <p class="mt-1 text-xs text-[gray] leading-normal">
                            このメモはあなたにしか表示されません。追加した順に記録され、いつでも削除できます。
                        </p>
                    </div>

                    <!-- Add a new entry -->
                    <div class="space-y-2">
                        <LongInput
                            v-model="draft"
                            placeHolder="例）初回訪問済み。次回は決裁者同席で提案予定。"
                            id="private-memo-input"
                        />
                        <LoaderButton :loading="adding" content="メモを追加" @triggered="addMemo"/>
                    </div>

                    <!-- Log -->
                    <div class="space-y-2">
                        <div v-if="loading" class="text-xs text-[gray] text-center py-4">読み込み中…</div>
                        <div v-else-if="!memos.length" class="!box-border border-[1.5px] border-dashed border-[var(--formBorder)] p-5 text-center text-[gray] text-[13px]">
                            まだメモはありません
                        </div>
                        <div
                            v-else
                            v-for="m in memos"
                            :key="m.id"
                            class="!box-border bg-[var(--message-background)] border border-solid border-[var(--normalBorder)] p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-[13px] text-[var(--primary-color)] leading-[1.7] whitespace-pre-wrap break-words flex-1 min-w-0">{{ m.body }}</p>
                                <button
                                    @click="removeMemo(m)"
                                    title="削除"
                                    class="flex-none w-[24px] h-[24px] flex items-center justify-center text-[gray] hover:text-[var(--primary-color)] bg-transparent border-none cursor-pointer"
                                >
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                                </button>
                            </div>
                            <div class="text-[11px] text-[gray] mt-2">{{ fmt(m.created_at) }}</div>
                        </div>
                    </div>
                </div>
            </template>
        </Modal>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import { ContactPrivateMemo, ContactRecord } from '@/interface/contactInterface';
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useApi } from '@/composables/api';

const props = defineProps<{
    open: boolean;
    contact: ContactRecord | null;
}>();
const emit = defineEmits<{
    (e: 'close'): void;
}>();

const api = useApi();
const memos = ref<ContactPrivateMemo[]>([]);
const draft = ref('');
const loading = ref(false);
const adding = ref(false);

const contactLabel = computed(() => {
    if (!props.contact) return 'コンタクト';
    const name = props.contact.name || '名称未設定';
    const company = props.contact.company_name ? `（${props.contact.company_name}）` : '';
    return `${name}${company}`;
});

const load = async () => {
    if (!props.contact?.id) return;
    loading.value = true;
    try {
        const data = await api.get(`/contact_private_memos_list/${props.contact.id}`, null, { silent: true });
        memos.value = Array.isArray(data) ? data : [];
    } finally {
        loading.value = false;
    }
};

watch(
    () => [props.open, props.contact?.id],
    () => {
        if (props.open && props.contact?.id) {
            draft.value = '';
            memos.value = [];
            load();
        }
    },
    { immediate: true },
);

const addMemo = async () => {
    const body = draft.value.trim();
    if (!body || adding.value || !props.contact?.id) return;
    adding.value = true;
    try {
        const created = await api.post('/contact_private_memo_add', {
            contact_id: props.contact.id,
            body,
        }, { toast: 'メモを追加しました。' });
        if (created) {
            memos.value.unshift(created);
            draft.value = '';
        }
    } finally {
        adding.value = false;
    }
};

const removeMemo = async (m: ContactPrivateMemo) => {
    await api.del(`/contact_private_memo/${m.id}`, {}, { ask: 'このメモを削除しますか？', toast: '削除しました。' });
    memos.value = memos.value.filter(x => x.id !== m.id);
};

const fmt = (value: string) => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const pad = (n: number) => (n < 10 ? '0' + n : '' + n);
    return `${date.getFullYear()}/${pad(date.getMonth() + 1)}/${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
};
</script>
