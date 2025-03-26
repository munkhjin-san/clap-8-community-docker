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
                <!-- <ShortInput 
                    name="itemName"
                    place-holder="品名"
                    rules="required"
                    custom-class="full"
                    ref="itemNameRef"
                    type="text"
                    v-model="item_name"
                /> -->
                <AssetTypePicker 
                    placeHolder="品名"
                    v-model="item_name"
                />  
            </div>
            <div class="si-box">
                <LongInput 
                    name="specs"
                    place-holder="詳細（スペックなど）"
                    v-model="specs"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    name="modelNumber"
                    place-holder="型番号"
                    rules="required"
                    custom-class="full"
                    ref="modelNumberRef"
                    type="text"
                    v-model="model_number"
                />
            </div>
            <div class="si-box">
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
            </div>
            <div class="si-box">
                <MemberSelector 
                    place-holder="使用者"
                    v-model="selectedUser"
                    :multiple="false"
                    :options="choosAbleMembers"
                    rules="required"
                    ref="memberSelectRef"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    place-holder="価値"
                    v-model="value"
                    type="number"
                />
            </div>
            <div class="si-box">
                <p class="mb-[10px]">分類</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="classification">
                    <option v-for="(classification, index) in AssetClass" :value="classification.value">{{ classification.label }}</option>
                </select>
            </div>
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
import ItemSelector from '@/components/Form/ItemSelector.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { Asset } from '@/interface/assetInterface';
import { DialogMethods, User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface'
import { useAuthUserStore } from '@/store/auth';
import axios from 'axios';
import { inject, reactive, ref, useTemplateRef, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import AssetClass from 'assets/AssetClass.json'
import AssetStatus from 'assets/AssetStatus.json'
import LongInput from '../Form/LongInput.vue';
import AssetTypePicker from './AssetTypePicker.vue';
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

const { notify, info } = inject('dialog') as DialogMethods
const gl_number = ref('')
const item_name = ref(props.editData?.item_name ?? null)
const model_number = ref(props.editData?.model_number ?? '')
const classification = ref(props.editData?.classification ?? 1)
const value = ref(props.editData?.value ?? '')
const specs = ref(props.editData?.specs ?? '')
const status = ref(props.editData?.status ?? 1)
const glNumberRef = useTemplateRef('glNumberRef')
const selectedUser = ref<User | null>(props.editData?.current_user ? props.editData?.current_user : auth.user ? auth.user : null )
const projects = ref<number | null>(props.editData?.current_project?.id ?? null)
const memberSelectRef = useTemplateRef('memberSelectRef')
const projectSelectRef = useTemplateRef('projectSelectRef')
const assetTypes = ref([])
onMounted(() => {
    if (!props.editData) {
        projects.value = route.params.projectId ? Number(route.params.projectId) : null
    }
    if(props.editData) {
        gl_number.value = padNumber(props.editData?.id)?.toString() ?? ''
    }
})
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}
const createAsset = async() => {
    
    try {
        if (gl_exists.value === 0) {
            const glVal = await glNumberRef.value?.validate()
            if (!glVal?.valid) return
        }
        if(props.mode !== 'admin'){
            const [memberVal, projectVal] = await Promise.all([
                memberSelectRef.value?.validate(),
                projectSelectRef.value?.validate()
            ]);
            if (!memberVal?.valid || !projectVal?.valid) return
        }

        const params = {
            id: convertToHalfWidth(gl_number.value),
            params : {
                item_name: item_name.value,
                model_number: model_number.value,
                classification: classification.value,
                value: value.value,
                status: status.value,
                project_id: projects.value ?? null,
                user_id: selectedUser.value?.id ?? null,
                specs: specs.value

            }
        }
        await axios.post('/create_asset', params)
        info('作成しました。')
        emit('close', true)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}

const choosAbleMembers = computed(() => {
    return props.allMembers
});

const convertToHalfWidth = (num: string) => {
    return num.normalize("NFKC")
}
</script>