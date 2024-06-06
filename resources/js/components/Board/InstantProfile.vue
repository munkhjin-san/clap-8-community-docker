<template>            
    <div class="instant-profile" ref="instantProfileWindow" id="instantProfileWindow" :style="{top: top, left: left, right: right, opacity: opacity}">   
        <div style="padding:15px;position:relative">   
            <div v-if="inviteLock" style="position: absolute;width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;left:0;top:0;">
                <div style="border:4px #fff solid; border-top: solid 4px transparent;" class="spinner-micro"></div>
            </div> 
            <div v-if="!skLoader && found && user" style="display:flex;align-items:center">
                <div>
                    <UserIcon size="80" :user="user" :disableInstant="true" imgClass="userLargeIcon"/> 
                </div>
                <div style="display:flex;flex-direction:column;overflow: hidden;font-size:14px;overflow: hidden;font-size: 14px;margin-left: 13px;min-height: 72px;place-content: center;">   
                    <div style="font-weight:600;margin-bottom:10px;display: flex;">
                        <router-link class="user-link" :to="'/user/' + user.id">{{user.name}}</router-link>
                        <img v-if="user.weathers" style="margin-left:10px" :src="'/images/icon_' + user.weathers.value_int + '.svg'" alt="Weather Icon" width="16" height="16">
                    </div>
                    <div v-if="user.work_email" style="margin-bottom:10px;height:14px"><a class="prvt" :href="'mailto:' + user.work_email">{{user.work_email}}</a></div>
                    <div v-if="user.phone_number" style="margin-bottom:10px;height:14px"><a class="prvt" :href="'tel:' + user.phone_number">{{user.phone_number}}</a></div>   
                    <div v-if="!auth.isPartner && auth.id !== user.id" style="margin-bottom:10px;height:14px;cursor:pointer"><a :href="`/start_private_board?with=${user.id}`" class="prvt">個別ボード</a></div>   

                </div>
                
            </div>
            <div v-if="!skLoader && !found && !user" style="display:flex;align-items:center">
                <div class="mini-sk userLargeIcon"></div>
                <div style="display:flex;flex-direction:column;overflow: hidden;font-size:14px;overflow: hidden;font-size: 14px;width: calc(100% - 80px);margin-left: 13px;">   
                    
                    <div style="height:14px">ユーザーが見つかりませんでした</div>
                </div>                    
            </div>
            <div v-if="skLoader"  style="display:flex;align-items:center">
                <div class="mini-sk userLargeIcon"></div>
                <div style="display:flex;flex-direction:column;overflow: hidden;font-size:14px;overflow: hidden;font-size: 14px;width: calc(100% - 80px);margin-left: 13px;">   
                    <div class="mini-sk" style="margin-bottom:10px;height:14px;width: 50%;"></div>
                    <div class="mini-sk" style="margin-bottom:10px;height:14px;width: 85%;"></div>
                    <div class="mini-sk" style="margin-bottom:10px;height:14px;width: 65%;"></div>     
                    <div v-if="!auth.isPartner" class="mini-sk" style="margin-bottom:10px;height:14px;width: 45%;"></div>                
                </div>
            </div>
        </div>        
    </div>                                       
</template>

<script setup>
import { computed, inject, onMounted, onUnmounted, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import UserIcon from './Mixed/UserIcon.vue';
    const props = defineProps(['data'])
    const emit = defineEmits(['resetInstantUser'])
    const auth = useAuthUserStore()
    const info = ref(null)
    const skLoader = ref(true)
    const inviteLock = ref(false)
    const { notify } = inject('dialog')
    const instantProfileWindow = ref(null)
    const top = ref('auto')
    const left = ref('auto')
    const right = ref('auto')
    const opacity = ref(0)
    onMounted(async() => {
        document.addEventListener('mouseup', clickHandle)
        document.addEventListener('touchend', clickHandle)        
        const el = instantProfileWindow.value
        await getInstantUser(el)
    })
    onUnmounted(() => {
        document.removeEventListener('mouseup', clickHandle)
        document.removeEventListener('touchend', clickHandle)
    })
    const user = computed(() => {
        return info.value ? info.value.user : null
    })
    const found = computed(() => {
        return info.value ? info.value.found : false
    })
    const clickHandle = (event) => {
        const cont1 = instantProfileWindow.value;    
        if(cont1 && !cont1.contains(event.target)){
            emit('resetInstantUser')
        } 
    }
    const getInstantUser = async(el) => {

        try{
            info.value = await axios.post('/get_instant_user', {id: props.data.id}).then(response => response.data)
            setTimeout(() => {
                skLoader.value = false
            },100)
            const windowWidth = window.innerWidth;
            const cX = props.data.cX
            const cY = props.data.cY
            let menuRect = el.getBoundingClientRect()                
            top.value = '-3000px';
            left.value = '-3000px';                
            top.value = cY - menuRect.height - 15 + 'px'
            left.value = cX - (menuRect.width / 2) + 'px'
            if(cX + ((menuRect.width / 2) + 10) > windowWidth){
                right.value = '10px';
                left.value = 'auto'
            }
            else if(cX - (menuRect.width / 2) < 0 ){
                right.value = 'auto';
                left.value = '10px'
            }            
            if(cY - menuRect.height - 10 < 0){                    
                top.value = cY + 10 + 'px'                    
            }
            opacity.value = 1            

        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
        
        
    }     

</script>
<style lang="scss">
.instant-profile{
    background: #565656;
    color: #fff;
    border-radius: 5px;
    position: fixed;
    left:50%;
    top:50%;
    z-index: 45;   
    min-height: 90px;
    max-height: 130px;
    min-width: 300px;
    max-width: calc(100vw - 20px);
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
    opacity: 0;
    transition: opacity 0.3s;
}
.prvt{
    color: #64a5fb !important;
    word-break: keep-all;
    white-space: nowrap;
}
.prvt:hover{
    color: #2e7ce4 !important;
}
.iuButton{
    padding: 5px 10px;
    background: #fff;
    color: #000;
    border-radius: 50px;
    margin-right: 10px;
    cursor: pointer;
}
.mini-sk{
    background:#a7a7a747;
    animation: pulse-bg-bk 1s infinite;
}
@keyframes pulse-bg-bk {
    0% {
        background-color: #ffffff47;
    }
    50% {
        background-color: #a7a7a747;
    }
    100% {
        background-color: #ffffff47;
    }
}

</style>
