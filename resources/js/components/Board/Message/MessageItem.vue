<template>
    <MessageItemInner
        ref="messageBox"
        :messageMenuItems="messageMenuItems"
        :shareMenuItems="shareMenuItems"
        :message="message"
        :unreadMessages="unreadMessages"
        :compact="compact"
        :mIndex="mIndex"
        :searchTargetId="searchTargetId"
        :messageListType="messageListType"
        :draftSending="draftSending"
        :editing="editing"
        :reacting="reacting"
        @sendEmote="num => sendEmote(num)"
        @draftSend="draftSend"
        @remind="message => remind(message)"
        @reactOrCheck="message => reactOrCheck(message)"
        @cancelEdit="editing = false"
    >
        <template #unreadLine>
            <div v-bind="unreadLineVisible()" @click="refresh" v-if="unreadMessages.id == message.id" :id="'unread_line_' + message.id" class="cursor-pointer" style="user-select:none;width:100%;border-bottom:solid thin #a09f9f;position: absolute;bottom:10px;font-size:12px;">
                <p class="unread-inner" style="margin-bottom: -12px;">新しいメッセージ</p>
            </div>
        </template>
    </MessageItemInner>


</template>

<script setup lang="ts">

import { computed, inject, onMounted, ref, useTemplateRef } from 'vue'
import { useRouter } from "vue-router";
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useBadgeStore } from '@/store/badge'
import { useApi } from "@/composables/api";
import { useDialog } from "@/composables/dialog";
import { BoardMethodsKey, BoardMethods, MessageMethods, MessageMethodsKey } from "@/interface/keys";
import { MenuList, Message, MessageFile } from "@/interface/globalInterface";
import { useBoardList } from "@/composables/board";
import MessageItemInner from "./MessageItemInner.vue";
import { useResponsive } from '@/store/responsive';
import { useQuoteReply } from '@/store/quoteReply';
import { useUrlMessage } from '@/store/urlMessage';
import { useDashboardStore } from '@/store/dashboard';
import { useSharingDataStore } from '@/store/sharingData';
    const badge = useBadgeStore()
    const auth = useAuthUserStore()
    const menu = useMenuStore()
    const responsive = useResponsive()
    const quoteReply = useQuoteReply()
    const sharingData = useSharingDataStore()
    const props = defineProps<{
        message: Message,
        mIndex?: number | string,
        searchTargetId?: number | null,
        messageListType?: string,
        unreadMessages?: any
        compact?: boolean
    }>()
    const emit = defineEmits<{
        updateReplyKey: [],
        unreadJumperOn: [data:any]
    }>()
    const router = useRouter()
    const reacting = ref(false)
    const draftSending = ref(false)
    const messageBox = useTemplateRef('messageBox')
    const { openedBoard } = useBoardList()
    const { refreshMessages, close, reload, messageLoader, open } = inject(BoardMethodsKey) as BoardMethods 
    const { copy, remind, check, sent } = inject(MessageMethodsKey) as MessageMethods;
    const editing = ref(false)
    const api = useApi()
    const { ask, ping, toast } = useDialog()
    const urlMessage = useUrlMessage()
    const { getBatchDashboardData } = useDashboardStore()
    onMounted(() => {
        if((messageBox.value && props.message.id == props.searchTargetId && props.messageListType == 'search') || urlMessage.id == props.message.id){
            messageBox.value?.$el?.scrollIntoView({block: 'center' }); 
            urlMessage.setUrlMessageId(props.message.id)         
            setTimeout(() => { urlMessage.setUrlMessageId(null)}, 2500);  
        }       
    })

    const refresh = () => {
        messageLoader(true)
        if(openedBoard.value)
        open(openedBoard.value)
    }   
    const shareMenuItems = computed(() => {
        const list:MenuList[]= []; 
        function addItem(title, action) {
            list.push({ title, action });
        }
        const builtInApps = [
            {name: 'board', name_jp: 'チャット'}, 
            {name: 'schedule', name_jp: 'スケジュール'},
            {name: 'task', name_jp: 'タスク'},
            {name: 'external', name_jp: 'その他'}
        ] 
        builtInApps.forEach(app => {
            addItem(app.name_jp, () => shareTo(app.name))
        });

        return list
    })
    const authorized = computed(() => {
        return props.message.user_id == auth.activeUser.id
    })
    const messageMenuItems = computed(() => {
        if(!openedBoard.value) return []
        const canConfirm = props.message.emoji_flag == 0 && openedBoard.value.private_flag !== 3
        const isDraft = props.message.draft_flag
        const list:MenuList[] = []; 
        function addItem(title, action) {
            list.push({ title, action });
        }
        if(authorized.value){
            addItem('編集する', () => editing.value = true )
        }
        if(!authorized.value && !isDraft){
            addItem('返信する', () => replyQuotStart('reply'))
        }
        if (!isDraft) {
            addItem('引用する', () => replyQuotStart('quot')) 
        }
                 
        addItem('コピー', () => copyTextStart())
        if(authorized.value){
            if (!isDraft) {
                if(!props.message.check_flag && canConfirm){
                    addItem('確認依頼', () => check(props.message, 'confirm'))
                }else if(props.message.check_flag){
                    addItem('再確認依頼', () => resendConfrim() )
                }
            }
            
            addItem('削除する', () => deleteMessage(props.message.id as number) )
            
        }
        if (!isDraft) {
            addItem('未読にする', () => markUnread(props.message.id as number))
        }
          

        return list
    })
    const replyQuotStart = (which) => { 
        const innerEl = messageBox.value
        if(!innerEl) return
        const body = innerEl.messageBoxBody
        if(!body) return

        const widthS = body.clientWidth + 20;
        const heightS = body.clientHeight + 20;
        const file = props.message.message && props.message.message.length ? false : true
        const text = props.message.message && props.message.message.length ? props.message.message : null
        const data = {
            active: true,
            which: which,
            message: props.message,
            height: heightS,
            width: widthS,
            text: text,
            file: file
        }
        quoteReply.setQuoteReply(data)
        emit('updateReplyKey')


    }
    const resendConfrim = async() => {     

        await api.post('/send_reconfirm_email', {
            send_list: props.message.unchecked_users?.map(ob => ob.id),
            board_id: openedBoard.value?.id,
            send_condition: 2,
            msg_id: props.message.id
        }, {
            ask: '未確認者へ確認依頼のメールを送りますか？',
            toast: '再確認依頼のメールを送信しました。'
        })
    }
    const unreadLineVisible = () => {
        
        setTimeout(() => {
            const rect = messageBox.value?.$el?.getBoundingClientRect()
            if(!rect || !openedBoard.value) return
            if(badge.activeUsersBoardBadge[openedBoard.value.id] && (rect.y + rect.height < 0)){
                const data = {
                    status: true,
                    count: props.unreadMessages.count,
                    id: props.message.id
                }
                emit('unreadJumperOn', data)
                
            
            }
            
        })
        return{
            unreadLineVisible
        }
    }
    const copyTextStart = () => {   
        const innerEl = messageBox.value
        if(!innerEl) return
        const body = innerEl.messageBoxBody
        if(!body) return
        copy({
            height: body.clientHeight + 20,
            width: body.clientWidth + 20,
            text: body.textContent
        })             
    }

    const deleteMessage = async (id) => {        
        const data = await api.post('/chat_delete_api', {id: id}, {
            ask: 'メッセージを削除してもよろしいですか？',
            toast: 'メッセージを削除しました。'
        }) 
        if (!data) return 
        refreshMessages(data)
    }
    const markUnread = async(id) => {
        menu.close()
        const response = await api.post('/chat_mark_unread', {
            message_id: id,
            user_id: auth.activeUser.id,
            board_id: openedBoard.value?.id
        }, {
            ask: 'メッセージを未読にしますか？',
            toast: '未読にしました。'
        })
        if (!response) return
        badge.getBoardBadge() 
        reload()    
        router.push({name: 'board'})     
        close()

    }
    const reactOrCheck = async(msg) => {        
        if(msg.user_id == auth.activeUser.id) return    
        reacting.value = msg.reacted_users.filter(ob => ob.id == auth.activeUser.id).length ? false : true    
        
        const message = await api.post('/send_reaction_api', {id: msg.id})
        refreshMessages(message)
        const checkedMessage = message
        if(checkedMessage.check_flag == 1){
            finishCheck(checkedMessage)
        } 
           
    }   
    const finishCheck = async(checkedMessage) => {
        const checked = checkedMessage.checked_users.filter(ob => ob.id == auth.activeUser.id).length
        const unchecked = checkedMessage.unchecked_users.filter(ob => ob.id == auth.activeUser.id).length
        const reacted = checkedMessage.reacted_users.filter(ob => ob.id == auth.activeUser.id).length          
        if(unchecked && reacted){     
            const confirmed = await ask('確認済みにしますか')
            if(confirmed.value){
                const data = await api.post('/check_send_api', { message_id: checkedMessage.id, user_id: auth.activeUser.id, pattern: 'check' })                              
                refreshMessages(data.message)    
                toast('確認済みにしました。')    
                getBatchDashboardData(['mustCheckMessages'])
            }                                  
        }
        if(checked && reacted){                  
            ping('既に確認しています。')  
        }
    }    
    const fastPreCheckEmote = (name) => {
        // pretend to send emote api for fast response
        const checkExist = props.message.emoted_users?.find(ob => ob.id == auth.activeUser.id)
        if(checkExist){
            if(checkExist.pivot.emote_name == name) {
                refreshMessages({
                    ...props.message,
                    emoted_users: props.message.emoted_users.filter(ob => !(ob.id == auth.activeUser.id && ob.pivot.emote_name == name))
                })
            }else{ 
                const newEmotedUsers = props.message.emoted_users.map(ob => {
                    if(ob.id == auth.activeUser.id){
                        return {
                            ...ob,
                            pivot: {
                                ...ob.pivot,
                                emote_name: name
                            }
                        }
                    }
                    return ob
                })
                refreshMessages({
                    ...props.message,
                    emoted_users: newEmotedUsers
                })
            }
            
            
        } else {
            refreshMessages({
                ...props.message,
                emoted_users: [{
                    ...auth.activeUser,
                    pivot: {
                        message_id: props.message.id as number,
                        user_id: auth.activeUser.id as number,
                        emote_name: name
                    }
                }, ...props.message.emoted_users]
            })
        }
    }
    const sendEmote = async(name) => {
        menu.close()
        fastPreCheckEmote(name)
        const data = await api.post('/send_emote', {id: props.message.id, reaction: name})
        if (data.check_flag == 1) {
            finishCheck(data)
        }
        refreshMessages(data)
    }    

   
    const draftSend = async() => {
        if (draftSending.value) return
        draftSending.value = true
        const data = await api.put('/draft_send', {id: props.message.id, draft_flag: 0})
        if(props.message.id){
            refreshMessages(data.mutated, props.message.id as number)
        }
        
        sent(data.mutated, data.message.messages.data, data.last_message)
        setTimeout(() => {
            draftSending.value = false
        }, 200)
    }
    const shareToTask = inject<Function>('shareToTask') as Function

    const shareTo = (to) => {
        if(to == 'external'){
            navigator.share({
                text: props.message.message?.toString(),
                files:[]
            })

        }else{
        let files: {path: string, record: MessageFile}[] = []
            props.message.message_files.forEach(element => {
                const file = {
                    path: `/cdn/shared_files/${props.message.record_id}/${element.id}_${element.user_id}_${element.message_id}.${element.extension}`,
                    record: element
                }
                files.push(file)
            });
            const shareData = {
                active: true,
                message: props.message,
                title: '',
                text: props.message.message,
                files: files,
                from: 'message',
                to: to,
                drag: false,
                instruction: to == 'board' ? '送る先のチャットを選択してください' : ''
            }
            sharingData.setSharingData(shareData)
            if(to == 'task'){
                if(responsive.mobile){
                    setTimeout(() =>{
                        router.push({name: to})
                    },0) 
                }else{             
                    
                    shareToTask()                       
                }
            }else if(to !== 'board'){
                router.push({name: to})
            }
            else if(to == 'board' && responsive.mobile){
                router.push({name: 'board'})
            }
        }
        
        menu.close()
    }
</script>
