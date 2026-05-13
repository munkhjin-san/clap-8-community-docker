<template>
    <Modal @close="emit('close', false)" size="large">
        <template #title>
            <p>{{ editTarget ? '更新情報を編集する' : '更新情報を作成する' }}</p>
        </template>
        <template #content>
            <div class="pt-1">
                <div class="my-5 grid grid-cols-2 gap-4 under960:grid-cols-1">
                    <label class="flex flex-col gap-2 text-[12px] text-[var(--primary-color)]">
                        <span>カテゴリー</span>
                        <select v-model="params.category" class="h-[42px] border border-solid border-[var(--primary-color)] bg-[var(--background-color)] px-3 text-[14px] text-[var(--primary-color)]">
                            <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-2 text-[12px] text-[var(--primary-color)]">
                        <span>状態</span>
                        <select v-model="params.status" class="h-[42px] border border-solid border-[var(--primary-color)] bg-[var(--background-color)] px-3 text-[14px] text-[var(--primary-color)]">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                </div>

                <div class="si-box">
                    <ShortInput
                        ref="titleInput"
                        v-model="params.title"
                        placeHolder="タイトル（必須）"
                        name="systemUpdateTitle"
                        rules="required|max:200"
                    />
                </div>

                <div class="si-box">
                    <LongInput
                        v-model="params.summary"
                        placeHolder="概要"
                        name="systemUpdateSummary"
                        rules="max:5000"
                    />
                </div>
                <div class="my-5 flex flex-wrap gap-4 items-center">
                    <label class="flex w-[min(260px,100%)] flex-col gap-2 text-[12px] text-[var(--primary-color)]">
                        <span>公開する</span>
                        <select v-model="publishedValue" class="h-[42px] border border-solid border-[var(--primary-color)] bg-[var(--background-color)] px-3 text-[14px] text-[var(--primary-color)]">
                            <option value="1">公開</option>
                            <option value="0">下書き</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-[var(--primary-color)] cursor-pointer">
                        <input type="checkbox" v-model="params.must_read" class="custom-f-checkbox"/>
                        <span>必読にする</span>
                    </label>
                </div>
                

                <section class="mt-7">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold">詳細項目</p>
                        <button type="button" class="rounded bg-[var(--bg3)] px-3 py-2 text-[12px] text-[var(--primary-color)]" @click="addDetail">+ 追加</button>
                    </div>

                    <div
                        v-for="(detail, index) in params.details"
                        :key="index"
                        class="mb-4 bg-[var(--bg3)] p-4"
                    >
                        <div class="my-5 grid grid-cols-2 gap-4 under960:grid-cols-1">
                            <label class="flex flex-col gap-2 text-[12px] text-[var(--primary-color)]">
                                <span>種別</span>
                                <select v-model="detail.type" class="h-[42px] border border-solid border-[var(--primary-color)] bg-[var(--background-color)] px-3 text-[14px] text-[var(--primary-color)]">
                                    <option v-for="option in detailTypeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </label>
                            <button
                                type="button"
                                class="self-end justify-self-end rounded bg-[var(--bg3)] px-3 py-2 text-[12px] text-[var(--primary-color)]"
                                @click="removeDetail(index)"
                            >
                                削除
                            </button>
                        </div>
                        <div class="si-box">
                            <ShortInput
                                v-model="detail.title"
                                placeHolder="詳細タイトル（必須）"
                                :name="`systemUpdateDetailTitle${index}`"
                                rules="required|max:200"
                            />
                        </div>
                        <div class="si-box">
                            <LongInput
                                v-model="detail.content"
                                placeHolder="詳細内容"
                                :name="`systemUpdateDetailContent${index}`"
                                rules="max:5000"
                            />
                        </div>
                        <div class="si-box">
                            <FileUploader
                                v-model="detail.files"
                                path="/system_update_files"
                                customPlaceHolder="添付ファイル"
                            />
                        </div>
                    </div>
                </section>

                <div class="si-box">
                    <LoaderButton @triggered="save" :loading="processing" content="保存する" />
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import {
    SystemUpdateDetail,
    SystemUpdateRecord,
} from '@/interface/supportInterface';
import { categoryOptions, detailTypeOptions, statusOptions } from './options';

const props = defineProps<{
    editTarget?: SystemUpdateRecord | null;
}>();

const emit = defineEmits<{
    close: [refreshNeeded: boolean];
}>();

const api = useApi();
const { ping } = useDialog();
const processing = ref(false);
const titleInput = ref<InstanceType<typeof ShortInput> | null>(null);

const params = reactive<SystemUpdateRecord>({
    id: props.editTarget?.id,
    category: props.editTarget?.category ?? 'update_log',
    title: props.editTarget?.title ?? '',
    summary: props.editTarget?.summary ?? '',
    status: props.editTarget?.status ?? 'published',
    is_published: props.editTarget?.is_published ?? true,
    published_at: props.editTarget?.published_at ?? null,
    scheduled_start_at: null,
    scheduled_end_at: null,
    must_read: props.editTarget?.must_read ?? false,
    details: props.editTarget?.details?.length
        ? props.editTarget.details.map((detail) => ({ ...detail, files: [...(detail.files ?? [])] }))
        : [createEmptyDetail()],
});

const publishedValue = computed({
    get: () => params.is_published ? '1' : '0',
    set: (value: string) => {
        params.is_published = value === '1';
    },
});

function createEmptyDetail(): SystemUpdateDetail {
    return {
        type: 'notice',
        title: '',
        content: '',
        files: [],
    };
}

const addDetail = () => {
    params.details.push(createEmptyDetail());
};

const removeDetail = (index: number) => {
    params.details.splice(index, 1);
};

const save = async () => {
    const titleValid = await titleInput.value?.validate();
    if (!titleValid?.valid) return;

    const validDetails = params.details
        .map((detail, index) => ({
            ...detail,
            sort_order: index,
            title: detail.title.trim(),
            files: detail.files ?? [],
        }))
        .filter((detail) => detail.title);

    if (!validDetails.length) {
        ping('詳細項目を1件以上入力してください。');
        return;
    }

    processing.value = true;
    await api.post('/system_update_save', {
        ...params,
        details: validDetails,
    }, {
        toast: props.editTarget ? '更新しました。' : '作成しました。',
        loadingRef: processing,
    });

    emit('close', true);
};
</script>
