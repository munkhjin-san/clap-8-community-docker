<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>物品作成</p>
        </template>
        <template #content>
            <div>
                <p class="mb-[20px]">GL番号</p>                
                <div style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap;">
                    <div @click="gl_exists = 0" :class="['ch-selector', { chSelected: gl_exists == 0}]">あり</div>
                    <div @click="gl_exists = 1" :class="['ch-selector', { chSelected: gl_exists == 1}]">なし</div>
                </div>
            </div>
            <div class="si-box" v-if="gl_exists === 0">
                <div class="flex items-center">
                    <p>GLー</p>
                    <ShortInput 
                        name="glNumber" 
                        place-holder="番号" 
                        :rules="'number'"
                        ref="glNumberRef"
                        type="text"
                        v-model="gl_number"
                    />
                </div>
                
            </div>
            <div class="si-box">
                <AssetTypePicker 
                    placeHolder="物品名"
                    v-model="item_name"
                    rules="required"
                    ref="itemNameRef"
                    :options="tagOptions.map(tag => tag.title)"
                />  
            </div>
            <div class="si-box">
                <LongInput 
                    name="specs"
                    :place-holder="detailPlaceHolder"
                    v-model="specs"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    name="modelNumber"
                    :place-holder="numberRequiredNames.includes(item_name) ? `${item_name}番号` : `型番号`"
                    rules="required"
                    custom-class="full"
                    ref="modelNumberRef"
                    type="text"
                    v-model="model_number"
                />
            </div>
            <!-- <div class="si-box">
                <ItemSelector 
                    place-holder="使用プロジェクト"
                    v-model="projects"
                    :clearable="true"
                    label="name"
                    :closeOnSelect="true"
                    :reduce="option => option.id"
                    :options="allProjects"
                    :multiple="false"
                    rules="required"
                    ref="projectSelectRef"
                />
            </div> -->
            
            <div class="si-box">
                <div class="flex gap-1 text-sm mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="isExternal" type="radio" name="external-member" class="custom-f-radio" :value="false"/>
                        社内メンバー
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="isExternal" type="radio" name="external-member" class="custom-f-radio" :value="true"/>
                        社外メンバー
                    </label>
                </div>
                <MemberSelector 
                    v-if="!isExternal"
                    place-holder="使用者"
                    v-model="selectedUser"
                    :multiple="false"
                    :options="choosAbleMembers"
                    rules="required"
                    ref="memberSelectRef"
                />
                <ShortInput 
                    v-else
                    name="externalUser"
                    :place-holder="'社外使用者名'"
                    rules="required"
                    v-model="externalUser"
                    ref="externalUserNameRef"
                />
            </div>
            <!-- <div class="si-box">
                <ShortInput 
                    place-holder="価値"
                    v-model="value"
                    type="number"
                />
            </div> -->
            <!-- <div class="si-box">
                <p class="mb-[10px]">分類</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="classification">
                    <option v-for="(classification, index) in AssetClass" :value="classification.value">{{ classification.label }}</option>
                </select>
            </div>
            <p class="mt-[10px] text-[12px] leading-normal text-[gray]">消耗品（取得価格が10万円未満の物品）<br>資産(取得価格が10万円以上の物品)<br>重要資産(カード類、鍵)</p> -->
            <div class="si-box">
                <p class="mb-[10px]">ステータス</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="status">
                    <option v-for="(status, index) in AssetStatus" :value="status.value">{{ status.label }}</option>
                </select>
            </div>
            <div class="si-box" v-if="auth.activeUser.id === 610 || auth.activeUser.id === 608">
                <LoaderButton content="作成する" :loading="loading" @triggered="createAsset"/>
            </div>
            <div class="si-box" v-else>
                <LoaderButton content="申請する" :loading="loading" @triggered="createAsset"/>
            </div>
        </template>
    </Modal>    
</template>
<script setup lang="ts">
import MemberSelector from '@/components/Form/MemberSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { User } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
import { ref, useTemplateRef, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import AssetStatus from 'assets/AssetStatus.json'
import LongInput from '../Form/LongInput.vue';
import AssetTypePicker from './AssetTypePicker.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
const emit = defineEmits<{
    close:[flag: boolean]
}>()
const props = defineProps([
    'editData',
    'allMembers',
    'allProjects',
    'mode'
])
const route = useRoute()
const auth = useAuthUserStore()
const gl_exists = ref(0)
const loading = ref(false)

const numberRequiredNames = [
    '業務端末',
    'ガソリンカード',
    'レンタカーカード',
    'セキュリティカード',
]
const tagOptions = ref<{title: string, requiredData: string}[]>([
    {title: "ノートPC", requiredData: "メーカー・OS・バージョン"},
    {title: "デスクトップ", requiredData: "メーカー・OS・バージョン"},
    {title: "業務端末（本体）", requiredData: "メーカー"},
    {title: "SIM", requiredData: "電話番号"},
    {title: "事務所キー", requiredData: "キー番号"},
    {title: "ロッカーキー", requiredData: "キー番号"},
    {title: "ETCカード", requiredData: "カード番号"},
    {title: "ガソリンカード", requiredData: "カード番号・TFC番号"},
    {title: "レンタカーカード", requiredData: "カード番号"},
    {title: "ICカード", requiredData: "カード番号"},
    {title: "Times Business Card", requiredData: "カード番号"}
])

const detailPlaceHolder = computed(() => {
    const foundTag = tagOptions.value.find(tag => tag.title === item_name.value)
    return foundTag ? foundTag.requiredData : '詳細（スペックなど）'
})
const gl_number = ref('')
const item_name = ref(props.editData?.item_name ?? '')
const model_number = ref(props.editData?.model_number ?? '')
const classification = ref(props.editData?.classification ?? 2)
const value = ref(props.editData?.value ?? '')
const specs = ref(props.editData?.specs ?? '')
const status = ref(props.editData?.status ?? 1)
const externalUser = ref(props.editData?.external_user ?? '')
const isExternal = ref(props.editData?.external_user ? true : false)
const glNumberRef = useTemplateRef('glNumberRef')
const selectedUser = ref<User | null>(props.editData?.current_user ? props.editData?.current_user : auth.user ? auth.user : null )
// const projects = ref<number | null>(props.editData?.current_project?.id ?? null)
// const projectSelectRef = useTemplateRef('projectSelectRef')
const modelNumberRef = useTemplateRef('modelNumberRef')
const itemNameRef = useTemplateRef('itemNameRef')
const externalUserNameRef = useTemplateRef('externalUserNameRef')

const api = useApi()
const { ping } = useDialog()
onMounted(() => {
    // if (!props.editData) {
    //     projects.value = route.params.projectId ? Number(route.params.projectId) : null
    // }
    if(props.editData) {
        gl_number.value = padNumber(props.editData?.id)?.toString() ?? ''
    }
})
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}
const createAsset = async() => {   

    const validTargets = [modelNumberRef.value, itemNameRef.value]
    if (gl_exists.value === 0) {
        validTargets.push(glNumberRef.value)
    }        
    if (isExternal.value) {
        validTargets.push(externalUserNameRef.value)
    }

    let result = true
    for(const target of validTargets.filter(target => target !== null)){                
        const val = await target?.validate()
        const valid = val && val?.valid ? true : false
        result = result && valid
    }
    if (!result) {
        ping('必須項目を入力してください。')
        return
    }
    const params = {
        id: convertToHalfWidth(gl_number.value),
        params : {
            item_name: item_name.value,
            model_number: model_number.value,
            classification: classification.value,
            value: value.value,
            status: status.value,
            // project_id: projects.value ?? null,
            user_id: isExternal.value ? null : selectedUser.value?.id ?? null,
            specs: specs.value,
            external_user: isExternal.value ? externalUser.value : null,

        }
    }
    await api.post('/create_asset', params, { toast: '保存しました。' })
    emit('close', true)

}

const choosAbleMembers = computed(() => {
    return props.allMembers
});

const convertToHalfWidth = (num: string) => {
    return num.normalize("NFKC")
}
</script>