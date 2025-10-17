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
        <WeatherPopup v-if="weatherModal" @close="weatherModal = false"/>
    </div>
</template>

<script setup>

import theme from 'assets/theme.json'
import MessageUsers from '../Board/Message/MessageUsers.vue'
import SharingData from '../Global/SharingData.vue'
import FilePreview from '../Board/Tray/File/FilePreview.vue'
import CheckWork from '../Global/CheckWork.vue'
import TaskRequest from '../Board/Tray/Task/TaskRequest.vue'
import { onBeforeMount, onMounted, provide, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useFilePreview } from "@/store/filePreview"
import { useAuthUserStore } from '@/store/auth'
import { useTheme } from '@/store/theme'
import { useMessageUsers } from '@/store/messageUsers'
import { useSharingDataStore } from '@/store/sharingData'
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
import WeatherPopup from '../Global/WeatherPopup.vue'
import { isTodayDone } from '@/utils/tools'
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
    const checkToday = () => {
        if (auth.user.weathers) return true
        if (isTodayDone(auth.id)) return true
    }
    onMounted(() => {
        weatherModal.value = !checkToday()
    })


</script>

