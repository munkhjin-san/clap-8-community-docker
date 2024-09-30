<template>
<div class="container login-wrapper" style="display: flex;height: 100%;">
    <div class="login-body">
        <form class="login-form" action="/login" method="post" @submit.prevent="handleSubmit" ref="loginForm">
            <div class="login-header" style="display: flex;justify-content: center;margin-bottom: 20px;">   
                <Logo/>
                <!-- <svg style="width: 130px;" fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" id="Layer_2" data-name="Layer 2" viewBox="0 0 48.28 19.45">

                    <path class="cls-1" d="m10.13,14.54c-.19-.4-.5-1.01-.72-1.38-.22.34-.46.69-.77,1.03-.32.35-.65.67-1.03.93-.37.26-.77.44-1.2.52-.39.07-.9-.03-1.24-.23-.32-.19-.52-.46-.63-.8-.12-.35-.14-.77-.11-1.18.04-.4.18-.91.37-1.41.12-.3.27-.6.5-.97.22-.37.53-.76.84-1.1.29-.31.73-.73,1.08-.93.38-.21.78-.34,1.11-.3.33.04.45.32.5.64.05.35.03.86,0,1.24.38-.12.75-.28,1.13-.43.43-.18.95-.41,1.43-.62.36-.16.66-.32.97-.48-.02-.46-.07-.88-.15-1.23-.08-.37-.25-.86-.51-1.26-.25-.38-.55-.69-.98-.98-.48-.32-1.01-.53-1.74-.61-.76-.09-1.77.05-2.43.27-.72.24-1.55.68-2.19,1.16-.7.52-1.28,1.08-1.79,1.67-.52.61-.91,1.21-1.25,1.84-.38.71-.62,1.2-.85,1.9-.22.68-.34,1.23-.41,1.85-.07.67-.08,1.18.01,1.85.1.69.25,1.17.53,1.69.27.5.69,1,1.13,1.31.5.35,1.15.63,1.64.74.53.12,1.21.19,1.84.15.65-.05,1.36-.19,2.04-.46.69-.27,1.27-.61,1.66-.89.42-.31.91-.79,1.14-1.06.24-.28.55-.68.7-1-.15-.41-.43-1.06-.63-1.48Z"/>
                    <path class="cls-1" d="m17.39,14.81c-.8.18-1.63.37-2.28.51.41-1.25,1.13-3.16,1.73-4.62.58-1.42,1.67-4.04,2.4-5.57-1.3.12-4.35.57-5.53.81-.23.8-.54,1.95-.87,3.28-.47,1.87-1.04,4.29-1.49,6.18-.38,1.59-.67,3-.88,3.95,2.18-.32,6.45-.88,8.39-1.16.27-1.08.76-3.06.92-3.95-.79.19-1.59.38-2.39.57Z"/>
                    <path class="cls-1" d="m23.91,5.98c-1.18,2.48-3.99,10.23-4.93,13.32,1.09-.11,3.02-.35,3.92-.48.18-.58.42-1.3.64-1.95l2.96-.5c-.03,1.14-.06,2.08-.07,2.5,1.07-.13,2.55-.33,3.46-.46.13-1.43.38-10.65.39-13.31-1.74.18-4.95.62-6.36.87Zm.52,8.42c.33-.86.7-1.79.96-2.41.41-.98.88-2.03,1.28-2.8,0,.74-.06,2.77-.11,4.8l-2.14.4Z"/>
                    <path class="cls-1" d="m42.21,7.43c-.18-.42-.45-.84-.81-1.16-.4-.36-.74-.61-1.31-.82-.52-.19-1.02-.27-1.76-.29-.7-.02-1.7.13-2.56.31-.9.19-1.85.45-2.8.79-.38,1.36-.67,2.5-.98,3.79-.34,1.43-.71,3.12-.95,4.36-.24,1.23-.64,3.32-.9,4.89,1.01-.15,3.13-.46,3.97-.61.12-.84.31-2.08.48-3.02.77-.23,1.83-.56,2.42-.79.84-.33,1.79-.74,2.47-1.14.65-.38,1.2-.82,1.62-1.24.47-.47.85-1.03,1.08-1.56.22-.52.36-1.22.36-1.86,0-.61-.13-1.22-.33-1.67Zm-3.48,2.9c-.09.18-.24.39-.43.56-.21.2-.48.42-.73.59-.28.19-.71.45-1.11.65-.35.17-.92.42-1.36.57.16-.92.47-2.35.64-3.06,0,0,0,0,0,0,.25-.1.54-.21.81-.28.29-.08.59-.16.87-.2.27-.03.55-.03.75,0,.2.03.38.09.5.22.11.11.17.28.18.43.01.15-.03.36-.11.53Z"/>
                    <path class="cls-1" d="m41.08,3.83c.14-.09.24-.2.33-.32l.26-.36c.18-.24.35-.48.52-.72.17-.24.33-.49.5-.73.15-.25.36-.47.38-.8.01-.19-.04-.39-.16-.55-.27-.38-.8-.47-1.18-.19-.26.19-.34.48-.48.74-.12.27-.24.54-.36.81-.11.27-.23.55-.33.82l-.16.41c-.05.14-.1.28-.1.45,0,.1.02.19.08.28.15.24.47.31.71.16Z"/>
                    <path class="cls-1" d="m42.54,5.12c-.16.16-.31.34-.44.55-.07.12-.09.27-.03.41.1.24.37.36.62.27.23-.09.42-.22.6-.35l.56-.41c.37-.27.74-.54,1.11-.82.36-.28.73-.55,1.09-.84.35-.29.75-.54,1-.94.14-.22.17-.5.05-.75-.18-.4-.65-.58-1.06-.4-.44.19-.74.55-1.08.86-.33.31-.66.64-.99.96-.32.33-.65.65-.97.98l-.48.49Z"/>
                    <path class="cls-1" d="m47.58,6.53c-.33-.05-.6.09-.89.18-.29.1-.57.2-.86.3-.28.11-.57.22-.85.33l-.42.17c-.14.06-.28.12-.41.22-.1.08-.18.2-.2.34-.04.29.16.56.45.6.16.02.32,0,.47-.01l.45-.06c.3-.04.6-.09.9-.13.3-.05.6-.1.9-.16.29-.07.6-.08.86-.29.16-.13.27-.31.3-.52.07-.46-.24-.89-.7-.96Z"/>
                    
                </svg> -->
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
                    <div class="si-box" v-if="tempNum == null">
                        
                        <div @click="viewCondition = true" v-if="!viewCondition" class="btn btn-primary login-btn-change">コンデイションを選択してログイン</div>
                        <div class="list-wrapper focused" v-if="viewCondition">
                            <label class="form-plc">コンデイションを選択してログイン</label>
                            <button class="list-item" type="submit" :style="{background: tempNum === num ? '#efefef' : 'transparent'}" @click="saveWeather(num)" v-for="num in [0,1,2,3,4,5]">
                                <WeatherIcon :which="num" size="25"/>
                            </button>
                        </div>
                    </div>
                    <div v-else class="si-box">
                        <button class="btn btn-primary login-btn-change" type="submit">ログイン</button>
                    </div>
                    <input type="hidden" name="_token" :value="csrfToken">
                </div>
                <div class="login-group" v-if="errorMessage">
                    <p class="valid-error">{{ errorMessage }}</p>
                </div>
            </div>
        </form>
    </div>
</div>
</template>
<script setup>
    import Logo from '../Global/Logo.vue'
    import { onMounted, ref } from 'vue';
    import WeatherIcon from '../Global/WeatherIcon.vue';
    import moment from 'moment';
    const props = defineProps(['message'])
    const viewCondition = ref(false)
    const errorMessage = ref(null)
    const tempNum = ref(null)
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content
    const loginForm = ref(null)
    onMounted(() => {
        window.document.title = `CLAP - ログイン`; 
        if(props.message){
            errorMessage.value = props.message
        } else {
            errorMessage.value = sessionStorage.getItem('loginError')
            sessionStorage.removeItem('loginError')    
        }
        localStorage.removeItem('hiding_alerts')
        tempNum.value = getFromLocalStorage('condition');
    })     
    const saveWeather = (num) => {
        tempNum.value = num
        sessionStorage.setItem('condition_for_session', num)
        handleSubmit()
    }  
    const handleSubmit = () => {
        if (tempNum.value == null) {
            errorMessage.value = 'コンディションを教えてください'
        } else {
            loginForm.value.submit()
        }
    }
    const getFromLocalStorage = (key) => {
        const dataString = localStorage.getItem(key);
        if (!dataString) return null;

        const data = JSON.parse(dataString);
        const now = moment();
        const expirationTime = moment(data.expiration);

        if (now.isAfter(expirationTime)) {
            localStorage.removeItem(key); 
            return null;
        }

        return data.value; 
    };

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
