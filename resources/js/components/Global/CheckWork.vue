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
            <div v-if="departureReportFlag">
                <DepartureReportSend @sent="departureReportFlag = false"/>
            </div>
        </div>
    </div>
</template>
<script setup>
import { onMounted, computed, ref, provide } from 'vue';
import WorkNotSubmitted from '../Work/WorkNotSubmitted.vue';
import { useRoute } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';

import { DateTime, Interval } from 'luxon';
import { useApi } from '@/composables/api';
import DepartureReportSend from '../Work/DepartureReportSend.vue';
    const auth = useAuthUserStore()
    const shiftNotSubmittedList = ref([])
    const nextShiftNotSubmittedList = ref([])
    const timecardNotSubmittedList = ref([])


    const route = useRoute()
    const departureReportFlag = ref(false)
    const api = useApi()
    onMounted(() => {
        setTimeout(async () => {
            if(!auth.isRegistered && !auth.isOnLeave){
                await getNotSubmitted()
                checkDay() 
                fetchDatas()
            }       
            if(auth.isRegistered && !auth.isOnLeave){
                await getDepartureReport(false)
            }
        }, 3000);
        
    })
    const getDepartureReport = async(reload) => {
        const date = DateTime.now().toISODate()
        const lastDate = localStorage.getItem('departure_report')
        if(date === lastDate && !reload){
            return
        }
        const {should_send} = await api.get('/check_departure_report')
        if(should_send){
            departureReportFlag.value = true
            
        }else{
            localStorage.setItem('departure_report', date)
        }
        

    }
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
            nextShiftNotSubmittedList.value.length ||
            departureReportFlag.value
        return hasItems      
    })
    const checkQuery = computed(() => {
        return route.query && route.query.user_id
    })
    const getNotSubmitted = async() => {
  
        const data = await api.get('/remind_attendance')
        timecardNotSubmittedList.value = data.timecard_notSubmitted
        shiftNotSubmittedList.value = data.shift_notSubmitted

    }
    provide('checkWork', {
        nextMonthShift: () => checkNextMonthShift(),
        notSubmitted: () => getNotSubmitted()
    })


</script>