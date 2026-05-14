<template>
    <Modal size="large" @close="emit('close', false)" persist :body-style="bodyStyle">
        <template #title>
            <div class="flex items-center gap-[15px]">
                <div v-if="step > 1" @click="step--" class="flex items-center justify-center w-[30px] h-[30px] min-w-[30px] cursor-pointer ml-[-15px]">
                    <Back size="15" />
                </div>
                <div>{{ steps[step - 1] }}</div>
            </div>
        </template>
        <template #content>
            <div v-show="step == 1">  
                <div>
                    <ShortInput 
                        type="text"
                        v-model="title"
                        :placeHolder="'タイトル'"
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
                        @change="saveBufferTime"
                        class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                        :class="[{ 'date-color': theme.dark }]">
                        <option
                            v-for="bufferOp in bufferOptions"
                            :key="bufferOp.value" :value="bufferOp.value">
                            {{ bufferOp.label }}
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
                    <LoaderButton @triggered="search()" content="日時設定へ"/>
                </div>
            </div>
            <div v-show="step == 2" class="h-full">            
                <div
                    ref="reserveTableWrapper"
                    class="reserve-table-wrapper"
                    :class="{ 'drag-scroll-enabled': reserveView === 'memberTime', 'is-dragging': isReserveDragging }"
                > 
                    <Transition name="modalFade">
                        <div class="absolute w-full h-full left-0 top-0 bg-[var(--background-color)] opacity-50 z-[3]" v-if="searching">
                            <div id="loaderMini">
                                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                            </div>
                        </div>
                    </Transition>
                    <div class="reserve-table-toolbar">
                        <WeekPicker v-model="startDate"/>
                        <div class="reserve-table-toolbar-sub">
                            <div class="reserve-table-legend" aria-label="凡例">
                                <div class="reserve-legend-item">
                                    <span class="reserve-legend-swatch available"></span>
                                    <span>予約可</span>
                                </div>
                                <div class="reserve-legend-item">
                                    <span class="reserve-legend-swatch unavailable"></span>
                                    <span>予約不可</span>
                                </div>
                                <div class="reserve-legend-item">
                                    <span class="reserve-legend-swatch selected-available"></span>
                                    <span>選択（予約可）</span>
                                </div>
                                <div class="reserve-legend-item">
                                    <span class="reserve-legend-swatch selected-unavailable"></span>
                                    <span>選択（予約不可含む）</span>
                                </div>
                            </div>
                            <div class="reserve-view-switch" role="tablist" aria-label="表示切替">
                                <label :class="{ active: reserveView === 'dayTime' }">
                                    <input
                                        v-model="reserveView"
                                        class="custom-f-radio"
                                        name="reserve-view"
                                        type="radio"
                                        value="dayTime"
                                    >
                                    日・時間
                                </label>
                                <label :class="{ active: reserveView === 'memberTime' }">
                                    <input
                                        v-model="reserveView"
                                        class="custom-f-radio"
                                        name="reserve-view"
                                        type="radio"
                                        value="memberTime"
                                    >
                                    メンバー・時間
                                </label>
                            </div>
                        </div>
                    </div>
                    <table v-if="reserveView === 'dayTime'" class="temp-reserve-table">
                        <thead class="sticky top-[70px] z-[10] bg-[var(--background-color)]">
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
                    <table
                        v-else
                        class="temp-reserve-table member-time-table"
                        @mousedown="onReserveMouseDown"
                    >
                        <thead class="sticky top-[70px] z-[10] bg-[var(--background-color)]">
                            <td class="member-time-date-cell member-time-label-header">日付</td>
                            <td class="member-time-resource-cell member-time-label-header">メンバー</td>
                            <td
                                v-for="hour in hourColumns"
                                :key="hour"
                                class="member-time-hour-header"
                            >
                                {{ DateTime.fromFormat(hour, 'HH:mm').toFormat('H時') }}
                            </td>
                        </thead>
                        <tbody>
                            <template v-for="[date, dayData] in scheduleEntries" :key="date">
                                <tr
                                    v-for="(resource, resourceIndex) in reserveResources"
                                    :key="`${date}-${resource.key}`"
                                >
                                    <td
                                        v-if="resourceIndex === 0"
                                        :rowspan="reserveResources.length"
                                        class="member-time-date-cell no-hover cursor-default"
                                        :title="dayTitle(date)"
                                        :class="dayClass(date)"
                                    >
                                        <div>{{ DateTime.fromISO(date).toFormat('ccc') }}</div>
                                        <div class="mt-[5px]">{{ DateTime.fromISO(date).toFormat('d日') }}</div>
                                    </td>
                                    <td class="member-time-resource-cell">
                                        <div class="member-time-resource-name" :title="resource.label">
                                            {{ resource.label }}
                                        </div>
                                    </td>
                                    <td
                                        v-for="hour in hourColumns"
                                        :key="`${date}-${resource.key}-${hour}`"
                                        class="member-time-hour-cell"
                                    >
                                        <div class="member-time-quarter-grid">
                                            <button
                                                v-for="quarter in quarterHours(hour)"
                                                :key="`${date}-${resource.key}-${quarter}`"
                                                type="button"
                                                class="member-time-quarter"
                                                :class="{
                                                    'unavailable-slot': isResourceUnavailable(dayData, resource.key, quarter),
                                                    highlighted: highlightedStartForQuarter(date, quarter) !== null,
                                                    'highlighted-unavailable': highlightedStartForQuarter(date, quarter) !== null && slotIncludesUnavailable(date, highlightedStartForQuarter(date, quarter) ?? quarter)
                                                }"
                                                :title="`${resource.label} ${DateTime.fromISO(date).toFormat('M/d')} ${quarter}`"
                                                @click="handleMemberTimeSlotClick(dayData, quarter, date)"
                                            ></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>    
                <div class="mt-[25px]">
                    <LoaderButton @triggered="toConfirm" :loading="saving" content="内容確認へ"/>
                </div>
            </div>  
            <div v-show="step == 3">
                
                <div ref="confirmDetail" class="leading-normal whitespace-break-spaces text-[14px] flex flex-col gap-[20px]">
                    <p>タイトル：{{ title || '予定あり' }}</p>
                    <div>
                        <p class="mb-2.5">メンバー：</p>
                        <div class="flex flex-col gap-[5px]">
                            <div v-for="user in targetUsers" :key="user.id" >
                                <UserPanel :user="user" with-name disable-instant/>
                            </div>

                        </div>
                    </div>
                    <div>
                        <p class="mb-2.5">日時：</p>
                        <div class="flex flex-col gap-[5px]">
                            <div v-for="date in tempHighlighted" :key="date">
                                {{ DateTime.fromFormat(date, 'yyyy-MM-dd HH:mm').toFormat('M月d日 (ccc) HH:mm') }} ~ 
                                {{ DateTime.fromFormat(date, 'yyyy-MM-dd HH:mm').plus({ hours: duration.hour, minutes: duration.minute }).toFormat('HH:mm') }}
                            </div>
                        </div>
                    </div>
                    
                    <p>所要時間：{{ duration.hour }}時間{{ duration.minute }}分</p>
                    <p>施設：{{ selectedRoom !== null ? facilites.qualified_institution.find(f => f.value === selectedRoom)?.label : 'なし' }}</p>
                    <p>WEB会議：{{ selectedZoom !== null ? facilites.zoom_value.find(f => f.value === selectedZoom)?.label : 'なし' }}</p>
                    <p>メモ：{{ content || 'なし'}}</p>  
                </div>
                <div class="mt-[25px]">
                    <CommandButton :buttons="[{
                        title: '日時コピー', action: () => copy()
                    }]"/>
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
import { computed, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { User } from '@/interface/globalInterface';
import ShortInput from '../Form/ShortInput.vue';
import { DateTime, Interval } from 'luxon';
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
import UserPanel from '../Global/UserPanel.vue';
import CommandButton from '../Global/CommandButton.vue';

const emit = defineEmits<{
    close: [flag: boolean];
}>()
const steps = ['基本情報', '日時設定', '予約内容確認'];
const api = useApi()
const { ping, toast } = useDialog()
const auth = useAuthUserStore()
const theme = useTheme()
const targetUsers = ref<User[]>([auth.user as unknown as User]);
const searching = ref(false)
const targetUsersRef = useTemplateRef('targetUsersRef')
const startDate = ref( DateTime.now().startOf('week').toISODate())
const endDate = ref(DateTime.now().plus({ days: 1 }).toISODate())
const selectedRoom = ref<number | null>(null)
const selectedZoom = ref<number| null>(null)
const buffer = ref(0)
const saving = ref(false)
const step = ref(1)
const reserveView = ref<'dayTime' | 'memberTime'>('dayTime')
const reserveTableWrapper = useTemplateRef('reserveTableWrapper')
const reserveCursorPos = ref([0, 0])
const isReserveDragging = ref(false)
const hasReserveDragged = ref(false)
const title = ref('')
const content = ref('')
const bufferOptions = [
    { value: 0, label: 'なし' },
    { value: 15, label: '前後15分' },
    { value: 30, label: '前後30分' },
    { value: 45, label: '前後45分' },
    { value: 60, label: '前後60分' },
    { value: 120, label: '前後120分' }
]

const facilites = ref<FacList>({
    qualified_institution: [],
    zoom_value: [],
    qualified_care: []
})
onMounted(() => {
    blockData.value = initBlockData()
    const savedBufferTime = localStorage.getItem('tempReserveBuffer')
    buffer.value = savedBufferTime ? parseInt(savedBufferTime) : 0;
})
const initBlockData = () => {
    const data: DateSchedule = {}
    const start = DateTime.now().startOf('week');
    const end = DateTime.now().endOf('week');
    let current = start;

    while (current <= end) {
        const dateKey = current.toISODate();
        data[dateKey] = {};
        for (const hour of hourOfDay.value) {
            data[dateKey][hour] = {};
        }
        current = current.plus({ days: 1 });
    }
    return data;
}
const blockData = ref<DateSchedule>({})
const tempHighlighted = ref<string[]>([])
const duration = ref({
    hour: 1,
    minute: 0
})
const confirmDetail = useTemplateRef('confirmDetail')
onMounted(async() => {
    facilites.value = await api.get('/all_facility_items')
})
onUnmounted(() => {
    window.removeEventListener('mousemove', onReserveMouseHold)
    window.removeEventListener('mouseup', onReserveMouseUp)
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

const hourColumns = computed(() => {
    const hours: string[] = []
    const start = DateTime.fromObject({ hour: 7, minute: 0 })
    const end = DateTime.fromObject({ hour: 20, minute: 0 })
    if (!start.isValid || !end.isValid) {
        return hours
    }
    let current = start

    while (current <= end) {
        hours.push(current.toFormat('HH:mm'))
        current = current.plus({ hours: 1 })
    }

    return hours
})

const scheduleEntries = computed((): [string, DailySchedule][] => Object.entries(blockData.value))

const reserveResources = computed(() => {
    const users = targetUsers.value
        .filter((user): user is User => user !== null && user !== undefined)
        .map(user => ({
            key: user.name ?? '',
            label: user.name ?? '名称未設定'
        }))
        .filter(resource => resource.key !== '')

    const resources = [...users]

    if (selectedRoom.value !== null) {
        const room = facilites.value.qualified_institution.find(f => f.value === selectedRoom.value)
        resources.push({
            key: `room_${selectedRoom.value}`,
            label: room?.label ?? '施設'
        })
    }

    if (selectedZoom.value !== null) {
        const zoom = facilites.value.zoom_value.find(f => f.value === selectedZoom.value)
        resources.push({
            key: `zoom_${selectedZoom.value + 1}`,
            label: zoom?.label ?? 'WEB会議'
        })
    }

    return resources
})

const quarterHours = (hour: string) => {
    const start = DateTime.fromFormat(hour, 'HH:mm')
    if (!start.isValid) {
        return []
    }
    return Array.from({ length: 4 }, (_, index) => start.plus({ minutes: index * 15 }).toFormat('HH:mm'))
}

const isResourceUnavailable = (dayData: DailySchedule, resourceKey: string, hour: string) => {
    return dayData[hour]?.[resourceKey] === false
}

const onReserveMouseDown = (event: MouseEvent) => {
    if (event.button !== 0) {
        return
    }

    reserveCursorPos.value = [event.pageX, event.pageY]
    isReserveDragging.value = true
    hasReserveDragged.value = false
    window.addEventListener('mousemove', onReserveMouseHold)
    window.addEventListener('mouseup', onReserveMouseUp)
}

const onReserveMouseUp = () => {
    isReserveDragging.value = false
    window.removeEventListener('mousemove', onReserveMouseHold)
    window.removeEventListener('mouseup', onReserveMouseUp)
    window.setTimeout(() => {
        hasReserveDragged.value = false
    }, 0)
}

const onReserveMouseHold = (event: MouseEvent) => {
    const delta = [
        event.pageX - reserveCursorPos.value[0],
        event.pageY - reserveCursorPos.value[1],
    ]

    if (Math.abs(delta[0]) > 2 || Math.abs(delta[1]) > 2) {
        hasReserveDragged.value = true
    }

    reserveCursorPos.value = [event.pageX, event.pageY]
    if (!reserveTableWrapper.value || Math.abs(delta[0]) === 0) {
        return
    }

    event.preventDefault()
    requestAnimationFrame(() => {
        reserveTableWrapper.value?.scrollBy({
            left: -delta[0],
        })
    })
}

const handleMemberTimeSlotClick = (dayData: DailySchedule, hour: string, date: string) => {
    if (hasReserveDragged.value) {
        return
    }
    selectSlot(dayData, hour, date)
}

const highlightedStartForQuarter = (date: string, hour: string) => {
    const quarterInstance = DateTime.fromFormat(`${date} ${hour}`, 'yyyy-MM-dd HH:mm')
    if (!quarterInstance.isValid) {
        return null
    }

    return tempHighlighted.value.find(highlightedSlot => {
        const highlightedInstance = DateTime.fromFormat(highlightedSlot, 'yyyy-MM-dd HH:mm')
        if (!highlightedInstance.isValid || highlightedInstance.toISODate() !== date) {
            return false
        }

        const highlightedEnd = highlightedInstance.plus({ hours: duration.value.hour, minutes: duration.value.minute })
        return quarterInstance >= highlightedInstance && quarterInstance < highlightedEnd
    })?.split(' ')[1] ?? null
}

const slotIncludesUnavailable = (date: string, hour: string) => {
    const startPoint = DateTime.fromISO(date).set({
        hour: parseInt(hour.split(':')[0]),
        minute: parseInt(hour.split(':')[1])
    })
    const endPoint = startPoint.plus({ hours: duration.value.hour, minutes: duration.value.minute }).minus({ minutes: 15 })

    if (!startPoint.isValid || !endPoint.isValid) {
        return false
    }

    let cursor = startPoint
    while (cursor <= endPoint) {
        const hourKey = cursor.toFormat('HH:mm')
        const data = blockData.value[date]?.[hourKey]
        if (data && Object.values(data).some((value) => value === false)) {
            return true
        }
        cursor = cursor.plus({ minutes: 15 })
    }

    return false
}

const holidayName = (date: string) => {
    return holidays.value.find(h => DateTime.fromJSDate(h.date).toISODate() === date)?.name ?? ''
}

const dayTitle = (date: string) => {
    const dateInstance = DateTime.fromISO(date)
    return `${dateInstance.toFormat('yyyy年M月d日')} (${dateInstance.toFormat('ccc')}) ${holidayName(date)}`
}

const dayClass = (date: string) => {
    const dateInstance = DateTime.fromISO(date)
    return {
        isSaturday: dateInstance.weekday === 6,
        'special-day': dateInstance.weekday === 7 || holidayName(date),
        'cal-todayTitle': dateInstance.hasSame(DateTime.now(), 'day')
    }
}
const search = async () => {

    if (!validateDate()) {
        return
    }
    const validTargets = [targetUsersRef.value].filter(ref => ref !== null)
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
    const slotInstance = DateTime.fromFormat(slot, 'yyyy-MM-dd HH:mm');
    const slotInterval = Interval.fromDateTimes(
        slotInstance,
        slotInstance.plus({ hours: duration.value.hour, minutes: duration.value.minute })
    );
    if (!slotInstance.isValid || !slotInterval.isValid) return

    if (tempHighlighted.value.includes(slot)) {
        tempHighlighted.value = tempHighlighted.value.filter(s => s !== slot);
    } else {
        // Check if the slot overlaps with any existing highlighted slots
        const overlaps = tempHighlighted.value.some(highlightedSlot => {
            const highlightedInstance = DateTime.fromFormat(highlightedSlot, 'yyyy-MM-dd HH:mm');
            const highlightedInterval = Interval.fromDateTimes(
                highlightedInstance,
                highlightedInstance.plus({ hours: duration.value.hour, minutes: duration.value.minute })
            );
            return slotInterval.overlaps(highlightedInterval);
        });
        if (overlaps) {
            ping('選択された時間帯は既に選択されている時間帯と重複しています。');
            return;
        }
        tempHighlighted.value.push(slot);
    }
}
const toConfirm = () => {
    if(!tempHighlighted.value || tempHighlighted.value.length === 0){
        ping('予約する時間を選択してください')
        return
    }
    const selectedDates = tempHighlighted.value
    for(const selectedDate of selectedDates){
        const dateInstance = DateTime.fromFormat(selectedDate, 'yyyy-MM-dd HH:mm');
        if (!dateInstance.isValid) {
            ping('選択された時間が正しくありません。');
            return;
        }
        const once_date = dateInstance.toISODate();

        const checkData = blockData.value[once_date]
        let cursor = dateInstance;
        const endPoint = dateInstance.plus({ hours: duration.value.hour, minutes: duration.value.minute }).minus({ minutes: 15 })

        while (cursor <= endPoint) {
            const hourKey = cursor.toFormat('HH:mm');
            if (!checkData || !checkData[hourKey] || Object.values(checkData[hourKey]).some((value) => value === false)) {
                ping('選択された時間帯は予約できません。');
                return;
            }
            cursor = cursor.plus({ minutes: 15 });
        }
    }
    step.value = 3
}

const save = async() => {
    
    
    let convertableFacilities = {
        qualified_institution:<number | null> selectedRoom.value,
        zoom_value:<number | null> selectedZoom.value,
        qualified_car: null
    }  

    const selectedDates = tempHighlighted.value
    const pickedDates: { once_date: string, time_start: string, time_end:string }[] = [];
    for(const selectedDate of selectedDates){
        const dateInstance = DateTime.fromFormat(selectedDate, 'yyyy-MM-dd HH:mm');
        if (!dateInstance.isValid) {
            ping('選択された時間が正しくありません。');
            return;
        }
        const once_date = dateInstance.toISODate();
        const time_start = dateInstance.toFormat('HH:mm');
        const time_end = dateInstance.plus({ hours: duration.value.hour, minutes: duration.value.minute }).toFormat('HH:mm');
        pickedDates.push({
            once_date: once_date,
            time_start: time_start,
            time_end: time_end
        });
    }

    saving.value = true
    const params = {
        mainData: {
            editId: null,
            release_flag: 0,
            temp_flag: true,
            title: title.value ? title.value : '予約あり',
            remarks: content.value,
            users: targetUsers.value.filter(u => u !== null).map(ob => ob.id),
            edit_all: false,
            repetition_type: 0,
            zoom_waiting_room: 0,
            zoom_ai_companion: 1,
            facility: convertableFacilities,
            file_ids: [],
            department_id: null,
            view_users: [],
        },
        dateData: pickedDates
    }
    const data = await api.post('/calendar_add_temp_record', params, { toast: '作成しました。'})
    
    saving.value = false 

    data && emit('close', true)          
}
const copy = () => {
    console.log('copy', confirmDetail.value)
    if(!confirmDetail.value) return
    const textData = confirmDetail.value.innerText

    // remove empty lines
    const cleanedText = textData.split('\n').filter(line => line.trim() !== '').join('\n');
    try {
        navigator.clipboard.writeText(cleanedText)
        toast('内容をコピーしました')
    } catch (error) {
        console.error('Failed to copy text: ', error);
        toast('コピーに失敗しました')
    }
}

watch(startDate, (newValue) => {
    if (!validateDate()) {
        return
    }
    search()
})

const saveBufferTime = () => {
    localStorage.setItem('tempReserveBuffer', buffer.value.toString())
}
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
        border-bottom: solid thin var(--calendarBorder);
    }

    th:first-child, td:first-child {
        border-right: solid thin var(--calendarBorder);
    }

    td {
        border-right: solid thin var(--calendarBorder);
    }
    .time-index-45{
        border-bottom: solid thin var(--calendarBorder);
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

.reserve-table-toolbar {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: sticky;
    top: 0;
    left: 0;
    z-index: 15;
    min-height: 70px;
    width: calc(100% - 60px);
    background-color: var(--background-color);
    padding: 0 30px;
}

.reserve-table-toolbar-sub {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    width: 100%;
    min-height: 30px;
    padding: 0 15px;
    box-sizing: border-box;
    margin-top: 10px;
}

.reserve-table-legend {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 11px;
    white-space: nowrap;
}

.reserve-legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.reserve-legend-swatch {
    display: block;
    width: 14px;
    height: 14px;
    border: solid thin var(--calendarBorder);
    box-sizing: border-box;

    &.available {
        background-color: var(--background-color);
    }

    &.unavailable {
        background-color: var(--past-calendar);
    }

    &.selected-available {
        background-color: var(--link-color);
    }

    &.selected-unavailable {
        background-color: tomato;
    }
}

.reserve-view-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 12px;

    label {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 28px;
        padding: 0;
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s ease;

        
    }
}

.member-time-table {
    width: 100%;
    min-width: 1096px;
    table-layout: fixed;    
    box-sizing: border-box;
    thead{
        td{
            border-top: solid thin var(--calendarBorder);
        }
    }

    .member-time-date-cell {
        position: sticky;
        left: 0;
        z-index: 6;
        width: 60px;
        min-width: 60px;
        max-width: 60px;
        border-bottom: solid thin var(--calendarBorder);
        font-size: 12px;
        background-color: var(--background-color);
        box-shadow: inset 1px 0 0 var(--calendarBorder);
    }

    .member-time-date-cell.cal-todayTitle {
        background: #C5AF72;
    }

    .member-time-resource-cell {
        position: sticky;
        left: 60px;
        z-index: 6;
        width: 140px;
        min-width: 140px;
        max-width: 140px;
        padding: 0 8px;
        border-bottom: solid thin var(--calendarBorder);
        font-size: 12px;
        text-align: left;
        background-color: var(--background-color);
    }

    .member-time-label-header {
        z-index: 12;
        height: 38px;
        text-align: center;
        font-size: 12px;
        font-weight: normal;
        background-color: var(--background-color);
        border-bottom: solid thin var(--calendarBorder);
    }

    .member-time-resource-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .member-time-hour-header {
        width: auto;
        border-bottom: solid thin var(--calendarBorder);
        font-size: 12px;
    }

    .member-time-hour-cell {
        height: 28px;
        padding: 0;
        border-bottom: solid thin var(--calendarBorder);
        overflow: hidden;
    }

    .member-time-quarter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .member-time-quarter {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 28px;
        cursor: pointer;
        transition: background-color 0.2s ease;

        &.highlighted {
            background-color: var(--link-color);
        }

        &.highlighted-unavailable {
            background-color: tomato !important;
        }
    }
}

.reserve-table-wrapper {
    position: relative;
    width: calc(100% + 60px);
    margin-left: -30px;
    margin-right: -30px;
    height: calc(100% - 70px);
    overflow-y: auto;
    overflow-x: auto;

    &.drag-scroll-enabled {
        cursor: grab;
        width: 100%;
        margin: 0;
    }

    &.is-dragging {
        cursor: grabbing;
        user-select: none;
    }
}
@media screen and (max-width: 959px) {
    .temp-reserve-table {
        th, td {
            font-size: 12px;
        }
        th:first-child, td:first-child {
            font-size: 11px;
            height: 30px;       
            border-right: solid thin var(--calendarBorder);
        }
        th{
            border-bottom: solid thin var(--calendarBorder);
        }
    }
    .reserve-table-wrapper {
        width: calc(100% + 60px);
        margin-left: -30px;
        margin-right: -30px;
    }
    .reserve-table-toolbar {
        justify-content: flex-start;
        padding-left: 30px;
        padding-right: 30px;
    }
    .reserve-table-toolbar-sub {
        padding: 0;
        gap: 10px;
        flex-wrap: wrap;
    }
    .reserve-table-legend {
        flex-wrap: wrap;
        gap: 8px;
    }
    .reserve-view-switch {
        margin-left: auto;
    }
}

</style>
