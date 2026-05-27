<template>
    <Transition name="modalFade">
        <Modal
            v-if="contact"
            size="large"
            :disableScroll="true"
            bodyStyle="height: calc(100% - 0px);"
            @close="emit('close')"
        >
            <template #title>
                <span class="text-[16px] font-semibold text-[var(--font1)]">コメント</span>
            </template>
            <template #content>
                <div class="flex h-full min-h-0 flex-col">
                    <div class="border border-[var(--line)] bg-[var(--selected-background)] px-4 py-3">
                        <div class="flex flex-wrap items-center gap-3 text-[12px] text-[gray]">
                            <span>{{ formatDate(contact.created_at) }}</span>
                            <span :class="statusClass(contact.status)" class="inline-flex rounded-full px-3 py-1 font-semibold">
                                {{ statusLabel(contact.status) }}
                            </span>
                        </div>
                        <p class="mt-3 whitespace-pre-wrap break-words text-[13px] leading-6 text-[var(--font1)]">
                            {{ contact.content }}
                        </p>
                    </div>

                    <div class="mt-4 flex-1 overflow-y-auto border border-[var(--line)] bg-[var(--bg3)] p-4">
                        <div v-if="loading" class="py-10 text-center text-[13px] text-[gray]">コメントを読み込み中です。</div>
                        <div v-else-if="comments.length === 0" class="py-10 text-center text-[13px] text-[gray]">
                            まだコメントはありません。
                        </div>
                        <div v-else class="space-y-3">
                            <article v-for="comment in comments" :key="comment.id" class="bg-[var(--message-background)] px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-[12px] text-[gray]">
                                    <UserPanel :user="comment.user" size="25" with-name/>
                                    <span>{{ formatDate(comment.created_at) }}</span>
                                </div>
                                <p class="mt-2 whitespace-pre-wrap break-words text-[13px] leading-6 text-[var(--font1)]">{{ comment.text }}</p>
                            </article>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-[var(--line)] pt-4">
                        <div class="flex items-end gap-3">
                            <textarea
                                :value="commentText"
                                class="max-h-[100px] w-[calc(100%-2rem)] border-solid border border-[var(--line)] px-4 py-3 text-[13px] text-[var(--font1)] outline-none transition focus:border-[var(--main)]"
                                maxlength="2000"
                                placeholder="コメントを入力してください"
                                @input="emit('update:commentText', ($event.target as HTMLTextAreaElement).value)"
                            />
                            <button
                                class="rounded-full bg-[var(--main)] text-[12px] font-medium transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                :disabled="sendingComment || commentText.trim() === ''"
                                @click="emit('submit')"
                            >
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="25" viewBox="0 0 43 32">
                                    <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="text-[12px] text-[gray]">{{ commentText.length }}/2000</span>
                            
                        </div>
                    </div>
                </div>
            </template>
        </Modal>
    </Transition>
</template>

<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import type { EmergencyContactAction, EmergencyContactRecord, EmergencyContactStatus } from '@/interface/supportInterface';
import { DateTime } from 'luxon';
import UserPanel from '../Global/UserPanel.vue';

defineProps<{
    contact: EmergencyContactRecord | null;
    comments: EmergencyContactAction[];
    loading: boolean;
    commentText: string;
    sendingComment: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
    (e: 'update:commentText', value: string): void;
}>();

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