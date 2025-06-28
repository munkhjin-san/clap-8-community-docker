<template>
    <Modal @close="emit('close', false)" persist :body-style="bodyStyle">
        <template #title>
            <div class="flex items-center gap-[15px]">
                <div v-if="step > 1" @click="step--" class="flex items-center justify-center w-[30px] h-[30px] min-w-[30px] cursor-pointer ml-[-15px]">
                    <Back size="15" />
                </div>
                <div>{{ step == 1 ? '基本情報' : '日時設定' }}</div>
            </div>
        </template>
        <template #content>
            <div v-show="step == 1">  
                <div>
                    <ShortInput 
                        type="text"
                        v-model="title"
                        :placeHolder="'タイトル'"
                        :rules="'required'"
                        ref="titleRef"
                    />
                </div>          
                <div class="si-box">
                    <p>メンバー選択</p>
                    <div class="mt-[20px]">
                        <GroupSelector place-holder="グループ・プロジェクトから選択" v-model="targetUsers"/>
                    </div>
                    <div class="mt-[20px]">
                        <MemberSelector 
                            placeHolder="メンバー選択"
                            rules="required"
                            name="calendarUsers"
                            ref="targetUsersRef"
                            path="calendar_more_users"
                            :multiple="true"
                            :closeOnSelect="false"
                            v-model="targetUsers"
                        />
                    </div>
                </div>
                <div class="mt-[20px] flex items-center gap-[15px]">
                    <p class="min-w-[70px]">所要時間</p>
                    <select ref="durationHourRef" 
                        id="durationHour"
                        v-model="duration.hour"
                        class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                        :class="[{ 'date-color': theme.dark }]">
                        <option
                            v-for="hour in hourOptions"
                            :key="hour.value" :value="hour.value">
                            {{ hour.label }}
                        </option>
                    </select>
                    <select ref="durationMinuteRef" 
                        id="durationMinute"
                        v-if="duration.hour < 24"
                        v-model="duration.minute"
                        class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                        :class="[{ 'date-color': theme.dark }]">
                        <option
                            v-for="hour in [{value: 0, label: '0分'}, {value: 15, label: '15分'}, {value: 30, label: '30分'}, {value: 45, label: '45分'}]"
                            :key="hour.value" :value="hour.value">
                            {{ hour.label }}
                        </option>
                    </select>
                </div>
                <div class="mt-[20px] flex items-center gap-[15px]">
                    <p class="min-w-[70px]">バッファ</p>
                    <select
                        id="buffer"
                        v-model="buffer"
                        class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                        :class="[{ 'date-color': theme.dark }]">
                        <option
                            v-for="bufferOp in [15, 30, 45, 60]"
                            :key="bufferOp" :value="bufferOp">
                            {{ bufferOp }}分
                        </option>
                    </select>
                </div>
                <div class="mt-[20px] flex items-center gap-[15px]">
                    <p class="min-w-[70px]">施設</p>
                    <select
                        id="facility"
                        v-model="selectedRoom"
                        class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                        :class="[{ 'date-color': theme.dark }]">
                        <option :value="null">未選択</option>
                        <option
                            v-for="room in facilites.qualified_institution"
                            :key="room.value" :value="room.value">
                            {{ room.label }}
                        </option>
                    </select>
                </div>
                <div class="mt-[20px] flex items-center gap-[15px]">
                    <p class="min-w-[70px]">WEB会議</p>
                    <select 
                        id="zoon"
                        v-model="selectedZoom"
                        class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                        :class="[{ 'date-color': theme.dark }]">
                        <option :value="null">未選択</option>
                        <option
                            v-for="zoom in facilites.zoom_value"
                            :key="zoom.value" :value="zoom.value">
                            {{ zoom.label }}
                        </option>
                    </select>
                </div>

                <div class="si-box">
                    <LongInput 
                        type="text"
                        v-model="content"
                        :placeHolder="'メモ'"
                    />
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="search()" content="次へ"/>
                </div>
            </div>
            <div v-show="step == 2" class="h-full">            
                <div class="reserve-table-wrapper"> 
                    <Transition name="modalFade">
                        <div class="absolute w-full h-full left-0 top-0 bg-[var(--background-color)] opacity-50 z-[3]" v-if="searching">
                            <div id="loaderMini">
                                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                            </div>
                        </div>
                    </Transition>
                    <div class="flex justify-center z-[15] min-h-[40px] sticky top-[0px] bg-[var(--background-color)]">
                        <WeekPicker v-model="startDate"/>
                    </div>
                    <table class="temp-reserve-table">
                        <thead class="sticky top-[40px] z-[10] bg-[var(--background-color)]">
                            <td class="!border-0 !w-[45px] !max-w-[45px]"></td>      
                            <DayHeader 
                                v-for="(date) in Object.keys(blockData)" 
                                :key="date" 
                                :date="date"
                                :holidays="holidays"
                            />
                        </thead>
                        <tbody>
                            <DayRow 
                                v-for="(hourItem, index) in hourOfDay" 
                                :key="index"
                                :block-data="blockData"
                                :hour="hourItem"
                                :duration="duration"
                                :highlighted="tempHighlighted"
                                @setHighlight="selectSlot"
                            />
                        </tbody>                    
                    </table>
                </div>    
                <div class="mt-[25px]">
                    <LoaderButton @triggered="save" :loading="saving" content="保存する"/>
                </div>
            </div>        
        </template>
    </Modal>
</template>
<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import MemberSelector from '../Form/MemberSelector.vue';
import GroupSelector from '../Form/GroupSelector.vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { User } from '@/interface/globalInterface';
import ShortInput from '../Form/ShortInput.vue';
import { DateTime } from 'luxon';
import { useAuthUserStore } from '@/store/auth';
import 'styles/customForm.css'
import { useTheme } from '@/store/theme';
import WeekPicker from '../Global/WeekPicker.vue';
import Back from '../Icons/Back.vue';
import DayHeader from './TempReserve/DayHeader.vue';
import * as holiday_jp from '@holiday-jp/holiday_jp';
import { DailySchedule, DateSchedule, FacList } from '@/interface/calendarInterface';
import DayRow from './TempReserve/DayRow.vue';
import LongInput from '../Form/LongInput.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';

const emit = defineEmits<{
    close: [flag: boolean];
}>()
const api = useApi()
const { ping } = useDialog()
const auth = useAuthUserStore()
const theme = useTheme()
const targetUsers = ref<User[]>([auth.user as unknown as User]);
const searching = ref(false)
const targetUsersRef = useTemplateRef('targetUsersRef')
const titleRef = useTemplateRef('titleRef')
const startDate = ref( DateTime.now().startOf('week').toISODate())
const endDate = ref(DateTime.now().plus({ days: 1 }).toISODate())
const selectedRoom = ref<number | null>(null)
const selectedZoom = ref<number| null>(null)
const buffer = ref(15)
const saving = ref(false)
const step = ref(1)
const title = ref('')
const content = ref('')

const blockData = ref<DateSchedule>({})
const facilites = ref<FacList>({
    qualified_institution: [],
    zoom_value: [],
    qualified_care: []
})

const tempHighlighted = ref<string | null>(null)
const duration = ref({
    hour: 1,
    minute: 0
})

onMounted(async() => {
    facilites.value = await api.get('/all_facility_items')
})
const bodyStyle = computed(() => {
    if(step.value == 2){
        return 'height: calc(100% - 110px); overflow:hidden;'
    }
})
const holidays = computed(() => {
    const holidays = holiday_jp.between(DateTime.fromISO(startDate.value).startOf('year').toJSDate(), DateTime.fromISO(startDate.value).endOf('year').toJSDate());
    return holidays as {date: Date, name: string}[]
}) 
const hourOptions = computed(() => {
    const options:{value: number, label:string}[] = []
    for (let i = 0; i <= 8; i++) {
        options.push({
            value: i,
            label: `${i}時間`
        })
    }
    return options
})

const validateDate = () => {
    const start = DateTime.fromISO(startDate.value)
    if( !start.isValid ) {
        ping('日付が正しくありません')
        return false
    }
    return true
}


const hourOfDay = computed(() => {
    const hours: string[] = []
    const start = DateTime.fromObject({ hour: 7, minute: 0 })
    const end = DateTime.fromObject({ hour: 20, minute: 45 })
    if (!start.isValid || !end.isValid) {
        return hours
    }
    let current = start

    while (current <= end) {
        hours.push(current.toFormat('HH:mm'))
        current = current.plus({ minutes: 15 })
    }

    return hours
})
const search = async () => {

    if (!validateDate()) {
        return
    }
    const validTargets = [targetUsersRef.value, titleRef.value].filter(ref => ref !== null)
    let result = true
    for (const ref of validTargets) {
        const val = await ref.validate()
        result = result && (val?.valid ? true : false)
    }
    if (!result) {
        ping('必須項目を入力してください')
        return
    }
    if(duration.value.hour < 1 && duration.value.minute < 15){
        ping('所要時間は最低15分以上を設定してください')
        return
    }
    searching.value = true
    step.value = 2
    blockData.value = await api.post('/calendar_temp_reserve', {
        users: targetUsers.value ?? [],
        start_date: startDate.value,
        buffer: buffer.value,
        zoom: selectedZoom.value,
        room: selectedRoom.value,
    })   

    searching.value = false

}

const selectSlot = (day: DailySchedule, hourItem: string, dateIndex:number | string) => {
    const slot = `${dateIndex.toString()} ${hourItem}`
    if(tempHighlighted.value === slot){
        tempHighlighted.value = null
        return
    }
    tempHighlighted.value = slot
}

const save = async() => {
    if(!tempHighlighted.value){
        ping('予約する時間を選択してください')
        return
    }
    
    let convertableFacilities = {
        qualified_institution:<number | null> selectedRoom.value,
        zoom_value:<number | null> selectedZoom.value,
        qualified_car: null
    }  

    const selectedDate = DateTime.fromFormat(tempHighlighted.value, 'yyyy-MM-dd HH:mm');
    
    if (!selectedDate.isValid) {
        ping('選択された時間が正しくありません。');
        return;
    }
 
    const once_date = selectedDate.toISODate();
    const time_start = selectedDate.toFormat('HH:mm');
    const time_end = selectedDate.plus({ hours: duration.value.hour, minutes: duration.value.minute }).toFormat('HH:mm');

    const checkData = blockData.value[once_date]
    let cursor = selectedDate;
    const endPoint = selectedDate.plus({ hours: duration.value.hour, minutes: duration.value.minute }).minus({ minutes: 15 })

    while (cursor <= endPoint) {
        const hourKey = cursor.toFormat('HH:mm');
        if (!checkData || !checkData[hourKey] || Object.values(checkData[hourKey]).some((value) => value === false)) {
            ping('選択された時間帯は予約できません。');
            return;
        }
        cursor = cursor.plus({ minutes: 15 });
    }

    const confirmMessage = `以下の内容で仮スケジュールを作成します。
    タイトル: ${title.value}
    日時: ${once_date} ${time_start} ~ ${time_end}
    所要時間: ${duration.value.hour}時間${duration.value.minute}分
    メンバー: ${targetUsers.value.filter(u => u !== null).map(u => u.name).join(', ')}
    施設: ${selectedRoom.value !== null ? facilites.value.qualified_institution.find(f => f.value === selectedRoom.value)?.label : 'なし'}
    WEB会議: ${selectedZoom.value !== null ? facilites.value.zoom_value.find(f => f.value === selectedZoom.value)?.label : 'なし'}`;

    saving.value = true
    const params = {
        editId: null,
        release_flag: 0,
        temp_flag: true,
        title: title.value,
        remarks: content.value,
        users: targetUsers.value.filter(u => u !== null).map(ob => ob.id),
        edit_all: false,
        repetition_type: 0,
        zoom_waiting_room: 0,
        zoom_ai_companion: 1,
        time_start:  time_start,
        time_end: time_end,
        once_date: once_date,
        facility: convertableFacilities,
        file_ids: [],
        department_id: null,
        view_users: [],
    }
    const data = await api.post('/calendar_add_record', params, { toast: '作成しました。', ask: confirmMessage})
    saving.value = false 

    data && emit('close', true)          
    
 
}


watch(startDate, (newValue) => {
    if (!validateDate()) {
        return
    }
    search()
})
</script>
<style lang="scss">
.temp-reserve-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;

    th, td {
        height: 40px;
        text-align: center;
        position: relative;
        vertical-align: middle;
        transition: all 0.3s ease;
        box-sizing: border-box !important;
    }

    th {
        position: sticky;
        top: 0;
        z-index: 4;
        font-weight: normal;
        border-bottom: solid thin #ddd;
    }

    th:first-child, td:first-child {
        border-right: solid thin #ddd;
    }

    td {
        border-right: solid thin #ddd;
    }
    .time-index-45{
        border-bottom: solid thin #ddd;
    }
    .time-index-

    input[type="checkbox"] {
        cursor: pointer;
    }


    td input:disabled {
        opacity: 0.5; 
    }


    .unselectable-date{
        pointer-events: none;
        background-color: #efefef; 
        cursor: not-allowed;
        opacity: 0.5;
    }
    .t-cell{
        width: 100px;
    }
    .unavailable-slot {
        background-color: var(--past-calendar) !important;
    }
    .highlighted {
        background-color: var(--link-color);
        color: white !important;
        border-color: var(--link-color);
    }
}

.reserve-table-wrapper {
    position: relative;
    width: calc(100% + 30px);
    margin-left: -15px;
    margin-right: -15px;
    height: calc(100% - 55px);
    overflow-y: auto;
}
@media screen and (max-width: 959px) {
    .temp-reserve-table {
        th, td {
            font-size: 12px;
        }
        th:first-child, td:first-child {
            font-size: 11px;
            height: 30px;       
            border-right: solid thin #ddd;
        }
        th{
            border-bottom: solid thin #ddd;
        }
    }
    .reserve-table-wrapper {
        width: calc(100% + 60px);
        margin-left: -30px;
        margin-right: -30px;
    }
}

</style>