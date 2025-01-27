<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>要約 : {{ calendarRecord.title }}</p>
        </template>
        <template #content>
            <Transition name="modalFade">
                <div class="cal-month-loader" v-if="initialLoader">
                    <div id="loaderMini">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
            </Transition>
            <div class="leading-normal whitespace-break-spaces">
                <div v-for="summary in summariesData" class="mb-[30px] flex flex-col gap-[20px]">
                    <div>
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
                    </div>
                    <div v-if="expandedSummaries.includes(summary.id)" class="flex flex-col gap-[20px] ml-[15px]">
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
            <div class="si-box">
                <LoaderButton @triggered="emit('close')" :loading="false" content="閉じる"/>
            </div>

        </template>
    </Modal>

</template>
<script setup lang="ts">
import Modal from '../Global/Modal.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { ref, onMounted, inject } from 'vue';
import { DateTime } from 'luxon';
import { DialogMethods } from '@/interface/globalInterface';
import axios from 'axios';
import CloseIcon from '../Form/CloseIcon.vue';
import Back from '../Icons/Back.vue';

const props = defineProps(['calendarRecord']);
const emit = defineEmits(['close']);
const summariesData = ref<SummaryData[]>([])
const initialLoader = ref(true)
const expandedSummaries = ref<number[]>([])
const { notify } = inject('dialog') as DialogMethods
onMounted(() => {
    getSummareis(0)
})
interface SummaryData {
    id: number;
    title: string;
    created_at: string;
    overview: string;
    details: {
        label: string;
        summary: string;
    }[];
    steps: {
        content: string;
    }[];
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

</script>