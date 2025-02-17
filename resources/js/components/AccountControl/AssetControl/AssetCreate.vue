<template>
    <Modal @close="emit('close')">
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
                <ShortInput 
                    name="itemName"
                    place-holder="品名"
                    rules="required"
                    custom-class="full"
                    ref="itemNameRef"
                    type="text"
                    v-model="item_name"
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
                    :options="choosAbleProjects"
                    rules="required"
                    ref="projectSelectRef"
                />
            </div>
            <div class="si-box">
                <MemberSelector 
                    place-holder="使用者"
                    v-model="users"
                    :multiple="true"
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
                    <option v-for="(classification, index) in classifications" :value="classification.value">{{ classification.label }}</option>
                </select>
            </div>
            <div class="si-box">
                <p class="mb-[10px]">ステータス</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="status">
                    <option v-for="(status, index) in statuses" :value="status.value">{{ status.label }}</option>
                </select>
            </div>
            <div class="si-box">
                <LoaderButton content="作成する" :loading="loading" @triggered="createAsset"/>
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
import { DialogMethods, User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface'
import axios from 'axios';
import { inject, reactive, ref, useTemplateRef, onMounted, computed } from 'vue';
const emit = defineEmits<{
    (e: 'close'): void
    (e: 'getAssets'): void
}>()
const props = defineProps([
    'statuses', 
    'classifications', 
    'editData',
    'allMembers',
    'allProjects'
])
const gl_exists = ref(0)
const loading = ref(false)
const padNumber = inject('padNumber') as Function
const { notify, info } = inject('dialog') as DialogMethods
const gl_number = ref(padNumber(props.editData?.id) ?? '')
const item_name = ref(props.editData?.item_name ?? '')
const model_number = ref(props.editData?.model_number ?? '')
const classification = ref(props.editData?.classification ?? 1)
const value = ref(props.editData?.value ?? '')
const status = ref(props.editData?.status ?? 1)
const glNumberRef = useTemplateRef('glNumberRef')
const users = ref<User[]>(props.editData?.users ?? [])
const projects = ref<Project[]>(props.editData?.projects ?? [])
const memberSelectRef = useTemplateRef('memberSelectRef')
const projectSelectRef = useTemplateRef('projectSelectRef')
const itemNameRef = useTemplateRef('itemNameRef')
const createAsset = async() => {
    
    try {
        if (gl_exists.value === 0) {
            const glVal = await glNumberRef.value?.validate()
            if (!glVal?.valid) return
        }
        const [memberVal, projectVal, nameVal] = await Promise.all([
            memberSelectRef.value?.validate(),
            projectSelectRef.value?.validate(),
            itemNameRef.value?.validate(),
        ]);
        if ((!memberVal.valid || !projectVal?.valid) && !nameVal?.valid) return
        
        const params = {
            id: convertToHalfWidth(gl_number.value),
            params : {
                item_name: item_name.value,
                model_number: model_number.value,
                classification: classification.value,
                value: value.value,
                status: status.value
            },
            user_ids: users.value?.map(ob => ob.id),
            project_ids: projects.value
        }
        await axios.post('/create_asset', params)
        info('作成しました。')
        emit('close')
        emit('getAssets')
    } catch (e) {
        notify('')
    }
}
const choosAbleProjects = computed(() => {
    if (!users.value?.length) return props.allProjects

    const user_ids = users.value.map(user => user.id)
    return props.allProjects.filter(project => 
        project.members.some(member => user_ids.includes(member.id))
    )
})


const choosAbleMembers = computed(() => {
    if (!projects.value?.length) return props.allMembers
})
const convertToHalfWidth = (num: string) => {
    return num.normalize("NFKC")
}
</script>