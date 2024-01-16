<template>                    
    <div class="chatCreate scrollable">
        <div id="postCreateWindow" style="background:inherit">
            <Form v-slot="{ errors }" ref="form" style="background:inherit">           
                <div class="recordFormTitle" style="display:flex;">
                <p v-if="editFlag == false">新しいユーザーを作成する</p>
                <p v-if="editFlag == true">ユーザーを編集する</p>
                <div class="cursor-pointer" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
                <div class="input-wrapper mt-20">
                    <span  class="form-plc smallPlc">ログインID</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <Field @keydown.space.prevent class="recordText-user" v-model="user_login" type="text" rules="required|max:48" name="login" />
                            <span class="valid-error post-error">{{ errors.login }}</span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span  class="form-plc smallPlc">苗字</span> 
                    <div class="lastname_wrapper">
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_lastname" type="text" name="lastname" rules="required" />
                            <span class="valid-error post-error">{{ errors.lastname }}</span>
                        </div>                    
                    </div>
                    <div class="firstname_wrapper">
                        <span  class="form-plc smallPlc">名前</span> 
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_firstname" type="text" name="firstname" />
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span  class="form-plc smallPlc">苗字かな</span> 
                    <div class="lastname_wrapper">
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_kanalast" type="text" name="name_kanalast" rules="required" />
                            <span class="valid-error post-error">{{ errors.name_kanalast }}</span>
                        </div>                    
                    </div>
                    <div class="firstname_wrapper">
                        <span  class="form-plc smallPlc">名前かな</span> 
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_kanafirst" type="text" name="name_kanafirst" />
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span  class="form-plc smallPlc">メール</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_email" type="email" name="email" rules="required"/>
                            <span class="valid-error post-error">{{ errors.email }}</span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                        <div v-if="editForm == true" class="input-inner-wrapper">
                            <button @click="passwordChange()" class="btn btn-primary password-btn">パスワードの変更</button>
                        </div>
                    <div class="w-100" style="background:inherit;" v-if="editForm == false">
                        <span  class="form-plc smallPlc">パスワード</span> 
                        <div class="input-inner-wrapper">
                            <div style="position:relative">
                                <Field class="recordText-user" v-model="user_password" :type="showPassword ? 'text' : 'password'" name="user_password" rules="required" />
                                <svg @click="passwordShow" v-if="showPassword" style="position:absolute;right:10px;width:20px;bottom:12px;cursor:pointer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c5.2-11.8 8-24.8 8-38.5c0-53-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zm223.1 298L373 389.9c-16.4 6.5-34.3 10.1-53 10.1c-79.5 0-144-64.5-144-144c0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5z"></path></svg>
                                <svg @click="passwordShow" v-else style="position:absolute;right:10px;width:20px;bottom:12px;cursor:pointer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM432 256c0 79.5-64.5 144-144 144s-144-64.5-144-144s64.5-144 144-144s144 64.5 144 144zM288 192c0 35.3-28.7 64-64 64c-11.5 0-22.3-3-31.6-8.4c-.2 2.8-.4 5.5-.4 8.4c0 53 43 96 96 96s96-43 96-96s-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6z"></path></svg>
                            </div>
                            <span class="valid-error post-error">{{ errors.user_password }}</span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span  class="form-plc smallPlc">役職</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <select class="recordText-user dropdown" @change="awardSelect" v-model="positions" name="positions">
                                <option :key="index" v-for="(item , index) in positionData" :value="item.value">{{ item.label }}</option>
                            </select>  
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20" v-if="partnerForm == false">
                    <span  class="form-plc smallPlc">営業所</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <select class="recordText-user dropdown" v-model="offices" name="offices">
                                <option :key="index" v-for="(item , index) in officeData" :value="item.value">{{ item.label }}</option>
                            </select>  
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20">
                    <span  class="form-plc smallPlc">電話番号</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-user" v-model="user_phone" type="text" name="phone_number">
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper-memo mt-20">
                    <span  class="form-plc smallPlc">メモ</span> 
                    <div class="input-inner-wrapper">
                        <textarea class="recordTextArea-user" v-model="user_memo" name="user_memo" ></textarea> 
                    </div>
                </div>
                <div class="input-wrapper-memo mt-20" v-if="partnerForm == false" style="flex-direction:column">
                    <span class="user form-label">メンバーページに表示</span>
                    <div class="input-inner-wrapper" style="margin-top:10px;">
                        
                        <label class="check-container user" style="align-self: center;margin:auto">
                            <input id="membershow" type="checkbox" :true-value="1" :false-value="0" v-model="member_show" name="member_show">
                            <span class="checkmark-mini" style="width: 18px; height:18px"></span>
                            <label for="membershow">表示しない</label>
                        </label>
                    </div>
                </div>
                <p v-if="partnerForm == false" class="user-header">ワーク設定</p>
                                                
                <div class="input-wrapper mt-20" v-if="partnerForm == false">
                    <span  class="form-plc smallPlc">社員コード</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="user_code" type="number" name="user_code" rules="required"/>
                            <span class="valid-error post-error">{{ errors.user_code }}</span>
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper mt-20" v-if="partnerForm == false" style="flex-direction:column">
                    <span class="user form-label">雇用形態</span>
                    <div style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap; width: 100%;margin-top:10px;">
                        <div @click="work_type = 0" :class="['ch-selector', { chSelected: work_type == 0}]">フレックス</div>
                        <div @click="work_type = 1" :class="['ch-selector', { chSelected: work_type == 1}]">通常</div>
                    </div>
                </div>
                <div class="input-wrapper mt-20" v-if="partnerForm == false">
                    <span  class="form-plc smallPlc">1日の稼働時間(単位：分)</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <Field class="recordText-user" v-model="work_time_day" type="number" name="work_time_day" rules="required" />
                            <span class="valid-error post-error">{{ errors.work_time_day }}</span>
                        </div>                    
                    </div>
                </div>
                
                <div class="input-wrapper mt-20" v-if="partnerForm == false">
                    <span style="z-index: 1; background-color:var(--background-color);" class="form-plc smallPlc">ワークグループ</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <v-select class="recordText-user" v-model="workgroup" name="workgroup" multiple :options="workGroup">
                            </v-select>  
                        </div>                    
                    </div>
                </div>
                <p v-if="editForm == false && partnerForm == false"  class="user-header">チャレンジ設定</p>
                <div class="input-wrapper mt-20" v-if="editForm == false && partnerForm == false">
                    <span style="z-index: 1; background-color:var(--background-color);" class="form-plc smallPlc">チャージ額</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-user" v-model="award_charge" name="award_charge" disabled>    
                        </div>                    
                    </div>
                </div>
                <div class="input-wrapper-memo mt-20" v-if="editForm == true" style="flex-direction:column">
                    <span class="user form-label" style="color:red;">退職</span>
                    <div class="input-inner-wrapper" style="margin-top:10px">
                        
                        <label class="check-container user" style="align-self: center;">
                            <input id="retire" type="checkbox" :true-value="1" :false-value="0" v-model="user_retire" name="user_retire">
                            <span class="checkmark-mini" style="width: 18px; height:18px"></span>
                            <label for="retire">{{ checked }}</label>
                        </label>
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
       

        import { Field, Form  } from 'vee-validate'

        

        export default {
            props:['positionData', 'officeData', 'editFlag', 'editUserData', 'workGroup', 'passwordFlag'],
            components: {
                Field,
                Form,
            },
            data (){
                return {
                    user_firstname: "",
                    user_lastname: "",
                    user_login: "",
                    user_kanafirst: "",
                    user_kanalast: "",
                    user_kana: "",
                    user_name: "",
                    user_email: "",
                    user_password: "",
                    processing: false,
                    user_phone: "",
                    user_memo: "",
                    showPassword: false,
                    password: '',
                    editForm: this.editFlag,
                    partnerForm: false,
                    positions: "",
                    offices: "",
                    work_type: 0,
                    checked: '退職者',
                    user_retire: 0,
                    workgroup:[],
                    work_time_day: '',
                    user_code: '',
                    member_show: 0,
                    award_charge: ''
                }
            },
            
            computed: {
              
            },
            methods : {
                closeModal(flag){
                    document.body.classList.remove("modal-open");
                    this.processing = false
                    this.$emit('postFinish',flag);     
                },
                userAdd: async function(){
                    const result = await this.$refs.form.validate();
                    if (this.processing) return;

                    if (result.valid){
                        this.processing = true;
        
                        if(this.user_firstname){
                            this.user_name = this.user_lastname + ' ' + this.user_firstname
                        }else{
                            this.user_name = this.user_lastname
                        }

                        if(this.user_kanafirst){
                            this.user_kana = this.user_kanalast + ' ' + this.user_kanafirst
                        }else{
                            this.user_kana = this.user_kanalast
                        }

                        const params = {
                            user_login : this.user_login,
                            user_name : this.user_name,
                            user_kana : this.user_kana,
                            user_email: this.user_email,
                            user_phone: this.user_phone,
                            user_password: this.user_password,
                            user_positions : this.positions,
                            user_offices : this.offices,
                            user_partner_flag : this.partner_flag,
                            user_memo : this.user_memo,
                            user_code : this.user_code,
                            user_work_type : this.work_type,
                            user_work_time_day : this.work_time_day,
                            user_award_charge: this.award_charge,
                            user_work_group : this.workgroup,
                            user_member_show : this.member_show,
                        };

                        axios.post('/user_add', params).then(response => {
                            this.closeModal(true)
                        }).catch(function (error) {
                            if (error.response){
                                if(error.response.data.errors.user_login){
                                    this.errorToast('エラーが発生しました。 ' + error.response.data.errors.user_login[0])
                                }else{
                                    this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                                }
                            }
                            else if (error.request) this.errorToast('エラーが発生しました。')
                            else this.errorToast('エラーが発生しました。 ' + error.message)     
                            this.processing = false                      
                        }.bind(this));
                    }
                },
                errorToast(message){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: message,
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK']
                    })   
                },
                editUser(){
                    this.user_login = this.editUserData.login
                    if(this.editUserData.name){
                        this.user_name = this.editUserData.name.replaceAll('　', ' ')
                        this.user_lastname = this.user_name.split(' ')[0]
                        this.user_firstname = this.user_name.split(' ')[1]
                    }
                    
                    if(this.editUserData.name_kana){
                        this.user_kana = this.editUserData.name_kana.replaceAll(/　/g, ' ')
                        this.user_kanalast = this.user_kana.split(' ')[0]
                        this.user_kanafirst = this.user_kana.split(' ')[1]
                    }
                    this.user_email = this.editUserData.email
                    // 
                    if(this.editUserData.positions){
                        this.positions = this.editUserData.positions.id
                        if(this.positions === 14){
                            this.partnerForm = true
                        }else{
                            this.partnerForm = false
                        }
                    }
                    
                    this.partner_flag = this.editUserData.partner_flag

                    if(this.editUserData.offices){
                        this.offices = this.editUserData.offices.id
                    }
                    
                    this.user_phone = this.editUserData.phone_number
                    this.user_retire = this.editUserData.retire
                    this.member_show = this.editUserData.hide_flag
                    this.user_code = this.editUserData.user_code
                    this.work_time_day = this.editUserData.work_time_day
                    this.work_type = this.editUserData.work_type
                    
                    if(this.editUserData.user_detail){
                        this.user_memo = this.editUserData.user_detail.memo
                    }
                },
                editUserSend: async function(){
                    const result = await this.$refs.form.validate();
                    if (this.processing) return;

                    if (result.valid){
                        this.processing = true;

                        if(this.user_firstname){
                            this.user_name = this.user_lastname + ' ' + this.user_firstname
                        }else{
                            this.user_name = this.user_lastname
                        }

                        if(this.user_kanafirst){
                            this.user_kana = this.user_kanalast + ' ' + this.user_kanafirst
                        }else{
                            this.user_kana = this.user_kanalast
                        }

                        const params = {
                            user_id : this.editUserData.id,
                            user_login : this.user_login,
                            user_name : this.user_name,
                            user_kana : this.user_kana,
                            user_email: this.user_email,
                            user_phone: this.user_phone,
                            user_password: this.user_password,
                            user_positions : this.positions,
                            user_offices : this.offices,
                            user_partner_flag : this.partner_flag,
                            user_memo : this.user_memo,
                            user_retire : this.user_retire,
                            user_code : this.user_code,
                            user_work_type : this.work_type,
                            user_work_time_day : this.work_time_day,
                            user_work_group : this.workgroup,
                            user_member_show : this.member_show
                        };
                        axios.post('/user_edit', params).then(response => {
                            this.closeModal(true)
                        }).catch(function (error) {
                            if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                            else if (error.request) this.errorToast('エラーが発生しました。')
                            else this.errorToast('エラーが発生しました。 ' + error.message)     
                            this.processing = false                      
                        }.bind(this));
                    }
                },
                passwordShow() {
                    this.showPassword = !this.showPassword
                },
                passwordChange() {
                    this.editForm = false
                },
                awardSelect() {
                    console.log(this.positions)
                    if(this.positions < 7 || this.positions === 10){
                        this.award_charge = '15000'
                        this.partnerForm = false
                        this.partner_flag = 0
                    }else if(this.positions === 7){
                        this.award_charge = '9000'
                        this.partnerForm = false
                        this.partner_flag = 0
                    }else if(this.positions === 8 || this.positions === 9){
                        this.award_charge = '6000'
                        this.partnerForm = false
                        this.partner_flag = 0
                    }else if(this.positions === 11){
                        this.award_charge = '3000'
                        this.partnerForm = false
                        this.partner_flag = 0
                    }else if(this.positions === 14){
                        this.partnerForm = true
                        this.partner_flag = 1
                        this.award_charge = '0'
                    }else{
                        this.partner_flag = 0
                        this.award_charge = '0'
                        this.partnerForm = false
                    }
                },
            },
            
            mounted() {
                if(this.editUserData){
                    for(const work_guser of this.workGroup){
                        for(const work_group of this.editUserData.work_group_user){
                            if(work_guser.value == work_group.record_id && work_group.deleted_flag == 0){
                                this.workgroup.push({
                                    value : work_guser.value,
                                    label: work_guser.label
                                })
                            }
                        }
                    }
                    this.editUser()
                }
            },
    
        }
    
    </script>
    <style scoped lang="scss">
        .user-header{
            margin: 20px 0px;
            font-size: 17px;
            padding: 5px 10px 5px 0px;
            height: fit-content;
            line-height: 1.2;
        }
        .post-error{
            bottom: -12px !important;
        }
        .dropdown{
            background-color: var(--background-color);
            -webkit-appearance: none;
            appearance: none;
        }
        .recordText-user {
            width: -webkit-fill-available;
            margin: 0 auto;
            padding: 0px;
            border: 1px solid var(--formBorder);
            padding: 10px;
            color: inherit;
            width: -moz-available;
            font-size: 16px;
            line-height: 1.6;
            transition: border 0.3s ease;
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
            color:var(--primary-color);
          }
          input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
          .password-btn{
            color: var(--primary-color); 
            background: var(--bg2);
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
            position: relative;
            background: inherit;
          }
          .input-wrapper-memo{
            display: flex;
            flex-direction: row;
            position: relative;
            background: inherit;
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
            
          }
          .selectArea {
            height: auto ;
            background-repeat: no-repeat;
            background-position: top 5px right 5px;
        }
        .adminSelect{
            height: 40px;
            border: 1px solid var(--formBorder);
            padding: inherit;
            text-indent: 16px;
        }
        .userFormTitle {
           
            font-size: 17px; 
            margin: -8px 0px 30px;
          }
          .lastname_wrapper{
            width: 50%;
            background: inherit;
        }
        .firstname_wrapper{
            width: 50%;
            display: flex;
            align-items: center;
            margin-left: 20px;
            position: relative;
            background: inherit;
        }
        @media screen and (max-width: 959px){
            .input-wrapper{
                display: block;
            }
            .input-wrapper-memo{
                display: block;
            }
            .w-100{
                margin-top: 10px;
            }
            .userFormTitle > p{
                font-size: 18px;
            }
            .recordText-user.firstname{
                width:100%;
                margin-top: 10px;
                margin-bottom: 20px;
              }
            .lastname_wrapper {
                width: 100%;
                margin-top: 10px;
            }
            .firstname_wrapper{
                width: 100%;
                display: block;
                margin-left: 0;
                margin-top: 20px;
            }
            .mobile_mt10{
                margin-top: 10px;
            }
        }
         
    
    </style>
        
        
        
        
        