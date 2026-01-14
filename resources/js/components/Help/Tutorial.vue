<template>
    <div>    
        <div class="grid grid-cols-1 gap-4 p-[30px] leading-normal">
            <div v-if="auth.user?.position_id && auth.user?.position_id >= 6" @click="router.push({ name: 'about-project' })" class="p-4 border border-[var(--formBorder)] border-solid hover:shadow-lg hover:scale-[1.001] transition-all duration-200 ease-in-out cursor-pointer">
                <div class="flex items-center gap-4 mb-4">
                    <component :is="ProjectHelp.icon" size="30" fill="var(--primary-color)"/>
                    <h3>{{ ProjectHelp.title }}</h3>
                </div>
                <p class="mb-4">{{ ProjectHelp.content }}</p>
                
            </div>
        </div>
        <router-view></router-view>
    </div>
</template>
<script setup lang="ts">
import { useAuthUserStore } from '@/store/auth';
import { ProjectHelp } from 'assets/help/help.project';
import { useRouter } from 'vue-router';
const router = useRouter();
const auth = useAuthUserStore()
</script>