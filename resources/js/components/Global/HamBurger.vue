<template>
    <div class="hb-menu" @click="sideMenuView.setSideMenuView(!sideMenuView.active)">
        <svg v-if="!sideMenuView.active" class="dot-menu" version="1.1" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" >
            <path d="M5,7h14c0.6,0,1-0.4,1-1s-0.4-1-1-1H5C4.4,5,4,5.4,4,6S4.4,7,5,7z"/>
            <path d="M5,13h14c0.6,0,1-0.4,1-1s-0.4-1-1-1H5c-0.6,0-1,0.4-1,1S4.4,13,5,13z"/>
            <path d="M5,19h14c0.6,0,1-0.4,1-1s-0.4-1-1-1H5c-0.6,0-1,0.4-1,1S4.4,19,5,19z"/>
        </svg>
        <Back v-else/>
        <div style="top: -3px;right: -5px;left: auto;" class="notification" v-if="shouldView">
            {{ badge.sumOfAll > 99 ? '99+' : badge.sumOfAll }}
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useSideMenuView } from '@/store/sideMenuView';
import { useBadgeStore } from '@/store/badge'
import Back from '../Icons/Back.vue';
    const badge = useBadgeStore()
    const auth = useAuthUserStore()
    const sideMenuView = useSideMenuView()    
    const shouldView = computed(() => {
        return badge.sumOfAll && !sideMenuView.active && !auth.user.footer_view
    })
 
</script>