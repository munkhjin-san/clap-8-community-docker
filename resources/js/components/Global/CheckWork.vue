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
    const shiftNotSubmittedList = ref([])
    const timecardNotSubmittedList = ref([])
    const route = useRoute()
    onMounted(() => {
        getNotSubmitted()        
    })
    const checkworkShow = computed(() =>{
        const hasItems =
            shiftNotSubmittedList.value.length ||
            timecardNotSubmittedList.value.length
        return hasItems      
    })
    const checkQuery = computed(() => {
        return route.query && route.query.user_id
    })

    const getNotSubmitted = async() => {
        try{
            const response = await axios.post('/not_submitted')
            shiftNotSubmittedList.value = response.data.shiftNotSubmittedList;
            timecardNotSubmittedList.value = response.data.timecardNotSubmittedList;
        } catch (e) {
            console.log(e)
        }
    }
    
    provide('getNotSubmitted', getNotSubmitted)
</script>