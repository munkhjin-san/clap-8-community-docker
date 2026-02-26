<template>
    <div class="flex flex-col gap-[14px]">
        <div v-if="!editorOpen" class="flex items-center justify-between gap-[10px]">
            <p class="text-[12px] text-[gray]">保存前に業務マニュアルを複数追加できます。</p>
            <button type="button" class="manual-btn" @click="openCreate">+ 追加</button>
        </div>

        <div v-if="!editorOpen && manuals.length" class="flex flex-col gap-[10px]">
            <div
                v-for="(manual, index) in manuals"
                :key="manual.id ? `manual-${manual.id}` : `manual-new-${index}`"
                class="manual-card"
            >
                <p class="text-[14px] text-[var(--primary-color)] break-all">{{ manual.title }}</p>
                <div class="flex items-center gap-[8px]">
                    <button type="button" class="manual-btn manual-btn--ghost" @click="openEdit(index)">編集</button>
                    <button type="button" class="manual-btn manual-btn--danger" @click="removeManual(index)">削除</button>
                </div>
            </div>
        </div>

        <Transition name="lessonShift">
            <div v-if="editorOpen" class="manual-editor">
                <div class="flex items-center justify-between gap-[10px]">
                    <p class="text-[14px] font-semibold">{{ editingIndex === null ? '業務マニュアルを追加' : '業務マニュアルを編集' }}</p>
                    <button type="button" class="manual-btn manual-btn--ghost" @click="closeEditor">閉じる</button>
                </div>
                <div class="si-box">
                    <ShortInput
                        v-model="draftTitle"
                        place-holder="タイトル"
                        type="text"
                    />
                </div>
                <div class="flex justify-end">
                    <button type="button" class="manual-btn" :disabled="!canSave" @click="saveManual">保存</button>
                </div>
            </div>
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import ShortInput from '@/components/Form/ShortInput.vue';
import { computed, ref, watch } from 'vue';

type ManualDraft = {
    id?: string;
    title: string;
}

const props = withDefaults(defineProps<{
    modelValue?: ManualDraft[]
}>(), {
    modelValue: () => []
})

const emit = defineEmits<{
    'update:modelValue': [items: ManualDraft[]]
    'editing-change': [flag: boolean]
}>()

const manuals = ref<ManualDraft[]>([])
const editorOpen = ref(false)
const draftTitle = ref('')
const editingIndex = ref<number | null>(null)

const normalizeManuals = (items: ManualDraft[]) => {
    return (items ?? [])
        .filter(item => (item?.title ?? '').trim() !== '')
        .map(item => ({ id: item.id, title: item.title.trim() }))
}

watch(
    () => props.modelValue,
    (items) => {
        manuals.value = normalizeManuals(items ?? [])
    },
    { immediate: true, deep: true }
)

watch(
    editorOpen,
    (flag) => emit('editing-change', flag),
    { immediate: true }
)

const canSave = computed(() => draftTitle.value.trim().length > 0)

const syncModel = () => {
    emit('update:modelValue', manuals.value.map(item => ({ ...item })))
}

const openCreate = () => {
    editingIndex.value = null
    draftTitle.value = ''
    editorOpen.value = true
}

const openEdit = (index: number) => {
    editingIndex.value = index
    draftTitle.value = manuals.value[index]?.title ?? ''
    editorOpen.value = true
}

const closeEditor = () => {
    editorOpen.value = false
    editingIndex.value = null
    draftTitle.value = ''
}

const saveManual = () => {
    const title = draftTitle.value.trim()
    if (!title) return

    if (editingIndex.value === null) {
        manuals.value.push({ title })
    } else {
        const current = manuals.value[editingIndex.value]
        if (current) {
            manuals.value[editingIndex.value] = {
                ...current,
                title,
            }
        }
    }

    syncModel()
    closeEditor()
}

const removeManual = (index: number) => {
    manuals.value.splice(index, 1)
    syncModel()
}
</script>
<style scoped>
.manual-card {
    border: 1px solid var(--normalBorder);
    background: var(--bg3);
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.manual-editor {
    border: 1px solid var(--normalBorder);
    background: var(--background-color);
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.manual-btn {
    border: 1px solid var(--primary-color);
    background: var(--primary-color);
    color: var(--background-color);
    font-size: 12px;
    padding: 6px 12px;
    cursor: pointer;
}
.manual-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.manual-btn--ghost {
    background: transparent;
    color: var(--primary-color);
}
.manual-btn--danger {
    border-color: tomato;
    color: tomato;
    background: transparent;
}
</style>
