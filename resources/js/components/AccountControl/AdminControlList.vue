<template>
    <div class="adminList-wrapper">        

        <div class="admin-header">            
            <Hamburger v-if="responsive.mobile"/>
            <div v-if="auth.isAdmin || auth.isBoss || auth.isPM" class="admin-tab-container">
                    <div class="pc" style="font-size: 16px;margin: 20px 0px 0px 15px;padding-bottom: 10px;"></div>
                    <div v-if="auth.isAdmin" class="admin-tab-container">
                        <div class="admin-tab-item" @click="router.push({name: 'account'})" :class="{'selected-tab' : route.name == 'account' }">コミュニティ</div>
                        <div class="admin-tab-item" @click="router.push({name: 'community-permissions'})" :class="{'selected-tab' : route.name == 'community-permissions' }">権限</div>
                        <div class="admin-tab-item" @click="router.push({name: 'attendance'})" :class="{'selected-tab' : route.path.includes('workcontrol')}">タイムシート</div>
                    <div class="admin-tab-item" @click="router.push({name: 'learningcontrol'})" :class="{'selected-tab' : route.path.includes('learningcontrol')}">ラーニング</div>
                    <div class="admin-tab-item" @click="router.push({name: 'projectlist'})" :class="{'selected-tab' : route.path.includes('projectcontrol')}">プロジェクト</div>
                    <div class="admin-tab-item" @click="router.push({name: 'glowdnine'})" :class="{'selected-tab' : route.name == 'glowdnine'}">グラウドナイン</div>
                    <div class="admin-tab-item" @click="router.push({name: 'custom-form-control'})" :class="{'selected-tab' : route.path.includes('custom-form-control')}">フォーム</div>
                    <div class="admin-tab-item" @click="router.push({name: 'refresh-control'})" :class="{'selected-tab' : route.path.includes('refresh-control')}">リフレッシュ</div>
                    <div class="admin-tab-item" @click="router.push({name: 'employee-change-applications'})" :class="{'selected-tab' : route.name == 'employee-change-applications'}">各種届出</div>
                    <div class="admin-tab-item" @click="router.push({name: 'admin-offices'})" :class="{'selected-tab' : route.name == 'admin-offices'}">営業所</div>
                    <!-- <div class="admin-tab-item" @click="router.push({name: 'admin-ai'})" :class="{'selected-tab' : route.name == 'admin-ai'}">AI</div>
                    <div class="admin-tab-item" @click="router.push({name: 'cost-master'})" :class="{'selected-tab' : route.name == 'cost-master'}">コスト</div>
                    <div class="admin-tab-item" @click="router.push({name: 'actual-results'})" :class="{'selected-tab' : route.name == 'actual-results'}">実績</div> -->
                </div>
                <div v-if="auth.isBoss || auth.isPM" class="admin-tab-item" @click="router.push({name: 'custom-form-control'})" :class="{'selected-tab' : route.path.includes('custom-form-control')}">フォーム</div>
                <div v-if="auth.id === 494" class="admin-tab-item" @click="router.push({name: 'learningcontrol'})" :class="{'selected-tab' : route.path.includes('learningcontrol')}">ラーニング</div>
            </div>
        </div>
        <div class="admin-content-area" v-if="auth.isAdmin || auth.isBoss || (auth.isPM && route.path.includes('custom-form-control'))">
            <router-view></router-view>
        </div>
        <div v-else style="height: 100%;width: 100%;text-align: center;justify-content: center;display: flex;align-items: center;flex-direction: column;">
            <p>アクセス権限ありません。</p>
            <router-link class="l-button" style="margin: 30px 0 70px 0;" to="/board">チャットへ戻る</router-link>
        </div>
    </div>
    
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import Hamburger from '../Global/HamBurger.vue'
import { useResponsive } from '@/store/responsive';
import { useAuthUserStore } from '@/store/auth'
    const router = useRouter()
    const route = useRoute()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
</script>
