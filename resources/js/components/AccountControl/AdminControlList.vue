<template>
    <div class="adminList-wrapper">        

        <div class="admin-header">            
            <Hamburger v-if="responsive.mobile"/>
            <div v-if="[608, 610].includes(auth.activeUser.id) || auth.activeUser.position_id <= 6" class="admin-tab-container">
                <div class="pc" style="font-size: 16px;margin: 20px 0px 0px 15px;padding-bottom: 10px;"></div>
                <div v-if="[608, 610].includes(auth.activeUser.id)">
                    <div class="admin-tab-item" @click="router.push({name: 'account'})" :class="{'selected-tab' : route.name == 'account' }">アカウント</div>
                    <div class="admin-tab-item" @click="router.push({name: 'attendance'})" :class="{'selected-tab' : route.path.includes('workcontrol')}">タイムシート</div>
                    <div class="admin-tab-item" @click="router.push({name: 'clapcount'})" :class="{'selected-tab' : route.name == 'clapcount'}">クラップ数集計</div>
                    <div class="admin-tab-item" @click="router.push({name: 'learningcontrol'})" :class="{'selected-tab' : route.path.includes('learningcontrol')}">研修</div>
                    <div class="admin-tab-item" @click="router.push({name: 'projectlist'})" :class="{'selected-tab' : route.path.includes('projectcontrol')}">プロジェクト</div>
                    <div class="admin-tab-item" @click="router.push({name: 'glowdnine'})" :class="{'selected-tab' : route.name == 'glowdnine'}">グラウドナイン</div>
                    <div class="admin-tab-item" @click="router.push({name: 'custom-form-control'})" :class="{'selected-tab' : route.path.includes('custom-form-control')}">フォーム</div>
                </div>
                <div v-else class="admin-tab-item" @click="router.push({name: 'custom-form-control'})" :class="{'selected-tab' : route.path.includes('custom-form-control')}">フォーム</div>
            </div>
        </div>
        <div style="width: 100%;flex:1;overflow: hidden;background: var(--background-color);" v-if="[608, 610].includes(auth.activeUser.id) || (auth.activeUser.position_id <= 6 && route.path.includes('custom-form-control'))">
            
            <router-view
            ></router-view>
        </div>
        <div v-else style="height: 100%;width: 100%;text-align: center;justify-content: center;display: flex;align-items: center;flex-direction: column;">
            <p>アクセス権限ありません。</p>
            <router-link class="l-button" style="margin: 30px 0 70px 0;" to="/board">ボードへ戻る</router-link>
        </div>
        
    </div>
    
</template>
<script setup>
import { useRoute, useRouter } from 'vue-router';
import Hamburger from '../Global/HamBurger.vue'
import { useResponsive } from '@/store/responsive';
import { useAuthUserStore } from '@/store/auth'
    const router = useRouter()
    const route = useRoute()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
       




</script>
<style>
.admin-sub-c-bar{
    display: flex;
    margin: 20px;
    flex-wrap: wrap;
    place-content: space-between;
    gap: 15px;
}
.admin-window{
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: var(--bg3);
}
.admin-command-bar{
    width: fit-content;
    min-width: 300px;      
}
.control-loader{
    position: absolute;
    background: var(--bg3);
    z-index: 5;
    width:100%;
    height:100%;
    left:0;
    top:0;
    display: flex;
    align-items: center;
    justify-content: center;
}


.admin-tab-item{
    border: solid thin transparent;
    padding: 15px;
    font-size: 14px;
    height: fit-content;
    box-sizing: border-box;
    cursor: pointer;
    transition: all 0.2s ease;
    color: gray;
    word-break: keep-all;
}
.admin-tab-container{
    display: flex;
    background: var(--background-color);
    width: auto;
    height: 100%;
    flex-direction: column;
    margin: 0 15px

}
.selected-tab{
    background: var(--bg3);
    border: solid thin gray;
    color: var(--primary-color);
}
.absolute-searchBar{
    position: absolute;
    right: 5px;
}
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
    display: flex;
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
    height: 100%;
    width: auto;
    align-items: center;
    background: var(--background-color);
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
    .admin-window{
        background: var(--background-color);
        padding: 0;
        width: 100%;
        height: 100%;
    }
    .admin-header{
        width: 100%;
        height: 60px;
        background: var(--bg3);
    }
    .admin-tab-item{
        font-size: 12px;
        padding: 10px
    }
    .admin-tab-container{
        margin: 0;
        flex-direction: row;
        align-items: end;
        background: var(--bg3);
        
    }
    .searchBar-wrapper{
        width: calc(100% - 40px);
        margin: 0 20px;
    }
    .admin-button-wrapper{
        margin-bottom: 10px;
        margin-left: 15px;
        margin-right: 15px;
    }
    .admin-command-bar{
        min-width: calc(100% - 30px);
    }
    .adminList-wrapper{
        flex-direction: column;
    }
    .selected-tab{
        border: solid thin var(--formBorder);
        border-bottom: none;
        background: var(--background-color);
    }
}
</style>