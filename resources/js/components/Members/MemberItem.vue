<template>
    <div class="employee-card">                            
        <div class="employee-card-inner">                                
            <div  class="employee-card-body">
                <div class="employee-image" style="position:relative;">
                    <router-link :to="`/user/${member.id}`">  
                        <UserIcon size="80" :user="member" imgClass="userLargeIcon"/>
                    </router-link>
                </div>
                <div class="employee-info">
                    <ul>
                        <div class="employee-user-list" style="display:flex;">
                            <li style="font-size:17px;font-weight:bold;margin-right:5px;">{{member.name}}</li>
                            <img v-if="member.today_weather" :src="'images/icon_' +member.today_weather.value_int + '.svg'" width="20" height="20" /> 
                        </div>
                        <li v-if="member.name_kana" class="employee-user-list">{{member.name_kana}}</li>
                        <li v-if="member.motto" class="employee-user-list">{{member.motto}}</li>                                               
                    </ul>
                </div> 
            </div>
        </div>
    </div>
</template>
<script>
import UserIcon from '../Board/Mixed/UserIcon.vue';
export default{
    props: ['member'],
    components: {
        UserIcon
    }
}
</script>