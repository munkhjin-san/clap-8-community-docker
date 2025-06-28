<template>
    <div class="admin-window">
        <div class="h-full w-full overflow-auto p-[20px]">
            <div class="w-[calc(100%-40px)] flex flex-col gap-[20px]">
                <div @click="router.push({name: 'formDetail', params: {formId: form.id}})" v-for="form in forms" class="relative bg-[var(--background-color)] cursor-pointer p-[20px] ">
                    <div class="w-full">{{ form.title }}</div>
                    <div class="absolute right-[10px] top-[10px]">
                        <ItemMenu :items="[
                            {title: '編集', action: () => {editData = form; openModal = true}},
                            {title: '削除', action: () => {deleteForm(form.id)}},
                            {title: '再利用', action: () => {duplicateForm(form.id)}},
                        ]"/>
                    </div>
                    <div class="mt-[20px] w-fit">
                        <div @click.stop="setViewUsers({title: 'フォーム管理者', users: form.admins || []})" class="flex text-[12px] items-center leading-normal">
                            <div>管理者 : </div>
                            <div class="flex ml-[5px]">
                                <UserPanel v-for="admin in form.admins?.slice(0, 3)" :user="admin" size="15" disable-instant/>
                                <p class="ml-[3px] mt-[3px]" v-if="form.admins && form.admins?.length > 3">{{ `...(${form.admins?.length}人)` }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-[10px] w-fit">
                        <div @click.stop="setViewUsers({title: 'フォーム対象者', users: form.users || []})" class="flex text-[12px] items-center leading-normal">
                            <div>対象者 : </div>
                            <div class="flex ml-[5px] items-center">
                                <div v-for="user in form.users?.slice(0, 3)" class="relative h-fit">
                                    <UserPanel :user="user" size="15" disable-instant/>
                                    <div v-if="user.is_answered" title="回答済み" class="completed-badge-large completed-badge-medium" style="background: green;"></div>
                                </div>                                
                                <p class="ml-[3px] mt-[3px]" v-if="form.users && form.users?.length > 3">{{ `...(${form.users?.length}人)` }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-[10px] w-fit text-[12px]">
                        <div>繰り返し設定: {{ form.repeat_setting == 1 ? '毎月' : '1回のみ' }}</div>
                        <div v-if="form.repeat_setting == 1" class="mt-[10px]">繰り返し日: {{ form.repeat_day }}日</div>
                    </div>
                </div>
            </div>

        </div>
        <FloatButton v-if="!selectedForm" @action="openModal = true">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>
        <CustomFormCreate v-if="openModal" @close="closeCreate" :edit-data="editData" range="all"/>

        <router-view v-slot="{ Component }">
            <transition name="slideFromRight">
                <component :is="Component" 
                    v-if="selectedForm"
                    :form="selectedForm"
                />
            </transition>
        </router-view>
        <Modal @close="setViewUsers({title: '', users: []})" v-if="viewUsers.users.length > 0">
            <template #title>
                <p>{{ viewUsers.title }}</p>
            </template>
            <template #content>
                <div class="flex flex-col">
                    <div v-for="user in viewUsers.users" class="flex items-center p-[10px] hover:bg-[var(--bg3)]">
                        <UserPanel :user="user" size="30" with-name disable-instant/>
                        <div v-if="user.is_answered !== undefined" class="c-button ml-auto px-[7px]" :style="{background: user.is_answered ? 'green' : 'black', cursor: 'not-allowed'}">{{ user.is_answered ? '回答済み' : '未回答' }}</div>
                    </div>                    
                </div>
            </template>
        </Modal>
    </div>
</template>
<script setup lang="ts">
import { CustomForm, CustomFormUser } from '@/interface/customFormInterface';
import { computed, inject, ref } from 'vue';
import { onMounted } from 'vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import CustomFormCreate from './CustomFormCreate.vue';
import { useRoute } from 'vue-router';
import router from '@/router';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import Modal from '@/components/Global/Modal.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';

const api = useApi()
onMounted(() => {
    getForms()
})

const viewUsers = ref<{title: string, users: CustomFormUser[]}>({title: '', users: []})
const route = useRoute()
const forms = ref<CustomForm[]>([])
const openModal = ref(false)
const editData = ref<CustomForm | null>(null)
const selectedForm = computed(() => {
    const selectedId = route.params?.formId ? Number(route.params.formId) : null
    
    return selectedId ? forms.value.find( f => f.id == selectedId) ?? null : null 
})
const getForms = async() => {
    const data = await api.get('/get_custom_forms')
    data && (forms.value = data as CustomForm[])
}
const closeCreate = (flag:boolean) => {
    editData.value = null
    openModal.value = false
    if(flag){
        getForms()
    }
}
const deleteForm = async(id: number) => {
    const data = await api.del('/delete_custom_form', {id: id}, {
        ask: '削除しますか？',
        toast: '削除しました。',
    })
    data && getForms()    
}
const duplicateForm = async(id: number) => {
    const data = await api.post('/duplicate_custom_form', {id: id}, {
        ask: '再利用しますか？',
        toast: '再利用しました。',
    })
    data && getForms()
    
}
const setViewUsers = (payload: {title: string, users: CustomFormUser[]}) => {
    viewUsers.value = payload
}
</script>
<style scoped>
    .c-button{
        color: #fff;
        background-color: #000;
        font-size: 12px;
        line-height: 1.5;
        white-space: nowrap;
        height: 25px;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: center;
        width: fit-content;
        position: relative;
        user-select: none;
    }


    .primary-selection{
        padding: 0 7px;
    }
    @media (max-width: 959px) {
        .c-button{
            height: 30px;
        }
        .primary-selection{
            padding: 0 15px;
        }
    }
</style>