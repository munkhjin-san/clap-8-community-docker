<template>
<Modal @close="emit('close', false)" :persist="scanning || loading">
    <template #title>
        <p>{{ params.id ? `コンタクトを編集する` : `新しいコンタクトを作成する`}}</p>
    </template>
    <template #content>
        <div v-if="scanning" class="fixed w-full h-full bg-[#00000061] flex items-center justify-center z-[15] top-0 left-0">
            <div class="bg-[#00000085] flex gap-[15px] p-[15px]">
                <div id="loaderMini" style="width: fit-content;">
                    <div class="spinner-micro" style="border-color: transparent #fff #fff"></div>
                </div>
                <p class="text-white leading-normal">名刺を読み取っています…<br>少しお待ちください。</p>
            </div>
        </div>

        <div class="edit-layout">
        <div class="edit-fields">
        <div class="si-box" v-if="!params.id">
            <p class="text-[14px] mb-[10px]">名刺から自動入力（任意）</p>
            <p class="text-[12px] text-[gray] leading-normal mb-[10px]">名刺（1枚）の写真をアップロードすると、氏名・会社名・部署などを自動で読み取ってフォームに反映します。企業情報は保存後にバックグラウンドで取得されます。手入力のみでも登録できます。<br>複数枚をまとめて登録する場合は、右下の ＋ ボタンから一括取り込みをご利用ください（1枚の画像に複数の名刺があっても自動で分割されます）。</p>
            <div class="border border-[var(--normalBorder)] overflow-hidden min-h-[170px]">
                <Cropper ref="cropperInstanceRef" mode="scan" @scan="getScan" @crop="cropComplete"/>
            </div>
        </div>

        <div class="si-box">
            <p class="text-[14px] mb-[10px]">コンタクト種類（必須・複数選択可）</p>
            <TypeChipSelect ref="typeChipRef" v-model="selectedTypes" :options="contactTypes" />
            <p v-if="typeError" class="text-[12px] text-[tomato] mt-[6px]">1つ以上選択してください。</p>
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="氏名（必須）"
                rules="required"
                ref="nameRef"
                type="text"
                v-model="params.name"
            />
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="会社名"
                type="text"
                v-model="params.company_name"
            />
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="部署"
                type="text"
                v-model="params.department"
            />
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="役職"
                type="text"
                v-model="params.position!"
            />
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="住所"
                type="text"
                v-model="params.address"
            />
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="メールアドレス"
                type="text"
                v-model="params.email"
            />
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="電話番号"
                type="text"
                v-model="params.phone"
            />
        </div>
        <div class="si-box">
            <ShortInput
                place-holder="FAX"
                type="text"
                v-model="params.fax"
            />
        </div>
        <div class="si-box">
            <RichEditor ref="richEditor" :initila-value="params.data" @content-updated="params.data = $event" :key="editorKey"/>
        </div>
        </div><!-- /edit-fields -->

        <div v-if="cardSrc && !cardError" class="edit-card">
            <div class="edit-card-inner">
                <p class="text-[12px] text-[gray] mb-[8px]">名刺（表面）・入力内容の確認用</p>
                <img
                    :src="cardSrc"
                    @click="zoomOpen = true"
                    @error="cardError = true"
                    class="w-full max-h-[280px] object-contain border border-[var(--formBorder)] bg-[var(--bg2)] cursor-zoom-in"
                />
                <p class="text-[11px] text-[gray] mt-[6px]">タップで拡大し、各項目が名刺と一致しているか確認できます。</p>
                <label v-if="cardBlob" class="flex items-center gap-[6px] mt-[10px] text-[13px] cursor-pointer">
                    <input :checked="true" ref="isSaveCard" type="checkbox" />
                    名刺画像を保存する
                </label>
            </div>
        </div>
        </div><!-- /edit-layout -->

        <div class="si-box">
            <LoaderButton content="保存する" :loading="loading" @triggered="save"/>
        </div>

        <div v-if="zoomOpen" @click="zoomOpen = false" class="fixed top-0 left-0 w-full h-full z-[9999] bg-[#000000d9] flex items-center justify-center p-[24px] cursor-zoom-out">
            <img :src="cardSrc" class="max-w-full max-h-full object-contain"/>
        </div>
    </template>
</Modal>
</template>
<script setup lang="ts">
import { computed, inject, reactive, toRaw } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import { ContactRecord, ContactType } from '@/interface/contactInterface';
import { onMounted } from 'vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { ref } from 'vue';
import { useTemplateRef } from 'vue';
import Cropper from '@/components/Global/Cropper.vue';
import RichEditor from '@/components/Global/RichEditor.vue';
import TypeChipSelect from './Filters/TypeChipSelect.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';

interface Props {
  editData: ContactRecord | null
  contactTypes: ContactType[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
    close: [flag: boolean]
}>()


const scanning = ref(false)
const params = reactive<Partial<ContactRecord>>({})
const loading = ref(false)
const nameRef = useTemplateRef('nameRef')
const isSaveCard = useTemplateRef('isSaveCard')
const cropperInstanceRef = ref<InstanceType<typeof Cropper> | null>(null)
const cardUrl = ref('')
const cardBlob = ref<Blob | null>(null)
const cardError = ref(false)
const zoomOpen = ref(false)
// Card shown in the edit form for OCR verification: a freshly scanned blob takes
// priority, otherwise the saved card image (edit flow) via /cdn.
const cardSrc = computed(() => cardUrl.value || (params.card_path ? `/cdn/${params.card_path}` : ''))
const editorKey = ref(0);
const validationTrigger = ref(0)
const api = useApi()
const { ping } = useDialog()

const selectedTypes = ref<string[]>([])
const typeChipRef = useTemplateRef<{ commit: () => void }>('typeChipRef')

onMounted(() => {
    if(props.editData){
        Object.assign(params, props.editData)
        selectedTypes.value = (props.editData.types ?? []).map(t => t.title)
        editorKey.value ++
    }
})
const typeError = computed(() => {
    return !!validationTrigger.value && selectedTypes.value.length === 0
})
const save = async() => {
    validationTrigger.value++
    // Commit a type still sitting in the chip input that wasn't Enter/selected.
    typeChipRef.value?.commit()

    const validateTargets = [nameRef.value]
    const targets = validateTargets.filter(ob => ob !== null)
    let result = true
    for(const target of targets){
        const val = await target?.validate() || {valid: false}
        result = result && val.valid
    }
    if (!result || typeError.value) return

    let cardPath = ''
    if(isSaveCard.value?.checked && cardBlob.value){
        const form = new FormData
        form.append('image', cardBlob.value)
        cardPath = await api.post('/upload_name_card', form)
        params.card_path = cardPath
    }

    await api.post('/contact_item', { ...toRaw(params), types: selectedTypes.value }, {
        toast: '保存しました。',
        loadingRef: loading,
    })
    emit('close', true)
}
const cropComplete = async() => {
    if (cropperInstanceRef.value ){
        scanning.value = true
        const { blob, source } = await cropperInstanceRef.value.complete();
        if (!blob || !source) {
            ping('エラーが発生しました。')
            scanning.value = false
            return;
        }
        cardUrl.value = URL.createObjectURL(blob);
        cardBlob.value = blob
        cropperInstanceRef.value.destroy()
        scanning.value = false
        return true
    }    
    return
}

const getScan = async() => {
    if (cropperInstanceRef.value && !scanning.value) {
        const { blob, source } = await cropperInstanceRef.value.complete();
        if (!blob || !source) {
            ping('エラーが発生しました。')
            return;
        }

        const formData = new FormData();
        formData.append("image", blob, 'card.png');
        // Real-time OCR only — returns the basic contact fields fast. Company
        // enrichment happens in the background after the record is saved.
        const data = await api.post("/scan_card", formData, {
            loadingRef: scanning,
        }, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });
        await cropComplete()
        if (data?.data) {
            Object.assign(params, data.data)
        }
    }
}
</script>

<style scoped>
/* Tailwind preflight is disabled app-wide, so border-width utilities need an
   explicit border-style to render. */
[class~="border"],
[class~="border-2"] { border-style: solid; }
[class~="border-t"] { border-top-style: solid; }
[class~="border-b"] { border-bottom-style: solid; }
[class*="border"] { box-sizing: border-box !important; }

/* Fields + sticky card panel. align-items:stretch lets .edit-card span the row
   height so the sticky inner card has room to travel while the fields scroll. */
.edit-layout { display: flex; gap: 20px; align-items: stretch; }
.edit-fields { flex: 1 1 auto; min-width: 0; }
.edit-card { flex: 0 0 240px; }
.edit-card-inner { position: sticky; top: 80px; }
@media screen and (max-width: 768px) {
    .edit-layout { flex-direction: column; }
    .edit-card { order: -1; flex-basis: auto; }
    .edit-card-inner { position: static; }
}
</style>