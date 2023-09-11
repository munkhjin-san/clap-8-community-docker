<template>
    <div>
        <Form v-slot="{ errors }" ref="verifyform">
            <form @submit.prevent="phoneVerify" v-if="!registerDetail">
                <div class="row" style="margin-bottom: 30px;">
                    <span class="home-title">{{user_login ? $t('registerTitle') : $t('resetPasswordTitle')}}</span>
                </div>
                <div class="row">
                    <!-- <label for="verification_code"
                        class="col-md-4 col-form-label text-md-right">{{$t('otp')}} {{user_login ? user_login : password_login}}</label> -->
                    <div class="col-md-6" style="position: relative;">
                        <span :class="{smallPlc : $store.state.activeInput == 'verification_code' || verification_code.length}" class="form-plc">{{$t('otp')}}</span>
                        <Field 
                            id="verification_code" 
                            type="tel" 
                            @focus="$store.commit('setActiveInput', 'verification_code')" 
                            @blur="$store.commit('setActiveInput', '')" 
                            name="verification_code" 
                            autocomplete="off" 
                            v-model="verification_code" 
                            rules="required"
                            autofocus
                            class="login-plc" 
                        />
                        <span class="valid-error post-error" v-if="vali">{{ errors.verification_code }}</span>
                    </div>
                    <small class="valid-error post-error" v-if="verifyError">{{ verifyError }}</small>
                    <small class="valid-error post-error" v-if="newCode">{{ newCode }}</small>
                </div>
                <div class="row mb-0">
                    <div class="col-md-6 offset-md-4" >
                        <button type="submit" class="login-btn-change" style="width:100%;" :disabled="processing">
                            <span v-if="!processing">{{$t('verify')}}</span>
                            <div v-if="processing" id="loaderMini">
                                <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                            </div>
                        </button>
                    </div>
                </div>
                <div style="width: 100%;text-align: center;margin-top:10px;">
                    <a @click="sendVerify()" class="login-link jump-link" :disabled="processing">
                        {{$t('resendCode')}}
                    </a>
                </div>
            </form>
        </Form>
        <div v-if="registerDetail">
            <UserRegister 
                :user_id="user_id"
                :user_email="user_email"
                :intended="intended"
                :passwordreset="passwordreset"
                :register="register"
                :locale="locale"
            />
        </div>
    </div>
</template>
<script>
    import UserRegister from './UserRegister.vue'
    import { Field, Form } from 'vee-validate'
    export default{
        components : {
            UserRegister,
            Form,
            Field
        },
        props: [
            'user_login', 
            'country_code', 
            'token', 
            'intended', 
            'passwordreset', 
            'register', 
            'passwordToken', 
            'passwordOtp', 
            'password_login', 
            'password_country',
            'userEdit',
            'login_token',
            'locale'
            ],
        data(){
            return{
                verification_code: '',
                user_id: '',
                user_email: '',
                verifyError: '',
                registerDetail: false,
                newCode: '',
                processing: false,
                vali: false,
                resetOtp: null,
            }
        },
        methods: {
            phoneVerify: async function(){
                const result = await this.$refs.verifyform.validate()

                if(this.processing) return
                if(result.valid){
                    this.processing = true
                    if(this.passwordreset){
                        axios.post('/phone/verification', {
                            verification_code: this.resetOtp ? this.resetOtp : this.verification_code,
                            password_login: this.password_login,
                            phone_prefix: this.password_country,
                            token: this.passwordToken
                        }).then(response => {
                            if(response.data){
                                this.user_email = response.data.login
                                this.registerDetail = true
                                this.processing = false
                            }
                        }).catch(error => {
                            this.processing = false
                            if(error.response.status == 422){
                                this.verifyError = this.$t(error.response.data.message)
                                this.newCode = ''
                            }else if(error.response.status == 429){
                                this.verifyError = this.$t('tooManyAttemptError.otpCheckAction')
                                this.newCode = ''
                            }else{
                                this.verifyError = this.$t('verifyError')
                                this.newCode = ''
                            }
                        })
                    }else if(this.register){
                        axios.post('/phone/verification', {
                            verification_code: this.verification_code,
                            phone: this.user_login,
                            phone_prefix: this.country_code,
                            token: this.token
                        }).then(response => {
                            if(response.data){
                                this.user_id = response.data.id
                                this.registerDetail = true
                                this.processing = false
                            }
                        }).catch(error => {
                            this.processing = false
                            if(error.response.status == 422){
                                this.verifyError = this.$t(error.response.data.message)
                                this.newCode = ''
                            }else if(error.response.status == 429){
                                this.verifyError = this.$t('tooManyAttemptError.otpCheckAction')
                                this.newCode = ''
                            }else{
                                this.verifyError = this.$t('verifyError')
                                this.newCode = ''
                            }
                        })
                    }else if(this.userEdit){
                        axios.post('/phone/verification', {
                            verification_code: this.verification_code,
                            editData: this.user_login,
                            phone_prefix: this.country_code,
                            login_token: this.login_token
                        }).then(response => {
                            if(response.data == 'saved'){
                                this.$emit('closeModal')
                                this.$emit('getUserInfo')
                                this.processing = false
                                emitter.emit('setToast', { 
                                    active: true,
                                    content: this.$t('success')
                                })
                            }
                        }).catch(error => {
                            this.processing = false
                            if(error.response.status == 422){
                                this.verifyError = this.$t(error.response.data.message)
                                this.newCode = ''
                            }else if(error.response.status == 429){
                                this.verifyError = this.$t('tooManyAttemptError.otpCheckAction')
                                this.newCode = ''
                            }else{
                                this.verifyError = this.$t('verifyError')
                                this.newCode = ''
                            }
                        })
                    }
                }else{
                    this.vali = true
                    this.newCode = ''
                    this.verifyError = ''
                }
                
                
            },
            sendVerify(){
                if(this.processing) return

                this.processing = true
                if(this.passwordreset){
                    axios.post('/phone/send-code-again', {
                        password_login: this.password_login,
                        phone_prefix: this.password_country,
                        token: this.passwordToken,
                        lang: this.locale
                    }).then(response => {
                        this.newCode = this.$t(response.data.message)
                        this.resetOtp = response.data.otp
                        this.verifyError = ''
                        this.processing = false
                        this.vali = false
                    }).catch(error=> {
                        this.processing = false
                        this.vali = false
                        if(error.response.status == 429){
                            this.verifyError = this.$t('tooManyAttemptError.otpSendAction')
                            this.newCode = ''
                        }else{
                            this.verifyError = this.$t('sendCodeError')
                            this.newCode = ''
                        }
                    })
                }else if(this.register){
                    axios.post('/phone/send-code-again', {
                        phone: this.user_login,
                        phone_prefix: this.country_code,
                        token: this.token,
                        lang: this.locale
                    }).then(response => {
                        this.newCode = this.$t(response.data.message)
                        this.verifyError = ''
                        this.processing = false
                        this.vali = false
                    }).catch(error=> {
                        this.processing = false
                        this.vali = false
                        if(error.response.status == 429){
                            this.verifyError = this.$t('tooManyAttemptError.otpSendAction')
                            this.newCode = ''
                        }else{
                            this.verifyError = this.$t('sendCodeError')
                            this.newCode = ''
                        }
                    })
                }else if(this.userEdit){
                    axios.post('/phone/send-code-again', {
                        editData: this.user_login,
                        phone_prefix: this.country_code,
                        login_token: this.login_token,
                        lang: this.locale
                    }).then(response => {
                        this.newCode = this.$t(response.data.message)
                        this.verifyError = ''
                        this.processing = false
                        this.vali = false
                    }).catch(error=> {
                        this.processing = false
                        this.vali = false
                        if(error.response.status == 429){
                            this.verifyError = this.$t('tooManyAttemptError.otpSendAction')
                            this.newCode = ''
                        }else{
                            this.verifyError = this.$t('sendCodeError')
                            this.newCode = ''
                        }
                    })
                }
                
            }
        }
    }
</script>
<style scoped lang="scss">
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

</style>