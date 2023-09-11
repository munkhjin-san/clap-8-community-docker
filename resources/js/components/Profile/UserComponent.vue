<template>
    <div style="width: 100%;height:100%;left:0;top:55px;" class="user-conteiner-inner" :class="{scrollable : !showModalContent && !showSettingModalContent}">   
        
        <router-view v-slot="{ Component }">
            <transition name="modalFade">
                <component 
                    :is="Component" 
                    :user="UserAllData"
                    :UserAllData="UserAllData"
                    @close="closeModal"
                    @reload="updateUser"
                    :errorToast="errorToast"
                    @getUserInfo="getUserInfo"
                />
            </transition>
        </router-view>        
        <div style="position:relative;height: 100%;">
            <div style="height: 60px;
            display: flex;
            align-items: center;
            position: absolute;
            z-index:2;
            left: 0;
            top: 0;">
                <HamBurger/>
            </div>
            
            <div>
                <div v-if="UserAllData && $store.state.user && UserAllData !== null" class="row justify-content-center user-icon-content">  
                    <div class="user-three-menu">

                    
                        <div v-if="isAccessible" @click="introduceWindow = true" style="right:60px">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" height="13" class="dot-menu" viewBox="0 0 32 32">
                                <path d="M32.088 26.495c-0.044-0.117-0.103-0.22-0.161-0.338-0.059-0.103-0.117-0.22-0.176-0.308l-0.103-0.147-0.044-0.073-0.073-0.088c-0.088-0.117-0.191-0.235-0.279-0.352l-0.117-0.132-0.015-0.015c-0.073-0.059-0.132-0.103-0.206-0.161l-0.088-0.059-0.22-0.132c-0.103-0.059-0.206-0.117-0.323-0.176-0.103-0.059-0.22-0.117-0.338-0.161-0.455-0.191-0.969-0.294-1.483-0.308-0.998-0.029-2.011 0.338-2.76 1.028-0.044 0.029-0.088 0.044-0.132 0.015l-2.598-1.321-3.039-1.512-3.039-1.483-3.053-1.468c-1.028-0.484-2.040-0.954-3.068-1.439-0.881-0.411-1.776-0.822-2.672-1.218-0.088-0.044-0.132-0.132-0.132-0.235 0.015-0.117 0.029-0.235 0.029-0.367s0-0.279-0.015-0.411c-0.015-0.088 0.044-0.176 0.132-0.22 0.881-0.396 1.761-0.807 2.642-1.218 1.028-0.484 2.055-0.954 3.068-1.439l3.053-1.468 3.039-1.483 3.039-1.512 2.598-1.321c0.044-0.029 0.103-0.015 0.132 0.015 0.749 0.675 1.761 1.042 2.76 1.028 0.514-0.015 1.028-0.132 1.483-0.308 0.117-0.044 0.22-0.103 0.338-0.147 0.103-0.059 0.22-0.103 0.323-0.176l0.22-0.132 0.088-0.059c0.073-0.044 0.132-0.103 0.206-0.161l0.015-0.015 0.117-0.132c0.103-0.117 0.191-0.235 0.279-0.352l0.073-0.088 0.044-0.073 0.103-0.147c0.073-0.103 0.117-0.205 0.176-0.308s0.117-0.22 0.161-0.323c0.191-0.455 0.308-0.954 0.323-1.483 0.029-1.042-0.382-2.099-1.116-2.862-0.367-0.382-0.807-0.675-1.306-0.895-0.484-0.205-1.028-0.323-1.556-0.323s-1.057 0.103-1.527 0.279-0.91 0.44-1.292 0.719l-0.132 0.088-0.088 0.132c-0.279 0.382-0.543 0.807-0.719 1.292-0.176 0.47-0.279 0.998-0.279 1.527 0 0.117 0 0.235 0.015 0.352 0 0.059-0.029 0.103-0.073 0.117-0.881 0.396-1.747 0.807-2.628 1.218l-3.068 1.439-3.053 1.468-3.009 1.439c-1.013 0.499-2.026 1.013-3.024 1.512-0.851 0.426-1.688 0.851-2.525 1.292-0.147 0.073-0.338 0.059-0.47-0.044-0.294-0.235-0.602-0.426-0.939-0.572-0.484-0.206-1.028-0.323-1.556-0.323s-1.057 0.103-1.527 0.279-0.91 0.426-1.292 0.719l-0.147 0.103-0.088 0.117c-0.294 0.382-0.543 0.807-0.719 1.292-0.176 0.47-0.279 0.998-0.279 1.527 0 1.072 0.455 2.128 1.204 2.862 0.749 0.749 1.82 1.145 2.862 1.116 0.514-0.015 1.028-0.132 1.483-0.308 0.117-0.044 0.22-0.103 0.338-0.147 0.103-0.059 0.22-0.103 0.323-0.176l0.147-0.103 0.073-0.044 0.088-0.073 0.029-0.029c0.103-0.073 0.235-0.088 0.352-0.029 0.881 0.455 1.747 0.895 2.628 1.336 1.013 0.499 2.011 1.013 3.024 1.512l3.039 1.483 3.053 1.468 3.068 1.439c0.866 0.396 1.732 0.807 2.598 1.204 0.059 0.029 0.103 0.088 0.088 0.161-0.015 0.103-0.015 0.206-0.015 0.323 0 0.528 0.103 1.057 0.279 1.527s0.426 0.91 0.719 1.292l0.088 0.132 0.132 0.088c0.382 0.279 0.807 0.543 1.292 0.719 0.47 0.176 0.998 0.279 1.527 0.279s1.057-0.103 1.556-0.323c0.484-0.206 0.939-0.514 1.306-0.895 0.734-0.749 1.145-1.82 1.116-2.862 0-0.499-0.117-0.998-0.308-1.453z"></path>
                            </svg>
                        </div>
                        <div @click.stop="$store.commit('setMenu', {name:'userMenuList', id: 52})" v-if="UserAllData.id == $store.state.user.id">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" width="7" height="15" viewBox="0 0 7 32" style="margin:auto;">
                                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                            </svg>
                        </div>
                    </div>
                    <div id="userMenuList" class="boxMenu" v-if="UserAllData.id == $store.state.user.id && $store.state.menu.id == 52 && $store.state.menu.name == 'userMenuList'" style="z-index: 9;right: 40px;top: 32px;">
                        <ul>
                            <li @click="profileEdit()" class="boxMenuItems cursor-pointer">{{$t('profileEdit')}}</li>
                            <li @click="settingEdit()" class="boxMenuItems cursor-pointer">{{$t('personalEdit')}}</li>
                        </ul>
                    </div>
    
                    <UserIconEdit 
                        :UserAllData="UserAllData"
                        :deviceWidth="deviceWidth"
                        :isAccessible="isAccessible"
                        @updateUser="updateUser"
                    />
                    
                    <div v-if="UserAllData && UserAllData.user_detail" class="second-bar" @click="userMenuToggle=false" >
                        <div class="record-area" style="padding-right: 20px;" v-if="isAccessible">
                            
                            <div v-if="UserAllData.user_detail && UserAllData.user_detail.company" class="profile-info-box">
                                <!-- <div class="title">
                                    <p class="record-inner">{{ $t('company') }}</p>
                                </div> -->
                                <div class="record">
                                    <p >{{UserAllData.user_detail.company}}</p>
                                </div>
                            </div>
                            <div v-if="UserAllData.user_detail && UserAllData.user_detail.occupation" class="profile-info-box">
                                <!-- <div class="title">
                                    <p class="record-inner">{{ $t('occupation') }}</p>
                                </div> -->
                                <div class="record">
                                    <p >{{UserAllData.user_detail.occupation}}</p>
                                </div>
                            </div>
                            <div v-if="UserAllData.user_detail && UserAllData.user_detail.intro" class="profile-info-box">
                                <!-- <div class="title">
                                    <p class="record-inner">{{ $t('intro') }}</p>
                                </div> -->
                                <div class="record" style="word-break:break-word;white-space: break-spaces;">
                                    <p style="line-height:1.8">{{UserAllData.user_detail.intro}}</p>
                                </div>
                            </div>                               
                            <!-- <div class="profile-info-box qr-u-box" v-if="UserAllData.id == $store.state.user.id">
                                <div class="title" style="display: flex;align-items:center;position: relative;">
                                    <p class="record-inner">{{ $t('qrCode') }}</p>
                                    <div class="h-chip">?
                                        <div class="qr-exp">{{ $t('qrExplaination')}}<br>{{ $t('qrCaution')}}</div>
                                    </div>                                  
                                    
                                </div>
                                                            
                            </div>           -->
                                
                        </div>
                        <div v-else class="private-wrapper">
                            <p>{{$t('privateAccount')}}</p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <UserQRCode v-if="introduceWindow" :user="UserAllData" :loading="false" @close="introduceWindow = false"/>
        </Transition>
    </div>
    </template>
    
<script>

import UserInfoEdit from './UserEditComps/UserInfoEdit.vue';
import UserIconEdit from './UserEditComps/UserIconEdit.vue';
import UserSettingEdit from './UserEditComps/UserSettingEdit.vue';
import UserQRCode from './UserQRCode.vue'
import HamBurger from '../Global/HamBurger.vue'
export default {
    data() {
        return {
            
            userMenuToggle: false,
            showModalContent: false,
            showSettingModalContent: false,
            qrExplainView: false,            
            deviceWidth: 0,
            UserAllData: null,
            qrLock: false,
            introduceWindow: false
        }
    },
    components:{
        UserInfoEdit,
        UserIconEdit,
        UserSettingEdit,
        UserQRCode,
        HamBurger
    },
    created(){
        const data = {
            active: false,
            userList: [],
            title: ''
        }
        this.$store.commit('setMessageUsers', data)
    },
    mounted() {
        this.UserAllData = this.$route.meta.data;

        document.body.style.height = '100%';
        document.body.style.position = 'fixed';
        document.body.style.overflow = 'hidden';
        if(this.$store.state.mobile){
            document.body.style.background = 'var(--background-color)'
        }
        
        this.deviceWidth = window.innerWidth
        window.addEventListener('resize', this.handleResize)  
        // this.getTags()
    },   
    beforeUnmount(){
        window.removeEventListener('resize', this.handleResize)
    },    
    computed:{
        isAccessible(){
            if(this.UserAllData){
                if(this.UserAllData.id == this.$store.state.user.id){
                    return true
                }else if(this.UserAllData.is_public){
                    return !this.UserAllData.is_blocked_by
                }else{
                    return !this.UserAllData.is_blocked_by && this.UserAllData.is_friend
                }
            }
            return false
        }
    },
    watch: {
        '$route.params.userId': {
            immediate: true,
            handler(newUserId, oldUserId) {
                if (newUserId !== oldUserId) {
                    this.updateUser(newUserId, 1)
                }
            }
        }
    },
    methods: {        
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })   
        },
        
        getUserInfo(){
            this.updateUser();
            // this.getTags();
        },
        updateUser(targetId, shouldUpdateTags){
                const id = targetId ? targetId : this.UserAllData ? this.UserAllData.id : null
                if(!id) return
                axios.post('/profile_get_update_user', {id: id}).then(               
                response => {
                    if(response.data){
                        this.UserAllData = response.data
                        if(this.UserAllData.id == this.$store.state.user.id){
                            this.$store.commit('setUser', response.data);
                            if(shouldUpdateTags){
                                // this.getTags();
                            }
                            
                        }
                        window.document.title = `GLOWD - ${response.data.name}`; 
                    }                    

                });
        },
        
        profileEdit(){
            // this.userMenuToggle = false;
            // this.userMenuToggle;
            // this.showModalContent = true;
            this.$router.push({name: 'personal-info-settings'})
            this.$store.commit('setMenu', {name:'', id: null})
        },
        settingEdit(){
            this.$router.push({name: 'account-settings'})
            this.$store.commit('setMenu', {name:'', id: null})
            // this.userMenuToggle;
            // this.showSettingModalContent = true;
        },
        closeModal () {
            this.showModalContent = false;
            this.showSettingModalContent = false;
        },
        
        handleResize(){
            this.deviceWidth = window.innerWidth
        },      
        
    },
}
</script>
<style lang="scss">
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


@media screen and (max-width: 959px) {

    

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
