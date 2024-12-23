<template>
    <div >
        <p :class="titleClass" :style="titleStyle">{{boardTitle}}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthUserStore } from '@/store/auth'
    const auth = useAuthUserStore()

    const props = defineProps(['item', 'titleClass', 'titleStyle'])    
    const boardTitle = computed(() => {            
        if(props.item.private_flag == 1){
            var coresspondId = props.item.board_to_users.filter(obj => obj.user_id !== auth.activeUser.id);
            if(coresspondId && coresspondId.length && coresspondId[0].user){
                return coresspondId[0].user.name;
            }else{
                return '非アクティブユーザー'
            }
            
        }else if(props.item.private_flag == 3){
            return 'マイボード'
        }else{
            return props.item.title;
        }           
    })
   
</script>
