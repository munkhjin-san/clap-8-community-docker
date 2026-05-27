<template>
    <BaseLayout
        v-if="fullscreen || canSeeIncidentCard"
        :title="data.title"
        :count="data.data.attention.length"
        :fullscreen="fullscreen"
        :type="data.type"
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el) => emit('toggle', el, data.type)"
        @resize="emit('resize', data.type)"
        :class="{ 'incident-card--warning': data.data.attention.length > 0 }"
    >
        <template #icon>
            <svg :style="{fill: data.data.attention.length ? 'tomato' : 'var(--primary-color)'}" xmlns="http://www.w3.org/2000/svg" class="mr-1" width="18" height="18" viewBox="0 0 555.42749 492.03711">
                <path d="M513.79504,492.03711H41.63245c-15.02686,0-28.48389-7.76953-35.99756-20.7832-7.51318-13.01367-7.51318-28.55176,0-41.56543L241.71643,20.7832c7.51318-13.01367,20.97021-20.7832,35.99756-20.7832,15.02637,0,28.4834,7.76953,35.99707,20.7832l236.08105,408.90527c7.51367,13.0127,7.51367,28.55176.00098,41.56543-7.51367,13.01367-20.9707,20.7832-35.99805,20.7832ZM42.38635,450.03418l470.65381.00293L277.71545,42.43701,42.38635,450.03418Z" />
                <path d="M300.16721,303.86606c3.96201-28.71872,2.50677-57.67465,2.38568-86.55373-.25369-9.62187-.72961-19.24579-1.8385-28.87555-3.58115-26.0806-40.50627-26.66922-44.68898-.41252-2.52609,19.17458-2.78175,38.41208-3.12161,57.68835-.51553,19.2803-.91856,38.51483,1.53835,57.73135,3.97847,26.27393,41.13144,26.88968,45.72507.42209Z" />
                <path d="M303.98193,361.42068c-1.41043-3.83047-3.42941-7.15408-5.76379-10.25543-5.90765-4.73798-13.3096-8.11694-21.15579-8.09573-15.71594-.41785-29.55051,13.46042-28.98163,29.10031.12589,8.08966,3.74249,15.36737,9.00317,21.25189,23.82764,19.85123,57.14377-2.47241,46.89804-32.00101v-.00003Z" />
            </svg>
        </template>

        <div v-if="!fullscreen" class="mx-5 mt-5 mb-3">
            <div v-if="data.data.attention.length" class="mb-3">
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
                                    <div class="truncate text-[13px] leading-normal">{{ incident.description || '情報未設定' }}</div>
                                    <div class="text-[11px] text-[gray] truncate mt-1">
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
                                    <button
                                        type="button"
                                        class="jump-link text-sm text-center w-fit ml-auto bg-inherit"
                                        @click.stop="openIncidentDetail(incident)"
                                    >
                                        詳細を開く
                                    </button>
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
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { Incident } from '@/interface/incident';
import BaseLayout from './BaseLayout.vue';
import IncidentContainer from '@/components/Incident/IncidentContainer.vue';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import { useAuthUserStore } from '@/store/auth';

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
const router = useRouter()
const auth = useAuthUserStore()
const canSeeIncidentCard = computed(() => auth.isPM || auth.isBoss || auth.isAdmin)

const formatDate = (date?: string | null) => {
    if (!date) return '発生日未設定'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/MM/dd') : date
}

const openIncidentDetail = (incident: Incident) => {
    if (!incident.id) return

    router.push({
        name: 'dashboard',
        params: {
            type: props.data.type,
            itemId: incident.id,
        },
    })
}

defineExpose({
    cardType: props.data.type,
})
</script>
