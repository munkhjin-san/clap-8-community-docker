<template>
<Modal @close="emit('close', false)">
    <template #title>
        <p>{{ params.id ? `コンタクトを編集する` : `新しいコンタクトを作成する`}}</p>
    </template>
    <template #content>
        <div v-if="scanning" class="fixed w-full h-full bg-[#00000061] flex items-center justify-center z-[15] top-0 left-0">
            <div class="bg-[#00000085] flex gap-[15px] p-[15px]">
                <div id="loaderMini" style="width: fit-content;">
                    <div class="spinner-micro" style="border-color: transparent #fff #fff"></div>
                </div>
                <p class="text-white leading-normal">現在、企業データを検索中です。<br>ファイルや情報の容量によっては、数分かかる場合がありますので、<br>今しばらくお待ちください。</p>
            </div>
        </div>
        <div>
            <Cropper ref="cropperInstanceRef" mode="scan" @scan="getScan" @crop="cropComplete"/>
        </div>
              

        <div class="si-box">
            <p class="text-[14px] mb-[10px]">コンタクト種類（必須）</p>
            <div class="flex">
                <div class="max-w-[50%] relative">
                    <select v-model="params.contact_type_id" class="border border-solid border-[var(--primary-color)] h-[40px] m-h-[40px] px-[10px] text-[var(--primary-color)]">
                        <option v-for="type in types" :value="type.id">{{ type.title }}</option>
                    </select>
                    <p v-if="typeError" class="text-[12px] text-[tomato]">必須です。</p>
                </div>
                <div v-if="params.contact_type_id == -1" class="relative">
                    <input class="-ml-[1px] border border-solid border-[var(--primary-color)] h-[40px] m-h-[40px] px-[10px] text-[var(--primary-color)]"  v-model="params.pseudo_type" type="text"/>
                    <p v-if="typeInputError" class="text-[12px] text-[tomato]">必須です。</p>
                </div>              
            </div>
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
            <LongInput
                place-holder="詳細"
                v-model="params.description!"
            />
        </div>

        <div class="si-box">
            <RichEditor ref="richEditor" :initila-value="params.data" :key="editorKey"/>
        </div>
        <div v-if="cardUrl" class="si-box">
            <img :src="cardUrl" class="max-h-[250px]"/>
            <div class="absolute text-[14px] bottom-0 left-0 bg-[var(--background-color)] z-[5] p-[5px] w-fit">
                <label class="cursor-pointer">
                    <input :checked="true" ref="isSaveCard" type="checkbox" />
                    名称保存
                </label>
            </div>
        </div>
        <div class="si-box">
            <LoaderButton content="保存する" :loading="loading" @triggered="save"/>
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
import LongInput from'@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { ref } from 'vue';
import { useTemplateRef } from 'vue';
import Cropper from '@/components/Global/Cropper.vue';
import RichEditor from '@/components/Global/RichEditor.vue';
import {marked} from 'marked'
import DOMPurify from 'dompurify';
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
const editorKey = ref(0);
const validationTrigger = ref(0)
const api = useApi()
const { ping } = useDialog()
onMounted(() => {
    if(props.editData){
        Object.assign(params, props.editData)
        editorKey.value ++
    }
})
const types = computed(() => {
    const types = [...toRaw(props.contactTypes)]
    types.push({
        id: -1,
        title: 'その他（新規作成）'
    })
    return types
})
const typeError = computed(() => {
    return validationTrigger.value && (params.contact_type_id == undefined || params.contact_type_id == null)
})
const typeInputError = computed(() => {
    return params.contact_type_id == -1 && !params.pseudo_type
})
const save = async() => {
    validationTrigger.value++
    const validateTargets = [nameRef.value]
    const targets = validateTargets.filter(ob => ob !== null)
    let result = true
    for(const target of targets){            
        const val = await target?.validate() || {valid: false}
        result = result && val.valid
    }
    if (!result || typeError.value || typeInputError.value) return   


    let cardPath = ''
    if(isSaveCard.value?.checked && cardBlob.value){
        const form = new FormData
        form.append('image', cardBlob.value)
        cardPath = await api.post('/upload_name_card', form)
        params.card_path = cardPath
    }
    
    await api.post('/contact_item', params, {
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
        formData.append("image", blob);
        const data = await api.post("/scan_card", formData, {
            loadingRef: scanning,
        }, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });
        await cropComplete()
        const markedText = await marked(data.text)
        const saveText = DOMPurify.sanitize(markedText)
        params.data = saveText
        Object.assign(params, data.data)
        editorKey.value ++

    }
}
</script>