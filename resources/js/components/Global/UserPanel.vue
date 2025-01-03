<template>
    <div class="flex items-center text-[13px] w-fit" :title="user.name!" @click="push">
        <v-img
            :src="thumbnailUrl(size ? Number(size) : 30)"
            :srcset="generateSrcset"
            aspect-ratio="1"
            class="rounded-full"
            :draggable="false"
            :height="`${computedSize}px`"
            :width="`${computedSize}px`"
            :sizes="`(max-width: 959px) 200px, ${computedSize}px`"
            rounded="circle"
            :title="user.name"
        ></v-img>
        <div>
            <div v-if="withName" class="ml-[10px]">{{ user.name }}</div>
            <slot name="details"></slot>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, inject } from 'vue';    
import { TaskUser, User } from '@/interface/globalInterface'
import { useTheme } from '@/store/theme';
    const props = defineProps<{
        user: User | TaskUser
        imgClass?: string
        imgStyle?: string
        size?: string
        disableInstant?: boolean
        withName?: boolean 
        forceColor?: string
    }>()
    const theme = useTheme()
    const pushInstantUser = inject<Function>('pushInstantUser') as Function  
    const push = (event: Event) => {
        if(props.disableInstant) return
        event.stopPropagation()
        if(pushInstantUser){
            pushInstantUser(event, props.user)
        }        
    }    
    const thumbnailUrl = (size:number) => {    
        const color = props.forceColor ? props.forceColor : theme.dark ? 'dark' : 'light'
        const imageFileName = devicePixelRatio > 1 ? `${props.user.icon_id}_${props.user.id}_200.jpg` : `${props.user.icon_id}_${props.user.id}_${props.size ? props.size : '30'}.jpg`

        return props.user.icon_id ? 
        `${window.location.origin}/cdn/profile_icon/${imageFileName}` : 
        `/user_default_thumbnail/${props.user.name?.charAt(0).toUpperCase()}/${size}/${color}`
    }
    const generateSrcset = computed(() => {
        const set = computedSize.value
        const sizes:number[] = [200, set];
        return sizes.map(size => `${thumbnailUrl(size)} ${size}w`).join(', ');
    })
    
    const computedSize = computed(() => {
        return props.size ? Number(props.size) : 30
    })
</script>