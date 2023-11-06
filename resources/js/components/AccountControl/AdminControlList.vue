<template>
    <div class="adminList-wrapper">
        

        <div class="admin-header">
            
            <Hamburger v-if="$store.state.mobile"/>
            <div class="searchBar-wrapper">
                <Transition name="searchHide">
                    <UserSearchBar @setKeyord="setKeyord"/>
                </Transition>
            </div>
            <div class="admin-button-wrapper">
                <div class="admin-button" @click="mainMenu = 0" :class="{'is-active' : mainMenu == 0 }">アカウント管理</div>
                <div class="admin-button" @click="mainMenu = 4" :class="{'is-active' : mainMenu == 4}">ワークグループ管理</div>
                <!-- <div class="admin-button" @click="mainMenu = 2" :class="{'is-active' : mainMenu == 2}">役職管理</div>
                <div class="admin-button" @click="mainMenu = 3" :class="{'is-active' : mainMenu == 3}">事務所管理</div> -->
                <div class="admin-button" @click="mainMenu = 5" :class="{'is-active' : mainMenu == 5}">ワーク管理</div>
                <div class="admin-button" @click="mainMenu = 6" :class="{'is-active' : mainMenu == 6}">クラップ数集計</div>
            </div> 
        </div>
        <AdminAccount 
            :searchUser="searchUser"
            @getUsers="getUsers"
            :positionLabel="positionLabel"
            :officeLabel="officeLabel"
            :workGroup="workGroup"
            v-if="mainMenu == 0"
        />
        <AdminWork
            :searchUser="searchUser"
            :mainMenu="mainMenu" 
            v-if="mainMenu == 5"
        />
        <AdminWorkGroup
            :userList="userList" 
            :workgroupusers="workGroupUsers"
            v-if="mainMenu == 4"
            :searchUser="searchUser"
            @getUsers="getUsers"
        />
        <AdminPosition 
            v-if="mainMenu == 2"
            :positionList="positionList"
        />
        <AdminOffice 
            v-if="mainMenu == 3"
            :officeList="officeList"
            @getUsers="getUsers"
        />
        <AdminClapCount 
            v-if="mainMenu == 6"
            :searchUser="searchUser"
        />
    </div>
</template>
<script>
    import AdminAccount from './AdminAccount.vue'
    import Hamburger from '../Global/HamBurger.vue'
    import UserSearchBar from './UserSearchBar.vue'
    import AdminWork from './AdminWork.vue'
    import AdminWorkGroup from './AdminWorkGroup.vue'
    import AdminPosition from './AdminPosition.vue'
    import AdminOffice from './AdminOffice.vue'
    import AdminClapCount from './AdminClapCount.vue'
    export default{
        data() {
            return {
                mainMenu: 0,
                keywords: null,
                userList: [],
                positionList: [],
                positionLabel: [],
                officeList: [],
                officeLabel: [],
                workGroup: [],
                workGroupUsers: [],
            }
        },
        mounted(){
            this.getUsers()
        },
        computed: {
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
        methods: {
            getUsers(){
                axios.get('/get_user_list').then(response => {  
                    this.userList = response.data.user_list
                    this.positionList = response.data.position_list    
                    this.positionLabel = response.data.position_list_label
                    this.officeList = response.data.office_list
                    this.officeLabel = response.data.office_list_label
                    this.workGroup = response.data.work_group  
                    this.workGroupUsers = response.data.work_group_users
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
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
            setKeyord(val){
                this.keywords = val
            },
        },

        components:{
            AdminAccount,
            Hamburger,
            UserSearchBar,
            AdminWork,
            AdminWorkGroup,
            AdminPosition,
            AdminOffice,
            AdminClapCount
        }
    }
</script>
<style>
.searchBar-wrapper{
    width: 30%;
}
.admin-button-wrapper{
    display: flex;
    gap: 10px;
    margin-left: 15px;
    flex-wrap: wrap;
}
.is-active{
    background-color: #000 !important;
    border: solid thin gray;
}
.adminList-wrapper{
    color:var(--primary-color);
    fill:var(--primary-color);
    width: 100%;
    height: 100%;
}
.admin-column-left{
    float: left;
    display: block;
    width: 20%;
  }
.admin-menu{
    width: 100%;
    margin-top: 60px;
}
.admin-header{
    display: flex;
    height: 60px;
    width: 100%;
    align-items: center;
    flex-wrap: wrap;
}
.admin-menu li.is-active{
    background: var(--background-color);
}

  input:autofill {
    -webkit-text-fill-color: var(--primary-color);
}
.admin-button{
    background: #4b4b4b;
    color: #fff;
    font-size: 12px;
    white-space: nowrap;
    width: -moz-fit-content;
    width: fit-content;
    margin: auto;
    position: relative;
    min-width: auto;
    min-height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0 15px;
    flex: 1 0 auto;
}

@media screen and (max-width: 959px) {
    .searchBar-wrapper{
        width: calc(100% - 65px);
    }
    .admin-header{
        min-height: 140px;
    }
    .admin-button-wrapper{
        margin-bottom: 10px;
        margin-left: 15px;
        margin-right: 15px;
    }
}
</style>