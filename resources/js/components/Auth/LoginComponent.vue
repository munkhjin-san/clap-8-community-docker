<template>
<div class="container login-wrapper" style="display: flex;height: 100%;">
    <div class="login-body">
        <form class="login-form" action="/login" method="post">
            <div class="login-header" style="display: flex;justify-content: center;margin-bottom: 20px;">   
                <Logo/>
            </div>
            <div class="login-content">                
                <div class="login-group row">
                    <div class="col-md-6" style="position: relative;">
                        <span class="smallPlc form-plc" style="background: var(--background-color);">アカウント名</span>
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
                    </div>
                </div>
                <input type="text" name="username" hidden autocomplete="login"/>
                
                <div class="login-group row" style="margin-top: 30px;">                         
                    <div class="col-md-6" style="position: relative;">
                        <span class="smallPlc form-plc"  style="background: var(--background-color);">パスワード</span>
                        <input 
                            id="password" 
                            type="password" 
                            class="login-plc" 
                            name="password" 
                            rules="required" 
                            required 
                            autocomplete="current-password"
                        />
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <div class="si-box">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button class="btn btn-primary login-btn-change" type="submit">ログイン</button>
                    </div>
                </div>
                <div class="login-group" v-if="message">
                    <p class="valid-error">{{ message }}</p>
                </div>
            </div>
        </form>
    </div>
</div>
</template>
<script setup>
    import Logo from '../Global/Logo.vue'
    import { onMounted } from 'vue';
    const props = defineProps(['message'])
   
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content
    onMounted(() => {
        window.document.title = `CLAP - ログイン`; 
    })       

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
