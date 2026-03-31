<template>
    <Modal @close="emit('close')">
        <template #title>
            <div class="text-[var(--primary-color)] font-semibold">
                {{ editData?.id ? 'テンプレート編集' : '新規テンプレート' }}
            </div>
        </template>
        <template #content>
            <div>
                <div>
                    <AddableItemSelector
                        :multiple="false"
                        place-holder="カテゴリー"
                        path="/check_item_categories"
                        :close-on-select="true"
                        :allow-custom="true"
                        rules="required"
                        ref="itemSelectorRef"
                        v-model="selectedCategory"
                    />
                </div>
                <div class="si-box">
                    <p class="text-[14px] mb-[10px]">親項目</p>
                    <select v-model="selectedParentId" class="custom-a-input">
                        <option :value="null">親なし</option>
                        <option v-for="parent in availableParents" :key="parent.id" :value="parent.id">
                            {{ parent.label }}
                        </option>
                    </select>
                </div>
                <div class="si-box">
                    <ShortInput
                        place-holder="項目"
                        rules="required"
                        ref="labelRef"
                        v-model="checkItemLabel"
                    />
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="saveItem" :loading="loading" content="保存する"/>
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import AddableItemSelector from '@/components/Form/AddableItemSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { useApi } from '@/composables/api';
import { ref, useTemplateRef } from 'vue';

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'refresh'): void
}>()
const props = defineProps<{
    editData?: any
    selectedProjectTypeId: number | null
    availableParents: { id: number; label: string }[]
}>()
const selectedCategory = ref<number | string | null>(
    props.editData?.category?.id ?? props.editData?.project_checkitem_category_id ?? props.editData?.category_label ?? null
)
const selectedParentId = ref<number | null>(props.editData?.parent_id ?? null)
const checkItemLabel = ref<string>(props.editData?.label ?? '')
const itemSelectorRef = useTemplateRef('itemSelectorRef')
const labelRef = useTemplateRef('labelRef')
const loading = ref(false)
const api = useApi()

const saveItem = async() => {
    const validateTargets = [itemSelectorRef.value, labelRef.value]
    const targets = validateTargets.filter(ob => ob !== null)
    let result = true
    for(const target of targets){
        const val = await target?.validate() || {valid: false}
        result = result && val.valid
    }
    if (!result || !props.selectedProjectTypeId) return

    const params = {
        id: props.editData?.id ?? null,
        projectTypeId: props.selectedProjectTypeId,
        category_id: typeof selectedCategory.value === 'number' ? selectedCategory.value : null,
        category: typeof selectedCategory.value === 'string' ? selectedCategory.value : '',
        label: checkItemLabel.value,
        parent_id: selectedParentId.value,
    }
    loading.value = true
    await api.post('/create_update_checkitem', params, {
        toast: '保存しました。'
    })
    loading.value = false
    emit('close')
    emit('refresh')
}
</script>
