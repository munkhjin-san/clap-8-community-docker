<template>
    <div id="override">
            
        <IncompleteWindow ref="incompleteRef" v-if="auth.user && viewIncompleteWindow" 
        @closePopup="closePopup"/> 
        <Transition name="modalFade">
            <IncompleteFeedBack v-if="taskFeedBack.active"/>
        </Transition>
        <Transition name="modalFade">
            <MessageUsers v-if="messageUsers.active"/>
        </Transition>
        <Transition name="modalFade">
            <FilePreview v-if="filePreview.active"/>
        </Transition> 
        <Transition name="modalFade">
            <WeatherComponent v-if="auth.user"/>
        </Transition> 
        <SharingData v-if="sharingData.active && (route.name == 'board' || route.name == 'room')"/>
        <Transition name="modalFade">
            <CheckWork v-if="auth.user" />
        </Transition>
    </div>
</template>

<script setup>
    import IncompleteWindow from '../Board/IncompleteWindow.vue'
    import IncompleteFeedBack from '../Board/IncompleteFeedBack.vue'
    import theme from '../../../assets/theme.json'
    import MessageUsers from '../Board/Message/MessageUsers.vue'
    import WeatherComponent from '../Global/WeatherComponent.vue'
    import SharingData from '../Global/SharingData.vue'
    import FilePreview from '../Board/Tray/File/FilePreview.vue'
    import CheckWork from '../Global/CheckWork.vue'
    import { inject, onBeforeMount, onMounted, provide, ref, watch } from 'vue'
    import { useRoute } from 'vue-router'
    import { useFilePreview } from "@/store/filePreview"
    import { useAuthUserStore } from '@/store/auth'
    import { useTheme } from '@/store/theme'
    import { useMessageUsers } from '@/store/messageUsers'
    import { useSharingDataStore } from '@/store/sharingData'
    import { useTaskFeedback } from '@/store/taskFeedback'
    const sharingData = useSharingDataStore()
    const messageUsers = useMessageUsers()
    const taskFeedBack = useTaskFeedback()
    const route = useRoute()
    const themeStore = useTheme()
    const viewIncompleteWindow = ref(false)
    const incompleteRef = ref(null)
    const refresh = inject('refreshMessage')
    const filePreview = useFilePreview()
    const auth = useAuthUserStore()
    onBeforeMount(() => {
        const customTheme = localStorage.getItem('dark')
        if(customTheme == 0 || customTheme == '0' || !customTheme){
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                themeStore.setDark(true)
            } else {
                themeStore.setDark(false)
            }
        }else if(parseInt(customTheme) == 1 ){
            themeStore.setDark(true)
        }else if(parseInt(customTheme) == 2 ){
            themeStore.setDark(false)
        }
    })
    watch(() => themeStore.dark, (newVal) => {
        if(theme){
            theme.forEach(pallete => {
                document.documentElement.style.setProperty(pallete.className, newVal ? pallete.dark : pallete.light);
            });
        } 
    })
    watch(() => route.fullPath, (newVal, oldVal) => {
        if(!newVal.includes(oldVal) && !oldVal.includes(newVal)){
            incompleteCall()
        }        
    }) 
    onMounted(() => {
        incompleteCall()
    })
    const incompleteCall = () => {
        if(auth.id){
            const string = '/user/' + auth.id
            // const currentUrl = window.location.href;
            // console.log(window.location)
            if(window.location.pathname == string){
                viewIncompleteWindow.value = true
            }else{
                viewIncompleteWindow.value = false
            }
            if (hasOneHourPassed(auth.id)) {
                viewIncompleteWindow.value = true
            }
        }
        
    }
    const hasOneHourPassed = (user_id) => {
        const lastCloseTime = localStorage.getItem('popupCloseTime_' + user_id);
        if (!lastCloseTime) {
            return true; // If no timestamp found, treat it as an hour has passed
        }

        const oneHour = 60 * 60 * 1000; // 1 hour in milliseconds
        const currentTime = new Date().getTime();
        const elapsedTime = currentTime - parseInt(lastCloseTime, 10);

        return elapsedTime >= oneHour;
    }
    const closePopup = () => {
        if(auth.id){
            const user_id = auth.id
            const string = '/user/' + user_id
            const currentUrl = window.location.href;
            if(currentUrl.includes(string)){
                viewIncompleteWindow.value = false
            }else{
                const currentTime = new Date().getTime();
                localStorage.setItem('popupCloseTime_' + user_id, currentTime);
                viewIncompleteWindow.value = false
            }
        } 
    }
    const getIncompleteMessage = () => {
        if(incompleteRef.value && incompleteRef.value.getUnsignedMessages){
            incompleteRef.value.getUnsignedMessages()
        }else{
            refresh()
        }
    }
    provide('getIncompleteMessage', getIncompleteMessage)
</script>

