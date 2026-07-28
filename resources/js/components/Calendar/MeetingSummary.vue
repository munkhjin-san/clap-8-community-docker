<template>
    <Modal @close="emit('close')" :loader="initialLoader">
        <template #title>
            <p>会議記録 : {{ calendarRecord.title }}</p>
        </template>
        
        <template #content>
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

            <div v-if="activeTab === 'summary'" class="leading-normal whitespace-break-spaces">
                <p v-if="summariesData.length === 0" class="meeting-record-empty">
                    AIコンパニオン要約はありません。
                </p>

                <div v-for="(summary, index) in summariesData" :key="summary.id" class="mb-[30px] flex flex-col gap-[20px]">
                    <div class="flex justify-between sticky top-20 bg-[var(--background-color)]" :style="{zIndex: menuRef && menuRef.length && menuRef[index].active ? 7 : 6}">
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
                    
                    <div ref="summaryRef" v-if="expandedSummaries.includes(summary.id)">
                        <div class="flex flex-col gap-[10px] ml-[15px]" v-if="combinedSummary.id == summary.id">
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
                        
                        <div v-else-if="summary.edited_version" class="ml-[15px]">
                            <div v-html="fixedHtml(summary.edited_version)"></div>
                            
                        </div>
                        <div v-else class="flex flex-col gap-[20px] ml-[15px]">
                            <p>{{ summary.overview }}</p>

                            <div v-for="detail in summary.details" :key="`${summary.id}-${detail.label}`" class="mb-[20px]">
                                <h4>{{ detail.label }}</h4>
                                <p class="text-[15px] leading-[1.6] mt-[10px]">{{ detail.summary }}</p>

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
                        <button class="meeting-transcript__copy" @click="copyTranscript(transcript)">
                            コピー
                        </button>
                    </div>

                    <div v-if="expandedTranscripts.includes(transcript.id)" class="meeting-transcript__cues">
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
                    </div>
                </section>
            </div>
            
            <div class="si-box">
                <LoaderButton @triggered="emit('close')" :loading="false" content="閉じる"/>
            </div>

        </template>
    </Modal>

</template>
<script setup lang="ts">
import Modal from '../Global/Modal.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { ref, onMounted, useTemplateRef } from 'vue';
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
const { toast } = useDialog()
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
interface TranscriptData {
    id: number;
    meeting_id: string;
    meeting_uuid: string;
    meeting_start_time: string | null;
    downloaded_at: string | null;
    cues: TranscriptCue[];
}
interface MeetingRecordResponse {
    summaries: SummaryData[];
    transcripts: TranscriptData[];
}
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
const copyTranscript = async(transcript: TranscriptData) => {
    try {
        await navigator.clipboard.writeText(prepareTranscriptText(transcript))
        toast('コピーしました。')
    } catch (error) {
        console.error('Unable to copy transcript text to clipboard:', error)
    }
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
.meeting-record-tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-bottom: 28px;
    background: var(--bg-3);
}

.meeting-record-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 0;
    padding: 13px 10px;
    color: var(--text-color);
    border: 1px solid transparent;
}

.meeting-record-tab.active {
    border-color: var(--primary-color);
    background: var(--background-color);
}

.meeting-record-tab span {
    min-width: 20px;
    padding: 1px 6px;
    font-size: 11px;
    text-align: center;
    background: var(--bg-3);
}

.meeting-record-empty {
    padding: 28px 12px;
    color: gray;
    text-align: center;
}

.meeting-transcripts {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.meeting-transcript {
    border-bottom: 1px solid var(--border-color);
}

.meeting-transcript__header {
    position: sticky;
    top: 80px;
    z-index: 6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    background: var(--background-color);
}

.meeting-transcript__toggle {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 16px;
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

.meeting-transcript__copy {
    flex: none;
    padding: 6px 12px;
    font-size: 12px;
    background: var(--bg-3);
}

.meeting-transcript__cues {
    padding: 8px 0 24px 28px;
}

.meeting-transcript__cue {
    display: grid;
    grid-template-columns: 54px minmax(90px, 150px) minmax(0, 1fr);
    gap: 12px;
    padding: 10px 0;
    line-height: 1.6;
    border-bottom: 1px solid var(--border-color);
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

@media (max-width: 640px) {
    .meeting-record-tab {
        padding-inline: 6px;
        font-size: 12px;
    }

    .meeting-transcript__cues {
        padding-left: 0;
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
