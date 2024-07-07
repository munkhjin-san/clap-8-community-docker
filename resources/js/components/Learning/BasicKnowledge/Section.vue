<template>
    <DraftLayout>
        <template #main>

        
            <div style="background:inherit">
                <div>
                    <p v-if="material" v-html="filteredContent"></p>
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
                    <p :style="{marginBottom: sectionStatus != 2 ? '20px' : '0'}"><strong>{{ sectionStatus != 2 ? '基礎知識の内容で特に重要だと理解した点を入力してください' : '基礎知識の内容で特に重要だと理解した点'}}</strong></p>
                    <LongInput
                        v-if="sectionStatus != 2"
                        :initialValue="sectionContent ? sectionContent : comment"   
                        :placeHolder="`理解した点`"
                        :key="sectionContent ? sectionContent : 0"
                        ref="understandComment"
                        rules="required|max:2000"
                        name="recordBody"
                        label="タイトル"
                        v-model="comment"
                    />
                    <div v-else>
                        <p >{{ sectionContent ? sectionContent : "" }}</p>
                        <router-link :to="{name: 'more'}">もっと詳しく知りたい</router-link>
                    </div>
                    
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
                :sectionStatus="sectionStatus"
            >
            </router-view>
        </template>
    </DraftLayout>
</template>
<script setup>
    import { useRoute, useRouter } from 'vue-router';
    import LongInput from '../../Form/LongInput.vue';
    import LoaderButton from '../../Global/LoaderButton.vue'
    import { ref, computed, inject } from 'vue'
    import DraftLayout from './DraftLayout.vue';
    const router = useRouter()
    const route = useRoute()
    const { notify, info } = inject('dialog')
    const props = defineProps(['selectedTopic', 'filteredMaterials', 'sections_status'])

    const getLessonPortfolios = inject('getLessonPortfolios')

    const filteredContent = computed(() => {
        
        return material.value.content.replace(/\[\[learning_video src="(.*?)" learning_video\]\]/g, (match, videoSrc) => {
            return `<video class="ls-video"  controls="controls"><source src="${videoSrc}"></video>`;
        });
    })
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
    const list = [
        { value: 1, content: '理解した'},
        { value: 0, content: '理解できなかった'}        
    ]
    const selectedAnswer = ref('')
 
    const radioError = ref("")
    const processing_save = ref(false) 

    const validate = async(status) => {
        const valid = await understandComment.value.validate()
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
                info(props.editTarget ? '編集しました。' :'保存しました。')
                processing_save.value = false
            }
            await getLessonPortfolios() 
            radioError.value = ''
            processing.value = false
            return response.status
        }catch(error){
            if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。 ' + error.message)        
        }
                
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