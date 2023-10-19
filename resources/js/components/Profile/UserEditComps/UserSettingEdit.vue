<template>
    <div @mousedown="closeModal" class="overlay" v-if="user">
        <Form ref="form" v-slot="{ errors }" style="width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;">  
            <div id="editModal01" class="chatCreate" ref="editModal01" @mousedown.stop style="overflow: hidden auto;">
                <div v-if="step == 0">
                    <div @click="$router.go(-1)" class="m-close-button" v-if="!$store.state.mobile" style="z-index:22">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                    <div @click="$router.go(-1)" class="m-close-button" style="z-index:22;right: auto;left: 10px;" v-else>
                        <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>
                        
                    </div>
                    <h1 :style="{margin: $store.state.mobile ? '2px 0 0 40px' : '0'}">{{$t('personalEdit')}}</h1>
                </div>
                <div style="display:flex;align-items: center;">
                    <div v-if="step > 0" @click="step = 0" class="m-close-button" style="left:10px;right: auto;">
                        <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg> 
                    
                    </div>
                    <h1 class="subMenuTitle">{{ settingTitle }}</h1>
                </div>            
                <Transition name="page-transition" mode="out-in">
                <div v-if="step == 0">  
                    <!-- <div>
                        <div class="title" style="display: flex;align-items:center;position: relative;">
                            <p class="record-inner">{{ $t('color') }}:</p>
                            
                            <div class="h-chip-pb">?
                                <div class="qr-exp-pb">{{ $t('colorExplaination')}}</div>
                            </div>       
                            
                            
                        </div>   
                        <div class="c-picker" style="position: unset;width: fit-content;padding: 0;">                                                        
                            <div @click="setSelectedColor(color.id)" :id="color.name" :class="color.code" :key="'color_' + color.id" v-for="color in avialableColors" :style="{background: $store.state.dark ? color.dark : color.light}">
                                <div v-if="user.color == color.id">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :fill="$store.state.dark ? '#8d8d8d' : '#000'">
                                        <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>                            
                    </div>   -->
                    <div style="margin-top:25px">
                        <div @click="step = 1" class="suggested-wrap p-setting-item" v-html="$t('updatePassword')"></div>
                        <div @click="step = 2" class="suggested-wrap p-setting-item" v-html="'カラー設定'"></div>
                        <div @click="step = 3" class="suggested-wrap p-setting-item" v-html="'マイサイン'"></div>
                    </div>
                    
                </div>
                <div v-else-if="step==1" style="margin-top:20px">
                    <div class="pw-reset" style="margin-top: 20px;">
                        
                        <div style="position:relative">    
                            <span class="form-plc smallPlc" style="background-color:var(--background-color);">{{ $t('currentPassword') }}</span>                            
                            <Field rules="required" @focus="$store.commit('setActiveInput', 'currentPassword')" @blur="$store.commit('setActiveInput', '')" id="currentPassword" autocomplete="off" class="recordText slide-plc" v-model="currentPassword" type="password" name="currentPassword" />   
                            <span class="form-error">{{ errors.currentPassword }}</span>
                        </div>  
                    </div> 
                    <div class="pw-reset">
                        
                        <div style="position:relative">      
                            <span class="form-plc smallPlc" style="background-color:var(--background-color);">{{ $t('newPassword') }}</span>                          
                            <Field id="newPassword" @focus="$store.commit('setActiveInput', 'newPassword')" @blur="$store.commit('setActiveInput', '')" rules="required|passwordCase|min:8" autocomplete="off" class="recordText slide-plc" v-model="newPassword" type="password" name="newPassword" />   
                            <span class="form-error">{{ errors.newPassword }}</span>
                        </div>  
                    </div> 
                    <div class="pw-reset">
                        
                        <div style="position:relative">       
                            <span class="form-plc smallPlc" style="background-color:var(--background-color);">{{ $t('newPasswordConfirm') }}</span>                         
                            <Field id="newPasswordConfirm" @focus="$store.commit('setActiveInput', 'newPasswordConfirm')" @blur="$store.commit('setActiveInput', '')" rules="confirmed:@newPassword" autocomplete="off" class="recordText slide-plc" v-model="newPasswordConfirm" type="password" name="newPasswordConfirm" />   
                            <span class="form-error">{{ errors.newPasswordConfirm }}</span>
                        </div>  
                    </div> 
                    <div style="text-align: center;margin-top: auto;padding-top: 30px;">
                        <LoaderButton @triggered="reset" :loading="loader" :content="$t('save')"/>
                    </div>


                </div>
                <div v-else-if="step==2" style="margin-top: 20px;">
                    <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;" >
                        <div class="chosen-div" :style="{backgroundColor: isColor.light}"></div>
                        <div v-for="(color, index) in avialableColors" :key="index">
                            <div @click="chooseColor(color)" class="color-div cursor-pointer" :style="{backgroundColor: color.light}">
                            </div>
                        </div>
                        
                    </div>
                    <div style="text-align: center;margin-top: auto;padding-top: 30px;">
                        <LoaderButton @triggered="setSelectedColor" :loading="loader" :content="$t('save')"/>
                    </div>
                </div>
                <div v-else-if="step==3">
                    <UserSignature 
                        :user="user"
                        @reload="$emit('reload')"
                    />
                </div> 
                </Transition>                    
            </div>
        </Form>
    </div>
</template>

<script>
import colors from '../../../../assets/colors.json'
import LoaderButton from '../../Global/LoaderButton.vue'
import { Field, Form  } from 'vee-validate'
import UserSignature from './UserSignature.vue'
    export default {
        props: ['user', 'errorToast'],
        emits: ['close', 'reload'],
        data(){
            return{
                privacySetting:[
                    { label: this.$t('userPublic'), value: 1, id:"public", info: this.$t('userPublicInfo')},
                    { label: this.$t('userPrivate'), value: 0, id:"private", info: this.$t('userPrivateInfo')},
                ],
                toggleDisabled: false,                
                privateSetting: this.user && this.user.is_public ? 1 : 0,
                step: 0,
                currentPassword: '',
                newPassword: '',
                newPasswordConfirm: '',
                loader: false,
                avialableColors: colors,
                qrLock: false,
                phone_login: '',
                email_login: '',
                login_token: '',
                country_code: '+81',
                emailMessage: '',
                phoneMessage: '',
                verification_code: '',
                verifyError: '',
                newCode: '',
                mailChange: false,
                chosenColor: this.user && this.user.color ? this.user.color : (this.avialableColors ? this.avialableColors[0].id : ''),
                
            }
        },
       
       
        computed:{
            isColor(){
                return this.avialableColors ? this.avialableColors.find(ob => ob.id == this.chosenColor) : ''
            },
            settingTitle(){
                switch (this.step) {
                    case 3:
                        return 'マイサイン'
                    case 2:
                        return 'カラー設定';
                    case 1:
                        return this.$t('updatePassword');
                    
                    default:
                        return ''
                }
            }
        },
        components:{
            LoaderButton,
            Field,
            Form,
            UserSignature
        },
        methods:{
            
            chooseColor(color){
                this.chosenColor = color.id
            },
            
            async reset(){
                const result = await this.$refs.form.validate();
                if(!result.valid) {
                    return
                }
                const d = {
                    current: this.currentPassword,
                    password: this.newPassword,
                    password_confirmation: this.newPasswordConfirm
                }
                axios.post('/user_pass_change_api', d).then(response => {
                    this.errorToast(this.$t(response.data.message))   
                    if(response.status == 200){
                        this.step = 0
                        this.currentPassword = this.newPassword = this.newPasswordConfirm =  ''
                    }
                                
                }).catch(function (error) { 
                    console.log(error)               
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t(error.message))   
                }.bind(this));
            },
            closeModal(){
                if (!this.$refs.editModal01.contains(event.target)) {
                    this.$emit('close')
                }
            },
            setSelectedColor(){
                if(this.loader) return
                this.loader = true
                axios.post('/profile_set_color', {value: this.chosenColor}).then(response => { 
                    this.loader = false
                    this.step = 0     
                    this.$emit('reload')    
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.loader = false
                }.bind(this));
            }
        }
    }
</script> 
<style lang="scss" scoped>
    .subMenuTitle{
        position: absolute;
        margin-left: 25px;   

    }
    .step2__header{
        margin-top: -28px; 
        margin-left: 20px;
    }
    .select-wrapper{
        width: 100%;
    }
    .login_step2{
        margin-top: 20px;
    }
    .notAllowed{
        cursor: not-allowed !important;
    }
    .deleteMark {
        position: absolute;
        fill: var(--primary-color);
        right: 0;
        cursor: pointer;
        background: var(--formBorder);
        height: 100%;
        padding: 0 15px;
    }
    input:autofill {
        -webkit-text-fill-color: var(--primary-color);
    }
    .pw-reset{
        margin-bottom: 30px;
    }
    .verified-text{
        font-size: 12px;
        color: inherit;
    }
    .form-error{
        position: absolute; 
        bottom: -10px;
        font-size: 11px;
        color: inherit;
        left: 0;
        color:tomato;
    }
    .p-setting-item{        
        margin: 0 -10px !important;
        padding: 15px;
    }
    .p-danger:hover{
        color: #f53b3b !important;
    }
    .page-transition-enter {
        opacity: 0;
        transform: translateX(100%);
    }

    .page-transition-enter-active {
        opacity: 1;
        transform: translateX(0);
        transition: all 0.2s ease;
    }

    /* Leave transition */
    .page-transition-leave {
        opacity: 1;
        position: absolute;
        top: 0;
        width: 100%;
    }

    .page-transition-leave-active {
        opacity: 0;
        transform: translateX(-100%);
        transition: all 0.3s ease-in;
    }
    .c-picker{
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        grid-template-rows: repeat(3, 1fr);
        gap: 10px;
    }
    .c-picker > div {
        min-width: 25px;
        min-height: 25px;
        display: flex;
        align-items: center;
        place-content: center;
    }
    
    .separator {
        display: flex;
        align-items: center;
        padding-top: 10px;
        width: 120px;
        margin: 0 auto 10px;
    }

    .separator-line {
        flex: 1;
        height: 1px;
        margin: 0 auto;
        border-top: 1px solid #ccc;
    }
    .secondary-button{
        background-color: #8888 !important;
    }
    .separator-text {
        font-size: 12px;
    }
    .recordText option{
        background-color: var(--background-color);
    }
    .sendAgain-link{
        color: var(--primary-color);
        font-size: 12px;
        cursor: pointer;
    }
    .country_option{
        width:120px;
        border-right:none;
        padding: 9px;
    }
    input[type="radio"] {
        -webkit-appearance: none;
        appearance: none;
        background-color: #f1f1f1;
        border: 1px solid rgb(0, 0, 0);
        border-radius: 50%;
        min-height: 20px;
        min-width: 20px;
        width: 20px;
        height: 20px;
        outline: none;
        transition: all 0.3s;
        position: relative;
        cursor:pointer;
     }
     
     input[type="radio"]:checked::before {
        content: "";
        background-color: black;
        border-radius: 50%;
        height: 10px;
        position: absolute;
        width: 10px;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        margin: auto;
     }
     .color-div{
        width: 30px;
        height: 30px;
     }
     .chosen-div{
        width: 40px;
        height: 40px;
     }
    @media screen and (max-width: 959px) {
        .subMenuTitle{
            position: absolute;
            margin-left: 35px;
            margin-top: 22px;       

        }
        .login_step2{
            margin-top: 40px;
        }
        .step2__header{
            margin-top: -38px;
            margin-left: 25px;
            margin-right: 25px;
        }
        input[type="radio"]:checked::before{
            top: 0;
        }
    }
</style>
