<template>
    <div class="admin-window">
      <div class="admin-sub-c-bar">
            <PostSearchBar 
                className="newChatMemberSearch" 
                style="width:auto;min-width: 300px;"
                @search-start="(word) => {keywords = word}"
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
      <div class="table-wrapper" style="height: calc(100% - 70px); overflow: auto;">
        <table>
          <thead>
            <tr>
              <th>メンバー</th>
              <th v-for="month in months" :key="month">{{ monthFormat(month) }}</th>
            </tr>
          </thead>
    
          <tbody>
            <tr v-for="user in searchUsers" :key="user.id">
              <td>{{ user.name }}</td>
              <td v-for="month in months" :key="month">
                {{ user.task_users?.find(t_user => t_user.month === month)?.total_prize }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
    </div>
  </template>
  
<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue'
import YearPicker from '@/components/Global/YearPicker.vue'
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
const months = ref<string[]>([]);
const keywords = ref('')
const year = ref(DateTime.now().year)
const users = ref<any[]>([])
const api = useApi()
onMounted(() => {
    setDate({year: year.value})
});
const setDate = (val) => {
    year.value = val.year
    months.value = Array.from({ length: 12 }, (_, i) => {
        const month = (i + 1).toString().padStart(2, '0'); 
        return `${year.value}-${month}`;
    });
    getMonthlyPrizes()
}
const getMonthlyPrizes = async() => {

    const params = {
        year: year.value
    }
    const response = await api.get('/get_monthly_prizes', params)
    users.value = [...response];

}
const monthFormat = (yearMonth: string) => {
    return DateTime.fromFormat(yearMonth, 'yyyy-MM').toFormat('M月')
}

const searchUsers = computed(() => {
    if(keywords.value){
        let lowSearch = keywords.value.toLowerCase()
        return users.value.filter(user => Object.values(user).some(val => 
                String(val).toLowerCase().includes(lowSearch)
            )
        )
    }         
    return users.value
  
})
  
</script>
  
  <style scoped>
  .table-wrapper::-webkit-scrollbar{
    height: 4px;
  }
  table {
    border-collapse: collapse;
    width: -webkit-fill-available;
    width: -moz-available;
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
  