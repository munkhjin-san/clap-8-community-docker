<template>
    <div v-if="user" class="post-root" style="background: var(--background-color);color: var(--primary-color);">
        <div v-if="step == 0" style="height: 60px;display: flex; align-items: center;">
            <HamBurger v-if="$store.state.mobile"/>
            <div :style="{marginLeft: $store.state.mobile ? '0' : '30px'}">設定</div>
        </div>
        <Form ref="form" v-slot="{ errors }" style="padding: 0 15px;">  
            <div id="editModal01" ref="editModal01" @mousedown.stop style="overflow: hidden auto;">
                
                <div v-if="step > 0" class="recordFormTitle">    
                    <div @click="step = 0" style="margin: 2px 10px 0 0;cursor: pointer;">
                        <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>                    
                    </div>     
                    <p>{{ settingTitle }}</p>   
                </div>  
                <div v-if="step == 0">  
                    <div >
                        <div @click="step = 1" class="suggested-wrap p-setting-item" v-html="$t('updatePassword')"></div>
                        <div @click="step = 2" class="suggested-wrap p-setting-item" v-html="'カラー設定'"></div>
                        <div @click="step = 3" class="suggested-wrap p-setting-item" v-html="'マイサイン'"></div>
                        <div v-if="[540, 608 ,516, 604].includes($store.state.user.id)" @click="step = 4" class="suggested-wrap p-setting-item" v-html="'カレンダー設定'"></div>
                        <div @click="step = 5" class="suggested-wrap p-setting-item" v-html="'テーマ設定'"></div>
                        <div v-if="$store.state.mobile" @click="step = 6" class="suggested-wrap p-setting-item" v-html="'フッターメニュー表示'"></div>
                        <div @click="logoutConfirm" class="suggested-wrap p-setting-item" v-html="'ログアウト'"></div>
                    </div>
                    
                </div>
                <div v-else-if="step==1" class="height100-div">
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
                    <div style="text-align: center;margin-top: auto;margin-bottom: 30px;">
                        <LoaderButton @triggered="reset" :loading="loader" :content="$t('save')"/>
                    </div>


                </div>
                <div v-else-if="step==2" class="height100-div">
                    <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;" >
                        <!-- <div class="chosen-div" :style="{backgroundColor: isColor.light}"></div> -->
                        <div :class="['color-item-parent', {'selected-color' : chosenColor == color.id}]" v-for="(color, index) in avialableColors" :key="index">
                            <div @click="chooseColor(color)" class="color-div cursor-pointer" :style="{backgroundColor: color.light}">
                            </div>
                        </div>
                        
                    </div>
                    <div style="text-align: center;margin-top: 30px;margin-bottom: 30px;">
                        <LoaderButton @triggered="setSelectedColor" :loading="loader" :content="$t('save')"/>
                    </div>
                </div>
                <div v-else-if="step==3" style="height:100%;">
                    <UserSignature 
                        :user="user"
                        @reload="updateSignature"
                    />
                </div> 
                <div v-else-if="step==4" style="height:100%;">
                    <div>iCalendar出力</div>
                    <div style="padding: 15px;line-height: 1.5;font-size: 14px;background: var(--bg3);margin: 20px 0;display: flex;flex-wrap: wrap;" v-if="icalUrl.status">
                        <div ref="icalUrl" style="user-select: all;">{{ icalUrl.url }}</div>
                        <button @click.prevent="copyUrl" style="height: fit-content;margin-left: auto;user-select: none;" class="commentEditButton">コピー</button>
                    </div>
                    <LoaderButton content="URL生成" :loading="urlCreating" @triggered="createUrl"/>
                </div> 
                <div v-else-if="step==5" style="height:100%;">
                    <div style="padding: 10px 0px;display: flex">
                        <input class="fish-eye" v-model="dark" type="radio" id="theme_0" name="theme" :value="0">
                        <label style="margin-left:10px;cursor:pointer" for="theme_0">ブラウザと同じ</label>  
                    </div> 
                    <div style="padding: 10px 0px;display: flex;">
                        <input class="fish-eye" v-model="dark" type="radio" id="theme_1" name="theme" :value="1">
                        <label style="margin-left:10px;cursor:pointer" for="theme_1">ダーク</label>  
                    </div> 
                    <div style="padding: 10px 0px;display: flex;">
                        <input class="fish-eye" v-model="dark" type="radio" id="theme_2" name="theme" :value="2">
                        <label style="margin-left:10px;cursor:pointer" for="theme_2">ライト</label>  
                    </div> 
                </div>
                <div v-else-if="step == 6">
                    <div style="padding: 10px 0px;display: flex">
                        <input :checked="$store.state.user.footer_view == 1" @change="footerMenuToggle" class="fish-eye" type="radio" id="theme_0" name="theme" :value="1">
                        <label style="margin-left:10px;cursor:pointer" for="theme_0">ON</label>  
                    </div> 
                    <div style="padding: 10px 0px;display: flex;">
                        <input :checked="$store.state.user.footer_view == 0" @change="footerMenuToggle" class="fish-eye" type="radio" id="theme_1" name="theme" :value="2">
                        <label style="margin-left:10px;cursor:pointer" for="theme_1">OFF</label>  
                    </div> 
                </div>
                <!-- </Transition>                     -->
            </div>
        </Form>
    </div>
</template>

<script>
import colors from '../../../assets/colors.json'
import LoaderButton from '../Global/LoaderButton.vue'
import { Field, Form  } from 'vee-validate'
import UserSignature from '../Profile/UserEditComps/UserSignature.vue'
import HamBurger from '../Global/HamBurger.vue'
    export default {
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
                chosenColor: 0,
                urlCreating: false,
                icalUrl: {
                    status: false,
                    url: ''
                },
                themeChange: 0,
                
            }
        },
       
        mounted(){
            if(this.$store.state.user && this.$store.state.user.ical_key){
                this.icalUrl = {
                    status: true,
                    url: `${this.$store.state.baseLocation}/export_ical?id=${this.$store.state.user.id}&token=${this.$store.state.user.ical_key}`
                }
            }
            this.chosenColor = this.user ? this.user.color : (this.avialableColors ? this.avialableColors[0].id : '')
        },
        computed:{
            dark: {
                get () {
                    this.themeChange
                    const customTheme = localStorage.getItem('dark')
                    if(customTheme == 0 || customTheme == '0' || !customTheme){
                        return 0
                    }else {
                        return parseInt(customTheme)                    
                    }
                },
                set (value) {
                    
                    if(value == 0){
                        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            this.$store.commit('setDark', true)
                        } else {
                            this.$store.commit('setDark', false)
                        }
                    }else{
                        const newVal = value == 1 ? true : false
                        this.$store.commit('setDark', newVal)
                    }
                    localStorage.setItem('dark', value)
                    this.themeChange ++
                }
            },
            user(){
                return this.$store.state.user
            },
            isColor(){
                return this.avialableColors ? this.avialableColors.find(ob => ob.id == this.chosenColor) : ''
            },
            settingTitle(){
                const titles = ['パスワードの変更', 'カラー設定', 'マイサイン', 'カレンダー設定', 'テーマ設定', 'フッターメニュー表示']
                return titles[this.step - 1]
                
            },
        },
        components:{
            LoaderButton,
            Field,
            Form,
            UserSignature,
            HamBurger
        },
        methods:{
            updateSignature(){
                const data = {
                        text: '保存しました。',
                        channel: Math.random().toString(36).substring(5),
                        icon: 0,
                        view: true
                    }
                    emitter.emit('setInfo', data)
                    this.updateUser()
                    this.step = 0
            },
            updateUser(){
                axios.post('/profile_get_update_user', {id: this.$store.state.user.id}).then(               
                response => {
                    if(response.data && Object.hasOwn(response.data, 'id')){                        
                        this.$store.commit('setUser', response.data);                          
                    }                    

                })
            },
            logoutConfirm(){
                var uniqueChannell = Math.random().toString(36).substring(5);   
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: 'ログアウトしますか。',
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.logout(): false});
            },
            logout(){
                document.getElementById('logout-form').submit();
            },
            footerMenuToggle(){                
                const v = event.target.value == 1 ? true : false
                this.$store.commit('setFooterView', v)
                axios.patch('set_footer_view', {value:v})
            },
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
            copyUrl(){
                const selectedText = this.$refs.icalUrl ? this.$refs.icalUrl.textContent : ''
                if(!selectedText){
                    this.errorToast('コピーに失敗しました。')
                }
                navigator.clipboard.writeText(selectedText)
                
                .then(() => {
                    const data = {
                        text: 'コピーしました。',
                        channel: Math.random().toString(36).substring(5),
                        icon: 0,
                        view: true
                    }
                    emitter.emit('setInfo', data)
                })
                .catch((error) => {
                    console.error('Unable to copy text to clipboard:', error);
                    
                });
            },
            createUrl(){
                axios.get('/ical_url_generate').then(response => {
                    if(response.data.success){
                        this.icalUrl.url = response.data.url
                    }
                                
                }).catch(function (error) { 
                    console.log(error)               
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t(error.message))   
                }.bind(this));
            },
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
                    // this.errorToast(this.$t(response.data.message))   
                    console.log(response.status)
                    if(response.status == 200){
                        this.step = 0
                        this.currentPassword = this.newPassword = this.newPasswordConfirm =  ''
                        const data = {
                            text: '変更しました。',
                            channel: Math.random().toString(36).substring(5),
                            icon: 0,
                            view: true
                        }
                        emitter.emit('setInfo', data) 
                        
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
                    const data = {
                        text: '保存しました。',
                        channel: Math.random().toString(36).substring(5),
                        icon: 0,
                        view: true
                    }
                    emitter.emit('setInfo', data) 
                    this.updateUser()
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
    .color-item-parent{
        padding: 5px;
        border: solid 1px transparent;
    }
    .selected-color{
        border: solid 1px var(--primary-color);
    }
    label{
        color: var(--primary-color);
    }

    .height100-div{
        height: 100%;
        display: flex;
        flex-direction: column;
        margin: 0 25px;
        align-items: flex-start;

    }
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
        padding: 15px 25px;
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
        .height100-div{
            align-items: center;
        }
    }
</style>
