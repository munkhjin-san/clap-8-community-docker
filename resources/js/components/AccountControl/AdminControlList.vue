<template>
    <div class="adminList-wrapper">
        <div class="pc admin-column-left">
            <div class="admin-menu">
                <ul>
                    <li @click="accountsSelect" :class="{'is-active' : mainMenu == 0 }">アカウント管理</li>

                </ul> 
            </div>
        </div>
        <AdminAccountComponent v-if="mainMenu == 0"/>

    </div>
</template>
<script>
    import AdminAccountComponent from './AdminAccountComponent.vue'

    export default{
        data() {
            return {
                mainMenu: 0
            }
        },
        methods: {
            accountsSelect(){
                this.mainMenu = 0
            },
        },

        components:{
            AdminAccountComponent,

        }
    }
</script>
<style>
.adminList-wrapper{
    display: flex;
    color:var(--primary-color);
    fill:var(--primary-color);
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
.admin-menu li.is-active{
    background: var(--background-color);
}
.admin-menu > ul > li{
    display: block;
    width: 90%;
    height: 84px;
    cursor: pointer;
    padding: 0px 5% 0px 5%;
    margin-bottom: 20px;
  
    text-align: left;
    line-height: 84px;
  
    background: #efefef;
  }
</style>