<template>
    <div>
        <div>
            
            <Form v-slot="{ errors }" ref="registerform">
            <form class="register-form" @submit.prevent="handleRegister" style="position:relative;">
                <div class="register-content" v-if="!verifyModal">
                    <div class="row" style="margin-bottom: 30px;">
                        <span class="home-title">{{$t('registerTitle')}}</span>
                    </div>
                    <div class="register-group row" v-if="!emailOrPhone">
                        <!-- <label for="login" class="col-md-4 col-form-label text-md-right">{{$t('emailAddress')}}</label> -->
                        <div class="col-md-6" style="position: relative;">
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
                                autofocus 
                                class="login-plc"
                            />
                            <small class="text-danger" v-if="emailError">{{ emailError }}</small>
                            <span class="valid-error post-error" v-else-if="vali">{{ errors.login }}</span>
                            <small class="text-danger" v-if="email_login && !emailVali">{{$t('validEmail')}}</small>
                        </div>

                    </div>
                    <div class="register-group row" v-if="emailOrPhone">
                        <label for="login" class="col-md-4 col-form-label text-md-right">{{$t('phoneNumber')}}</label>
                        <div class="col-md-6" style="margin-top: 15px; display:flex; justify-content:space-between;">
                            <select class="countrySelect" v-model="country_code" name="country_code">
                                <option value="+81">JP +81</option>
                                <option value="+976">MN +976</option>
                            </select>
                            <Field id="login" @input="validPhone" class="login-input" type="login" v-model="phone_login" rules="required" name="login" autocomplete="phone_login" autofocus />
                        </div>
                        <small class="text-danger" v-if="emailError">{{ emailError }}</small>
                        <span class="valid-error post-error" v-else-if="vali">{{ errors.login }}</span>
                        <small class="text-danger" v-if="phone_login && !phoneVali">{{$t('validPhone') }}</small>
                    </div>


                    <div class="row mb-0">
                        <div class="col-md-6 offset-md-4">
                            <button type="submit" class="btn btn-primary login-btn-change" :disabled="processing">
                                <span v-if="!processing">{{$t('resetPasswordCode')}}</span>
                                <div v-if="processing" id="loaderMini">
                                    <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                                </div>
                            </button>
                            <!-- <div class="separator">
                                <span class="separator-line"></span>
                                <span class="separator-text">{{$t('or')}}</span>
                                <span class="separator-line"></span>
                            </div>
                            <div v-if="emailOrPhone" @click="changeLogin()" class="btn btn-primary login-btn-change secondary-button">
                                {{$t('registerWithEmail')}}
                            </div>
                            <div v-if="!emailOrPhone" @click="changeLogin()" class="btn btn-primary login-btn-change secondary-button">
                                {{$t('registerWithPhone')}}
                            </div> -->
                        </div>
                    </div>
                    <div class="link-wrapper" style="margin-top:10px;">
                        <a class="login-link jump-link" @click="closeRegisterModal()">
                            {{$t('backToLogin')}}
                        </a>
                    </div>
                </div>
                <div v-if="verifyModal">
                    <PhoneVerify 
                        :user_login="emailPhone"
                        :country_code="country_code"
                        :token="token"
                        :intended="intended"
                        :register="register"
                        :locale="locale"
                    />
                </div>
            </form>
            </Form>
        </div>
    </div>
</template>
<script>
    import { Field, Form  } from 'vee-validate'
    import PhoneVerify from './PhoneVerify.vue'
    export default{
        props: ['intended', 'locale'],
        data(){
            return{
                user_name:'',
                phone_login:'',
                email_login:'',
                user_password:'',
                user_passwordconfirm:'',
                emailError:'',
                passwordConfirmationError: '',
                country_code: '+81',
                emailOrPhone: false,
                verifyModal: false,
                token: '',
                register: false,
                processing: false,
                vali: false,
                phoneVali: false,
                emailVali: false,
            }
        },
        mounted(){
            if(this.locale == 'ja'){
                this.country_code = '+81'
            }else{
                this.country_code = '+976'
            }
        },
        computed:{
            emailPhone(){
                return this.phone_login ? this.phone_login : this.email_login
            }
        },
        // computed: {
        //     phonePattern(){
        //         return /^(0\d{7,13}|\d{8,14})$/;
        //     },
        //     isValidPhone(){
        //         return this.phonePattern.test(this.user_login);
        //     }
        // },
        components: {
            PhoneVerify,
            Form,
            Field
        },
        methods: {
            changeLogin(){
                this.emailOrPhone = !this.emailOrPhone
                this.phone_login = ''
                this.email_login = ''
            },
            closeRegisterModal(){
                this.$emit('closeRegister')
            },
            generateToken() {
                let token = '';
                const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                const charactersLength = characters.length;

                for (let i = 0; i < 10; i++) {
                    token += characters.charAt(Math.floor(Math.random() * charactersLength));
                }

                return token;
            },
            handleRegister: async function(){
                const result = await this.$refs.registerform.validate()

                if(this.processing) return
                // Call the function to generate a token
                if(result.valid && (this.phoneVali || this.emailVali)){
                    this.processing = true
                    const token = this.generateToken();
                    axios.post('/register', {
                        login: this.phone_login ? this.phone_login : this.email_login,
                        phone_prefix: this.country_code,
                        token: token,
                        lang: this.locale
                    })
                    .then(response => {
                        // window.location.href = response.data.redirect;
                        if(response.data && response.data == token){
                            localStorage.setItem('temp_token', token);
                            this.token = token
                        }
                        this.verifyModal = true
                        this.register = true
                        this.processing = false                    
                    })
                    .catch(error => {
                        this.processing = false
                        if(error.response.status === 422){
                            const data = error.response.data;
                            this.emailError = this.$t(data.message)
                        }else if(error.response.status == 429){
                            const errors = error.response.data
                            if(errors){
                                this.emailError = this.$t('tooManyAttemptError.mailOrPhoneRegisterCheck')
                            }
                        }else if(error.response.data.message.includes('Max send attempts')){
                            this.emailError = this.$t('tooManyAttemptError.mailOrPhoneRegisterCheck')
                        }else{
                            this.emailError = this.$t('registerError')
                        }
                    });
                }else{
                    this.vali = true
                    this.emailError = ''
                }
                
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
            } 
        }
    }
</script>
<style scoped lang="scss">
    .register-form{
        width: 80%;
        margin: 0 auto;
    }
    #register-popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }
    #register-popup .popup-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        max-width: 600px;
        background-color: #fff;
        padding: 40px 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
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
    .secondary-button{
        background-color: #888 !important;
    }
    .separator-text {
        font-size: 16px;
    }
    
    @media screen and (max-width: 959px){
        .register-form{
            font-size: 14px;
        }
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