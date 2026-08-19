<template>
    <div class="admin-window schedule-control">
        <div class="admin-command-bar schedule-control__nav">
            <div class="sub-tab-container">
                <div
                    :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'facility-rooms' }]"
                    @click="router.push({ name: 'facility-rooms' })"
                >
                    会議室
                </div>
                <div
                    :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'facility-cars' }]"
                    @click="router.push({ name: 'facility-cars' })"
                >
                    車両
                </div>
                <div
                    :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'web-meetings' }]"
                    @click="router.push({ name: 'web-meetings' })"
                >
                    Web会議
                </div>
                <div
                    :class="['sub-tab-item', { 'selected-sub-tab': route.path.includes('/facilities/freee') }]"
                    @click="router.push({ name: 'freee-integration' })"
                >
                    freee
                </div>
            </div>
        </div>
        <router-view />
    </div>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
</script>

<style scoped>
.schedule-control {
    gap: 0;
}

.schedule-control__nav {
    margin: 20px 20px 0;
}
</style>
