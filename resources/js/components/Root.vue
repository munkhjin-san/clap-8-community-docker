<template>
    <div style="width: 100%;height: 100%;display: flex;">
        <Transition name="modalFade">
            <div @click="$store.commit('setSideMenuView', false)" v-if="$store.state.sideMenuView" class="overlay mobile" style="z-index: 16;"></div>
        </Transition>

        <SideMenu :auth_user="auth_user" :session="session" :remember="remember"/>

        <!-- <router-view :key="$route.name" /> -->
        <router-view :key="keyGen"/>
    </div>

</template>
<script>
import SideMenu from './Global/SideMenu.vue';
export default{
    props: ['session', 'auth_user', 'remember'], 
    components: {
        SideMenu
    },
    mounted(){
        
    },
    computed:{
        keyGen(){
            const parts = this.$route.fullPath.split('/');
            if (parts.length > 1) {
                return parts[1];
            } else {
                return this.$route.fullPath
            }
        }
    }
}
</script>

