<template>                    
    <div class="chatCreate scrollable">
        <div id="postCreateWindow">
            <Form v-slot="{ errors }" ref="form">           
                <div class="userFormTitle" style="display:flex;">
                <p v-if="editFlag == false">新しいユーザーを作成する</p>
                <p v-if="editFlag == true">ユーザーを編集する</p>
                <div class="cursor-pointer" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
                <div class="input-wrapper mt-20">
                    <span class="user form-label">名前</span>
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_name" type="text" name="name" data-action="" placeholder="名前を入力" rules="required" />
                            <span class="valid-error post-error">{{ errors.name }}</span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span class="user form-label">メール</span>
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_email" type="email" name="email" data-action="" rules="required" placeholder="メールを入力"/>
                            <span class="valid-error post-error">{{ errors.email }}</span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                        <span class="user form-label">パスワード</span>
                        <div v-if="editForm == true" class="input-inner-wrapper">
                            <button @click="passwordChange()" class="btn btn-primary password-btn">変更</button>
                        </div>
                    <div class="w-100" v-if="editForm == false">
                        <div class="input-inner-wrapper">
                            <div style="position:relative">
                                <Field class="recordText-user" v-model="user_password" :type="showPassword ? 'text' : 'password'" name="user_password" placeholder="新しいパスワードを入力" rules="required" />
                                <svg @click="passwordShow" v-if="showPassword" style="position:absolute;right:10px;width:20px;bottom:12px;cursor:pointer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c5.2-11.8 8-24.8 8-38.5c0-53-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zm223.1 298L373 389.9c-16.4 6.5-34.3 10.1-53 10.1c-79.5 0-144-64.5-144-144c0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5z"></path></svg>
                                <svg @click="passwordShow" v-else style="position:absolute;right:10px;width:20px;bottom:12px;cursor:pointer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM432 256c0 79.5-64.5 144-144 144s-144-64.5-144-144s64.5-144 144-144s144 64.5 144 144zM288 192c0 35.3-28.7 64-64 64c-11.5 0-22.3-3-31.6-8.4c-.2 2.8-.4 5.5-.4 8.4c0 53 43 96 96 96s96-43 96-96s-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6z"></path></svg>
                            </div>
                            <span class="valid-error post-error">{{ errors.user_password }}</span>
                        </div>                    
                    </div>
                </div>
               
               
                <div class="input-wrapper mt-20">
                    <span class="user form-label">電話番号</span>
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-user" v-model="user_phone" type="text" name="phone_number" data-action="" placeholder="電話番号を入力">
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span class="user form-label">会社</span>
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-user" v-model="user_company" type="text" name="phone_number" data-action="" placeholder="会社を入力">
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span class="user form-label">役職</span>
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-user" v-model="user_occupation" type="text" name="phone_number" data-action="" placeholder="役職を入力">
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span class="user form-label">職業</span>
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-user" v-model="user_profession" type="text" name="phone_number" data-action="" placeholder="職業を入力">
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper-memo mt-20">
                    <span class="user form-label">概論</span>
                    <div class="input-inner-wrapper">
                        <textarea class="recordTextArea-user" v-model="user_memo" name="user_memo" data-action="" placeholder="概論を入力"></textarea> 
                    </div>
                </div>
            
    
                
                
            
                <div v-if="editFlag" class="l-button cursor-pointer" style="margin-top:30px" @click="editUserSend()" :disabled="processing">
                    <span v-if="!processing">保存する</span>
                    <div v-if="processing" id="loaderMini">
                        <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                    </div>
                </div>        
                <div v-else class="l-button cursor-pointer" style="margin-top:30px" @click="userAdd()" :disabled="processing">
                    <span v-if="!processing">保存する</span>
                    <div v-if="processing" id="loaderMini">
                        <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                    </div>
                </div>
            </Form> 
        </div>
    </div>
    
           
    </template>
    <script>
        import {
            enableBodyScroll,
        } from 'body-scroll-lock';

        import { Field, Form  } from 'vee-validate'

        

        export default {
            props:['editFlag', 'editUserData'],
            components: {
                vSelect: window["vue-select"],
                Field,
                Form,
            },
            data (){
                return {
                    user_name: "",
                    user_email: "",
                    user_password: "",
                    processing: false,
                    user_phone: "",
                    user_company: '',
                    user_profession: '',
                    user_occupation: '',
                    user_memo: "",
                    showPassword: false,
                    password: '',
                    editForm: this.editFlag,
                }
            },
            
            computed: {
              
            },
            methods : {
                closeModal(flag){
                    const el = document.getElementById('modalContent')
                    enableBodyScroll(el)
                    document.body.classList.remove("modal-open");
                    this.processing = false
                    this.$emit('postFinish',flag);     
                },
                userAdd: async function(){
                    const result = await this.$refs.form.validate();
                    if (this.processing) return;

                    if (result.valid){
                    this.processing = true;
    
                    const params = {
                        user_name : this.user_name,
                        user_email: this.user_email,
                        user_phone: this.user_phone,
                        user_password: this.user_password,
                        user_memo : this.user_memo,
                        user_company : this.user_company,
                        user_occupation : this.user_occupation,
                        user_profession : this.user_profession
                    };
                    axios.post('admin_account_control/user_add', params).then(response => setTimeout(() => {this.closeModal(true)},0));
                    }
                },
                editUser(){
                    this.user_name = this.editUserData.name
                    this.user_email = this.editUserData.email
                   
                    if(this.editUserData.user_detail){
                        
                        this.user_phone = this.editUserData.user_detail.phone
                        this.user_memo = this.editUserData.user_detail.intro
                        this.user_company = this.editUserData.user_detail.company
                        this.user_occupation = this.editUserData.user_detail.occupation
                        this.user_profession = this.editUserData.user_detail.profession
                    }
                },
                editUserSend: async function(){
                    const result = await this.$refs.form.validate();
                    if (this.processing) return;

                    if (result.valid){
                    this.processing = true;
    
                    const params = {
                        user_name : this.user_name,
                        user_email: this.user_email,
                        user_phone: this.user_phone,
                        user_password: this.user_password,
                        user_memo : this.user_memo,
                        user_company : this.user_company,
                        user_occupation : this.user_occupation,
                        user_profession : this.user_profession,
                        user_id : this.editUserData.id
                    };
                        axios.post('admin_account_control/user_edit', params).then(response => setTimeout(() => {this.closeModal(true)},0));
                    }
                },
                passwordShow() {
                    this.showPassword = !this.showPassword
                },
                passwordChange() {
                    this.editForm = false
                },
            },
            
            mounted() {
                if(this.editUserData){
                    this.editUser()
                }
            },
    
        }
    
    </script>
    <style scoped lang="scss">
@import "vue-select/dist/vue-select.css";

        .user-header{
            margin: 20px 0px;
            font-size: 20px;
            padding: 5px 10px 5px 0px;
            height: fit-content;
            line-height: 1.2;
            font-weight: 600;
        }
        .post-error{
            bottom: -12px !important;
        }
        .recordText-user {
            color:var(--primary-color);
            width: 100%;
            height: 40px;
            margin: 0 auto;
            padding: 0px;
            line-height: 48px;
            font-size: 16px;
            text-indent: 16px;
            border: 1px solid var(--formBorder);
            box-sizing: border-box;
          }
          .recordText-user::placeholder{
            font-size:14px !important;
         } 
          .recordTextArea-user {
            resize: none;
            width: 100%;
            height: 200px;
            padding: 12px 12px 12px 12px;
            line-height: 28px;
            box-sizing: border-box;
            font-size: 16px;
            border: 1px solid var(--formBorder);
            color: var(--primary-color);
          }
          .recordTextArea-user::placeholder{
            font-size: 14px !important;
          }
          .password-change{
            color: gray;
            cursor:pointer;
          }
          .password-change:hover{
            color: #000;
          }
          .vs__search::placeholder{
            font-size: 14px !important;
          }
          .password-wrapper{
            display: flex;
            align-items: center;
            width: 10%;
          }
          .check-container.user {
            display: block;
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
            font-size: medium;
            line-height: 1.3;
            color:#000
          }
          .password-btn{
            background: var(--primary-button);
            color: #fff;
            font-size: 12px;
            white-space: nowrap;
            width: fit-content;
            position:relative;
            min-width: 100px;
            min-height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0 15px;
          }
          .mt-20{
            margin-top: 20px;
          }
          .input-wrapper{
            display: flex;
            flex-direction: row;
            align-items: center;
          }
          .input-wrapper-memo{
            display: flex;
            flex-direction: row;
          }
          .input-inner-wrapper{
            position: relative;
            width:100%;
          }
          .w-100{
            width:100%;
          }
          .user.form-label{
            font-size:16px;
            width: 10%;
          }
          .selectArea {
            height: auto ;
            background-repeat: no-repeat;
            background-position: top 5px right 5px;
        }
        .userFormTitle {
           
            font-size: 17px; 
            margin: -8px 0px 15px;
          }
          
         
    
    </style>
        
        
        
        
        