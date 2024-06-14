<template>
    <div v-if="UserAllData" style="width: 100%;height:100%;left:0;top:55px;" class="user-conteiner-inner" :class="{scrollable : !showModalContent && !showSettingModalContent && !introUpload}">   
        
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
        <div style="position:relative;height: 100%;">
            
            <div style="height: 60px;display: flex;align-items: center;position: absolute;z-index:2;left: 0;top: 0;" v-if="responsive.mobile">
                <HamBurger/>
            </div>
            
            <div>
                <div v-if="UserAllData && auth.user && UserAllData !== null" class="row justify-content-center user-icon-content">  
                    <div class="user-three-menu">
                        <div @click.stop="menu.setMenu( {name:'userMenuList', id: 52})" v-if="UserAllData.id == auth.id">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" width="7" height="15" viewBox="0 0 7 32" style="margin:auto;">
                                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                            </svg>
                        </div>
                    </div>
                    <div id="userMenuList" class="boxMenu" v-if="UserAllData.id == auth.id && menu.id == 52 && menu.name == 'userMenuList'" style="z-index: 9;right: 40px;top: 32px;display: flex;flex-direction: column;">

                        <router-link class=" boxMenuItems menuLink" :to="{name: 'personal-info-settings'}">プロフィール編集</router-link>
                        <router-link v-if="!auth.isPartner && auth.user.user_code && !auth.isRegistered" class=" boxMenuItems menuLink" :to="{name: 'salary-issue'}">昇給課題</router-link>
                        
                    </div>
    
                    <UserIconEdit 
                        :UserAllData="UserAllData"
                        :clapData="clapData"
                        :movExist="movExist"
                        :key="movExist.length"
                        @updateUser="updateUser"
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
    </template>
    
<script setup>

import UserIconEdit from './UserEditComps/UserIconEdit.vue';
import HamBurger from '../Global/HamBurger.vue'
import 'swiper/css'
import Autolinker from 'autolinker';
import { computed, inject, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import UserPortfolio from './UserPortfolio.vue';
import UserPortfolioEdit from './UserPortfolioEdit.vue';
    const menu = useMenuStore()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const { notify } = inject('dialog')
    const route = useRoute()
    const showModalContent = ref(false)
    const showSettingModalContent = ref(false)
    const UserAllData = ref(null)
    const clapData = ref(null)
    const editingPortfolio = ref(null)
    const userPortfolio = computed(() => {
        return UserAllData.value.portfolio.filter(data => data.status == 3)
    })
    const movExist = computed(() => {
        return UserAllData.value.user_album
    })
    const urlCheck = (text) => {
        if(text){                
            var linkedText = Autolinker.link(text, {stripPrefix: false});       
            const catch_tag = '<a href=/app/public/user?id=' 
            const rep_tag = '<a class="mntuser" style="cursor:pointer" id=' 
            linkedText = linkedText.replaceAll(catch_tag, rep_tag);
            return linkedText;                
        }            
    }
    const updateUser = async(targetId) => {
        const id = targetId ? targetId : UserAllData.value ? UserAllData.value.id : null
        if(!id) return
        try{
            const response = await axios.post('/profile_get_update_user', {id: id})
            if(response.data && Object.hasOwn(response.data, 'id')){
                UserAllData.value = response.data
                if(UserAllData.value.id == auth.id){
                    auth.setUser(response.data)
                }
            }  
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const getClaps = async(targetId) => {
        const id = targetId ? targetId : UserAllData.value ? UserAllData.value.id : null
        if(!id) return
        try{
            const response = await axios.post('/get_user_claps', {id: id})
            if(response.data){
                    clapData.value = response.data
                }
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
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
    // watch(() => route.params.userId, (newUserId, oldUserId) => {
    //     if (newUserId !== oldUserId) {
    //       updateUser(newUserId);
    //       getClaps(newUserId);
    //     }
    //   },
    //   { immediate: true } 
    // )

    onMounted(() => {
        UserAllData.value = route.meta.data && Object.hasOwn(route.meta.data, 'id') ? route.meta.data : null;
        if (route.params.userId !== UserAllData.value.id) {
          updateUser(newUserId);
          getClaps(newUserId);
        }
    })  
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
