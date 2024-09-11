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
            </div>
            <div class="si-box" style="height: 70%;">
                <div style="font-size: 14px;margin-bottom: 15px;">基礎知識の内容</div>
                <RichEditor ref="richEdit" :initilaValue="initialValue"/>
            </div>
            <!-- <div class="si-box" style="height: 70%;">
                <div style="font-size: 14px;margin-bottom: 15px;">基礎知識の内容（理解できなかった際）</div>
                <RichEditor ref="richEditDetailed" :initilaValue="initialValueDetailed"/>
            </div> -->
            <div class="si-box" style="height: 70%;">
                <div>
                    <div v-for="priority in priorities" style="display: flex;align-items: center;padding: 5px 0;">
                        <input class="fish-eye" v-model="selectedPriority" type="radio" :id="priority.value" name="answer" :value="priority.value" >
                        <label style="margin-left:10px;cursor:pointer" :for="priority.value">{{priority.content}}</label>
                    </div>
                </div>
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
import { computed, inject, ref } from 'vue';
import { useRoute } from 'vue-router';
    const priorities = [
        {value: 0, content: 'ヘッダー'},
        {value: 1, content: 'セクション'},
    ]
    const route = useRoute()
    const { notify, info } = inject('dialog')
    const props = defineProps(['editTarget'])
    const emit = defineEmits(['createFinish'])
    const processing = ref(false)
    const hasFeedBack =  ref(props.editTarget && props.editTarget.has_feedback ? props.editTarget.has_feedback : false)
    const title = ref(props.editTarget && props.editTarget.title ? props.editTarget.title : '')
    const richEdit = ref(null)
    const richEditDetailed = ref(null)
    const selectedPriority = ref(props.editTarget ? props.editTarget.priority : null)
    const initialValue = computed(() => {
        return props.editTarget && props.editTarget.content ? props.editTarget.content : ''
    })
    const initialValueDetailed = computed(() => {
        return props.editTarget && props.editTarget.content_detailed ? props.editTarget.content_detailed : ''
    })
    const createSend = async() => {
            const richContent = richEdit.value.editor.getHTML()
            // const richContentDetailed = richEditDetailed.value.editor.getHTML()
            if(!richContent || !title.value || selectedPriority.value == null){
                processing.value = false
                return
            }
            try {
                
                const params = {
                    edit_id: props.editTarget ? props.editTarget.id : null,
                    lesson_theme_id: route.params.themeId,
                    title: title.value,
                    lesson_content: richContent,
                    content_detailed: props.editTarget ? props.editTarget.content_detailed : null,
                    has_feedback: hasFeedBack.value,
                    priority: selectedPriority.value
                }
        
                axios.post('/lesson_add_record',params)
                .then(response => setTimeout(() => {
                    closeModal(true, response.data.id)
                    info(props.editTarget ? '編集しました。' :'保存しました。')
                },0))
                .catch(function (error) {
                    if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) notify('エラーが発生しました。')
                    else notify('エラーが発生しました。 ' + error.message)      
                    processing.value = false                    
                });
                
            } catch (error) {
                console.error('Error fetching data:', error);
                processing.value = false
            }
        }
    const closeModal = (flag, id) => {
            processing.value = false
            emit('createFinish',flag, id);              
        }     
    
</script>

    
    
    
    
    
    