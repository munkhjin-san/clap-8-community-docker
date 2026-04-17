<template>
    <div class="admin-window">
        <div @click="router.push({name: 'learningcontrol'})" style="display: flex;align-items: center;padding: 20px;gap: 15px;position: sticky;top: 0;background: var(--bg3);z-index: 7;">
            <Back @click="activeLesson = null, lessons = []"/>
            <h3>{{ theme?.title }}</h3>
        </div>
        <div class="sub-tab-container"  style="margin: 0 20px 20px;">
            <div @click="router.push({name: 'content'})" :class="['sub-tab-item', {'selected-sub-tab' : route && route.name == 'content'}]">コンテンツ</div>
            <div v-if="theme?.has_case_study" @click="router.push({name: 'case-study'})" :class="['sub-tab-item', {'selected-sub-tab' : route && route.name == 'case-study'}]">参加者</div>
            <div v-else @click="router.push({name: 'trainee'})" :class="['sub-tab-item', {'selected-sub-tab' : route && route.name == 'trainee'}]">参加者</div>
            <div @click="router.push({name: 'non-trainee'})" :class="['sub-tab-item', {'selected-sub-tab' : route && route.name == 'non-trainee'}]">未参加者</div>
            <div @click="router.push({name: 'assistant'})" :class="['sub-tab-item', {'selected-sub-tab' : route && route.name == 'assistant'}]">AIアシスタント</div>
        </div>
        <div style="height: calc(100% - 110px);" v-if="theme">
            <router-view :theme="theme"></router-view>
        </div>
        
    </div>

</template>
<script setup>
import Back from '@/components/Icons/Back.vue';
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
const props = defineProps(['themes'])
const router = useRouter()
const route = useRoute()
const theme = computed(() => {
    return props.themes && props.themes.length ? props.themes.find(ob => ob.id == route.params.themeId) : null
})
</script>