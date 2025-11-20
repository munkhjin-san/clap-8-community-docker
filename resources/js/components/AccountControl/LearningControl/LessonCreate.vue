<template>
    <div class="overlay" @mousedown="closeModal(false, null)">                         
        <div class="chatCreate" @mousedown.stop style="overflow: hidden auto;">     
            <div class="recordFormTitle" style="display:flex">
                <p>{{ editTarget ? `トピックを編集する` : `新しいトピックを作成する`}}</p>
                <div class="m-close-button" @click="closeModal(false, null)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>      
            
            <div class="si-box">
                <ShortInput 
                    name="lessonTitle" 
                    placeHolder="タイトルを入力（必須）" 
                    :rules="'required'"
                    customClass="full"
                    ref="lessonTitle"
                    type="text"
                    v-model="title"
                />
                <span v-if="errors.title" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.title }}</span>
            </div>
            <div class="si-box">
                <div style="font-size: 14px;margin-bottom: 15px;">基礎知識の内容</div>
                <RichEditor 
                    ref="richEdit" 
                    :initilaValue="initialValue"
                    @content-updated="handleContentUpdated"
                />
                <span v-if="errors.content" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.content }}</span>
            </div>
            <div class="si-box" style="height: 70%;">
                <div>
                    <div v-for="priority in priorities" style="display: flex;align-items: center;padding: 5px 0;">
                        <input class="fish-eye" v-model="selectedPriority" type="radio" :id="priority.value" name="answer" :value="priority.value" >
                        <label style="margin-left:10px;cursor:pointer" :for="priority.value">{{priority.content}}</label>
                    </div>
                    <span v-if="errors.priority" class="form-error" style="font-size: 11px;color:tomato;">{{ errors.priority }}</span>
                    <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">
                        「ヘッダー」はレッスンページ上部の自由記述エリア、「セクション」は下部のカード一覧として表示されます。
                    </p>
                </div>
            </div>
            <div class="si-box" v-if="has_case_study && !isHeader && selectedPriority !== null">
                <div style="font-size: 14px;margin-bottom: 15px;">タイプ</div>
                <OptionSelector 
                    :initialValue="material_type"
                    :options="['基礎知識', 'ケーススタディ']"
                    v-model="material_type"
                    unit=""
                />
                <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">
                    {{ materialTypeDescription }}
                </p>
            </div>
            <div class="si-box" v-if="isHeader">
                <p class="form-helper" style="font-size: 12px;color: gray;margin: 0;">
                    ヘッダーはイントロダクションとして表示されるため、理解依頼・質問依頼・理解チェックは利用できません。
                </p>
            </div>
            <div class="si-box" v-if="!isHeader && !has_case_study">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">「理解」依頼</p>
                </div>
            

                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input v-model="has_understand" type="checkbox" id="for_understand" :disabled="has_question">
                    <label for="for_understand" style="min-width: 80px;" :class="['cursor-pointer', {'disabled-toggle' : has_question}]"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                </div>  
                <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">ONにすると受講者へ「理解したか」を回答してもらい、完了しない限り次の要素に進めません。</p>
            </div>
            <div class="si-box" v-if="!isHeader">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">「質問」依頼</p>
                </div>
            

                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input v-model="has_question" type="checkbox" id="for_question" :disabled="has_understand">
                    <label for="for_question" style="min-width: 80px;" :class="['cursor-pointer', {'disabled-toggle' : has_understand}]"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                </div>  
                <p class="form-helper" style="font-size: 12px;color: gray;margin-top: 5px;">ONにすると受講者へ質問投稿を求めるタスクが表示されます。理解依頼と同時に設定することはできません。</p>
            </div>
            
            
            <!-- <div class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', {'form-title-active' : hasFeedBack}]">「理解」依頼</p>
                </div>
                <div v-if="!editTarget || (editTarget && editTarget.award_entry == 0)" class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input type="checkbox" id="switchEntrySelect" v-model="hasFeedBack">
                    <label for="switchEntrySelect" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                    <div @click="hasFeedBack = !hasFeedBack" class="switch-toggle cursor-pointer">
                    </div>
                </div>
            </div> -->
  
                    
                    
            <div class="si-box">
                <LoaderButton @triggered="createSend" :loading="processing" :content="'保存する'"/>
            </div>               
       
        </div>
    </div>      
</template>

<script setup>      
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue'
import RichEditor from '../../Global/RichEditor.vue';
import { computed, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import OptionSelector from '@/components/Form/OptionSelector.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
    const priorities = [
        {value: 0, content: 'ヘッダー'},
        {value: 1, content: 'セクション'},
    ]
    const route = useRoute()
    const props = defineProps(['editTarget', 'has_case_study'])
    const emit = defineEmits(['createFinish'])
    const processing = ref(false)
    const hasFeedBack =  ref(props.editTarget && props.editTarget.has_feedback ? props.editTarget.has_feedback : false)
    const has_question = ref(props.editTarget?.has_question === 1 ? true : false)
    const has_understand = ref(props.editTarget?.has_understand === 0 ? false : props.has_case_study ? false : true)
    const title = ref(props.editTarget && props.editTarget.title ? props.editTarget.title : '')
    const richEdit = ref(null)
   
    const material_type = ref(props.editTarget?.material_type ?? '基礎知識')
    const selectedPriority = ref(props.editTarget ? props.editTarget.priority : null)
    const isHeader = computed(() => selectedPriority.value === 0)
    const api = useApi()
    const { toast, ping } = useDialog()
    const initialValue = computed(() => {
        return props.editTarget && props.editTarget.content ? props.editTarget.content : ''
    })
    const richContent = ref(initialValue.value || '')
    const errors = reactive({
        title: '',
        content: '',
        priority: ''
    })
    watch(initialValue, (value) => {
        richContent.value = value || ''
    })
    watch(title, () => {
        errors.title = ''
    })
    watch(selectedPriority, () => {
        errors.priority = ''
    })
    const cachedSectionSettings = ref({
        hasUnderstand: has_understand.value,
        hasQuestion: has_question.value,
        materialType: material_type.value
    })
    const materialTypeDescription = computed(() => {
        if(material_type.value === 'ケーススタディ'){
            return 'ケーススタディにすると、学習画面ではカードとして表示され、基礎知識を完了すると順次開放されます。\n※基礎知識のセクションがないテーマでは、ケーススタディは最初から受講できます。'
        }
        return '基礎知識にすると通常のセクションとして表示され、すぐに受講可能になります。'
    })
    watch([has_understand, has_question, material_type], () => {
        if(isHeader.value) return
        cachedSectionSettings.value = {
            hasUnderstand: has_understand.value,
            hasQuestion: has_question.value,
            materialType: material_type.value
        }
    })
    watch(isHeader, (header) => {
        if(header){
            has_understand.value = false
            has_question.value = false
            material_type.value = '基礎知識'
        }else{
            has_understand.value = cachedSectionSettings.value.hasUnderstand ?? true
            has_question.value = cachedSectionSettings.value.hasQuestion ?? false
            material_type.value = cachedSectionSettings.value.materialType ?? '基礎知識'
        }
    }, { immediate: true })
    
    const handleContentUpdated = (html) => {
        richContent.value = html
        errors.content = ''
    }
    const sanitizeHtml = (html) => {
        if(!html) return ''
        return html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, '').trim()
    }
    const resetErrors = () => {
        errors.title = ''
        errors.content = ''
        errors.priority = ''
    }
    const validateForm = () => {
        resetErrors()
        let valid = true
        if(!title.value || !title.value.trim()){
            errors.title = 'タイトルは必須です。'
            valid = false
        }
        if(!richContent.value || !sanitizeHtml(richContent.value)){
            errors.content = 'コンテンツは必須です。'
            valid = false
        }
        if(selectedPriority.value === null || selectedPriority.value === undefined){
            errors.priority = '優先度を選択してください。'
            valid = false
        }
        if(!valid){
            ping('入力内容を確認してください。')
        }
        return valid
    }
    const createSend = async() => {
        if(processing.value){
            return
        }
        if(!validateForm()){
            return
        }
        processing.value = true
        const params = {
            id: props.editTarget?.id,
            params: {
                lesson_theme_id: route.params.themeId,
                title: title.value,
                content: richContent.value,
                content_detailed: props.editTarget ? props.editTarget.content_detailed : null,
                prompt_id: props.editTarget?.prompt_id,
                has_feedback: hasFeedBack.value,
                priority: selectedPriority.value,
                has_question: has_question.value,
                has_understand: has_understand.value,
                material_type: material_type.value
            }
            
        }
        try{
            const response = await api.post('/lesson_add_record', params, {
                toast: props.editTarget ? '編集しました。' :'保存しました。'
            })
            closeModal(true, response.id)     
        }catch(error){
            console.error(error)
            toast('保存に失敗しました。時間をおいて再度お試しください。')
        }finally{
            processing.value = false
        }
    }
    const closeModal = (flag, id) => {
        processing.value = false
        emit('createFinish',flag, id);              
    }     
  
    
</script>

    
    
    
    
    
    
