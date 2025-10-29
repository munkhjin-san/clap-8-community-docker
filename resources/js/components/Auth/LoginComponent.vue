<template>
<div class="login-wrapper" style="display: flex;height: 100%;">
    <div class="login-body">
        <form class="login-form" action="/login" method="post" ref="loginForm">
            <div class="login-header" style="display: flex;justify-content: center;margin-bottom: 20px;">   
                <Logo/>
            </div>
            <div class="login-content">                
                <div class="login-group row">
                    <div class="form-wrapper">
                        <input 
                            id="login" 
                            type="text"   
                            class="login-plc" 
                            name="login" 
                            rules="required|max:100" 
                            required 
                            autocomplete="login" 
                            autofocus
                        />
                        <label class="form-plc">アカウント名</label>
                    </div>
                </div>
                <input type="text" name="username" hidden autocomplete="login"/>
                
                <div class="login-group row" style="margin-top: 30px;">                         
                    <div class="form-wrapper">
                        <input 
                            id="password" 
                            type="password" 
                            class="login-plc" 
                            name="password" 
                            rules="required" 
                            required 
                            autocomplete="current-password"
                        />
                        <label class="form-plc">パスワード</label>
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <div class="si-box">
                        <button class="btn btn-primary login-btn-change" type="submit">ログイン</button>
                    </div>
                    <input type="hidden" name="_token" :value="csrfToken">
                </div>
                <div class="login-group" v-if="errorMessage">
                    <p class="valid-error">{{ errorMessage }}</p>
                </div>
                <div class="login-group" v-if="errors && errors.length">
                    <p class="valid-error" v-for="error in errors">{{ error }}</p>
                </div>
            </div>
        </form>
    </div>
</div>
</template>
<script setup>
    import Logo from '../Global/Logo.vue'
    import { onMounted, ref } from 'vue';
    const props = defineProps(['message', 'errors'])
    const errorMessage = ref(null)
    const tempNum = ref(null)
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content
    const loginForm = ref(null)
    onMounted(() => {
        window.document.title = `ログイン`; 
        if(props.message){
            errorMessage.value = props.message
        } else {
            errorMessage.value = sessionStorage.getItem('loginError')
            sessionStorage.removeItem('loginError')    
        }
        localStorage.removeItem('hiding_alerts')

    })     


</script>

<style lang="scss">
    input:focus + label, input:valid + label{
        font-size: 11px;
        top: 15px;
        left: 15px;
        color: var(--primary-color);
        transform: translateY(-50%);
    }
    input:autofill {
        -webkit-text-fill-color: var(--primary-color);
    }
    input:-webkit-autofill + label {
        font-size: 11px;
        top: 15px;
        left: 15px;
        color: var(--primary-color);
        transform: translateY(-50%);
    }

    /* Ensure the same styles are applied in all cases */
    input:-webkit-autofill:focus + label,
    input:-webkit-autofill:valid + label {
        font-size: 11px;
        top: 15px;
        left: 15px;
        color: var(--primary-color);
        transform: translateY(-50%);
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
        font-size: 13px;
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
    .list-wrapper {
        text-align: center;
        display: flex;
        flex-wrap: nowrap;
        justify-content: space-around;
        padding: 25px 0 5px;
        border: 1px solid var(--primary-color);
    }
    .list-item{
        height: 40px;
        width: 40px;
        min-width: 40px;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        display: flex;
        cursor: pointer;
    }
    .list-item:hover{
        background: #efefef;
    }
 
    @media screen and (max-width: 959px){
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
    }
    
</style>
