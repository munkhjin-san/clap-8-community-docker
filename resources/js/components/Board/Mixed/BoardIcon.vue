<template>
    <div >
        <img v-if="item.private_flag == 0 && boardIcon" draggable="false" loading="lazy" :class="[imgClass, themeIcon]" :src="boardIcon" :style="imgStyle">
        <UserIcon v-if="item.private_flag > 0 && correspondUser" :user="correspondUser" :imgClass="imgClass" size="45"/>
        <svg v-if="!boardIcon && !correspondUser" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" :class="[imgClass]">
            <circle cx="15" cy="15" r="15" fill="#ddd"/>
        </svg>
    </div>
</template>

<script>
import UserComponent from '../../Profile/UserComponent.vue';
import UserIcon from './UserIcon.vue';
    export default {
        props: ['item', 'imgClass', 'imgStyle'],
        components: {
            UserIcon,
        },
        computed:{
            themeIcon(){
                 
                if(this.item.a_version == 0){
                    return this.$store.state.dark ? 'darkIcon' : 'lightIcon'
                }
              
                
            },
            correspondUser(){
                if(this.item.private_flag == 1){
                    var user = this.item.board_to_users.filter(obj => obj.user_id !== this.$store.state.user.id);
                    return user && user.length && user[0].user? user[0].user : null
                    
                }else if(this.item.private_flag == 3){
                    var me = this.item.board_to_users.filter(obj => obj.user_id == this.$store.state.user.id);
                    return me && me.length && me[0].user ? me[0].user : null
                }
                return null
            },
            boardIcon(){
                var path = null
                if(this.item.icons){
                    var path = `${this.$store.state.baseLocation}/content/board_icon/board_${this.item.icon_id}.${this.item.icons.extension}`;
                }
                return path;
            },
            boardIconSrc(){
                var path = this.$store.state.baseLocation + '/profile_icon/noimage.jpg';
                if(this.item.icons){
                    var path = `${this.$store.state.baseLocation}/content/board_icon/board_${this.item.icon_id}.${this.item.icons.extension}`;
                }
                return path;
            },
        }
    }
</script>
<style lang="scss" scoped>
    .darkIcon{
        filter: invert(0.8);
    }
</style>