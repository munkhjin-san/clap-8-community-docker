<template>
    <BaseLayout
        :title="data.title" 
        :count="0" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >   
    <template #icon>
        <svg class="side-app-icon mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21.76 21.79" style="width: auto; height: 16px; min-width: 17px;">
            <path d="m21.54.32c-.25-.3-.67-.39-1.04-.25h0c-.84.33-1.68.66-2.51,1-.84.34-1.67.68-2.5,1.02-1.67.68-3.33,1.38-4.99,2.07l-4.99,2.08L.52,8.35c-.27.11-.48.37-.52.71s.18.7.51.84h.01c.69.31,1.39.6,2.08.89l2.09.86c.7.28,3.95,1.5,4.24,1.6s.6.06.86-.17,6.1-6.39,6.1-6.39c.23-.23.22-.61-.02-.83s-.6-.2-.83.02l-5.71,5.43c-.16.15-.39.19-.59.1-.42-.19-4.51-1.88-5.16-2.14-.16-.06-.16-.28,0-.35l2.59-1.04,5.01-2.02c1.67-.68,3.34-1.35,5.01-2.03.59-.24,1.74-.72,2.42-1,.2-.08.4.12.31.31l-3.04,7.42-2.04,5.01c-.36.9-.73,1.79-1.09,2.69-.06.15-.28.16-.34,0l-1.52-3.53c-.15-.31-.56-.46-.92-.32s-.5.5-.37.81l2.22,6c.1.26.33.48.65.54.39.07.78-.16.94-.53h0c.7-1.67,1.39-3.33,2.08-4.99l2.07-4.99L21.69,1.26c.12-.29.09-.66-.15-.95Z"></path>
        </svg>
    </template>
        <div v-if="!fullscreen" class="m-5">
            <p class="mb-3 text-[13px]">進捗・結果報告依頼（{{ data.data.length }}件）</p>
            <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                <ExpansionPanelItem
                    hide-actions
                    static
                    :tile="true"
                    class="rm-p"
                    v-for="(challenge, index) in data.data"
                    :key="challenge.id ?? index"
                    :value="challenge.id ?? index"
                >
                    <template #title="{ expanded }">
                        <PanelTitle :expanded="expanded">
                            {{ challenge.title }}
                        </PanelTitle>
                    </template>
                    <template #body>
                        <PanelData>
                            <p v-if="isOverdue(challenge)">チャレンジ期間が終了しました。結果を入力してください。</p>
                            <p v-else>チャレンジの締切が近づいています。進捗を入力してください。</p>
                            <div class="mt-3 text-right">
                                <router-link :to="{ name: 'post', query: { id: challenge.id, status: 5 } }">対応</router-link>
                            </div>
                        </PanelData>
                    </template>
                </ExpansionPanelItem>
            </ExpansionGrid>
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { Post } from '@/interface/postInterface';
import BaseLayout from './BaseLayout.vue';
import { DateTime } from 'luxon';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';

const props = defineProps<{
    data: {
        title: string,
        data: Post[],
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col?: string
    },
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const isOverdue = (challenge: Post) => {
    const endData = DateTime.fromISO(challenge.date_end);
    const now = DateTime.local();
    const diff = endData.diff(now, 'days').days;
    return diff < 0;
}
  

defineExpose({
    cardType: props.data.type,
})
</script>

