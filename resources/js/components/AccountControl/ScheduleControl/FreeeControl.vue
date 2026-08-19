<template>
    <div class="freee-control">
        <div class="freee-control__nav">
            <div
                :class="['freee-control__nav-item', { 'is-selected': route.name === 'freee-integration' }]"
                @click="router.push({ name: 'freee-integration' })"
            >
                連携設定
            </div>
            <div
                :class="['freee-control__nav-item', { 'is-selected': route.name === 'freee-partners' }]"
                @click="router.push({ name: 'freee-partners' })"
            >
                取引先
            </div>
        </div>
        <div class="freee-control__body">
            <router-view />
        </div>
    </div>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
</script>

<style scoped>
/* ScheduleControl の縦フレックス内に入るので、height:100% ではなく flex で埋める。
   min-height:0 が無いと中身がはみ出してスクロールしなくなる。 */
.freee-control {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--bg3);
}

.freee-control__nav {
    display: flex;
    gap: 18px;
    padding: 14px 20px 0;
}

.freee-control__nav-item {
    padding: 0 2px 7px;
    border-bottom: 1px solid transparent;
    color: gray;
    font-size: 13px;
    line-height: 1.4;
    cursor: pointer;
    user-select: none;
}

.freee-control__nav-item.is-selected {
    border-bottom-color: var(--primary-button);
    color: var(--text-color);
}

.freee-control__body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
}
</style>
