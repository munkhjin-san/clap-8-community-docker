<template>
    <div class="admin-window">
        <div class="admin-command-bar" style="margin: 20px">            
            <div class="sub-tab-container" style="margin-bottom: 20px;">
                <div @click="router.push({name: 'projectlist'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'projectlist'}]">プロジェクト一覧</div>
                <div @click="router.push({name: 'mentorcontrol'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'mentorcontrol'}]">人事考課管理</div>
                <!-- <div @click="router.push({name: 'targetperiod'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'targetperiod'}]">評価指標期間管理</div> -->
            </div>  
            <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="route.name === 'projectlist' ? `プロジェクト検索` : `メンバーとメンター検索`" 
                    v-model="keywords"
                /> 
        </div>
        <!-- <CreatePeriod /> -->
        <router-view
            :keywords="keywords"
            :userList="userList"
            :mentorList="mentorList"
            v-model="selectedDate"
        >
        </router-view>
        
    </div>
</template>
<script setup lang="ts">
import { onMounted, provide, ref, watch } from 'vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import { useRoute, useRouter } from 'vue-router';
import { User } from '@/interface/globalInterface';
import axios from 'axios';
import moment from 'moment';
const keywords = ref('')
const router = useRouter()
const route = useRoute()
const userList = ref<User[]>([])
const mentorList = ref([])
const selectedDate = ref('')
onMounted(() => {
    const currentMonth = moment().month()
    if (currentMonth >= 1 && currentMonth < 7) {
        selectedDate.value = moment().month(1).set('date', 1).format('YYYY-MM-DD')
    } else {
        selectedDate.value = moment().month(7).set('date', 1).format('YYYY-MM-DD')
    }
})
const getSelectableUsers = async() => {
    try {
        const data = await axios.post('/get_selectable_users', {date: selectedDate.value}).then(res => res.data)
        userList.value = data.users
        mentorList.value = data.mentors
    } catch (e) {

    }
}
watch(() => selectedDate.value, () => {
    getSelectableUsers()
})
provide('refresh', getSelectableUsers)
</script>
