<template>
    <div class="flex items-center text-[13px] w-fit" :title="contact.name!">
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
            :title="contact.name"
        ></v-img>
        <div>
            <div v-if="withName" class="ml-[10px]">{{ contact.name }}</div>
            <slot name="details"></slot>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue';    
import { useTheme } from '@/store/theme';
import { ContactRecord } from '@/interface/contactInterface';
    const props = defineProps<{
        contact: ContactRecord
        size?: string
        withName?: boolean 
        forceColor?: string
    }>()
    const theme = useTheme()
  
    const thumbnailUrl = (size:number) => {    
        return props.contact.icon_path ? 
        `/contact_icon_thumbnail/${props.contact.icon_path}/${size}/000000` : 
        `/user_default_thumbnail/${props.contact.name?.charAt(0).toUpperCase()}/${size}/000000`
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