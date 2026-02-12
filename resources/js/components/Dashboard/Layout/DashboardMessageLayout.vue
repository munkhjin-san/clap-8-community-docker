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
            <v-expansion-panel hide-actions static :tile="true" class="rm-p" v-for="(message, index) in data.data" :key="index">
                <v-expansion-panel-title>
                    <template v-slot:default="{ expanded }">
                        <PanelTitle :expanded="expanded">
                            <UserPanel disable-instant :user="message.user" :with-name="true" />
                            <span v-if="!expanded"> : </span>
                            <div v-if="!expanded" class="text-[14px] flex-1 ml-2 whitespace-nowrap overflow-hidden text-ellipsis leading-normal" v-html="mentionFormatter(message.message)"></div>

                        </PanelTitle>
                    </template>
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                    <PanelData class="px-4 py-4 pt-0">
                        <MessageItemInner
                            :message-menu-items="[]"
                            :share-menu-items="[]"
                            :message="message"
                            :compact="true"
                            mode="remind"
                            @remind="remindRequest(message)"
                        />
                    </PanelData>
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>
    </div>
    <div v-if="fullscreen" class="space-y-4">
        <MessageItemInner
            :message-menu-items="[]"
            :share-menu-items="[]"
            v-for="message in data.data"
            :key="message.id ?? message.record_id"
            :message="message"
            :compact="false"
            mode="remind"
            @remind="remindRequest(message)"
        />
    </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { Message } from '@/interface/globalInterface';
import UserPanel from '@/components/Global/UserPanel.vue';
import { mentionFormatter } from '@/utils/tools';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useBadgeStore } from '@/store/badge';
import MessageItemInner from '@/components/Board/Message/MessageItemInner.vue';
import BaseLayout from './BaseLayout.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';

const props = defineProps<{
    data: {
        title: string,
        data: Message[],
        order?: number,
        type: string
    },
    fullscreen: boolean
}>()

const api = useApi()
const { toast, ask } = useDialog()
const badge = useBadgeStore()

const emit = defineEmits<{
    refreshData: [key: string]
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const remindRequest = async(message: Message) => {
    const data = await api.post('/remind_add', { id: message.id })
    const inf = data?.reminded === true ? 'リマインドしました。' : 'リマインドを取り消しました。'
    toast(inf)
    emit('refreshData', 'remindedMessages')
    // if(data !== null){
    //     badge.getRemindBadge()
    //     const numericId = typeof message.id === 'string' ? Number(message.id) : message.id
    //     if (typeof numericId === 'number' && Number.isFinite(numericId)) {
    //         emit('remove', numericId)
    //     }
    // }
}

defineExpose({
    cardType: props.data.type,
})
</script>
