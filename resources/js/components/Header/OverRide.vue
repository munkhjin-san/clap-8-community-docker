<template>
    <div id="override">

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
        <Transition name="modalFade">
            <EmoteUsers v-if="emoteUsers.length"/>
        </Transition>
        <WeatherPopup v-if="weatherModal" @close="weatherModal = false"/>
    </div>
</template>

<script setup>

import theme from 'assets/theme.json'
// import MessageUsers from '../Board/Message/MessageUsers.vue'
// import SharingData from '../Global/SharingData.vue'
// import FilePreview from '../Board/Tray/File/FilePreview.vue'
import CheckWork from '../Global/CheckWork.vue'
// import TaskRequest from '../Board/Tray/Task/TaskRequest.vue'
import { defineAsyncComponent, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFilePreview } from "@/store/filePreview"
import { useAuthUserStore } from '@/store/auth'
import { useTheme } from '@/store/theme'
import { useMessageUsers } from '@/store/messageUsers'
import { useSharingDataStore } from '@/store/sharingData'
// import TaskUsers from '../Board/Tray/Task/TaskUsers.vue'
import { useTaskUsers } from '@/store/taskUsers'
import { useTaskRequest } from '@/store/taskRequest'
// import ProjectUsers from '../AccountControl/ProjectControl/ProjectUsers.vue'
// import DateTimeSelect from '../Global/DateTimeSelect.vue'
import { useProjectUsers } from '@/store/projectUsers'
import { useMessageSchedule } from '@/store/messageSchedule'
// import SurveyUsers from '../Survey/SurveyUsers.vue'
import { useSurveyUsers } from '@/store/surveyUsers'
// import WeatherPopup from '../Global/WeatherPopup.vue'
import { isTodayDone } from '@/utils/tools'
import { useModal } from '@/composables/modal'
// import EmoteUsers from '../Global/EmoteUsers.vue'
const TaskUsers = defineAsyncComponent(() => import('../Board/Tray/Task/TaskUsers.vue'))
const ProjectUsers = defineAsyncComponent(() => import('../AccountControl/ProjectControl/ProjectUsers.vue'))
const DateTimeSelect = defineAsyncComponent(() => import('../Global/DateTimeSelect.vue'))
const SurveyUsers = defineAsyncComponent(() => import('../Survey/SurveyUsers.vue'))
const WeatherPopup = defineAsyncComponent(() => import('../Global/WeatherPopup.vue'))
const EmoteUsers = defineAsyncComponent(() => import('../Global/EmoteUsers.vue'))
const MessageUsers = defineAsyncComponent(() => import('../Board/Message/MessageUsers.vue'))
const SharingData = defineAsyncComponent(() => import('../Global/SharingData.vue'))
const FilePreview = defineAsyncComponent(() => import('../Board/Tray/File/FilePreview.vue'))
const TaskRequest = defineAsyncComponent(() => import('../Board/Tray/Task/TaskRequest.vue'))
    const sharingData = useSharingDataStore()
    const messageUsers = useMessageUsers()
    const taskUsers = useTaskUsers()
    const surveyUsers = useSurveyUsers()
    const projectUsers = useProjectUsers()
    const taskRequest = useTaskRequest()
    const route = useRoute()
    const themeStore = useTheme()
    const filePreview = useFilePreview()
    const auth = useAuthUserStore()
    const messageSchedule = useMessageSchedule()
    const weatherModal = ref(false)
    const { emoteUsers } = useModal()
    
    watch(() => themeStore.dark, (newVal) => {
        if(theme){
            theme.forEach(pallete => {
                document.documentElement.style.setProperty(pallete.className, newVal ? pallete.dark : pallete.light);
            });
        } 
    })
    const checkToday = () => {
        if (auth.user.weathers) return true
        if (isTodayDone(auth.id)) return true
    }
    onMounted(() => {
        weatherModal.value = !checkToday()
    })


</script>

