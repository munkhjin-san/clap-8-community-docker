<template>
    <div :title="user && user.name ? user.name : ''">
        <img draggable="false" :class="[imgClass, themeIcon]" v-lazy="{src: userIcon}" :style="imgStyle" />
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
                if(this.user && this.user.a_path){
                    const devicePixelRatio = window.devicePixelRatio || 1;
                    const imageFileName = devicePixelRatio > 1 ? `${this.user.id}_${this.user.a_path}_200.jpg` : `${this.user.id}_${this.user.a_path}_${this.size ? this.size : '30'}.jpg`
                    return `${window.location.origin}/content/profile_icon/${imageFileName}`;
                    
                }else{
                    return this.$store.state.baseLocation + '/content/profile_icon/noimage.jpg'
                }
            },
        }
    }
</script>
<style lang="scss" scoped>
    .darkIcon{
        filter: invert(0.8);
    }
</style>