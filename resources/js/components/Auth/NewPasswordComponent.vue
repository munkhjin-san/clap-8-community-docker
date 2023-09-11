<template>
    <div style="width: 80%; margin: 0 auto;">
        <div>
            <!-- <div style="position:absolute;right:20px;top:20px;z-index:10;">
                <div class="cursor-pointer" @click="closeEmailModal()" style="position:unset;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div>
            </div> -->
            <Form v-slot="{ errors }" ref="resetform">
            <form @submit.prevent="resetPassword" v-if="!verifyModal">
                <div class="row" style="margin-bottom: 30px;">
                    <span class="home-title">{{$t('resetPasswordTitle')}}</span>
                </div>
                <div class="row mb-3" v-if="!emailOrPhone">
                    <!-- <label for="login" class="col-md-4 col-form-label text-md-right">{{$t('emailAddress')}}</label> -->
                    <div class="col-md-6">
                        <span :class="{smallPlc : $store.state.activeInput == 'login' || email_login.length}" class="form-plc">{{ $t('emailAddress') }}</span>
                            <Field 
                                id="login" 
                                v-model="email_login" 
                                @input="validEmail" 
                                @focus="$store.commit('setActiveInput', 'login')" 
                                @blur="$store.commit('setActiveInput', '')" 
                                type="login" 
                                rules="required" 
                                name="login" 
                                autocomplete="email_login" 
                                autofocus class="login-plc"
                            />
                        <small class="text-danger" v-if="emailMessage">{{ emailMessage }}</small>
                        <span class="valid-error post-error" v-else-if="vali">{{ errors.login }}</span>
                        <small class="text-danger" v-if="email_login && !emailVali">{{$t('validEmail')}}</small>                                
                    </div>
                </div>
                <div class="row" v-if="emailOrPhone">
                    <label for="login" class="col-md-4 col-form-label text-md-right">{{$t('phoneNumber')}}</label>
                    <div class="col-md-6" style="margin-top: 15px; display:flex; justify-content:space-between;">
                        <select class="countrySelect" v-model="password_country" name="password_country">
                            <option value="+81">JP +81</option>
                            <option value="+976">MN +976</option>
                        </select>
                        <Field id="login" class="login-input" type="login" @input="validPhone" v-model="phone_login" rules="required" name="login" autocomplete="phone_login" autofocus />
                    </div>
                    <small class="text-danger" v-if="emailMessage">{{ emailMessage }}</small>
                    <span class="valid-error post-error" v-else-if="vali">{{ errors.login }}</span>
                    <small class="text-danger" v-if="phone_login && !phoneVali">{{$t('validPhone') }}</small>

                </div>
                <div class="row mb-0">
                    <div class="col-md-6 offset-md-4">
                        <button type="submit" class="btn btn-primary login-btn-change" style="width: 100%;" :disabled="processing">
                            <span v-if="!processing">{{$t('resetPasswordCode')}}</span>
                            <div v-if="processing" id="loaderMini">
                                <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                            </div>
                        </button>
                        <!-- <div class="separator">
                                <span class="separator-line"></span>
                                <span class="separator-text">{{$t('or')}}</span>
                                <span class="separator-line"></span>
                        </div> -->
                        <!-- <div v-if="emailOrPhone" @click="changeLogin()" class="btn btn-primary login-btn-change secondary-button">
                            {{$t('resetWithEmail')}}
                        </div>
                        <div v-if="!emailOrPhone" @click="changeLogin()" class="btn btn-primary login-btn-change secondary-button">
                            {{$t('resetwithPhone')}}
                        </div> -->
                    </div>
                </div>
                <div class="link-wrapper" style="margin-top:10px;">
                    <a class="login-link jump-link" @click="closeEmailModal()">
                        {{$t('backToLogin')}}
                    </a>
                </div>
            </form>
            </Form>
            <div v-if="verifyModal">
                <PhoneVerify 
                    :password_login="emailPhone"
                    :password_country="password_country"
                    :passwordreset="passwordreset"
                    :passwordOtp="passwordOtp"
                    :passwordToken="passwordToken"
                    :locale="locale"
                />
            </div>
            <!-- <form v-if="emailOrPhone" @submit.prevent="phoneResetPassword">
                <div class="form-group row" style="width:100%;">
                    <label for="login" class="col-md-4 col-form-label text-md-right">Утасны дугаар</label>
                    <div class="col-md-6" style="margin-top: 15px; display:flex; justify-content:space-between;">
                        <select class="countrySelect" v-model="country_code_reset" name="country_code">
                            <option value="+81">JP +81</option>
                            <option value="+976">MN +976</option>
                        </select>
                        <input id="login" class="login-input" type="login" v-model="user_login_phone" name="login" required autocomplete="login" autofocus>
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-md-6 offset-md-4">
                        <button type="submit" class="btn btn-primary reset-button">
                            Нууц үг шинэчлэх код илгээх
                        </button>
                    </div>
                </div>
            </form> -->
        </div>
    </div>
</template>

<script>
    import { Field, Form } from 'vee-validate'
    import PhoneVerify from './PhoneVerify.vue'
    export default{
        props:['locale'],
        components : {
            PhoneVerify,
            Field,
            Form
        },
        data(){
            return{
                phone_login: '',
                email_login: '',
                emailMessage: '',
                emailOrPhone: false,
                password_country: '+81',
                verifyModal: false,
                passwordreset: false,
                passwordToken: '',
                passwordOtp: '',
                processing: false,
                vali: false,
                phoneVali: false,
                emailVali: false
            }
        },
        mounted(){
            if(this.locale == 'ja'){
                this.password_country = '+81'
            }else{
                this.password_country = '+976'
            }
        },
        computed: {
            emailPhone(){
                return this.phone_login ? this.phone_login : this.email_login
            }
        },
        methods: {
            changeLogin(){
                this.emailOrPhone = !this.emailOrPhone
            },
            closeEmailModal(){
                this.$emit('closeEmail')
            },
            validPhone(){
                
                const value = this.phone_login;
                const valInput = /^\d{6,14}$/.test(value);

                this.phoneVali = valInput
            },  
            validEmail(){
                const value = this.email_login;

                const valInput = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

                this.emailVali = valInput
            },
            resetPassword: async function(){
                const result = await this.$refs.resetform.validate()

                if(this.processing) return
                
                if(result.valid && (this.phoneVali || this.emailVali)){
                    this.processing = true
                    axios.post('/password/reset/phone',{
                        login: this.phone_login ? this.phone_login : this.email_login,
                        phone_prefix: this.password_country,
                        lang: this.locale
                    })
                    .then(response => {
                        if(response.data){
                            this.passwordToken = response.data.token
                            this.verifyModal = true
                            this.passwordreset = true
                            this.processing = false
                        }
                    })
                    .catch(error => {
                        this.processing = false
                        if(error.response.status == 422){
                            this.emailMessage = this.$t(error.response.data.message)
                        }else if(error.response.data.message.includes('Max send attempts')){
                            this.emailMessage = this.$t('tooManyAttemptError.mailOrPhoneRegisterCheck')
                        }else{
                            this.emailMessage = this.$t('sendCodeError')
                        }
                    })
                }else{
                    this.vali = true
                    this.emailMessage = ''
                }
                
            }
        },
    }
</script>

<style scoped lang="scss">
    #email-popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }
    #email-popup .popup-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        max-width: 28rem;
        background-color: #fff;
        padding: 1rem 1.5rem;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }
    .reset-button{
        margin-top: 1rem;
        display: block;
        text-align: center;
        color: #fff;
        background-color: #000;
        margin-bottom: 0px;
        padding: 0.5rem 1rem;
        text-transform: uppercase;
        font-weight: 600;
        font-size: 0.75rem;
        line-height: 1rem;
        float: left;
    }
    
    .secondary-button{
        background-color: #888 !important;
    }
    
    @media screen and (max-width: 959px){
        .separator{
            padding: 10px 0 0 0;
        }
        .separator-text{
            font-size: 12px;
        }
        .countrySelect{
            font-size: 12px;
            padding: 5px 5px;
            color: #000;
        }
        #login{
            font-size: 12px;
        }
    }
</style>