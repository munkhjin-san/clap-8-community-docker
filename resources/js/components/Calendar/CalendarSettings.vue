<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>カレンダー設定</p>
        </template>
        <template #content>
            <div>
                <h4>カラー設定</h4>
                <p class="mt-[20px]">マイカラー</p>
                <div class="flex mt-[20px] flex-wrap">                    
                    <div :class="['color-item-parent', {'selected-color' : chosenColor == color.id}]" v-for="(color, index) in colors" :key="index">
                        <div @click="chosenColor = color.id" class="color-div cursor-pointer" :style="{backgroundColor: color.light}"></div>
                    </div>                    
                </div>
                <div class="jump-link text-[13px] mt-[20px]" @click="setProjectColor">
                    プロジェクトごとにカラーを設定する
                </div>
                <div v-if="projectColorSetting" class="mt-[20px] bg-[var(--bg3)]">
                    <div class="h-[40vh] bg-[var(--bg3)] overflow-y-auto px-[15px]">                  
                        <table>
                            <thead class="h-[45px]">
                                <tr>
                                    <th class="text-left">プロジェクト名</th>
                                    <th class="text-left ml-[10px]">カラー</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="projectLoader == 0">
                                    <tr v-for="n in 5" :key="n">
                                        <td>
                                            <div class="bg-[var(--background-color)] animate-pulse h-[20px] my-[7px] rounded-md" :style="{width: `${Math.floor(Math.random() * (50 - 40 + 1)) + 40}%`}"></div>
                                        </td>
                                        <td>
                                            <div class="bg-[var(--background-color)] animate-pulse h-[20px] my-[7px] rounded-md w-[20px]"></div>
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <ProjectColorItem v-for="project in myProjects" :key="project.id" :project="project" v-model="projectSelectedItems[project.id]"/>
                                    <ProjectColorItem v-for="project in otherProjects" :key="project.id" :project="project" v-model="projectSelectedItems[project.id]"/>
                                </template>
                            </tbody>
                        </table>                     
                    </div>
                </div>
                <div class="my-[30px]">
                    <LoaderButton class="!m-0" @triggered="setSelectedColor" :loading="colorLoader" :content="'保存'"/>
                </div>
            </div>
            <div>
                <h4>Google カレンダー連携</h4>
                <p class="text-[gray] my-[30px] text-sm leading-normal">Google カレンダーと連携し、Google カレンダーのイベントを表示できます。Googleアカウントでログインする必要があります。</p>
                <div>
                    <div class="flex items-center gap-2">
                        <img class="w-9 h-9 rounded-full" v-if="googleSettingData.user_info.avatar_url" :src="googleSettingData.user_info.avatar_url" alt="User Avatar" />
                        <div>
                            <p v-if="googleSettingData.user_info.name" class="mb-[10px]">{{ googleSettingData.user_info.name }}</p>
                            <p class="text-[12px]">ステータス：{{ googleSettingData.status }}</p>
                        </div>
                        
                    </div>
                    <div class="mt-[15px]">
                        <LoaderButton class="!m-0" content="カレンダー連携" :loading="syncing" v-if="googleSettingData.status === '未設定'" @triggered="startSync"/>
                        <LoaderButton class="!m-0" content="連携解除" :loading="syncing" v-if="googleSettingData.status === '接続済み' || googleSettingData.status === 'エラーが発生しました'" @triggered="disconnectGoogleCalendar"/>
                    </div>
                </div>
                <div class="mt-[20px] flex flex-col gap-4">
                    <div v-for="calendar in googleSettingData.calendars" :key="calendar.id" >
                        <label class="flex gap-[10px] items-center cursor-pointer">
                            <input type="checkbox" class="custom-f-checkbox" v-model="googleSettingData.calendar_ids" :value="calendar.id" />
                            <span class="w-4 h-4 rounded-full" :style="{ backgroundColor: calendar.backgroundColor }"></span>
                            <p>{{ calendar.summary }}</p>
                        </label>
                        
                    </div>
                </div>
                <p class="text-[gray] my-[30px] text-[11px] whitespace-break-spaces leading-normal">
                    ※ Google カレンダーのデータは、GoogleのAPIを通じて取得されます。当アプリケーションのデータベースには保存されません。<br/>
                    ※ Google カレンダーのイベントは閲覧のみ可能で、編集や削除はできません。<br/>
                    ※ Google カレンダーのイベントは本人のみが閲覧できます。他のユーザーには表示されません。<br/>
                    ※ Google カレンダーのイベントの時間は、日本時間に基づいて表示されます。<br/>
                </p>
                <div class="si-box" v-if="googleSettingData.calendars && googleSettingData.calendars.length">
                    <LoaderButton class="!m-0" :loading="loading" content="保存する" @triggered="save"/>
                </div>
                <div class="mt-[30px]" v-if="auth.activeUser?.id && [540, 608 ,516, 604].includes(auth.activeUser?.id)">
                    <div>iCalendar出力</div>
                    <div style="padding: 15px;line-height: 1.5;font-size: 14px;background: var(--bg3);margin: 20px 0;display: flex;flex-wrap: wrap;" v-if="icalUrl.status">
                        <div ref="icalRef" style="user-select: all;">{{ icalUrl.url }}</div>
                        <button @click.prevent="copyUrl" style="height: fit-content;margin-left: auto;user-select: none;" class="commentEditButton">コピー</button>
                    </div>
                    <div class="my-[30px]">
                        <LoaderButton class="!m-0" @triggered="createUrl" :loading="urlCreating" :content="'URL生成'"/>
                    </div>
                </div>
                
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref, useTemplateRef } from 'vue';
import Modal from '../Global/Modal.vue';
import axios from 'axios';
import { GoogleCalendarListResponse, ProjectSetting } from '@/interface/calendarInterface';
import 'styles/customForm.css'
import LoaderButton from '../Global/LoaderButton.vue';
import { useDialog } from '@/composables/dialog';
import colors from 'assets/colors.json'
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import ProjectColorItem from './CalendarSettings/ProjectColorItem.vue';
const emit = defineEmits<{
    close: [reload: boolean]
}>()
const { ask, ping, toast } = useDialog()
const api = useApi()
const auth = useAuthUserStore()
const loading = ref(false)
const syncing = ref(false)
const colorLoader = ref(false)
const projectColorSetting = ref(false)
const projectLoader = ref(0)
const urlCreating = ref(false)
const icalUrl = ref({
    status: false,
    url: ''
})
const googleSettingData = ref<GoogleCalendarListResponse>({
    calendars: [],
    status: '読み込み中',
    calendar_ids: [],
    user_info: {
        name: '',
        avatar_url: ''
    }
})

const myProjects = ref<ProjectSetting[]>([])
const otherProjects = ref<ProjectSetting[]>([])
const chosenColor = ref(auth.user?.color || 0)
const projectSelectedItems = reactive<{[key: number]: string}>({})
const icalRef = useTemplateRef('icalRef')
onMounted(() => {
    getGoogleCalendars()
    if(auth.user && auth.user.ical_key){
        icalUrl.value = {
            status: true,
            url: `${window.location.origin}/export_ical?id=${auth.id}&token=${auth.user.ical_key}`
        }
    }
})
const setProjectColor = async() => {
    projectColorSetting.value = true
    const allProjects = await api.get('/project_list')
    myProjects.value = allProjects.myProjects
    otherProjects.value = allProjects.otherProjects
    const merged = [...myProjects.value, ...otherProjects.value]
    for(const project of merged) {
        if(project.color !== null) {
            projectSelectedItems[project.id] = project.color
        }
    }
    projectLoader.value ++

}
const getGoogleCalendars = async() => {    
    try {
        const res = await axios.get('/check_google_calendars')
        googleSettingData.value = res.data
    } catch (error) {
        console.error('Error fetching Google Calendars:', error)
        googleSettingData.value.status = 'エラーが発生しました'
    } 
}
const save = async() => {
    loading.value = true
    try {
        await axios.post('/save_google_calendar_settings', {
            calendar_ids: googleSettingData.value.calendar_ids
        })
        toast('保存しました。')
        emit('close', true)
    } catch (error: any) {
        ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
    } finally {
        loading.value = false
    }
}
const startSync = async() => {
    syncing.value = true
    const proceed = await ask('Google カレンダーと連携します。よろしいですか？')
    if(!proceed.value) {
        syncing.value = false
        return;
    };
    window.location.href = '/auth/google/auth'
}
const disconnectGoogleCalendar = async() => {
    syncing.value = true
    const proceed = await ask('Google カレンダーとの連携を解除します。よろしいですか？')
    if(!proceed.value) {
        syncing.value = false
        return;
    };
    try {
        await axios.post('/disconnect_google_calendar')
        toast('連携を解除しました。')
        emit('close', true)
    } catch (error: any) {
        ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
    } finally {
        syncing.value = false
    }
}
const setSelectedColor = async() => {
    if(colorLoader.value) return
    colorLoader.value = true
    await api.post('/profile_set_color', {value: chosenColor.value, project_colors: projectSelectedItems}, {
        toast: '保存しました。'
    })
    colorLoader.value = false  
    updateUser()

}
const updateUser = async() => {
    const response = await api.post('/profile_get_update_user', {id: auth.id})
    if(response && Object.hasOwn(response, 'id')){  
        auth.setUser(response)                       
    }

}
const copyUrl = () => {
    const selectedText = icalRef.value ? icalRef.value.textContent : ''
    if(!selectedText){
        ping('コピーに失敗しました。')
    }
    navigator.clipboard.writeText(selectedText)
    
    .then(() => {
        toast('コピーしました。')
    })
    .catch((error) => {
        ping('テキストをクリップチャットにコピーできません:' + error)
    });
}
const createUrl = async() => {

    const response = await api.get('/ical_url_generate', {}, {
        toast: 'URLを生成しました。',
        loadingRef: urlCreating
    })
    if(response !== null && Object.hasOwn(response, 'url')){
        icalUrl.value.url = response.url
    }

}
</script>
<style lang="scss" scoped>
    .color-item-parent{
        padding: 5px;
        border: solid 1px transparent;
    }
    .selected-color{
        border: solid 1px var(--primary-color);
    }
    label{
        color: var(--primary-color);
    }   

    .color-div{
        width: 25px;
        height: 25px;
        min-width: 25px;
    }
    table{
        width: 100%;
        th, td{
            padding:5px;
            font-size: 13px;
            font-weight: normal;
        }
        thead{
            border-bottom: solid 1px var(--border-color);
            th{
                padding-bottom: 10px;
            }
            position: sticky;
            background: var(--bg3);
            top: 0;
            z-index: 6;
        }
        
    }

</style>