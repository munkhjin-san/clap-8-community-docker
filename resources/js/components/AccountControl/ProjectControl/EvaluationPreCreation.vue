<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <div>
                <p>{{ `${user?.name}` }}</p>
                <p class="text-[gray] text-[13px] mt-[15px]">{{ selectedDate.name }}</p>
            </div>
    
        </template>
        <template #content>
            <div class="si-box">
                <ItemSelector 
                    place-holder="職階"
                    :options="positions"
                    v-model="general_position"
                    :reduce="option => option.name"
                    :close-on-select="true"
                    :multiple="false"
                    :clearable="true"
                    label="name"
                />
            </div>
            <div class="si-box">
                <!-- <EvaluationLevels 
                    :initial="editData?.current_level ?? ''" 
                    :user="user" 
                    :selectedDate="selectedDate"
                    v-model="checkedCriteria"
                /> -->

                
                <!-- <ItemSelector 
                    place-holder="職務評価基準"
                    :options="criteriaMaster"
                    v-model="checkedCriteria"
                    label="level"
                    :reduce="option => option['level']"
                    :close-on-select="true"
                    @search="search"
                    :multiple="false"
                    :clearable="true"
                /> -->
            </div>
            <!-- <div class="si-box">
                <ItemSelector 
                    place-holder="等級"
                    :options="salary_options"
                    v-model="grade"
                    :reduce="option => option['salary_grade']"
                    label="salary_grade"
                    :close-on-select="true"
                    :multiple="false"
                    :clearable="true"
                />
            </div> -->
            <div class="si-box">
                <ItemSelector 
                    place-holder="現在の号俸"
                    :options="salary_options"
                    v-model="current_salary"
                    :reduce="option => option['basic_salary']"
                    label="basic_salary"
                    :close-on-select="true"
                    :multiple="false"
                    :clearable="true"
                />
            </div>
            <div class="si-box">
                <ItemSelector 
                    place-holder="異動後の号俸"
                    :options="salary_options"
                    :reduce="option => option['basic_salary']"
                    label="basic_salary"
                    v-model="after_salary"
                    :close-on-select="true"
                    :multiple="false"
                    :clearable="true"
                />
            </div>
            <div class="si-box">
                <LoaderButton @triggered="saveGrade" content="保存"/>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { inject, onMounted, ref } from 'vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { generalPositions } from '@/utils/tools';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import Modal from '@/components/Global/Modal.vue';
import { useApi } from '@/composables/api';
const props = defineProps([
    'user', 
    'selectedDate', 
    'mentorList', 
    'salary_options',
    'editData'
])
const positions = generalPositions()

const emit = defineEmits(['close'])
const current_salary = ref(props.editData?.current_salary_rank ?? '')
const after_salary = ref(props.editData?.after_salary_rank ?? '')
const general_position = ref(props.editData?.general_position ?? '')
const grade = ref(props.editData?.grade ?? '')
const refresh = inject('refresh') as Function
const api = useApi()
const saveGrade = async() => {    
    const params = {
        attributes: {
            user_id: props.user.id,
            year: props.selectedDate.year,
            which_half: props.selectedDate.which_half,
        },
        params : {                    
            current_salary_rank: current_salary.value,
            after_salary_rank: after_salary.value,
            grade: grade.value,
            general_position: general_position.value,
        }
        
    }
    await api.post('/save_evaluation_grade', params, { toast: '保存しました' })
    emit('close')
    refresh()
}
onMounted(() => {
    getPreviousEvaluation()
})
const getPreviousEvaluation = async() => {
    const params = {
        user_id: props.user.id,
        year: props.selectedDate.year,
        which_half: props.selectedDate.which_half,
    }

    const data = await api.post('/get_previous_evaluation', params)
    if(data && data?.id){
        current_salary.value = data.after_salary_rank
    }

}
</script>