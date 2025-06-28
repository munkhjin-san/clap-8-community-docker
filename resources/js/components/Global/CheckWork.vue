<template>
    <div v-if="checkworkShow && !checkQuery" class="overlay">
        <div class="chatCreate scrollable" style="gap:20px;">
            <div class="recordFormTitle" style="padding: 20px 0 0;">
                <p style="font-size: 18px;">前日までの日報に申請漏れがあります</p>
            </div>
            
            <div class="work-notstyle" v-if="shiftNotSubmittedList.length">
                <div v-for="item in shiftNotSubmittedList">
                    <WorkNotSubmitted 
                        v-if="item"
                        :item="item"
                    />
                </div>
            </div>
            <div class="work-notstyle" v-if="nextShiftNotSubmittedList.length">
                <div v-for="item in nextShiftNotSubmittedList">
                    <WorkNotSubmitted 
                        v-if="item"
                        :item="item"
                    />
                </div>
            </div>
            <div class="work-notstyle" style="display: grid; gap: 20px;" v-if="timecardNotSubmittedList.length">
                <div v-for="item in timecardNotSubmittedList">
                    <WorkNotSubmitted 
                        v-if="item"
                        :item="item"
                    />
                </div> 
            </div>
        </div>
    </div>
</template>
<script setup>
import { onMounted, computed, ref, provide } from 'vue';
import WorkNotSubmitted from '../Work/WorkNotSubmitted.vue';
import { useRoute } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import { getCustomFields, getWorkGroup } from '../../utils/workApi';

import { DateTime, Interval } from 'luxon';
import { useApi } from '@/composables/api';
    const auth = useAuthUserStore()
    const shiftNotSubmittedList = ref([])
    const nextShiftNotSubmittedList = ref([])
    const timecardNotSubmittedList = ref([])
    const workGroups = ref([])
    const customFieldData = ref([])
    const route = useRoute()
    const api = useApi()
    onMounted(() => {
        if(!auth.isRegistered && !auth.isOnLeave){
            getNotSubmitted()
            checkDay() 
            fetchDatas()
        }        
    })
    const checkDay = () => {
        const currentDate = DateTime.now()
        const lastDayOfMonth = currentDate.endOf('month').plus({days: 1})
        const twentyFifthOfMonth = currentDate.set({day: 25})
        const interval = Interval.fromDateTimes(twentyFifthOfMonth, lastDayOfMonth)
        if(interval.isValid){
            if(interval.contains(currentDate)){
                checkNextMonthShift()
            }
        }
    }
    const checkNextMonthShift = async() => {
        const data = await api.get('/next_month_shift')
        nextShiftNotSubmittedList.value = data
    }
    const checkworkShow = computed(() =>{
        const hasItems =
            shiftNotSubmittedList.value.length ||
            timecardNotSubmittedList.value.length ||
            nextShiftNotSubmittedList.value.length
        return hasItems      
    })
    const checkQuery = computed(() => {
        return route.query && route.query.user_id
    })
    const fetchDatas = async() => {
    
        workGroups.value = await getWorkGroup()
        customFieldData.value = await getCustomFields()

    } 
    const getNotSubmitted = async() => {
  
        const data = await api.get('/remind_attendance')
        timecardNotSubmittedList.value = data.timecard_notSubmitted
        shiftNotSubmittedList.value = data.shift_notSubmitted

    }
    provide('checkWork', {
        nextMonthShift: () => checkNextMonthShift(),
        notSubmitted: () => getNotSubmitted()
    })
    provide('customInfo', customFieldData)
    provide('workGroups', workGroups)
</script>