<template>
    <BaseLayout
        :title="data.title" 
        :count="data.data.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >   
    <template #icon>
        <svg class="side-app-icon mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27.43 22.08" style="height: 16px; overflow: visible;">
            <path d="m9.48,19.15l-1.14-1.83-1.15-1.82c-.38-.61-.77-1.21-1.16-1.81-.06-.1-.15-.19-.26-.26-.4-.26-.93-.14-1.19.26h0c-.39.6-.78,1.21-1.16,1.81-.39.61-.76,1.22-1.14,1.82-.38.61-.75,1.22-1.13,1.83-.37.61-.93,1.55-1.08,1.86s-.12.75.22.95.76,0,.96-.26.82-1.12,1.22-1.7c.4-.6.81-1.19,1.2-1.79.4-.6.8-1.19,1.19-1.8.08-.11.15-.23.23-.34.11-.16.34-.16.45,0,.07.11.15.23.22.34l1.18,1.8,1.19,1.79c.4.59,1,1.48,1.21,1.74s.68.46,1.02.24.38-.6.17-1.04c-.19-.4-.7-1.19-1.07-1.79Z"></path>
            <path d="m27.3,20.95c-.35-.62-.71-1.2-1.06-1.82s-.75-1.22-1.13-1.83c-.38-.61-.77-1.21-1.16-1.81-.39-.6-.79-1.2-1.2-1.79l-.03-.04c-.08-.11-.18-.2-.31-.27-.38-.18-.84-.02-1.05.34l-.3.51c-.1.18-.21.35-.31.53-.2.36-.4.71-.6,1.08-.17.32-.07.74.26.94s.74.09.96-.21c.16-.21.35-.5.51-.72.09-.13.29-.13.38.01.67,1.06,3.14,4.89,3.91,5.91.2.27.62.41.95.18s.43-.59.19-1.02Z"></path>
            <path d="m19.72,17.65c-.68-1.11-1.38-2.22-2.07-3.33-.69-1.11-1.4-2.21-2.09-3.31l-2.12-3.3h0s-.02-.04-.02-.05c.01-.48.02-.97.03-1.45.31-.15.61-.29.92-.44.44-.21.88-.43,1.32-.65.44-.22.88-.44,1.32-.66l1.32-.66c.2-.1.35-.28.4-.53.08-.35-.12-.7-.44-.85h0c-.87-.41-1.74-.79-2.61-1.19-.87-.4-1.75-.78-2.62-1.17h0c-.12-.05-.25-.08-.39-.06-.4.04-.7.39-.7.8,0,.61.01,1.22.02,1.82l.02,1.85c.01,1.07.03,2.13.06,3.2-.01.02-.02.03-.04.05l-1.06,1.72c-.35.57-.7,1.15-1.05,1.72-.7,1.15-1.7,2.83-1.84,3.12s-.14.81.2,1.01.72.05.98-.29,1.29-1.87,2.02-2.99c.37-.56.73-1.13,1.1-1.7l.18-.29c.09-.13.28-.13.37,0l1.2,1.9c.71,1.1,1.4,2.2,2.12,3.3.71,1.1,1.42,2.19,2.14,3.28.72,1.09,1.77,2.7,2.09,3.11s.74.56,1.1.36.36-.71.15-1.08-1.3-2.15-1.98-3.26Zm-3.5-14.46c-.4.21-.8.43-1.2.65-.43.23-.86.47-1.29.71-.09.05-.17.1-.26.14,0-.07,0-.15,0-.22l.02-1.85c0-.22,0-.43,0-.65.51.23,1.02.46,1.53.69.4.18.79.35,1.19.53Z"></path>
        </svg>
    </template>
        <div v-if="!fullscreen" class="mx-3 mb-3">
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
                            <div class="mt-3">
                                <router-link :to="{ name: 'post', query: { id: challenge.id, status: 5 } }">対応</router-link>
                            </div>
                        </PanelData>
                    </template>
                </ExpansionPanelItem>
            </ExpansionGrid>
        </div>
        <div v-if="fullscreen" class="px-4">
            
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

