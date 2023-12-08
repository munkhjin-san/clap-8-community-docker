<template>
<div class="container login-wrapper" style="display: flex;height: 100%;">
    <div class="login-body">
        <Form v-slot="{ errors }" action="/login" method="post" class="login-form" ref="loginform">
            <div class="login-header" style="display: flex;justify-content: center;margin-bottom: 20px;">   
                <Logo/>
            </div>
            <div class="login-content" v-if="!registerModal && !sentEmailModal">                
                <div class="login-group row">
                    <div class="col-md-6" style="position: relative;">
                        <span class="smallPlc form-plc" style="background: var(--background-color);">アカウント名</span>
                        <Field 
                            id="login" 
                            type="text"   
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
                    <div class="col-md-6" style="position: relative;">
                        <span class="smallPlc form-plc"  style="background: var(--background-color);">パスワード</span>
                        <Field 
                            id="password" 
                            type="password" 
                            
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
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button type="submit" class="btn btn-primary login-btn-change" :disabled="processing">
                            <span v-if="!processing"> {{$t('login')}}</span>
                            <div v-if="processing" id="loaderMini">
                                <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                            </div>
                        </button>
                        
                        <div class="d-flex m-auto" style="place-content:center;margin-top:30px;">
                            
                        </div>
                    </div>
                </div>
                
            </div>
           
        </Form>
        
        
    </div>
    <!-- <div class="login-footer">
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
        <router-view /> -->
</div>
</template>
<script>
    import { Field, Form } from 'vee-validate'
    import Logo from '../Global/Logo'
    
    import moment from 'moment';
    export default {
        props: ['intended'],
        components: {
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
                csrfToken: document.head.querySelector('meta[name="csrf-token"]').content
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
            window.document.title = `CLAP - ログイン`; 
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
                        window.location.href = url.includes('app/public') ? '/' : url
                        // this.processing = false
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
        background-color: var(--background-color);
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
    .login-content{
        width: 35%;
        min-height: 494px;
    }
    .login-form{
        background-color:var(--background-color);
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
