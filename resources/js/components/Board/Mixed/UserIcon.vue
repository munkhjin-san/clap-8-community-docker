<template>
    <div :title="user && user.name ? user.name : ''"  @click="push($event, user.id)">
        <img v-if="userIcon" draggable="false" :class="[imgClass]" v-lazy="{src: userIcon}" :style="imgStyle" />
        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" :class="[imgClass]">
            <circle cx="15" cy="15" r="15" fill="#ddd"/>
        </svg>
    </div>
</template>
<script setup>
import { computed, inject } from 'vue';

    const props = defineProps(['user', 'imgClass', 'imgStyle', 'size', 'disableInstant']) 
    const userIcon = computed(() => {
        if(props.user && props.user.icon_path){
            const devicePixelRatio = window.devicePixelRatio || 1;
            const imageFileName = devicePixelRatio > 1 ? `${props.user.icon_path}_${props.user.id}_200.jpg` : `${props.user.icon_path}_${props.user.id}_${props.size ? props.size : '30'}.jpg`
            return `${window.location.origin}/cdn/profile_icon/${imageFileName}`;
            
        }else{
            return null
        }
    })       
    const push = (e, user) => {
        if(props.disableInstant) return
        e.stopPropagation()
        pushInstantUser(e, user)
    }
    const pushInstantUser = inject('pushInstantUser')
</script>