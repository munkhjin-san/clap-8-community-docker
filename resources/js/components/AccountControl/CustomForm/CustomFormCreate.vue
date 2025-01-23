
<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>{{ params?.id ? `アンケートを編集する` : `アンケートを作成する`}}</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput 
                    name="titleRef" 
                    placeHolder="タイトルを入力（必須）" 
                    :rules="'required'"
                    customClass="full"
                    ref="titleRef"
                    type="text"
                    v-model="params.title"
                />                
            </div>
            <div class="si-box">
                <MemberSelector 
                    :initialValue="params.admins" 
                    ref="adminSelectorRef"
                    placeHolder="管理者"
                    name="admins"
                    path="get_authorized_users"
                    :multiple="true"
                    v-model="params.admins"
                />
                <span class="text-[gray] text-[12px]">※フォームの回答は管理者のみ閲覧可能です。「システム管理者含む」</span>
            </div>
            <GroupSelector v-model="params.users"/>
            <div class="si-box">
                <MemberSelector 
                    :initialValue="params.users" 
                    ref="userSelectorRef"
                    placeHolder="対象者"
                    name="users"
                    path="board_possible_users"
                    :multiple="true"
                    v-model="params.users"
                />
                <span class="text-[gray] text-[12px]">※フォームのURLはどなたでもアクセス可能ですが、回答は対象者のみ必須となります。</span>
            </div>
            <div class="si-box">
                <RichEditor ref="richEdit" :initila-value="editData ? editData.description : ''"/>
                <!-- <LongInput
                    :initialValue="params.description"  
                    ref="descriptionRef"
                    placeHolder="説明"
                    name="description"
                    v-model="params.description!"
                />  -->
            </div>
            <div class="si-box">                
                <div ref="sortParent" class="flex flex-col gap-[30px]">
                    <div :key="block.id" v-for="(block, index) in params.blocks">
                        <div class="bg-[var(--bg3)] relative">
                            <div class="flex items-center h-[50px] px-[5px]">
                                <div class="handler flex items-center justify-center gap-[2px] w-[30px] h-[30px] cursor-grab">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="min-width: 3px;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="min-width: 3px;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                </div>
                                <div class="text-[12px]">{{ blockTypes.find( t => t.value == block.type)?.label }}</div>
                                <div @click="removeItem(block.id)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center ml-auto">
                                    <CloseIcon/>
                                </div>
                            </div>
                            
                            <div @click.stop @mousedown.stop class="px-[15px] pb-[15px]">
                                <CustomCheckbox 
                                    v-if="block.type == 'checkbox'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomRadio
                                    v-else-if="block.type == 'radio'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomSelect
                                    v-else-if="block.type == 'select'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomSingleText
                                    v-else-if="block.type == 'singletext' || block.type == 'date' || block.type == 'time'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomMultiText
                                    v-else-if="block.type == 'multitext'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                            </div>
                        </div>
                        
                    </div>
                    
                </div>
                <div class="text-[12px] flex flex-col items-center overflow-hidden whitespace-nowrap mt-[30px]">
                    <div @click.stop="menu.setMenu({parent: 'initial-plus'})" class="w-[30px] h-[30px] flex items-center justify-center min-w-[30px] cursor-pointer">
                        <div class="flex items-center gap-[5px]">
                            <AddIcon size="15"/>
                            <div>項目追加</div>
                        </div>
                    </div>                    
                    <div v-if="menu.parent == 'initial-plus'" id="initial-plus" class="flex gap-[10px] mt-[15px]">
                        <button v-for="type in blockTypes" :key="type.value" @click="addBlock(type.value, params.blocks.length)" class="px-[5px] py-[5px] bg-[var(--primary-color)] text-[var(--background-color)]">{{ type.label }}</button>
                    </div>
                </div>

            </div>
            <div class="si-box">
                <LoaderButton content="保存する" :loading="sending" @triggered="saveForm"/>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import { CustomForm, CustomFormBlock, CustomFormBlockType } from '@/interface/customFormInterface';
import { inject, nextTick, onMounted, reactive, ref, useTemplateRef } from 'vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import CustomCheckbox from '@/components/Form/CustomElements/CustomCheckbox.vue'
import { useMenuStore } from '@/store/menu';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import CustomRadio from '@/components/Form/CustomElements/CustomRadio.vue';
import CustomSingleText from '@/components/Form/CustomElements/CustomSingleText.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import CustomMultiText from '@/components/Form/CustomElements/CustomMultiText.vue';
import CustomSelect from '@/components/Form/CustomElements/CustomSelect.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import axios from 'axios';
import { DialogMethods } from '@/interface/globalInterface';
import { DialogKey } from '@/interface/keys';
import { useSortable, moveArrayElement } from '@vueuse/integrations/useSortable'
import RichEditor from '@/components/Global/RichEditor.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import GroupSelector from '@/components/Form/GroupSelector.vue';
const props = defineProps<{
    editData: CustomForm | null
}>()
const emit = defineEmits<{
    close: [flag: boolean]
}>()
const richEdit = ref<typeof RichEditor | null>(null)
const { confirm, info, notify } = inject('dialog') as DialogMethods;

const blockTypes:{label:string, value: CustomFormBlockType}[] = [
    {label: 'チェックボックス', value: 'checkbox'}, 
    {label: 'ラジオボタン', value: 'radio'}, 
    {label: 'ドロップダウン', value: 'select'},
    {label: '短文', value: 'singletext'}, 
    {label: '長文', value: 'multitext'},
    {label: '日付', value: 'date'},
    {label: '時間', value: 'time'},
]
const menu = useMenuStore()
const blockMenuIndex = ref<number | null>(null)
const sending = ref(false)
const params = reactive<CustomForm>({
    id: -1,
    title: '',
    description: '',
    blocks: [],
    users: [],
    admins: [],
})
const sortParent = useTemplateRef('sortParent')

onMounted(() => {
    if(props.editData && props.editData?.id){
        Object.assign(params, props.editData)
    }
    console.log('desc',params.description)

})

useSortable(sortParent, params.blocks, {
    animation: 150,
    handle: '.handler',
    onUpdate: (e) => {
            console.log(e)
        // do something
        moveArrayElement(params.blocks, e.oldIndex, e.newIndex, e)
        // nextTick required here as moveArrayElement is executed in a microtask
        // so we need to wait until the next tick until that is finished.
        nextTick(() => {
        /* do something */
        })
    }
})
const addBlock = (type:CustomFormBlockType, index: number) => {
    const id = -(Math.floor(100000 + Math.random() * 900000))
    const item:CustomFormBlock = {
        type: type,
        elements: [],
        id: id,
        question: '',
        is_required: false,
        placeholder: '', 
    }
    if(!params.blocks){
        params.blocks = []
    }
    params.blocks.splice(index + 1, 0, item);
    menu.close()
}

const removedItems = ref<number[]>([])

const removeItem = (id: number) => {
    if(params?.blocks && params.blocks.length){
        const index = params.blocks?.findIndex( b => b.id == id)
        if(index !== undefined && index > -1){
            removedItems.value.push(params.blocks[index].id)
            params.blocks.splice(index, 1)
        }
    }
}

const saveForm = async() => {
    console.log(params)
    const desc = richEdit.value ? richEdit.value?.editor.getHTML() : null
    params.description = desc
    params.blocks.forEach((block, index) => {
        block.order_number = index + 1
    })
    
    try {
        await axios.post('/save_custom_form', {
            ...params,
            removed_items: removedItems.value
        })
        info('保存しました。')
        emit('close', true)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }   
}
</script>

