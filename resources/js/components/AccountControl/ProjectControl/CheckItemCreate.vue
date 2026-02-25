<template>
    <Modal @close="emit('close')">
        <template #title>
            <div class="text-[var(--primary-color)] font-semibold">
                {{ editData?.id ? '科目編集' : '新規科目' }}
            </div>
        </template>
        <template #content>
            <div>
                <div>
                    <AddableItemSelector 
                        :multiple="false"
                        place-holder="カテゴリー"
                        path="/check_item_categories"
                        :reduce="option => typeof option === 'string' ? option : (option?.name ?? option?.label ?? option?.title ?? option?.id ?? '')"
                        :close-on-select="true"
                        rules="required"
                        ref="itemSelectorRef"
                        v-model="selectedCategory"
                    />
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
import { ProjectCheckItem } from '@/interface/projectInterface';
import { ref, useTemplateRef } from 'vue';
const emit = defineEmits<{
    (e: 'close'): void
    (e: 'refresh'): void
}>()
const props = defineProps<{
    editData?: ProjectCheckItem | null
    selectedProjectId: number | null
}>()
const selectedCategory = ref<string | null>(props.editData?.category ?? null)
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
    if (!result) return
    
    const params = {
        id: props.editData?.id ?? null,
        projectId: props.selectedProjectId,
        category: selectedCategory.value,
        label: checkItemLabel.value
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
