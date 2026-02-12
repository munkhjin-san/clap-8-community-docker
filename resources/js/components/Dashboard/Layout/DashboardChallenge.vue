<template>
    <BaseLayout
        :title="data.title" 
        :count="data.data.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >   
        <div v-if="!fullscreen" class="mx-3 mb-3">
             <v-expansion-panels>
                <v-expansion-panel hide-actions static :tile="true" class="rm-p" v-for="(challenge, index) in data.data" :key="index">
                    <v-expansion-panel-title>
                        <template v-slot:default="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                {{ challenge.title }}
                            </PanelTitle>
                        </template>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                        <PanelData>
                            <p v-if="isOverdue(challenge) ">チャレンジ期間が終了しました。結果を入力してください。</p>
                            <p v-else>チャレンジの締切が近づいています。進捗を入力してください。</p>
                            <div class="mt-3">
                                <router-link :to="{name: 'post', query: {id: challenge.id}}">対応</router-link>
                            </div>
                        </PanelData>
                    </v-expansion-panel-text>
                </v-expansion-panel>
            </v-expansion-panels>
        </div>
        <div v-if="fullscreen" class="px-4">
            
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { Post } from '@/interface/postInterface';
import { useTemplateRef } from 'vue'
import ListBox from '@/components/Task/List/ListBox.vue';
import BaseLayout from './BaseLayout.vue';
import { DateTime } from 'luxon';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';

const props = defineProps<{
    data: {
        title: string,
        data: Post[],
        order?: number,
        type: string
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

