<template>
    <div style="width: 100%;height:100%;" class="user-conteiner-inner relative" :class="{scrollable : !showModalContent && !showSettingModalContent }">   
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
                        <div v-if="UserAllData.id == auth.id" class="user-three-menu">
                            <ItemMenu :items="[
                                {title: 'プロフィール編集', action:() => router.push({name: 'personal-info-settings'})},
                            ]"/>
                        </div>    
                        <UserIconEdit 
                            :UserAllData="UserAllData"
                            :clapData="clapData"
                            :movExist="movExist"
                            :key="movExist.length"
                            @updateUser="updateUser"
                            @openHistory="showRefreshHistory = true"
                        />
                        
                        <div v-if="UserAllData" class="second-bar">
                            <div class="record-area" style="padding-right: 20px;">
                                <div style="display:flex; gap: 10px; margin-bottom: 10px;">
                                    <p class="record-inner title">役職</p>
                                    <p class="record-inner record" v-if="UserAllData.positions !== null">{{UserAllData.positions.name}}</p>
                                </div>
                                <div style="display:flex; gap: 10px; margin-bottom: 10px;">
                                    <p class="record-inner title">営業所</p>
                                    <p class="record-inner record" v-if="UserAllData.offices !== null">{{UserAllData.offices.name}}</p>
                                </div>
                                <!-- <div v-if="canViewRefreshHistory" style="margin-bottom: 14px;">
                                    <button class="refresh-history-button" @click="showRefreshHistory = true">リフレッシュ履歴</button>
                                </div> -->
                                <div v-if="UserAllData.motto !== null">
                                    <p class="record-inner title">好きな言葉</p>
                                    <p class="record-inner record">{{UserAllData.motto}}</p>
                                </div>
                                <div v-if="UserAllData.enjoy !== null">
                                    <p class="record-inner title">私の「楽」</p>
                                    <p class="record-inner record">{{UserAllData.enjoy}}</p>
                                </div>
                                <div v-if="UserAllData.intro !== null">
                                    <p class="record-inner title">自己紹介</p>
                                    <p class="record-inner record">{{UserAllData.intro}}</p>
                                </div>
                                <div v-if="UserAllData.recommend !== null">
                                    <p class="record-inner title">推し</p>
                                    <p class="record-inner record" v-html="urlCheck(UserAllData.recommend)"></p>
                                </div>
                                <div v-if="userPortfolio && userPortfolio.length">
                                    <p class="record-inner title" style="margin-bottom: 10px;">ポートフォリオ</p>
                                    <UserPortfolio class="record" @editPortfolio="editingPortfolio = portfolio" v-for="portfolio in userPortfolio" :portfolio="portfolio" @reload="updateUser"/>
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
import UserPortfolio from './UserPortfolio.vue';
import UserPortfolioEdit from './UserPortfolioEdit.vue';
import UserRefreshHistoryModal from './UserRefreshHistoryModal.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue'
import { urlCheck } from '@/utils/tools';
import { useApi } from '@/composables/api';
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
