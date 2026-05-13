<template>
    <BaseLayout
        :title="data.title"
        :count="data.data.attention.length"
        :fullscreen="fullscreen"
        :type="data.type"
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el) => emit('toggle', el, data.type)"
        @resize="emit('resize', data.type)"
    >
        <template #icon>
            <svg class="mr-1" fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                <path d="M12 2.2c.7 0 1.34.37 1.69.98l9.03 15.64A1.95 1.95 0 0 1 21.03 21H2.97a1.95 1.95 0 0 1-1.69-2.18l9.03-15.64A1.95 1.95 0 0 1 12 2.2Zm0 2.4L3.78 18.83h16.44L12 4.6Zm-.02 10.67c.66 0 1.2.54 1.2 1.2s-.54 1.2-1.2 1.2-1.2-.54-1.2-1.2.54-1.2 1.2-1.2Zm0-7.34c.55 0 1 .45 1 1v4.27a1 1 0 1 1-2 0V8.93c0-.55.45-1 1-1Z"/>
            </svg>
        </template>

        <div v-if="!fullscreen" class="mx-5 mt-5 mb-3">
            <div v-if="data.data.attention.length" class="mb-3">
                <p class="text-sm mb-2">対応が必要なインシデント</p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        selected-class="selected-panel-item"
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="(incident, index) in data.data.attention"
                        :key="incident.id ?? index"
                        :value="incident.id ?? index"
                        :col="Number(data.col?.split('-')[2] ?? 1)"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate">{{ incident.title || '無題のインシデント' }}</div>
                                    <div class="text-[11px] text-[gray] truncate">
                                        {{ formatDate(incident.occurred_date) }} / {{ incident.caused_by_user?.name || '対象者未設定' }}
                                    </div>
                                </div>
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div class="flex flex-col gap-2 text-[12px]">
                                    <div><span class="text-[gray]">ステータス：</span>{{ incident.status || '未設定' }}</div>
                                    <div><span class="text-[gray]">プロジェクト：</span>{{ incident.project_record?.name || '未設定' }}</div>
                                    <div><span class="text-[gray]">区分：</span>{{ incident.category?.name || '未設定' }}</div>
                                    <div v-if="incident.description" class="whitespace-pre-wrap">{{ incident.description }}</div>
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div v-else class="text-sm text-[gray] mb-3 text-center">
                対応が必要なインシデントはありません。
            </div>
            <div class="text-center">
                <router-link :to="{name: 'dashboard', params: { type: 'incidents'}}" class="jump-link text-sm text-center">
                    詳細を見る
                </router-link>
            </div>
        </div>

        <IncidentContainer v-if="fullscreen" />
    </BaseLayout>
</template>

<script setup lang="ts">
import { DateTime } from 'luxon';
import { Incident } from '@/interface/incident';
import BaseLayout from './BaseLayout.vue';
import IncidentContainer from '@/components/Incident/IncidentContainer.vue';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';

const props = defineProps<{
    data: {
        title: string
        data: {
            attention: Incident[]
        }
        order?: number
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col?: string
    }
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
    refreshData: [key: string]
}>()

const formatDate = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/MM/dd') : date
}

defineExpose({
    cardType: props.data.type,
})
</script>
