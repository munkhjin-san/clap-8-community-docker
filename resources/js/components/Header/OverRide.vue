<template>
    <div id="override">
            
        <!-- <IncompleteWindow 
            ref="incompleteRef" 
            v-if="auth.user && viewIncompleteWindow"
            :canGetRemind="canGetRemind" 
            @closePopup="closePopup"
        />  -->
        <!-- <Transition name="modalFade">
            <IncompleteFeedBack v-if="taskFeedBack.active"/>
        </Transition> -->
        <Transition name="modalFade">
            <TaskRequest v-if="taskRequest.active"/>
        </Transition>
        <Transition name="modalFade">
            <MessageUsers v-if="messageUsers.active"/>
        </Transition>
        <Teleport to="body">
            <Transition name="modalFade">
                <FilePreview v-if="filePreview.active"/>
            </Transition> 
        </Teleport>
        <Transition>
            <ProjectWeather v-if="auth.user && auth.user.position_id === 6"/>
        </Transition>
        <SharingData v-if="sharingData.active && (route.name == 'board' || route.name == 'room')"/>
        <Transition name="modalFade">
            <CheckWork v-if="auth.user" />
        </Transition>
        <Transition name="modalFade">
            <TaskUsers v-if="taskUsers.active"/>
        </Transition>
        <Transition name="modalFade">
            <ProjectUsers v-if="projectUsers.active"/>
        </Transition>
        <Transition name="modalFade">
            <DateTimeSelect v-if="messageSchedule.active"/>
        </Transition>
        <Transition name="modalFade">
            <SurveyUsers v-if="surveyUsers.active"/>
        </Transition>
    </div>
</template>

<script setup>
    import IncompleteWindow from '../Board/IncompleteWindow.vue'
    import IncompleteFeedBack from '../Board/IncompleteFeedBack.vue'
    import theme from '../../../assets/theme.json'
    import MessageUsers from '../Board/Message/MessageUsers.vue'
    import SharingData from '../Global/SharingData.vue'
    import FilePreview from '../Board/Tray/File/FilePreview.vue'
    import CheckWork from '../Global/CheckWork.vue'
    import TaskRequest from '../Board/Tray/Task/TaskRequest.vue'
    import { inject, onBeforeMount, onMounted, provide, ref, watch } from 'vue'
    import { useRoute } from 'vue-router'
    import { useFilePreview } from "@/store/filePreview"
    import { useAuthUserStore } from '@/store/auth'
    import { useTheme } from '@/store/theme'
    import { useMessageUsers } from '@/store/messageUsers'
    import { useSharingDataStore } from '@/store/sharingData'
    import { useTaskFeedback } from '@/store/taskFeedback'
    import TaskUsers from '../Board/Tray/Task/TaskUsers.vue'
    import { useTaskUsers } from '@/store/taskUsers'
    import { useTaskRequest } from '@/store/taskRequest'
    import ProjectUsers from '../AccountControl/ProjectControl/ProjectUsers.vue'
    import DateTimeSelect from '../Global/DateTimeSelect.vue'
    import { useProjectUsers } from '@/store/projectUsers'
    import { useMessageSchedule } from '@/store/messageSchedule'
    import ProjectWeather from '../Global/ProjectWeather.vue'
    import SurveyUsers from '../Survey/SurveyUsers.vue'
    import { useSurveyUsers } from '@/store/surveyUsers'
    const sharingData = useSharingDataStore()
    const messageUsers = useMessageUsers()
    const taskUsers = useTaskUsers()
    const surveyUsers = useSurveyUsers()
    const projectUsers = useProjectUsers()
    const taskFeedBack = useTaskFeedback()
    const taskRequest = useTaskRequest()
    const route = useRoute()
    const themeStore = useTheme()
    const viewIncompleteWindow = ref(false)
    const incompleteRef = ref(null)
    const refresh = inject('refreshMessage')
    const filePreview = useFilePreview()
    const auth = useAuthUserStore()
    const messageSchedule = useMessageSchedule()
    const canGetRemind = ref(false)
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
    watch(() => route.path, (newVal, oldVal) => {
        if(!newVal.includes(oldVal) && !oldVal.includes(newVal)){
            incompleteCall()
        }        
    }) 
    onMounted(() => {
        incompleteCall()
        
    })
    const incompleteCall = () => {
        if (!auth.id) return;
        const userPath = `/user/${auth.id}`;
        const isUserProfile = window.location.pathname === userPath;
        
        // canGetRemind.value = shouldCallRemindMessagesNextMorning(auth.activeUser.id) || isUserProfile;
        viewIncompleteWindow.value = hasOneHourPassed(auth.activeUser.id) || isUserProfile;
    }
    const shouldCallRemindMessagesNextMorning = (user_id) => {
        const lastCloseTime = localStorage.getItem('remindPopupCloseTime_' + user_id);
        const nextMorning9am = getNextMorning9AM();

        if (!lastCloseTime) {
            return true;
        }

        return new Date().getTime() >= nextMorning9am.getTime();
    } 
    const getNextMorning9AM = () => {
        const now = new Date();
        const nextMorning = new Date(now);
        nextMorning.setHours(9, 0, 0, 0);
        
        if (now.getHours() >= 9) {
            nextMorning.setDate(now.getDate() + 1);
        }

        return nextMorning;
    }
    const hasOneHourPassed = (user_id) => {
        const lastCloseTime = localStorage.getItem('popupCloseTime_' + user_id);
        if (!lastCloseTime) {
            return true;
        }

        const oneHour = 60 * 60 * 1000;
        const currentTime = new Date().getTime();
        const elapsedTime = currentTime - parseInt(lastCloseTime, 10);

        return elapsedTime >= oneHour;
    }
    const closePopup = () => {
        if(auth.id){
            const user_id = auth.activeUser.id
            const string = '/user/' + user_id
            const currentUrl = window.location.href;
            // const { remind, ... updatedQuery } = route.query
            // router.replace({
            //     path: route.fullPath,
            //     query: updatedQuery
            // })
            if(currentUrl.includes(string)){
                viewIncompleteWindow.value = false
            }else{
                const currentTime = new Date().getTime();
                localStorage.setItem('popupCloseTime_' + user_id, currentTime);
                // localStorage.setItem('remindPopupCloseTime_' + user_id, currentTime);
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

