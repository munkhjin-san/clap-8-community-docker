<template>
<div class="container login-wrapper">
    <div class="login-body">
        <Form v-slot="{ errors }" class="login-form" ref="loginform">
            <div class="login-header">   
                <Logo/>
            </div>
            <div class="login-content" v-if="!registerModal && !sentEmailModal">
                <div class="login-group row" style="margin-bottom: 30px;">
                    <span class="home-title">{{$t('loginTitle')}}</span>
                </div>
                
                <div class="login-group row">
                    <!-- <label for="login" class="col-md-4 col-form-label text-md-right">{{$t('emailAddress')}}</label> -->
                    <div class="col-md-6" style="position: relative;">
                        <span :class="{smallPlc : $store.state.activeInput == 'login' || user_login.length}" class="form-plc">{{ $t('emailAddress') }}</span>
                        <Field 
                            id="login" 
                            type="text"  
                            @focus="$store.commit('setActiveInput', 'login')" 
                            @blur="$store.commit('setActiveInput', '')"  
                            v-model="user_login" 
                            class="login-plc" 
                            name="login" 
                            rules="required|max:100" 
                            required 
                            autocomplete="login" 
                            autofocus
                        />
                        <span class="valid-error post-error" v-if="vali">{{ errors.username }}</span>
                    </div>
                </div>
                <input type="text" name="username" hidden autocomplete="login"/>
                
                <div class="login-group row" style="margin-top: 30px;">                         
                    <!-- <label for="password" class="col-md-4 col-form-label text-md-right">{{$t('password')}}</label> -->
                    <div class="col-md-6" style="position: relative;">
                        <span :class="{smallPlc : $store.state.activeInput == 'password' || user_password.length}" class="form-plc">{{ $t('password') }}</span>
                        <Field 
                            id="password" 
                            type="password" 
                            @focus="$store.commit('setActiveInput', 'password')" 
                            @blur="$store.commit('setActiveInput', '')" 
                            @keydown.enter="handleLogin()" 
                            v-model="user_password" 
                            class="login-plc" 
                            name="password" 
                            rules="required" 
                            required 
                            autocomplete="current-password"
                        />
                        <small class="valid-error post-error" v-if="loginError">{{ loginError }}</small>
                        <span class="valid-error post-error" v-if="vali">{{ errors.password }}</span>
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <div class="col-md-8 offset-md-4">
                        <button @click="handleLogin()" type="button" class="btn btn-primary login-btn-change" :disabled="processing">
                            <span v-if="!processing"> {{$t('login')}}</span>
                            <div v-if="processing" id="loaderMini">
                                <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                            </div>
                        </button>
                        <div class="separator">
                            <span class="separator-line"></span>
                            <span class="separator-text">{{$t('orSocial')}}</span>
                            <span class="separator-line"></span>
                        </div>
                        <div class="d-flex m-auto" style="place-content:center;margin-top:30px;">
                            <!--Facebook Login-->
                            <div class="flex items-center">
                                <a class="btn1" :href="`auth/facebook?intended=${this.intended}`">
                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="var(--primary-color)" viewBox="0 0 24 24">
                                    <path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm3 8h-1.35c-.538 0-.65.221-.65.778v1.222h2l-.209 2h-1.791v7h-3v-7h-2v-2h2v-2.308c0-1.769.931-2.692 3.029-2.692h1.971v3z"/>
                                </svg>                                
                                </a>
                            </div> 
                            <!--Facebook Login-->
        
                            <!--Google Login-->
                            <div class="flex items-center" style="margin:0px 30px;">
                                <a class="btn1" :href="`auth/google?intended=${this.intended}`">
                                
                                    <span style="background:var(--primary-color);border-radius:50%;width:30px;height:30px;" class="d-flex">                                    
                                    <svg class="m-auto" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="var(--background-color)" viewBox="0 0 640 640">
                                        <path d="M326.331 274.255v109.761h181.49c-7.37 47.115-54.886 138.002-181.49 138.002-109.242 0-198.369-90.485-198.369-202.006 0-111.509 89.127-201.995 198.369-201.995 62.127 0 103.761 26.516 127.525 49.359l86.883-83.635C484.99 31.512 412.741-.012 326.378-.012 149.494-.012 6.366 143.116 6.366 320c0 176.884 143.128 320.012 320.012 320.012 184.644 0 307.256-129.876 307.256-312.653 0-21-2.244-36.993-5.008-52.997l-302.248-.13-.047.024z"/>
                                    </svg>
                                    </span>                               
                                </a>
                            </div> 
                            <!--Google Login-->
        
                            <!--Twitter Login-->
                            <div class="flex items-center">
                                <a class="btn1" :href="`auth/twitter?intended=${this.intended}`">
                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="var(--primary-color)">
                                    <path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6.066 9.645c.183 4.04-2.83 8.544-8.164 8.544-1.622 0-3.131-.476-4.402-1.291 1.524.18 3.045-.244 4.252-1.189-1.256-.023-2.317-.854-2.684-1.995.451.086.895.061 1.298-.049-1.381-.278-2.335-1.522-2.304-2.853.388.215.83.344 1.301.359-1.279-.855-1.641-2.544-.889-3.835 1.416 1.738 3.533 2.881 5.92 3.001-.419-1.796.944-3.527 2.799-3.527.825 0 1.572.349 2.096.907.654-.128 1.27-.368 1.824-.697-.215.671-.67 1.233-1.263 1.589.581-.07 1.135-.224 1.649-.453-.384.578-.87 1.084-1.433 1.489z"/>
                                </svg>                                
                                </a>
                            </div> 
                            <!--Twitter Login-->
                        </div>
                        <div class="link-wrapper">
                            <a class="login-link jump-link" @click="openRegister()">
                                {{$t('register')}}
                            </a>
                        </div>
                        <div class="link-wrapper">
                            <a class="login-link jump-link" @click="openEmail()">
                                {{$t('resetPassword')}}
                            </a>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="component-wrapper" v-if="registerModal">
                <RegisterComponent 
                    @closeRegister="closeRegister"
                    :intended="intended"
                    :locale="locale"
                />
            </div>
            <div class="component-wrapper" v-if="sentEmailModal">
                <NewPasswordComponent 
                    @closeEmail="closeEmail"
                    :locale="locale"
                />
            </div>
        </Form>
        
        
    </div>
    <div class="login-footer">
        <a class="jump-link" :href="`/help`">{{ $t('help.helpTitle') }}</a>
        <router-link class="jump-link" :to="`/auth/${locale}/privacy-policy`">{{ $t('privacy') }}</router-link>
        <router-link class="jump-link" :to="`/auth/${locale}/terms-of-service`">{{ $t('terms') }}</router-link>
        
        <div class="locale-login-selector">
            <select name="locales" v-model="locale" class="lang-select dropDownSelector" style="min-width: 70px;">
                <option value="mn">Монгол</option>
                <option value="en">English</option>
                <option value="ja">日本語</option>
            </select>
        </div>
    </div>
        <router-view />
</div>
</template>
<script>
    import RegisterComponent from './RegisterComponent.vue'
    import NewPasswordComponent from './NewPasswordComponent.vue'
    import { Field, Form } from 'vee-validate'
    import Logo from '../Global/Logo'
    
    import moment from 'moment';
    export default {
        props: ['intended'],
        components: {
            RegisterComponent,
            NewPasswordComponent,
            Form,
            Field,
            Logo
        },
        
        data(){
            return {
                user_password: '',
                user_login: '',
                registerModal: false,
                sentEmailModal: false,
                emailOrPhone: true,
                loginError: '',
                processing: false,
                vali: false,
            }
        },
        computed: {
            locale: {
                get () {
                    return this.$store.state.local
                },
                set (value) {
                    this.$store.commit('setLocale', value)
                    localStorage.setItem('lang', value)
                    this.$i18n.locale = value
                }
            },
        },
        created(){
            // const customLocale = localStorage.getItem('lang')
            // if(customLocale){
            //     this.$store.commit('setLocale', customLocale)
            //     this.$i18n.locale = customLocale
            // }else {
            //     const browserLang = navigator.language.substring(0, 2)
            //     if (browserLang === 'ja' || browserLang === 'mn') {
            //         this.$store.commit('setLocale', browserLang)
            //         this.$i18n.locale = browserLang
            //     } else {
            //         this.$store.commit('setLocale', 'en')
            //         this.$i18n.locale = 'en'
            //     }
            // }
            window.document.title = `GLOWD - ${this.$t('titleAuth')}`; 
        },
        methods: {
            // changeLogin(){
            //     this.emailOrPhone = !this.emailOrPhone
            // },
            openEmail(){
                this.sentEmailModal = true
            },
            closeEmail(){
                this.sentEmailModal = false
            },
            openRegister(){
                this.registerModal = true
            },
            closeRegister(){
                this.registerModal = false
            },
            handleLogin: async function() {
                const result = await this.$refs.loginform.validate();
                
                if (this.processing) return

                if(result.valid){
                    this.processing = true
                
                    axios.post('/login', {
                        login: this.user_login,
                        password: this.user_password
                    }).then(response => {
                        // Handle successful login
                        const url = this.intended ? this.intended : '/'
                        window.location.href = url
                        this.processing = false
                    })
                    .catch(error => {
                        // Handle login error
                        this.processing = false
                        if(error.response){
                            if(error.response.status == 401){
                                let errors = error.response.data
                                if(errors){
                                    this.loginError = this.$t(errors.message)
                                }
                            }else if(error.response.status == 429){
                                let errors = error.response.data
                                if(errors){
                                    this.loginError = this.$t('tooManyAttemptError.loginAction')
                                }
                            }else if(error.response.status == 404){
                                let errors = error.response.data
                                if(errors){
                                    this.loginError = this.$t(errors.message)
                                }
                            }else{
                                this.loginError = this.$t('loginError')
                            }
                       }else{
                            this.loginError = this.$t('loginError')
                       }
                    });
                }else{
                    this.vali = true
                }
                
            }
        }
    }
</script>

<style lang="scss">
    input:autofill {
        -webkit-text-fill-color: var(--primary-color);
    }
    .login-wrapper{
        background-color: var(--bg2);
        position: absolute;
        width: 100%;
        display: flex; 
        height: 100%; 
        min-height: 700px;
        flex-direction:column
    }
    .home-title{
        font-size: 18px;
    }
    .routerClass{
        position: fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        overflow: hidden auto;
    }
    .login-header > svg{
        font-size: 3em;
        margin-bottom: 25px;
        width: 156px;
        height: 60px;
        fill: var(--primary-color);
    }
    .login-header__p{
        font-size: 24px;
    }
    .mt-40{
        margin-top: 40px !important;
    }
    .lang-select{
        margin: 0 10px;
        outline:none;
        color:inherit;
    }
    .lang-select option{
        background-color: var(--background-color);
    }
    .login-footer{
        font-size:13px;
        width: 100%;
        margin-top:10px;
        display: flex;
        justify-content: center;
        color: var(--primary-color);
        align-items: center;
        text-align: center;
        margin-bottom: 10px;
    }
    .login-footer a{
        margin: 0 10px;
        word-break: break-word;
    }
    .login-footer span{
        margin: 0 10px;
    }
    .link-wrapper{
        width: 100%;
        text-align: center;
        margin-top:30px;
    }
    .jump-link{
        color: var(--link-color);
        text-decoration: underline;
    }
    .login-link{
        cursor:pointer;
        height: 40px;
        width:120px;
        text-align:center;
    }
    .jump-link:hover{
        color: var(--link-color);
        text-decoration: underline;
        font-weight: 600;
    }
    .login-link:hover{
        color: var(--link-color);
        text-decoration: underline;
        font-weight: 600;
    }
    .login-group{
        width: 80%;
        margin: 0 auto;
    }
    .login-btn-change{
        margin: 0 auto;
        display: block;
        height: 50px;
        text-align: center;
        line-height: 50px;
        color: #fff;
        background-color: var(--primary-button);
        cursor:pointer;
        margin-top:30px; 
        margin-bottom:20px;
        width:100%;
    }
    .login-header{
        width: 50%;
        padding: 20px 0;
        text-align: center;
    }
    .login-content{
        width: 35%;
        min-height: 494px;
    }
    .login-form{
        background-color:var(--bg2);
        position:relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        color: var(--primary-color);
    }
    .login-body{
        display:block;
        width: 80%;
        margin: auto;
        position: relative;
    }
    .locale-login-selector{
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .d-flex{
        display: flex;
    }
    .btn1{
        background-color: transparent;
        border: 1px solid transparent;
        border-radius: 0.25rem;
        color: #212529;
        display: inline-block;
        font-size: .9rem;
        font-weight: 400;
        text-align: center;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        user-select: none;
        vertical-align: middle;
    }
    .m-auto{
        margin: auto;
    }
    .countrySelect{
        -webkit-appearance: none;
        appearance: none;
        padding: 5px 10px;
        background: whitesmoke;
        border: 1px solid #b8b8b8;
        font-size: 16px;
        line-height: 20px;
    }
    .login-input{
        margin-left:10px;
    }
    .separator {
        display: flex;
        align-items: center;
        padding-top: 10px;
    }
   
    .separator-line {
        flex: 1;
        height: 1px;
        margin: 0 10px;
        border-top: 1px solid #ccc;
    }
    .footer-link{
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .separator-text {
        font-size: 14px;
    }
    .component-wrapper{
        width: 35%;
        min-height: 494px;
    }
    @media screen and (max-width: 959px){
        .keyboard-open{
            margin: 0px auto;
        }
        .login-body{
            width: 100%;
        }
        .login-form{
            flex-direction: column;
            align-items: center;
        }
        .login-header{
            width: 100%;
            padding: 30px 0 50px;
        }
        .login-header > svg {
            font-size: 2.5em;
            width: 156px;
            height: 60px;
            margin-bottom: 15px;
        }
        .login-header__p{
            font-size: 18px;
        }
        .login-content{
            font-size: 14px;
            width: 90%;
        }
        .component-wrapper{
            width: 90%;
            font-size: 14px;
        }
        // .login-footer{
        //     flex-direction: column
        // }
        .footer-link{
            text-align: center;
        }
        .locale-login-selector{
            margin-top: 5px;
        }
    }
    
</style>
