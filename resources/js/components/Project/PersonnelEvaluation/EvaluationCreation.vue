<template>
    <div class="overlay">
        <div class="chatCreate kadaiCreate scrollable">
            <div>
                <div class="cursor-pointer" @click="emit('close')" style="position:unset; margin:20px 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div>
            </div>
            <div class="kadai-root">
                <div v-if="step === 0">
                    <div>人事結果</div>
                    <div style="margin-top: 10px;">
                        
                        <div style="display: flex; gap: 30px;">
                            <ShortInput 
                                place-holder="昇給課題設定数"
                                type="number"
                                v-model="setIssue"
                                custom-class="fit"
                            />
                            <ShortInput 
                                place-holder="昇給課題達成数"
                                type="number"
                                v-model="achievedIssue"
                                custom-class="fit"
                            />
                        </div>
                            
                        <!-- <div class="si-box">           
                            <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">雇用条件変更有無</p>     
                            <div style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap;">
                                <div @click="changeValue = 0" :class="['ch-selector', { chSelected: changeValue == 0}]">変更なし</div>
                                <div @click="changeValue = 1" :class="['ch-selector', { chSelected: changeValue == 1}]">変更あり</div>
                            </div>
                        </div>
                        <div class="si-box">
                            <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">人事確定ー人事会議使用欄ー</p>     
                            <div style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap;">
                                <div @click="confirmed = 0" :class="['ch-selector', { chSelected: confirmed == 0}]">未確定</div>
                                <div @click="confirmed = 1" :class="['ch-selector', { chSelected: confirmed == 1}]">確定</div>
                            </div>
                        </div> -->
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="人事結果"
                                :reduce="option => option"
                                label="candidate"
                                :options="increaseOptions"
                                :close-on-select="false"
                                :multiple="true"
                                v-model="increased"
                            />
                        </div>
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="雇用形態"
                                v-model="chosenStatus"
                                label="status"
                                :options="employmentStatuses"
                                :reduce="option => option"
                                :multiple="false"
                                :close-on-select="true"
                            />
                            <!-- <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">雇用形態</p>
                            <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="chosenStatus">
                                <option v-for="status in employmentStatuses" :value="status">{{ status }}</option>
                            </select> -->
                        </div>
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="新職階"
                                :options="positions"
                                v-model="general_position"
                                :reduce="option => option.name"
                                :close-on-select="true"
                                :multiple="false"
                                label="name"
                            />
                        </div>
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="新職務"
                                :options="criteriaMaster"
                                v-model="checkedLevel"
                                label="level"
                                :reduce="option => option['level']"
                                :close-on-select="true"
                                @search="handleSearch"
                                :multiple="false"
                            />
                        </div>
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="等級"
                                :options="gradeOptions"
                                v-model="grade"
                                :reduce="option => option"
                                label="salary_grade"
                                :close-on-select="true"
                                :multiple="false"
                            />
                        </div>
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="給料"
                                :options="salary_options"
                                v-model="current_salary"
                                :reduce="option => option['basic_salary']"
                                label="basic_salary"
                                :close-on-select="true"
                                :multiple="false"
                            />
                        </div>
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="新給料"
                                :options="salary_options"
                                :reduce="option => option['basic_salary']"
                                label="basic_salary"
                                v-model="after_salary"
                                :close-on-select="true"
                                :multiple="false"
                            />
                        </div>
                        <div class="si-box">
                            <LoaderButton content="保存" @triggered="saveIncrease"/>
                        </div>
                    </div>
                </div>
                <div v-if="step === 1">
                    <div style="font-weight: 600;">人事計画</div>
                    <div style="margin-top: 10px;">
                        <div class="si-box">
                            <ItemSelector 
                                placeHolder="職業能力評価基準チェックシート"
                                :reduce="option => option['standard']"
                                label="standard"
                                :multiple="true"
                                :options="criteriaMaster?.[0]?.standards"
                                v-model="checkedCriteria"
                                :closeOnSelect="false"
                            />
                        </div>
                        <div class="si-box">
                            <div style="margin-bottom: 10px;">能力保有数</div>
                            <div>{{ checkedCriteria?.length }}／{{ criteriaMaster?.[0]?.standards.length }}</div>
                        </div>
                        <!-- <div class="si-box">
                            <div style="margin-bottom: 10px;">項目数</div>
                            <div>{{ criteriaMaster.length }}</div>
                        </div>
                        <div class="si-box">
                            <div style="margin-bottom: 10px;">保有数</div>
                            <div>{{ checkedCriteria.length }}</div>
                        </div> -->
                        <div class="si-box">
                            <div style="margin-bottom: 10px;">能力保有率</div>
                            <div>{{ Math.round(checkedCriteria.length / criteriaMaster?.[0]?.standards.length * 100) }}%</div>
                        </div>
                        <div class="si-box">
                            <ItemSelector 
                                place-holder="人事計画"
                                :reduce="option => option"
                                label="candidate"
                                :options="increaseOptions"
                                :close-on-select="false"
                                :multiple="true"
                                v-model="current_increase"
                            />
                        </div>
                        <div class="si-box">
                            <LongInput 
                                placeHolder="昇格後のビジョン"
                                v-model="reason"
                                name="reason"
                                rules="required"
                                ref="reasonRef"
                            />
                        </div>
                        <div class="si-box">
                            <LongInput 
                                place-holder="メンター記入欄"
                                v-model="mentorEntry"
                                name="mentorEntry"
                                rules="required"
                            />
                        </div>
                        <div class="si-box">
                            <LoaderButton content="保存" @triggered="setIncrease" :loading="loading"/>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
    </div>
</template>
<script setup lang="ts">
import ShortInput from '@/components/Form/ShortInput.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import { computed, inject, onMounted, ref } from 'vue';
import LongInput from '@/components/Form/LongInput.vue';
import axios from 'axios';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { generalPositions } from '@/utils/tools';
import { Dialog } from '@/interface/globalInterface';
const props = defineProps([
    'evaluation', 
    'editData', 
    'memberData',
    'criteriaMaster',
    'step',
    'evaluationDate',
    'selectedDate'
])
const emit = defineEmits([
    'close', 
    'reload', 
    'search',
    'next',
])
const increaseOptions = [
    '昇給（号俸）',
    '昇格（職階）',
    '正社員登用',
    '降給',
    '降格'
]
const employmentStatuses = ([
    '正社員',
    '契約社員'
])
const loading = ref(false)
const positions = generalPositions()
const general_position = ref(props.editData?.evaluation?.new_position ?? '')
const checkedLevel = ref(props.editData?.evaluation?.current_level ?? '')
const chosenStatus = ref(props.editData?.evaluation?.employment_type ?? '正社員')
const changeValue = ref(props.editData?.change_in_position ?? 0)
const confirmed = ref(props.editData?.position_approved ?? 0)
const increased = ref(
    props.editData?.candidate?.filter(ob => ob.last_candidate !== null).map(ob => ob.last_candidate) ?? []
)
const setIssue = ref(props.editData?.last_set ?? '')
const achievedIssue = ref(props.editData?.last_achieved ?? '')
const current_increase = ref(
  props.editData?.candidate?.filter(ob => ob.next_candidate !== null).map(ob => ob.next_candidate) ?? []
);
const checkedCriteria = ref(
    props.editData?.checklist?.map(ob => ob.content) ?? []
)
const reason = ref(props.editData?.reason ?? '')
const mentorEntry = ref(props.editData?.mentor_entry ?? '')
const salary_options = ref<any>([])
const current_salary = ref(props.editData?.evaluation?.current_salary_rank ?? '')
const after_salary = ref(props.editData?.evaluation?.after_salary_rank ?? '')
const grade = ref(props.editData?.evaluation?.grade ?? '')
const { notify } = inject<Dialog>('dialog')!
const getProjects = inject('getProjects') as Function
onMounted(() => {
    getSalaryOptions()
})
const getSalaryOptions = async() => {
    try {
        salary_options.value = await axios.get('/get_salary_options').then(res => res.data)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const gradeOptions = computed(() => {
    return [...new Set(salary_options.value.map(ob => ob.salary_grade))];
})
const saveIncrease = async() => {
    loading.value = true
    const params = {
        id: props.editData?.id ?? null,
        params: {
            user_id: props.memberData?.id,
            last_set: setIssue.value,
            last_achieved: achievedIssue.value,
            change_in_position: changeValue.value,
            position_approved: confirmed.value,
            date: props.evaluationDate,
            target_period: props.selectedDate.lastDate,
        },
        
        last_candidates: increased.value,
        candidates: current_increase.value,
        evaluations: {
            new_position: general_position.value,
            employment_type: chosenStatus.value,
            date: props.evaluationDate,
            grade: grade.value,
            current_salary_rank: current_salary.value,
            after_salary_rank: after_salary.value,
            current_level: checkedLevel.value,
        }
    }
    try {
        await axios.post('/save_evaluation', params)
        emit('close')
        emit('reload')
        getProjects()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        loading.value = false
    }
    
}
const setIncrease = async() => {
    
    loading.value = true
    const params = {
        id: props.editData?.id ?? null,
        params : {
            user_id: props.memberData?.id,
            reason: reason.value,
            mentor_entry: mentorEntry.value,
        },
        skills: checkedCriteria.value,
        candidates: current_increase.value,
        last_candidates: increased.value,
    }
    try {
        await axios.post('/set_increase_request', params)
        emit('close')
        emit('reload')
    } catch(e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        loading.value = false
    }
    
}
const handleSearch = (keyword: string) => {
    emit('search', keyword)
}
</script>