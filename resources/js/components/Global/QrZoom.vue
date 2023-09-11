<template>
    <div @mousedown="closeModal" class="overlay" style="z-index: 27;">       
        <div id="editModal01" class="chatCreate" ref="editModal01" @mousedown.stop>
            <img style="width:inherit;max-height:100%;margin: auto;" v-if="this.content && this.content.user" :src="`${this.$store.state.baseLocation}/qr/${this.content.user.q_token}_${this.content.user.id}.png`"/>
            <div @click="$emit('closeModal')" class="commentEditButton" style="position: absolute;bottom: 30px;left:0;right:0;margin: 0 auto;padding:10px;">{{ $t('back') }}</div>
        </div>        
    </div>
</template>
<script>    
    export default {
        props: ['content'],
        emits: ['closeModal'],
        data(){
            return{
                active: false
            }
        }, 
        mounted(){
            console.log(this.content)
        },
        methods:{
            closeModal(){
                this.$emit('closeModal')
            }
        }
    }
</script>
<style lang="scss">
    
</style>
