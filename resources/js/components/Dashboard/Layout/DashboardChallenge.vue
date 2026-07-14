<template>
    <BaseLayout
        :title="data.title"
        :count="data.data.length"
        :fullscreen="fullscreen"
        :type="data.type"
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) => emit('toggle', el, data.type)"
        @resize="emit('resize', data.type)"
    >
        <template #icon>
            <svg class="side-app-icon mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21.76 21.79" style="width: auto; height: 16px; min-width: 17px;">
                <path d="m21.54.32c-.25-.3-.67-.39-1.04-.25h0c-.84.33-1.68.66-2.51,1-.84.34-1.67.68-2.5,1.02-1.67.68-3.33,1.38-4.99,2.07l-4.99,2.08L.52,8.35c-.27.11-.48.37-.52.71s.18.7.51.84h.01c.69.31,1.39.6,2.08.89l2.09.86c.7.28,3.95,1.5,4.24,1.6s.6.06.86-.17,6.1-6.39,6.1-6.39c.23-.23.22-.61-.02-.83s-.6-.2-.83.02l-5.71,5.43c-.16.15-.39.19-.59.1-.42-.19-4.51-1.88-5.16-2.14-.16-.06-.16-.28,0-.35l2.59-1.04,5.01-2.02c1.67-.68,3.34-1.35,5.01-2.03.59-.24,1.74-.72,2.42-1,.2-.08.4.12.31.31l-3.04,7.42-2.04,5.01c-.36.9-.73,1.79-1.09,2.69-.06.15-.28.16-.34,0l-1.52-3.53c-.15-.31-.56-.46-.92-.32s-.5.5-.37.81l2.22,6c.1.26.33.48.65.54.39.07.78-.16.94-.53h0c.7-1.67,1.39-3.33,2.08-4.99l2.07-4.99L21.69,1.26c.12-.29.09-.66-.15-.95Z"></path>
            </svg>
        </template>
        <div v-if="!fullscreen" class="m-5">
            <div class="grid gap-5">
                <section v-for="section in challengeSections" :key="section.key" class="grid gap-2">
                    <p class="text-[13px]">{{ section.title }} ({{ section.items.length }})</p>
                    <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                        <ExpansionPanelItem
                            v-for="(challenge, index) in section.items"
                            :key="`${section.key}-${challenge.id ?? index}`"
                            :value="challenge.id ?? `${section.key}-${index}`"
                            hide-actions
                            static
                            :tile="true"
                            class="rm-p"
                        >
                    <template #title="{ expanded }">
                        <PanelTitle :expanded="expanded">
                            <div class="flex gap-2 items-center">
                                <div v-if="challenge.attention_is_overdue" class="mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                <Nice v-if="isNiceReminder(challenge) || isGlowdNinePlay(challenge) || isRakuawardNominate(challenge)" size="16"/>
                                <Challenge v-else size="16"/>
                                <div class="text-wrap">
                                    
                                    {{ titleText(challenge) }}
                                </div>
                            </div>
                            
                        </PanelTitle>
                    </template>
                    <template #body>
                        <PanelData v-if="isChallengeRelayReceived(challenge)">
                            <p class="text-[12px]" :class="challenge.attention_is_overdue ? 'text-[tomato]' : 'text-[gray]'">
                                {{ challenge.user?.name ?? '誰か' }}さんからチャレンジリレーのバトンが届きました。1週間以内にチャレンジを作成するか、パスしてください。
                            </p>
                            <p v-if="challenge.source_post_title" class="mt-2 text-[11px] text-[gray]">
                                元のチャレンジ: {{ challenge.source_post_title }}
                            </p>
                            <p v-if="challenge.attention_deadline" class="mt-1 text-[11px] text-[gray]">
                                締切: {{ formatDeadline(challenge.attention_deadline) }}
                            </p>
                            <div class="mt-3 flex items-center justify-end gap-3 text-right">
                                <router-link v-if="challenge.source_post_id" :to="{ name: 'post', query: { id: challenge.source_post_id, app_type: 2 } }">見る</router-link>
                                <router-link :to="{ name: 'post', query: { app_type: 2, create: '1', relay_id: challenge.relay_id } }">作成</router-link>
                                <button
                                    type="button"
                                    class="jump-link !bg-inherit"
                                    :disabled="isRelayProcessing(challenge)"
                                    @click="passChallengeRelay(challenge)"
                                >
                                    パス
                                </button>
                            </div>
                        </PanelData>
                        <PanelData v-else-if="isChallengeRelayReturned(challenge)">
                            <p class="text-[12px] text-[gray]">
                                {{ challenge.declined_by_user?.name ?? challenge.user?.name ?? 'メンバー' }}さんがチャレンジリレーをパスしました。別のメンバーへ渡すか、バトンを終了してください。
                            </p>
                            <p v-if="challenge.source_post_title" class="mt-2 text-[11px] text-[gray]">
                                元のチャレンジ: {{ challenge.source_post_title }}
                            </p>
                            <div v-if="!isRelayReassignOpen(challenge)" class="mt-3 flex items-center justify-end gap-3 text-right">
                                <button
                                    type="button"
                                    class="jump-link !bg-inherit"
                                    :disabled="isRelayProcessing(challenge)"
                                    @click="openRelayReassign(challenge)"
                                >
                                    他の人へ渡す
                                </button>
                                <button
                                    type="button"
                                    class="jump-link !bg-inherit"
                                    :disabled="isRelayProcessing(challenge)"
                                    @click="closeChallengeRelay(challenge)"
                                >
                                    渡さない
                                </button>
                            </div>
                            <div v-else class="mt-3">
                                <MemberSelector
                                    placeHolder="次に渡すメンバー"
                                    rules="required"
                                    name="challengeRelayTarget"
                                    :multiple="false"
                                    path="post_get_challenge_users"
                                    :exclude="relayExcludeIds(challenge)"
                                    v-model="relayTargets[relayId(challenge)]"
                                />
                                <div class="mt-3 flex items-center justify-end gap-3 text-right">
                                    <button
                                        type="button"
                                        class="jump-link !bg-inherit"
                                        :disabled="isRelayProcessing(challenge)"
                                        @click="reassignChallengeRelay(challenge)"
                                    >
                                        渡す
                                    </button>
                                    <button type="button" class="jump-link !bg-inherit" @click="cancelRelayReassign(challenge)">キャンセル</button>
                                </div>
                            </div>
                        </PanelData>
                        <PanelData v-else-if="isNiceReminder(challenge)">
                            <p class="text-[12px]" :class="challenge.attention_is_overdue ? 'text-[tomato]' : 'text-[gray]'">
                                {{ challenge.user?.name ?? '誰か' }}さんからナイスが届きました。1週間以内にナイスを送ってみましょう。
                            </p>
                            <div class="mt-3 flex items-center justify-end gap-3 text-right">
                                <router-link :to="{ name: 'post', query: { id: challenge.id, app_type: 0 } }">見る</router-link>
                                <router-link :to="{ name: 'post', query: { app_type: 0, create: '1' } }">作成</router-link>
                                <button
                                    type="button"
                                    class="jump-link !bg-inherit"
                                    :disabled="isNiceProcessing(challenge)"
                                    @click="dismissNiceReminder(challenge)"
                                >
                                    送らない
                                </button>
                            </div>
                        </PanelData>
                        <PanelData v-else-if="isGlowdNinePlay(challenge)">
                            <p v-if="challenge.glowd_nine_source === 'rakuaward'" class="text-[12px] text-[gray]">
                                楽アワードノミネートチャージからグラウドナインを受け取りました
                            </p>
                            <p v-else-if="challenge.glowd_nine_source === 'challenge_award'" class="text-[12px] text-[gray]">
                                応援したチャレンジが達成されました！グラウドナインに挑戦できます
                            </p>
                            <p v-else class="text-[12px] text-[gray]">
                                ナイスリレーからグラウドナインを受け取りました!
                            </p>
                            <div class="mt-3 flex items-center justify-end gap-2 text-right">
                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-full bg-[var(--bg3)] text-[var(--primary-color)] text-[12px] px-3 py-1 cursor-pointer"
                                    @click="openGlowdNine(challenge)"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12" fill="currentColor" aria-hidden="true">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                    プレイ
                                </button>
                            </div>
                        </PanelData>
                        <PanelData v-else-if="isRakuawardNominate(challenge)">
                            <p class="text-[12px]" :class="challenge.attention_is_overdue ? 'text-[tomato]' : 'text-[gray]'">
                                今月の楽アワードノミネートがまだ作成されていません。20日までに作成してください。
                            </p>
                            <p v-if="challenge.attention_deadline" class="mt-1 text-[11px] text-[gray]">
                                締切: {{ formatDeadline(challenge.attention_deadline) }}
                            </p>
                            <div class="mt-3 text-right">
                                <router-link :to="{ name: 'post', query: { app_type: 0, create: '1' } }">作成</router-link>
                            </div>
                        </PanelData>
                        <PanelData v-else>
                            <p v-if="isUpdateNeed(challenge)" class="text-[12px] text-[tomato]">チャレンジ期間が終了しました。結果を入力してください。</p>
                            <p v-else-if="isProgressNeed(challenge)" class="text-[12px] text-[gray]">チャレンジが {{ challenge.attention_checkpoint }}% のチェックポイントを通過しました。進行状況の報告を提出してください。</p>
                            <p v-else class="text-[12px] text-[gray]">このチャレンジはあなたの注意が必要です。</p>
                            <p v-if="isProgressNeed(challenge) && challenge.attention_progress_percent" class="mt-2 text-[11px] text-[gray]">
                                進行状況: {{ challenge.attention_progress_percent }}%
                            </p>
                            <p v-if="challenge.attention_deadline" class="mt-1 text-[11px] text-[gray]">
                                締切: {{ formatDeadline(challenge.attention_deadline) }}
                            </p>
                            <div class="mt-3 text-right">
                                <router-link :to="{ name: 'post', query: { id: challenge.id, status: isProgressNeed(challenge) ? 5 : undefined, progress_checkpoint: challenge.attention_checkpoint } }">対応</router-link>
                            </div>
                        </PanelData>
                    </template>
                        </ExpansionPanelItem>
                    </ExpansionGrid>
                </section>
            </div>
        </div>
        <Transition name="modalFade">
            <RollDice
                v-if="glowdNineTarget"
                :relayRootId="glowdNineTarget"
                @close="closeGlowdNine"
            />
        </Transition>
    </BaseLayout>
</template>

<script setup lang="ts">
import type { DashboardChallengeCard, DashboardChallengeData } from '@/interface/dashboard';
import type { User } from '@/interface/globalInterface';
import BaseLayout from './BaseLayout.vue';
import { DateTime } from 'luxon';
import { computed, ref } from 'vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import Nice from '@/components/Icons/Nice.vue';
import Challenge from '@/components/Icons/Challenge.vue';
import RollDice from '@/components/Global/RollDice.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import { useApi } from '@/composables/api';
import { useDashboardStore } from '@/store/dashboard';
import { useDialog } from '@/composables/dialog';
import { useAuthUserStore } from '@/store/auth';

type DashboardPostReminder = DashboardChallengeData

const props = defineProps<{
    data: DashboardChallengeCard,
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const api = useApi()
const auth = useAuthUserStore()
const { getBatchDashboardData } = useDashboardStore()
const { ping } = useDialog()
const relayTargets = ref<Record<number, User | null>>({})
const relayReassignOpen = ref<Record<number, boolean>>({})
const relayProcessing = ref<Record<number, boolean>>({})
const niceProcessing = ref<Record<number, boolean>>({})
const glowdNineTarget = ref<number | null>(null)

const NICE_RELAY_LIMIT = 9

const isNiceReminder = (challenge: DashboardPostReminder) => challenge.attention_type === 'nice_follow_up'
const isGlowdNinePlay = (challenge: DashboardPostReminder) => challenge.attention_type === 'nice_relay_glowd_nine'
const isRakuawardNominate = (challenge: DashboardPostReminder) => challenge.attention_type === 'rakuaward_nominate'
const isProgressNeed = (challenge: DashboardPostReminder) => challenge.attention_type === 'progress_need'
const isUpdateNeed = (challenge: DashboardPostReminder) => challenge.attention_type === 'update_need'
const isChallengeRelayReceived = (challenge: DashboardPostReminder) => challenge.attention_type === 'challenge_relay_received'
const isChallengeRelayReturned = (challenge: DashboardPostReminder) => challenge.attention_type === 'challenge_relay_returned'
const isChallengeRelay = (challenge: DashboardPostReminder) => isChallengeRelayReceived(challenge) || isChallengeRelayReturned(challenge)

const isOverdue = (challenge: DashboardPostReminder) => {
    const endData = DateTime.fromISO(challenge.date_end);
    const now = DateTime.local();
    const diff = endData.diff(now, 'days').days;
    return diff < 0;
}

const challengeSections = computed(() => {
    const challengeRelays = props.data.data.filter(isChallengeRelay)
    const niceRelays = props.data.data.filter(isNiceReminder)
    const glowdNinePlays = props.data.data.filter(isGlowdNinePlay)
    const rakuawardNominates = props.data.data.filter(isRakuawardNominate)
    const progressAndResultNeeds = props.data.data.filter(challenge => !isChallengeRelay(challenge) && !isNiceReminder(challenge) && !isGlowdNinePlay(challenge) && !isRakuawardNominate(challenge))

    return [
        { key: 'challenge-relay', title: 'チャレンジリレー', items: challengeRelays },
        { key: 'nice-relay', title: 'ナイスリレー', items: niceRelays },
        { key: 'glowd-nine', title: 'グラウドナイン', items: glowdNinePlays },
        { key: 'raku-award', title: '楽アワードノミネート', items: rakuawardNominates },
        { key: 'progress-result', title: '進捗・結果報告依頼', items: progressAndResultNeeds },
    ].filter(section => section.items.length)
})

const formatDeadline = (deadline?: string | null) => {
    if (!deadline) {
        return ''
    }

    const parsed = DateTime.fromISO(deadline)
    return parsed.isValid ? parsed.toFormat('yyyy/MM/dd') : ''
}

const titleText = (challenge: DashboardPostReminder) => {
    if (isNiceReminder(challenge)) {
        return `${challenge.user?.name ?? '誰か'}さんからナイスが届きました`
    }

    if (isGlowdNinePlay(challenge)) {
        return challenge.glowd_nine_source === 'rakuaward'
            ? '楽アワードのグラウドナイン'
            : 'グラウドナインを受け取りました'
    }

    if (isChallengeRelayReceived(challenge)) {
        return `${challenge.user?.name ?? '誰か'}さんからバトンが届きました`
    }

    if (isChallengeRelayReturned(challenge)) {
        return `${challenge.declined_by_user?.name ?? challenge.user?.name ?? 'メンバー'}さんがバトンをパスしました`
    }

    if (isRakuawardNominate(challenge)) {
        return '楽アワードノミネートを作成'
    }

    return challenge.title
}

const relayId = (challenge: DashboardPostReminder) => Number(challenge.relay_id ?? 0)
const isRelayProcessing = (challenge: DashboardPostReminder) => !!relayProcessing.value[relayId(challenge)]
const isRelayReassignOpen = (challenge: DashboardPostReminder) => !!relayReassignOpen.value[relayId(challenge)]
const setRelayProcessing = (challenge: DashboardPostReminder, value: boolean) => {
    relayProcessing.value[relayId(challenge)] = value
}

const relayExcludeIds = (challenge: DashboardPostReminder) => {
    return Array.from(new Set([
        auth.id,
        challenge.user?.id,
        challenge.declined_by_user?.id,
    ].filter((id): id is number => typeof id === 'number')))
}

const refreshChallenges = async () => {
    await getBatchDashboardData(['challenges'])
}

const openGlowdNine = (challenge: DashboardPostReminder) => {
    const rootPostId = Number(challenge.relay_root_post_id ?? 0)
    if (!rootPostId) return
    glowdNineTarget.value = rootPostId
}

const closeGlowdNine = async () => {
    glowdNineTarget.value = null
    await refreshChallenges()
}

const passChallengeRelay = async (challenge: DashboardPostReminder) => {
    const id = relayId(challenge)
    if (!id) return

    setRelayProcessing(challenge, true)
    try {
        await api.post('challenge_relay_pass', { relay_id: id }, { toast: 'チャレンジリレーをパスしました。' })
        await refreshChallenges()
    } catch {
        // useApi already shows the error dialog.
    } finally {
        setRelayProcessing(challenge, false)
    }
}

const openRelayReassign = (challenge: DashboardPostReminder) => {
    const id = relayId(challenge)
    if (!id) return

    relayTargets.value[id] = null
    relayReassignOpen.value[id] = true
}

const cancelRelayReassign = (challenge: DashboardPostReminder) => {
    const id = relayId(challenge)
    if (!id) return

    relayTargets.value[id] = null
    relayReassignOpen.value[id] = false
}

const reassignChallengeRelay = async (challenge: DashboardPostReminder) => {
    const id = relayId(challenge)
    const target = relayTargets.value[id]
    if (!id) return

    if (!target?.id) {
        ping('バトンを渡すメンバーを選択してください。')
        return
    }

    setRelayProcessing(challenge, true)
    try {
        await api.post('challenge_relay_reassign', { relay_id: id, to_user_id: target.id }, { toast: 'チャレンジリレーを渡しました。' })
        relayReassignOpen.value[id] = false
        relayTargets.value[id] = null
        await refreshChallenges()
    } catch {
        // useApi already shows the error dialog.
    } finally {
        setRelayProcessing(challenge, false)
    }
}

const closeChallengeRelay = async (challenge: DashboardPostReminder) => {
    const id = relayId(challenge)
    if (!id) return

    setRelayProcessing(challenge, true)
    try {
        await api.post('challenge_relay_close', { relay_id: id }, { toast: 'チャレンジリレーを終了しました。' })
        await refreshChallenges()
    } catch {
        // useApi already shows the error dialog.
    } finally {
        setRelayProcessing(challenge, false)
    }
}

const nicePostId = (challenge: DashboardPostReminder) => Number(challenge.id ?? 0)
const isNiceProcessing = (challenge: DashboardPostReminder) => !!niceProcessing.value[nicePostId(challenge)]
const dismissNiceReminder = async (challenge: DashboardPostReminder) => {
    const id = nicePostId(challenge)
    if (!id) return

    niceProcessing.value[id] = true
    try {
        await api.post('nice_follow_up_dismiss', { post_id: id }, { toast: 'ナイスリレーを閉じました。' })
        await refreshChallenges()
    } catch {
        // useApi already shows the error dialog.
    } finally {
        niceProcessing.value[id] = false
    }
}

defineExpose({
    cardType: props.data.type,
})
</script>
