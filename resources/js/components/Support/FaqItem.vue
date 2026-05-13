<template>
    <ExpansionPanelItem
        ref="panelRef"
        :value="item.id"
        class="faq-item"
        panelClass="faq-item-panel"
        titleClass="faq-item-title"
    >
        <template #title="{ expanded }">
            <div class="faq-item-heading">
                <Back
                    size="10"
                    :class="['faq-item-indicator', { 'faq-item-indicator--expanded': expanded }]"
                    fill="currentColor"
                />
                <div class="faq-item-q">
                    <span class="faq-badge faq-badge--q">Q</span>
                    <span class="faq-item-question">{{ item.question }}</span>
                    <span
                        v-if="isAdmin"
                        :class="['faq-sync-badge', syncStatusClass(item.ai_sync_status)]"
                        :title="item.ai_sync_status === 'error' ? (item.ai_sync_error || '同期エラー') : syncStatusLabel(item.ai_sync_status)"
                    >
                        <svg fill="currentColor" version="1.1" width="15" height="15" viewBox="0 0 34.02 33.29" xmlns="http://www.w3.org/2000/svg"><path d="M27.57.33c.22.46.33.93.48,1.38.15.45.31.9.5,1.33.72,1.64,1.59,3.11,3.01,4.16.34.25.7.49,1.09.69.3.17.63.25.91.46.11.08.24.22.33.36.25.36.11.86-.29,1.04-.58.27-1.1.04-1.65-.11-1.49-.44-2.85-1.39-3.75-2.67-1.08-1.46-1.5-3.2-1.7-4.94-.06-.51-.12-1.02-.04-1.55.08-.57.87-.68,1.11-.16h0Z"></path><path d="M26.47,17.91c-.08-.52-.01-1.03.04-1.55.19-1.73.61-3.48,1.7-4.94.98-1.4,2.5-2.38,4.15-2.78.42-.12.81-.21,1.25,0,.42.19.54.75.24,1.11-.29.44-.77.52-1.19.76-1.14.6-2.09,1.44-2.83,2.51-.73,1.07-1.36,2.38-1.77,3.67-.15.46-.26.93-.48,1.39-.25.51-1.02.39-1.1-.17h0Z"></path><path d="M27.57.5c.08.52.01,1.03-.04,1.55-.19,1.73-.61,3.48-1.7,4.94-.98,1.4-2.5,2.38-4.15,2.79-.42.12-.81.21-1.25,0-.42-.19-.54-.75-.24-1.11.29-.44.77-.52,1.19-.76,1.14-.6,2.09-1.44,2.83-2.51.73-1.07,1.36-2.38,1.77-3.67.15-.46.26-.93.48-1.39.25-.51,1.02-.39,1.1.17h0Z"></path><path d="M26.47,18.08c-.22-.46-.33-.93-.48-1.38-.15-.45-.31-.9-.5-1.33-.72-1.64-1.59-3.11-3.01-4.16-.34-.25-.7-.49-1.09-.69-.3-.17-.63-.25-.91-.46-.11-.08-.24-.22-.33-.36-.25-.36-.11-.86.29-1.04.58-.27,1.1-.04,1.65.11,1.49.44,2.85,1.39,3.75,2.67,1.08,1.46,1.5,3.2,1.7,4.94.06.51.12,1.02.04,1.55-.08.57-.87.68-1.11.16h0Z"></path><path d="M11.41,5.24c.29.72.45,1.46.67,2.17,1.03,3.36,2.6,6.71,5.47,8.81.61.45,1.3.86,2,1.2.62.28,1.28.46,1.74,1.01.29.33.26.84-.08,1.13-.44.33-1.07.3-1.55.21-.39-.08-.82-.21-1.21-.34-2.45-.77-4.6-2.46-5.97-4.65-1.38-2.14-1.95-4.53-2.28-6.98-.1-.79-.2-1.57-.13-2.37.07-.73,1.07-.87,1.34-.2h0Z"></path><path d="M10.07,32.66c-.07-.8.03-1.58.13-2.37.1-.79.24-1.58.42-2.37.96-4.5,3.86-8.21,8.43-9.43.62-.18,1.22-.33,1.88-.11.42.14.64.6.5,1.01-.11.31-.42.56-.67.72-.46.29-.98.43-1.46.68-3.92,1.88-6.03,5.85-7.24,9.89-.22.72-.38,1.46-.67,2.18-.28.67-1.27.52-1.34-.2h0Z"></path><path d="M11.41,5.43c.07.8-.03,1.58-.13,2.37-.1.79-.24,1.58-.42,2.37-.96,4.5-3.86,8.21-8.43,9.43-.62.18-1.22.33-1.88.11-.42-.14-.64-.6-.5-1.01.11-.31.42-.56.67-.72.46-.29.98-.43,1.46-.68,3.92-1.88,6.03-5.85,7.24-9.89.22-.72.38-1.46.67-2.18.28-.67,1.27-.52,1.34.2h0Z"></path><path d="M10.07,32.86c-.29-.72-.45-1.46-.67-2.17-1.03-3.36-2.6-6.71-5.47-8.81-.61-.45-1.3-.86-2-1.2-.62-.28-1.28-.46-1.74-1.01-.29-.33-.26-.84.08-1.13.44-.33,1.07-.3,1.55-.21.39.08.82.21,1.21.34,2.45.77,4.6,2.46,5.97,4.65,1.38,2.14,1.95,4.53,2.28,6.97.1.79.2,1.57.13,2.37-.07.73-1.07.87-1.34.2h0Z"></path></svg>
                    </span>
                </div>
            </div>
            <ItemMenu
                v-if="isAdmin"
                class="faq-item-menu"
                @click.stop
                :items="[
                    { title: '編集する', action: () => emit('edit', item) },
                    { title: '削除する', action: () => emit('delete', item) }
                ]"
            />
        </template>
        <template #body>
            <div class="faq-detail-body">
                <div class="mb-2 text-[11px] text-[gray] text-right">更新日: {{ DateTime.fromISO(item.updated_at).toFormat('yyyy年MM月dd日') }}</div>
                <div class="faq-item-a">
                    <span class="faq-badge faq-badge--a">A</span>
                    <span class="faq-item-answer">{{ item.answer }}</span>
                </div>
                <div v-if="item.content" v-html="item.content" class="faqd-content"></div>
                <div class="faqd-resolve si-box">
                    <p class="faqd-resolve-title">問題は解決しましたか？</p>
                    <div class="faqd-resolve-actions">
                        <div @click="onFeedback(true)" class="commentEditButton">はい</div>
                        <div @click="onFeedback(false)" class="commentEditButton">いいえ</div>
                    </div>
                </div>
                <div ref="feedbackFormRef" class="si-box" v-if="showFeedbackForm">
                    <p style="margin-bottom: 30px;">解決しなかった理由をお聞かせください。</p>
                    <LongInput
                        :initialValue="feedbackContent"
                        :placeHolder="`解決しなかった理由`"
                        uId="feedBackBody"
                        name="feedBackBody"
                        rules="required|max:2000"
                        label="タイトル"
                        v-model="feedbackContent"
                    />
                    <div class="si-box">
                        <LoaderButton content="送信する" @triggered="sendFeedback" :loading="sending" />
                    </div>
                </div>
            </div>
        </template>
    </ExpansionPanelItem>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from 'vue'
import { DateTime } from 'luxon'
import ExpansionPanelItem from '../Dashboard/ExpansionPanelItem.vue'
import ItemMenu from '../Global/ItemMenu.vue'
import Back from '../Icons/Back.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import LongInput from '../Form/LongInput.vue'
import { useApi } from '@/composables/api'

const props = defineProps<{
    item: any
    expandedId: number | null
    isAdmin: boolean
}>()

const emit = defineEmits<{
    edit: [item: any]
    delete: [item: any]
    close: []
}>()

const api = useApi()
const panelRef = ref<InstanceType<typeof ExpansionPanelItem> | null>(null)
const feedbackFormRef = ref<HTMLElement | null>(null)

const isExpanded = ref(props.expandedId === props.item.id)

onMounted(() => {
    if (isExpanded.value) {
        setTimeout(() => {
            panelRef.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
        }, 150)
    }
})

watch(() => props.expandedId, (val) => {
    const expanded = val === props.item.id
    if (expanded && !isExpanded.value) {
        // Became expanded — scroll into view after the panel animates open
        nextTick(() => {
            setTimeout(() => {
                panelRef.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
            }, 150)
        })
    }
    isExpanded.value = expanded
    if (!expanded) {
        showFeedbackForm.value = false
        feedbackContent.value = ''
        sending.value = false
    }
})

// Feedback state
const showFeedbackForm = ref(false)
const feedbackContent = ref('')
const sending = ref(false)

const onFeedback = async (resolved: boolean) => {
    if (!resolved) {
        showFeedbackForm.value = true
        nextTick(() => {
            feedbackFormRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
        })
    } else {
        await api.post('/support_resolve_decision', { id: props.item.id }, {
            toast: '送信しました。'
        })
        emit('close')
    }
}

const sendFeedback = async () => {
    sending.value = true
    await api.post('/support_feedback', {
        consultation_content: feedbackContent.value,
        contact_address: null,
        kind_value: 99,
        id: props.item.id
    }, {
        toast: '送信しました。'
    })
    sending.value = false
    emit('close')
}

const syncStatusLabel = (status: string) => {
    switch (status) {
        case 'syncing': return '同期中'
        case 'synced': return '同期済み'
        case 'error': return 'エラー'
        default: return '未同期'
    }
}

const syncStatusClass = (status: string) => {
    switch (status) {
        case 'syncing': return 'faq-sync-badge--syncing'
        case 'synced': return 'faq-sync-badge--synced'
        case 'error': return 'faq-sync-badge--error'
        default: return 'faq-sync-badge--pending'
    }
}
</script>
<style scoped lang="scss">
.faq-item {
    border-bottom: 1px solid rgba(128, 128, 128, 0.08);
    transition: background 0.15s;
    border-radius: 0 !important;
}
.faq-item:last-child { border-bottom: none; }

:deep(.faq-item-title) {
    width:calc(100% - 40px);
    padding: 16px 20px;
    gap: 12px;
}
:deep(.faq-item-panel.is-expanded) {
    background: var(--selected-background);
}
:deep(.faq-item-panel:not(.is-expanded):hover) {
    background: var(--bg3);
}
:deep(.faq-item-panel .expansion-panel-text__wrapper) {
    padding: 0 20px 18px 42px;
}

.faq-item-heading {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.faq-item-indicator {
    flex-shrink: 0;
    margin: auto 0;
    opacity: 0.55;
    transform: rotate(180deg);
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.faq-item-indicator--expanded {
    opacity: 1;
    transform: rotate(270deg);
}
.faq-item-menu { flex-shrink: 0; margin-top: 1px; }

.faq-item-q,
.faq-item-a {
    display: flex;
    align-items: baseline;
    gap: 10px;
    line-height: 1.6;
}
.faq-item-q {
    min-width: 0;
    flex: 1;
}
.faq-item-a {
    align-items: flex-start;
}
.faq-item-question {
    font-size: 14px;
    min-width: 0;
    word-break: break-word;
}
.faq-item-answer {
    font-size: 13px;
    opacity: 0.72;
    white-space: pre-wrap;
    word-break: break-word;
}

/* ── Sync badge ──────────────────────────────────────── */
.faq-sync-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 4px;
    padding: 2px;
    width: 19px;
    height: 19px;
    line-height: 1;
}
.faq-sync-badge--pending  { background: var(--bg3); color: var(--secondary-color); }
.faq-sync-badge--syncing  { background: rgba(59, 130, 246, 0.14); color: #2563eb; }
.faq-sync-badge--synced   { background: rgba(34, 197, 94, 0.14); color: #16a34a; }
.faq-sync-badge--error    { background: rgba(239, 68, 68, 0.14); color: #dc2626; }

/* ── Q / A badges ────────────────────────────────────── */
.faq-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 800;
    flex-shrink: 0;
    line-height: 1;
}
.faq-badge--q,
.faq-badge--a {
    background: var(--bg3);
    color: var(--primary-color);
}
.faq-badge--lg { width: 26px; height: 26px; font-size: 13px; border-radius: 7px; }

/* ── Inline detail body ──────────────────────────────── */
.faq-detail-body {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 0 20px 20px 20px;
}
.faqd-content {
    white-space: normal;
    font-size: 13px;
    line-height: 1.75;
}
.faqd-resolve {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}
.faqd-resolve-title {
    font-weight: 600;
    font-size: 14px;
    margin: 0;
}
.faqd-resolve-actions {
    display: flex;
    gap: 12px;
}

/* ── Mobile ──────────────────────────────────────────── */
@media screen and (max-width: 959px) {
    :deep(.faq-item-title) { padding: 14px 16px; }
    :deep(.faq-item-panel .expansion-panel-text__wrapper) {
        padding: 0 16px 16px 38px;
    }
    .faq-item-heading { gap: 10px; }
}
</style>