<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>要約 : {{ calendarRecord.title }}</p>
        </template>
        
        <template #content>
            <Transition name="modalFade">
                <div class="cal-month-loader" style="height: calc(100% - 80px); top: 80px;" v-if="initialLoader">
                    <div id="loaderMini">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
            </Transition>
            
            <div class="leading-normal whitespace-break-spaces">
                
                <div v-for="(summary, index) in summariesData" class="mb-[30px] flex flex-col gap-[20px]">
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
                            <div class="flex gap-[10px]" v-if="ttsStore.active && ttsStore.id == summary.id">
                                <CommandButton 
                                    :buttons="[
                                        { title: ttsStore.play ? '一時停止' : '再開する', action: () => stopPlay(summary.id) },
                                        { title: 'ストップ', action: () => endPlay()}
                                    ]"
                                />
                            </div>
                            <ItemMenu 
                                ref="menuRef"
                                :items="[
                                    { title: '編集', action: () => editSummary(summary) },
                                    { title: 'コピー', action: () => copySummary(summary) },
                                    { title: 'シェア', action: () => {}, children: [{ title: 'ボード', action: () => shareSummary(summary)}] },
                                    { title: '読み上げる', action: () => convertToSpeech(prepareText(summary), summary.id)},
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

                            <div v-for="detail in summary.details" class="mb-[20px]">
                                <h4>{{ detail.label }}</h4>
                                <p class="text-[15px] leading-[1.6] mt-[10px]">{{ detail.summary }}</p>

                            </div>
                            <div v-if="summary.steps.length > 0">
                                <h4>次のステップ </h4>
                                <ul class="list-disc pl-[20px]">
                                    <li v-for="step in summary.steps">{{ step.content }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    

                </div>
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
import { ref, onMounted, inject, useTemplateRef } from 'vue';
import { DateTime } from 'luxon';
import { DialogMethods } from '@/interface/globalInterface';
import axios from 'axios';
import Back from '../Icons/Back.vue';
import ItemMenu from '../Global/ItemMenu.vue';
import RichEditor from '../Global/RichEditor.vue';
import CommandButton from '../Global/CommandButton.vue';
import { useSharingDataStore } from '@/store/sharingData';
import { useRouter } from 'vue-router';
import { convertToSpeech, endPlay, stopPlay } from '@/utils/tts';
import { useTtsStore } from '@/store/ttsStore';
import { ComponentExposed } from 'vue-component-type-helpers';
const props = defineProps(['calendarRecord']);
const emit = defineEmits(['close']);
const summariesData = ref<SummaryData[]>([])
const initialLoader = ref(true)
const expandedSummaries = ref<number[]>([])
const summaryRef = useTemplateRef<HTMLElement[]>('summaryRef')
const summaryEditor = useTemplateRef('summaryEditor')
const sharingData = useSharingDataStore()
const router = useRouter()
const ttsStore = useTtsStore()
const menuRef = useTemplateRef<ComponentExposed<typeof ItemMenu>[]>('menuRef')
const combinedSummary = ref<{
    id: number | null;
    html: string;
}>({
    id: null,
    html: ''
})
const { notify, info, confirm } = inject('dialog') as DialogMethods
onMounted(() => {
    getSummareis(0)
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
const fixedHtml = (html: string) => {
    return html.replace(/<p>\s*<\/p>/g, '<p>&nbsp;</p>')
}
const getSummareis = async(counter:number) => {
    try{
        
        summariesData.value = await axios.get('/get_schedule_summaries', { params: {
            id: props.calendarRecord.id
        }}).then(response => response.data)

        if(counter == 0 && summariesData.value.length > 0){
            expandedSummaries.value.push(summariesData.value[0].id)
        }
        initialLoader.value = false
 
    }catch(e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        emit('close')
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
    try {
        let summaryEdited = summaryEditor.value?.[0]?.editor?.getHTML() || combinedSummary.value.html
        await axios.put('/save_edited_summary', {
            id,
            html: summaryEdited
        })
        info('保存しました。')
        getSummareis(1)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        combinedSummary.value = { id: null, html: '' }
    }
}
const copySummary = (summary: SummaryData) => {
    let textToCopy = prepareText(summary)
    navigator.clipboard.writeText(textToCopy)
    .then(() => {
        info('コピーしました。')
    })
    .catch((error) => {
        console.error('Unable to copy text to clipboard:', error);
    });
}
const deleteSummary = async(summary: SummaryData) => {
    const answer = await confirm('削除しますか？')
    if(!answer.value) return
    try {
        await axios.delete('/delete_schedule_summary', { params: {
            id: summary.id
        }}).then(() => {
            info('削除しました。')
            getSummareis(1)
        })        
        
    } catch (e) {   
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
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
        instruction: '送る先のボードを選択してください'
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
</script>