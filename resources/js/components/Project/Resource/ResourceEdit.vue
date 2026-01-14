<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>給料手当数量編集</p>
        </template>
        <template #content>
            <div class="flex flex-col gap-3">
                <p>メンバー名</p>
                <p class="text-sm">{{ editData?.member }}</p>
            </div>
            <div class="si-box flex flex-col gap-3">
                <p>プロジェクト名</p>
                <p class="text-sm">{{ editData?.project }}</p>
            </div>
            <div class="si-box">
                <ShortInput 
                    place-holder="給料手当数量"
                    v-model="salaryQuantity"
                    type="number"
                    rules="required"
                    ref="quantityRef"
                />
            </div>
            <div class="si-box">
                <LoaderButton content="保存" :loading="saving" @triggered="updateResource"/>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { useApi } from '@/composables/api';
import { ref, useTemplateRef } from 'vue';
const emit = defineEmits<{
    (e: 'close'): void
    (e: 'reload'): void
}>()
type ResourceValue = {
  '給料手当数量': number
  '所定労働日数': number
  '給料手当出金': number
  'レコード番号': number
  '部門コード': string 
}
type EditResourceValue = ResourceValue & {
  project: string
  member: string
}
const props = defineProps<{
    editData: EditResourceValue | null
}>()
const api = useApi()
const salaryQuantity = ref<number>(props.editData?.['給料手当数量'] ?? 0)
const saving = ref(false)
const quantityRef = useTemplateRef('quantityRef')
const updateResource = async() => {
    const val = await quantityRef.value?.validate()
    if (!val?.valid) return
    const payload = {
        quantity: salaryQuantity.value,
        member: props.editData?.member,
        recordId: props.editData?.['レコード番号']
    }
    await api.post('/update_resource_kintone', payload, { toast: '保存しました。', loadingRef: saving })
    emit('close')
    emit('reload') 
}
</script>