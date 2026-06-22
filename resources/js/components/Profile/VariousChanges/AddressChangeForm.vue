<template>
<div class="space-y-8">
    <ShortInput
        ref="effectiveDateRef"
        v-model="params.effective_date"
        class="fit"
        name="effective_date"
        place-holder="住所変更適用日"
        rules="required"
        type="date"
    />

    <LongInput
        ref="addressRef"
        v-model="params.address"
        custom-class="full"
        name="address"
        place-holder="変更後の住所"
        rules="required"
    />

    <div class="address-change-file">
        <FileUploader
            ref="residentCardFilesRef"
            v-model="params.resident_card_files"
            accept=".jpg,.jpeg,.png,.pdf"
            custom-place-holder="住民票※写し"
            path="/various_changes/address_change"
        />
        <p v-if="residentCardFileError" class="address-change-error">{{ residentCardFileError }}</p>
    </div>

    <div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="params.share_with_pm" class="custom-f-checkbox" />
            今回の変更をPMに共有済み。
        </label>
        <p v-if="shareWithPmError" class="address-change-error mt-1">{{ shareWithPmError }}</p>
    </div>
</div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import LongInput from '@/components/Form/LongInput.vue';
import ShortInput from '@/components/Form/ShortInput.vue';

type UploadedFile = {
    id: number
    [key: string]: unknown
}

type AddressChangePayload = {
    effective_date: string
    address: string
    resident_card_files: UploadedFile[]
    share_with_pm: boolean
}

const params = reactive<AddressChangePayload>({
    effective_date: '',
    address: '',
    resident_card_files: [],
    share_with_pm: false,
})

const effectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const addressRef = ref<InstanceType<typeof LongInput> | null>(null)
const residentCardFilesRef = ref<InstanceType<typeof FileUploader> | null>(null)
const residentCardFileError = ref('')
const shareWithPmError = ref('')

const validate = async () => {
    const targets = [
        effectiveDateRef.value,
        addressRef.value,
        residentCardFilesRef.value,
    ]

    let result = true
    for (const target of targets) {
        const validation = await target?.validate() || { valid: false }
        result = result && validation.valid
    }

    residentCardFileError.value = params.resident_card_files.length > 1 ? '住民票の写しは1ファイルのみ添付してください。' : ''
    shareWithPmError.value = params.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return result && !residentCardFileError.value && !shareWithPmError.value
}

const getPayload = () => ({
    ...params,
    resident_card_file_ids: params.resident_card_files.map(file => file.id),
})

defineExpose({ validate, getPayload })
</script>

<style scoped>
.address-change-file {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.address-change-error {
    color: tomato;
    font-size: 11px;
}
</style>
