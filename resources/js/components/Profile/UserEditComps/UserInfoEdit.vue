<template>
<div class="overlay" v-if="UserAllData">
    <Transition name="slidePop">
            <div v-if="saved" class="copySuccess">    
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 38 32" fill="#fff">
                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                </svg>
                <span>{{ $t('profileUpdated') }}</span>
            </div>
        </Transition> 
    <div class="chatCreate scrollable">        
        
            <div class="recordFormTitle">    
                <div @click="$router.go(-1)" style="margin: 2px 10px 0 0;" v-if="$store.state.mobile">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>                    
                </div>     
                <p>{{$t('editInfo')}}</p>
                 
                <div v-if="!$store.state.mobile" class="cursor-pointer" @click="$router.go(-1)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>              
                <div class="si-box">
                    <FormShortText 
                        :initialValue="userPhone"
                        ref="userPhone"
                        placeHolder="電話番号"
                        uId="userPhone"
                        name="userPhone"
                        rules=""
                        label="電話番号"
                        @setValue="val => userPhone = val"
                    />
                </div>
                <div class="si-box">
                    <FormShortText 
                        :initialValue="userMail"
                        ref="userMail"
                        placeHolder="メールアドレス"
                        uId="userMail"
                        name="userMail"
                        rules=""
                        label="メールアドレス"
                        @setValue="val => userMail = val"
                    />
                </div>
                <div class="si-box">
                    <FormShortText 
                        :initialValue="userMotto"
                        ref="userMotto"
                        placeHolder="好きな言葉"
                        uId="userMotto"
                        name="userMotto"
                        rules=""
                        label="好きな言葉"
                        @setValue="val => userMotto = val"
                    />
                </div>
                <div class="si-box">
                    <FormShortText 
                        :initialValue="userEnjoy"
                        ref="userEnjoy"
                        placeHolder="私の「楽」"
                        uId="userEnjoy"
                        name="userEnjoy"
                        rules=""
                        label="私の「楽」"
                        @setValue="val => userEnjoy = val"
                    />
                </div>
                <div class="si-box">
                    <FormLongText 
                        :initialValue="userIntro"
                        ref="userIntro"
                        placeHolder="自己紹介"
                        uId="userIntro"
                        name="userIntro"
                        rules=""
                        label="自己紹介"
                        @setValue="val => userIntro = val"
                    />
                </div>
                <div class="si-box">
                    <FormShortText 
                        :initialValue="userRecommend"
                        ref="userRecommend"
                        placeHolder="推し"
                        uId="userRecommend"
                        name="userRecommend"
                        rules=""
                        label="推し"
                        @setValue="val => userRecommend = val"
                    />
                </div>
                <!-- <div class="si-box">
                    <UserFileUploader
                        :initialValue="userImages"
                        @updated="val => userImages = val"
                        @cancel="val => cancelImages = val"
                        :path="'/user_album/' + UserAllData.id"
                        :intro_flag="2"
                        placeholder="推しの写真アップロード"
                    />
                </div> -->
                <div class="si-box">
                    <FormLongText 
                        :initialValue="userAwareness"
                        ref="userAwareness"
                        placeHolder="自己認識"
                        uId="userAwareness"
                        name="userAwareness"
                        rules=""
                        label="自己認識"
                        @setValue="val => userAwareness = val"
                    />
                </div>
                <div class="si-box">
                    <LoaderButton 
                        @triggered="profileEditSend()" 
                        :loading="processing" 
                        content="保存する"
                    />
                </div>
                

    </div>
</div>
</template>
<script>
import UserFileUploader from './UserFileUploader.vue'
import LoaderButton from '../../Global/LoaderButton.vue';
import { markRaw } from 'vue';
import FormShortText from '../../Global/FormShortText.vue';
import FormLongText from '../../Global/FormLongText.vue';
    export default {
        components: {
            UserFileUploader,
            LoaderButton,
            FormShortText,
            FormLongText
        },
        props: ['UserAllData', 'albumImages'],
        data(){
            return{
                processing: false,
                login_token: '',
                country_code: '',
                user_login: '',
                userEdit: false,
                tagOptions: [],
                editFlag: false,
                saved: false,
                userMail: this.UserAllData && this.UserAllData.work_email ? this.UserAllData.work_email : '',
                userIntro: this.UserAllData && this.UserAllData.intro ? this.UserAllData.intro : '',
                userAwareness: this.UserAllData && this.UserAllData.awareness ? this.UserAllData.awareness : '',
                userMotto: this.UserAllData && this.UserAllData.motto ? this.UserAllData.motto : '',
                userEnjoy: this.UserAllData && this.UserAllData.enjoy ? this.UserAllData.enjoy : '',
                userRecommend: this.UserAllData && this.UserAllData.recommend ? this.UserAllData.recommend : '',
                userPhone: this.UserAllData && this.UserAllData.phone_number ? this.UserAllData.phone_number : '',
                userImages: this.albumImages ? this.albumImages : [],
                cancelImages: [],
                phoneVali: true,
                showName: null,
                Deselect: markRaw({
                    template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
                }),
                isEnter: false,
                uploading: false,
                noUploading: true
            }
        },
        methods: {
            profileEditSend(index) {
                const params = {
                    phone_number: this.userPhone,
                    work_email: this.userMail,
                    intro: this.userIntro,
                    awareness: this.userAwareness,
                    motto: this.userMotto,
                    enjoy: this.userEnjoy,
                    recommend: this.userRecommend,
                    uploadedImages : this.userImages.length ? this.userImages : [], 
                    path: '/user_album/' + this.UserAllData.id,
                    intro_flag: 2,
                    canceledImageIds: this.cancelImages.length ? this.cancelImages : [],
                }               
                
                if(this.processing) return

                this.processing = true
                axios.post('/profile_profile_edit_api', params)
                    .then(response => {
                        this.processing = false
                        
                        if(response.data == "saved"){
                            this.getUserInfo()
                            this.saved = true
                            this.$router.go(-1)
                            setTimeout(() => {
                                this.saved = false
                            }, 1500);   
                        }
                    }).catch(error => {
                        
                    })
            },
            getUserInfo(){
                this.$emit('getUserInfo');
            },
            
           
            validPhone(index){
                
                const input = document.getElementById("input-" + index);
                const value = input.value;
                if(value){
                    const valInput = /^\d{6,14}$/.test(value);

                    this.inputs[index].valid = valInput
                }
                
            },  
            validEmail(index){
                const input = document.getElementById("input-" + index);
                const value = input.value;
                if(value){
                    const valInput = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

                    this.inputs[index].valid = valInput
                }
                
            }            
        },
        
    }
</script>
<style lang="scss" scoped>
    .vs__dropdown-menu {
        position: unset !important;
    }
    .inactiveMask{
        width: 100%;
        height: 100%;
        top:0;
        left:0;
        background-color: lightgray;
        opacity: 0.6;
        position: absolute;
    }
    .countrySelect{
        -webkit-appearance: none;
        appearance: none;
        padding: 5px 10px;
        line-height: 20px;
    }
    input:autofill {
        -webkit-text-fill-color: var(--primary-color);
    }
    .pb-35{
        padding-bottom: 35px !important;
    }
    .recordText option{
        background-color: var(--background-color);
    }
    .verify-wrapper{
        display: flex;
        justify-content: center;
    }
    .input-wrapper{
        height: auto; 
        width:100%;
        position: relative;
        transition: opacity 0.2s;
      }
      .firstname_wrapper{
        width: 53%;
        display: flex;
        align-items: center;
        margin-left: 40px;
    }
    .recordText-user {
        width: 100%;
        height: 40px;
        margin: 0 auto;
        padding: 0px;
        line-height: 48px;
        font-size: 16px;
        text-indent: 16px;
        border: 1px solid #ccc;
        box-sizing: border-box;
      }
      .recordText-user.firstname{
        float:right; width:95%;
      }
      .lastname_wrapper{
        width: 43%;
    }
    .infoCard{
        margin-top: 10px;
        margin-bottom: 20px;
        position: relative;
        display: flex;
        justify-content: space-between;
    }
    .phoneSelector {
        width: 200px;
        padding: 10px;
        font-size: 16px;
        border-radius: 5px;
        border: 1px solid #ccc;
        background-color: #f7f7f7;
    }
    .recordText option {
        padding: 10px;
        border-radius: 0;
    }
    .recordEditButton {
        padding: 5px 10px 5px 10px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 0px;
        background: var(--primary-button);
        color: #fff;
    }
    .align-center{
        align-items: center;
    }
    .button-wrapper{
        display: flex;
        position: absolute;
        right: 12px;
        top: 8px;
        z-index: 3;
    }
    .textarea-button{
        display: flex;
        position: absolute;
        right: 12px;
        bottom: 8px;
        z-index: 3;
    }
    .mr-20{
        margin-right: 10px;
    }
    .input-wrapper [disabled]{
        cursor: not-allowed;
    }
    .profileEditModal{
        width: 50%;
        max-height: 80%;
        background: var(--background-color);
        position: relative;
        height: auto;
        overflow: hidden auto;
    }
    .profileEditInput:focus{
        border: solid 1px var(--hoverBorder);
    }
    @media screen and (max-width: 959px){
        .profileEditModal{
            width: 100%;
            height: 100%; 
            max-height: 100%;       
        }
        .mr-20{
            margin-right: 5px;
        }
        .input-wrapper{
            width: 100%;
        }
        .pb{
            width: 90% !important;
        }
        .infoCard{
            display: block;
            font-size: 12px;
        }
        .recordEditButton{
            height: 25px;
            font-size: 12px;
            line-height: 0;
            padding: 0 5px 0 5px;
        }
        .button-wrapper{
            top: 7px;
            right: 4px;
        }
        .textarea-button{
            bottom: 6px;
            right: 4px;
        }
    } 
</style>