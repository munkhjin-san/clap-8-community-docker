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
                                <div
                                    v-if="shouldShowUnreadDot(incident)"
                                    class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5"
                                    :class="{ 'custom-heartbeat': isNewIncident(incident) }"
                                ></div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1">
                                        <div class="truncate text-[13px] leading-normal">{{ incident.description || '情報未設定' }}</div>
                                   

                                        <div class="relative flex" v-if="incident.unread_comments_count">
                                
                                            <svg fill="orange" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 30.88051 24.9735">
                                            <path d="M30.72814,8.8769c-.14532-.82959-.40253-1.64972-.77496-2.4184-.37347-.76801-.86078-1.48114-1.43018-2.11041-.56958-.63019-1.21985-1.17505-1.91077-1.64008-.69165-.46552-1.42749-.84625-2.17938-1.16577-1.5072-.63647-3.08105-1.02167-4.65607-1.25201C18.1997.06067,16.61914-.02142,15.04528.00464c-1.57648.02826-3.16119.16687-4.73059.47339-1.56677.30853-3.12598.77979-4.58923,1.52222-.73016.37158-1.43451.81073-2.08917,1.32697-.65393.51624-1.25677,1.11188-1.7735,1.78302-.51813.66943-.9433,1.41797-1.25366,2.21051-.31232.7923-.4989,1.63013-.57269,2.46863-.03809.41821-.04175.84344-.03156,1.24939.01123.41052.04254.82294.0976,1.23492.11224.82324.32281,1.6463.65656,2.427.33209.7807.78845,1.51337,1.34021,2.15607.55261.64252,1.19427,1.19592,1.88171,1.6568,1.37878.92578,2.68457,1.41705,4.21594,1.83752,1.40436.38562,3.01337.61237,4.42383.68085.11499.00562.22223.05609.29999.14099.35828.39093.73218.8374,1.12903,1.18121.52246.45294,1.09735.87909,1.70001,1.23297.59595.34991,1.21814.62427,1.8606.87347.67725.2442,1.7251.4682,2.2804.51007.54651.0412.61255-.37128.435-.73407s-.21918-.43036-.29242-.58905c-.07404-.16064-.14563-.32257-.21429-.48541-.13745-.3255-.26355-.65436-.37738-.98267-.09088-.26556-.22833-.73004-.30035-1.09607-.02545-.12921.06171-.25269.19214-.27081,1.26611-.17621,2.52991-.42755,3.77478-.80463.76044-.23096,1.51337-.50958,2.24554-.85553.73206-.34485,1.44232-.76208,2.10303-1.26599.65881-.50543,1.26453-1.10352,1.7677-1.78918.25061-.34308.4754-.70667.67157-1.0849.19421-.37921.35907-.77295.49432-1.17499.26868-.80518.41492-1.64044.46771-2.46826.05145-.82404.01685-1.66162-.12994-2.49219Z" />
                                            </svg>
                                            <div class="text-[white] leading-[14px] absolute top-0 left-0 right-0 text-[10px] w-fit mx-auto">{{ (incident.unread_comments_count ?? 0) }}</div>
                                        </div>
                                    </div>
                                    
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
const isNewIncident = (incident: Incident) => !incident.last_read_at && !(incident.read_histories?.length)
const shouldShowUnreadDot = (incident: Incident) => (isNewIncident(incident) || (incident.unread_update_logs_count ?? 0) > 0) && incident.status !== '完了'

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

<style scoped>
.incident-comment-badge{
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
}

.incident-comment-badge span{
    position: absolute;
    top: 1px;
    left: 0;
    right: 0;
    width: fit-content;
    margin: auto;
    color: white;
    font-size: 10px;
    line-height: 1;
}
</style>
