<template>
    <div class="employee-card">                            
        <div class="employee-card-inner">                                
            <div  class="employee-card-body">
                <div class="employee-image" style="position:relative;">                    
                    <UserPanel size="60" :user="member" imgClass="userLargeIcon"/>                    
                </div>
                <div class="employee-info">
                    <div class="employee-user-list" style="display:flex;">
                        <router-link style="font-size:17px;font-weight:bold;margin-right:5px;" class="user-link" :to="`/user/${member.id}`">{{member.name}}</router-link>
                        <WeatherIcon v-if="member.today_weather" style="min-width: 20px" :which="member.today_weather.value_int" size="20"/>
                    </div>
                    <div v-if="member.name_kana" class="employee-user-list">{{member.name_kana}}</div>
                    <div v-if="member.motto" class="employee-user-list">{{member.motto}}</div> 
                </div> 
            </div>
        </div>
    </div>
</template>
<script setup>
import UserPanel from '@/components/Global/UserPanel.vue'
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
const props = defineProps(['member'])
    
</script>