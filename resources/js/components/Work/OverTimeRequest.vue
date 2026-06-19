<template>
    <Transition name="modalFade">
        <div class="overlay" @mousedown="emit('close', false)">
            <div class="chatCreate scrollable" @mousedown.stop>     
                <div class="recordFormTitle" style="display:flex">
                    <p><strong>{{day}}</strong>残業申請</p>
                    
                    <div class="m-close-button" style="position:unset; margin:auto 0 auto auto;width:auto;gap:30px">
                        <button v-if="target" @click="deleteRequest" class="workRecords-button">削除</button>
                        <svg @click="emit('close', false)"  version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>

                <div v-if="target" style="padding-bottom: 20px;">
                    ステータス：<strong>{{ statuses[target.status] }}</strong>
                </div>
                <div class="si-box" style="background: var(--bg3);padding: 15px;margin-top: 0;">
                    <p class="report-header">申請前の就業時間</p>
                    <div class="report-time">
                        <div class="timePreview">{{ timeParser(data?.shift?.start_time) }}</div>
                        <div class="between-line"> ～ </div>
                        <div class="timePreview">{{ timeParser(data?.shift?.end_time) }}</div>
                    </div>
                </div>
                <div class="si-box overtime-project-box">
                    <div class="overtime-project-head">
                        <p class="report-header">残業プロジェクト</p>
                        <strong>{{ overtimeTotalLabel }}</strong>
                    </div>
                    <div class="overtime-project-list" @click.stop="handleProjectListClick">
                        <div
                            v-for="(segment, index) in projectSegments"
                            :key="segment.key"
                            class="overtime-project-row"
                        >
                            <div class="overtime-project-row-main">
                                <select class="optionPicker overtime-project-select" v-model="segment.project_id" @change="normalizeProjectSelection(segment)">
                                    <option :value="null" disabled>プロジェクトを選択</option>
                                    <option
                                        v-for="group in workGroupOptions"
                                        :key="group.id"
                                        :value="group.id"
                                        :disabled="isProjectOptionDisabled(group.id, segment)"
                                    >{{ group.name }}</option>
                                </select>
                                <div class="overtime-minute-input">
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                        v-model.number="segment.minutes"
                                    >
                                    <span>分</span>
                                </div>
                                <button
                                    v-if="projectSegments.length > 1"
                                    type="button"
                                    class="project-time-action-button overtime-project-remove"
                                    data-overtime-action="remove"
                                    :data-index="index"
                                    title="削除"
                                    aria-label="プロジェクトを削除"
                                >−</button>
                            </div>
                            <textarea
                                class="overtime-project-content"
                                v-model="segment.content"
                                maxlength="2000"
                                placeholder="作業内容"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="project-time-action-button overtime-add-project"
                        title="プロジェクトを追加"
                        aria-label="プロジェクトを追加"
                        @click.stop.prevent="addProjectSegment"
                    >
                        ＋
                    </button>
                    <p class="overtime-project-note">残業時間は1分単位で、プロジェクトごとに1行で申請します。</p>
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="send" :loading="loading" content="申請する"/>
                </div>  
            </div>
        </div>
    </Transition>
</template>
<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue'
import { useAuthUserStore } from '@/store/auth';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
const statuses = ['差戻中', '申請中', '承認済']
const auth = useAuthUserStore()
const props = defineProps({
    data: { type: Object, default: null },
    workGroups: { type: Array, default: () => [] },
})
const emit = defineEmits(['close'])
const loading = ref(false)
const projectSegments = ref([])
const api = useApi()
const { ask, ping } = useDialog()
const fetchShiftDataTable = inject('fetchShiftDataTable')
onMounted(() => {
    projectSegments.value = initialProjectSegments()
})

const target = computed(() => {
    return props.data?.shift?.overtime_request
})
const workGroupOptions = computed(() => {
    const groups = Array.isArray(props.workGroups) ? props.workGroups : []
    const options = groups
        .filter(group => group?.id && group?.name)
        .map(group => ({ id: Number(group.id), name: group.name }))

    const shiftProject = props.data?.shift?.department
    if (shiftProject?.id && !options.some(group => Number(group.id) === Number(shiftProject.id))) {
        options.unshift({ id: Number(shiftProject.id), name: shiftProject.name })
    }

    return options
})
const defaultProjectId = computed(() => {
    return props.data?.shift?.department_id
        ?? props.data?.shift?.department?.id
        ?? workGroupOptions.value[0]?.id
        ?? null
})
const makeProjectSegment = (segment = {}) => ({
    key: `overtime-project-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    project_id: segment.project_id ? Number(segment.project_id) : defaultProjectId.value,
    minutes: Number(segment.minutes || 0),
    content: String(segment.content ?? '').trim(),
})
const combineContent = (contents) => {
    return [...new Set(contents.map(content => String(content ?? '').trim()).filter(Boolean))].join("\n")
}
const combineProjectSegments = (segments) => {
    const byProject = new Map()

    segments.forEach(segment => {
        const projectId = Number(segment?.project_id || 0)
        const minutes = Math.max(0, Number(segment?.minutes || 0))
        if (!projectId || !minutes) return

        const existing = byProject.get(projectId)
        if (existing) {
            existing.minutes += minutes
            existing.content = combineContent([existing.content, segment.content])
            return
        }

        byProject.set(projectId, {
            project_id: projectId,
            minutes,
            content: String(segment?.content ?? '').trim(),
        })
    })

    return [...byProject.values()]
}
const targetProjectSegments = computed(() => {
    const segments = Array.isArray(target.value?.project_segments) ? target.value.project_segments : []
    return combineProjectSegments(segments
        .map(segment => ({
            project_id: Number(segment?.project_id ?? 0),
            minutes: Number(segment?.minutes ?? 0),
            content: String(segment?.content ?? '').trim(),
        }))
        .filter(segment => segment.project_id > 0 && segment.minutes > 0))
})
const initialProjectSegments = () => {
    if (targetProjectSegments.value.length) {
        return targetProjectSegments.value.map(makeProjectSegment)
    }

    return [makeProjectSegment({
        project_id: defaultProjectId.value,
        minutes: Number(target.value?.minutes || 0),
        content: target.value?.content ?? '',
    })]
}
const normalizedProjectSegments = computed(() => {
    return projectSegments.value
        .map(segment => ({
            project_id: Number(segment.project_id || 0),
            minutes: Math.max(0, Number(segment.minutes || 0)),
            content: String(segment.content ?? '').trim(),
        }))
})
const minuteProjectSegments = computed(() => {
    return normalizedProjectSegments.value
        .filter(segment => segment.project_id > 0 && segment.minutes > 0)
})
const cleanProjectSegments = computed(() => {
    return combineProjectSegments(minuteProjectSegments.value).filter(segment => segment.content)
})
const totalMinutes = computed(() => minuteProjectSegments.value.reduce((sum, segment) => sum + segment.minutes, 0))
const overtimeTotalLabel = computed(() => {
    const hours = Math.floor(totalMinutes.value / 60)
    const minutes = totalMinutes.value % 60
    if (hours && minutes) return `合計 ${hours}時間${minutes}分`
    if (hours) return `合計 ${hours}時間`
    return `合計 ${minutes}分`
})
const addProjectSegment = () => {
    const projectId = firstAvailableProjectId()
    if (!projectId) {
        ping('追加できるプロジェクトがありません。')
        return
    }

    projectSegments.value.push(makeProjectSegment({
        project_id: projectId,
        minutes: 0,
    }))
}
const selectedProjectIds = (currentSegment = null) => {
    return new Set(projectSegments.value
        .filter(segment => segment !== currentSegment)
        .map(segment => Number(segment.project_id || 0))
        .filter(projectId => projectId > 0)
    )
}
const firstAvailableProjectId = () => {
    const usedProjectIds = selectedProjectIds()
    return workGroupOptions.value.find(group => !usedProjectIds.has(Number(group.id)))?.id ?? null
}
const isProjectOptionDisabled = (projectId, currentSegment) => {
    return selectedProjectIds(currentSegment).has(Number(projectId))
}
const normalizeProjectSelection = (segment) => {
    const projectId = Number(segment?.project_id || 0)
    if (!projectId) return

    const duplicateSegment = projectSegments.value.find(item => item !== segment && Number(item.project_id || 0) === projectId)
    if (!duplicateSegment) return

    duplicateSegment.minutes = Number(duplicateSegment.minutes || 0) + Number(segment.minutes || 0)
    duplicateSegment.content = combineContent([duplicateSegment.content, segment.content])

    const index = projectSegments.value.indexOf(segment)
    if (index >= 0 && projectSegments.value.length > 1) {
        projectSegments.value.splice(index, 1)
    }
    ping('同じプロジェクトは1行にまとめました。')
}
const removeProjectSegment = (index) => {
    if (projectSegments.value.length <= 1) return
    projectSegments.value.splice(index, 1)
}
const handleProjectListClick = (event) => {
    const button = event.target?.closest?.('[data-overtime-action="remove"]')
    if (!button) return

    removeProjectSegment(Number(button.dataset.index))
}
const overtimeContentSummary = computed(() => {
    return cleanProjectSegments.value
        .map(segment => segment.content)
        .filter(Boolean)
        .join("\n")
})

const day = computed(() => {
    return props.data?.shift ? DateTime.fromSQL(props.data.shift.shift_day).toFormat('M月d日') : ''
})
const timeParser = (time) => {
    if(!time) return 
    const shift = props.data?.shift
    const combined = DateTime.fromSQL(`${shift.shift_day} ${time}`)
    return combined.toFormat('M月d日 HH:mm')
}


const send = async() => {
    const confirmed = target.value && target.value.status == 2 ? await ask('「承認済み」の残業時間を編集すると、ステータスが「申請中」に戻ります。よろしいでしょうか。') : {value: true}
    if (!confirmed.value) return
    if(!totalMinutes.value){
        ping('残業時間は必須です')
        return
    }
    if(cleanProjectSegments.value.length !== projectSegments.value.length){
        ping('残業プロジェクト、時間、作業内容を入力してください。')
        return
    }
    const params = {
        record_id: props.data.shift.id,
        overtime_content: overtimeContentSummary.value,
        minutes: totalMinutes.value,
        project_segments: cleanProjectSegments.value,
        created_by: auth.activeUser.id,
        status: 1,
        overtime_day: props.data.shift.shift_day
    }
    loading.value = true
    await api.post('/request_overtime', params, {
        toast: '申請しました。',
    })
    await fetchShiftDataTable()
    emit('close', true)

    loading.value = false
    
    
}
const deleteRequest = async() => {

    await api.del(`/request_overtime`, {
        id: target.value.id,
    }, {
        toast: '残業申請を削除しますか。',
        ask: '削除しますか？',
    })
    await fetchShiftDataTable()
    emit('close', true)
    loading.value = false
}
</script>
<style scoped>
.overtime-project-box {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.overtime-project-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.overtime-project-head strong {
    font-size: 13px;
    font-weight: 500;
    color: var(--primary-color);
    white-space: nowrap;
}

.overtime-project-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.overtime-project-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
}

.overtime-project-row-main {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) 120px 28px;
    gap: 8px;
    align-items: center;
}

.overtime-project-select {
    height: 36px;
    min-width: 0;
}

.overtime-minute-input {
    position: relative;
}

.overtime-minute-input input {
    width: 100%;
    height: 36px;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
    background: var(--background-color);
    padding: 0 32px 0 10px;
    box-sizing: border-box !important;
}

.overtime-minute-input input::-webkit-outer-spin-button,
.overtime-minute-input input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.overtime-minute-input input[type="number"] {
    -moz-appearance: textfield;
}

.overtime-minute-input span {
    position: absolute;
    top: 0;
    right: 9px;
    height: 36px;
    line-height: 36px;
    font-size: 12px;
    color: var(--primary-color);
}

.overtime-project-content {
    width: 100%;
    min-height: 66px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 8px;
    resize: vertical;
    font-size: 13px;
    box-sizing: border-box !important;
}

.overtime-add-project {
    align-self: flex-start;
}

.overtime-project-note {
    margin: 0;
    font-size: 12px;
    color: color-mix(in srgb, var(--primary-color) 72%, transparent);
}

@media screen and (max-width: 767px) {
    .overtime-project-row-main {
        grid-template-columns: 1fr 100px 28px;
    }
}
</style>
