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
        >
        <template v-slot:error>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" :width="`${computedSize}px`" :height="`${computedSize}px`">
                <circle cx="15" cy="15" r="15" fill="var(--secondary-background)"/>
            </svg>
        </template>
        </v-img>
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
        size?: string | number
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
        const color = props.user.icon_bg || '000000'
        return props.user.icon_path ? 
        `/user_icon_thumbnail/${props.user.icon_path}/${size}/${color}` : 
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