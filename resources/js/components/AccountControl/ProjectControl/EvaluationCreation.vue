<template>
    <div class="overlay" @mousedown="emit('close')">
        <div class="chatCreate scrollable" @mousedown.stop>
            <div class="recordFormTitle">
                <p>{{props.user.name}}の{{selectedDate}}</p>
                <div class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div>
            </div>
            <!-- <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">雇用形態（必須）</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="chosenStatus">
                    <option v-for="status in employmentStatuses" :value="status">{{ status }}</option>
                </select>
            </div> -->
            <div class="si-box">
                <MemberSelector 
                    placeHolder="メンター選択"
                    v-model="mentor"
                    :options="mentorList"
                    :multiple="false"
                    name="mentor"
                />
            </div>
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
                <ItemSelector 
                    place-holder="職務評価基準"
                    :options="criteriaMaster"
                    v-model="checkedCriteria"
                    label="level"
                    :reduce="option => option['level']"
                    :close-on-select="true"
                    @search="search"
                    :multiple="false"
                    :clearable="true"
                />
            </div>
            <div class="si-box">
                <ItemSelector 
                    place-holder="現在の号俸"
                    :options="salary_options"
                    v-model="grade"
                    :reduce="option => option['salary_grade']"
                    label="salary_grade"
                    :close-on-select="true"
                    :multiple="false"
                    :clearable="true"
                />
            </div>
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
        </div>
    </div>
</template>
<script setup lang="ts">
import MemberSelector from '@/components/Form/MemberSelector.vue';
import { inject, markRaw, onMounted, ref } from 'vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { User } from '@/interface/globalInterface';
import axios from 'axios';
import { generalPositions } from '@/utils/tools';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import { debounce } from '@/utils/tools';
const props = defineProps([
    'user', 
    'selectedDate', 
    'mentorList', 
    'salary_options',
    'editData'
])
const positions = generalPositions()
const employmentStatuses = ([
    '正社員',
    '契約社員'
])
const chosenStatus = ref(props.editData?.employment_type ?? '正社員')
const emit = defineEmits(['close'])
const mentor = ref<User>(props.editData?.mentor ?? null)
const current_salary = ref(props.editData?.current_salary_rank ?? '')
const after_salary = ref(props.editData?.after_salary_rank ?? '')
const general_position = ref(props.editData?.general_position ?? '')
const criteriaMaster = ref<any>([])
const checkedCriteria = ref(props.editData?.current_level ?? '')
const grade = ref(props.editData?.grade ?? '')
const refresh = inject('refresh') as Function
const search = debounce(async(key: string) => {
    try {
        criteriaMaster.value = await axios.post('/get_project_criteria', {keywords: key, first: false}).then(res => res.data)
    } catch (e) {
        
    }
    
}, 350)
const firstFetch = async() => {
    try {
        criteriaMaster.value = await axios.post('/get_project_criteria', { first: true }).then(res => res.data)
    } catch (e) {
        
    }
}
const saveGrade = async() => {
    
    const params = {
        id: props.editData?.id ?? null,
        params : {
            user_id: props.user.id,
            mentor_id: mentor.value?.id,
            date: props.selectedDate,
            current_salary_rank: current_salary.value,
            after_salary_rank: after_salary.value,
            current_level: checkedCriteria.value,
            // employment_type: chosenStatus.value,
            grade: grade.value,
            general_position: general_position.value,
        }
        
    }
    try {
        await axios.post('/save_evaluation_grade', params)
        emit('close')
        refresh()
    } catch (e) {

    }
}
onMounted(() => {
    firstFetch()
})
</script>