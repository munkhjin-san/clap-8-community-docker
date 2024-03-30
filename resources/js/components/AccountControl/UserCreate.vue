<template>                    
    <div class="chatCreate scrollable">
        <div id="postCreateWindow" style="background:inherit">
            <div style="background:inherit">           
                <div class="recordFormTitle" style="display:flex;">
                <p> {{ editUserData ? 'ユーザーを編集する' : '新しいユーザーを作成する' }}</p>
                <div class="cursor-pointer" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
                <div class="si-box">
                    <ShortInput 
                        name="login" 
                        placeHolder="ログインID（必須）" 
                        :rules="'required|max:48'"
                        customClass="full"
                        ref="loginRef"
                        type="text"
                        v-model="userParams.login"
                    />
                </div>
                <div class="si-box">            
                    <ShortInput 
                        name="name" 
                        placeHolder="氏名（必須）※半角スペース" 
                        :rules="'required|halfspace'"
                        customClass="full"
                        ref="nameRef"
                        type="text"
                        v-model="userParams.name"
                    />                                        
                </div>
                <div class="si-box">
                    <ShortInput 
                        name="name_kana" 
                        placeHolder="氏名かな（必須）※半角スペース" 
                        :rules="'required|halfspace'"
                        customClass="full"
                        ref="nameKanaRef"
                        type="text"
                        v-model="userParams.name_kana"
                    />                 
                </div>
                <div class="si-box">
                    <ShortInput 
                        name="email" 
                        placeHolder="メール（必須）" 
                        :rules="'required'"
                        customClass="full"
                        ref="emailRef"
                        type="text"
                        v-model="userParams.email"
                    />
                </div>
                <div class="si-box">
                    <div v-if="editUserData" class="input-inner-wrapper" style="margin-bottom: 20px;">
                        <div @click="passwordReset = !passwordReset" class="btn btn-primary password-btn">パスワードの変更</div>
                    </div>
                    <div class="w-100" style="background:inherit;position: relative" v-if="passwordReset || !editUserData">                         
                        <ShortInput 
                            name="user_password" 
                            placeHolder="パスワード（必須）" 
                            :rules="'required'"
                            customClass="full"
                            ref="passwordRef"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="userParams.password"
                        />     
                        <svg @click="showPassword = !showPassword" v-if="showPassword" style="position:absolute;right:10px;width:20px;cursor:pointer;top:0;bottom:0;left:0; margin: auto 0 auto auto;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c5.2-11.8 8-24.8 8-38.5c0-53-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zm223.1 298L373 389.9c-16.4 6.5-34.3 10.1-53 10.1c-79.5 0-144-64.5-144-144c0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5z"></path></svg>
                        <svg @click="showPassword = !showPassword" v-else style="position:absolute;right:10px;width:20px;cursor:pointer;top:0;bottom:0;left:0; margin: auto 0 auto auto;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM432 256c0 79.5-64.5 144-144 144s-144-64.5-144-144s64.5-144 144-144s144 64.5 144 144zM288 192c0 35.3-28.7 64-64 64c-11.5 0-22.3-3-31.6-8.4c-.2 2.8-.4 5.5-.4 8.4c0 53 43 96 96 96s96-43 96-96s-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6z"></path></svg>              
                    </div>
                </div>
                <div class="si-box">
                    <span  class="form-plc smallPlc">役職</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <select class="recordText-user dropdown" v-model="userParams.position_id" name="positions">
                                <option :key="index" v-for="(item , index) in positions" :value="item.value">{{ item.label }}</option>
                            </select>  
                        </div>                    
                    </div>
                </div>
                <div class="si-box" v-if="!isPartner">
                    <span  class="form-plc smallPlc">営業所</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <select class="recordText-user dropdown" v-model="userParams.office_id" name="offices">
                                <option :key="index" v-for="(item , index) in offices" :value="item.value">{{ item.label }}</option>
                            </select>  
                        </div>                    
                    </div>
                </div>
                <div class="si-box">
                    <span  class="form-plc smallPlc">電話番号</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <input class="recordText-user" v-model="userParams.phone_number" type="text" name="phone_number">
                        </div>                    
                    </div>
                </div>
                <div class="si-box" v-if="!isPartner" style="flex-direction:column">
                    <span class="user form-label">メンバーページに表示</span>
                    <div class="input-inner-wrapper" style="margin-top:15px;">                        
                        <label class="check-container user" style="align-self: center;margin:auto">
                            <input id="membershow" type="checkbox" :true-value="1" :false-value="0" v-model="userParams.hide_flag" name="member_show">
                            <span class="checkmark-mini" style="width: 18px; height:18px;top:2px"></span>
                            <label for="membershow">表示しない</label>
                        </label>
                    </div>
                </div>
                <p v-if="!isPartner" class="user-header">ワーク設定</p>
                                                
                <div class="si-box" v-if="!isPartner">                  
                    <ShortInput 
                        name="user_code" 
                        placeHolder="社員コード（必須）" 
                        :rules="'required'"
                        customClass="full"
                        ref="userCodeRef"
                        type="text"
                        v-model="userParams.user_code"
                    />
                </div>
                <div class="si-box" v-if="!isPartner" style="flex-direction:column">
                    <span class="user form-label">雇用形態</span>
                    <div style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap; width: 100%;margin-top:10px;">
                        <div @click="userParams.work_type = 0" :class="['ch-selector', { chSelected: userParams.work_type  == 0}]">フレックス</div>
                        <div @click="userParams.work_type  = 1" :class="['ch-selector', { chSelected: userParams.work_type  == 1}]">通常</div>
                    </div>
                </div>
                <div class="si-box" v-if="!isPartner">
                    <ShortInput 
                        name="work_time_day" 
                        placeHolder="1日の稼働時間（必須）" 
                        :rules="'required'"
                        customClass="full"
                        ref="workTimeRef"
                        type="number"
                        v-model="userParams.work_time_day"
                    />
                </div>
                
                <div class="si-box" v-if="!isPartner">
                    <span style="z-index: 1; background-color:var(--background-color);" class="form-plc smallPlc">ワークグループ</span> 
                    <div class="w-100">
                        <div class="input-inner-wrapper">
                            <drop-selector 
                                class="recordText-user" 
                                style="padding: 0;" 
                                v-model="subParams.workGroup" 
                                name="workgroup"
                                multiple 
                                label="name"
                                :options="workGroups"
                                :components="{Deselect}"
                            >
                            </drop-selector>  
                        </div>                    
                    </div>
                </div>
                <div class="si-box" v-if="editUserData" style="flex-direction:column">
                    <span class="user form-label" style="color:red;">退職</span>
                    <div class="input-inner-wrapper" style="margin-top:10px">
                        
                        <label class="check-container user" style="align-self: center;">
                            <input id="retire" type="checkbox" :true-value="1" :false-value="0" v-model="userParams.retire" name="user_retire">
                            <span class="checkmark-mini" style="width: 18px; height:18px"></span>
                            <label for="retire">退職者</label>
                        </label>
                    </div>
                </div>        
                <div class="si-box">
                    <MemberSelector 
                        placeHolder="サブアカウント"
                        v-model="subParams.linked"
                        :options="linkables"
                        rules=""
                        name="workgroup_users"
                        :closeOnSelect="false"
                    />

                </div>
                
                
            
                <div class="l-button cursor-pointer" style="margin-top:30px" @click="send" :disabled="processing">
                    <span v-if="!processing">保存する</span>
                    <div v-if="processing" id="loaderMini">
                        <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                    </div>
                </div>    
            </div> 
        </div>
    </div>
    
           
    </template>
    <script setup>
        import { computed, inject, onMounted, reactive, ref, markRaw } from 'vue';
        import ShortInput from '../Form/ShortInput.vue';
        import MemberSelector from '../Form/MemberSelector.vue'
        const emit = defineEmits(['postFinish'])
        const props = defineProps(['positions', 'offices', 'editUserData', 'workGroups', 'linkables'])
        const processing = ref(false)
        const showPassword = ref(false)
        const { notify, info } = inject('dialog')
        const passwordReset = ref(false)
        const Deselect = markRaw({
            template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
        }) 
        
        const nameRef = ref(null)
        const nameKanaRef = ref(null)
        const loginRef = ref(null)
        const emailRef = ref(null)
        const passwordRef = ref(null)
        const userCodeRef = ref(null)
        const workTimeRef = ref(null)

        const userParams = reactive({
            name: props.editUserData ? props.editUserData.name : '',
            name_kana: props.editUserData ? props.editUserData.name_kana :  '',
            login: props.editUserData ? props.editUserData.login :  '',
            email: props.editUserData ? props.editUserData.email :  '',
            password: props.editUserData ? props.editUserData.password :  '',
            phone_number: props.editUserData ? props.editUserData.phone_number :  '',
            retire: props.editUserData ? props.editUserData.retire :  0,
            work_type: props.editUserData ? props.editUserData.work_type :  0,
            work_time_day: props.editUserData ? props.editUserData.work_time_day :  '',
            position_id: props.editUserData ? props.editUserData.position_id :  '',
            office_id: props.editUserData ? props.editUserData.office_id :  '',
            hide_flag: props.editUserData ? props.editUserData.hide_flag :  0,
            user_code:props.editUserData ? props.editUserData.user_code :  '',
        })

        const subParams = reactive({
            workGroup: props.editUserData && props.editUserData.work_groups ? props.editUserData.work_groups :  [],
            linked: props.editUserData && props.editUserData.linked ? props.editUserData.linked :  [],
        })

        onMounted(() => {
            console.log(props.linkables)
        })
        const closeModal = (flag) => {
            processing.value = false
            emit('postFinish',flag);     
        }
        const isPartner = computed(() => {
            return userParams.position_id == 14
        })
        const send = async() => {
            const targets = [
                nameRef.value,
                nameKanaRef.value,
                loginRef.value,
                emailRef.value,
                passwordRef.value,
                userCodeRef.value,
                workTimeRef.value
            ]
            const validateTargets = targets.filter( target => target !== null)
            let result = true
            for(const target of validateTargets){                
                const val = await target?.validate() || false
                result = result * val.valid
            }
            if (!result) return
           


            const params = {
                id: props.editUserData ? props.editUserData.id : null,
                user_params: userParams,
                linked: subParams.linked.map(ob => ob.id),
                work_groups: subParams.workGroup.map(ob => ob.id),
                password_reset: props.editUserData && passwordReset.value
            }
            try {
                await axios.post('/user_add', params)
                info('保存しました')
                emit('postFinish', true)
            } catch (e) {
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            }
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
            border: 1px solid var(--primary-color);
            padding: 20px 10px 10px 15px;
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
            border: 1px solid var(--primary-color);
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
        
        
        
        
        