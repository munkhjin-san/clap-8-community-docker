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
                    @search-start="(word) => {keywords = word}"
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
import { onMounted, provide, reactive, ref, watch } from 'vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import { useRoute, useRouter } from 'vue-router';
import { User } from '@/interface/globalInterface';
import axios from 'axios';
import moment from 'moment';
import { detailedDateOptions } from '@/utils/tools';
import { DateTime } from 'luxon';
const keywords = ref('')
const router = useRouter()
const route = useRoute()
const userList = ref<User[]>([])
const mentorList = ref([])
const selectedDate = ref({
    year: '',
    which_half: '',
    name: '',
    short_name: ''
})
onMounted(() => {
    const options = detailedDateOptions()
    const today = DateTime.now()
    const which_half = today.month > 3 && today.month < 9 ? 'second' : 'first'
    const year = today.year.toString()
    const foundOption = options.find(option => option.year == year && option.which_half == which_half)
    if (foundOption) {
        selectedDate.value = foundOption
    }
})
const getSelectableUsers = async() => {
    try {
        const data = await axios.post('/get_selectable_users', {params: selectedDate.value}).then(res => res.data)
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
