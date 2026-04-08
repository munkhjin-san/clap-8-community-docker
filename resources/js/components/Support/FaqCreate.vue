<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>{{ editTarget ? 'FAQを編集する' : 'FAQを作成する' }}</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput
                    ref="questionRef"
                    placeHolder="質問を入力（必須）"
                    name="faqQuestion"
                    rules="required|max:200"
                    label="質問"
                    v-model="params.question"
                />
            </div>
            <div class="si-box">
                <ShortInput
                    ref="answerRef"
                    placeHolder="簡単な回答を入力（必須）"
                    name="faqAnswer"
                    rules="required|max:500"
                    label="回答（要約）"
                    v-model="params.answer"
                />
            </div>
            <div class="si-box">
                <div style="font-size: 14px; margin-bottom: 15px;">詳細内容</div>
                <RichEditor
                    ref="richEdit"
                    :initilaValue="params.content"
                    :key="editorKey"
                    @content-updated="params.content = $event"
                />
            </div>
            <div class="si-box">
                <ItemSelector
                    placeHolder="タグを選択"
                    :options="tagOptions"
                    :multiple="true"
                    :clearable="true"
                    :close-on-select="false"
                    label="text"
                    :reduce="(opt) => opt.text"
                    v-model="selectedTags"
                />
            </div>
            <div class="si-box">
                <LoaderButton content="保存する" @triggered="save" :loading="processing" />
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import RichEditor from '@/components/Global/RichEditor.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import { useApi } from '@/composables/api';

interface FaqItem {
    id?: number;
    question: string;
    answer: string;
    content: string;
    tag_text: string;
}

interface TagItem {
    id: number;
    text: string;
}

interface Props {
    editTarget?: FaqItem | null;
    tagList?: TagItem[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [refreshNeeded: boolean];
}>();

const api = useApi();
const processing = ref(false);
const editorKey = ref(0);

const questionRef = ref<InstanceType<typeof ShortInput> | null>(null);
const answerRef = ref<InstanceType<typeof ShortInput> | null>(null);
const richEdit = ref<InstanceType<typeof RichEditor> | null>(null);

const tagOptions = computed(() =>
    (props.tagList ?? []).filter(t => t.id !== 0)
);

// tag_text is comma-separated text values — split on load, rejoin on save
const selectedTags = ref<string[]>(
    props.editTarget?.tag_text
        ? props.editTarget.tag_text.split(',').map(t => t.trim()).filter(Boolean)
        : []
);

const params = reactive<FaqItem>({
    id: props.editTarget?.id ?? undefined,
    question: props.editTarget?.question ?? '',
    answer: props.editTarget?.answer ?? '',
    content: props.editTarget?.content ?? '',
    tag_text: props.editTarget?.tag_text ?? '',
});

const validate = async (): Promise<boolean> => {
    let valid = true;
    for (const ref of [questionRef.value, answerRef.value]) {
        if (ref) {
            const result = await ref.validate();
            valid = valid && (result?.valid ?? false);
        }
    }
    return valid;
};

const save = async (): Promise<void> => {
    processing.value = true;
    try {
        const isValid = await validate();
        if (!isValid) {
            processing.value = false;
            return;
        }
        params.tag_text = selectedTags.value.join(',');
        await api.post('/faq_add_record', { ...params }, {
            toast: props.editTarget ? '編集しました。' : '作成しました。',
            loadingRef: processing,
        });
        emit('close', true);
    } catch {
        processing.value = false;
    }
};
</script>
