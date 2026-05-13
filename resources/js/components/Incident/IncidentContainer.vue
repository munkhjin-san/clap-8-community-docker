<template>
    <div class="bg-[var(--background-color)] relative min-h-full pb-5">
        <div v-if="loading" class="spinner-micro fixed top-2/4 left-2/4"></div>
        <table class="incident-table mx-4 w-[calc(100%-40px)]">
            <thead>
                <tr>
                    <td>発生日</td>
                    <td>当事者</td>
                    <td>報告者</td>
                    <td>プロジェクト</td>
                    <td>区分</td>
                    <td>ステータス</td>
                    <td>ポイント</td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
                <template v-if="incidentsData.data.length">
                    <template v-for="incident in incidentsData.data" :key="incident.id">
                        <tr
                            class="data-row cursor-pointer"
                            :class="{ expanded: selectedIncidentIds.includes(incident.id) }"
                            @click.stop="toggleIncidentDetail(incident.id)"
                        >
                            <td><div class="inner-col"><span class="mobile">発生日</span><p class="text-[gray] text-[12px]">{{ formatDate(incident.occurred_date) }}</p></div></td>
                            <td><div class="inner-col"><span class="mobile">対象者</span><UserPanel v-if="incident.caused_by_user" :user="incident.caused_by_user" with-name size="20" disable-instant/></div></td>
                            <td><div class="inner-col"><span class="mobile">報告者</span><UserPanel v-if="incident.reported_by_user" :user="incident.reported_by_user" with-name size="20" disable-instant/></div></td>
                            <td class="max-w-[180px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">プロジェクト</span><p class="truncate">{{ incident.project_record?.name || '-' }}</p></div></td>
                            <td><div class="inner-col"><span class="mobile">区分</span>{{ incident.category?.name || '-' }}</div></td>
                            <td><div class="inner-col"><span class="mobile">ステータス</span>{{ incident.status || '未設定' }}</div></td>
                            <td><div class="inner-col"><span class="mobile">ポイント</span>
                                <div :style="{backgroundColor: riskLevelColor(incident)}" class="w-6 h-6 rounded-full flex items-center justify-center text-[12px]">
                                    {{ (incident.risk_level ?? 0) * (incident.severity_level ?? 0) || '-' }}
                                </div>
                            </div></td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="row-toggle"
                                    :aria-expanded="selectedIncidentIds.includes(incident.id)"
                                    aria-label="詳細を開閉"
                                    @click.stop="toggleIncidentDetail(incident.id)"
                                >
                                    <span class="toggle-icon" :class="{ open: selectedIncidentIds.includes(incident.id) }">
                                        <Back size="10" fill="var(--primary-color)" />
                                    </span>
                                </button>
                            </td>
                        </tr>
                        <tr class="detail-row" v-if="selectedIncidentIds.includes(incident.id)">
                            <td colspan="8" class="detail-cell open">
                                <Transition name="incident-accordion">
                                    <div class="incident-accordion-body">
                                        <div class="grid grid-cols-2 gap-4 under960:grid-cols-1">
                                            <DetailBlock title="概要" :value="incident.description" />
                                            <DetailBlock title="発生場所" :value="incident.occured_location" />
                                            <DetailBlock title="原因" :value="incident.reason" />
                                            <DetailBlock title="再発防止策" :value="incident.prevention" />
                                            <DetailBlock title="指導内容" :value="incident.instruction" />
                                            <DetailBlock title="解決内容" :value="incident.resolution" />
                                            <DetailBlock title="関係者" :value="incident.related_parties" />
                                            <DetailBlock title="懲罰区分" :value="incident.punishment?.name" />
                                            <DetailBlock title="損害額" :value="formatAmount(incident.amount_of_damage)" />
                                            <DetailBlock title="重大度" :value="incident.severity_level" />
                                            <DetailBlock title="委員会メンバー" :value="incident.committee_members" />
                                            <DetailBlock title="委員会決定" :value="incident.committee_decision" />
                                            <DetailBlock title="委員会決定日" :value="formatDate(incident.committee_decision_date)" />
                                            <DetailBlock title="メモ" :value="incident.memo" />
                                            <DetailBlock title="非公開メモ" :value="incident.private_notes" />
                                        </div>
                                    </div>
                                </Transition>
                            </td>
                        </tr>
                    </template>
                </template>
                <tr v-else-if="fetchCount > 0">
                    <td colspan="9" class="!text-center">データがありません</td>
                </tr>
            </tbody>
        </table>
        <div class="mt-3">
            <PostSearchPager
                style="margin: 0;"
                :possiblePage="incidentsData.last_page"
                :activePath="incidentsData.current_page"
                @setNavi="(index) => getIncidents(incidentsData.current_page + index)"
                @setActivePage="(index) => getIncidents(index)"
            />
        </div>
        <FloatButton class="fixed" @action="">
            <template #icon>
                <AddIcon />
            </template>
        </FloatButton>
        <Teleport to="body">
            <Transition name="modalFade">
                
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { h, onMounted, ref } from 'vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { Incident } from '@/interface/incident';
import Back from '@/components/Icons/Back.vue';
import PostSearchPager from '@/components/Post/PostSearchPager.vue';
import UserPanel from '../Global/UserPanel.vue';
import FloatButton from '../Global/FloatButton.vue';
import AddIcon from '../Form/AddIcon.vue';

const DetailBlock = (props: { title: string; value?: string | number | null }) => {
    const value = props.value === null || props.value === undefined || props.value === '' ? '-' : String(props.value)
    return h('div', { class: 'text-[12px] leading-normal' }, [
        h('div', { class: 'text-[gray] mb-1' }, props.title),
        h('div', { class: 'whitespace-pre-wrap' }, value),
    ])
}

const api = useApi()
const loading = ref(false)
const fetchCount = ref(0)
const selectedIncidentIds = ref<number[]>([])
const incidentsData = ref<{
    data: Incident[]
    current_page: number
    last_page: number
    total: number
}>({
    data: [],
    current_page: 1,
    last_page: 0,
    total: 0,
})

onMounted(() => {
    getIncidents()
})

const getIncidents = async (page?: number) => {
    loading.value = true
    const pageIndex = page ?? incidentsData.value.current_page
    const response = await api.get('/get_incidents', {
        page: pageIndex,
        per_page: 50,
    })

    if (response) {
        incidentsData.value = response
        selectedIncidentIds.value = []
        fetchCount.value++
    }
    loading.value = false
}

const toggleIncidentDetail = (incidentId: number) => {
    if (selectedIncidentIds.value.includes(incidentId)) {
        selectedIncidentIds.value = selectedIncidentIds.value.filter(id => id !== incidentId)
        return
    }
    selectedIncidentIds.value = [...selectedIncidentIds.value, incidentId]
}

const formatDate = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/M/d') : date
}

const formatAmount = (amount?: number | null) => {
    if (amount === null || amount === undefined) return '-'
    return amount.toLocaleString()
}
const RISK_LEVEL_COLORS = [
    { min: 9, color: '#ff6347' }, // 出勤停止・降給・降格・諭旨退職
    { min: 6, color: '#ff826c' }, // 減給
    { min: 4, color: '#ffa191' }, // 訓戒
    { min: 2, color: '#ffc1b5' }, // 厳重注意
    { min: 1, color: '#ffe0da' }, // 注意処分
]

const riskLevelColor = (incident: Incident) => {
    const riskLevel = (incident.risk_level ?? 0) * (incident.severity_level ?? 0)
    return RISK_LEVEL_COLORS.find(l => riskLevel >= l.min)?.color ?? 'var(--bg2)'
}

</script>

<style lang="scss">
.incident-table{
    background-color: var(--background-color);
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    table-layout: fixed;
    color: var(--primary-color);
}

.incident-table td{
    padding: 10px;
    font-size: 13px;
    border-bottom: solid thin var(--calendarBorder);
    vertical-align: middle;
}

.incident-table thead td{
    padding: 16px 12px;
    font-size: 12px;
    font-weight: 700;
    background: var(--bg3);
    color: var(--primary-color);
    border-bottom: 1px solid var(--calendarBorder);
    overflow: visible;
}

.incident-table .data-row:hover{
    background: var(--bg3);
}

.incident-table .data-row.expanded{
    background: var(--selected-background);
}

.incident-table .data-row.expanded td{
    border-bottom: none;
}

.incident-table .row-toggle{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: inherit;
}

.incident-table .row-toggle:hover{
    background: var(--bg3);
}

.incident-table .toggle-icon{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transform: rotate(-90deg);
    transition: transform 0.18s ease;
}

.incident-table .toggle-icon.open{
    transform: rotate(90deg);
}

.incident-table .detail-cell{
    padding: 0;
    background: var(--selected-background);
    border-bottom: 1px solid var(--calendarBorder);
}

.incident-table .incident-accordion-body{
    padding: 12px;
}

.incident-table .incident-accordion-enter-active,
.incident-table .incident-accordion-leave-active{
    transition: max-height 0.25s ease, opacity 0.2s ease;
    overflow: hidden;
}

.incident-table .incident-accordion-enter-from,
.incident-table .incident-accordion-leave-to{
    max-height: 0;
    opacity: 0;
}

.incident-table .incident-accordion-enter-to,
.incident-table .incident-accordion-leave-from{
    max-height: 1200px;
    opacity: 1;
}

@media screen and (max-width: 959px) {
    .incident-table{
        thead{
            display: none;
        }
        tbody{
            tr{
                display: block;
                margin-bottom: 20px;
                border-bottom: solid thin var(--calendarBorder);
                border-top: solid thin var(--calendarBorder);

                td{
                    display: block;
                    border-left: solid thin var(--calendarBorder);
                    border-right: solid thin var(--calendarBorder);
                    border-bottom: none;
                    max-width: 100%;
                }
            }
            tr:first-of-type{
                margin-top: 20px;
            }
        }
    }

    .inner-col{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }

    .detail-row{
        margin-top: -21px;
    }
}
</style>
