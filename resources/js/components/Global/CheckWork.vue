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
    import { useAuthUserStore } from '../../store/auth';
    import { getCustomFields, getWorkGroup } from '../../utils/workApi';

    import moment from 'moment';
    const auth = useAuthUserStore()
    const shiftNotSubmittedList = ref([])
    const nextShiftNotSubmittedList = ref([])
    const timecardNotSubmittedList = ref([])
    const workGroups = ref([])
    const customFieldData = ref([])
    const route = useRoute()
    onMounted(() => {
        if(!auth.isRegistered && !auth.isOnLeave){
            getNotSubmitted()
            checkDay() 
            fetchDatas()
        }        
    })
    const checkDay = () => {
        const currentDate = moment()
        const lastDayOfMonth = moment().endOf('month')
        const twentyFifthOfMonth = moment().date(25)
        if (currentDate.isBetween(twentyFifthOfMonth, lastDayOfMonth, null, '[]')){
            checkNextMonthShift()
        }
    }
    const checkNextMonthShift = async() => {
        try {
            const data = await axios.get('/next_month_shift').then(res => res.data)
            nextShiftNotSubmittedList.value = data
        } catch (e) {

        }
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
        try{
            workGroups.value = await getWorkGroup()
            customFieldData.value = await getCustomFields()
        }catch (e){
            notify(e?.message || 'エラーが発生しました。') 
        }
    } 
    const getNotSubmitted = async() => {
        try{
            const data = await axios.post('/not_submitted').then(res => res.data)
            timecardNotSubmittedList.value = data.timecard_notSubmitted
            shiftNotSubmittedList.value = data.shift_notSubmitted
        } catch (e) {

        }
    }
    provide('checkWork', {
        nextMonthShift: () => checkNextMonthShift(),
        notSubmitted: () => getNotSubmitted()
    })
    provide('customInfo', customFieldData)
    provide('workGroups', workGroups)
</script>