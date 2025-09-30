<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>カレンダー設定</p>
        </template>
        <template #content>
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
                        <LoaderButton class="!m-0" content="連携解除" :loading="syncing" v-if="googleSettingData.status === '接続済み'" @triggered="disconnectGoogleCalendar"/>
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
                <div class="si-box" v-if="googleSettingData.calendars">
                    <LoaderButton :loading="loading" content="保存する" @triggered="save"/>
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { inject, onMounted, ref } from 'vue';
import Modal from '../Global/Modal.vue';
import axios from 'axios';
import { GoogleCalendarListResponse } from '@/interface/calendarInterface';
import 'styles/customForm.css'
import LoaderButton from '../Global/LoaderButton.vue';
import { useDialog } from '@/composables/dialog';

const emit = defineEmits<{
    close: [reload: boolean]
}>()
const { ask, ping, toast } = useDialog()
const loading = ref(false)
const syncing = ref(false)
const googleSettingData = ref<GoogleCalendarListResponse>({
    calendars: [],
    status: '読み込み中',
    calendar_ids: [],
    user_info: {
        name: '',
        avatar_url: ''
    }
})

onMounted(() => {
    getGoogleCalendars()
})

const getGoogleCalendars = async() => {    
    try {
        const res = await axios.get('/check_google_calendars')
        googleSettingData.value = res.data
    } catch (error) {
        console.error('Error fetching Google Calendars:', error)
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
    } catch (error) {
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
    } catch (error) {
        ping(error.response?.data.message || error?.message || 'エラーが発生しました。')
    } finally {
        syncing.value = false
    }
}
</script>