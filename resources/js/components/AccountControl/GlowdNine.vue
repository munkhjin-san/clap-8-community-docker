<template>
    <div class="admin-window">
      <div class="admin-sub-c-bar">
            <PostSearchBar 
                className="newChatMemberSearch" 
                style="width:auto;min-width: 300px;"
                :searching="false"  
                v-model="keywords"
            />   
            <div style="align-self: center;">
              <div style="display: flex;align-items: center;">
                  <YearPicker 
                      :selectedYear="year"
                      @setDate="setDate"
                  />
              </div>
          </div>
      </div>
      <div style="height: calc(100% - 70px); overflow: hidden auto;">
        <table>
          <thead>
            <tr>
              <th>メンバー</th>
              <th v-for="month in months" :key="month">{{ monthFormat(month) }}</th>
            </tr>
          </thead>
    
          <tbody>
            <tr v-for="user in searchUsers" :key="user.name">
              <td>{{ user.name }}</td>
              <td v-for="month in months" :key="month">
                {{ user.task_users.find(t_user => t_user.month === month)?.total_prize }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
    </div>
  </template>
  
<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue';
import moment from 'moment';
import PostSearchBar from '@/components/Post/PostSearchBar.vue'
import YearPicker from '@/components/Global/YearPicker.vue'
import axios from 'axios';
import { User } from '@/interface/globalInterface';
const months = ref<string[]>([]);
const keywords = ref('')
const year = ref(moment().year())
const users = ref<any[]>([])
onMounted(() => {
    moment.locale('ja');
    months.value = Array.from({ length: 12 }, (_, i) =>
      moment().month(i).format('YYYY-MM')
    );
    getMonthlyPrizes()
});
const searchUsers = computed(() => {
  if(keywords.value){
      let lowSearch = keywords.value.toLowerCase()
      return users.value.filter(user => Object.values(user).some(val => 
              String(val).toLowerCase().includes(lowSearch)
          )
      )
  }else{         
      return users.value
  }
})
const getMonthlyPrizes = async() => {
    try {
        const params = {
          year: year.value
        }
        const response = await axios.get('/get_monthly_prizes', {params: params})
        users.value = response.data
    } catch (e) {

    }
}
const monthFormat = (yearMonth: string) => {
  return moment(yearMonth).format('M月')
}
const setDate = (val) => {
    year.value = val.year
    getMonthlyPrizes()
}
  
</script>
  
  <style scoped>
  table {
    border-collapse: collapse;
    width: 100%;
    font-size: 13px;
    background-color: var(--background-color);
  }
  thead {
    position: sticky;
    top: -1px;
    background-color: var(--background-color);
  }
  th, td {
    border: 1px solid var(--calendarBorder);
    text-align: left;
    padding: 5px;
    line-height: normal;
    max-width: 250px;
    height: 25px;
    vertical-align: middle;
    min-width: 80px;
  }
  </style>
  