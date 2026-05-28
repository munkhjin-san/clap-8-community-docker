<template>
    <Modal size="large" @close="emit('close', false)" persist :body-style="bodyStyle">
        <template #title>
            <div class="flex items-center gap-[15px]">
                <div v-if="step > 2" @click="step--" class="flex items-center justify-center w-[30px] h-[30px] min-w-[30px] cursor-pointer ml-[-15px]">
                    <Back size="15" />
                </div>
                <div>{{ stepTitle }}</div>
            </div>
        </template>
        <template #content>
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
                        <div class="reserve-primary-row">
                            <WeekPicker v-model="startDate"/>
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
                        <div class="reserve-option-grid">
                            <div class="reserve-option-field reserve-option-users">
                                <span>メンバー</span>
                                <TempReserveUserPicker v-model="targetUsers"/>
                            </div>
                            <label class="reserve-option-field reserve-duration-field">
                                <span>所要時間</span>
                                <div class="reserve-inline-selects">
                                    <select
                                        id="durationHour"
                                        v-model="duration.hour"
                                        class="reserve-option-select"
                                        :class="[{ 'date-color': theme.dark }]"
                                    >
                                        <option
                                            v-for="hour in hourOptions"
                                            :key="hour.value"
                                            :value="hour.value"
                                        >
                                            {{ hour.label }}
                                        </option>
                                    </select>
                                    <select
                                        id="durationMinute"
                                        v-model="duration.minute"
                                        class="reserve-option-select"
                                        :class="[{ 'date-color': theme.dark }]"
                                    >
                                        <option
                                            v-for="minute in minuteOptions"
                                            :key="minute.value"
                                            :value="minute.value"
                                        >
                                            {{ minute.label }}
                                        </option>
                                    </select>
                                </div>
                            </label>
                            <label class="reserve-option-field reserve-option-buffer">
                                <span>バッファ</span>
                                <select
                                    id="buffer"
                                    v-model="buffer"
                                    class="reserve-option-select"
                                    :class="[{ 'date-color': theme.dark }]"
                                >
                                    <option
                                        v-for="bufferOp in bufferOptions"
                                        :key="bufferOp.value"
                                        :value="bufferOp.value"
                                    >
                                        {{ bufferOp.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="reserve-option-field reserve-option-facility">
                                <span>施設</span>
                                <select
                                    id="facility"
                                    v-model="selectedRoom"
                                    class="reserve-option-select"
                                    :class="[{ 'date-color': theme.dark }]"
                                >
                                    <option :value="null">未選択</option>
                                    <option
                                        v-for="room in facilites.qualified_institution"
                                        :key="room.value"
                                        :value="room.value"
                                    >
                                        {{ room.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="reserve-option-field reserve-option-zoom">
                                <span>WEB会議</span>
                                <select
                                    id="zoom"
                                    v-model="selectedZoom"
                                    class="reserve-option-select"
                                    :class="[{ 'date-color': theme.dark }]"
                                >
                                    <option :value="null">未選択</option>
                                    <option
                                        v-for="zoom in facilites.zoom_value"
                                        :key="zoom.value"
                                        :value="zoom.value"
                                    >
                                        {{ zoom.label }}
                                    </option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div v-if="!hasReserveOption" class="reserve-empty-state">
                        <p>メンバー・施設・WEB会議のいずれかを選択してください。</p>
                    </div>
                    <table v-else-if="reserveView === 'dayTime'" class="temp-reserve-table">
                        <thead class="reserve-table-head sticky z-[10] bg-[var(--background-color)] top-[116px]">  
                            <tr class="">
                                <td style="border:none"></td>
                                <DayHeader 
                                    v-for="(date) in Object.keys(blockData)" 
                                    :key="date" 
                                    :date="date"
                                    :holidays="holidays"
                                />
                            </tr>
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
                        <thead class="reserve-table-head sticky z-[10] bg-[var(--background-color)] top-[116px]">
                            <tr>
                            <th class="member-time-date-cell member-time-label-header">日付</th>
                            <th class="member-time-resource-cell member-time-label-header">メンバー</th>
                            <th
                                v-for="hour in hourColumns"
                                :key="hour"
                                class="member-time-hour-header"
                            >
                                {{ DateTime.fromFormat(hour, 'HH:mm').toFormat('H時') }}
                            </th>
                            </tr>
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
                <div class="reserve-table-legend mt-3" aria-label="凡例">
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
                <div class="mt-[25px]">
                    <LoaderButton @triggered="toConfirm" :loading="saving" content="内容確認へ"/>
                </div>
            </div>  
            <div v-show="step == 3" class="temp-confirm-step">
                <div ref="confirmDetail" class="temp-confirm-panel">
                    <div class="temp-confirm-edit">
                        <label class="temp-confirm-field">
                            <span>タイトル</span>
                            <input v-model="title" type="text" placeholder="予定">
                        </label>
                        <label class="temp-confirm-field">
                            <span>説明</span>
                            <textarea v-model="content" rows="3" placeholder="予定"></textarea>
                        </label>
                    </div>

                    <div class="temp-confirm-summary">
                        <section class="temp-confirm-section">
                            <div class="temp-confirm-section-title">メンバー</div>
                            <div class="temp-confirm-member-list">
                                <div v-for="user in targetUsers" :key="user.id" class="temp-confirm-member">
                                    <UserPanel :user="user" with-name disable-instant/>
                                </div>
                            </div>
                        </section>

                        <section class="temp-confirm-section">
                            <div class="temp-confirm-section-title">日時</div>
                            <div class="temp-confirm-date-list">
                                <div v-for="date in tempHighlighted" :key="date" class="temp-confirm-date-item">
                                    <span>{{ DateTime.fromFormat(date, 'yyyy-MM-dd HH:mm').toFormat('M月d日 (ccc)') }}</span>
                                    <strong>
                                        {{ DateTime.fromFormat(date, 'yyyy-MM-dd HH:mm').toFormat('HH:mm') }} ~ 
                                        {{ DateTime.fromFormat(date, 'yyyy-MM-dd HH:mm').plus({ hours: duration.hour, minutes: duration.minute }).toFormat('HH:mm') }}
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <section class="temp-confirm-meta">
                            <div>
                                <span>所要時間</span>
                                <strong>{{ duration.hour }}時間{{ duration.minute }}分</strong>
                            </div>
                            <div>
                                <span>施設</span>
                                <strong>{{ selectedRoomLabel }}</strong>
                            </div>
                            <div>
                                <span>WEB会議</span>
                                <strong>{{ selectedZoomLabel }}</strong>
                            </div>
                        </section>
                    </div>
                </div>
                <div class="mt-[25px]">
                    <CommandButton :buttons="[{
                        title: '内容をコピー', action: () => copy()
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
import { computed, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { User } from '@/interface/globalInterface';
import { DateTime, Interval } from 'luxon';
import { useAuthUserStore } from '@/store/auth';
import 'styles/customForm.css'
import { useTheme } from '@/store/theme';
import WeekPicker from '../Global/WeekPicker.vue';
import Back from '../Icons/Back.vue';
import DayHeader from './TempReserve/DayHeader.vue';
import { DailySchedule, DateSchedule, FacList } from '@/interface/calendarInterface';
import DayRow from './TempReserve/DayRow.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import UserPanel from '../Global/UserPanel.vue';
import CommandButton from '../Global/CommandButton.vue';
import { usePublicHolidayStore } from '@/store/publicHoliday';
import TempReserveUserPicker from './TempReserve/TempReserveUserPicker.vue';

const emit = defineEmits<{
    close: [flag: boolean];
}>()
const stepTitles: Record<number, string> = {
    2: '日時設定',
    3: '予約内容確認',
}
const api = useApi()
const { ping, toast } = useDialog()
const auth = useAuthUserStore()
const theme = useTheme()
const publicHolidayStore = usePublicHolidayStore()
const targetUsers = ref<User[]>([auth.user as unknown as User]);
const searching = ref(false)
const startDate = ref( DateTime.now().startOf('week').toISODate())
const endDate = ref(DateTime.now().plus({ days: 1 }).toISODate())
const selectedRoom = ref<number | null>(null)
const selectedZoom = ref<number| null>(null)
const buffer = ref(0)
const saving = ref(false)
const step = ref(2)
const reserveView = ref<'dayTime' | 'memberTime'>('dayTime')
const reserveTableWrapper = useTemplateRef('reserveTableWrapper')
const reserveCursorPos = ref([0, 0])
const isReserveDragging = ref(false)
const hasReserveDragged = ref(false)
const title = ref('予定')
const content = ref('予定')
const storageKey = 'tempReserveOptions'
const restoringOptions = ref(false)
let searchTimer: ReturnType<typeof window.setTimeout> | null = null
let latestSearchId = 0
const bufferOptions = [
    { value: 0, label: 'なし' },
    { value: 15, label: '前後15分' },
    { value: 30, label: '前後30分' },
    { value: 45, label: '前後45分' },
    { value: 60, label: '前後60分' },
    { value: 120, label: '前後120分' }
]
const minuteOptions = [
    { value: 0, label: '0分' },
    { value: 15, label: '15分' },
    { value: 30, label: '30分' },
    { value: 45, label: '45分' }
]
type TempReserveOptions = {
    targetUsers?: User[];
    duration?: {
        hour?: number;
        minute?: number;
    };
    buffer?: number;
    selectedRoom?: number | null;
    selectedZoom?: number | null;
    reserveView?: 'dayTime' | 'memberTime';
}

const facilites = ref<FacList>({
    qualified_institution: [],
    zoom_value: [],
    qualified_care: []
})
onMounted(async() => {
    restoringOptions.value = true
    publicHolidayStore.ensureLoaded()
    restoreOptions()
    blockData.value = initBlockData()
    facilites.value = await api.get('/all_facility_items')
    restoringOptions.value = false
    search()
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

const restoreOptions = () => {
    try {
        const raw = localStorage.getItem(storageKey)
        const savedOptions = raw ? JSON.parse(raw) as TempReserveOptions : null
        if (!savedOptions) {
            return
        }

        if (Array.isArray(savedOptions.targetUsers)) {
            targetUsers.value = savedOptions.targetUsers.filter((user): user is User => {
                return Boolean(user && typeof user.id === 'number')
            })
        }

        if (typeof savedOptions.duration?.hour === 'number') {
            duration.value.hour = savedOptions.duration.hour
        }
        if (typeof savedOptions.duration?.minute === 'number') {
            duration.value.minute = savedOptions.duration.minute
        }
        if (typeof savedOptions.buffer === 'number') {
            buffer.value = savedOptions.buffer
        } else {
            const savedBufferTime = localStorage.getItem('tempReserveBuffer')
            buffer.value = savedBufferTime ? parseInt(savedBufferTime) : 0
        }
        if (typeof savedOptions.selectedRoom === 'number' || savedOptions.selectedRoom === null) {
            selectedRoom.value = savedOptions.selectedRoom
        }
        if (typeof savedOptions.selectedZoom === 'number' || savedOptions.selectedZoom === null) {
            selectedZoom.value = savedOptions.selectedZoom
        }
        if (savedOptions.reserveView === 'dayTime' || savedOptions.reserveView === 'memberTime') {
            reserveView.value = savedOptions.reserveView
        }
    } catch (error) {
        console.error('Failed to restore temp reserve options:', error)
    }
}

const saveOptions = () => {
    const options: TempReserveOptions = {
        targetUsers: targetUsers.value.filter((user): user is User => Boolean(user && typeof user.id === 'number')),
        duration: duration.value,
        buffer: buffer.value,
        selectedRoom: selectedRoom.value,
        selectedZoom: selectedZoom.value,
        reserveView: reserveView.value,
    }
    localStorage.setItem(storageKey, JSON.stringify(options))
    localStorage.setItem('tempReserveBuffer', buffer.value.toString())
}
const blockData = ref<DateSchedule>({})
const tempHighlighted = ref<string[]>([])
const duration = ref({
    hour: 1,
    minute: 0
})
const confirmDetail = useTemplateRef('confirmDetail')
onUnmounted(() => {
    if (searchTimer !== null) {
        window.clearTimeout(searchTimer)
    }
    window.removeEventListener('mousemove', onReserveMouseHold)
    window.removeEventListener('mouseup', onReserveMouseUp)
})
const bodyStyle = computed(() => {
    if(step.value == 2){
        return 'height: calc(100% - 110px); overflow:hidden;'
    }
})
const stepTitle = computed(() => stepTitles[step.value] ?? '日時設定')
const holidays = computed(() => {
    const holidays = publicHolidayStore.between(DateTime.fromISO(startDate.value).startOf('year').toJSDate(), DateTime.fromISO(startDate.value).endOf('year').toJSDate());
    return holidays as {date: Date, name: string}[]
}) 
const selectedRoomLabel = computed(() => {
    if (selectedRoom.value === null) {
        return 'なし'
    }
    return facilites.value.qualified_institution.find(f => f.value === selectedRoom.value)?.label ?? 'なし'
})
const selectedZoomLabel = computed(() => {
    if (selectedZoom.value === null) {
        return 'なし'
    }
    return facilites.value.zoom_value.find(f => f.value === selectedZoom.value)?.label ?? 'なし'
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

const hasReserveOption = computed(() => reserveResources.value.length > 0)

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
    const searchId = ++latestSearchId
    if (!hasReserveOption.value) {
        step.value = 2
        searching.value = false
        tempHighlighted.value = []
        blockData.value = initBlockData()
        return
    }
    if (!validateDate()) {
        searching.value = false
        return
    }
    if(duration.value.hour < 1 && duration.value.minute < 15){
        searching.value = false
        ping('所要時間は最低15分以上を設定してください')
        return
    }
    searching.value = true
    step.value = 2
    try {
        const data = await api.post('/calendar_temp_reserve', {
            users: targetUsers.value ?? [],
            start_date: startDate.value,
            buffer: buffer.value,
            zoom: selectedZoom.value,
            room: selectedRoom.value,
        })
        if (searchId === latestSearchId) {
            blockData.value = data
        }
    } finally {
        if (searchId === latestSearchId) {
            searching.value = false
        }
    }

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
    
    
    let convertableFacilities: { qualified_institution: number | null, zoom_value: number | null, qualified_car: number | null } = {
        qualified_institution: selectedRoom.value,
        zoom_value: selectedZoom.value,
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
            title: title.value || '予定',
            remarks: content.value || '予定',
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
    const dateText = tempHighlighted.value.map(date => {
        const dateInstance = DateTime.fromFormat(date, 'yyyy-MM-dd HH:mm')
        return `${dateInstance.toFormat('M月d日 (ccc) HH:mm')} ~ ${dateInstance.plus({ hours: duration.value.hour, minutes: duration.value.minute }).toFormat('HH:mm')}`
    }).join('\n')
    const memberText = targetUsers.value
        .filter((user): user is User => Boolean(user))
        .map(user => user.name)
        .filter(Boolean)
        .join('\n')
    const cleanedText = [
        `タイトル：${title.value || '予定'}`,
        `説明：${content.value || '予定'}`,
        'メンバー：',
        memberText,
        '日時：',
        dateText,
        `所要時間：${duration.value.hour}時間${duration.value.minute}分`,
        `施設：${selectedRoomLabel.value}`,
        `WEB会議：${selectedZoomLabel.value}`,
    ].filter(line => line.trim() !== '').join('\n')
    try {
        navigator.clipboard.writeText(cleanedText)
        toast('内容をコピーしました')
    } catch (error) {
        console.error('Failed to copy text: ', error);
        toast('コピーに失敗しました')
    }
}

const scheduleSearch = () => {
    if (searchTimer !== null) {
        window.clearTimeout(searchTimer)
    }
    searchTimer = window.setTimeout(() => {
        search()
    }, 250)
}

const targetUserIds = computed(() => targetUsers.value
    .filter((user): user is User => Boolean(user && typeof user.id === 'number'))
    .map(user => user.id)
    .sort((a, b) => a - b)
    .join(',')
)

watch([startDate, buffer, selectedRoom, selectedZoom, targetUserIds], () => {
    if (restoringOptions.value) {
        return
    }
    tempHighlighted.value = []
    saveOptions()
    scheduleSearch()
})

watch(reserveView, () => {
    if (!restoringOptions.value) {
        saveOptions()
    }
})

watch(duration, () => {
    if (restoringOptions.value) {
        return
    }
    tempHighlighted.value = []
    saveOptions()
}, { deep: true })
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
    justify-content: flex-start;
    position: sticky;
    top: 0;
    left: 0;
    z-index: 15;
    min-height: var(--reserve-toolbar-height);
    width: 100%;
    background-color: var(--background-color);
    gap: 10px;
}

.reserve-primary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    width: 100%;
    min-height: 34px;
}

.reserve-option-grid {
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 16px;
    width: 100%;
    overflow: visible;
}

.reserve-option-field {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    font-size: 11px;

    span {
        color: var(--sub-text);
        height: 14px;
        line-height: 1;
    }
}



.reserve-duration-field {
    width: 124px;
}

.reserve-option-buffer {
    width: 108px;
}

.reserve-option-facility,
.reserve-option-zoom {
    width: 128px;
}

.reserve-option-select {
    min-width: 0;
    height: 32px;
    padding: 0 24px 0 10px;
    border: solid 1px var(--calendarBorder);
    border-radius: 6px;
    background-color: var(--secondary-background);
    color: var(--primary-color);
    font-size: 12px;
    box-sizing: border-box;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.reserve-option-select {
    appearance: none;
    cursor: pointer;
    background-image: linear-gradient(45deg, transparent 50%, var(--primary-color) 50%), linear-gradient(135deg, var(--primary-color) 50%, transparent 50%);
    background-position: calc(100% - 14px) 14px, calc(100% - 9px) 14px;
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;

    &:hover,
    &:focus {
        border-color: var(--primary-color);
        background-color: var(--background-color);
        box-shadow: inset 0 0 0 1px var(--primary-color);
        outline: none !important;
    }
}

.reserve-inline-selects {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0;

    .reserve-option-select:first-child {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .reserve-option-select:last-child {
        margin-left: -1px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
}

.reserve-table-head th {
    top: var(--reserve-toolbar-height);
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

.reserve-empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 260px;
    padding: 30px;
    color: gray;
    font-size: 13px;
    text-align: center;
}

.temp-confirm-step {
    margin: 0 auto;
}

.temp-confirm-panel {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 18px;
    font-size: 13px;
    line-height: 1.6;
}

.temp-confirm-edit {
    display: grid;
    gap: 12px;
    padding: 16px;
    border: solid 1px var(--calendarBorder);
    background: var(--secondary-background);
}

.temp-confirm-field {
    display: flex;
    flex-direction: column;
    gap: 6px;

    span {
        font-size: 12px;
        color: gray;
    }

    input,
    textarea {
        border: solid 1px var(--formBorder);
        background: var(--background-color);
        color: var(--primary-color);
        font-size: 14px;
        box-sizing: border-box;
    }

    input {
        height: 38px;
        padding: 0 12px;
    }

    textarea {
        min-height: 78px;
        padding: 10px 12px;
        resize: vertical;
    }
}

.temp-confirm-summary {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
    gap: 14px;
}

.temp-confirm-section,
.temp-confirm-meta {
    padding: 14px;
    border: solid 1px var(--calendarBorder);
    background: var(--background-color);
}

.temp-confirm-section-title {
    margin-bottom: 10px;
    font-size: 12px;
    color: gray;
}

.temp-confirm-member-list,
.temp-confirm-date-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.temp-confirm-member {
    width: fit-content;
}

.temp-confirm-date-item,
.temp-confirm-meta > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.temp-confirm-date-item {
    padding-bottom: 8px;
    border-bottom: solid 1px var(--calendarBorder);

    &:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }
}

.temp-confirm-date-item span,
.temp-confirm-meta span {
    color: gray;
}

.temp-confirm-date-item strong,
.temp-confirm-meta strong {
    font-weight: 600;
}

.temp-confirm-meta {
    grid-column: 1 / -1;
    display: grid;
    gap: 8px;
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
    --reserve-toolbar-height: 116px;
    position: relative;
    height: calc(100% - 90px);
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
        --reserve-toolbar-height: 204px;
        width: calc(100% + 60px);
        margin-left: -30px;
        margin-right: -30px;
    }
    .reserve-table-toolbar {
        justify-content: flex-start;
        padding-left: 30px;
        padding-right: 30px;
    }
    .reserve-primary-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 8px;
    }
    .reserve-option-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        justify-content: stretch;
    }
    .reserve-option-users,
    .reserve-duration-field,
    .reserve-option-buffer,
    .reserve-option-facility,
    .reserve-option-zoom {
        width: auto;
    }
    .reserve-option-users {
        grid-column: 1 / -1;
    }
    .reserve-table-legend {
        flex-wrap: wrap;
        gap: 8px;
    }
    .reserve-view-switch {
        margin-left: auto;
    }
    .temp-confirm-summary {
        grid-template-columns: minmax(0, 1fr);
    }
    .temp-confirm-date-item,
    .temp-confirm-meta > div {
        align-items: flex-start;
        flex-direction: column;
        gap: 2px;
    }
}

</style>
