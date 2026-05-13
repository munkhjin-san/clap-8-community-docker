<template>
    <div v-if="hasPrivilage" class="px-5 pb-5 text-[var(--primary-color)] overflow-auto h-[calc(100%-95px)]">
        <div v-if="auth.isAdmin" class="py-5 sticky top-0 z-10 bg-[var(--background-color)] flex items-center justify-between">
            <div class="flex gap-4 ml-auto">
                <div>
                    <button v-if="selectedProject?.status == 'director_approved'" @click="confirm('running')" class="bg-[var(--primary-button)] text-white py-1 px-2">
                        確定
                    </button>
                    <span v-else class="text-xs">
                        確定済み({{ selectedProject?.status ? PROJECT_STATUS_LABEL[selectedProject?.status] : '不明' }})
                    </span>
                </div>
            </div>
        </div>
           
                <div
                    v-for="(items, category) in groupedCheckitems"
                    :key="category"
                    class="border border-solid border-[var(--calendarBorder)] mb-5 bg-[var(--background-color)] rounded-md overflow-hidden"
                >
                    <div :class="[{'border-b [border-bottom-style:solid] border-[var(--calendarBorder)]' : expanded[category]}, 'flex items-center p-4 bg-[var(--background-color)]']">
                        <div data-v-f3d19ae5="" @click="toggleCategory(category)" title="すべて表示する" class="selector-accordion-el" style="min-width: 30px; min-height: 30px;">
                            <svg data-v-f3d19ae5="" fill="var(--primary-color)" version="1.1" width="11" height="11" :class="[{'expand' : !expanded[category]}, 'cursor-pointer', 'selector-accordion-inactive']" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="">
                                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>
                        </div>
                        <div class="font-bold">{{category}}<span class="text-[11px] opacity-70 pt-2 px-2">
                            完了 {{ (categoryStats[category]?.done ?? 0) + (categoryStats[category]?.na ?? 0) }} / {{ categoryStats[category]?.total ?? 0 }}
                            <!-- <span v-if="categoryStats[category]?.na">・対象外 {{ categoryStats[category]?.na }}</span> -->
                        </span></div>
                        
                        <div>
                            <span v-if="projectReportBadgeType?.[category]" class="side-notification side-notification--comment-only" style="position: unset;">{{ projectReportBadgeType?.[category] }}</span>

                        </div>
                    </div>
                    
                    

                    <div v-if="expanded[category]" class="px-4 py-4 space-y-3">
                        <div class="flex flex-col md:flex-row gap-5 items-start">
                            <div class="w-full">
                                <p class="text-sm">仕様</p>
                                <ProjectCreationSummary
                                    :category="category"
                                    :edit-data="selectedProject?.specs?.spec_data"
                                />
                            </div>
                            <div class="w-full">
                                <p class="text-sm">経営管理本部チェック項目</p>
                                <div
                                    v-for="it in items"
                                    :key="it.id"
                                    class="p-3 mt-2 border border-solid border-[var(--calendarBorder)] rounded-md"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-3 text-sm leading-relaxed">
                                            <div>
                                                <div>{{ it.label }}</div>
                                                <div v-if="it.checked_at" class="text-[11px] opacity-60 mt-1">
                                                    更新: {{ formatDate(it.checked_at) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 text-[11px]">
                                            <label :class="statusOptionClass(it, 'done')">
                                                <input
                                                    @change="() => toggleCheck(it, 'done')"
                                                    class="sr-only"
                                                    :name="`status-${it.id}`"
                                                    type="radio"
                                                    value="done"
                                                    :checked="it.status === 'done'"
                                                    :disabled="!canEditStatus(it)"
                                                >
                                                完了
                                            </label>
                                            <label :class="statusOptionClass(it, 'na')">
                                                <input
                                                    @change="() => toggleCheck(it, 'na')"
                                                    :name="`status-${it.id}`"
                                                    class="sr-only"
                                                    type="radio"
                                                    value="na"
                                                    :checked="it.status === 'na'"
                                                    :disabled="!canEditStatus(it)"
                                                >
                                                対象外
                                            </label>
                                            <button
                                                v-if="it.status !== 'pending' && auth.isAdmin"
                                                type="button"
                                                class="px-2 py-1 rounded border border-[var(--normalBorder)] opacity-80 hover:opacity-100 transition text-[11px] break-keep"
                                                :disabled="!canEditStatus(it)"
                                                @click="toggleCheck(it, 'pending')"
                                            >
                                                未選択
                                            </button>
                                            
                                        
                                            <span v-if="it.checked_by" class="break-keep">
                                                <div class="relative" v-if="it.check_user">
                                                    <UserPanel size="25" :user="it.check_user" imgClass="userNormalIcon"/>
                                                    <div class="absolute bottom-[-2px] right-[-2px] z-[3]" v-if="it.link_user">
                                                        <UserPanel size="15" :user="it.link_user"/>
                                                    </div>                   
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="it.children.length" class="mt-2 pl-2" v-for="ch in it.children" :key="ch.id">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-3 text-xs leading-relaxed">
                                                <div>
                                                    <div>{{ ch.label }}</div>
                                                    <div v-if="ch.checked_at" class="text-[11px] opacity-60 mt-1">
                                                        更新: {{ formatDate(ch.checked_at) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 text-[11px]">
                                                <label :class="statusOptionClass(ch, 'done')">
                                                    <input
                                                        @change="() => toggleCheck(ch, 'done')"
                                                        class="sr-only"
                                                        :name="`status-${ch.id}`"
                                                        type="radio"
                                                        value="done"
                                                        :checked="ch.status === 'done'"
                                                        :disabled="!canEditStatus(ch)"
                                                    >
                                                    完了
                                                </label>
                                                <label :class="statusOptionClass(ch, 'na')">
                                                    <input
                                                        @change="() => toggleCheck(ch, 'na')"
                                                        :name="`status-${ch.id}`"
                                                        class="sr-only"
                                                        type="radio"
                                                        value="na"
                                                        :checked="ch.status === 'na'"
                                                        :disabled="!canEditStatus(ch)"
                                                    >
                                                    対象外
                                                </label>
                                                <button
                                                    v-if="ch.status !== 'pending' && auth.isAdmin"
                                                    type="button"
                                                    class="px-2 py-1 rounded border border-[var(--normalBorder)] opacity-80 hover:opacity-100 transition text-[11px] break-keep"
                                                    :disabled="!canEditStatus(ch)"
                                                    @click="toggleCheck(ch, 'pending')"
                                                >
                                                    未選択
                                                </button>
                                                
                                            
                                                <span v-if="ch.checked_by" class="break-keep">
                                                    <div class="relative" v-if="ch.check_user">
                                                        <UserPanel size="25" :user="ch.check_user" imgClass="userNormalIcon"/>
                                                        <div class="absolute bottom-[-2px] right-[-2px] z-[3]" v-if="ch.link_user">
                                                            <UserPanel size="15" :user="ch.link_user"/>
                                                        </div>                   
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="mt-6">
                            <MessageArea 
                                :type="category"
                                :passingData="passingData"
                                :item="{
                                    ...selectedProject,
                                    reports: selectedProject?.reports?.filter(report => report.type === category) ?? []
                                }"
                                @refresh="updateProject([
                                    { name: 'reports', include: ['user', 'files'] },
                                    { name: 'status' },
                                ])"
                            />
                        </div>
                    </div>
                    
                </div>
            
            
        
        

        
    </div>
    <div v-else class="h-[calc(100%-115px)] w-full flex items-center justify-center">
        権限がありません
    </div>
</template>
<script lang="ts" setup>
import { useProject } from '@/composables/project';
import MessageArea from '../../MessageArea.vue';
import { computed, onMounted, ref, watchEffect } from 'vue';
import { ProjectCheckItem } from '@/interface/projectInterface';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import { useDialog } from '@/composables/dialog';
import { DateTime } from 'luxon';
import { PROJECT_STATUS_LABEL } from '@/utils/tools';
import ProjectCreationSummary from './ProjectCreationSummary.vue';
import { useBadgeStore } from '@/store/badge';
import UserPanel from '@/components/Global/UserPanel.vue';
import { useRoute } from 'vue-router';
import { getProjectCreationActiveCategoryIds, getProjectCreationActiveCategories } from './projectCreationForm';
import { useDashboardStore } from '@/store/dashboard';

const { selectedProject, updateProject, readProjectMessage } = useProject()
const api = useApi()
const auth = useAuthUserStore()
const { ping } = useDialog()
const seen = ref<Record<string, number>>({})
const emit = defineEmits<{
    (e: 'close') : void
}>()
const props = defineProps<{
    hasPrivilage: boolean
}>()
const updatingIds = ref<number[]>([])
const passingData = {
    path: '/project_checkitem_comment_add',
    title: 'メッセージ',
    file_path: 'project_checkitem_report_files'
}
const expanded = ref<Record<string, boolean>>({})
const activeSpecCategoryIds = computed<Set<number> | null>(() =>
    getProjectCreationActiveCategoryIds(selectedProject.value?.specs?.spec_data)
)
const activeSpecCategories = computed<Set<string> | null>(() =>
    getProjectCreationActiveCategories(selectedProject.value?.specs?.spec_data)
)
const groupedCheckitems = computed<Record<string, ProjectCheckItem[]>>(() => {
    const activeCategoryIds = activeSpecCategoryIds.value
    const activeCategories = activeSpecCategories.value
    const items: ProjectCheckItem[] = selectedProject.value?.checkitems ?? []
    const filtered = activeCategoryIds
        ? items.filter((item) => activeCategoryIds.has(Number(item.project_checkitem_category_id)))
        : activeCategories
            ? items.filter((item) => activeCategories.has(item.category?.trim() || ''))
        : items
    const sorted = [...filtered].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
    return sorted.reduce((acc, item) => {
        const key = item.category?.trim() || "未分類"
        ;(acc[key] ||= []).push(item)
        return acc
    }, {} as Record<string, ProjectCheckItem[]>)
})
watchEffect(() => {
    for (const key of Object.keys(groupedCheckitems.value)) {
        if (expanded.value[key] === undefined) {
            expanded.value[key] = false
        }
    }
})
const toggleCategory = (category: string) => {
    expanded.value[category] = !expanded.value[category]
    readProjectMessage(category)
}
const categoryStats = computed(() => {
    const stats: Record<string, { total: number; done: number; na: number }> = {}
    for (const [category, items] of Object.entries(groupedCheckitems.value)) {
        stats[category] = {
            total: items.length,
            done: items.filter(it => it.status === 'done').length,
            na: items.filter(it => it.status === 'na').length,
        }
    }
    return stats
})
const isUpdating = (id: number) => updatingIds.value.includes(id)
const setUpdating = (id: number, value: boolean) => {
    if (value) {
        if (!updatingIds.value.includes(id)) {
            updatingIds.value = [...updatingIds.value, id]
        }
        return
    }
    updatingIds.value = updatingIds.value.filter(v => v !== id)
}

const canEditStatus = (it: ProjectCheckItem) => {
    return auth.isAdmin && !isUpdating(it.id) && selectedProject.value?.status !== 'running'
}

const statusOptionClass = (it: ProjectCheckItem, option: 'done' | 'na') => {
    const isActive = it.status === option
    const isDisabled = !canEditStatus(it)
    const base = 'px-3 py-1 rounded-full border border-solid break-keep select-none transition-all duration-150'
    const active = option === 'done'
        ? 'bg-green-600 text-white border-green-600 shadow-[0_0_0_1px_rgba(22,163,74,0.35)]'
        : 'bg-amber-600 text-white border-amber-600 shadow-[0_0_0_1px_rgba(217,119,6,0.35)]'
    const idle = 'bg-[var(--bg2)] text-[var(--primary-color)] border-[var(--normalBorder)]'
    const hover = isDisabled ? '' : 'hover:brightness-110 hover:-translate-y-[1px] hover:border-[var(--hoverBorder)] focus-within:outline focus-within:outline-2 focus-within:outline-offset-1 focus-within:outline-[var(--hoverBorder)] cursor-pointer'
    const disabled = isDisabled ? 'opacity-50 cursor-not-allowed' : ''
    return `${base} ${isActive ? active : idle} ${hover} ${disabled}`
}

const formatDate = (value: string | null) => {
    if (!value) return ''
    return DateTime.fromSQL(value).toFormat('yyyy/M/d HH:mm:ss')
}

const toggleCheck = async (it: ProjectCheckItem, nextStatus: 'pending' | 'done' | 'na') => {
    if (!auth.isAdmin || !selectedProject.value?.id) return
    if (selectedProject.value?.status === 'running') {
        ping('プロジェクト進行中')
        return
    }

    const prev = { status: it.status, checked_by: it.checked_by, checked_at: it.checked_at }
    const toast = nextStatus === 'done'
        ? '完了に更新しました。'
        : nextStatus === 'na'
            ? '対象外に更新しました。'
            : '未選択に戻しました。'
    it.status = nextStatus
    setUpdating(it.id, true)
    try {
        const data = await api.patch('/project_checkitem_update', {
            id: it.id,
            project_id: selectedProject.value.id,
            status: nextStatus,
        }, {
            toast: toast
        })
        if (data) {
            Object.assign(it, data)
        }
    } catch (e) {
        Object.assign(it, prev)
        ping('更新に失敗しました。')
    } finally {
        setUpdating(it.id, false)
    }
}
const { getBatchDashboardData } = useDashboardStore()
const badge = useBadgeStore()
const route = useRoute()
const confirm = async(status: string) => {
    await api.patch('/project_change_status', {
        status: status,
        id: selectedProject.value?.id
    }, {
        toast: '確定しました'
    }) 
    updateProject([{name: 'status'}])
    badge.clearProjectConfirmBadge()
    getBatchDashboardData(['pendingProjects'])
}


const projectReportBadgeType = computed(() => {
    return badge.projectReportMapByType[Number(route.params.projectId)] ?? []
})
</script>
<style scoped>
    .expand {
        transform: rotate(180deg);
    }
</style>
