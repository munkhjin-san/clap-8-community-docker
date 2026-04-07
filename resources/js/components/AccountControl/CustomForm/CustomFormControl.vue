<template>
    <div class="admin-window">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <div class="h-full overflow-auto p-[20px]">
            <div class="sub-tab-container" style="margin-bottom: 20px;">
                <div @click="formStatus = 0" :class="['sub-tab-item', { 'selected-sub-tab': formStatus == 0}]">進行中</div>
                <div @click="formStatus = 1" :class="['sub-tab-item', { 'selected-sub-tab': formStatus == 1}]">完了</div>
            </div> 
            <div class="filter-panel">
                <div class="filter-grid">
                    <label class="filter-field">
                        <span class="filter-label">フォーム名</span>
                        <input
                            v-model="filters.keyword"
                            type="text"
                            class="custom-a-input !w-full !box-border"
                            placeholder="フォーム名で検索"
                            @keyup.enter="debouncedGetForms"
                        >
                    </label>
                    <label class="filter-field">
                        <span class="filter-label">フォーム種別</span>
                        <select v-model="filters.usage" class="custom-a-input !w-full !box-border">
                            <option value="">すべての種別</option>
                            <option value="general">通常フォーム</option>
                            <option value="public">公開フォーム</option>
                            <option value="project_creation">案件作成フォーム</option>
                        </select>
                    </label>
                    <label class="filter-field" v-if="filters.usage === 'project_creation'">
                        <span class="filter-label">プロジェクト種別</span>
                        <select
                            v-model="filters.project_type_id"
                            class="custom-a-input !w-full !box-border"
                            :disabled="filters.usage !== 'project_creation'"
                        >
                            <option value="">すべてのプロジェクト種別</option>
                            <option v-for="type in projectTypes" :key="type.id" :value="String(type.id)">
                                {{ type.label }}
                            </option>
                        </select>
                    </label>
                </div>
                <div class="filter-footer">
                    <p class="filter-summary">{{ forms.length }}件</p>
                    <!-- <div class="filter-actions">
                        <button type="button" class="filter-button" @click="getForms">
                            検索
                        </button>
                        <button type="button" class="filter-button" @click="resetFilters">
                            解除
                        </button>
                    </div> -->
                </div>
            </div>
            <div class="w-full flex flex-col gap-[20px]">
                <div @click="router.push({name: 'formDetail', params: {formId: form.id}})" v-for="form in forms" class="relative bg-[var(--background-color)] cursor-pointer p-[20px] ">
                    <div class="w-full flex items-center gap-[10px]">
                        <span>{{ form.title }}</span>
                        <span class="text-[11px] px-[8px] py-[2px] border border-solid border-[var(--calendarBorder)] rounded-full">
                            {{ formTypeLabel(form) }}
                        </span>
                    </div>
                    <div class="absolute right-[10px] top-[10px]">
                        <ItemMenu :items="[
                            {title: '編集', action: () => {editData = form; openModal = true}},
                            {title: '削除', action: () => {deleteForm(form.id)}},
                            {title: '再利用', action: () => {duplicateForm(form.id)}},
                            {title: formStatus == 0 ? '完了' : '再開', action: () => {updateFormStatus(form.id, formStatus == 0 ? 1 : 0)}},
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
                        <div
                            v-if="form.usage !== 'project_creation' && !form.is_public"
                            @click.stop="setViewUsers({title: 'フォーム対象者', users: form.users || []})"
                            class="flex text-[12px] items-center leading-normal"
                        >
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
                    <div class="mt-[10px] w-fit text-[12px] space-y-2">
                        <div v-if="form.usage === 'project_creation'">プロジェクト種別: {{ form.projectType?.label ?? form.project_type?.label ?? '未設定' }}</div>
                        <div v-else-if="form.is_public">公開設定: ログイン不要</div>
                        <template v-else>
                            <div>繰り返し設定: {{ form.repeat_setting == 1 ? '毎月' : '1回のみ' }}</div>
                            <div v-if="form.repeat_setting == 1" class="mt-[10px]">繰り返し日: {{ form.repeat_day }}日</div>
                        </template>
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
import type { ProjectType } from '@/interface/projectInterface';
import { computed, ref, watch } from 'vue';
import { onMounted } from 'vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import CustomFormCreate from './CustomFormCreate.vue';
import { useRoute, useRouter } from 'vue-router';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import Modal from '@/components/Global/Modal.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import { debounce } from '@/utils/tools';

const api = useApi()
let keywordSearchTimer: ReturnType<typeof setTimeout> | null = null
const viewUsers = ref<{title: string, users: CustomFormUser[]}>({title: '', users: []})
const route = useRoute()
const router = useRouter()
const forms = ref<CustomForm[]>([])
const projectTypes = ref<ProjectType[]>([])
const openModal = ref(false)
const editData = ref<CustomForm | null>(null)
const formStatus = ref(0)
const loading = ref(true)
const skipFilterWatch = ref(false)
const filters = ref({
    keyword: '',
    usage: '',
    project_type_id: '',
})
const selectedForm = computed(() => {
    const selectedId = route.params?.formId ? Number(route.params.formId) : null
    
    return selectedId ? forms.value.find( f => f.id == selectedId) ?? null : null 
})
const formTypeLabel = (form: CustomForm) => {
    if (form.usage === 'project_creation') {
        return 'プロジェクト作成'
    }

    return form.is_public ? '公開フォーム' : '通常'
}
const getProjectTypes = async() => {
    const data = await api.get('/project_types')
    projectTypes.value = Array.isArray(data) ? data as ProjectType[] : []
}
const getForms = async() => {
    loading.value = true
    try {
        const data = await api.get('/get_custom_forms', {
            status: formStatus.value,
            usage: filters.value.usage || undefined,
            project_type_id: filters.value.project_type_id || undefined,
            keyword: filters.value.keyword.trim() || undefined,
        })
        data && (forms.value = data as CustomForm[])
    } finally {
        loading.value = false
    }
}
const resetFilters = () => {
    skipFilterWatch.value = true
    if (keywordSearchTimer) {
        clearTimeout(keywordSearchTimer)
        keywordSearchTimer = null
    }
    filters.value = {
        keyword: '',
        usage: '',
        project_type_id: '',
    }
    getForms().finally(() => {
        skipFilterWatch.value = false
    })
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
    })
    data && getForms()
    
}
const updateFormStatus = async(id: number, status: number) => {
    const data = await api.post('/update_custom_form_status', {id: id, status: status}, {
        toast: status == 1 ? 'フォームを完了しました。' : 'フォームを再開しました。',
    })
    data && getForms()    
}
const setViewUsers = (payload: {title: string, users: CustomFormUser[]}) => {
    viewUsers.value = payload
}
watch(() => filters.value.usage, (usage) => {
    if (usage !== 'project_creation' && filters.value.project_type_id) {
        filters.value.project_type_id = ''
    }
})
watch(() => filters.value.keyword, () => {
    if (skipFilterWatch.value) {
        return
    }
    if (keywordSearchTimer) {
        clearTimeout(keywordSearchTimer)
    }
    keywordSearchTimer = setTimeout(() => {
        getForms()
    }, 300)
})
watch([formStatus, () => filters.value.usage, () => filters.value.project_type_id], () => {
    if (skipFilterWatch.value) {
        return
    }
    getForms()
})
const debouncedGetForms = debounce(() => {
    getForms()
}, 300)
onMounted(async() => {
    await getProjectTypes()
    getForms()
})
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
    .filter-panel{
        padding: 16px;
        margin-bottom: 20px;
        background: var(--background-color);
        border: 1px solid var(--calendarBorder);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .filter-grid{
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr);
        gap: 12px;
        align-items: end;
    }
    .filter-field{
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
    }
    .filter-label{
        font-size: 12px;
        line-height: 1.4;
        color: gray;
    }
    .filter-footer{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .filter-summary{
        font-size: 12px;
        color: gray;
    }
    .filter-actions{
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .filter-button{
        min-height: 36px;
        padding: 0 14px;
        border: 1px solid var(--normalBorder);
        background: transparent;
        font-size: 12px;
        cursor: pointer;
    }
    @media (max-width: 959px) {
        .c-button{
            height: 30px;
        }
        .primary-selection{
            padding: 0 15px;
        }
        .filter-grid{
            grid-template-columns: 1fr;
        }
        .filter-footer{
            flex-direction: column;
            align-items: stretch;
        }
        .filter-actions{
            width: 100%;
        }
        .filter-button{
            flex: 1 1 0;
        }
    }
</style>
