<template>
    <div class="h-full w-full relative bg-[var(--bg3)]" :class="{scrollable : !showModalContent && !showSettingModalContent }">   
        <Transition name="modalFade">
            <div class="work-loader" style="height: 100%; z-index: 2" v-if="loading">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <div v-if="UserAllData">
            <div style="position:relative;height: 100%;">
                <div style="height: 60px;display: flex;align-items: center;position: absolute;z-index:2;left: 0;top: 0;" v-if="responsive.mobile">
                    <HamBurger/>
                </div>
                <div>
                    <div v-if="UserAllData && auth.user && UserAllData !== null" class="row justify-content-center user-icon-content">  
                         
                        <UserIconEdit 
                            :UserAllData="UserAllData"
                            :clapData="clapData"
                            :movExist="movExist"
                            :key="movExist.length"
                            @updateUser="updateUser"
                            @openHistory="showRefreshHistory = true"
                        />
                        
                        <div v-if="UserAllData" class="second-bar relative">
                            <div v-if="UserAllData.id == auth.id" class="user-three-menu">
                                <ItemMenu :items="[
                                    {title: 'プロフィール編集', action:() => router.push({name: 'personal-info-settings'})},
                                ]"/>
                            </div>   
                            <div class="profile-fields">
                                <div v-if="UserAllData.positions !== null || UserAllData.offices !== null"
                                     class="flex flex-wrap gap-3">
                                    <div v-if="UserAllData.positions !== null" class="profile-badge">
                                        <span class="badge-label">役職</span>
                                        <span class="badge-value">{{ UserAllData.positions.name }}</span>
                                    </div>
                                    <div v-if="UserAllData.offices !== null" class="profile-badge">
                                        <span class="badge-label">営業所</span>
                                        <span class="badge-value">{{ UserAllData.offices.name }}</span>
                                    </div>
                                </div>

                                <div v-if="UserAllData.motto !== null" class="profile-row">
                                    <span class="row-label">好きな言葉</span>
                                    <p class="row-value">{{ UserAllData.motto }}</p>
                                </div>
                                <div v-if="UserAllData.enjoy !== null" class="profile-row">
                                    <span class="row-label">私の「楽」</span>
                                    <p class="row-value">{{ UserAllData.enjoy }}</p>
                                </div>
                                <div v-if="UserAllData.intro !== null" class="profile-row">
                                    <span class="row-label">自己紹介</span>
                                    <p class="row-value">{{ UserAllData.intro }}</p>
                                </div>
                                <div v-if="UserAllData.recommend !== null" class="profile-row">
                                    <span class="row-label">推し</span>
                                    <p class="row-value" v-html="urlCheck(UserAllData.recommend)"></p>
                                </div>

                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
        <div v-else style="display:flex;width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;color: var(--primary-color);">
            <p>メンバーが見つかりませんでした。</p>
        </div>
        <router-view v-slot="{ Component }">
            <transition name="modalFade">
                <component 
                    :is="Component" 
                    :user="UserAllData"
                    :UserAllData="UserAllData"
                    @close="closeModal"
                    @reload="updateUser"
                />
            </transition>
        </router-view>       
        <Transition name="modalFade">
            <UserPortfolioEdit v-if="editingPortfolio" :editTarget="editingPortfolio" @close="portfolioEditComplete"/>
        </Transition> 
        <Transition name="modalFade">
            <UserRefreshHistoryModal
                v-if="showRefreshHistory && UserAllData"
                :user-id="UserAllData.id"
                @close="showRefreshHistory = false"
            />
        </Transition>
        
    </div>

    
</template>
    
<script setup>

import UserIconEdit from './UserEditComps/UserIconEdit.vue';
import HamBurger from '../Global/HamBurger.vue'
import 'swiper/css'
import { computed, onMounted, provide, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import UserPortfolioEdit from './UserPortfolioEdit.vue';
import UserRefreshHistoryModal from './UserRefreshHistoryModal.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue'
import { urlCheck } from '@/utils/tools';
import { useApi } from '@/composables/api';
import ProfileIconUpdater from './ProfileIconUpdater.vue';
    const router = useRouter()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const route = useRoute()
    const showModalContent = ref(false)
    const showSettingModalContent = ref(false)
    const UserAllData = ref(null)
    const clapData = ref(null)
    const editingPortfolio = ref(null)
    const showRefreshHistory = ref(false)
    const api = useApi()
    const loading = ref(true)
    const editIconModal = ref(false)
    
    const userPortfolio = computed(() => {
        return UserAllData.value.portfolio.filter(data => data.status == 3)
    })
    const movExist = computed(() => {
        return UserAllData.value.user_album
    }) 
    const updateUser = async(targetId) => {
        const id = targetId ?? UserAllData.value?.id ?? null
        if(!id) return
  
        const data = await api.post('/profile_get_update_user', { id })
        if (data && 'id' in data) {
            UserAllData.value = data
            if (data.id === auth.id) auth.setUser(data)
        }  
        loading.value = false
    }
    const getClaps = async(targetId) => {
        const id = targetId ? targetId : UserAllData.value ? UserAllData.value.id : null
        if(!id) return
        const data = await api.post('/get_user_claps', {id: id})     
        data && (clapData.value = data)    
    }
    const closeModal = () => {
        showModalContent.value = false;
        showSettingModalContent.value = false;
    }
         
    const portfolioEditComplete = (flag) => {
        if(flag){
            updateUser()
        }
        editingPortfolio.value = null
    }
    watch(() => route.params.userId, (newUserId, oldUserId) => {
        if (newUserId !== oldUserId) {
          updateUser(newUserId);
          getClaps(newUserId);
        }
      },
      { immediate: true } 
    ) 
    provide('UserAllData', UserAllData)
</script>
<style lang="scss" scoped>
/* ── Profile fields ────────────────────────────────────── */
.profile-fields {
    display: flex;
    flex-direction: column;
    gap: 26px;
}

/* Chips for position / office */
.profile-badge {
    display: inline-flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 16px;
    border-radius: 4px;
    background: var(--background-color);
    min-width: 80px;
}

.badge-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--third-color);
    letter-spacing: 0.03em;
}

.badge-value {
    font-size: 15px;
    font-weight: 500;
    color: var(--primary-color);
    line-height: 1.3;
}

/* Text info rows */
.profile-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px 16px;
    border-radius: 4px;
    background: var(--background-color);
}

.row-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--third-color);
    letter-spacing: 0.03em;
}

.row-value {
    font-size: 14px;
    color: var(--primary-color);
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
    margin: 0;
}
/* ──────────────────────────────────────────────────────── */

.menuLink{
    text-decoration: none;
}
.menuLink:hover{
    text-decoration: none;
    font-weight: 400;
}
.recordFile-inner{
    overflow: hidden;
}
.refresh-history-button{
    padding: 7px 12px;
    border-radius: 8px;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 12px;
    line-height: 1.2;
}
.refresh-history-button:hover{
    opacity: 0.9;
}
.private-wrapper{
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}
.profile-info-box{
    margin-bottom: 20px;
}
.qr-exp{
    position: absolute;
    top: 35px;
    padding: 10px;
    line-height: 1.5;
    background: var(--menu-bg);
    max-width: 30vw;
    min-width: 20vw;
    font-size: 13px;
    font-weight: 400;
    box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.3) 0px 2px 6px 2px;
    color: var(--primary-color);
    white-space: break-spaces;
    display: none;
    left:0;
    z-index: 7;
}
.qr-exp-pb{
    position: absolute;
    top: 35px;
    padding: 10px;
    line-height: 1.5;
    background: var(--menu-bg);
    max-width: 30vw;
    min-width: 20vw;
    font-size: 13px;
    font-weight: 400;
    box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.3) 0px 2px 6px 2px;
    color: var(--primary-color);
    white-space: break-spaces;
    display: none;
    left:0;
    z-index: 10;
    z-index: 7;
}
.h-chip{
    
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50px;
    

}
.h-chip-pb{
    
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50px;
    

}
.h-chip-pb:hover .qr-exp-pb{
    display: block !important;

    


}
.h-chip-pb:hover{
    background: var(--primary-color);
    color: var(--background-color);   


}
.h-chip:hover .qr-exp{
    display: block !important;

    


}
.h-chip:hover{
    background: var(--primary-color);
    color: var(--background-color);   


}
.button-cover {
  height: 100px;
  margin: 20px;
  background-color: #fff;
  box-shadow: 0 10px 20px -8px #c5d6d6;
  border-radius: 4px;
}

.button-cover:before {
  counter-increment: button-counter;
  content: counter(button-counter);
  position: absolute;
  right: 0;
  bottom: 0;
  color: #d7e3e3;
  font-size: 12px;
  line-height: 1;
  padding: 5px;
}

.button-cover,
.knobs,
.layer {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.prpb-toggle {
  position: relative;
  top: 50%;
  width: 74px;
  height: 30px;
  overflow: hidden;
}

.prpb-toggle.r,
.prpb-toggle.r .layer {
  border-radius: 100px;
}



.checkbox {
  position: relative;
  width: 100%;
  height: 100%;
  padding: 0;
  margin: 0;
  opacity: 0;
  cursor: pointer;
  z-index: 3;
}

.knobs {
  z-index: 2;
}

.layer {
  width: 100%;
  background-color: var(--bg2);
  transition: 0.3s ease all;
  z-index: 1;
}

#button-10 .knobs:before,
#button-10 .knobs:after,
#button-10 .knobs span {
  position: absolute;
  top: 0;
  width: 50%;
  height: 100%;
  font-size: 10px;
  font-weight: bold;
  text-align: center;
  line-height: 30px;
  transition: 0.3s ease all;
}

#button-10 .knobs:before {
  content: "";
  left: 50%;
  background-color: var(--primary-color);
}

#button-10 .knobs:after {
  content: "NO";
  right: 0;
  color: var(--background-color);
}

#button-10 .knobs span {
  display: inline-block;
  left: 0;
  color: var(--background-color);
  z-index: 1;
}

#button-10 .checkbox:checked + .knobs span {
  color: var(--background-color);
}

#button-10 .checkbox:checked + .knobs:before {
  left: 0;
  background-color: var(--primary-color);
}

#button-10 .checkbox:checked + .knobs:after {
  color: var(--background-color);
}

.swiper-slide{
    max-width: 300px;
}
@media screen and (max-width: 959px) {

    .swiper-slide{
        width: fit-content !important;
    }

    .profile-info-box{
        display: block;
    }
    .qr-exp{
        max-width: 80vw;
        min-width: 70vw;
    }
    .phone-select{
        width: 100%;
    }
    .qr-u-box{
        display: inline-block;
    }
}
</style>
