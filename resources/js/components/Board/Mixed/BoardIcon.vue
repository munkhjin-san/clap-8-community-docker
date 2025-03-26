<template>
    <div >
        <img v-if="item.private_flag == 0 && boardIcon" draggable="false" loading="lazy" :class="[imgClass]" :src="boardIcon" :style="imgStyle">
        <UserPanel v-if="item.private_flag > 0 && correspondUser" :disableInstant="true" :user="correspondUser" :imgClass="imgClass" size="45"/>
        <svg v-if="!boardIcon && !correspondUser" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" :class="[imgClass]">
            <circle cx="15" cy="15" r="15" fill="#ddd"/>
        </svg>
    </div>
</template>
<script setup>
import { computed } from 'vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { useAuthUserStore } from '@/store/auth'
    const auth = useAuthUserStore()
    const props = defineProps(['item', 'imgClass', 'imgStyle'])
    const correspondUser = computed(() => {
        if(props.item.private_flag == 1){
            var user = props.item.board_to_users.filter(obj => obj.user_id !== auth.activeUser.id);
            return user && user.length && user[0].user? user[0].user : null
            
        }else if(props.item.private_flag == 3){
            var me = props.item.board_to_users.filter(obj => obj.user_id == auth.activeUser.id);
            return me && me.length && me[0].user ? me[0].user : null
        }
        return null
    })
    const boardIcon = computed(() => {
        if (props.item.icon_path) {
            return `/board_icon_thumbnail/${props.item.icon_path}`
        } else if (props.item.icon_text) {
            const color = encodeURIComponent(props.item?.icon_bg ?? '#000');
            const noSpace = props.item.icon_text?.replace(/[\s　]/g, '');   
            const noSlash = noSpace?.replace(/\//g, '');   
            const basePath = '/board_default_thumbnail'
            return `${basePath}/${noSlash}/45/${color}`; 
        } else  {
            const color = encodeURIComponent('#000');
            const noSpace = props.item.title?.replace(/[\s　]/g, ''); 
            const noSlash = noSpace?.replace(/\//g, '');   
            const basePath = '/board_default_thumbnail'
            return `${basePath}/${noSlash}/45/${color}`;
        }
    })   
</script>