<template>
    <div class="adminList-wrapper">
        

        <div class="admin-header">
            
            <Hamburger v-if="$store.state.mobile"/>
            
            <div class="admin-button-wrapper">
                <div class="admin-button" @click="$router.push({name: 'account'})" :class="{'is-active' : $route.name == 'account' }">アカウント管理</div>
                <div class="admin-button" @click="$router.push({name: 'workgroup'})" :class="{'is-active' : $route.name == 'workgroup'}">ワークグループ管理</div>
                <!-- <div class="admin-button" @click="$router.push({name: 'account'})2" :class="{'is-active' : $route.name == 2}">役職管理</div>
                <div class="admin-button" @click="$router.push({name: 'account'})3" :class="{'is-active' : $route.name == 3}">事務所管理</div> -->
                <div class="admin-button" @click="$router.push({name: 'workcontrol'})" :class="{'is-active' : $route.name == 'workcontrol'}">ワーク管理</div>
                <div class="admin-button" @click="$router.push({name: 'plannedpaid'})" :class="{'is-active' : $route.name == 'plannedpaid'}">計画有給管理</div>
                <div class="admin-button" @click="$router.push({name: 'clapcount'})" :class="{'is-active' : $route.name == 'clapcount'}">クラップ数集計</div>
                <div class="admin-button" @click="$router.push({name: 'learningcontrol'})" :class="{'is-active' : $route.name == 'learningcontrol'}">研修</div>
            </div> 
        </div>
        <div v-if="$route.name == 'workcontrol' || $route.name == 'account'" class="searchBar-wrapper">
            <Transition name="searchHide">
                <UserSearchBar @setKeyord="setKeyord"/>
            </Transition>
        </div>
        <router-view
            :searchUser="searchUser"
            @getUsers="getUsers"
            :positionLabel="positionLabel"
            :officeLabel="officeLabel"
            :workGroup="workGroup"
            :mainMenu="mainMenu" 
            :userList="userList" 
            :workgroupusers="workGroupUsers"
            :positionList="positionList"
            :officeList="officeList"
        ></router-view>
    </div>
</template>
<script setup>
import Hamburger from '../Global/HamBurger.vue'
import UserSearchBar from './UserSearchBar.vue'
import { computed, onMounted, ref } from 'vue'

    const mainMenu = ref(0)
    const keywords = ref(null)
    const userList = ref([])
    const positionList = ([])
    const positionLabel = ref([])
    const officeList = ref([])
    const officeLabel = ref([])
    const workGroup = ref([])
    const workGroupUsers = ref([])

    onMounted(() => {
        getUsers()
    })
       
    const searchUser = computed(() => {
        if(keywords.value){
            let lowSearch = keywords.value.toLowerCase()
            return userList.value.filter(user => 
                Object.values(user).some(val => 
                    String(val).toLowerCase().includes(lowSearch)
                )
            )
        }else{         
            return userList.value
        }
    })
       

    const getUsers = () => {
        axios.get('/get_user_list').then(response => {  
            userList.value = response.data.user_list
            positionList.value = response.data.position_list    
            positionLabel.value = response.data.position_list_label
            officeList.value = response.data.office_list
            officeLabel.value = response.data.office_list_label
            workGroup.value = response.data.work_group  
            workGroupUsers.value = response.data.work_group_users
        }).catch(function (error) {
            if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。 ' + error.message)     
        })
    }

    const errorToast = (message) => {
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: message,
            closeButton: false, 
            autoClose: false,
            answers: ['OK']
        })                
    }

    const setKeyord = (val) => {
        keywords.value = val
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
    overflow: hidden;
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
        width: calc(100% - 40px);
        margin: 0 20px;
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