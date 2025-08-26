<template>
    <Modal @close="emit('close')">
        <template #title>
            <div>
                <p>人事考課 : {{ memberData?.name }}</p>
                <p class="text-[gray] mt-[15px] text-[14px]">{{ date?.name }}</p>
            </div>
            
        </template>
        <template #content>
            <Transition name="modalFade">
                <div v-if="initialLoader" class="flex items-center justify-center fixed top-0 left-0 w-full h-full bg-[var(--overlay)] z-50">
                    <div id="loaderMini">
                        <div class="spinner-mini" style="border-color: transparent #fff #fff;"></div>
                    </div>
                </div>
            </Transition>
            <div :class="step == 1 ? 'h-0 overflow-hidden' : ''">
                <div>メンター : <strong>{{ evaluationData?.mentor?.name }}</strong></div>

                <div class="mt-[20px]">
                    前期成果目標達成率 : <strong>{{ previousStats.total_achievment }}%</strong>／600%
                </div>

                <div class="mt-[20px]">
                    昇給課題設定可能数 : <strong>{{ previousStats.possible_increase_number }}</strong>／2
                </div>
                <div>
                    <div class="si-box">
                        <p class="mb-[15px]">人事計画</p>
                        <div class="flex flex-wrap gap-[15px]">
                            <label v-for="option in increaseOptions" class="flex items-center gap-[10px] cursor-pointer">
                                <input type="checkbox" class="custom-f-checkbox" v-model="evaluationParams.children.candidate" :value="option">
                                {{ option }}
                            </label>
                        </div>
                        
                        <!-- <ItemSelector 
                            place-holder="人事計画"
                            :reduce="option => option"
                            label="next_candidate"
                            :options="increaseOptions"
                            :close-on-select="false"
                            :multiple="true"
                            :clearable="true"
                            v-model="evaluationParams.children.candidate"
                        /> -->
                    </div>
                    <div class="si-box">
                        <p class="mb-[15px]">職能レベル</p>
                        <EvaluationLevels
                            v-if="evaluationData"
                            :key="previousStats.current_level?.length || evaluationData.current_level?.length"
                            :initial="evaluationData.status == 0 ? previousStats.current_level : evaluationData.current_level" 
                            :selectedDate="date"
                            :auto-set="evaluationData.status !== 0"
                            ref="evaluationLevelsRef"
                            v-model="evaluationParams.children.checklist"
                        />
                    </div>
                    <div v-if="evaluationParams.children.candidate.includes('昇格（職階）')" class="si-box">
                        <LongInput 
                            placeHolder="昇格後のビジョン"
                            v-model="evaluationParams.params.vision"
                            name="reason"
                            rules="required"
                            ref="reasonRef"
                        />
                    </div>
                    <div class="si-box">
                        <LongInput 
                            place-holder="メンター記入欄"
                            v-model="evaluationParams.params.mentor_comment"
                            name="mentorEntry"
                            rules="required"
                        />
                    </div>
                    <div class="si-box">
                        <LoaderButton content="次へ" @triggered="nextStep"/>
                    </div>
                </div>
            </div>
            <div v-if="step == 1" class="leading-normal">
                <div @click="step = 0" class="flex items-center gap-[15px] text-[16px] cursor-pointer">
                    <Back/>
                    <div>戻る</div>
                </div>
                <div class="mt-[20px]"><strong>内容確認</strong></div>
                <div class="si-box">
                    <p><strong>昇給課題</strong></p>
                    <ul>
                        <li v-for="item in evaluationParams.children.candidate">{{ item }}</li>
                    </ul>
                </div>
                <div class="si-box">
                    <p><strong>職能レベル</strong>:{{ evaluationParams.params.current_level }}</p>
                    <p class="mt-[20px]">チェックシート</p>
                    <ul class="list-disc ml-[20px]">
                        <li v-for="skill in evaluationParams.children.checklist">{{ skill }}</li>
                    </ul>
                </div>
                <div class="si-box">
                    <p><strong>昇格後のビジョン</strong></p>
                    <p class="mt-15px whitespace-break-spaces">{{ evaluationParams.params.vision }}</p>
                </div>
                <div class="si-box">
                    <p><strong>メンターコメント</strong></p>
                    <p class="mt-15px whitespace-break-spaces">{{ evaluationParams.params.mentor_comment }}</p>    
                </div>
                <div class="si-box flex items-center flex-wrap">
                    <LoaderButton content="一時保存" @triggered="setIncrease(1)"/>
                    <LoaderButton content="申請する" @triggered="setIncrease(2)"/>
                </div>  
            </div>
            
        </template>
        
    </Modal>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref, useTemplateRef } from 'vue';
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import EvaluationLevels from '../EvaluationLevels.vue';
import Back from '@/components/Icons/Back.vue';
import 'styles/customForm.css'
import { EvaluationRecord } from '@/interface/evaluationInterface';
import { useProject } from '@/composables/project';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
const props = defineProps([
    'evaluation', 
    'date',
])
const emit = defineEmits([
    'close', 
    'reload', 
])

const { memberData } = useProject()
const increaseOptions = [
    '異動なし',
    '昇給（号俸）',
    '昇格（職階）',
    '正社員登用',
    '降給',
    '降格',
]
const evaluationParams = reactive({
    attributes: {
        id: null,
    },
    params: {
        current_level: '',
        status: 1,
        vision: '',
        mentor_comment: '',
    },
    children:{
        checklist: [],
        candidate: <any>[],
    }


})

const loading = ref(false)
const initialLoader = ref(true)
const evaluationData = ref<EvaluationRecord | null>(null)
const step = ref(0)
const evaluationLevelsRef = useTemplateRef('evaluationLevelsRef')
const previousStats = reactive<{
    total_achievment: number,
    possible_increase_number: number,
    current_level: string
    current_skills: string[]
}>({
    total_achievment: 0,
    possible_increase_number: 0,
    current_level: '',
    current_skills: []

})

const api = useApi()
const { ask, ping } = useDialog()
onMounted(() => {
    checkMentorSelected()
})
const checkMentorSelected = async() => {
    try{
        const response = await api.get('/check_evaluation_for_user_in_span', {

            user_id: memberData.value?.id,
            year: props.date.year,
            which_half: props.date.which_half

        })

        evaluationData.value = response.evaluation

        if(response.evaluation){
            evaluationParams.attributes.id = response.evaluation.id
            evaluationParams.params.vision = response.evaluation.vision
            evaluationParams.params.mentor_comment = response.evaluation.mentor_comment
            evaluationParams.children.candidate = response.evaluation.candidate?.map((item: any) => item.next_candidate) || []
        }
        previousStats.total_achievment = response.total_achievment
        previousStats.possible_increase_number = response.possible_increase_number
        previousStats.current_level = response.current_level
        previousStats.current_skills = response.current_skills
        const currentSkills = response?.evaluation?.checklist?.map(ob => ob.content)
        const previousSkills = response.current_skills
        evaluationParams.children.checklist = currentSkills.length ? currentSkills : previousSkills.length ? previousSkills : []

    }catch(e) {
        emit('close')
    } finally {
        initialLoader.value = false
    }

}
const setIncrease = async(status) => {
    if(!evaluationData.value || !evaluationData.value.id){
        ping('人事考課レコードが作成されていません。')
        return
    }
    evaluationParams.params.status = status
    loading.value = true
    const params = evaluationParams

    await api.post('/set_increase_request', params, {
        toast: '保存しました。'
    })
    emit('close')
    emit('reload')
    loading.value = false

    
}

const nextStep = async() => {
    if(step.value == 0){
        evaluationParams.params.current_level = evaluationLevelsRef.value?.selectedLevel || ''
        if(!evaluationParams.params.current_level){
            const confirmed = await ask('職能レベルが選択されていません。続行しますか？')
            if(!confirmed.value){
                return
            }
        }
        step.value = 1
    } 
}
</script>