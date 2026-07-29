<template>
    <Modal @close="emit('close')" :loader="initialLoader">
        <template #title>
            <p>会議記録 : {{ calendarRecord.title }}</p>
        </template>
        
        <template #content>
            <div class="meeting-record-content">
                <div class="meeting-record-tabs" role="tablist" aria-label="会議記録の種類">
                <button
                    class="meeting-record-tab"
                    :class="{ active: activeTab === 'summary' }"
                    role="tab"
                    :aria-selected="activeTab === 'summary'"
                    @click="activeTab = 'summary'"
                >
                    AIコンパニオン要約
                    <span>{{ summariesData.length }}</span>
                </button>
                <button
                    class="meeting-record-tab"
                    :class="{ active: activeTab === 'transcript' }"
                    role="tab"
                    :aria-selected="activeTab === 'transcript'"
                    @click="activeTab = 'transcript'"
                >
                    文字起こし
                    <span>{{ transcriptsData.length }}</span>
                </button>
                </div>

                <div v-if="activeTab === 'summary'" class="meeting-companion-summaries">
                <p v-if="summariesData.length === 0" class="meeting-record-empty">
                    AIコンパニオン要約はありません。
                </p>

                    <div v-for="(summary, index) in summariesData" :key="summary.id" class="meeting-companion-summary">
                    <div class="meeting-companion-summary__header" :style="{zIndex: menuRef && menuRef.length && menuRef[index].active ? 7 : 6}">
                        <label class="flex items-center gap-[20px] cursor-pointer">
                            <div :style="{ transition: 'transform 0.2s', transform: expandedSummaries.includes(summary.id) ? 'rotate(270deg)' : 'rotate(180deg)' }">
                                <Back size="12"/>
                            </div>
                            <div>
                                <h3>{{ summary.title }}</h3>
                                <p class="text-[12px] text-[gray]">{{ DateTime.fromISO(summary.created_at).toLocaleString(DateTime.DATETIME_MED) }}</p>
                            </div>                            
                            <input type="checkbox" class="hidden" :value="summary.id" v-model="expandedSummaries"/>
                        </label>
                        <div class="flex gap-[10px]">
                            <div class="h-[25px]">
                                <TTSPlayer
                                    :text="prepareText(summary)" 
                                    :key="`tts_message_${summary.id}`"
                                />
                            </div>
                            <ItemMenu 
                                ref="menuRef"
                                :items="[
                                    { title: '編集', action: () => editSummary(summary) },
                                    { title: 'コピー', action: () => copySummary(summary) },
                                    { title: 'シェア', action: () => {}, children: [{ title: 'チャット', action: () => shareSummary(summary)}] },
                                    { title: '削除', action: () => deleteSummary(summary) },
                                ]"
                            />
                        </div>
                        
                    </div>
                    
                    <div ref="summaryRef" v-if="expandedSummaries.includes(summary.id)" class="meeting-companion-summary__body">
                        <div class="flex flex-col gap-[10px]" v-if="combinedSummary.id == summary.id">
                            <RichEditor 
                                ref="summaryEditor"
                                :initila-value="combinedSummary.html"
                            />
                            <div class="flex gap-[10px]">
                                <CommandButton 
                                    :buttons="[
                                        { title: '保存', action: () => saveEditedVersion(summary.id) },
                                        { title: 'キャンセル', action: () => combinedSummary = { id: null, html: '' } },
                                    ]"
                                />
                            </div>
                        </div>
                        
                        <div v-else-if="summary.edited_version">
                            <div v-html="fixedHtml(summary.edited_version)"></div>
                            
                        </div>
                        <div v-else class="meeting-companion-summary__content">
                            <p>{{ summary.overview }}</p>

                            <div v-for="detail in summary.details" :key="`${summary.id}-${detail.label}`" class="meeting-companion-summary__detail">
                                <h4>{{ detail.label }}</h4>
                                <p>{{ detail.summary }}</p>

                            </div>
                            <div v-if="summary.steps.length > 0">
                                <h4>次のステップ </h4>
                                <ul class="list-disc pl-[20px]">
                                    <li v-for="step in summary.steps" :key="step.content">{{ step.content }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <div v-else class="meeting-transcripts">
                <p v-if="transcriptsData.length === 0" class="meeting-record-empty">
                    文字起こしはありません。
                </p>

                <section
                    v-for="(transcript, index) in transcriptsData"
                    :key="transcript.id"
                    class="meeting-transcript"
                >
                    <div class="meeting-transcript__header">
                        <button
                            class="meeting-transcript__toggle"
                            :aria-expanded="expandedTranscripts.includes(transcript.id)"
                            @click="toggleTranscript(transcript.id)"
                        >
                            <span
                                class="meeting-transcript__arrow"
                                :class="{ active: expandedTranscripts.includes(transcript.id) }"
                            >
                                <Back size="12"/>
                            </span>
                            <span>
                                <strong>文字起こし{{ transcriptsData.length > 1 ? ` ${index + 1}` : '' }}</strong>
                                <small>{{ formatDateTime(transcript.meeting_start_time) }}</small>
                            </span>
                        </button>
                        <div class="meeting-transcript__actions">
                            <button
                                class="meeting-transcript__ai"
                                :disabled="isAiSummaryBusy(transcript)"
                                @click="requestAiSummary(transcript)"
                            >
                                <span
                                    v-if="isAiSummaryBusy(transcript)"
                                    class="meeting-transcript__spinner"
                                    aria-hidden="true"
                                ></span>
                                AI要約
                            </button>
                        </div>
                    </div>

                    <div v-if="expandedTranscripts.includes(transcript.id)" class="meeting-transcript__cues">
                        <section
                            v-if="transcript.ai_summary"
                            class="transcript-ai-summary"
                        >
                            <div class="transcript-ai-summary__header">
                                <div>
                                    <span class="transcript-section-label">
                                        <span class="transcript-section-label__mark transcript-section-label__mark--ai">AI</span>
                                        AIによる要約
                                    </span>
                                    <small v-if="transcript.ai_summary.completed_at">
                                        {{ formatDateTime(transcript.ai_summary.completed_at) }}
                                    </small>
                                </div>
                                <div class="transcript-ai-summary__header-actions">
                                    <span
                                        v-if="isAiSummaryProcessing(transcript.ai_summary)"
                                        class="transcript-ai-summary__status"
                                    >
                                        要約を作成しています
                                    </span>
                                    <button
                                        v-if="transcript.ai_summary.content"
                                        class="transcript-section-copy"
                                        @click="copyTranscriptAiSummary(transcript.ai_summary)"
                                    >
                                        コピー
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="transcript.ai_summary.content"
                                class="transcript-ai-summary__content"
                            >
                                <p class="transcript-ai-summary__overview">
                                    {{ transcript.ai_summary.content.overview }}
                                </p>

                                <div v-if="transcript.ai_summary.content.topics.length" class="transcript-ai-summary__section">
                                    <h4>議題別まとめ</h4>
                                    <article
                                        v-for="(topic, topicIndex) in transcript.ai_summary.content.topics"
                                        :key="`${transcript.id}-topic-${topicIndex}`"
                                    >
                                        <strong>{{ topic.title }}</strong>
                                        <p>{{ topic.summary }}</p>
                                        <div class="transcript-ai-summary__evidence">
                                            <span
                                                v-for="evidence in topic.evidence"
                                                :key="`${evidence.segment_id}-${evidence.timestamp}`"
                                                :title="evidence.quote"
                                            >
                                                {{ evidence.timestamp }}
                                            </span>
                                        </div>
                                    </article>
                                </div>

                                <div
                                    v-if="transcript.ai_summary.content.decisions.length"
                                    class="transcript-ai-summary__section"
                                >
                                    <h4>決定事項</h4>
                                    <ul>
                                        <li
                                            v-for="(decision, decisionIndex) in transcript.ai_summary.content.decisions"
                                            :key="`${transcript.id}-decision-${decisionIndex}`"
                                        >
                                            {{ decision.content }}
                                            <EvidenceTimes :evidence="decision.evidence"/>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    v-if="transcript.ai_summary.content.action_items.length"
                                    class="transcript-ai-summary__section"
                                >
                                    <h4>アクション項目</h4>
                                    <ul>
                                        <li
                                            v-for="(action, actionIndex) in transcript.ai_summary.content.action_items"
                                            :key="`${transcript.id}-action-${actionIndex}`"
                                        >
                                            <strong>{{ action.task }}</strong>
                                            <small v-if="action.owner || action.due_date">
                                                <template v-if="action.owner">担当：{{ action.owner }}</template>
                                                <template v-if="action.owner && action.due_date"> ／ </template>
                                                <template v-if="action.due_date">期限：{{ action.due_date }}</template>
                                            </small>
                                            <EvidenceTimes :evidence="action.evidence"/>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    v-if="transcript.ai_summary.content.open_questions.length"
                                    class="transcript-ai-summary__section"
                                >
                                    <h4>未解決事項</h4>
                                    <ul>
                                        <li
                                            v-for="(question, questionIndex) in transcript.ai_summary.content.open_questions"
                                            :key="`${transcript.id}-question-${questionIndex}`"
                                        >
                                            {{ question.content }}
                                            <EvidenceTimes :evidence="question.evidence"/>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    v-if="transcript.ai_summary.content.risks.length"
                                    class="transcript-ai-summary__section"
                                >
                                    <h4>懸念・リスク</h4>
                                    <ul>
                                        <li
                                            v-for="(risk, riskIndex) in transcript.ai_summary.content.risks"
                                            :key="`${transcript.id}-risk-${riskIndex}`"
                                        >
                                            {{ risk.content }}
                                            <EvidenceTimes :evidence="risk.evidence"/>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <p
                                v-if="transcript.ai_summary.status === 'failed'"
                                class="transcript-ai-summary__error"
                            >
                                {{ transcript.ai_summary.last_error || 'AI要約の作成に失敗しました。' }}
                            </p>
                        </section>

                        <section class="meeting-transcript-source">
                            <div class="meeting-transcript-source__header">
                                <span class="transcript-section-label">
                                    <span class="transcript-section-label__mark">原文</span>
                                    文字起こし原文
                                </span>
                                <button
                                    class="transcript-section-copy"
                                    :disabled="transcript.cues.length === 0"
                                    @click="copyTranscript(transcript)"
                                >
                                    コピー
                                </button>
                            </div>
                            <div
                                v-for="(cue, cueIndex) in transcript.cues"
                                :key="`${transcript.id}-${cue.start}-${cueIndex}`"
                                class="meeting-transcript__cue"
                            >
                                <time>{{ formatCueTime(cue.start) }}</time>
                                <strong v-if="cue.speaker">{{ cue.speaker }}</strong>
                                <p>{{ cue.text }}</p>
                            </div>
                            <p v-if="transcript.cues.length === 0" class="meeting-record-empty">
                                表示できる文字起こしデータがありません。
                            </p>
                        </section>
                    </div>
                </section>
                </div>
            
                <div class="meeting-record-close">
                    <LoaderButton @triggered="emit('close')" :loading="false" content="閉じる"/>
                </div>
            </div>

        </template>
    </Modal>

</template>
<script setup lang="ts">
import Modal from '../Global/Modal.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { defineComponent, h, onMounted, onUnmounted, ref, useTemplateRef, type PropType } from 'vue';
import { DateTime } from 'luxon';
import Back from '../Icons/Back.vue';
import ItemMenu from '../Global/ItemMenu.vue';
import RichEditor from '../Global/RichEditor.vue';
import CommandButton from '../Global/CommandButton.vue';
import { useSharingDataStore } from '@/store/sharingData';
import { useRouter } from 'vue-router';
import { ComponentExposed } from 'vue-component-type-helpers';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import TTSPlayer from '../Global/TTSPlayer.vue';
const api = useApi()
const props = defineProps(['calendarRecord']);
const emit = defineEmits(['close']);
const summariesData = ref<SummaryData[]>([])
const transcriptsData = ref<TranscriptData[]>([])
const initialLoader = ref(true)
const activeTab = ref<'summary' | 'transcript'>('summary')
const expandedSummaries = ref<number[]>([])
const expandedTranscripts = ref<number[]>([])
const summaryRef = useTemplateRef<HTMLElement[]>('summaryRef')
const summaryEditor = useTemplateRef('summaryEditor')
const sharingData = useSharingDataStore()
const router = useRouter()
const menuRef = useTemplateRef<ComponentExposed<typeof ItemMenu>[]>('menuRef')
const { toast, ask, ping } = useDialog()
const requestingAiSummaries = ref<number[]>([])
let summaryPollingTimer: ReturnType<typeof setTimeout> | null = null
const combinedSummary = ref<{
    id: number | null;
    html: string;
}>({
    id: null,
    html: ''
})

onMounted(() => {
    getMeetingRecords(0)
})
onUnmounted(() => {
    if (summaryPollingTimer) {
        clearTimeout(summaryPollingTimer)
    }
})
interface SummaryData {
    id: number;
    title: string;
    created_at: string;
    overview: string;
    edited_version: string;
    details: {
        label: string;
        summary: string;
    }[];
    steps: {
        content: string;
    }[];
}
interface TranscriptCue {
    start: string;
    end: string;
    speaker: string | null;
    text: string;
}
interface TranscriptEvidence {
    segment_id: string;
    timestamp: string;
    quote: string;
}
interface TranscriptSummaryItem {
    content: string;
    evidence: TranscriptEvidence[];
}
interface TranscriptAiSummaryContent {
    overview: string;
    topics: {
        title: string;
        summary: string;
        evidence: TranscriptEvidence[];
    }[];
    decisions: TranscriptSummaryItem[];
    action_items: {
        task: string;
        owner: string | null;
        due_date: string | null;
        evidence: TranscriptEvidence[];
    }[];
    open_questions: TranscriptSummaryItem[];
    risks: TranscriptSummaryItem[];
}
interface TranscriptAiSummary {
    status: 'pending' | 'processing' | 'completed' | 'failed';
    content: TranscriptAiSummaryContent | null;
    model: string | null;
    prompt_version: string;
    last_error: string | null;
    requested_at: string | null;
    completed_at: string | null;
}
interface TranscriptData {
    id: number;
    meeting_id: string;
    meeting_uuid: string;
    meeting_start_time: string | null;
    downloaded_at: string | null;
    cues: TranscriptCue[];
    ai_summary: TranscriptAiSummary | null;
}
interface MeetingRecordResponse {
    summaries: SummaryData[];
    transcripts: TranscriptData[];
}
interface TranscriptAiSummaryResponse {
    ai_summary: TranscriptAiSummary;
    message: string;
}
const EvidenceTimes = defineComponent({
    name: 'EvidenceTimes',
    props: {
        evidence: {
            type: Array as PropType<TranscriptEvidence[]>,
            required: true,
        },
    },
    setup(componentProps) {
        return () => h(
            'span',
            { class: 'transcript-ai-summary__evidence' },
            componentProps.evidence.map(item => h(
                'span',
                {
                    key: `${item.segment_id}-${item.timestamp}`,
                    title: item.quote,
                },
                item.timestamp,
            )),
        )
    },
})
const fixedHtml = (html: string) => {
    return html.replace(/<p>\s*<\/p>/g, '<p>&nbsp;</p>')
}
const getMeetingRecords = async(counter: number) => {
    try {
        const response = await api.get('/get_schedule_summaries', {
            id: props.calendarRecord.id
        }) as MeetingRecordResponse
        summariesData.value = response.summaries
        transcriptsData.value = response.transcripts

        if (counter === 0) {
            if (summariesData.value.length > 0) {
                expandedSummaries.value.push(summariesData.value[0].id)
            } else if (transcriptsData.value.length > 0) {
                activeTab.value = 'transcript'
            }

            if (transcriptsData.value.length > 0) {
                expandedTranscripts.value.push(transcriptsData.value[0].id)
            }
        }
        syncSummaryPolling()
    } finally {
        initialLoader.value = false
    }
}
const editSummary = (summary: SummaryData) => {
    if (!expandedSummaries.value.includes(summary.id)) {
        expandedSummaries.value.push(summary.id)
    }
    combinedSummary.value.id = summary.id
    if (summary.edited_version) {
        combinedSummary.value.html = summary.edited_version
    } else {
        let string = document.createElement('div')
        let tag = document.createElement('p')
        tag.innerText = summary.overview
        string.appendChild(tag)
        string.appendChild(document.createElement('p'))
        string.appendChild(document.createElement('p'))
        summary.details.forEach(detail => {
            let tag1 = document.createElement('h4')
            tag1.innerText = detail.label
            string.appendChild(tag1)
            string.appendChild(document.createElement('p'))
            let tag2 = document.createElement('p')
            tag2.innerText = detail.summary
            string.appendChild(tag2)
            string.appendChild(document.createElement('p'))
            string.appendChild(document.createElement('p'))
        })
        if (summary.steps.length > 0) {
            let tag3 = document.createElement('h4')
            tag3.innerText = '次のステップ'
            string.appendChild(tag3)
            let tag4 = document.createElement('ul')
            summary.steps.forEach(step => {
                
                let tag5 = document.createElement('li')
                tag5.innerText = step.content
                tag4.appendChild(tag5)
            })
            string.appendChild(tag4)
        }
        combinedSummary.value.html = string.innerHTML
    }
    
}
const saveEditedVersion = async(id: number) => {

    let summaryEdited = summaryEditor.value?.[0]?.editor?.getHTML() || combinedSummary.value.html
    await api.put('/save_edited_summary', {
        id,
        html: summaryEdited
    }, {
        toast: '保存しました。'
    })
    getMeetingRecords(1)
    combinedSummary.value = { id: null, html: '' }
}
const copySummary = (summary: SummaryData) => {
    let textToCopy = prepareText(summary)
    navigator.clipboard.writeText(textToCopy)
    .then(() => {
        toast('コピーしました。')
    })
    .catch((error) => {
        console.error('Unable to copy text to clipboard:', error);
    });
}
const deleteSummary = async(summary: SummaryData) => {
    await api.del('/delete_schedule_summary', { id: summary.id }, { toast: '削除しました。', ask: '削除しますか？' })  
    getMeetingRecords(1)
}
const shareSummary = (summary: SummaryData) => {
    let textToShare = prepareText(summary)
    const shareData = {
        active: true,
        message: textToShare,
        title: '',
        text: textToShare,
        files: [],
        from: 'schedule',
        to: 'board',
        drag: false,
        instruction: '送る先のチャットを選択してください'
    }
    sharingData.setSharingData(shareData)
    router.push({ name: 'board'})
}
const prepareText = (summary: SummaryData) => {
    let text = ''
    if (summary.edited_version) {
        const tempDiv = document.createElement('div')
        tempDiv.innerHTML = summary.edited_version.replace(/<p>\s*<\/p>/g, '\n\n')
        text = tempDiv.innerText
    } else {
        text = summary.overview
        summary.details.forEach(detail => {
            text += '\n\n' + detail.label + '\n\n' + detail.summary
        })
        if (summary.steps.length > 0) {
            text += '\n\n次のステップ'
            summary.steps.forEach(step => {
                text += '\n' + step.content
            })
        }
    }
    return text
}
const toggleTranscript = (id: number) => {
    expandedTranscripts.value = expandedTranscripts.value.includes(id)
        ? expandedTranscripts.value.filter(transcriptId => transcriptId !== id)
        : [...expandedTranscripts.value, id]
}
const prepareTranscriptText = (transcript: TranscriptData) => {
    return transcript.cues.map(cue => {
        const speaker = cue.speaker ? `${cue.speaker}: ` : ''
        return `[${formatCueTime(cue.start)}] ${speaker}${cue.text}`
    }).join('\n')
}
const prepareTranscriptEvidenceText = (evidence: TranscriptEvidence[]) => {
    if (evidence.length === 0) return ''

    return evidence
        .map(item => `  根拠 [${item.timestamp}] ${item.quote}`)
        .join('\n')
}
const prepareTranscriptAiSummaryText = (summary: TranscriptAiSummary) => {
    const content = summary.content
    if (!content) return ''

    const sections = ['AIによる要約', content.overview]

    if (content.topics.length > 0) {
        sections.push(
            '議題別まとめ\n'
            + content.topics.map(topic => [
                topic.title,
                topic.summary,
                prepareTranscriptEvidenceText(topic.evidence),
            ].filter(Boolean).join('\n')).join('\n\n'),
        )
    }

    const appendItems = (title: string, items: TranscriptSummaryItem[]) => {
        if (items.length === 0) return

        sections.push(
            `${title}\n`
            + items.map(item => [
                `・${item.content}`,
                prepareTranscriptEvidenceText(item.evidence),
            ].filter(Boolean).join('\n')).join('\n'),
        )
    }

    appendItems('決定事項', content.decisions)

    if (content.action_items.length > 0) {
        sections.push(
            'アクション項目\n'
            + content.action_items.map(action => {
                const metadata = [
                    action.owner ? `担当：${action.owner}` : '',
                    action.due_date ? `期限：${action.due_date}` : '',
                ].filter(Boolean).join(' ／ ')

                return [
                    `・${action.task}`,
                    metadata,
                    prepareTranscriptEvidenceText(action.evidence),
                ].filter(Boolean).join('\n')
            }).join('\n'),
        )
    }

    appendItems('未解決事項', content.open_questions)
    appendItems('懸念・リスク', content.risks)

    return sections.join('\n\n')
}
const copyTranscriptAiSummary = async(summary: TranscriptAiSummary) => {
    try {
        await navigator.clipboard.writeText(prepareTranscriptAiSummaryText(summary))
        toast('AI要約をコピーしました。')
    } catch (error) {
        console.error('Unable to copy transcript AI summary:', error)
        ping('AI要約をコピーできませんでした。')
    }
}
const copyTranscript = async(transcript: TranscriptData) => {
    try {
        await navigator.clipboard.writeText(prepareTranscriptText(transcript))
        toast('文字起こし原文をコピーしました。')
    } catch (error) {
        console.error('Unable to copy transcript text to clipboard:', error)
        ping('文字起こし原文をコピーできませんでした。')
    }
}
const isAiSummaryProcessing = (summary: TranscriptAiSummary | null) => {
    return summary?.status === 'pending' || summary?.status === 'processing'
}
const isAiSummaryBusy = (transcript: TranscriptData) => {
    return requestingAiSummaries.value.includes(transcript.id)
        || isAiSummaryProcessing(transcript.ai_summary)
}
const requestAiSummary = async(transcript: TranscriptData) => {
    if (isAiSummaryBusy(transcript)) return

    const regenerate = Boolean(transcript.ai_summary?.content)
    if (regenerate) {
        const answer = await ask(
            '現在のAI要約は新しい結果に置き換えられます。再生成しますか？',
            {
                answers: [
                    { value: true, label: '再生成する' },
                    { value: false, label: 'キャンセル' },
                ],
            },
        )
        if (!answer.value) return
    }

    requestingAiSummaries.value.push(transcript.id)
    try {
        const response = await api.post('/generate_transcript_ai_summary', {
            transcript_id: transcript.id,
            regenerate,
        }, {
            silent: true,
        }) as TranscriptAiSummaryResponse

        transcript.ai_summary = response.ai_summary
        toast('AI要約の作成を開始しました。')
        syncSummaryPolling()
    } catch (error: unknown) {
        const apiError = error as { response?: { data?: { message?: string } } }
        ping(apiError.response?.data?.message || 'AI要約を開始できませんでした。')
    } finally {
        requestingAiSummaries.value = requestingAiSummaries.value.filter(id => id !== transcript.id)
    }
}
const syncSummaryPolling = () => {
    if (summaryPollingTimer) {
        clearTimeout(summaryPollingTimer)
        summaryPollingTimer = null
    }

    if (!transcriptsData.value.some(transcript => isAiSummaryProcessing(transcript.ai_summary))) {
        return
    }

    summaryPollingTimer = setTimeout(() => {
        getMeetingRecords(1)
    }, 2500)
}
const formatDateTime = (value: string | null) => {
    if (!value) return ''

    return DateTime.fromISO(value).toLocaleString(DateTime.DATETIME_MED)
}
const formatCueTime = (value: string) => {
    return value.replace(/^00:/, '').replace(/\.\d{3}$/, '')
}
</script>
<style scoped>
.meeting-record-content {
    font-size: 14px;
    line-height: 1.55;
}

.meeting-record-content strong,
.meeting-record-content h3,
.meeting-record-content h4 {
    font-weight: 400;
}

.meeting-record-tabs {
    display: flex;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--normalBorder);
}

.meeting-record-tab {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-width: 0;
    margin-bottom: -1px;
    padding: 8px 8px 9px;
    color: var(--primary-color);
    border-bottom: 2px solid transparent;
    font-size: 14px;
    opacity: 0.55;
    transition: opacity 0.15s, border-color 0.15s;
}

.meeting-record-tab.active {
    border-bottom-color: var(--primary-color);
    opacity: 1;
}

.meeting-record-tab span {
    min-width: 18px;
    padding: 0 5px;
    border-radius: 10px;
    font-size: 11px;
    text-align: center;
    background: var(--bg3);
}

.meeting-record-empty {
    padding: 20px 10px;
    color: gray;
    text-align: center;
}

.meeting-companion-summaries {
    white-space: break-spaces;
}

.meeting-companion-summary {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 6px 0 14px;
}

.meeting-companion-summary + .meeting-companion-summary {
    padding-top: 14px;
}

.meeting-companion-summary__header {
    position: sticky;
    top: 80px;
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    background: var(--background-color);
}

.meeting-companion-summary__header label {
    gap: 12px;
}

.meeting-companion-summary__body {
    padding-left: 22px;
}

.meeting-companion-summary__content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.meeting-companion-summary__detail p {
    margin-top: 4px;
    line-height: 1.55;
}

.meeting-transcripts {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.meeting-transcript__header {
    position: sticky;
    top: 80px;
    z-index: 6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 6px 0;
    background: var(--background-color);
}

.meeting-transcript__toggle {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 11px;
    font-size: 14px;
    text-align: left;
}

.meeting-transcript__toggle small {
    display: block;
    margin-top: 3px;
    color: gray;
    font-size: 12px;
}

.meeting-transcript__arrow {
    display: inline-flex;
    transition: transform 0.2s;
    transform: rotate(180deg);
}

.meeting-transcript__arrow.active {
    transform: rotate(270deg);
}

.meeting-transcript__actions {
    display: flex;
    flex: none;
    align-items: center;
    gap: 8px;
}

.meeting-transcript__ai {
    display: inline-flex;
    min-width: 68px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 5px 10px;
    color: var(--background-color);
    font-size: 13px;
    background: var(--primary-color);
}

.meeting-transcript__ai:disabled {
    cursor: wait;
    opacity: 0.55;
}

.meeting-transcript__spinner {
    width: 12px;
    height: 12px;
    border: 2px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: transcript-summary-spin 0.7s linear infinite;
}

@keyframes transcript-summary-spin {
    to {
        transform: rotate(360deg);
    }
}

.meeting-transcript__cues {
    padding: 4px 0 12px 22px;
}

.transcript-ai-summary {
    margin-bottom: 12px;
    padding: 13px 14px;
    line-height: 1.55;
    background: var(--bg3);
}

.transcript-section-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.transcript-section-label__mark {
    min-width: 30px;
    padding: 2px 5px;
    color: gray;
    font-size: 10px;
    line-height: 1.4;
    text-align: center;
    background: var(--bg3);
}

.transcript-section-label__mark--ai {
    color: var(--background-color);
    background: var(--primary-color);
}

.transcript-ai-summary__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.transcript-ai-summary__header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.transcript-ai-summary__header small {
    display: block;
    margin-top: 2px;
    color: gray;
    font-size: 11px;
}

.transcript-ai-summary__status {
    color: gray;
    font-size: 12px;
}

.transcript-ai-summary__status::before {
    display: inline-block;
    width: 7px;
    height: 7px;
    margin-right: 7px;
    border-radius: 50%;
    background: var(--primary-color);
    content: "";
    animation: transcript-summary-pulse 1.2s ease-in-out infinite;
}

@keyframes transcript-summary-pulse {
    50% {
        opacity: 0.25;
    }
}

.transcript-ai-summary__content {
    display: flex;
    flex-direction: column;
    gap: 13px;
}

.transcript-ai-summary__overview {
    font-size: 14px;
    white-space: break-spaces;
}

.transcript-ai-summary__section h4 {
    margin-bottom: 4px;
    font-size: 14px;
    color: gray;
}

.transcript-ai-summary__section article,
.transcript-ai-summary__section li {
    padding: 6px 0;
}

.transcript-ai-summary__section article p {
    margin-top: 4px;
}

.transcript-ai-summary__section ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.transcript-ai-summary__section li > small {
    display: block;
    margin-top: 3px;
    color: gray;
    font-size: 11px;
}

.transcript-ai-summary__evidence {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 6px;
}

.transcript-ai-summary__evidence > span {
    padding: 1px 6px;
    color: gray;
    font-size: 10px;
    font-variant-numeric: tabular-nums;
    background: var(--background-color);
}

.transcript-ai-summary__error {
    margin-top: 12px;
    color: tomato;
    font-size: 12px;
    white-space: break-spaces;
}

.meeting-transcript-source {
    margin-top: 18px;
}

.meeting-transcript-source__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
}

.transcript-section-copy {
    flex: none;
    padding: 4px 9px;
    color: var(--primary-color);
    font-size: 12px;
    background: var(--bg3);
}

.transcript-ai-summary .transcript-section-copy {
    background: var(--background-color);
}

.transcript-section-copy:disabled {
    cursor: not-allowed;
    opacity: 0.4;
}

.meeting-transcript__cue {
    display: grid;
    grid-template-columns: 54px minmax(90px, 150px) minmax(0, 1fr);
    gap: 12px;
    padding: 7px 0;
    line-height: 1.55;
}

.meeting-transcript__cue time {
    color: gray;
    font-size: 12px;
    font-variant-numeric: tabular-nums;
}

.meeting-transcript__cue p {
    min-width: 0;
    white-space: break-spaces;
    overflow-wrap: anywhere;
}

.meeting-record-close {
    display: flex;
    justify-content: center;
    padding-top: 14px;
}

@media (max-width: 640px) {
    .meeting-record-tab {
        padding-inline: 4px;
        font-size: 13px;
    }

    .meeting-transcript__cues {
        padding-left: 0;
    }

    .meeting-transcript__header {
        align-items: flex-start;
    }

    .meeting-transcript__actions {
        gap: 5px;
    }

    .meeting-transcript__ai {
        padding-inline: 8px;
    }

    .transcript-ai-summary {
        padding: 12px;
    }

    .meeting-transcript__cue {
        grid-template-columns: 48px minmax(0, 1fr);
        gap: 6px 10px;
    }

    .meeting-transcript__cue p {
        grid-column: 2;
    }
}
</style>
