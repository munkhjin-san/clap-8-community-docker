<template>
    <div class="overlay" @mousedown="emit('closeModal', false)">                         
        <div class="chatCreate scrollable" @mousedown.stop>
            <div class="recordFormTitle" style="display:flex">
                <p>{{ editTarget ? `テーマを編集する` : `新しいテーマを作成する`}}</p>
                <div class="m-close-button" @click="emit('closeModal', false)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>   
            <div style="background: inherit;">
                <div class="si-box">
                    <ShortInput 
                        name="themeTitle" 
                        placeHolder="タイトルを入力（必須）" 
                        :rules="'required'"
                        :initialValue="editTarget ? editTarget.title : ''"
                        customClass="full"
                        ref="themeTitle"
                        type="text"
                        v-model="title"
                    />
                    <span v-if="errors.title" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.title }}</span>
                </div>
                <div class="si-box">
                    <div class="switchLabel">
                        <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">ポートフォリオ</p>
                    </div>
                

                    <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                        <input v-model="portfolio" type="checkbox" id="portfolio_create" :disabled="case_study">
                        <label for="portfolio_create" style="min-width: 80px;" :class="['cursor-pointer', {'disabled-toggle' : case_study}]"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                        
                    </div>  
                    <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">ONにすると受講者は全セクション完了後にポートフォリオ作成フローへ進みます。案内文が学習画面に表示されます。<br>※ポートフォリオが ON の場合、ケーススタディタイプは選択できません。</p>
                </div>
               
                <div class="si-box" v-if="portfolio">
                    <div style="font-size: 14px;margin-bottom: 15px;">ポートフォリオに関する説明</div>
                    <RichEditor 
                        ref="portfolioGuidance" 
                        :initilaValue="initialPortfolioGuidance"
                        @content-updated="handlePortfolioGuidanceUpdate"
                    />
                    <span v-if="errors.portfolioGuidance" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.portfolioGuidance }}</span>
                </div>
                <div class="si-box" v-if="portfolio">
                    <div style="font-size: 14px;margin-bottom: 15px;">エピソードに関する説明</div>
                    <RichEditor 
                        ref="episodeGuidance" 
                        :initilaValue="initialEpisodeGuidance"
                        @content-updated="handleEpisodeGuidanceUpdate"
                    />
                    <span v-if="errors.episodeGuidance" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.episodeGuidance }}</span>
                </div>
                <div class="si-box" v-if="portfolio">
                    <div style="font-size: 14px;margin-bottom: 15px;">タイトルに関する説明</div>
                    <RichEditor 
                        ref="titleGuidance" 
                        :initilaValue="initialTitleGuidance"
                        @content-updated="handleTitleGuidanceUpdate"
                    />
                    <span v-if="errors.titleGuidance" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.titleGuidance }}</span>
                </div>
            <div class="si-box">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">ケーススタディ</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                        <input v-model="case_study" type="checkbox" id="has_case_study" :disabled="portfolio">
                        <label for="has_case_study" style="min-width: 80px;" :class="['cursor-pointer', {'disabled-toggle' : portfolio}]"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                        
                </div>  
                    <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">ONにすると各レッスンで『ケーススタディ』タイプを選択でき、学習画面ではカード形式で表示されます。<br>※ケーススタディが ON の場合、ポートフォリオは選択できません。</p>
                    <span v-if="errors.structure" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.structure }}</span>
            </div>
                <div class="si-box">
                    <div class="switchLabel">
                        <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">アクティブ</p>
                    </div>
                

                    <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                        <input v-model="active" type="checkbox" id="edit_all">
                        <label for="edit_all" style="min-width: 80px;" class="cursor-pointer"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                        
                    </div>  
                    <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">OFFのテーマは受講者画面に表示されません（下書き状態）。</p>
                </div>
                <div class="si-box">
                    <div style="font-size: 13px;margin-bottom: 15px;">グループディスカッション日付（任意）</div>
                    <input 
                        class="taskDateTimePicker" 
                        :class="[{'date-color' : theme.dark }]" 
                        type="date" 
                        v-model="discussionDate"
                    />
                </div>
                <div class="si-box">
                    <ItemSelector 
                        placeHolder="アンケート"
                        :options="forms"
                        label="title"
                        :multiple="false"
                        :clearable="true"
                        :close-on-select="true"
                        v-model="selectedForm"
                    />
                    <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">テーマ完了後に受講者へ表示するカスタムアンケートを選択できます。</p>
                </div>
                <div style="text-align: center;margin-top: auto;padding: 20px 0;">
                    <LoaderButton @triggered="create" :loading="loader" content="作成する"/>
                </div>


            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import { useTheme } from '@/store/theme';
import RichEditor from '@/components/Global/RichEditor.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
const props = defineProps(['editTarget'])
const emit = defineEmits(['closeModal'])
const title = ref(props.editTarget ? props.editTarget.title : '')
const loader = ref(false)
const discussionDate = ref(props.editTarget ? props.editTarget.discussion_date : null)
const active = ref(props.editTarget && props.editTarget.active ? true : false)
const episodeGuidance = ref(null)
const portfolioGuidance = ref(null)
const titleGuidance = ref(null)
const theme = useTheme()
const portfolio = ref(props.editTarget?.portfolio === 1 ? true : false);
const case_study = ref(props.editTarget?.has_case_study === 1 ? true : false);
const forms = ref([])
const selectedForm = ref(props.editTarget?.custom_form_id ?? null)
const api = useApi()
const { toast, ping } = useDialog()
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
const errors = reactive({
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
const handlePortfolioGuidanceUpdate = (html) => {
    portfolioGuidanceContent.value = html
    errors.portfolioGuidance = ''
}
const handleEpisodeGuidanceUpdate = (html) => {
    episodeGuidanceContent.value = html
    errors.episodeGuidance = ''
}
const handleTitleGuidanceUpdate = (html) => {
    titleGuidanceContent.value = html
    errors.titleGuidance = ''
}
const sanitizeHtml = (html) => {
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
        await api.post('/create_learning_theme', {     
            id: props.editTarget ? props.editTarget.id : null,
            params: {
                active: active.value,
                discussion_date: discussionDate.value,
                title: title.value,
                episode_guidance: portfolio.value ? episodeGuidanceContent.value : '',
                guidance: portfolio.value ? portfolioGuidanceContent.value : '',
                title_guidance: portfolio.value ? titleGuidanceContent.value : '',
                portfolio: portfolio.value,
                has_case_study: case_study.value,
                custom_form_id: selectedForm.value
            }
    
        },{       
            toast: props.editTarget ? '編集しました。' :'保存しました。'       
        })
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
    forms.value = response
}
onMounted(() => {
    getForms()
})
</script>
