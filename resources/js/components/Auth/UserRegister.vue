<template>
    <div>
        <Form ref="form" v-slot="{ errors }" class="register-form" style="position:relative;">
        <form @submit.prevent="registerSuccess" >
            <div class="row" style="margin-bottom: 30px;">
                <span class="home-title">{{register ? $t('registerTitle') : $t('resetPasswordTitle')}}</span>
            </div>
            <div class="row" v-if="register">
                <!-- <label for="name" class="col-md-4 col-form-label text-md-right">{{$t('name')}}</label> -->
                <div class="col-md-6" style="position:relative;">
                    <span :class="{smallPlc : $store.state.activeInput == 'user_name' || user_name.length}" class="form-plc">{{$t('name')}}</span>
                    <Field 
                        id="name" 
                        @focus="$store.commit('setActiveInput', 'user_name')" 
                        @blur="$store.commit('setActiveInput', '')" 
                        name="name" 
                        autocomplete="off" 
                        v-model="user_name" 
                        rules="required|max:100|nameCase"
                        class="login-plc"
                        autofocus
                    />
                    <span class="valid-error post-error" v-if="vali">{{ errors.name }}</span>
                </div>
            </div>
            <div class="row" :class="{'mt-30' : register == true }">
                <!-- <label for="password" class="col-md-4 col-form-label text-md-right">{{passwordreset ? $t('newPassword') : $t('password')}}</label> -->
                <div class="col-md-6" style="position:relative;">
                    <span :class="{smallPlc : $store.state.activeInput == 'user_password' || user_password.length}" class="form-plc">{{passwordreset ? $t('newPassword') : $t('password')}}</span>
                    <Field 
                        id="password" 
                        @focus="$store.commit('setActiveInput', 'user_password')" 
                        @blur="$store.commit('setActiveInput', '')" 
                        name="password"
                        type="password" 
                        autocomplete="off" 
                        v-model="user_password" 
                        rules="required|passwordCase|min:8"
                        class="login-plc"
                    />
                    <span class="valid-error post-error" v-if="vali">{{ errors.password }}</span>
                </div>
            </div>

            <div class="row mt-30">
                <!-- <label for="password" class="col-md-4 col-form-label text-md-right">{{passwordreset ? $t('newPasswordConfirm') : $t('passwordConfirmation')}}</label> -->
                <div class="col-md-6" style="position:relative;">
                    <span :class="{smallPlc : $store.state.activeInput == 'user_passwordconfirm' || user_passwordconfirm.length}" class="form-plc">{{passwordreset ? $t('newPasswordConfirm') : $t('passwordConfirmation')}}</span>
                    <Field 
                        id="password-confirm" 
                        @focus="$store.commit('setActiveInput', 'user_passwordconfirm')" 
                        @blur="$store.commit('setActiveInput', '')" 
                        name="password_confirmation" 
                        autocomplete="off"
                        type="password" 
                        v-model="user_passwordconfirm" 
                        class="login-plc"
                        rules="confirmed:@password"
                    />
                    <small class="valid-error post-error" v-if="passwordConfirmationError">{{ passwordConfirmationError }}</small>
                    <span class="valid-error post-error" v-if="vali">{{ errors.password_confirmation }}</span>
                </div>
            </div>
            <div class="row mt-30" v-if="register">
                <router-link class="jump-link" :to="`/auth/${locale}/terms-of-service`">{{ $t('jumpToTerms') }}</router-link>
            </div>
            <div class="row mt-30" v-if="register">
                <div class="termscheck-wrapper">
                    <label class="check-container" style="align-self: center;">
                        <input id="termsCheck" name="termsCheck" v-on:change="termsAgree" type="checkbox">
                        <span class="checkmark-mini-task"></span>
                    </label>
                    <span>{{$t('agreeTerms')}}</span>
                </div>
                <span class="valid-error post-error" v-if="!checkPls && vali">{{ termsValid }}</span>
            </div>

            <div class="row mb-0">
                <div class="col-md-6 offset-md-4">
                    <button type="submit" style="line-height: 0; width:100%;"  class="btn btn-primary login-btn-change mt-40" :disabled="processing">
                        <span v-if="!processing">{{passwordreset ? $t('forgotPassword') : $t('registerButton')}}</span>
                        <div v-if="processing" id="loaderMini">
                            <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                        </div>
                    </button>
                </div>
            </div>
            </form>
        </Form>
    </div>
</template>
<script>
    import { Field, Form } from 'vee-validate'
    
    export default{
        props: ['intended', 'user_id', 'register', 'passwordreset', 'user_email', 'locale'],
        components: {
            Field,
            Form
        },
        data(){
            return {
                user_name: '',
                user_password: '',
                user_passwordconfirm: '',
                passwordConfirmationError: '',
                processing: false,
                vali: false,
                nameVali: true,
                termsValid: '',
                checkPls: false,
            }
        },
        mounted(){
            
        },
        methods: {
            termsAgree(){
                let checked = document.getElementById('termsCheck').checked
                if(checked){
                    this.checkPls = true
                }else{
                    this.checkPls = false
                }
            },  
            registerSuccess: async function(){
                const result = await this.$refs.form.validate();

                if(this.processing) return

                if (result.valid && this.checkPls || result.valid && this.passwordreset){
                    this.processing = true
                    
                    if(this.register){
                        axios.post('/register/complete', {
                            name: this.user_name,
                            password: this.user_password,
                            user_id: this.user_id,
                            password_confirmation: this.user_passwordconfirm,
                        }).then(response => {
                            const url = this.intended ? this.intended : '/'
                            window.location.href = url
                            this.processing = false
                        }).catch(error => {
                            this.vali = false
                            this.processing = false
                            if(error.response.status === 422){
                                const data = error.response.data;
                                if(data.errors){
                                    this.passwordConfirmationError = this.$t('confPassError')
                                }
                            }
                            
                            // 111111111111111111111111
                        })
                    }else if(this.passwordreset){
                        axios.post('/register/complete', {
                            password: this.user_password,
                            user_login: this.user_email,
                            password_confirmation: this.user_passwordconfirm,
                        }).then(response => {
                            const url = this.intended ? this.intended : '/'
                            window.location.href = url
                            this.processing = false
                        }).catch(error => {
                            this.vali = false
                            this.processing = false
                            if(error.response.status === 422){
                                const data = error.response.data;
                                if(data.errors){
                                    this.passwordConfirmationError = this.$t('confPassError')
                                }
                            }
                        })
                    }
                }else{
                    this.vali = true
                    this.passwordConfirmationError = ''
                    this.termsValid = this.$t('readTerms')
                }
            },
        }
    }
</script>

<style scoped lang="scss">
    .mt-30{
        margin-top: 30px;
    }
    .mt-30 a{
        word-break: break-word;
    }
    .termscheck-wrapper{
        display: flex;
    }
    
</style>