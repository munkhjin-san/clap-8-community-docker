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
                <div style="display: flex;align-items: center;gap:10px">
                    <UserPanel size="30" :user="message.user" imgClass="userNormalIcon"/>                   
                    <div @click.stop="pushInstantUser($event, message.user_id)" class="cursor-pointer" style="font-size: 14px;">{{ messageUserName }}</div>     
                </div>                                     
                <div class="m-date">{{messageKind}}</div>  
                <div class="messageIconContainer">
                    <div v-if="message.deleted_at == null && message.message" title="読み上げる" class="relative">
                        <div class="boardMenuContainer" @click="readAloud(message)">   
                            <svg class="m-auto" fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32.57 26.53">
                                <path class="cls-1" d="M12.49,1.31l-3.5.03h-.03c-.4,0-.78.2-1.01.56l-1.71,2.6c-.46.7-.92,1.39-1.37,2.09-1.12.02-2.69.06-3.56.07-.48.03-.79.28-.95.59-.11.13-.18.29-.2.5-.1,1.79-.14,3.59-.14,5.38.01,1.76.03,3.58.17,5.33,0,.01,0,.02,0,.03,0,.63.49,1.15,1.12,1.16,1.18.02,2.37.04,3.55.05.56.84,3.09,4.67,3.09,4.67.22.33.6.55,1.03.56.02,0,3.5.02,3.52.03.72,0,1.3-.58,1.29-1.29,0-1.76.03-3.51.03-5.27.01-4.9.02-10.95-.03-15.83,0-.71-.58-1.28-1.29-1.27ZM6.48,17.82c-.22-.31-.57-.51-.98-.5-.87,0-2.35-.05-3.09-.09-.25-.01-.44-.16-.45-.48-.05-1.31-.08-5.53-.02-7.13.02-.42.31-.64.7-.66l2.86-.03c.38,0,.76-.18.98-.52.86-1.22,2-2.86,2.96-4.24.13-.19.35-.3.58-.3.25,0,.51,0,.87,0,.16,0,.29.13.29.3,0,1.23-.02,2.46-.02,3.69-.01,4.36-.01,9.63.01,14.17,0,.19-.16.35-.35.35h-.78c-.25,0-.48-.11-.62-.32-1.02-1.46-2.94-4.23-2.95-4.25Z"/>
                                <path class="cls-1" d="M30.96,5.82c-.65-1.41-1.51-2.73-2.5-3.93-.6-.71-1.23-1.41-2.08-1.83-.53-.27-1.11.31-.84.84.34.76.9,1.47,1.36,2.15.74,1.15,1.37,2.41,1.82,3.69,1.78,5.13,1.38,11.03-1.41,15.73-.57.95-1.25,1.82-1.93,2.72-.23.29-.24.71,0,1.01.28.36.8.43,1.16.15.98-.76,1.79-1.7,2.53-2.7,3.81-5.02,4.64-12.13,1.9-17.83Z"/>
                                <path class="cls-1" d="M25.26,8.18c-.46-.95-1.07-1.82-1.76-2.61-.42-.48-.86-.94-1.49-1.14-.51-.18-1.02.34-.84.84.13.54.48,1.01.77,1.46l.26.46c.98,1.82,1.42,3.9,1.42,5.94-.02,2.03-.43,4.12-1.45,5.92-.35.62-.78,1.18-1.17,1.8-.48.75.42,1.64,1.17,1.14.69-.47,1.25-1.1,1.76-1.77.51-.67.96-1.38,1.34-2.15,1.54-3.05,1.52-6.85,0-9.9Z"/>
                                <path class="cls-1" d="M17.66,8.79c-.41,0-.76.3-.82.71-.03.15-.04.34-.02.46.02.16.11.32.17.45.58,1.39.56,2.92.32,4.38-.1.68-.47,1.14-.69,1.78-.24.7.51,1.38,1.19,1.06.4-.19.7-.5.99-.83.28-.33.55-.69.77-1.08,1.17-1.96.79-4.63-.79-6.25-.33-.32-.59-.7-1.12-.69Z"/>
                            </svg>
                        </div>

                        <div v-if="ttsStore.active && ttsStore.id == message.id" class="absolute bg-[var(--bg3)] shadow-me rounded-md top-[-40px] left-0 w-min" >
                            <div class="flex items-center gap-[5px]">
                                <div v-if="ttsStore.play" class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer" @click="stopPlay(message.id)">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="var(--primary-color)">
                                        <rect height="40" width="10" y="5" x="10"></rect>
                                        <rect height="40" width="10" y="5" x="30"></rect>
                                    </svg>
                                </div>
                                <div v-else class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer" @click="stopPlay(message.id)">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="var(--primary-color)">
                                        <polygon points="10,5 40,25 10,45"></polygon>
                                    </svg>
                                </div>
                                <div @click="endPlay" class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="tomato">
                                        <rect height="30" width="30" y="10" x="10"></rect>
                                    </svg>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div v-if="message.deleted_at == null || reminded" title="リマインド" class="boardMenuContainer" @click="remind(message)">
                        <svg :style="{ opacity : reminded ? 1 : 0.5 }" xmlns="http://www.w3.org/2000/svg" class="m-auto" fill="fill:var(--kebab-icon)" version="1.1" x="0px" y="0px" height="13" viewBox="0 0 27 29" enable-background="new 0 0 26 29" xml:space="preserve">
                            <path d="M25.469,20.171c-0.7-0.206-1.325-0.619-1.875-1.108c-0.156-0.436-0.258-1.137-0.337-1.714  c-0.223-1.772-0.337-3.599-0.568-5.4c-0.225-1.931-0.658-4.1-1.937-5.683c-1.059-1.357-2.512-2.479-4.189-2.918  c-0.066-0.017-0.112-0.075-0.112-0.143c0.001-0.889,0.002-1.944,0.002-1.944C16.452,0.563,15.887,0,15.19,0  c-0.003,0.001-3.967,0-3.97,0.001c-0.696,0.001-1.261,0.566-1.26,1.262l0.002,1.943c0,0.068-0.046,0.126-0.111,0.143  c-1.678,0.44-3.13,1.561-4.189,2.918c-1.867,2.38-1.902,5.581-2.224,8.422c-0.086,0.902-0.167,1.799-0.277,2.661  c-0.085,0.601-0.146,1.16-0.335,1.698c-0.004,0.01-0.008,0.021-0.012,0.029c-0.19,0.17-0.688,0.562-0.969,0.706  c-0.289,0.167-0.585,0.305-0.9,0.394c0.041-0.03-0.948,1.155-0.945,1.155c0.001,0.015,0.017,2.729,0.019,2.741  c0.004,0.636,0.522,1.147,1.159,1.143c2.012-0.012,5.394-0.045,8.306-0.076c-0.027,0.112-0.038,0.231-0.025,0.354  c0.007,0.051,0.015,0.156,0.024,0.206c0.131,0.869,0.464,1.659,1.089,2.321c1.045,1.095,2.678,1.354,4.108,0.914  c1.402-0.504,2.303-2.001,2.318-3.443c0.008-0.115-0.001-0.222-0.02-0.32c2.899,0.021,6.253,0.041,8.257,0.053  c0.642,0.004,1.165-0.513,1.168-1.154c0.003-0.012,0.012-2.726,0.016-2.737C26.423,21.332,25.428,20.14,25.469,20.171   M23.537,19.014c0,0,0.002,0.002,0.003,0.002c-0.006-0.005-0.012-0.01-0.012-0.01C23.52,18.998,23.533,19.01,23.537,19.014   M4.502,20.775c0.779-0.735,0.893-2.135,1.055-3.106c0.127-0.933,0.216-1.84,0.31-2.74c0.187-1.71,0.342-3.536,0.779-5.15  c0.507-1.773,1.895-3.339,3.644-3.939c0.332-0.112,0.729-0.203,1.012-0.277c0.796-0.209,1.008-0.459,1.009-1.151  c0,0,0.001-1.216,0.002-2.071c0-0.092,0.074-0.167,0.168-0.167h1.491c0.093,0,0.168,0.075,0.168,0.168  c0.001,0.854,0.002,2.07,0.002,2.07c0,0.693,0.302,1.014,1.031,1.188c2.149,0.252,4.041,2.189,4.595,4.18  c0.653,2.528,0.717,5.269,1.085,7.892c0.083,0.588,0.178,1.231,0.356,1.842c0.126,0.409,0.271,0.817,0.612,1.182  c0.651,0.607,1.407,1.135,2.236,1.486c0.002,0.307,0.002,0.365,0.004,0.714c-3.2,0.019-8.211,0.051-10.854,0.078  c-0.094,0.004-0.181,0.019-0.263,0.038c-2.706-0.032-7.499-0.083-10.598-0.105c0.002-0.355,0.003-0.416,0.005-0.728  C3.143,21.84,3.866,21.341,4.502,20.775 M14.984,25.282c-0.001-0.019-0.008,0.005-0.012,0.012l-0.017,0.036  c-0.16,0.356-0.385,0.793-0.687,1.014c-0.139,0.112-0.296,0.146-0.448,0.225c-0.31,0.149-0.857,0.176-1.188,0.07  c-0.591-0.15-0.941-0.739-1.098-1.311l-0.01-0.037c-0.003-0.007-0.007-0.03-0.006-0.011c-0.006-0.057-0.018-0.11-0.031-0.162  c0.529-0.006,1.019-0.012,1.455-0.017c0.082,0.021,0.169,0.034,0.263,0.038c0.521,0.005,1.132,0.012,1.802,0.017  C14.999,25.197,14.99,25.238,14.984,25.282"/>
                        </svg>
                    </div>                    
                    <ItemMenu v-if="message.deleted_at == null" type="share" :items="shareMenuItems" fit="boardListInner"/>
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
                    <div class="commentEditButton" @click="draftSend">送信</div>
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
        
        <div v-bind="unreadLineVisible(unreadMessages)" @click="refresh" v-if="unreadMessages.id == message.id" :id="'unread_line_' + message.id" class="cursor-pointer" style="user-select:none;width:100%;border-bottom:solid thin #a09f9f;position: absolute;bottom:10px;font-size:12px;">
            <p class="unread-inner" style="margin-bottom: -12px;">新しいメッセージ</p>
        </div>
    </div>
</template>

<script setup>

import MessageQuoteReply from "./MessageQuoteReply.vue";
import MessageFiles from "./MessageFiles.vue";
import { computed, inject, onMounted, ref } from 'vue'
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
import { convertToSpeech, endPlay, stopPlay } from "@/utils/tts";
import { useTtsStore } from "@/store/ttsStore";
import UserPanel from "@/components/Global/UserPanel.vue";
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
    const messageBox = ref(null)
    const messageBoxBody = ref(null)
    const board = inject('openedBoard')
    const { refreshMessages, close, reload, messageLoader } = inject('boardItem')    
    const { copy, remind, check } = inject('messageItem')
    const { notify, confirm, info } = inject('dialog')
    const pushInstantUser = inject('pushInstantUser')
    const itemMenuRef = ref(null)
    const longPressDuration = ref(500)
    const longPressTimer = ref(null)
    const isLongPress = ref(false)
    const messageSchedule = useMessageSchedule()
    const ttsStore = useTtsStore()
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
        const list= []; 
        function addItem(title, action) {
            list.push({ title, action });
        }
        const builtInApps = [
            {name: 'board', name_jp: 'ボード'}, 
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
        const canConfirm = props.message.emoji_flag == 0 && board?.value.private_flag !== 3
        const isDraft = props.message.draft_flag
        const list= []; 
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
    const readAloud = () => {
        const textContent = messageBoxBody.value?.textContent
        const cleanedText = textContent?.replace(/https?:\/\/[^\s]+/g, '');
        convertToSpeech(cleanedText, props.message.id)
    }
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
        return props.message.reacted_users && props.message.reacted_users.length ? Array.from(props.message.reacted_users).reverse() : []                
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
    const showItemMenu = (event) => {
        if(itemMenuRef.value && itemMenuRef.value.show){
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
    const unreadLineVisible = (data) => {
        
        setTimeout(() => {
            const rect = messageBox.value?.getBoundingClientRect()
            if(badge.activeUsersBoardBadge[board.value.id] && (rect.y + rect.height < 0)){
                const data = {
                    status: true,
                    count: props.unreadMessages.count,
                    id: props.message.id
                }
                emit('unreadJumperOn', data)
                
            
            }
            
        })
    }
    const replyQuotStart = (which) => { 
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
    const closeMenu = () => {
        menu.close()
    }
    const resendConfrim = async() => {      
        const confirmed = await confirm('未確認者へ確認依頼のメールを送りますか。')
        if(!confirmed.value) return
        try {
            const send_users = props.message.unchecked_users.map(ob => ob.id) 
            await axios.post('/send_reconfirm_email',{send_list: send_users, board_id: board.value.id, send_condition: 2, msg_id: props.message.id})
            info('再確認依頼のメールを送信しました。')
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } 
    }
  

      
    
    const copyTextStart = (id) => {   
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
        const confirmed = await confirm('このメッセージを削除してもよろしいですか?')
        if(!confirmed.value) return
        try{
            await axios.post('/chat_delete_api', {id: id})                  
            refreshMessages()
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }                
    }
    const markUnread = async(id) => {
        try{
            closeMenu()
            await axios.post('/chat_mark_unread', {message_id: id, user_id: auth.activeUser.id, board_id: board.value.id}) 
            info('未読にしました。')
            badge.getBoardBadge() 
            reload()    
            router.push({name: 'board'})     
            close()
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }  
    }
    const reactOrCheck = async(msg) => {        
        if(msg.user_id == auth.activeUser.id) return    
        reacting.value = msg.reacted_users.filter(ob => ob.id == auth.activeUser.id).length ? false : true    
        try{
            const response = await axios.post('/send_reaction_api', {id: msg.id})
            await refreshMessages()
            const checkedMessage = response.data
            if(checkedMessage.check_flag == 1){
                const checked = checkedMessage.checked_users.filter(ob => ob.id == auth.activeUser.id).length
                const unchecked = checkedMessage.unchecked_users.filter(ob => ob.id == auth.activeUser.id).length
                const reacted =   checkedMessage.reacted_users.filter(ob => ob.id == auth.activeUser.id).length          
                if(unchecked && reacted){     
                    const confirmed = await confirm('確認済みにしますか')
                    if(confirmed.value){
                        await axios.post('/check_send_api', { message_id: msg.id, user_id: auth.activeUser.id, pattern: 'check' })                              
                        refreshMessages()    
                        info('確認済みにしました。') 
                        badge.getRemindBadge()     
                    }                                  
                }
                if(checked && reacted){                  
                    notify('既に確認しています。')  
                }
            } 
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }              
    }           
    const {shareToTask} = inject('taskItem')
    const shareTo = (to, flag, single_file) => {
        if(to == 'external'){
            navigator.share({
                text: props.message.message,
                files:[]
            })

        }else{
            let files = []
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
                instruction: to == 'board' ? '送る先のボードを選択してください' : ''
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
        
        closeMenu()
    }
    const draftSend = async() => {
        try{
            await axios.put('/draft_send', {id: props.message.id, draft_flag: 0})
            await refreshMessages()
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            sending.value = false
        }
    }
    const setSchedule = () => {
        const data = {
            active: true,
            message_id: props.message.id
        }
        messageSchedule.setMessageSchedule(data)
    }
</script>
