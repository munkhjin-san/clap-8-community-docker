<template>
    <Modal size="medium" :loader="processing" @close="emit('closeModal')">
        <template #title>
            <p style="font-size: 18px;">{{ shiftYear }}年{{ shiftMonth }}月の勤怠予定</p>
        </template>
        <template #content>
        <div class="work-shifts-content text-[var(--primary-color)]">
            <Transition name="modalFade">
                <div class="work-loader" v-if="processing">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div> 
            </Transition>
            <div class="work-shifts-scroll bg-[var(--background-color)]">
                <div class="recordFormTitle" style="z-index: 26;">
                    <p style="font-size: 18px;">{{ shiftYear }}年{{ shiftMonth }}月の勤怠予定</p>
                    <div @click="emit('closeModal')" class="cursor-pointer flex items-center" style="margin: auto 0 auto auto;">
                        <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div class="shift-title" style="margin-bottom: 20px;">
                    <div class="sub-tab-container gap-5">
                        <div @click="checkLeave = 0" style="padding: 10px 0;" :class="['sub-tab-item', { 'selected-sub-tab': checkLeave == 0}]">予定入力</div>
                        <div v-if="usersData[0].position_id !== 15" @click="checkLeave = 1" style="padding: 10px 0;" :class="['sub-tab-item', { 'selected-sub-tab': checkLeave == 1}]">計画有給確認</div>
                    </div>
                    <div v-if="selectedShiftType == 3 && checkLeave == 0" style="margin-left:auto;display:flex;gap: 10px;">
                        <MonthPickerNew 
                            v-model:month="shiftMonth"
                            v-model:year="shiftYear"
                            :max="DateTime.fromISO(tempStartEnd).toISODate()"
                            :min="tempStartDate"
                            right='0' 
                            @setDate="setDate"
                        />
                    </div>
                </div>
                
                <div v-if="checkLeave == 0">
                    <div class="shift-wrapper">
                        
                        <!-- <div class="shift-types">
                            <div class="shift-type_name" v-for="(shift_type, index) in groupedLeaves.main" :key="index">
                                <input type="radio" :disabled="shift_type.id === 3 && notSubmitted || shift_type.id === 16 && odaCheck" :id="shift_type.id" v-model="selectedShiftType" :value="shift_type.id">
                                <label :class="{'planned-date' : notSubmitted && shift_type.id === 3 || shift_type.id === 16 && odaCheck}" :for="shift_type.id">{{ shift_type.name }}</label>
                            </div>
                        </div> -->
                        <div class="my-4 flex gap-4 items-center justify-between flex-wrap">
                            <div class="flex gap-3 items-center flex-wrap">
                                <select v-model="selectedShiftType" id="shift_type_selector" class="custom-a-input">
                                    <optgroup label="勤務">
                                        <option :value="type.id" v-for="type in groupedLeaves.main" :key="'m-'+type.id">{{ type.name }}</option>
                                    </optgroup>
                                    <optgroup label="休日">
                                        <option :value="type.id" :disabled="type.id === 3 && notSubmitted" v-for="type in groupedLeaves.holiday" :key="'h-'+type.id">{{ type.name }}</option>
                                    </optgroup>
                                    <optgroup label="年休">
                                        <option :value="type.id" v-for="type in groupedLeaves.planned" :key="'p-'+type.id">{{ type.name }}</option>
                                    </optgroup>
                                    <optgroup label="時間休日">
                                        <option :value="type.id" :disabled="type.id === 3 && notSubmitted" v-for="type in groupedLeaves.hourly" :key="'h-'+type.id">{{ type.name }}</option>
                                    </optgroup>
                                    <optgroup label="その他">
                                        <option :value="type.id" :disabled="type.id === 16 && odaCheck" v-for="type in groupedLeaves.other" :key="'m-'+type.id">{{ type.name }}</option>
                                    </optgroup>
                                </select>
                                <div v-if="projectSelectionVisible" class="shift-project-selector">
                                    <select v-model="selectedDepartmentId" class="custom-a-input">
                                        <option value="" disabled>プロジェクトを選択</option>
                                        <option v-for="project in shiftProjectOptions" :key="project.id" :value="project.id">
                                            {{ project.name }}
                                        </option>
                                    </select>
                                </div>
                                <select id="planned_year_selector" v-if="selectedShiftType === 3" v-model="plannedLeaveTargetYear" class="custom-a-input">
                                    <option :value="year" v-for="year in yearOptions">{{ year }}年度</option>
                                </select>
                                <p class="text-sm" v-if="selectedShiftType == 3">期間: {{ DateTime.fromISO(tempStartDate).isValid ? DateTime.fromISO(tempStartDate).toLocaleString() : '' }}~{{ DateTime.fromISO(tempStartEnd).isValid ? DateTime.fromISO(tempStartEnd).toLocaleString() : '' }}</p>
                            </div>
                            
                            <div class="shift-holiday">
                                <div v-if="selectedShiftType !== 3 && selectedShiftType !== 27">年間休日取得数（現時点）: <strong>{{ displayTotalHolidays }}</strong></div>
                                <p v-if="zan_nissu && selectedShiftType !== 3 && selectedShiftType !== 27" class="paid-leave-balance">
                                    有給残日数:
                                    <strong :class="{ negative: projectedPaidLeaveMinutes < 0 }">{{ formatLeaveBalance(projectedPaidLeaveMinutes) }}</strong>
                                   
                                </p>
                                <p v-if="selectedShiftType == 3">計画有給: <strong>{{ remainingDays }}</strong>日</p>
                                <p v-if="selectedShiftType !== 3 && selectedShiftType !== 27">休日数: <strong>{{ holidayCount }}</strong>日／所定休日数: <strong>{{ shouldHoliday }}</strong>日</p>
                                <p v-if="selectedShiftType == 27">特別休暇: <strong>{{ remainingSpecialHoliday }}</strong>日</p>
                            </div>
                        </div>
                        
                        
                        <div class="shift-calendar">
                            <div class="shift-header">
                                <div class="shift-weekdays" v-for="wk in weekHeaderArray">
                                    <div @click="selectByWeek(wk.id)" :class="{'shift-saturday' : wk.id == 6, 'shift-sunday' : wk.id == 7}">
                                        {{ wk.name }}
                                    </div>
                                </div>
                            </div>
                            <div class="shift-inner">
                                <div class="shift-month" v-for="(week, index) in dataLoad" :key="index">                
                                    <div class="shift-week" v-for="(day, index) in week" :key="index">
                                        <div @click="selectShift(day, [], index + 1)" :class="{ 'hidden-date': !day.day_short, 'showed-date': day.day_short, 'planned-date': selectedShift(day) && selectedShift(day).id == 3}">
                                            <div>
                                                <div class="shift-day" :class="{'shift-saturday' : index == 5, 'shift-sunday' : index == 6, 'shift-everyholiday' : day.day_holiday}">
                                                {{ day.day_short }}
                                                </div>
                                                <div class="shift-select">{{ selectedShift(day) && selectedShift(day).name }}</div>
                                                <div class="shift-project-name" v-if="selectedShiftProjectName(day)" :title="selectedShiftProjectName(day)">
                                                    {{ selectedShiftProjectName(day) }}
                                                </div>
                                                <div style="font-size:10px;color:tomato" v-if="!selectedShift(day) && required">
                                                    必須です
                                                </div>
                                            </div>
                                        </div>   
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div ref="shiftTime">
                        <div class="shift-title">
                            <p>基本就業時間の入力</p>
                        </div>
                        <div class="shift-workTime">
                            <div>
                                <p v-if="!responsive.mobile">始業時間</p>
                            </div>
                            <div>
                                <ShortInput 
                                    customClass="date" 
                                    type="time" 
                                    v-model="startTime" 
                                    name="start_time" 
                                    rules="required" 
                                    ref="startTimeRef"
                                    :customStyle="{fontSize: '13px'}"
                                />
                            </div>
                            <div>
                                <p>{{responsive.mobile ? '～' : '終業時間'}}</p>
                            </div>
                            <div>
                                <ShortInput 
                                customClass="date" 
                                type="time" 
                                v-model="endTime" 
                                name="end_time" 
                                rules="required" 
                                ref="endTimeRef"
                                :customStyle="{fontSize: '13px'}"
                                />
                            </div>
                        </div>
                        <div v-if="usersData[0].position_id !== 15 && selectedShiftType !== 3">
                            <section class="border mt-[10px] border-[var(--calendarBorder)] border-solid p-[10px]">
                                <p class="text-sm font-medium leading-normal">
                                    法定上の所定労働時間
                                </p>
                                <p class="mt-2 text-sm font-semibold tracking-tight">
                                    {{ workTimeData?.days }}日／{{ workTimeData?.work_minutes / 60 }}時間
                                </p>
                                <p class="text-sm font-medium leading-normal mt-3">
                                    あなたの申請内容
                                </p>

                                <dl class="mt-3 space-y-2">
                                    <div class="flex items-baseline justify-between gap-6">
                                        <dt class="text-sm">勤務日数</dt>
                                        <dd class="text-sm tabular-nums">{{ summary.workDays }}日</dd>
                                    </div>

                                    <div class="flex items-baseline justify-between gap-6">
                                        <dt class="text-sm">実働時間</dt>
                                        <dd class="text-sm tabular-nums">{{ fmtHours(summary.workMinutes) }}時間</dd>
                                    </div>

                                    <div class="flex items-baseline justify-between gap-6">
                                        <dt class="text-sm">有給（目安）</dt>
                                        <dd class="text-sm tabular-nums">{{ summary.paidLeaveDays }}日</dd>
                                    </div>

                                    <div class="flex items-baseline justify-between gap-6">
                                        <dt class="text-sm">有給相当時間</dt>
                                        <dd class="text-sm tabular-nums">{{ fmtHours(summary.paidLeaveMinutes) }}時間</dd>
                                    </div>

                                    <!-- Total -->
                                    <div class="pt-3 mt-3 border-t [border-top-style:solid] border-[var(--calendarBorder)] flex items-baseline justify-between gap-6">
                                        <dt class="text-sm font-semibold">合計計上時間</dt>
                                        <dd class="text-sm font-semibold tabular-nums">{{ fmtHours(summary.accountedMinutes) }}時間</dd>
                                    </div>
                                </dl>
                            </section>
                        </div>

                        <LoaderButton style="margin-top:30px;" @triggered="shiftAdd" :loading="loading" :content="attendanceFlag ? '勤怠確定後は編集できません' : '申請'"/>
                        
                    </div>
                </div>
                <div v-else-if="checkLeave == 1">
                    <WorkPaidLeave 
                        :user-id="usersCheckArray[0]"
                    />
                </div>
            </div>
        </div>
        </template>
    </Modal>
        
</template>
<script setup>
import LoaderButton from '../Global/LoaderButton.vue'
import Modal from '../Global/Modal.vue'
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useTheme } from '@/store/theme';
import { useResponsive } from '@/store/responsive';
import ShortInput from '../Form/ShortInput.vue';
import { getShiftData, getWorkGroup } from '../../utils/workApi';
import WorkPaidLeave from './WorkPaidLeave.vue';
import { useBadgeStore } from '@/store/badge';
import { DateTime } from 'luxon';
import MonthPickerNew from '../Global/MonthPickerNew.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import YearPicker from '../Global/YearPicker.vue';
import { useRoute } from 'vue-router';
import { useDashboardStore } from '@/store/dashboard';
import { usePublicHolidayStore } from '@/store/publicHoliday';
    const responsive = useResponsive()
    const theme = useTheme()
    const emit = defineEmits(['closeModal', 'reload', 'viewPaidLeave'])
    const props = defineProps([
        'selectedMonth', 
        'selectedYear', 
        'usersData',
        'startDate',
        'notSubmitted',
        'usersCheckArray',
        'chosenId',
        'attendanceFlag',
        'workGroups'
    ])
    const shiftTime = ref(null)
    const selectedShiftType = ref(0)
    const loading = ref(false)
    const selectedShifts = ref([])
    const holidayCount = ref(0)
    const workdayCount = ref(0)
    const startTime = ref('')
    const endTime = ref('')
    const shiftMonth = ref(props.selectedMonth)
    const shiftYear = ref(props.selectedYear)
    const required = ref(false)
    const startTimeRef = ref(null)
    const endTimeRef = ref(null)
    const checkLeave = ref(0)
    const remainingDays = ref(0)
    const workTemp = ref(null)
    const shiftTypes = ref([])
    const shiftRecords = ref([])
    const localWorkGroups = ref([])
    const selectedDepartmentId = ref('')
    const odaCheck = ref([])
    const badge = useBadgeStore()
    const processing = ref(true)
    const remainingSpecialHoliday = ref(0)
    const weekHeaderArray = [
        { id: 1, name: '月' },
        { id: 2, name: '火' },
        { id: 3, name: '水' },
        { id: 4, name: '木' },
        { id: 5, name: '金' },
        { id: 6, name: '土' },
        { id: 7, name: '日' }
    ]
    const totalHolidayInYearByMinutes = ref(0)
    const userWorkMinutesPerDay = ref(0)
    const api = useApi()
    const zan_nissu = ref(null)
    const workTimeData = ref(null)
    const { ping } = useDialog()
    const plannedLeaveTargetYear = ref(DateTime.now().year)
    const yearOptions = [DateTime.now().minus({year: 1}).year, DateTime.now().year, DateTime.now().plus({year: 1}).year]
    const { getBatchDashboardData } = useDashboardStore()
    const publicHolidayStore = usePublicHolidayStore()
    const route = useRoute()
    onMounted(async() => {
        publicHolidayStore.ensureLoaded()
        propsCheck()
        getRemainingDays()
        await fetchWorkGroups()
        await fetchShiftData()
        isShiftRecord()
        await nextTick()
        if(route.query.action === 'request_planned_leave_change'){
            checkLeave.value = 1
        }
    })
    const shiftDateInstance = computed(() => DateTime.fromObject({ year: shiftYear.value, month: shiftMonth.value }))
    const yearlyHolidays = computed(() => {
        return publicHolidayStore.between(new Date(shiftYear.value + '-01-01'), new Date(shiftYear.value + '-12-31'))
    })
    const dataLoad = computed(() => {
        const firstDay = shiftDateInstance.value.startOf('week')
        const lastDay = shiftDateInstance.value.endOf("month").endOf("week");
        const holidays = yearlyHolidays.value
        
        const calendar = [];
        let i = firstDay
        while (i <= lastDay) {
            const weekIndex = calendar.length - 1;
            if (weekIndex < 0 || calendar[weekIndex].length === 7) {
                calendar.push([]);
            }
            if (i.month !== shiftDateInstance.value.month) {
                calendar[calendar.length - 1].push({});
            } else {
                const holiday = holidays.find(h => DateTime.fromJSDate(h.date).hasSame(i, 'day'))
                calendar[calendar.length - 1].push({ 
                    "day_short" : i.day.toString(),
                    "day_full" : i.toISODate(),
                    "day_holiday" : holiday ? holiday.name : null,
                    "weekday" : i.weekday,
                    "formated_date" : `${i.toFormat('M / d (ccc)')}`,
                });
            }
            i = i.plus({days: 1})
        }
        return calendar
    })
    const categorize = (name) =>  {
        if (name.includes('年休') || name === '計画有給') return 'planned'
        if (name === '休日') return 'holiday'
        if (name.includes('時間休日')) return 'hourly'
        if (name.includes('勤務')) return 'main'
        return 'other'
    }

    const groupedLeaves = computed(() => {
        const g = { main: [], holiday: [], planned: [], hourly: [], other: [] }
        for (const s of shiftTypes.value) g[categorize(s.name)].push(s)
        return g
    })
    const allWorkGroups = computed(() => {
        return Array.isArray(props.workGroups) && props.workGroups.length ? props.workGroups : localWorkGroups.value
    })
    const targetUserId = computed(() => Number(props.usersData?.[0]?.id ?? props.chosenId ?? props.usersCheckArray?.[0] ?? 0))
    const shiftProjectOptions = computed(() => {
        const userId = targetUserId.value
        const groups = Array.isArray(allWorkGroups.value) ? allWorkGroups.value : []

        if (!userId) return groups

        return groups.filter(group => {
            const members = Array.isArray(group.members) ? group.members : []
            const managers = Array.isArray(group.manager) ? group.manager : []
            const directors = Array.isArray(group.director) ? group.director : group.director ? [group.director] : []

            return [...members, ...managers, ...directors].some(user => Number(user?.id) === userId)
                || Number(group.director_id) === userId
        })
    })
    const ensureDefaultDepartment = () => {
        if (selectedDepartmentId.value) return

        const existingProject = shiftRecords.value.find(shift => shift.department_id)?.department_id
        selectedDepartmentId.value = existingProject ?? shiftProjectOptions.value[0]?.id ?? ''
    }
    const fetchWorkGroups = async() => {
        if (Array.isArray(props.workGroups) && props.workGroups.length) {
            return
        }

        localWorkGroups.value = await getWorkGroup()
    }
    const getWorkTemp = async() => {
        const user_code = props.usersData[0].user_code
        const user_id = props.usersData[0].id
        if (!user_code) return
        const data = await api.get('/get_work_temp', {
            planned_year: plannedLeaveTargetYear.value, 
            user_code: user_code,
            user_id: user_id
        })
        if (data) {
            remainingDays.value = data.remaining_days
            workTemp.value = data.workTemp
        }
    }
    const fetchShiftData = async() => {
        const work_group = props.chosenId ? [props.chosenId] : props.usersCheckArray
        const tempdate = props.startDate ? DateTime.fromISO(props.startDate).toISODate() : ''
       
        const shiftData = await getShiftData(shiftDateInstance.value.toISODate(), work_group)
        shiftTypes.value = shiftData.shift_type
        shiftRecords.value = shiftData.shift_record
        odaCheck.value = shiftData.odaCheck
        totalHolidayInYearByMinutes.value = shiftData.total_holidays
        userWorkMinutesPerDay.value = shiftData.user_work_minutes_per_day
        workTimeData.value = shiftData.work_time_data
        remainingSpecialHoliday.value = shiftData.remaining_special_holiday
        ensureDefaultDepartment()
        processing.value = false
    }
    const getRemainingDays = async() => {
        const user_code = props.usersData[0].user_code
        if (!user_code) return
        const data = await api.get('/get_remaining_days', {user_code: user_code})
        if (data) {
            zan_nissu.value = data
        }
    }
    const displayTotalHolidays = computed(() => {
        let totalMinutes = totalHolidayInYearByMinutes.value;

        if(selectedShifts.value && selectedShifts.value.length){
            const selectedHolidays = selectedShifts.value.filter(shift => [0, 18, 19, 20, 21, 22, 23, 24, 25, 26].includes(shift.type));
            selectedHolidays.forEach(element => {
                const fullDay = element.type === 0 || element.type === 18;
                const halfDay = element.type === 19;
                const minutesValue = shiftTypes.value.find(type => type.id === element.type)?.value || 0;
                if (fullDay) {
                    totalMinutes += userWorkMinutesPerDay.value;
                } else if (halfDay) {
                    totalMinutes += userWorkMinutesPerDay.value / 2;
                }else {
                    totalMinutes += minutesValue;
                }
            });
        }
            

        const minutesPerDay = userWorkMinutesPerDay.value;

        if (!totalMinutes || !minutesPerDay) return '0日';

        const totalDays = Math.floor(totalMinutes / minutesPerDay);
        const remainingMinutes = totalMinutes % minutesPerDay;
        const remainingHours = Math.floor(remainingMinutes / 60);

        let result = `${totalDays}日`;
        if (remainingHours > 0) result += `${remainingHours}時間`;

        return result;
    });
    const propsCheck = () => {
        if(props.startDate){
            const newDate = props.startDate;
            shiftYear.value = DateTime.fromISO(newDate).year;
            shiftMonth.value = DateTime.fromISO(newDate).month;
            plannedLeaveTargetYear.value = DateTime.fromISO(newDate).year;
            selectedShiftType.value = 3;
        }
    }
    const tempStartDate = computed(() => {
        return workTemp.value ? workTemp.value.date : props.startDate
    })
    const tempStartEnd = computed(() => {
        const start = DateTime.fromISO(tempStartDate.value)
        return start.plus({ years: 1 }).minus({ days: 1 })
    })
    const between = computed(() => {
        return (shiftDateInstance.value > DateTime.fromISO(tempStartDate.value) || shiftDateInstance.value < DateTime.fromISO(tempStartEnd.value)) && selectedShiftType.value == 3
    }) 
    const calcBreakMinutes = (workMinutes) => {
        if (workMinutes > 6 * 60) return 60
        if (workMinutes >= 3 * 60) return 30
        return 0
    }

    const minutesPerDay = computed(() => {
        if (!startTime.value || !endTime.value) return 0

        const [sh, sm] = startTime.value.split(':').map(Number)
        const [eh, em] = endTime.value.split(':').map(Number)

        let start = sh * 60 + sm
        let end = eh * 60 + em

        // overnight shift対応（必要なら）
        if (end < start) end += 24 * 60

        const gross = Math.max(0, end - start)
        const breakMin = calcBreakMinutes(gross)
        return Math.max(0, gross - breakMin)
    })
    const shiftTypeMap = computed(() => {
        const map = new Map()

        const list = Array.isArray(shiftTypes.value)
            ? shiftTypes.value
            : Array.isArray(shiftTypes.value?.data)
            ? shiftTypes.value.data
            : []

        list.forEach((t) => {
            map.set(Number(t.id), t)
        })

        return map
    })
    const isHolidayType = (typeId) => typeId === 0 || typeId === 18
    const isFullDayNonWorkType = (typeId) => {
        const type = shiftTypeMap.value.get(Number(typeId))
        if (!type) return false

        return Number(type.id) === 0 || Number(type.id) === 18 || Number(type.full_day) === 2
    }
    const shiftTypeHasWorkTime = (typeId) => {
        const type = shiftTypeMap.value.get(Number(typeId))
        if (!type || isFullDayNonWorkType(typeId)) return false
        if (type.value === null || type.value === undefined || type.value === '') return true

        const leaveMinutes = Number(type.value) || 0
        return Math.max(0, minutesPerDay.value - leaveMinutes) > 0
    }
    const projectSelectionVisible = computed(() => shiftTypeHasWorkTime(selectedShiftType.value))
    const paidLeaveMinutesPerDay = computed(() => {
        const ledgerMinutesPerDay = Number(zan_nissu.value?.minutes_per_day)
        if (ledgerMinutesPerDay > 0) return ledgerMinutesPerDay

        const workMinutesPerDay = Number(userWorkMinutesPerDay.value)
        if (workMinutesPerDay > 0) return workMinutesPerDay

        return 480
    })
    const basePaidLeaveMinutes = computed(() => {
        const minutes = Number(zan_nissu.value?.minutes)
        if (Number.isFinite(minutes) && minutes >= 0) return minutes

        return Math.round((Number(zan_nissu.value?.days) || 0) * paidLeaveMinutesPerDay.value)
    })
    const shiftRecordTypeId = (record) => Number(record?.shift_type?.id ?? record?.shift_type ?? record?.type ?? 0)
    const isPaidLeaveType = (typeId) => {
        const type = shiftTypeMap.value.get(Number(typeId))
        const name = String(type?.name || '')

        return Number(typeId) === 3
            || name.includes('有給')
            || name.includes('年休')
            || name.includes('時間休日')
    }
    const paidLeaveMinutesForType = (typeId) => {
        if (Number(typeId) === 3) {
            return paidLeaveMinutesPerDay.value
        }

        const type = shiftTypeMap.value.get(Number(typeId))
        if (!type || !isPaidLeaveType(typeId)) return 0

        if (Number(type.full_day) === 2) {
            return paidLeaveMinutesPerDay.value
        }

        if (Number(type.full_day) === 1) {
            return Math.round(paidLeaveMinutesPerDay.value / 2)
        }

        return Math.max(0, Number(type.value) || 0)
    }
    const originalPaidLeaveMinutes = computed(() => {
        return (shiftRecords.value || []).reduce((sum, record) => {
            return sum + paidLeaveMinutesForType(shiftRecordTypeId(record))
        }, 0)
    })
    const selectedPaidLeaveMinutes = computed(() => {
        return (selectedShifts.value || []).reduce((sum, shift) => {
            return sum + paidLeaveMinutesForType(Number(shift.type))
        }, 0)
    })
    const paidLeaveDeltaMinutes = computed(() => selectedPaidLeaveMinutes.value - originalPaidLeaveMinutes.value)
    const projectedPaidLeaveMinutes = computed(() => basePaidLeaveMinutes.value - paidLeaveDeltaMinutes.value)
    const formatDays = (value) => {
        return new Intl.NumberFormat('ja-JP', { maximumFractionDigits: 2 }).format(Number(value) || 0)
    }
    const formatLeaveBalance = (minutes) => {
        const perDay = Math.max(1, Number(paidLeaveMinutesPerDay.value) || 480)
        const rawTotal = Math.round(Number(minutes) || 0)
        const total = Math.abs(rawTotal)
        const days = Math.floor(total / perDay)
        const rest = total % perDay
        const hours = Math.floor(rest / 60)
        const mins = rest % 60

        let label = `${days}日`
        if (hours > 0) label += `${hours}時間`
        if (mins > 0) label += `${mins}分`

        return rawTotal < 0 ? `-${label}` : label
    }
    const signedLeaveMinutes = (minutes) => {
        const number = Math.round(Number(minutes) || 0)
        if (number === 0) return formatLeaveBalance(0)

        return `${number > 0 ? '+' : '-'}${formatLeaveBalance(Math.abs(number))}`
    }
    const normalizedShiftArray = () => {
        return selectedShifts.value.map(shift => {
            const type = Number(shift.type)
            const hasWorkTime = shiftTypeHasWorkTime(type)

            return {
                ...shift,
                date: shift.date,
                type,
                status_flag: shift.status_flag ?? 2,
                planned_year: shift.planned_year ?? (type === 3 ? plannedLeaveTargetYear.value : shiftYear.value),
                department_id: hasWorkTime ? (shift.department_id || selectedDepartmentId.value || null) : null,
            }
        })
    }
    const shiftsForSummary = computed(() => selectedShiftType.value === 3 ? selectedShifts.value : normalizedShiftArray())
    const summary = computed(() => {
        const shifts = shiftsForSummary.value || []

        let holidayDays = 0
        let workDays = 0

        let workMinutes = 0
        let paidLeaveDays = 0
        let paidLeaveMinutes = 0

        for (const s of shifts) {
            const typeId = s.type
            const type = shiftTypeMap.value.get(typeId)

            // 休日（0/18）は労働も計上もしない（会社ルールで変えるならここ）
            if (isHolidayType(typeId)) {
                holidayDays++
                continue
            }

            // 勤務（valueがNULLの想定） => 基本労働時間
            // 休暇（480/240/60など） => その分を有給相当として計上
            const typeValue = type?.value // minutes or null

            // “勤務”かどうかはマスタで判定できるのが理想だけど、
            // 今は value が null なら勤務扱いにしてしまう（休憩みたいなのが混ざるなら別判定が必要）
            if (typeValue == null) {
                workDays++
                workMinutes += minutesPerDay.value
            } else {
                // 有給/休暇系
                const leaveMinutes = Number(typeValue) || 0
                paidLeaveMinutes += leaveMinutes

                // 全日休暇（480分）以外は残り時間を労働時間として計上
                const remainingWorkMinutes = Math.max(0, minutesPerDay.value - leaveMinutes)
                if (remainingWorkMinutes > 0) {
                    workDays++
                    workMinutes += remainingWorkMinutes
                }

                if (type?.full_day === 2) paidLeaveDays += 1
                else if (type?.full_day === 1) paidLeaveDays += 0.5
                // full_day 0 は日数カウントしない（時間休）
            }
        }

        const accountedMinutes = workMinutes + paidLeaveMinutes

        return {
            holidayDays,
            workDays,
            workMinutes,
            paidLeaveDays,
            paidLeaveMinutes,
            accountedMinutes,
        }
    })

    const fmtHours = (mins) => (mins / 60).toFixed(1)

    const isShiftRecord = () => {
        selectedShifts.value = []
        if(shiftRecords.value && shiftRecords.value.length){
            startTime.value = shiftRecords.value[0] ? shiftRecords.value[0].start_time : ''
            endTime.value = shiftRecords.value[0] ? shiftRecords.value[0].end_time : ''
            ensureDefaultDepartment()
            for(let shift of shiftRecords.value){
                let date = {
                    day_full : shift.shift_day,
                }
                selectShift(date, shift, false)
            } 
        }else{
            holidayCount.value = 0
            startTime.value = '09:00'
            endTime.value = '18:00'
        }
    }
    const selectShift = (date, record, val) => {
        const status_flag = record ? record?.status_flag : 2
        const type_id = record && record.shift_type ? record?.shift_type?.id : selectedShiftType.value
        let existingShift = selectedShifts.value.find(shift => shift.date === date.day_full)
        if (existingShift) {
            if (val) {
                if(existingShift.type == 3 && existingShift.status_flag == 1){
                    ping('計画有給を変えることができません。')   
                    return
                }
                const nextDepartmentId = shiftTypeHasWorkTime(type_id) ? (selectedDepartmentId.value || null) : null
                if (
                    existingShift.type === type_id
                    && nextDepartmentId
                    && Number(existingShift.department_id) !== Number(nextDepartmentId)
                ) {
                    existingShift.department_id = nextDepartmentId
                    return
                }
                selectedShifts.value = selectedShifts.value.filter(shift => shift.date !== date.day_full);
                if(type_id == 3 && existingShift.type == 3){
                    remainingDays.value++
                }
                if(type_id == 27 && existingShift.type == 27){
                    remainingSpecialHoliday.value++
                }
            }
        } else {
            selectedShifts.value.push({
                date: date.day_full,
                type: type_id,
                status_flag: status_flag,
                planned_year: type_id === 3 ? plannedLeaveTargetYear.value : shiftYear.value,
                department_id: shiftTypeHasWorkTime(type_id) ? (record?.department_id ?? selectedDepartmentId.value ?? null) : null,
            });
            if(val && type_id == 3){
                remainingDays.value--
            }
            if (val && type_id == 27){
                remainingSpecialHoliday.value--
            }
            if (type_id == 27 && remainingSpecialHoliday.value < 0){
                remainingSpecialHoliday.value = 0
                selectedShifts.value.pop()
                ping('特別休暇日数が足りない。これ以上選択できません。')
            }
            if(type_id == 3){
                const previousPeriodStart = DateTime.fromISO(tempStartDate.value).minus({ years: 1 });
                const previousPeriodEnd = DateTime.fromISO(tempStartDate.value);
                if (!tempStartDate.value) return
                if (
                    record?.planned_year !== 2023 &&
                    !(
                        ((DateTime.fromISO(date.day_full) >= previousPeriodStart && 
                          DateTime.fromISO(date.day_full) <= previousPeriodEnd) && 
                         record?.planned_year === DateTime.now().year - 1) || // Valid in previous year's planning
                        (DateTime.fromISO(date.day_full) >= DateTime.fromISO(tempStartDate.value) && 
                         DateTime.fromISO(date.day_full) <= tempStartEnd.value) // Valid in current planning period
                    )
                )
                {
                    selectedShifts.value.pop()
                    remainingDays.value++
                    const content = DateTime.fromISO(date.day_full).toFormat('yyyy/MM/dd') + 'は計画期間外です。<br>設定可能な期間は' + '<strong>' + DateTime.fromISO(tempStartDate.value).toFormat('yyyy/MM/dd') + '</strong>' + '-' + '<strong>' + DateTime.fromISO(tempStartEnd.value).toFormat('yyyy/MM/dd') + '</strong>'

                    ping(content)  
                    return
                }
                if(remainingDays.value < 0){
                    remainingDays.value = 0
                    selectedShifts.value.pop()
                    ping('計画有給日数が足りない又は当年の計画有給は付与されていません。')  
                    return
                }
            }
        }
        holidayCount.value = selectedShifts.value.filter(shift => (shift.type === 0 || shift.type === 18)).length
        workdayCount.value = selectedShifts.value.filter(shift => (shift.type !== 0 && shift.type !== 18)).length
    }
    const selectedShift = (date) => {
        const record = selectedShifts.value.find(shift => shift.date == date.day_full)
        if(record){
            const shiftType = shiftTypes.value.find(type => type.id == record.type)
            return shiftType
        }
    }
    const selectedShiftProjectName = (date) => {
        const record = selectedShifts.value.find(shift => shift.date == date.day_full)
        if (!record?.department_id) return ''

        return shiftProjectOptions.value.find(project => Number(project.id) === Number(record.department_id))?.name ?? ''
    }
    const shouldHoliday = computed(() => {
        const month = shiftMonth.value
        const lastDay = shiftDateInstance.value.daysInMonth
        if (month == 12 || month == 1) {
            return (props.usersData[0].position_id <= 11) ? ((month == 12) ? 10 : 12) : 9;
        } else {
            return (lastDay >= 29) ? 9 : 8;
        }
    })
    const shiftAdd = async() => {
        if(props.attendanceFlag) return
        const month = shiftMonth.value
        const lastDay = shiftDateInstance.value.daysInMonth
        var holidayNum;
        required.value = false
        if (month == 12 || month == 1) {
            holidayNum = (props.usersData[0].position_id <= 11) ? ((month == 12) ? 10 : 12) : 9;
        } else {
            holidayNum = (lastDay >= 29) ? 9 : 8;
        }
        if(endTime.value < startTime.value){
            ping('終業時間は始業時間より先にすることができません。')
            return
        }
        if (selectedShiftType.value !== 3 && selectedShiftType.value !== 27 && projectedPaidLeaveMinutes.value < 0) {
            ping('有休残数が不足しています。選択後の残数を確認してください。')
            return
        }
        if (props.usersData[0].position_id !== 15) {
            if(lastDay > selectedShifts.value.length && selectedShiftType.value !== 3){
                required.value = true
                return
            }
        }
        if(props.usersData[0].work_type == 1 && props.usersData[0].position_id < 13 && props.usersData[0].position_id > 4 && !between.value){
            const legalHolidays = selectedShifts.value.filter(shift => shift.type === 18);
            if(legalHolidays.length !== 4){
                ping('法定休日は4日必要です。')
                return
            }
        }
        if(holidayCount.value >= holidayNum || props.usersData[0].position_id === 15 || between.value){
            const targets = [startTimeRef.value, endTimeRef.value]
            let result = true
            for(const target of targets){            
                const val = await target?.validate() || {valid: false}
                result = result * val.valid
            }
            if(!result) return
            if (loading.value) return

            const shiftArray = normalizedShiftArray()
            const projectMissing = shiftArray.some(shift => shiftTypeHasWorkTime(shift.type) && !shift.department_id)
            if (projectMissing) {
                ping('プロジェクトを選択してください。')
                return
            }

            const params = {
                shiftTimeStart : startTime.value,
                shiftEndStart : endTime.value,
                shift_array : shiftArray,
                year: shiftYear.value,
                month: shiftMonth.value,
                planned_year: tempStartDate.value ? tempStartDate.value.substring(0, 4) : props.selectedYear,
                userId: props.usersData[0].id,
                position_id: props.usersData[0].position_id,
                yearMonth: shiftDateInstance.value.toISODate(),
            }
  
            await api.post('/add_shift', params, {
                toast: '申請しました。',
                loadingRef: loading,
            });
            emit('closeModal')
            emit('reload')
            getBatchDashboardData(['timesheet'])
            
        }else{
            ping('今月の休日数は' + holidayNum + '日以上取得が必要です。')
        }
        
    }
    const setDate = async(date) => {
        // selectedShifts.value = []
        shiftYear.value = date.year
        shiftMonth.value = date.month
        await fetchShiftData()
        isShiftRecord()
    }
    const selectByWeek = (num) => {
        let selectedDays = []
        for (let week of dataLoad.value) {
            for (let day of week) {
                if (day.weekday === num) {
                    selectedDays.push(day);
                }
            }
        }
        let count = 0
        for(let date of selectedDays){
            let existingShift = selectedShifts.value.find(shift => shift.date === date.day_full);
            if (!existingShift) {
                selectShift(date, [], 1);
            }else{
                count++
                if(count === selectedDays.length){
                    for(let date of selectedDays){
                        selectShift(date, [], 1)
                    }
                }
            }
        }
    }
    const setPlannedLeaveTargetLeave = (payload) => {
        plannedLeaveTargetYear.value = payload.year
    }
    watch(
        [selectedShiftType, plannedLeaveTargetYear],
        ([type, year]) => {
            if (type === 3 && year) {
                getWorkTemp();
            }
            ensureDefaultDepartment()
        },
        { flush: "post" }
    );
    watch(shiftProjectOptions, () => {
        ensureDefaultDepartment()
    })
    watch(tempStartDate, async (newVal) => {
        if (newVal) {
            if (DateTime.now().year !== DateTime.fromISO(newVal).year) {
                shiftYear.value = DateTime.fromISO(newVal).year;
                shiftMonth.value = DateTime.fromISO(newVal).month;
            }
            await fetchShiftData()
            isShiftRecord()
        }
    })
</script>
<style scoped>
.work-shifts-content {
    height: 100%;
    min-height: 0;
    overflow: hidden;
}
.work-shifts-scroll {
    height: 100%;
    padding-bottom: 30px;
}
.work-shifts-content > .work-loader,
.work-shifts-scroll > .recordFormTitle {
    display: none;
}
.shift-project-selector {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: min(360px, 100%);
}
.shift-project-selector select {
    min-width: 240px;
    max-width: 360px;
    box-sizing: border-box !important;
}
.paid-leave-balance {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 4px;
}
.paid-leave-balance strong.negative {
    color: #b42318;
}
.paid-leave-balance-detail {
    color: var(--third-color);
    font-size: 11px;
}
.work-shifts-scroll .shift-month {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    height: auto;
    min-height: 90px;
}
.work-shifts-scroll .shift-header {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}
.work-shifts-scroll .shift-weekdays {
    min-width: 0;
}
.work-shifts-scroll .shift-week {
    display: flex;
    align-items: stretch;
    min-height: 90px;
    min-width: 0;
}
.work-shifts-scroll .showed-date,
.work-shifts-scroll .hidden-date {
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    min-width: 0;
    min-height: 0;
    height: auto;
    overflow: hidden;
    padding: 6px 4px;
}
.work-shifts-scroll .showed-date > div,
.work-shifts-scroll .hidden-date > div {
    display: grid;
    grid-template-rows: 18px 18px 14px;
    align-items: center;
    min-height: 50px;
    min-width: 0;
    width: 100%;
}
.work-shifts-scroll .shift-day {
    height: 18px;
    line-height: 18px;
    margin-bottom: 4px;
    padding-top: 0;
}
.work-shifts-scroll .shift-select {
    height: 16px;
    line-height: 16px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
}
.shift-project-name {
    margin-top: 3px;
    color: var(--primary-color);
    font-size: 10px;
    height: 12px;
    line-height: 12px;
    opacity: 0.72;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
@media (max-width: 640px) {
    .work-shifts-scroll .shift-month,
    .work-shifts-scroll .shift-week {
        min-height: 66px;
    }
    .work-shifts-scroll .showed-date,
    .work-shifts-scroll .hidden-date {
        padding: 5px 1px;
    }
    .work-shifts-scroll .showed-date > div,
    .work-shifts-scroll .hidden-date > div {
        grid-template-rows: 16px 22px 11px;
        min-height: 52px;
    }
    .work-shifts-scroll .shift-day {
        font-size: 11px;
        font-weight: 400;
        height: 16px;
        line-height: 16px;
        margin-bottom: 0;
        width: 100%;
    }
    .work-shifts-scroll .shift-select {
        align-items: center;
        display: flex;
        font-size: 10px;
        height: 22px;
        justify-content: center;
        line-height: 1.15;
        white-space: normal;
    }
    .shift-project-name {
        display: block;
        width: 100%;
        height: 11px;
        margin-top: 0;
        color: var(--primary-color);
        font-size: 8px;
        line-height: 11px;
        opacity: 0.62;
    }
    .shift-project-selector {
        width: 100%;
        align-items: flex-start;
        flex-direction: column;
    }
    .shift-project-selector select {
        width: 100%;
        max-width: none;
    }
}
</style>
