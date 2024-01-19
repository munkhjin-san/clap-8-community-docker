<template>
    <div class="section-wrapper">
        <div class="section-inner">

        
            <div v-if="$route.name == 'material'" style="background:inherit">
                <div>
                    <p v-if="material" v-html="material.content"></p>
                </div>
                <div class="post-separetor"></div>
                <div v-if="sectionStatus != 2">
                    <p><strong>基礎知識の内容を理解しましたか？</strong></p>
                    <div v-for="answer in list" style="display: flex;align-items: center;padding: 5px 0;">
                        <input class="fish-eye" v-model="selectedAnswer" type="radio" :id="answer.value" name="answer" :value="answer.value" >
                        <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>
                    </div>
                    <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedAnswer != null ? '' : radioError }}</span>
                </div>
                
                <div v-if="selectedAnswer == 1 || sectionStatus == 2" class="si-box" style="margin:0">
                    <p :style="{marginBottom: sectionStatus != 2 ? '20px' : '0'}"><strong>{{ sectionStatus != 2 ? '基礎知識の内容で特に重要だと理解した部分を入力してください。' : '基礎知識の内容で理解した部分。'}}</strong></p>
                    <FormLongText
                        v-if="sectionStatus != 2"
                        :initialValue="sectionContent ? sectionContent : comment"   
                        :placeHolder="`理解した内容`"
                        :key="sectionContent ? sectionContent : 0"
                        ref="understandComment"
                        rules="required|max:2000"
                        uId="recordBody"
                        name="recordBody"
                        label="タイトル"
                        @setValue="val => comment = val"
                    />
                    <p v-else>{{ sectionContent ? sectionContent : "" }}</p>
                </div>
                
                <div v-if="sectionStatus != 2" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                    <div v-if="selectedAnswer == 1">
                        <LoaderButton @triggered="validate('save')" :loading="processing_save" :content="'一時保存'"/>
                    </div> 
                    <div>
                        <LoaderButton @triggered="nextStage" :loading="processing" :content="selectedAnswer == 0  ? '次へ' : '完了'"/>
                    </div>
                </div>
            </div>
            
            <router-view
                :material="material"
                :selectedTopic="selectedTopic"
                :sectionUpdate="sectionUpdate"
            >
            </router-view>
        </div>
    </div>
</template>
<script setup>
    import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
    import FormLongText from '../../Global/FormLongText.vue';
    import LoaderButton from '../../Global/LoaderButton.vue'
    import { ref, computed, inject, watch } from 'vue'
    const router = useRouter()
    const route = useRoute()
    const props = defineProps(['selectedTopic', 'filteredMaterials', 'sections_status'])

    const getLessonPortfolios = inject('getLessonPortfolios')

    const material = computed(() => {
        return props.filteredMaterials ? props.filteredMaterials.filter(val => val.id == route.params.materialId)[0] : ''
    })
    const sectionStatus = computed(() => {
        return props.sections_status && props.sections_status.length ? props.sections_status.find(val => val.material_id === material.value.id)?.status : 0
    })
    const sectionContent = computed(() => {
        return props.sections_status && props.sections_status.length ? props.sections_status.find(val => val.material_id === material.value.id)?.content : ''
    })
    const understandComment = ref(null)
    
    const comment = ref("")
    const processing = ref(false)
    const formKey = ref(0)
    const list = ref([
        { value: 1, content: '理解しました'},
        { value: 0, content: 'もっと詳しく知りたい'}        
    ])
    const selectedAnswer = ref('')
 
    const radioError = ref("")
    const processing_save = ref(false) 
    const portfolio = inject('portfolio')

    const validate = async(status) => {
        const valid = await understandComment.value.$refs.recordBody.validate()
        if(valid.valid){
            const content = comment.value ? comment.value : sectionContent.value
            return await sectionUpdate(status, content)
        }
    } 
    const sectionUpdate = async(status, content) => {
    
        let section_status = 1
        if(status == 'next'){
            processing.value = true
            section_status = 2

        }else{
            processing_save.value = true
        }

        const params = {
            content: content,
            lesson_theme_id: route.params.lessonThemeId,
            title: props.selectedTopic.title,
            material_id: route.params.materialId,
            section_status: section_status,
        }
        try{
            const response = await axios.post('/section_update', params)
            if(status == 'save'){
                const data = {
                    text: props.editTarget ? '編集しました。' :'保存しました。',
                    channel: Math.random().toString(36).substring(5),
                    icon: 0,
                    view: true
                }
                emitter.emit('setInfo', data)
                processing_save.value = false
            }
            await getLessonPortfolios() 
            radioError.value = ''
            processing.value = false
            return response.status
        }catch(error){
            if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。 ' + error.message)        
        }
                
    }
    const errorToast = (message) => {
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })  
            processing.value = false
            
        }
    const nextStage = async() => {
        if(selectedAnswer.value == 1){
            const checkValidate = await validate('next')
            if(checkValidate){
                router.push({name: 'basic'})
            }
        }else if(selectedAnswer.value == 0){
            router.push({name: 'more'})
        }else{
            radioError.value = '必須です'
            return
        }
        
    }
</script>