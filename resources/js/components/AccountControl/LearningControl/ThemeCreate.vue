<template>
    <div class="overlay" @mousedown="emit('closeModal', false)">                         
        <div class="chatCreate scrollable" @mousedown.stop>
            <div class="recordFormTitle theme-create__header">
                <p>{{ editTarget ? `テーマを編集する` : `新しいテーマを作成する`}}</p>
                <div class="m-close-button theme-create__close" @click="emit('closeModal', false)">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>   
            <div class="theme-create__body">
                <div class="si-box">
                    <ShortInput 
                        name="themeTitle" 
                        placeHolder="タイトルを入力（必須）" 
                        :rules="'required'"
                        customClass="full"
                        ref="themeTitle"
                        type="text"
                        v-model="title"
                    />
                    <span v-if="errors.title" class="form-error theme-create__error">{{ errors.title }}</span>
                </div>
                <ThemeStructureSection
                    v-model:portfolio="portfolio"
                    v-model:case-study="case_study"
                    :initial-portfolio-guidance="initialPortfolioGuidance"
                    :initial-episode-guidance="initialEpisodeGuidance"
                    :initial-title-guidance="initialTitleGuidance"
                    :errors="errors"
                    @portfolio-guidance-updated="handlePortfolioGuidanceUpdate"
                    @episode-guidance-updated="handleEpisodeGuidanceUpdate"
                    @title-guidance-updated="handleTitleGuidanceUpdate"
                />
                <div class="si-box">
                    <ItemSelector
                        v-model="selectedCategories"
                        place-holder="カテゴリー"
                        :options="categoryOptions"
                        label="name"
                        :multiple="true"
                        :clearable="true"
                        :close-on-select="false"
                    />
                </div>
                <div class="si-box">
                    <div class="switchLabel">
                        <p class="form-lbl theme-create__switch-label">アクティブ</p>
                    </div>
                

                    <div class="selectSwitchArea theme-create__switch">
                        <input v-model="active" type="checkbox" id="edit_all">
                        <label for="edit_all" class="cursor-pointer theme-create__toggle-label"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                        
                    </div>  
                    <p class="form-helper theme-create__helper">OFFのテーマは受講者画面に表示されません（下書き状態）。</p>
                </div>
                <div class="si-box">
                    <div class="switchLabel">
                        <p class="form-lbl theme-create__switch-label">アーカイブ</p>
                    </div>

                    <div class="selectSwitchArea theme-create__switch">
                        <input v-model="archive" type="checkbox" id="archive_theme">
                        <label for="archive_theme" class="cursor-pointer theme-create__toggle-label"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                    </div>
                    <p class="form-helper theme-create__helper">ONにすると管理画面には残りますが、受講者画面のテーマ選択には表示されません。</p>
                </div>
                <ThemeAccessSection
                    v-model:selected-form="selectedForm"
                    v-model:selected-positions="selectedPositions"
                    v-model:selected-members="selectedMembers"
                    :forms="forms"
                    :positions="positions"
                    :members="members"
                />
                <div class="si-box">
                    <LoaderButton @triggered="create" :loading="loader" content="作成する"/>
                </div>


            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import { useApi } from '@/composables/api';
import { useLearningApi } from '@/composables/learningApi';
import { useDialog } from '@/composables/dialog';
import type { LearningTheme, LearningThemeCategory } from '@/types/learning';
import type { User } from '@/interface/globalInterface';
import ThemeAccessSection, { type ThemeFormOption, type ThemePositionOption } from './Authoring/ThemeAccessSection.vue';
import ThemeStructureSection from './Authoring/ThemeStructureSection.vue';

interface ThemeFormErrors {
    title: string
    portfolioGuidance: string
    episodeGuidance: string
    titleGuidance: string
    structure: string
}

const props = defineProps<{
    editTarget?: LearningTheme | null
}>()
const emit = defineEmits<{
    closeModal: [refresh: boolean]
}>()
const title = ref(props.editTarget ? props.editTarget.title : '')
const loader = ref(false)
const active = ref(props.editTarget && props.editTarget.active ? true : false)
const archive = ref(props.editTarget && props.editTarget.archive ? true : false)
const portfolio = ref(props.editTarget?.portfolio === 1 ? true : false);
const case_study = ref(props.editTarget?.has_case_study === 1 ? true : false);
const forms = ref<ThemeFormOption[]>([])
const positions = ref<ThemePositionOption[]>([])
const categoryOptions = ref<LearningThemeCategory[]>([])
const selectedForm = ref<number | null>(props.editTarget?.custom_form_id ?? null)
const selectedPositions = ref<number[]>([])
const selectedMembers = ref<User[]>(props.editTarget?.access_members ?? [])
const selectedCategories = ref<number[]>(props.editTarget?.categories?.map(category => category.id) ?? [])
const api = useApi()
const learningApi = useLearningApi()
const { toast, ping } = useDialog()
const members = computed(() => {
    const list: User[] = []
    positions.value.forEach(position => {
        if(position.employees){
            position.employees.forEach(employee => {
                list.push(employee)
            })
        }
    })
    return list
})
const initialPortfolioGuidance = computed(() => {
    return props.editTarget && props.editTarget.guidance ? props.editTarget.guidance : ''
})
const initialEpisodeGuidance = computed(() => {
    return props.editTarget && props.editTarget.episode_guidance ? props.editTarget.episode_guidance : ''
})
const initialTitleGuidance = computed(() => {
    return props.editTarget && props.editTarget.title_guidance ? props.editTarget.title_guidance : ''
})
const portfolioGuidanceContent = ref(initialPortfolioGuidance.value || '')
const episodeGuidanceContent = ref(initialEpisodeGuidance.value || '')
const titleGuidanceContent = ref(initialTitleGuidance.value || '')
const errors = reactive<ThemeFormErrors>({
    title: '',
    portfolioGuidance: '',
    episodeGuidance: '',
    titleGuidance: '',
    structure: ''
})
watch(initialPortfolioGuidance, (value) => {
    portfolioGuidanceContent.value = value || ''
})
watch(initialEpisodeGuidance, (value) => {
    episodeGuidanceContent.value = value || ''
})
watch(initialTitleGuidance, (value) => {
    titleGuidanceContent.value = value || ''
})
watch(title, () => {
    errors.title = ''
})
watch(portfolio, (value) => {
    if(!value){
        errors.portfolioGuidance = ''
        errors.episodeGuidance = ''
        errors.titleGuidance = ''
    }
})
watch(selectedPositions, (val) => {
    if (val) {
        const selectedMembersSet = new Set<number>()
        val.forEach(positionId => {
            const position = positions.value.find(pos => pos.id === positionId)
            if (position && position.employees) {
                position.employees.forEach(emp => selectedMembersSet.add(emp.id))
            }
        })
        selectedMembers.value = members.value.filter(member => selectedMembersSet.has(member.id))
    } else {
        selectedMembers.value = []
    }
})
const handlePortfolioGuidanceUpdate = (html: string) => {
    portfolioGuidanceContent.value = html
    errors.portfolioGuidance = ''
}
const handleEpisodeGuidanceUpdate = (html: string) => {
    episodeGuidanceContent.value = html
    errors.episodeGuidance = ''
}
const handleTitleGuidanceUpdate = (html: string) => {
    titleGuidanceContent.value = html
    errors.titleGuidance = ''
}
const sanitizeHtml = (html: string) => {
    if(!html) return ''
    return html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, '').trim()
}
const resetErrors = () => {
    errors.title = ''
    errors.portfolioGuidance = ''
    errors.episodeGuidance = ''
    errors.titleGuidance = ''
    errors.structure = ''
}
const validateForm = () => {
    resetErrors()
    let valid = true
    if(!title.value || !title.value.trim()){
        errors.title = 'タイトルは必須です。'
        valid = false
    }
    if(portfolio.value){
        if(!portfolioGuidanceContent.value || !sanitizeHtml(portfolioGuidanceContent.value)){
            errors.portfolioGuidance = 'ポートフォリオ説明は必須です。'
            valid = false
        }
        if(!episodeGuidanceContent.value || !sanitizeHtml(episodeGuidanceContent.value)){
            errors.episodeGuidance = 'エピソード説明は必須です。'
            valid = false
        }
        if(!titleGuidanceContent.value || !sanitizeHtml(titleGuidanceContent.value)){
            errors.titleGuidance = 'タイトル説明は必須です。'
            valid = false
        }
    }
    if(!portfolio.value && !case_study.value){
        errors.structure = 'ポートフォリオまたはケーススタディのどちらかは必ずONにしてください。'
        valid = false
    }
    if(!valid){
        ping('入力内容を確認してください。')
    }
    return valid
}
const create = async() => {
    if(loader.value){
        return
    }
    if(!validateForm()){
        return
    }
    loader.value = true
    try{
        await learningApi.saveTheme({
            id: props.editTarget ? props.editTarget.id : null,
            params: {
                active: active.value,
                archive: archive.value,
                title: title.value,
                episode_guidance: portfolio.value ? episodeGuidanceContent.value : '',
                guidance: portfolio.value ? portfolioGuidanceContent.value : '',
                title_guidance: portfolio.value ? titleGuidanceContent.value : '',
                portfolio: portfolio.value,
                has_case_study: case_study.value,
                custom_form_id: selectedForm.value,
            },
            access_members: selectedMembers.value.map((member) => member.id),
            category_ids: selectedCategories.value,
        }, Boolean(props.editTarget))
        emit('closeModal', true)
    }catch(error){
        console.error(error)
        toast('保存に失敗しました。時間をおいて再度お試しください。')
    }finally{
        loader.value = false
    }

}
const getForms = async() => {
    const response = await api.get('/get_forms')
    forms.value = response ?? []
}
const getPositions = async() => {
    const response = await api.get('/get_members_by_position')
    positions.value = response ?? []
}
const getCategories = async() => {
    categoryOptions.value = await learningApi.getThemeCategories()
}
onMounted(() => {
    getForms()
    getPositions()
    getCategories()
})
</script>

<style scoped>
.theme-create__header{
    display: flex;
}

.theme-create__close{
    margin: auto 0 auto auto;
    position: unset;
}

.theme-create__body{
    background: inherit;
}

.theme-create__error{
    color: tomato;
    font-size: 11px;
}

.theme-create__switch-label{
    font-size: 14px;
    white-space: nowrap;
}

.theme-create__switch{
    display: flex;
    width: 100%;
}

.theme-create__toggle-label{
    min-width: 80px;
}

.theme-create__helper{
    color: gray;
    font-size: 12px;
    line-height: normal;
    margin-top: 5px;
}

.theme-create__section-title{
    font-size: 13px;
    margin-bottom: 15px;
}
</style>
