<template>
    <div
        class="records-wrapper"
        :class="{'records-wrapper-mobile-day-grouped': showMobileDayGroups}"
        ref="wrapper"
        :style="{height: `calc(100% - ${headerHeight}px)`}"
    >
        
        <div v-if="!isLoading && !records.length" class="empty-state">
            <div class="empty-state-text">メンバーを選択してください</div>
        </div>  
        <div v-if="isLoading" class="work-loader work-records-loader">
            <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
        </div>
        <div v-else-if="showMobileDayGroups" class="mobile-day-card-list">
            <section
                v-for="group in mobileDayGroups"
                :key="group.key"
                class="mobile-day-card"
            >
                <div
                    class="mobile-day-card-date"
                    :class="mobileDayDateClass(group)"
                    :data-work-day="group.key"
                >
                    {{ group.label }}
                </div>
                <table class="mobile-day-member-table">
                    <tbody>
                        <WorkRecordRow
                            v-for="record in group.records"
                            :key="`${group.key}-${record.user_id}`"
                            :item="record"
                            :hasHeader="hasHeader"
                            @procedureStart="procedureStart"
                            @approveProjectSegment="approveProjectSegment"
                            @rejectProjectSegment="rejectProjectSegment"
                            @approveOvertimeSegment="approveOvertimeSegment"
                            @rejectOvertimeSegment="rejectOvertimeSegment"
                            @cancelOvertimeSegment="cancelOvertimeSegment"
                            :holidays="holidays"
                            :wrapper="wrapper"
                            :workGroups="workGroups"
                        />
                    </tbody>
                </table>
            </section>
            <table v-if="displayMonthAverage.length" class="mobile-total-table">
                <tbody>
                    <WorkRecordTotal
                        v-for="(data, index) in displayMonthAverage"
                        :key="data.user_id ?? index"
                        :data="data"
                        :dIndex="index"
                        :hasHeader="hasHeader"
                    />
                </tbody>
            </table>
        </div>
        <component
            v-else-if="!isLoading"
            :is="recordsTableComponent"
            :headers="headers"
            :items="displayRecords"
            :hide-no-data="true"
            :items-per-page="-1"
            hide-default-footer
            item-value="name"
            id="dt-responsive-table"
            class="p-datatable-table"
            dense
            disable-sort
        >
            <template v-slot:item="{ item }">
                <WorkRecordRow 
                    :item="item" 
                    :hasHeader="hasHeader" 
                    @procedureStart="procedureStart"
                    @approveProjectSegment="approveProjectSegment"
                    @rejectProjectSegment="rejectProjectSegment"
                    @approveOvertimeSegment="approveOvertimeSegment"
                    @rejectOvertimeSegment="rejectOvertimeSegment"
                    @cancelOvertimeSegment="cancelOvertimeSegment"
                    :holidays="holidays"
                    :wrapper="wrapper"
                    :workGroups="workGroups"
                />                
            </template>
            <template v-slot:body.append>
                <WorkRecordTotal
                    v-for="(data, index) in displayMonthAverage"
                    :key="data.user_id ?? index"
                    :data="data"
                    :dIndex="index"
                    :hasHeader="hasHeader"
                />
            </template>            
                
            
        </component>
        <Transition name="modalFade">
            <WorkProcedureButtons 
                :item="tempItem"
                :selectedSegment="tempSegment"
                :workGroups="workGroups"
                @dailyButtons="dailyButtons"
                @approveProjectSegment="approveProjectSegment"
                @rejectProjectSegment="rejectProjectSegment"
                @cancelProjectSegment="cancelProjectSegment"
                @closeModal="closeProcedureButtons"
                @reload="emit('reload')"
                v-if="tempItem"
            />
        </Transition>
        <Transition name="modalFade">
            <OverTimeRequest v-if="overTimeRequestData" :data="overTimeRequestData" :workGroups="workGroups" @close="closeOverTimeRequest"/>
        </Transition>
        
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { VDataTable } from 'vuetify/components/VDataTable'
import { DateTime } from 'luxon';
import WorkRecordRow from './WorkRecordRow.vue';
import WorkProcedureButtons from './WorkProcedureButtons.vue'
import OverTimeRequest from './OverTimeRequest.vue';
import WorkRecordTotal from './WorkRecordTotal.vue'
import { useApi } from '@/composables/api';
import { useDashboardStore } from '@/store/dashboard';
import { usePublicHolidayStore } from '@/store/publicHoliday';
import { useAuthUserStore } from '@/store/auth';
import { useResponsive } from '@/store/responsive';
    const props = defineProps([
        'monthAverage',
        'usersData',
        'selectedMonth',
        'records',
        'loading',
        'selectedYear',
        'headerHeight',
        'workGroups',
    ]) 
    const api = useApi()
    const overTimeRequestData = ref(null)
    const emit = defineEmits(['reload'])
    const tempItem = ref(null)
    const tempSegment = ref(null)
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const { getBatchDashboardData } = useDashboardStore()
    const publicHolidayStore = usePublicHolidayStore()

    onMounted(() => {
        publicHolidayStore.ensureLoaded()
    })
    const getDayClass = computed(() => {
        const date = displayDayFull.value
        const dateInstance = DateTime.fromISO(date)
        return {
            'shift-saturday': dateInstance.weekday === 6,
            'shift-sunday': dateInstance.weekday === 7,
            'shift-everyholiday' : props.holidays.find(h => DateTime.fromJSDate(h.date).hasSame(dateInstance, 'day')),
            'today' : date === DateTime.now().toISODate(),
        }
    })
    const isLoading = computed(() => Number(props.loading) === 0)
    const selectedUserCount = computed(() => props.usersData?.length ?? 0)
    const showMobileDayGroups = computed(() => responsive.mobile)
    const recordsTableComponent = computed(() => {
        return VDataTable
    })
    const recordsWithMobileDays = computed(() => {
        let currentDayShow = null
        let currentDayFull = null

        return (props.records ?? []).map((record) => {
            if (record?.day_show) currentDayShow = record.day_show
            if (record?.day_full) currentDayFull = record.day_full

            if (record?.day_show && record?.day_full) return record

            return {
                ...record,
                mobile_day_show: record?.day_show || currentDayShow,
                mobile_day_full: record?.day_full || currentDayFull,
            }
        })
    })
    const displayRecords = computed(() => {
        return recordsWithMobileDays.value
    })
    const mobileDayLabel = (date) => {
        const parsed = DateTime.fromISO(date ?? '')
        return parsed.isValid ? parsed.toFormat('M / d (ccc)') : ''
    }
    const mobileDayDateClass = (group) => {
        return {
            today: group?.key === DateTime.now().toISODate(),
            working: (group?.records ?? []).some(record => record?.time_card?.stamp_flag == 0),
        }
    }
    const mobileDayGroups = computed(() => {
        const groups = []
        let currentGroup = null

        recordsWithMobileDays.value.forEach((record) => {
            const key = record?.mobile_day_full || record?.day_full || record?.mobile_day_show || record?.day_show
            if (!key) return

            if (!currentGroup || currentGroup.key !== key) {
                currentGroup = {
                    key,
                    label: mobileDayLabel(record?.mobile_day_show || record?.day_show || key),
                    records: [],
                }
                groups.push(currentGroup)
            }

            currentGroup.records.push(record)
        })

        return groups
    })
    const holidays = computed(() => {
        const holidays = publicHolidayStore.between(new Date(props.selectedYear + '-01-01'), new Date(props.selectedYear + '-12-31'));
        return holidays
    })
    const wrapper = ref(null)
    const pendingActionKeys = ref(new Set())
    const hasPendingAction = (key) => pendingActionKeys.value.has(key)
    const addPendingAction = (key) => {
        pendingActionKeys.value = new Set(pendingActionKeys.value).add(key)
    }
    const removePendingAction = (key) => {
        const nextKeys = new Set(pendingActionKeys.value)
        nextKeys.delete(key)
        pendingActionKeys.value = nextKeys
    }
    const withPendingAction = async(key, action) => {
        if (!key) return action()
        if (hasPendingAction(key)) return

        addPendingAction(key)
        try {
            return await action()
        } finally {
            removePendingAction(key)
        }
    }
    const timecardActionKey = (item) => {
        if (!item?.user_id || !item?.day_full) return ''
        return `timecard:${item.user_id}:${item.day_full}`
    }
    const projectSegmentActionKey = (segment) => {
        return segment?.id ? `project-segment:${segment.id}` : ''
    }
    const overtimeSegmentActionKey = (request, segmentIndex, segment) => {
        if (!request?.id) return ''
        return `overtime-segment:${request.id}:${segmentIndex}:${segment?.project_id ?? ''}`
    }
    const procedureStart = (item, segment = null) => {
        tempItem.value = item
        tempSegment.value = segment
    }
    const closeProcedureButtons = () => {
        tempItem.value = null
        tempSegment.value = null
    }
    const dailyButtons = async(value, item) => {
        const targets = [dailyApproval, timeCardRemand, dailyCancel, overtTimeRequest]
        const target = targets[value]
        if (!target) return

        await withPendingAction(timecardActionKey(item), async() => {
            closeProcedureButtons()
            await Promise.resolve(target(item))
            await getBatchDashboardData(['timesheet'])
        })
    }
    const overtTimeRequest = (item) => {
        overTimeRequestData.value = item
    }

    const includeRegistered = computed(() => {
        return !!(props.usersData ?? []).find(ob => ob.position_id === 15)
    })
    const orderedMonthAverage = computed(() => {
        const userOrder = new Map((props.usersData ?? []).map((user, index) => [Number(user.id), index]))
        return [...(props.monthAverage ?? [])].sort((a, b) => {
            const aOrder = userOrder.has(Number(a?.user_id)) ? userOrder.get(Number(a?.user_id)) : Number.MAX_SAFE_INTEGER
            const bOrder = userOrder.has(Number(b?.user_id)) ? userOrder.get(Number(b?.user_id)) : Number.MAX_SAFE_INTEGER
            if (aOrder !== bOrder) return aOrder - bOrder

            return Number(a?.user_id ?? 0) - Number(b?.user_id ?? 0)
        })
    })
    const displayMonthAverage = computed(() => {
        return orderedMonthAverage.value
    })
    const hasHeader = (title) => {
        return headers.value.findIndex(element => element.title == title) !== -1
    }
    const headers = computed(() => {
        let headersArray = [
            { title: '日付'},
            { title: 'メンバー'},
            // { title: 'コンディション'},
            { title: '予定' },
            { title: '出勤'},
            { title: '退勤'},
            { title: '労働時間'},
            { title: '時間外'},
            { title: '休憩時間'},
            { title: 'プロジェクト'},
            { title: '承認者' },
            { title: '諸手当'},
            { title: 'インシデント'},
            { title: 'コメント'},
            { title: '経費'},
            { title: '実績'},
            { title: '車両使用'},
            { title: 'マイカー使用'},
            { title: 'ステータス'},
            { title: '操作'},
        ];
        if(includeRegistered.value){
            // const index = headersArray.findIndex(element => element.title == 'ステータス')
            // headersArray.splice(index, 0, {title: 'インセンティブ'})
            const t_index = headersArray.findIndex(element => element.title == '時間外')
            headersArray.splice(t_index, 0, {title: '研修時間'})
        }      

        return headersArray;
    })
    const timeCardRemand = async(item) => {
        
        const params = {
            user_id: item.user_id,
            record_day: item.day_full
        }

        await api.post('/remand_time_card', params, {
            ask: `${item.day_full}日報を差し戻します。`,
            toast: '差戻ししました。'
        })
        emit('reload')

    }
    
    const dailyApproval = async(item) => {
        const params = {
            user_id: item.user_id,
            record_day: item.day_full,
            overTimeRequest: item?.shift?.overtime_request,
        };

        await api.post('/approve_time_card', params, {
            toast: '承認しました。',
        })
        emit('reload')

    }
    const dailyCancel = async(item) => {
        const params = {
            user_id: item.user_id,
            record_day: item.day_full
        };
        await api.post('/cancel_time_card', params, {
            toast: '承認取消しました。',
        })
        emit('reload')
    }

    const approveProjectSegment = async(segment, item) => {
        await withPendingAction(projectSegmentActionKey(segment), async() => {
            await api.post('/approve_timecard_project_segment', { id: segment.id }, {
                toast: 'プロジェクト時間を承認しました。',
            })
            await getBatchDashboardData(['timesheet'])
            emit('reload')
        })
    }

    const rejectProjectSegment = async(segment, item) => {
        await withPendingAction(projectSegmentActionKey(segment), async() => {
            await api.post('/reject_timecard_project_segment', { id: segment.id }, {
                ask: `${item.day_full} ${segment.project?.name ?? 'プロジェクト'}の時間を差し戻します。\n日報も差戻中になり、本人が編集できます。`,
                toast: 'プロジェクト時間を差し戻しました。',
            })
            await getBatchDashboardData(['timesheet'])
            emit('reload')
        })
    }

    const cancelProjectSegment = async(segment, item) => {
        await withPendingAction(projectSegmentActionKey(segment), async() => {
            await api.post('/cancel_timecard_project_segment', { id: segment.id }, {
                toast: 'プロジェクト時間の承認を取り消しました。',
            })
            await getBatchDashboardData(['timesheet'])
            emit('reload')
        })
    }

    const respondOvertimeSegment = async(segment, segmentIndex, item, status) => {
        const overtimeRequest = item?.shift?.overtime_request
        if (!overtimeRequest?.id) return

        const toastMessage = {
            0: '残業申請を差し戻しました。',
            1: '残業申請の承認を取り消しました。',
            2: '残業申請を承認しました。',
        }[status] ?? '残業申請を更新しました。'

        await withPendingAction(overtimeSegmentActionKey(overtimeRequest, segmentIndex, segment), async() => {
            await api.patch('/request_overtime', {
                id: overtimeRequest.id,
                status,
                segment_index: segmentIndex,
                project_id: segment?.project_id,
                approved_by: auth.activeUser?.id,
            }, {
                toast: toastMessage,
            })
            await getBatchDashboardData(['timesheet'])
            emit('reload')
        })
    }

    const approveOvertimeSegment = (segment, segmentIndex, item) => {
        return respondOvertimeSegment(segment, segmentIndex, item, 2)
    }

    const rejectOvertimeSegment = (segment, segmentIndex, item) => {
        return respondOvertimeSegment(segment, segmentIndex, item, 0)
    }

    const cancelOvertimeSegment = (segment, segmentIndex, item) => {
        return respondOvertimeSegment(segment, segmentIndex, item, 1)
    }
  
    const closeOverTimeRequest = (val) => {
        overTimeRequestData.value = null
        if(val){
            emit('reload')
        }
    }
</script>
<style lang="scss">
::-webkit-scrollbar {
    height: 4px;
}
.w-hover-button{
    display: flex;
    justify-content: center;
}
.absolute-div{
    position:absolute; 
    color: var(--primary-color); 
    display: flex; 
    justify-content: center; 
    align-items: center;
    width: 100%;
    height: 100%;
}
.workButton-wrapper{
    display: flex;
    justify-content: center;
    gap: 5px;
    align-items: center;
}
.text-wrap {
    white-space: break-spaces;
    max-height: 40px;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}
.v-table{
    height: 100%;
    background: var(--bg2) !important;
    table{
        font-size: 12px;
        background: var(--background-color);
        border-collapse: separate;
        border-spacing: 0;
        color: var(--primary-color);
        table-layout: auto;
        min-width: 100%;
        width: max-content;
        thead{
            position: sticky;
            top: 0;
            line-height: 40px;
            text-align: center;
            width: 90px;
            background-color: #606060;
            font-size: 12px;
            color: #fff;
            z-index: 10;
            vertical-align: middle;
            white-space: nowrap;
            height: 40px;
            th{
                border-right: 1px solid var(--calendarBorder);
                border-left: none;
                border-top: none;
                text-align: center;
                font-weight: 400;
                padding: 0 !important;
                height: 40px !important;
                overflow: hidden;
                text-overflow: ellipsis;

                .v-data-table-header__content{
                    justify-content: center;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
            }
            th:nth-child(1){
                position: sticky;
                left: 0;
                z-index: 12;
                min-width: 90px;
                width: 90px;
                background-color: #606060;
            }
            th:nth-child(2){
                position: sticky;
                left: 90px;
                z-index: 12;
                min-width: 0;
                width: 1%;
                max-width: none;
                background-color: #606060;
                white-space: nowrap;
            }
            th:nth-last-child(2){
                min-width: 112px;
                width: 112px;
            }
            
        }
        tbody{
            .w-row{
                td{
                    border-bottom: 1px solid var(--calendarBorder);
                    border-right: 1px solid var(--calendarBorder);
                    vertical-align: middle;
                    width: auto;
                    text-align: center;
                    height: 40px !important;
                    box-sizing: border-box !important;
                    overflow: hidden;
                    padding: 0 4px !important;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                .work-date-cell{
                    position: sticky;
                    left: 0;
                    z-index: 5;
                    background: var(--background-color);
                    min-width: 90px;
                    width: 90px;
                }
                .work-member-cell{
                    position: sticky;
                    left: 90px;
                    z-index: 5;
                    background: var(--background-color);
                    min-width: 0;
                    width: 1%;
                    max-width: none;
                    overflow: visible !important;
                    text-align: left;
                    padding-left: 10px !important;
                    padding-right: 10px !important;
                    white-space: nowrap !important;
                }
                .work-date-cell.today{
                    background: #606060;
                    color: #fff;
                }
                .work-date-cell.working,
                .work-date-cell.today.working{
                    background-color: #c5af72;
                    color: #fff;
                }
                .project-segment-detail-column,
                .report-status-cell,
                .report-action-cell{
                    overflow: visible !important;
                }
            }
            .w-row:not(#bottomTotal):hover{
                background: var(--background-color);
                border-color: var(--calendarBorder);
            }
            .w-row:not(#bottomTotal):hover .work-date-cell,
            .w-row:not(#bottomTotal):hover .work-member-cell{
                background: var(--background-color);
            }
            .w-row:not(#bottomTotal):hover .work-date-cell.today{
                background: #606060;
                color: #fff;
            }
            .w-row:not(#bottomTotal):hover .work-date-cell.working,
            .w-row:not(#bottomTotal):hover .work-date-cell.today.working{
                background-color: #c5af72;
                color: #fff;
            }

            .compact-blank-row .work-date-cell.today{
                color: #fff;
            }
            .compact-blank-row .project-segment-cell{
                min-height: 32px;
            }
            
            
        }
    }   
}



.v-table .v-table__wrapper > table > tbody {
    tr.w-row > td{
        border-bottom: 1px solid var(--calendarBorder);
    }
}
.last-row > td{
    border-bottom: thin solid var(--calendarBorder) !important;
}

@media (max-width: 959px) {
    .comment-wrap {
        -webkit-line-clamp: 1;
        line-clamp: 1;
        display: -webkit-box;
        -webkit-box-orient: vertical;
    }
    .text-wrap {
        white-space: nowrap;
    }
    .mb-space{
        margin: 10px 0;
    }
    .w-hover-button{
        justify-content: flex-start;
    }
    .center-mobile{
        justify-content: center;
        margin-top: 10px;
    }
    
    .workButton-wrapper{
        justify-content: flex-start;
    }
    .mt-10 {
        margin-bottom: 5px;
    }
    .last-row{
        margin-bottom: 25px !important;
    }
    .td-first{
        box-sizing: border-box !important;
        width: 100%;
        padding: 0;
        text-align: center;
        margin-bottom: 0;
    }
    .today .td-first{
        padding: 0;
    }
    .v-table{
        table{            
            font-size: 14px;        
            width: 100% !important;
            background: var(--bg2);
            .memberName{
                // font-weight: 600;
                white-space: nowrap;
            }
             thead {
                display: none !important; /* Hide the table header on mobile */
            }
            tfoot {
                display: none !important; /* Hide the table header on mobile */
            }
            tbody {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                min-height: auto !important; 
            

                /* Styles for individual table rows (cards) */
                .w-row {
                    border: 1px solid var(--calendarBorder);
                    border-radius: 4px;
                    display: block !important;
                    background: var(--background-color);
                    height: auto !important;
                    margin: 0 12px 10px;
                    overflow: hidden;
                    box-sizing: border-box !important;
                    font-size: 13px;
                    padding-bottom: 0;
                    position: relative;
                    .date-cell{
                        padding: 5px 20px !important;
                        text-align: center !important;
                    }
                }
                .w-row.project-chip-open-row {
                    overflow: visible !important;
                    z-index: 15;
                }
                .w-row.project-chip-open-row .mobile-project-segment-card-cell {
                    overflow: visible !important;
                }
                .w-row.project-segment-extra-row {
                    display: none !important;
                }
                
                /* Styles for table cells within rows */
                .w-row td {
                    text-align: left !important;
                    border: none !important;
                    border-bottom: none !important;
                    display: block !important;
                    height: fit-content !important;
                    box-sizing: border-box !important;
                    line-height: 2;
                    min-width: 0 !important;
                    max-width: 100% !important;
                    padding: 0 20px !important;
                    width: 100% !important;
                }
                .w-row td:nth-child(1),
                .w-row td:nth-child(2),
                .w-row td:nth-child(3),
                .w-row td:nth-child(4),
                .w-row td:nth-child(5),
                .w-row td:nth-child(6),
                .w-row td:last-child{
                    min-width: 0 !important;
                    max-width: 100% !important;
                    width: 100% !important;
                }
                .w-row td:nth-child(6){
                    width: 100% !important;
                }
                .w-row:not(#bottomTotal) td:not(.mobile-project-segment-card-cell) {
                    display: none !important;
                }
                .w-row:not(#bottomTotal) .mobile-project-segment-card-cell {
                    display: block !important;
                }
                .w-row .work-date-cell,
                .w-row .work-member-cell{
                    position: static;
                    left: auto;
                    background: var(--background-color);
                    min-width: 0 !important;
                    max-width: 100% !important;
                    width: 100% !important;
                }
                .w-row .work-date-cell{
                    align-items: center;
                    background: var(--bg3);
                    display: flex;
                    font-size: 12px;
                    min-height: 36px;
                    padding: 8px 14px !important;
                    text-align: center !important;
                }
                .w-row .work-member-cell{
                    font-size: 13px;
                    min-height: 34px;
                    padding: 8px 14px 4px !important;
                    max-width: none;
                    width: 100%;
                }
                .w-row .project-segment-cell {
                    min-width: 0 !important;
                    max-width: 100% !important;
                    padding-top: 6px !important;
                    width: 100% !important;
                }
                .w-row .project-segment-cell.project-segment-cell-empty {
                    display: none !important;
                    min-height: 0 !important;
                    padding: 0 !important;
                }
                .w-row .work-date-cell.working,
                .w-row .work-date-cell.today.working{
                    background-color: #c5af72;
                    color: #fff;
                }
                .w-row .work-total-mobile-cell {
                    padding: 0 !important;
                }
                .w-row:hover{
                    background: var(--background-color);
                    border-color: var(--calendarBorder);
                }
                .w-row:not(#bottomTotal):hover {
                    background: var(--background-color) !important;
                    border-color: var(--calendarBorder);
                }
                .w-row:hover .mobile-project-segment-card,
                .w-row:hover .mobile-project-segment-box,
                .w-row:not(#bottomTotal):hover .mobile-project-segment-card,
                .w-row:not(#bottomTotal):hover .mobile-project-segment-box {
                    background: var(--background-color) !important;
                    border-color: var(--calendarBorder);
                }
                .w-row:hover > td::after,
                .w-row:not(#bottomTotal):hover > td::after {
                    background: transparent !important;
                    opacity: 0 !important;
                }
                .command-cell{
                    display: flex !important;
                    width: 100% !important;
                    justify-content: center;
                }

            }
        }
    }
}

</style>
<style scoped>
.records-wrapper {
    position: relative;
    display: flex;
    flex-direction: column;
}

.records-wrapper :deep(.v-table) {
    flex: 1 1 auto;
    min-height: 0;
    height: auto !important;
}

.tc{
    border-bottom: 1px solid var(--calendarBorder);
    vertical-align: middle;
    text-align: center;
    white-space: nowrap;
    box-sizing: border-box !important;
    min-height: 40px;
    height: 40px;
}
.tc:last-of-type{
    border-bottom: none;
}
.empty-state {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: var(--primary-color);
    opacity: 0.45;
    pointer-events: none;
    z-index: 0;
}
.empty-state-text { font-size: 16px; letter-spacing: 0.02em; }

.work-records-loader {
    inset: 0 !important;
    height: 100% !important;
    min-height: 260px;
    z-index: 20;
}

.mobile-day-card-list,
.mobile-total-table {
    display: none;
}

@media (max-width: 959px) {
    .records-wrapper-mobile-day-grouped {
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .mobile-day-card-list {
        display: flex;
        flex: 0 0 auto;
        flex-direction: column;
        gap: 10px;
        padding: 0 0 10px;
    }

    .mobile-day-card {
        background: var(--background-color);
        border: 1px solid var(--calendarBorder);
        margin: 0 12px;
        overflow: hidden;
    }

    .mobile-day-card:has(.project-chip-open-row) {
        overflow: visible;
        position: relative;
        z-index: 15;
    }

    .mobile-day-card-date {
        align-items: center;
        background: var(--bg3);
        color: var(--primary-color);
        display: flex;
        font-size: 12px;
        justify-content: center;
        min-height: 36px;
        padding: 8px 14px;
    }
    .mobile-day-card-date.today{
        background: #606060;
        color: #fff;
    }
    .mobile-day-card-date.working,
    .mobile-day-card-date.today.working{
        background-color: #c5af72;
        color: #fff;
    }
    .mobile-day-member-table,
    .mobile-day-member-table tbody,
    .mobile-total-table,
    .mobile-total-table tbody {
        border-collapse: collapse;
        display: block;
        width: 100%;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row) {
        background: var(--background-color);
        border: 0;
        border-radius: 0;
        display: block !important;
        font-size: 13px;
        margin: 0;
        overflow: visible;
        padding-bottom: 0;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row.project-chip-open-row) {
        overflow: visible !important;
        position: relative;
        z-index: 15;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row.project-chip-open-row .mobile-project-segment-card-cell) {
        overflow: visible !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:hover) {
        background: var(--background-color);
        border-color: var(--calendarBorder);
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:not(#bottomTotal):hover) {
        background: var(--background-color) !important;
        border-color: var(--calendarBorder);
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:hover .mobile-project-segment-card),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:hover .mobile-project-segment-box),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:not(#bottomTotal):hover .mobile-project-segment-card),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:not(#bottomTotal):hover .mobile-project-segment-box) {
        background: var(--background-color) !important;
        border-color: var(--calendarBorder);
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:hover > td::after),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:not(#bottomTotal):hover > td::after) {
        background: transparent !important;
        opacity: 0 !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row.project-segment-extra-row) {
        display: none !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td) {
        border: none !important;
        box-sizing: border-box !important;
        display: block !important;
        font-size: 13px;
        height: fit-content !important;
        line-height: 2;
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 0 14px !important;
        text-align: left !important;
        white-space: normal;
        width: 100% !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:not(#bottomTotal) td:not(.mobile-project-segment-card-cell)) {
        display: none !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:not(#bottomTotal) .mobile-project-segment-card-cell) {
        display: block !important;
        padding: 0 !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-project-segment-date) {
        display: none;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td:nth-child(1)),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td:nth-child(2)),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td:nth-child(3)),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td:nth-child(4)),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td:nth-child(5)),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td:nth-child(6)),
    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td:last-child) {
        max-width: 100% !important;
        min-width: 0 !important;
        width: 100% !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row:not(:last-child)) {
        border-bottom: 1px solid var(--calendarBorder);
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td.work-date-cell) {
        display: none !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td.work-member-cell) {
        background: var(--background-color);
        display: none !important;
        font-size: 13px;
        min-height: auto;
        padding: 10px 14px 2px !important;
        max-width: none;
        width: 100%;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td.work-member-cell + td) {
        padding-top: 0 !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td.project-segment-cell) {
        padding-top: 6px !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-day-member-table .w-row td.project-segment-cell.project-segment-cell-empty) {
        display: none !important;
        min-height: 0 !important;
        padding: 0 !important;
    }

    .mobile-total-table {
        display: block;
        padding-bottom: 0;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-total-table .w-row) {
        display: block !important;
        margin: 0 12px 10px;
        overflow: hidden;
        padding-bottom: 0 !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-total-table .w-row:last-child) {
        margin-bottom: 0 !important;
    }

    .records-wrapper-mobile-day-grouped :deep(.mobile-total-table .w-row td) {
        border: 0 !important;
        display: block !important;
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
}

</style>
