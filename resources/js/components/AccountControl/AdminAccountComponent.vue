<template>
    <div class="admin-account-outwrapper">
        <div class="admin-account-center">
            <div style="margin-bottom:20px;display:flex;transition: transform 0.3s;">
                <!-- <div class="filterButton-wrapper">
                    <button class="btn btn-primary filter-btn" :class="{'is-active' : retireFlag === false }" @click="activeUser">社員</button>
                    <button class="btn btn-primary filter-btn" :class="{'is-active' : retireFlag === true }" @click="retiredUser">退職者</button>
                </div> -->
                <Transition name="searchHide">
                    <UserSearchBar @setKeyord="setKeyord"/>
                </Transition>
            </div>
            <div class="user-record-parent" :style="{ height: scrollHeight}">
                <div class="admin-account-center-inner" v-bind:key="item.id" v-for="item in searchUser">
                    <div class="account-wrapper">
                        <div style="display:flex; align-items:center;margin-bottom:15px;">
                            <UserIconPreLoad size="45" :title="item.name" :user="item" imgClass="boardNormalIcon"/>
                            <div style="display:flex; flex-direction:column">
                                <span style="margin-left:10px;">{{item.name}}</span>
                                <!-- <span style="margin-left:10px; margin-top:10px;">{{ item.name_kana }}</span> -->
                            </div>
                        </div>
                        <!-- <p>{{ item.phone }}</p> -->
                        <p class="account-content">{{ item.email }}</p>
                    </div>
                    <div class="detail-wrapper">
                        <div style="display:flex;overflow:hidden;margin-right:20px;padding-bottom:10px;" >
                            <p style="font-weight:600;white-space:nowrap">Company:</p>
                            <p style="margin-left:10px;white-space:break-spaces;">{{ item.user_detail ? item.user_detail.company : ' ' }}</p>
                        </div>
                        <div style="display:flex;overflow:hidden;margin-right:20px;padding-bottom:10px;" >
                            <p style="font-weight:600;white-space:nowrap">Intro:</p>
                            <p style="margin-left:10px;white-space:break-spaces;">{{ item.user_detail ? item.user_detail.intro : ' ' }}</p>
                        </div>
                        <div style="display:flex;overflow:hidden;margin-right:20px;padding-bottom:10px;" >
                            <p style="font-weight:600;white-space:nowrap">Occupation:</p>
                            <p style="margin-left:10px;white-space:break-spaces;">{{ item.user_detail ? item.user_detail.occupation : ' ' }}</p>
                        </div>
                        <div style="display:flex;overflow:hidden;margin-right:20px;padding-bottom:10px;" >
                            <p style="font-weight:600;white-space:nowrap">Profession:</p>
                            <p style="margin-left:10px;white-space:break-spaces;">{{ item.user_detail ? item.user_detail.profession : ' ' }}</p>
                        </div>
                    </div>
                    <div class="button-wrapper">
                        <button type="submit" @click="userEdit(item)" class="btn btn-primary account-btn" style="margin-bottom:20px;">
                            編集
                        </button>
                        <button type="submit" @click="deleteUser(item)" class="btn btn-primary account-btn" style="margin-bottom:20px;color:tomato">
                            削除
                        </button>
                    </div>        
                </div>
            </div>
        </div>
            <div style="bottom: 30px" class="createBoardButton fileNewButton" @click="newRecord()">
                <!-- :style="{bottom : $store.state.mobile ? '70px' : '30px'}" -->
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div>           
       
        <Transition name="modalFade">
            <div class="overlay" v-if="showModalContent">                      
                <UserCreate 
                    :editUserData="editUserData"
                    :editFlag="editFlag"
                    @postFinish="postFinish"
                />
            </div>   
        </Transition>
    </div>
   
</template>
<script>
import UserSearchBar from './UserSearchBar.vue'
import UserCreate from './UserCreate.vue'
import UserIconPreLoad from '../Board/Mixed/UserIcon.vue'
    export default{
        data(){
            return{
                userList: [],
                showModalContent: false,
                editUserData: null,
                editFlag: false,
                createHidden: false,
                scrollPosition: 0,
                keywords: null,
            }
        },
        computed: {
            scrollHeight() {
                return 'calc(100% - 80px)'
                // const mobile = this.$store.state.mobile
                
                // if(mobile){
                //     return 'calc(100% - 105px)'
                // }else{
                    
                // }
            },
            searchUser(){
                if(this.keywords){
                    let lowSearch = this.keywords.toLowerCase()
                    return this.userList.filter(user => 
                        Object.values(user).some(val => 
                            String(val).toLowerCase().includes(lowSearch)
                        )
                    )
                }else{         
                    return this.userList
                }
            }, 
        },
        mounted(){
            this.getUsers()
        },
        methods: {      
            postFinish() {
                this.showModalContent = false;
                this.editUserData = null;
                this.getUsers()
            },
            getUsers(){
                axios.get('admin_account_control/get_user_list').then(response => {  
                    this.userList = response.data.user_list
                })
            },
            userMemo(user_detail){
                return user_detail?user_detail.memo:' '
            },
            newRecord() {
                this.openModal();
                this.editFlag = false;
            },
            userEdit(item) {
                this.editUserData = item;
                this.openModal();
                this.editFlag = true;
            },
            openModal: function () {
                this.showModalContent = true;
            },
            
            setKeyord(val){
                this.keywords = val
            },
            deleteUser(user){

                var uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: 'このユーザーを削除してもよろしいですか?',
                    closeButton: true, 
                    autoClose: false,
                    answers: ['はい', 'いいえ'],
                    channel: uniqueChannell
                })                      
                emitter.on(uniqueChannell, (data) => { data.answer == 'はい' ? 

                    axios.post('admin_account_control/user_delete', user).then(response => {  
                        emitter.emit('setToast', {
                            active: true,  
                            type: 'info', 
                            content: this.$t('success') ,
                            closeButton: false, 
                            autoClose: true,
                            answers: ['OK'],
                        }) 
                        this.getUsers()
                    })
                : false });

                
            }

        },
        watch: {
                
            },
        components:{
            UserIconPreLoad,
            UserSearchBar,
            UserCreate
        }
    }
</script>
<style scoped lang="scss">
    .fileNewButton{
        transition: all 0.2s;
        width: 35px; 
        height: 35px;
        background: #efefef;
    }
    .createBoardButton{
        z-index: 12;
        border-radius: 50%;
        background: #e2e2e2;
        width: 35px;
        height: 35px;
        text-align: center;
        display: flex;
        position: absolute;
        bottom:20px;
        right: 20px;
        cursor: pointer;
        box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;
        transition: transform .2s;
    }
    .admin-account-outwrapper{
        padding: 0 20px;
        margin: 40px auto auto auto;
        width: 80%;
        height: 100%;
        display: block;
        float: right;
    }
    .account-wrapper{
        width: 25%;
        margin-right:30px;
    }
    .detail-wrapper{
        width: 60%;
    }
   
    .admin-account-center-inner{
        padding: 30px;
        background: var(--background-color);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
    }
    .admin-account-title{
        margin-top: 30px;
        font-size: 20px;
    }
    
      .account-content{
        margin-bottom: 15px;
      }
      .account-btn{
        display: block;
        width: 120px;
        height: 40px;
        text-align: center;
        color: #fff;
        background-color: var(--primary-button);
      }
      .filter-btn{
        display: block;
        width: 120px;
        height: 40px;
        text-align: center;
        color: #000;
        background-color: #efefef;
        margin-right:20px;
      }
      .filter-btn.is-active{
        background-color: #000;
        color: #fff;
      }
      .filterButton-wrapper{
        display: flex;
        z-index: 10;
      }
      .user-record-parent {
        position: fixed;
        width: 78.5%;
        overflow: hidden scroll;
        z-index: 12;
        transition: transform 0.3s;
      }
    
</style>