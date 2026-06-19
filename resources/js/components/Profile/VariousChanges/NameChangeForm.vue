<template>
<div class="space-y-8">
    <ShortInput
        ref="reasonRef"
        v-model="params.reason"
        custom-class="full"
        name="reason"
        place-holder="氏名変更の事由"
        rules="required|max:1000"
    />
    <ShortInput
        ref="effectiveDateRef"
        v-model="params.effective_date"
        name="effective_date"
        place-holder="氏名変更適用日"
        rules="required"
        class="fit"
        type="date"
    />
    <div class="flex gap-8 under960:flex-col">
        <div class="flex-1">
            <ShortInput
                ref="lastNameRef"
                v-model="params.last_name"
                custom-class="full"
                name="last_name"
                place-holder="変更後の氏名（姓）"
                rules="required|max:100"
            />
        </div>
        <div class="flex-1">
            <ShortInput
                ref="firstNameRef"
                v-model="params.first_name"
                custom-class="full"
                name="first_name"
                place-holder="変更後の氏名（名）"
                rules="required|max:100"
            />
        </div>
    </div>
    <div class="flex gap-8 under960:flex-col">
        <div class="flex-1">
            <ShortInput
                ref="lastNameKanaRef"
                v-model="params.last_name_kana"
                custom-class="full"
                name="last_name_kana"
                place-holder="変更後の氏名（姓）（カナ）"
                rules="required|max:100"
            />
        </div>
        <div class="flex-1">
            <ShortInput
                ref="firstNameKanaRef"
                v-model="params.first_name_kana"
                custom-class="full"
                name="first_name_kana"
                place-holder="変更後の氏名（名）（カナ）"
                rules="required|max:100"
            />
        </div>       
    </div> 


    <div class="name-change-file">
        <FileUploader
            ref="residentCardFilesRef"
            v-model="params.resident_card_files"
            accept=".jpg,.jpeg,.png,.pdf"
            custom-place-holder="住民票※写し"
            path="/various_changes/name_change"
        />
        <p v-if="residentCardFileError" class="name-change-error">{{ residentCardFileError }}</p>
    </div>
    <div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="params.share_with_pm" class="custom-f-checkbox" />
            今回の変更をPMに共有済み。
        </label>
        <p v-if="shareWithPmError" class="name-change-error mt-1">{{ shareWithPmError }}</p>
    </div>
</div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import ShortInput from '@/components/Form/ShortInput.vue';

type UploadedFile = {
    id: number
    [key: string]: unknown
}

type NameChangePayload = {
    reason: string
    effective_date: string
    last_name: string
    first_name: string
    last_name_kana: string
    first_name_kana: string
    resident_card_files: UploadedFile[]
    share_with_pm: boolean
}

const params = reactive<NameChangePayload>({
    reason: '',
    effective_date: '',
    last_name: '',
    first_name: '',
    last_name_kana: '',
    first_name_kana: '',
    resident_card_files: [],
    share_with_pm: false,
})

const reasonRef = ref<InstanceType<typeof ShortInput> | null>(null)
const effectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const lastNameRef = ref<InstanceType<typeof ShortInput> | null>(null)
const firstNameRef = ref<InstanceType<typeof ShortInput> | null>(null)
const lastNameKanaRef = ref<InstanceType<typeof ShortInput> | null>(null)
const firstNameKanaRef = ref<InstanceType<typeof ShortInput> | null>(null)
const residentCardFilesRef = ref<InstanceType<typeof FileUploader> | null>(null)
const residentCardFileError = ref('')
const shareWithPmError = ref('')

const validate = async () => {
    const targets = [
        reasonRef.value,
        effectiveDateRef.value,
        lastNameRef.value,
        firstNameRef.value,
        lastNameKanaRef.value,
        firstNameKanaRef.value,
        residentCardFilesRef.value,
    ]

    let result = true
    for (const target of targets) {
        const validation = await target?.validate() || { valid: false }
        result = result && validation.valid
    }

    residentCardFileError.value = params.resident_card_files.length > 1 ? '住民票の写しは1ファイルのみ添付してください。' : ''
    shareWithPmError.value = params.share_with_pm ? '' : 'PMへの共有確認が必要です。'
    result = result && !residentCardFileError.value

    return result && !shareWithPmError.value
}

const getPayload = () => ({
    ...params,
    resident_card_file_ids: params.resident_card_files.map(file => file.id),
})

defineExpose({ validate, getPayload })
</script>

<style scoped>
.name-change-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.change-form-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.change-form-grid > :first-child {
    grid-column: 1 / -1;
}

.name-change-file {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.name-change-help {
    color: gray;
    font-size: 11px;
}

.name-change-error {
    color: tomato;
    font-size: 11px;
}

.name-change-selector {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
}

.name-change-selector-label {
    color: var(--primary-color);
    font-size: 13px;
    font-weight: 700;
}

@media screen and (max-width: 639px) {
    .change-form-grid {
        grid-template-columns: 1fr;
    }

    .name-change-selector {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
