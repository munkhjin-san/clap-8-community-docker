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
        <svg xmlns="http://www.w3.org/2000/svg" height="15" class="dot-menu mr-1" viewBox="0 0 11.84 13.06"><path d="M11.42,9.04c-.31-.09-.59-.28-.84-.5-.07-.2-.12-.51-.15-.77-.1-.79-.15-1.61-.25-2.42-.1-.87-.29-1.84-.87-2.55-.47-.61-1.13-1.11-1.88-1.31-.03,0-.05-.03-.05-.06,0-.4,0-.87,0-.87,0-.31-.25-.57-.57-.57,0,0-1.78,0-1.78,0-.31,0-.57.25-.56.57v.87s-.02.06-.05.06c-.75.2-1.4.7-1.88,1.31-.84,1.07-.85,2.5-1,3.78-.04.4-.07.81-.12,1.19-.04.27-.07.52-.15.76,0,0,0,0,0,.01-.09.08-.31.25-.43.32-.13.07-.26.14-.4.18C.44,9.03,0,9.56,0,9.56c0,0,0,1.22,0,1.23,0,.29.23.51.52.51.9,0,2.42-.02,3.72-.03-.01.05-.02.1-.01.16,0,.02,0,.07.01.09.06.39.21.74.49,1.04.47.49,1.2.61,1.84.41.63-.23,1.03-.9,1.04-1.54,0-.05,0-.1,0-.14,1.3,0,2.8.02,3.7.02.29,0,.52-.23.52-.52,0,0,0-1.22,0-1.23,0,0-.44-.54-.43-.52M10.55,8.52s0,0,0,0c0,0,0,0,0,0,0,0,0,0,0,0M2.02,9.31c.35-.33.4-.96.47-1.39.06-.42.1-.82.14-1.23.08-.77.15-1.59.35-2.31.23-.79.85-1.5,1.63-1.77.15-.05.33-.09.45-.12.36-.09.45-.21.45-.52,0,0,0-.55,0-.93,0-.04.03-.07.08-.07h.67s.08.03.08.08c0,.38,0,.93,0,.93,0,.31.14.45.46.53.96.11,1.81.98,2.06,1.87.29,1.13.32,2.36.49,3.54.04.26.08.55.16.83.06.18.12.37.27.53.29.27.63.51,1,.67,0,.14,0,.16,0,.32-1.43,0-3.68.02-4.87.03-.04,0-.08,0-.12.02-1.21-.01-3.36-.04-4.75-.05,0-.16,0-.19,0-.33.36-.15.68-.38.96-.63M6.72,11.33s0,0,0,0v.02c-.08.16-.18.36-.32.45-.06.05-.13.07-.2.1-.14.07-.38.08-.53.03-.26-.07-.42-.33-.49-.59v-.02s0-.01,0,0c0-.03,0-.05-.01-.07.24,0,.46,0,.65,0,.04,0,.08.02.12.02.23,0,.51,0,.81,0,0,.02,0,.04-.01.06"></path></svg>
    </template>
    <div v-if="!fullscreen" class="m-5">
        <ExpansionGrid class="gap-x-4" :col="Number(data.col.split('-')[2] ?? 1)">
            <ExpansionPanelItem 
                hide-actions 
                class="rm-p" 
                v-for="(message, index) in data.data" 
                :key="message.id ?? index" 
                :value="message.id ?? index"
                :col="Number(data.col.split('-')[2] ?? 1)"
            >
                <template #title="{ expanded }">
                    <PanelTitle :expanded="expanded">
                        <div class="flex gap-2 overflow-hidden text-ellipsis items-center">
                            <UserPanel disable-instant :user="message.user" size="25" />
                            <div class="overflow-hidden text-ellipsis w-full">{{ message.user.name }}</div>
                        </div>
                        <span v-if="!expanded"> : </span>
                        <div v-if="!expanded" class="text-[14px] flex-1 ml-2 whitespace-nowrap overflow-hidden text-ellipsis leading-normal" v-html="mentionFormatter(message.message)"></div>
                        <div title="リマインドから外す" @click.stop="remindRequest(message)" v-if="expanded && data.type === 'remindedMessages'" class="ml-auto boardMenuContainer">
                            <svg xmlns="http://www.w3.org/2000/svg" height="13" class="m-auto dot-menu" viewBox="0 0 11.84 13.06">
                                <path d="M11.42,9.04c-.31-.09-.59-.28-.84-.5-.07-.2-.12-.51-.15-.77-.1-.79-.15-1.61-.25-2.42-.1-.87-.29-1.84-.87-2.55-.47-.61-1.13-1.11-1.88-1.31-.03,0-.05-.03-.05-.06,0-.4,0-.87,0-.87,0-.31-.25-.57-.57-.57,0,0-1.78,0-1.78,0-.31,0-.57.25-.56.57v.87s-.02.06-.05.06c-.75.2-1.4.7-1.88,1.31-.84,1.07-.85,2.5-1,3.78-.04.4-.07.81-.12,1.19-.04.27-.07.52-.15.76,0,0,0,0,0,.01-.09.08-.31.25-.43.32-.13.07-.26.14-.4.18C.44,9.03,0,9.56,0,9.56c0,0,0,1.22,0,1.23,0,.29.23.51.52.51.9,0,2.42-.02,3.72-.03-.01.05-.02.1-.01.16,0,.02,0,.07.01.09.06.39.21.74.49,1.04.47.49,1.2.61,1.84.41.63-.23,1.03-.9,1.04-1.54,0-.05,0-.1,0-.14,1.3,0,2.8.02,3.7.02.29,0,.52-.23.52-.52,0,0,0-1.22,0-1.23,0,0-.44-.54-.43-.52M11.1,8.55s0,0,0,0c0,0,0,0,0,0,0,0,0,0,0,0"/>
                            </svg>
                        </div>
                    </PanelTitle>
                </template>
                <template #body>
                    <PanelData class="px-4 py-4 pt-0">
                        <MessageItemInner
                            :message-menu-items="[]"
                            :share-menu-items="[]"
                            :message="message"
                            :compact="true"
                            :reacting="reacting === message.id"
                            mode="remind"
                            @remind="remindRequest(message)"
                            @react-or-check="reactOrCheck"
                            @sendEmote="num => sendEmote(message, num)"
                        />
                        <div class="mt-3 ml-auto w-fit">
                            <router-link :to="`/board/${message.record_id}?m=${message.id}&jump_message=true`">チャットへ移動</router-link>
                        </div>
                    </PanelData>
                </template>
            </ExpansionPanelItem>
        </ExpansionGrid>
    </div>
    <div v-if="fullscreen" class="space-y-4 bg-[var(--bg3)] mx-4 mb-4 pb-4">
        <div v-for="message in data.data" :key="message.id ?? message.record_id">        
            <MessageItemInner
                :message-menu-items="[]"
                :share-menu-items="[]"          
                
                :message="message"
                :compact="false"
                :reacting="reacting === message.id"
                mode="remind"            
                @remind="remindRequest(message)"
                @react-or-check="reactOrCheck"
                @sendEmote="num => sendEmote(message, num)"
            />
            <div class="px-3 -mt-7 z-[6] relative">
                <router-link class="text-[12px]" :to="`/board/${message.record_id}?m=${message.id}&jump_message=true`">チャットへ移動</router-link>
            </div>
        </div>
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
import { useAuthUserStore } from '@/store/auth';
import { ref } from 'vue';
import { useMenuStore } from '@/store/menu';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import ExpansionGrid from '../ExpansionGrid.vue';

const props = defineProps<{
    data: {
        title: string,
        data: Message[],
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col: string
    },
    fullscreen: boolean
}>()

const api = useApi()
const { toast, ask, ping } = useDialog()
const badge = useBadgeStore()
const auth = useAuthUserStore()
const reacting = ref<number | string | null>(null)
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
}
const reactOrCheck = async(msg: Message) => {        
    console.log('reactOrCheck', msg)
    if(msg.user_id == auth.activeUser.id) return    
    reacting.value = msg.reacted_users?.filter(ob => ob.id == auth.activeUser.id).length ? null : msg.id    

    const message:Message = await api.post('/send_reaction_api', {id: msg.id})
    // emit('getUncheckedMessages')
    // emit('getRemindMessages')
    const checkedMessage = message
    if(checkedMessage.check_flag == 1){
        const checked = checkedMessage?.checked_users?.filter(ob => ob.id == auth.activeUser.id).length
        const unchecked = checkedMessage?.unchecked_users?.filter(ob => ob.id == auth.activeUser.id).length
        const reacted =   checkedMessage?.reacted_users?.filter(ob => ob.id == auth.activeUser.id).length          
        if(unchecked && reacted){     
            const confirmed = await ask('確認済みにしますか')
            if(confirmed.value){
                await api.post('/check_send_api', { message_id: msg.id, user_id: auth.activeUser.id, pattern: 'check' })                              
                // emit('getUncheckedMessages')    
                toast('確認済みにしました。') 
                emit('refreshData', props.data.type)  
            }                                  
        }
        if(checked && reacted){                  
            ping('既に確認しています。')  
        }
    } 
    emit('refreshData', props.data.type)
    setTimeout(() => {
        reacting.value = null
    }, 500);
        
}  
const menu = useMenuStore()
const sendEmote = async(message: Message, num: string) => {
    menu.close()
    const data = await api.post('/send_emote', {id: message.id, reaction: num})
    emit('refreshData', props.data.type)
    // refreshMessages(data)
}  
defineExpose({
    cardType: props.data.type,
})
</script>
