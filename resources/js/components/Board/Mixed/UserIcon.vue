<template>
    <div :title="user && user.name ? user.name : ''"  @click.stop="pushInstantUser($event, user.id)">
        <img v-if="userIcon" draggable="false" :class="[imgClass, themeIcon]" v-lazy="{src: userIcon}" :style="imgStyle" />
        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" :class="[imgClass]">
            <circle cx="15" cy="15" r="15" fill="#ddd"/>
        </svg>
    </div>
</template>
<script>
    export default {
        props: ['user', 'imgClass', 'imgStyle', 'size'],        
        computed:{
            themeIcon(){
                if(this.user && this.user.a_version == 0){
                    return this.$store.state.dark ? 'darkIcon' : 'lightIcon'
                }
                return ''
                
            },
            userIcon(){
                if(this.user && this.user.icon_id){
                    const devicePixelRatio = window.devicePixelRatio || 1;
                    const imageFileName = devicePixelRatio > 1 ? `${this.user.icon_id}_${this.user.id}_200.jpg` : `${this.user.icon_id}_${this.user.id}_${this.size ? this.size : '30'}.jpg`
                    return `${window.location.origin}/content/profile_icon/${imageFileName}`;
                    
                }else{
                    return null
                }
            },
        },
        methods:{
            pushInstantUser(event, id){
                if(this.$store.state.user && id == this.$store.state.user.id) return
                const cX = event.clientX;
                const cY = event.clientY;  
                const data = {
                    id: id,
                    cX: cX,
                    cY: cY
                }
                this.$store.commit('setInstantUser', data)   
                this.$store.commit('setMenu', {name: 'instantProfileWindow', id: 5000})                 
            },
        }
    }
</script>
<style lang="scss" scoped>
    .darkIcon{
        filter: invert(0.8);
    }
</style>