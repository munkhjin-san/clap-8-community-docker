<template>
    <Transition name="modalFade">
        <Modal v-if="open" @close="handleClose">
            <template #title>
                <p>自分用メモ</p>
            </template>
            <template #content>
                <div class="space-y-8">
                    <div class="!box-border bg-[var(--bg2)] p-4 text-sm">
                        <p>
                            {{ contactLabel }}
                        </p>
                        <p class="mt-1 text-xs text-[gray]">
                            このメモはあなたにしか表示されません。コンタクトへの印象や会話のポイントを自由に残しておけます。
                        </p>
                    </div>
                    <div class="space-y-2">
                        <LongInput 
                            v-model="localMemo"
                            placeHolder="例）初回訪問済み。次回は決裁者同席で提案予定。"
                            id="private-memo-input"
                        />
                    </div>
                    <!-- <div class="flex flex-wrap justify-between gap-3 text-xs text-gray-400">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="h-4 w-4">
                                <path
                                    fill="currentColor"
                                    d="M10 0a10 10 0 1010 10A10 10 0 0010 0zm0 18.75A8.75 8.75 0 1118.75 10 8.76 8.76 0 0110 18.75zM9.38 4.37a.94.94 0 11.94.94.94.94 0 01-.94-.94zm1.87 10h-1.88a.62.62 0 010-1.25h.63v-4.38h-.63a.62.62 0 010-1.24h1.25a.62.62 0 01.62.62v5h.63a.62.62 0 010 1.25z"
                                />
                            </svg>
                            <span>保存するとクラウドに記録されます。</span>
                        </div>
                        <span v-if="lastUpdatedLabel">{{ lastUpdatedLabel }}</span>
                    </div> -->
                    <div>
                        <LoaderButton :loading="saving" content="保存する" @triggered="handleSave"/>
                        <!-- <button
                            type="button"
                            class="rounded-md border border-gray-600 px-4 py-2 text-xs text-gray-200 hover:bg-gray-700 disabled:opacity-40"
                            :disabled="saving"
                            @click="handleClose"
                        >
                            キャンセル
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-[var(--primary-color)] px-4 py-2 text-xs text-[var(--background-color)] hover:brightness-110 disabled:opacity-40"
                            :disabled="isSaveDisabled"
                            @click="handleSave"
                        >
                            保存する
                        </button> -->
                    </div>
                </div>
            </template>
        </Modal>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import { ContactRecord } from '@/interface/contactInterface';
import LongInput from '@/components/Form/LongInput.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';

const maxLength = 2000;

const props = defineProps<{
    open: boolean;
    contact: ContactRecord | null;
    memo: string;
    saving: boolean;
    viewerId: number | null;
}>();
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', payload: string): void;
}>();

const localMemo = ref('');
watch(
    () => props.memo,
    (value) => {
        if (value !== localMemo.value) {
            localMemo.value = value ?? '';
        }
    },
    { immediate: true },
);

const contactLabel = computed(() => {
    if (!props.contact) {
        return 'コンタクト';
    }
    const name = props.contact.name || '名称未設定';
    const company = props.contact.company_name ? `（${props.contact.company_name}）` : '';
    return `${name}${company}`;
});

const lastUpdatedLabel = computed(() => {
    const viewerId = props.viewerId;
    if (!viewerId) {
        return '';
    }
    const updatedAt = props.contact?.collaborators?.find(collab => collab.id === viewerId)?.pivot?.updated_at;
    if (!updatedAt) {
        return '';
    }
    const date = new Date(updatedAt);
    if (Number.isNaN(date.getTime())) {
        return '';
    }
    return `最終更新: ${date.toLocaleString()}`;
});

const isSaveDisabled = computed(() => {
    if (props.saving) {
        return true;
    }
    const baseline = props.memo ?? '';
    return localMemo.value.trim() === baseline.trim();
});

const handleClose = () => {
    emit('close');
};

const handleSave = () => {
    emit('save', localMemo.value);
};
</script>
