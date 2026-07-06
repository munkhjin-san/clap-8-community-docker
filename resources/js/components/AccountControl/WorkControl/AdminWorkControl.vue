<template>
    <div class="admin-window" style="gap: 0">
        <div class="admin-command-bar" style="margin: 20px 20px 0 20px;">            
            <div class="sub-tab-container">
                <div @click="router.push({name: 'attendance'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'attendance'}]">勤怠管理</div>
                <div @click="router.push({name: 'receipt-audit'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'receipt-audit'}]">領収書監査</div>
                <!-- <div @click="router.push({name: 'workgroup'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'workgroup'}]">グループ管理 </div>                 -->
                <div @click="router.push({name: 'paidholiday'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'paidholiday'}]">計画有給管理</div>
                <div @click="router.push({name: 'paid-leave-rules'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'paid-leave-rules'}]">有休ルール</div>
                <div @click="router.push({name: 'paid-leave-ledger'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'paid-leave-ledger'}]">有休台帳</div>
                <div @click="router.push({name: 'shift-types'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'shift-types'}]">シフト種別</div>
            </div>
                    
        </div>
        <router-view></router-view>
    </div>
</template>
<script setup lang="ts">
    import { ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    const keywords = ref('')
    const route = useRoute()
    const router = useRouter()
</script>
