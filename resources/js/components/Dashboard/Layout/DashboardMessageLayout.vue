<template>
    <BaseLayout 
        :title="data.title" 
        :count="data.data.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <v-expansion-panels v-if="!fullscreen">
            <v-expansion-panel hide-actions static :tile="true" class="rm-p" v-for="(message, index) in data.data" :key="index">
                <v-expansion-panel-title>
                    <template v-slot:default="{ expanded }">
                        <div class="px-4 flex items-center w-[calc(100%-32px)]">
                            <UserPanel disable-instant :user="message.user" :with-name="true" />
                            <span v-if="!expanded"> : </span>
                            <div v-if="!expanded" class="text-[14px] flex-1 ml-2 whitespace-nowrap overflow-hidden text-ellipsis leading-normal" v-html="mentionFormatter(message.message)"></div>

                            <div v-if="expanded" class="title-row ml-auto">
                                <v-btn
                                    class="t-button ml-auto"
                                    size="small"
                                    @click.stop.prevent="remindRequest(message)"
                                    :ripple="false"
                                    variant="plain"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" height="13" class="m-auto dot-menu" viewBox="0 0 11.84 13.06">
                                        <path d="M11.42,9.04c-.31-.09-.59-.28-.84-.5-.07-.2-.12-.51-.15-.77-.1-.79-.15-1.61-.25-2.42-.1-.87-.29-1.84-.87-2.55-.47-.61-1.13-1.11-1.88-1.31-.03,0-.05-.03-.05-.06,0-.4,0-.87,0-.87,0-.31-.25-.57-.57-.57,0,0-1.78,0-1.78,0-.31,0-.57.25-.56.57v.87s-.02.06-.05.06c-.75.2-1.4.7-1.88,1.31-.84,1.07-.85,2.5-1,3.78-.04.4-.07.81-.12,1.19-.04.27-.07.52-.15.76,0,0,0,0,0,.01-.09.08-.31.25-.43.32-.13.07-.26.14-.4.18C.44,9.03,0,9.56,0,9.56c0,0,0,1.22,0,1.23,0,.29.23.51.52.51.9,0,2.42-.02,3.72-.03-.01.05-.02.1-.01.16,0,.02,0,.07.01.09.06.39.21.74.49,1.04.47.49,1.2.61,1.84.41.63-.23,1.03-.9,1.04-1.54,0-.05,0-.1,0-.14,1.3,0,2.8.02,3.7.02.29,0,.52-.23.52-.52,0,0,0-1.22,0-1.23,0,0-.44-.54-.43-.52M11.1,8.55s0,0,0,0c0,0,0,0,0,0,0,0,0,0,0,0"/>
                                    </svg>
                                </v-btn>
                            </div>

                        </div>

                    </template>

                </v-expansion-panel-title>
                <v-expansion-panel-text>
                    <MessageItemInner
                        :message-menu-items="[]"
                        :share-menu-items="[]"
                        :message="message"
                        :compact="true"
                        mode="remind"
                        @remindRequest="remindRequest(message)"
                    />
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>

        <div v-if="fullscreen" class="space-y-4">
            <MessageItemInner
                :message-menu-items="[]"
                :share-menu-items="[]"
                v-for="message in data.data"
                :key="message.id ?? message.record_id"
                :message="message"
                :compact="false"
                mode="remind"
                @remindRequest="remindRequest(message)"
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
    remove: [id: number]
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const remindRequest = async(message: Message) => {
    const confirm = await ask('このメッセージをリマインドから削除しますか？')
    if(!confirm.value) return

    const data = await api.post('/remind_add', { id: message.id })
    const inf = data === true ? 'リマインドしました。' : 'リマインドを取り消しました。'
    toast(inf)

    if(data !== null){
        badge.getRemindBadge()
        const numericId = typeof message.id === 'string' ? Number(message.id) : message.id
        if (typeof numericId === 'number' && Number.isFinite(numericId)) {
            emit('remove', numericId)
        }
    }
}

defineExpose({
    cardType: props.data.type,
})
</script>
