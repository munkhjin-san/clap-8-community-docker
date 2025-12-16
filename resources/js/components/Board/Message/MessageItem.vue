<template>
    <div :id="'messageRoot_' + message.id" 
        ref="messageBox"
        class="messageBoxRoot" 
        :class="{infoMessage : message.info_flag == 1, selfMessage: message.user_id == auth.activeUser.id}"
        :style="{marginBottom: editing && mIndex == 0 ? '25px' : '0'}"
        v-if="!message.draft_flag || (message.draft_flag && message.user_id === auth.activeUser.id)">
        <div class="infoMessageInner" v-if="message.info_flag > 0">   
            <p v-if="showDate">{{DateParser(message.created_at)}}</p>       
            <p style="cursor:pointer" @click="showDate = !showDate" v-html="infoMessage"></p>        
        </div>
         <div 
            v-else-if="message.info_flag == 0" 
            :style="{
                float: auth && auth.activeUser.id == message.user.id ? 'right' : 'left',
                margin: '0 15px',
                maxWidth: message.message == null || !message.message || !message.message.length ? '50%' : '80%',
                width: 'fit-content'
            }" 
            :class="['mobileMessageBody', { 'reached' : urlMessage.id == message.id}, { emojiOnly: (message.emoji_flag == 1 || message.emoji_flag == 2) && !message.message_reply && !message.message_quot, editIsOn:editing, 'mb-35':editing && unreadMessages.id == message.id}]"
        >
            <div class="message-top-block">
                <div class="flex items-center gap-[10px]">
                    <div class="relative">
                        <UserPanel size="30" :user="message.user" imgClass="userNormalIcon"/>
                        <div class="absolute bottom-[-2px] right-[-2px] z-[3]" v-if="message.actual_sender">
                            <UserPanel size="15" :user="message.actual_sender"/>
                        </div>                   
                    </div>
                    <div class="mr-[20px]">
                        <div @click.stop="pushInstantUser($event, message.user_id)" :class="{'!text-[12px]' :  message.actual_sender}" class="cursor-pointer text-[14px] break-keep">{{ messageUserName }}</div>
                        <div v-if="message.actual_sender" class="text-[12px] mt-[3px]">{{ message.actual_sender.name }}</div>
                    </div>
                    
                         
                </div>                                     
                <div class="m-date">{{messageKind}}</div>  
                <div class="messageIconContainer">
                    <div v-if="message.deleted_at == null && message.message" title="読み上げる" class="relative min-w-[25px]">
                        <TTSPlayer 
                            :text="readableText" 
                            :key="`tts_message_${message.id}`"
                        />
                    </div>
                    <div v-if="(message.deleted_at == null || reminded) && !message.draft_flag" title="リマインド" class="boardMenuContainer" @click="remind(message)">
                        <svg v-if="reminded" xmlns="http://www.w3.org/2000/svg" height="13" class="m-auto dot-menu" viewBox="0 0 11.84 13.06">
                            <path d="M11.42,9.04c-.31-.09-.59-.28-.84-.5-.07-.2-.12-.51-.15-.77-.1-.79-.15-1.61-.25-2.42-.1-.87-.29-1.84-.87-2.55-.47-.61-1.13-1.11-1.88-1.31-.03,0-.05-.03-.05-.06,0-.4,0-.87,0-.87,0-.31-.25-.57-.57-.57,0,0-1.78,0-1.78,0-.31,0-.57.25-.56.57v.87s-.02.06-.05.06c-.75.2-1.4.7-1.88,1.31-.84,1.07-.85,2.5-1,3.78-.04.4-.07.81-.12,1.19-.04.27-.07.52-.15.76,0,0,0,0,0,.01-.09.08-.31.25-.43.32-.13.07-.26.14-.4.18C.44,9.03,0,9.56,0,9.56c0,0,0,1.22,0,1.23,0,.29.23.51.52.51.9,0,2.42-.02,3.72-.03-.01.05-.02.1-.01.16,0,.02,0,.07.01.09.06.39.21.74.49,1.04.47.49,1.2.61,1.84.41.63-.23,1.03-.9,1.04-1.54,0-.05,0-.1,0-.14,1.3,0,2.8.02,3.7.02.29,0,.52-.23.52-.52,0,0,0-1.22,0-1.23,0,0-.44-.54-.43-.52M11.1,8.55s0,0,0,0c0,0,0,0,0,0,0,0,0,0,0,0"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" height="13" class="m-auto dot-menu" viewBox="0 0 11.84 13.06">
                            <path d="M11.42,9.04c-.31-.09-.59-.28-.84-.5-.07-.2-.12-.51-.15-.77-.1-.79-.15-1.61-.25-2.42-.1-.87-.29-1.84-.87-2.55-.47-.61-1.13-1.11-1.88-1.31-.03,0-.05-.03-.05-.06,0-.4,0-.87,0-.87,0-.31-.25-.57-.57-.57,0,0-1.78,0-1.78,0-.31,0-.57.25-.56.57v.87s-.02.06-.05.06c-.75.2-1.4.7-1.88,1.31-.84,1.07-.85,2.5-1,3.78-.04.4-.07.81-.12,1.19-.04.27-.07.52-.15.76,0,0,0,0,0,.01-.09.08-.31.25-.43.32-.13.07-.26.14-.4.18C.44,9.03,0,9.56,0,9.56c0,0,0,1.22,0,1.23,0,.29.23.51.52.51.9,0,2.42-.02,3.72-.03-.01.05-.02.1-.01.16,0,.02,0,.07.01.09.06.39.21.74.49,1.04.47.49,1.2.61,1.84.41.63-.23,1.03-.9,1.04-1.54,0-.05,0-.1,0-.14,1.3,0,2.8.02,3.7.02.29,0,.52-.23.52-.52,0,0,0-1.22,0-1.23,0,0-.44-.54-.43-.52M10.55,8.52s0,0,0,0c0,0,0,0,0,0,0,0,0,0,0,0M2.02,9.31c.35-.33.4-.96.47-1.39.06-.42.1-.82.14-1.23.08-.77.15-1.59.35-2.31.23-.79.85-1.5,1.63-1.77.15-.05.33-.09.45-.12.36-.09.45-.21.45-.52,0,0,0-.55,0-.93,0-.04.03-.07.08-.07h.67s.08.03.08.08c0,.38,0,.93,0,.93,0,.31.14.45.46.53.96.11,1.81.98,2.06,1.87.29,1.13.32,2.36.49,3.54.04.26.08.55.16.83.06.18.12.37.27.53.29.27.63.51,1,.67,0,.14,0,.16,0,.32-1.43,0-3.68.02-4.87.03-.04,0-.08,0-.12.02-1.21-.01-3.36-.04-4.75-.05,0-.16,0-.19,0-.33.36-.15.68-.38.96-.63M6.72,11.33s0,0,0,0v.02c-.08.16-.18.36-.32.45-.06.05-.13.07-.2.1-.14.07-.38.08-.53.03-.26-.07-.42-.33-.49-.59v-.02s0-.01,0,0c0-.03,0-.05-.01-.07.24,0,.46,0,.65,0,.04,0,.08.02.12.02.23,0,.51,0,.81,0,0,.02,0,.04-.01.06"/>
                        </svg>
                    </div>                    
                    <ItemMenu v-if="message.deleted_at == null && !message.draft_flag" type="share" :items="shareMenuItems" fit="boardListInner"/>
                    <ItemMenu v-if="message.deleted_at == null" ref="itemMenuRef" :items="messageMenuItems" fit="boardListInner"/>
                </div>                                           
            </div>    
            
            <div v-if="message.deleted_at" style="background: var(--bg2);">
                <p style="color: gray;padding: 10px;font-size: 13px;">このメッセージは削除されました</p>
            </div>
            
            <div v-if="message.deleted_at == null" :class="message.user_id == auth.activeUser.id ? 'commentTextBoxRight' : 'commentTextBoxLeft'" style="background:transparent">               
                
                <MessageQuoteReply 
                    v-if="message.message_reply"
                    :which="'reply'"
                    :message="message.message_reply"
                    :mentionClick="mentionClick"
                    :quotMessage="null"/>
                <MessageQuoteReply 
                    v-if="message.message_quot"
                    :which="'quot'"
                    :message="message.message_quot"
                    :mentionClick="mentionClick"
                    :quotMessage="message.quot_message"/>
                <MessageQuoteReply 
                    v-if="message.message_forward"
                    :which="'forward'"
                    :message="message.message_forward"
                    :mentionClick="mentionClick"
                    :quotMessage="null"/>             

                <div class="normal-body">
                    <div
                        v-if="!editing"
                        @click.stop="mentionClick"
                        @dragstart.prevent
                        @dblclick="showItemMenu"
                        @touchstart="startTouch"
                        @touchend="endTouch"
                        @touchmove="cancelTouch"
                        @blur.prevent
                        ref="messageBoxBody" 
                        :style="{display: messageBody  ? 'inline-block' : 'none', marginBottom: message.message_files && message.message_files.length && !messageBody ? '10px' : '0'}" 
                        v-html="messageBody" 
                        class="messageInnerBody"
                        :class="{ emojiOnlyInner: (message.emoji_flag == 1 || message.emoji_flag == 2) && !message.message_reply && !message.message_quot}">
                    </div>   
                    <MessageEditor 
                        v-else 
                        :message="message" 
                        @cancel="editing = false"
                    />
                    <MessageFiles 
                        v-if="message.message_files && message.message_files.length"
                        :list="message.message_files"
                        :message="message"
                        :mIndex="mIndex"
                    />
                                                 
                </div>  
                <div v-if="message.draft_flag && !editing" style="margin-top: 15px; display: flex;">
                    <div class="commentEditButton" @click="draftSend">{{ draftSending ? '送信中...' : '送信' }}</div>
                    <div v-if="message.reserved_at == null" class="commentEditButton" @click="setSchedule">予約送信</div>        
                </div>                         
            </div>
            <div v-if="message.deleted_at == null" class="message-foot-area">
                <div style="display:flex;width: fit-content;">                    
                    <div v-if="reactButtonView" class="reactButton" :class="{cursorBlock : message.user_id == auth.activeUser.id, reactOn: reacting}" @click.stop="reactOrCheck(message)">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :fill="checkSendIconColor ? 'var(--primary-color)' : 'var(--check-inactive)'">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </div>
                    <div v-if="message.reacted_users.length" @click.stop="viewReactedUsersList" style="display:flex;padding: 10px;margin: 5px 0 -15px -15px;height: 15px;">
                        <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in reactedUsersListAll.slice(0,3)">  
                            <UserPanel :title="user.name" :disableInstant="true" size="15" :user="user" imgClass="userSmallIcon"/>                                         
                        </div>
                        <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="message.reacted_users.length > 3">...({{message.reacted_users.length}})</span>
                    </div>                                    
                </div>
                <div v-if="checkFunctionView" style="display: flex;margin-top: auto;gap: 15px;min-height: 25px;align-items: end;">
                    <div @click.stop="viewCheckedUserList" style="display: flex;font-size: 12px;cursor: pointer">確認済み ({{ message.checked_users.length}})</div>
                    <div @click.stop="viewunCheckedUserList" style="display: flex;font-size: 12px;cursor: pointer">未確認 ({{ message.unchecked_users.length}})</div> 
                </div>
                
            </div>          
        </div>
        <div class="clear-both"></div>
        
        <div v-bind="unreadLineVisible()" @click="refresh" v-if="unreadMessages.id == message.id" :id="'unread_line_' + message.id" class="cursor-pointer" style="user-select:none;width:100%;border-bottom:solid thin #a09f9f;position: absolute;bottom:10px;font-size:12px;">
            <p class="unread-inner" style="margin-bottom: -12px;">新しいメッセージ</p>
        </div>
    </div>
</template>

<script setup lang="ts">

import MessageQuoteReply from "./MessageQuoteReply.vue";
import MessageFiles from "./MessageFiles.vue";
import { computed, inject, onMounted, ref, useTemplateRef } from 'vue'
import { useRouter } from "vue-router";
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from "@/store/responsive";
import { useMessageUsers } from '@/store/messageUsers'
import { useQuoteReply } from "@/store/quoteReply";
import { useSharingDataStore } from '@/store/sharingData'
import { useUrlMessage } from "@/store/urlMessage";
import { useBadgeStore } from '@/store/badge'
import MessageEditor from './MessageEditor.vue'
import ItemMenu from "@/components/Global/ItemMenu.vue";
import { DateParser, mentionFormatter } from "@/utils/tools";
import { useMessageSchedule } from "@/store/messageSchedule"
import UserPanel from "@/components/Global/UserPanel.vue";
import { useApi } from "@/composables/api";
import { useDialog } from "@/composables/dialog";
import { BoardMethodsKey, BoardMethods, MessageMethods, MessageMethodsKey } from "@/interface/keys";
import { MenuList, MessageFile, SharingData, User } from "@/interface/globalInterface";
import { useBoardList } from "@/composables/board";
import TTSPlayer from "@/components/Global/TTSPlayer.vue";
    const badge = useBadgeStore()
    const sharingData = useSharingDataStore()
    const quoteReply = useQuoteReply()
    const messageUsers = useMessageUsers()
    const auth = useAuthUserStore()
    const menu = useMenuStore()
    const responsive = useResponsive()
    const urlMessage = useUrlMessage()
    const props = defineProps(['message', 'mIndex', 'mLength', 'searchTargetId', 'messageListType', 'unreadMessages'])
    const emit = defineEmits(['updateReplyKey', 'unreadJumperOn'])
    const router = useRouter()
    const editing = ref(false)
    const reacting = ref(false)
    const showDate = ref(false)
    const draftSending = ref(false)
    const messageBox = useTemplateRef('messageBox')
    const messageBoxBody = useTemplateRef('messageBoxBody')
    const { openedBoard } = useBoardList()
    const { refreshMessages, close, reload, messageLoader } = inject(BoardMethodsKey) as BoardMethods 
    const { copy, remind, check } = inject(MessageMethodsKey) as MessageMethods;
    const pushInstantUser = inject('pushInstantUser') as Function
    const itemMenuRef = useTemplateRef('itemMenuRef')
    const longPressDuration = ref(500)
    const longPressTimer = ref<any>(null)
    const isLongPress = ref(false)
    const messageSchedule = useMessageSchedule()
    const api = useApi()
    const { ask, ping, toast } = useDialog()
    onMounted(() => {
        if((props.message.id == props.searchTargetId && props.messageListType == 'search') || urlMessage.id == props.message.id){
            messageBox.value?.scrollIntoView({block: 'center' }); 
            urlMessage.setUrlMessageId(props.message.id)         
            setTimeout(() => { urlMessage.setUrlMessageId(null)}, 2500);  
        }       
    })
    const refresh = () => {
        messageLoader(true)
        refreshMessages()
    }
    const startTouch = (event) => {
        isLongPress.value = false

        longPressTimer.value = setTimeout(() => {
            isLongPress.value = true
            onLongPress(event)
        }, longPressDuration.value)
    }
    const endTouch = () => {
        if (!isLongPress.value) {
            clearTimeout(longPressTimer.value)
        }
    }
    const cancelTouch = () => {
        clearTimeout(longPressTimer.value)
    }
    const onLongPress = (event) => {
        showItemMenu(event)
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
            
            addItem('削除する', () => deleteMessage(props.message.id) )
            
        }
        if (!isDraft) {
            addItem('未読にする', () => markUnread(props.message.id))
        }
          

        return list
    })
    const readableText = computed(() => {
        const textContent = messageBoxBody.value?.textContent
        return textContent?.replace(/https?:\/\/[^\s]+/g, '') ?? '';
    })
    const authorized = computed(() => {
        return props.message.user_id == auth.activeUser.id
    })
    const reminded = computed(() => {
        const list = props.message.message_remind_users
        return list ? list.some(item => item.user_id === auth.activeUser.id && item.reminded === 1) : false
    })
    const checkSendIconColor = computed(() => {                
        const check_list = props.message.reacted_users.filter(ob => ob.id == auth.activeUser.id).length                
        return (props.message.user_id == auth.activeUser.id || check_list) ? true : false              
        
    }) 
    const infoMessage = computed(() => {
        return props.message.message
    })
    const messageUserName = computed(() => {                
        return props.message.user && props.message.user.deleted_at == null
        ? props.message.user.name
        : '非アクティブユーザー';
    })
    const messageBody = computed(() => {
        return mentionFormatter(props.message.message, true)    
    })
    const reactedUsersListAll = computed(() => {
        return props.message.reacted_users && props.message.reacted_users.length ? Array.from(props.message.reacted_users).reverse() as User[] : []                
    })
    const checkFunctionView = computed(() => {
        if(props.message.check_flag == 1){
            let checked = props.message.checked_users.filter(ob => ob.id == auth.activeUser.id).length
            let unchecked = props.message.unchecked_users.filter(ob => ob.id == auth.activeUser.id).length
            return checked || unchecked || props.message.user_id == auth.activeUser.id
        }
        return false
        
    })
    const messageKind = computed(() => {
        if (props.message.draft_flag && props.message.reserved_at !== null) {
            return DateParser(props.message.reserved_at) + 'に送信予定'
        } else if (props.message.draft_flag) {
            return '下書き'
        } else {
            return DateParser(props.message.created_at)
        }
    })

    const reactButtonView = computed(() => {
        return !(props.message.user_id == auth.activeUser.id && !props.message.reacted_users.length)
    })
    const showItemMenu = (event: Event) => {
        if(itemMenuRef.value){
            itemMenuRef.value.longTapAction(event)
        }
    }
    const mentionClick = (event) => {            
        const target = event.target;
        if (target.classList.contains('mntuser')) {
            const username = target.getAttribute('data-username');
            const striped = username ? username.replace(/@/g, "") : '';
            const userid =  target.getAttribute('data-userid');
            if(username == '全員') return
            pushInstantUser(event, userid, striped)
        }
        menu.close()   
    }
    const unreadLineVisible = () => {
        
        setTimeout(() => {
            const rect = messageBox.value?.getBoundingClientRect()
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
    const replyQuotStart = (which) => { 
        if(!messageBoxBody.value) return
        const widthS = messageBoxBody.value.clientWidth + 20;
        const heightS = messageBoxBody.value.clientHeight + 20;
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
            send_list: props.message.unchecked_users.map(ob => ob.id),
            board_id: openedBoard.value?.id,
            send_condition: 2,
            msg_id: props.message.id
        }, {
            ask: '未確認者へ確認依頼のメールを送りますか？',
            toast: '再確認依頼のメールを送信しました。'
        })
    }
  

      
    
    const copyTextStart = () => {   
        if(!messageBoxBody.value) return
        copy({
            height: messageBoxBody.value.clientHeight + 20,
            width: messageBoxBody.value.clientWidth + 20,
            text: messageBoxBody.value.textContent
        })             
    }
    const viewReactedUsersList = () => {
        const data = {
            active: true,
            userList: reactedUsersListAll.value,
            title: 'チェックしたメンバー'
        }
        messageUsers.setMessageUsers(data)
        
    }
    const viewCheckedUserList = () => {
        const data = {
            active: true,
            userList: props.message.checked_users,
            title: '確認済みメンバー'
        }
        messageUsers.setMessageUsers(data)
    }
    const viewunCheckedUserList = () => {
        const data = {
            active: true,
            userList: props.message.unchecked_users,
            title: '未確認メンバー'
        }
        messageUsers.setMessageUsers(data)
    }
    const deleteMessage = async (id) => {        
        await api.post('/chat_delete_api', {id: id}, {
            ask: 'メッセージを削除してもよろしいですか？',
            toast: 'メッセージを削除しました。'
        })  
        refreshMessages()
    }
    const markUnread = async(id) => {
        menu.close()
        await api.post('/chat_mark_unread', {
            message_id: id,
            user_id: auth.activeUser.id,
            board_id: openedBoard.value?.id
        }, {
            ask: 'メッセージを未読にしますか？',
            toast: '未読にしました。'
        })
        badge.getBoardBadge() 
        reload()    
        router.push({name: 'board'})     
        close()

    }
    const reactOrCheck = async(msg) => {        
        if(msg.user_id == auth.activeUser.id) return    
        reacting.value = msg.reacted_users.filter(ob => ob.id == auth.activeUser.id).length ? false : true    

        const message = await api.post('/send_reaction_api', {id: msg.id})
        await refreshMessages()
        const checkedMessage = message
        if(checkedMessage.check_flag == 1){
            const checked = checkedMessage.checked_users.filter(ob => ob.id == auth.activeUser.id).length
            const unchecked = checkedMessage.unchecked_users.filter(ob => ob.id == auth.activeUser.id).length
            const reacted =   checkedMessage.reacted_users.filter(ob => ob.id == auth.activeUser.id).length          
            if(unchecked && reacted){     
                const confirmed = await ask('確認済みにしますか')
                if(confirmed.value){
                    await api.post('/check_send_api', { message_id: msg.id, user_id: auth.activeUser.id, pattern: 'check' })                              
                    refreshMessages()    
                    toast('確認済みにしました。') 
                    badge.getRemindBadge()     
                }                                  
            }
            if(checked && reacted){                  
                ping('既に確認しています。')  
            }
        } 
           
    }           
    const shareToTask = inject<Function>('shareToTask') as Function

    const shareTo = (to) => {
        if(to == 'external'){
            navigator.share({
                text: props.message.message,
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
                text: props.message.message_text,
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
    const draftSend = async() => {
        if (draftSending.value) return
        draftSending.value = true
        await api.put('/draft_send', {id: props.message.id, draft_flag: 0})
        refreshMessages()
        setTimeout(() => {
            draftSending.value = false
        }, 200)
    }
    const setSchedule = () => {
        const data = {
            active: true,
            message_id: props.message.id
        }
        messageSchedule.setMessageSchedule(data)
    }
</script>
