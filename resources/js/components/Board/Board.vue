<template>
        <div class="boardOuterContainer" style="width: 100%;height: 100%;display:flex;flex-grow: 1;overflow: hidden;">     
            <div class="boardInnerContainer relative">        
                <Transition name="searchHide">
                <BoardSearchBar 
                    v-if="searchView"
                    @openBoard="openTargetBoard"
                    @openMessageSearch="openMessageSearch"
                />
                </Transition>
                <BoardList 
                    v-show="!responsive.mobile || (responsive.mobile && route.name == 'board')"  
                    :failedMessagesList="failedMessagesList"  
                    :key="listKey"
                    @onScroll="onScroll"  
                />
                <div v-if="boardLoader" id="infiniteLoader" style="top: auto; bottom: 20px;">
                    <div class="spinner-micro color-change"></div>
                </div>
            </div>
            <Transition name="modalFade">
                <BoardDetails v-if="detailedBoard" :board="detailedBoard" @close="detailedBoard = null"/>
            </Transition>
            
            <Transition name="modalFade">
                <SearchMessage 
                    v-if="searchMessageView"
                    :advancedSearchWord="advancedSearchWord"
                    :filteredAllBoard="filteredAllBoard"
                    :privateSearch="privateSearch"
                    :key="searchWindowKey"
                    @closeMessageSearch="closeMessageSearch"
                    @jumpToMessage="jumpToMessage"
                />
            </Transition>
            <Transition name="modalFade" mode="out-in">
                <BoardEdit 
                    v-if="activeEditBoard"
                    :editTarget="activeEditBoard"
                    @close="activeEditBoard = null"
                />
            </Transition>

            
            <router-view v-slot="{ Component }">
                <transition name="slideFromRight">
                    <component 
                        ref="messageContainerRef"
                        :is="Component" 
                        :messageLoader="messageLoader"
                        :key="messageContainerKey"
                        :messageList="messageList"
                        :openedBoard="openedBoard"
                        :microLoader="microLoader"
                        :queuedMessages="queuedMessages"  
                        :messageListType="listType" 
                        :searchTargetId="searchTargetId"
                        :unreadMessages="unreadMessages"
                        @reload="getMessageList"    
                        @appendSearchResult="appendSearchResult"
                        @afterRequestHandled="afterRequestHandled"
                        @closeContainer="closeMessageContainer"      
                        @reachedTop="reachedTop"                
                        @jumpToMessage="jumpMessageFromFile"
                    />
                </transition>
            </router-view> 
            
            <TrayComponent
                v-if="openedBoard && !responsive.mobile"
                :key="`${trayComponentKey}_${openedBoard.id}`" 
                :board="openedBoard"
                :trayItemWhich="trayItemWhich"
                @setTrayItem="setTrayItem"
                @jumpToMessage="jumpMessageFromFile"
            />
            <Transition name="modalFade">
                <CopyWindow v-if="copyData" :data="copyData" @close="copyData = null"/>
            </Transition>
            <Transition name="modalFade">
                <ConfirmWindow @reload="getMessageList" v-if="checkRequestData" :requestType="requestType" :message="checkRequestData" @close="checkRequestData = null"/>
            </Transition>  
            <Transition name="modalFade">
                <InviteMember @close="inviteTarget = null" v-if="inviteTarget" :item="inviteTarget" @reload="boardEditFinished"/>
            </Transition>      
            <Transition name="modalFade">
                <BoardCreateWindow 
                    @close="newBoardWindow = false" 
                    v-if="newBoardWindow"
                    @reload="boardEditFinished"
                />
            </Transition> 

            <Transition name="modalFade">
                <BoardMembers 
                    :board="reactiveMemberList"
                    @close="viewingMembersOf = null" 
                    v-if="viewingMembersOf"
                    @reload="boardEditFinished"
                    @afterRequestHandled="afterRequestHandled"
                />
            </Transition> 
        </div>
    <!-- </Transition> -->
    </template>
    
<script setup lang="ts">
import BoardList from './BoardList.vue'
import { onMounted, onUnmounted, watch, computed, nextTick, ref, provide, onBeforeUnmount, useTemplateRef, defineAsyncComponent } from 'vue'
import TrayComponent from './Tray.vue'
import BoardSearchBar from './Search/BoardSearchBar.vue'
// import InviteMember from './InviteMember.vue'
// import BoardCreateWindow from './BoardCreateWindow.vue'
// import BoardMembers from './BoardMembers.vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'
import { useMenuStore } from "@/store/menu";
import { useQuoteReply } from '@/store/quoteReply'
import { useFocused } from '@/store/focused'
import { useUrlTask } from '@/store/urlTask'
import { useUrlMessage } from '@/store/urlMessage'
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import { useBadgeStore } from '@/store/badge'

// import BoardDetails from './BoardDetails.vue'
// import SearchMessage from './Search/SearchMessage.vue'
// import BoardEdit from './BoardEdit.vue'
// import CopyWindow from './Message/CopyWindow.vue'
// import ConfirmWindow from './Message/ConfirmWindow.vue'
import { instance } from '@/utils/broadcaster'
import { useKeyboardStore } from '@/store/keyboardStore'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { useBoardList } from '@/composables/board'
import { Board, CopyData, Message, UnreadMessages } from '@/interface/globalInterface'
import { BoardMethodsKey, MessageMethodsKey } from '@/interface/keys'
import { DateTime } from 'luxon'
    const BoardDetails = defineAsyncComponent(() => import('./BoardDetails.vue'))
    const SearchMessage = defineAsyncComponent(() => import('./Search/SearchMessage.vue'))
    const BoardEdit = defineAsyncComponent(() => import('./BoardEdit.vue'))
    const CopyWindow = defineAsyncComponent(() => import('./Message/CopyWindow.vue'))
    const ConfirmWindow = defineAsyncComponent(() => import('./Message/ConfirmWindow.vue'))
    const InviteMember = defineAsyncComponent(() => import('./InviteMember.vue'))
    const BoardCreateWindow = defineAsyncComponent(() => import('./BoardCreateWindow.vue'))
    const BoardMembers = defineAsyncComponent(() => import('./BoardMembers.vue'))
    
    const badge = useBadgeStore()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const quoteReply = useQuoteReply()
    const focused = useFocused()
    const urlTask = useUrlTask()
    const urlMessage = useUrlMessage()
    const urlTaskEdit = useUrlTaskEdit()
    const route = useRoute()
    const router = useRouter()
    const mainLoader = ref(false)
    const allBoardList = ref<Board[]>([])
    const nextMessageCursor = ref<string | null>(null)
    const reachedMessageEnd = ref(false)
    const activeEditBoard = ref<Board | null>(null)
    const pageIndex = ref(1)
    const pageLimiter = ref(false)
    const messageList = ref<Message[]>([])
    const copyData = ref<CopyData | null>(null)
    const checkRequestData = ref(null)
    const microLoader = ref(false)
    const boardLoader = ref(false)
    const currentLen = ref(0)
    const infiniteLock = ref(false)
    const messageContainerKey = ref(0)
    const queuedMessages = ref<Message[]>([])
    const messageLoader = ref(true)
    const failedMessagesList = ref([])
    const trayComponentKey = ref(1999)
    const searchWindowKey = ref(27000)
    const advancedSearchWord = ref('')
    const searchMessageView = ref(false)
    const listType = ref('normal')
    const privateSearch = ref(false)
    const searchTargetId = ref(null)
    const scrllDir = ref('up')
    const appendLock = ref(false)
    const trayItemWhich = ref(-1)
    const detailedBoard = ref<Board | null>(null)
    const unreadMessages = ref<UnreadMessages>({
        active: false,
        id: null,
        count: 0
    })
    const listKey = ref(986)
    const searchView = ref(true)
    const inviteTarget = ref(null)
    const newBoardWindow = ref(false)
    const viewingMembersOf = ref<number | null>(null)
    const requestType = ref('')
    const routeWatchLock = ref(false)
    const messageContainerRef = useTemplateRef('messageContainerRef')
    const keyboardStore = useKeyboardStore()
    const activeListeners = new Set<string>();
    const api = useApi()
    const { toast, ask } = useDialog()
    const { openedBoard, setList, boardList, setNextCursor, setReachEnd, nextCursor, reachEnd, setSkeleton } = useBoardList()
    const BOARD_REFRESH_COOLDOWN_MS = 2000
    let boardRefreshTimer: ReturnType<typeof setTimeout> | null = null
    let boardRefreshInFlight = false
    let boardRefreshPending = false
    let lastBoardRefreshAt = 0
    const getRefreshAnchorId = () => {
        if(allBoardList.value.length){
            return allBoardList.value[allBoardList.value.length - 1].id
        }
        return openedBoard.value ? openedBoard.value.id : null
    }
    const removeBoardFromList = (id: number) => {
        allBoardList.value = allBoardList.value.filter(ob => ob.id !== id)
        setList(allBoardList.value)
    }
    const queueBoardListRefresh = () => {
        const now = Date.now()
        if(boardRefreshInFlight){
            boardRefreshPending = true
            return
        }
        if(mainLoader.value){
            boardRefreshPending = true
            return
        }
        if((now - lastBoardRefreshAt) < BOARD_REFRESH_COOLDOWN_MS){
            if(!boardRefreshTimer){
                boardRefreshTimer = setTimeout(() => {
                    boardRefreshTimer = null
                    queueBoardListRefresh()
                }, BOARD_REFRESH_COOLDOWN_MS - (now - lastBoardRefreshAt))
            }
            return
        }
        boardRefreshInFlight = true
        const anchorId = getRefreshAnchorId()
        getBoardList('refresh', anchorId, false).finally(() => {
            lastBoardRefreshAt = Date.now()
            boardRefreshInFlight = false
            if(boardRefreshPending){
                boardRefreshPending = false
                queueBoardListRefresh()
            }
        })
    }
    const refreshBoardList = () => {
        queueBoardListRefresh()
    }
    let badgeTimer: number | null = null;

    const scheduleBoardBadgeUpdate = () => {
        if (badgeTimer) clearTimeout(badgeTimer);

        badgeTimer = setTimeout(() => {
            const boardId = openedBoard.value?.id;
            if (!focused.active || !boardId) return;

            const exists = badge.activeUsersBoardBadge?.[boardId];
            if (exists) badge.updateBoardBadge(boardId);
        }, 3000);
    };

    watch(() => focused.active, (after) => {
        if (after) scheduleBoardBadgeUpdate();
    });

    watch(() => badge.activeUsersBoardBadge, () => {
        if (focused.active) scheduleBoardBadgeUpdate();
    }, { deep: true });


    watch(() => route.params.chatId, (chatId) => {
        if (messageContainerRef.value && (messageContainerRef.value as any).resetUnread) {
            (messageContainerRef.value as any).resetUnread();
        }
        if(chatId){
            const targetBoard = filteredAllBoard.value.find(ob => ob.id === Number(chatId))          
            if(routeWatchLock.value){
                return
            }
            if(targetBoard){
                openBoard(targetBoard, 'watch')   
            }
        }else{
            closeMessageContainer()
        }
         
        
    })
    onBeforeUnmount(() => {
        menu.setMenu( {name: '', id: null})
    })        
    onUnmounted(() => {        
        const Navigator: any = navigator;    
        if(Navigator.virtualKeyboard){
            Navigator.virtualKeyboard.removeEventListener('geometrychange', keyboardHeightListener);
        }
        instance.off('refresh:board', updateBoardHandler)    
        clearListeners()
        setNextCursor(null)
    })
    onMounted(() => {
        const trayIndex = localStorage.getItem('favorite_tray');
        trayItemWhich.value = trayIndex ? parseInt(trayIndex) : 1

        const Navigator: any = navigator; 
        if(Navigator.virtualKeyboard){
            Navigator.virtualKeyboard.addEventListener('geometrychange', keyboardHeightListener);
        }
        closeMessageContainer()        
        listKey.value ++
        const url_string = window.location.href;
        const url = new URL(url_string);
        const m_id = url.searchParams.get("m");
        if(m_id){
            urlMessage.setUrlMessageId(parseInt(m_id))                
        }
        const t_id = url.searchParams.get("t");
        if(t_id){    
            urlTask.setUrlTaskId(parseInt(t_id))            
            const data = {status : true, val: 1}
            const t_edit = url.searchParams.get("task_edit");
            if(t_edit){     
                if(t_edit === 'true'){
                    urlTaskEdit.setUrlTaskEdit(true)
                }                              
            }
            setTrayItem(data.val)
                        
        }

        getBoardList('mounted')
        getUnsentMessages();
        badge.getTaskBadge();      
        instance.on('refresh:board', updateBoardHandler)
    })        
    const updateBoardHandler = (data) => {
        const related = data && data.length? data[0] : []
        if(related.includes(auth.id) || related.includes(auth.activeUser.id)){
            queueBoardListRefresh()
        }
    }
    const socketMessageHandler = (data) => {        
        if(openedBoard.value && listType.value == 'normal'){            
            getMessageList('pusher'); 
        }       
    }
    const onPusher = (e) => {


    }
    const reactiveMemberList = computed(() =>{
        return filteredAllBoard.value ? filteredAllBoard.value.filter(ob => ob.id == viewingMembersOf.value)[0] : null
        
    })
    const filteredAllBoard = computed(() => boardList.value )

    const openedBoardId = computed(() => {
        return route.params.chatId ? Number(route.params.chatId) : null
    })

    const keyboardHeightListener = (event) => {
        const { height } = event.target.boundingRect;
        keyboardStore.height = height
    }
    const boardDelete = async(item) => {       
        const data = await api.post('/board_delete', { id: item.id }, {
            ask: 'チャットを削除しますか？',
            toast: '削除しました。'
        })
        if(!data) return
        if(openedBoard.value && openedBoard.value.id == item.id){                                
            closeMessageContainer()
        }
        removeBoardFromList(item.id)
        refreshBoardList()
    }

    const afterRequestHandled = (response, id) => {
        if(response === 'respondDeleted'){
            closeMessageContainer()
            refreshBoardList()
        }else if(response === 'respondConfirmed'){
            const anchorId = getRefreshAnchorId()
            getBoardList('refresh', anchorId, false).then(() => {
                if(id){
                    const target = allBoardList.value.find(ob => ob.id == id)
                    if(target){
                        openTargetBoard(target)
                    }
                }
            })
        }
    }
    const setTrayItem = (val) => {
        trayItemWhich.value = val
        localStorage.setItem('favorite_tray', val)
    }
    
    const appendSearchResult = async(dir) => {
        if(scrllDir.value !== dir){
            scrllDir.value = dir
            appendLock.value = false 
        }
        if(appendLock.value || !messageList.value.length) return
        appendLock.value = true
        let lastMessage: string | number | null = null;
        if(scrllDir.value == 'up'){
            lastMessage = messageList.value[messageList.value.length - 1].id
        }else if(scrllDir.value == 'down'){
            lastMessage = messageList.value[0].id
        }
        
        const currentLength = messageList.value.length
        let data = {
            direction: scrllDir.value,
            last_message_id: lastMessage
        }
        var container1 = document.getElementById('boardListInner')
        var currentPos = container1?.scrollHeight; 
        microLoader.value = true
        const res = await api.post('/get_bottom_messages', data)
        if(res) {  
            if(scrllDir.value == 'up'){
                messageList.value = messageList.value.concat(res)
                if(messageList.value.length !== currentLength){
                    appendLock.value = false 
                }
            }else if(scrllDir.value == 'down'){
                messageList.value = res.concat(messageList.value)
                if(messageList.value.length !== currentLength){
                    appendLock.value = false 
                }
                nextTick(() => {                   
                    var cont = document.getElementById('boardListInner')            
                    if(cont && currentPos){
                        cont.scrollTop = currentPos - cont.scrollHeight 
                    }                 
                });                  
            }         
        }
        setTimeout(() => {microLoader.value = false}, 200)    
    }
    const jumpMessageFromFile = (file) => {                  
        const target = {
            id: file.message_id,
            record_id: file.board_id
        }                  
        jumpToMessage(target)                
    }
    const jumpToMessage = async(message) => {
        messageLoader.value = true
        const data = await api.post('/get_target_message', message)
        if(data){              
            let board = filteredAllBoard.value.find( obj => obj.id == message.record_id);
            if(board){
                openBoard(board, 'search')
                setTimeout(() => {
                    document.getElementById('board_item_' + board?.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' }) 
                },100)                         
                messageList.value = data;
                messageContainerKey.value ++;
                messageLoader.value = false
                searchMessageView.value = false
                searchTargetId.value = message.id                        
                listType.value = 'search'                        
            }
            appendLock.value = false            
            
            messageLoader.value = false                        
        }else{
            urlMessage.setUrlMessageId(null) 
        }
    }
    const startPrivateSearch = () => {
        privateSearch.value = true
        searchMessageView.value = true
    }
    const openMessageSearch = (keyword) => {
        privateSearch.value = false
        advancedSearchWord.value = keyword
        // document.getElementById('boardSearchArea').blur();
        searchMessageView.value = true
    }
    const closeMessageSearch = () => {
        searchMessageView.value = false
    }

    const openTargetBoard = (item) => {
        openBoard(item)
        setTimeout(() =>{document.getElementById('board_item_' + item.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' })  },0)       
    }
    const removeError = (id) => {
        const index = queuedMessages.value.map(e => e.id).indexOf(id);        
        if(index > -1){
            queuedMessages.value = queuedMessages.value.filter(ob => ob.id !== id)
        }
        var failedList = localStorage.getItem('failed_messages');
        if(failedList){
            let data = JSON.parse(failedList)
            const index = data.map(e => e.id).indexOf(id);
            if(index > -1){
                data = data.filter( ob => ob.id !== id)
                localStorage.setItem('failed_messages', JSON.stringify(data))                
                openedBoard.value && getUnsentMessages(openedBoard.value.id);
            }            
        }
    }
    const sendError = (item) => {
        let err = item
        err.error = true
        var failedList = localStorage.getItem('failed_messages');
        if(failedList){
            let data = JSON.parse(failedList)
            const index = data.map(e => e.id).indexOf(item.id);
            if(index == -1){
                data.push(err)
                localStorage.setItem('failed_messages', JSON.stringify(data));
            }            
        }else{
            let data: Message[] = [];
            data.push(err)
            localStorage.setItem('failed_messages', JSON.stringify(data));
        }
        openedBoard.value && getUnsentMessages(openedBoard.value.id)        
    }
    const sentMessage = (item, list: Message[], last_message:any) => {
        pageIndex.value = 1;
        pageLimiter.value = false
        if(item){
            removeError(item.id)
            let box = document.getElementById('queueMessage_' + item.u_id);                       
            if(box){                            
                box.style.display = 'none'
            }
            getUnsentMessages(item.record_id);
            const data = {
                active: false,
                id: null,
                count: 0
            }
            unreadMessages.value = data
        }
        list.forEach( message => {
            const exists = messageList.value.find( ob => ob.id == message.id)
            if(!exists){
                messageList.value.unshift(message)
            }
        })
        const targetBoard = allBoardList.value.find( ob => ob.id == item.record_id)
        if(targetBoard){
            const stamp = list.length ? list[0].created_at : DateTime.now().toISO()
            targetBoard.last_message = last_message
            targetBoard.updated_at = stamp
            allBoardList.value = allBoardList.value.sort((a, b) => {
                return new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime();
            });
        }
        // getMessageList('queue', item);
        // getBoardList()
        const boardId = item.record_id

        if(item && item.attached_temp_files && item.attached_temp_files.length){
            trayComponentKey.value ++;
        }        
    }
    const closeMessageContainer = () => {
        keyboardStore.setKeyboardHeight(0)
        messageList.value = [];
        // messageContainerKey.value ++
    }
    const reachedTop = () => {
        currentLen.value = messageList.value.length
        if(reachedMessageEnd.value) return
        if(!pageLimiter.value && !infiniteLock.value){
            pageLimiter.value = true
            pageIndex.value ++ 
            getMessageList('infiniteLoader')
            microLoader.value = true
        }       
    }
    const remindRequest = async(message) => {
        const res = await api.post('/remind_add', { id: message.id })
        const inf = res.reminded === true ? 'リマインドしました。' : 'リマインドを取り消しました。'
        toast(inf)
        badge.getRemindBadge()
        refreshMessages(res.data)
        
    }
    const checkRequest = (data, request) => {
        checkRequestData.value = data
        requestType.value = request
    }
    
    const getUnsentMessages = (id?:number) => {
        var failedList = localStorage.getItem('failed_messages');
        if(failedList){
            let data = JSON.parse(failedList)
            failedMessagesList.value = data.filter(ob => ob.user_id == auth.activeUser.id)
            const failed = data.filter(ob => ob.record_id == id && ob.user_id == auth.activeUser.id)
            if(failed.length){
                queuedMessages.value = failed
            }          
        }
    }

    const openBoard = async(item: Board, second_atr?:any) => {
        messageLoader.value = true        
        pageIndex.value = 1;
        pageLimiter.value = false;
        microLoader.value = false;
        currentLen.value = 0;
        unreadMessages.value = {
            active: false,
            id: null,
            count: 0
        }
        if(badge.activeUsersBoardBadge && badge.activeUsersBoardBadge[item.id]){            
            const count = badge.activeUsersBoardBadge[item.id]                    
            const index = Math.ceil(count / 30)
            pageIndex.value = index
            const self = item.board_to_users.find( ob => ob.user_id == auth.activeUser.id)
            if(self){
                const data = {
                    active: true,
                    id: self.last_message,
                    count: badge.activeUsersBoardBadge[item.id]
                }
                unreadMessages.value = data
            }                                      
        }
        infiniteLock.value = false
        queuedMessages.value = []
        nextMessageCursor.value = null
        reachedMessageEnd.value = false
        getUnsentMessages(item.id)
        resetReplyQuot()
                 
           

        routeWatchLock.value = true

        router.push(`/board/${item.id}`);
        setTimeout(() => {
            if(second_atr !== 'search'){
                if(urlMessage.id){
                    const atr = {
                        id: urlMessage.id,
                        record_id: item.id
                    }
                    jumpToMessage(atr)
                }else{                
                    getMessageList('first_load', undefined, item.id);
                }            
            }  
            microLoader.value = false
            searchWindowKey.value ++;
        });

        setTimeout(() => {
            routeWatchLock.value = false
        }, 100);

        clearListeners()
        instance.on(`board:${item.id}`, socketMessageHandler)
        activeListeners.add(`board:${item.id}`);
    }
    const clearListeners = () => {
        activeListeners.forEach(listener => {
            if (listener.startsWith('board:') || listener.startsWith('task:')) {
                instance.off(listener, socketMessageHandler);
                activeListeners.delete(listener);
            }
        });
    }
    const getMessageList = async(source?:string, queue?:any, chatId?:number) => {
        const boardId = Number(route.params.chatId) || chatId 
        if(!boardId) return
        
        const payload: any = {}
        const useCursor = source !== 'pusher' && nextMessageCursor.value
        if (useCursor) payload.cursor = nextMessageCursor.value
        payload.record_id = boardId
        const message_id = unreadMessages.value?.id ?? null
        const message_count = unreadMessages.value?.count ?? 0
        if (message_id && message_count > 30) payload.message_id = message_id
        const response = await api.post('/get_messages', payload, { cancel: source !== 'pusher' })
        if(!response) {
            if(source == 'infiniteLoader'){
                pageLimiter.value = false
                microLoader.value = false
            }
            return
        }
        const rows = response?.messages?.data ?? []
        listType.value = 'normal' 
        if (source == 'first_load') {
            messageList.value = []
            messageList.value = rows
        } else if (source == 'pusher') {
            if(!messageList.value.length){
                messageList.value = rows
                nextMessageCursor.value = response?.messages?.next_cursor ?? null
                reachedMessageEnd.value = !nextMessageCursor.value
            }else if(rows.length){
                const existingIds = new Set(messageList.value.map(ob => ob.id))
                const freshRows = rows.filter(ob => !existingIds.has(ob.id))
                if(freshRows.length){
                    messageList.value = freshRows.concat(messageList.value)
                }
            }
        } else {
            messageList.value.push(...rows)
        }
        if(source == 'infiniteLoader'){                        
            setTimeout(() => { 
                pageLimiter.value = false
                microLoader.value = false
            },500)
        }
        infiniteLock.value = currentLen.value == messageList.value.length
        if(source == 'first_load'){        
            const hasUnread = badge.activeUsersBoardBadge[boardId] || 0   
            if(hasUnread){
                badge.updateBoardBadge(boardId)
            }         
            
        }            
        if(source !== 'pusher'){
            nextMessageCursor.value = response?.messages?.next_cursor ?? null
            reachedMessageEnd.value = !nextMessageCursor.value    
        }
        messageLoader.value = false
        
    }
    const unreadLineTrigger = () => {
        if(openedBoardId.value){
            const board = filteredAllBoard.value.filter(ob => ob.id == openedBoardId.value)
            if(board.length && badge.activeUsersBoardBadge[openedBoardId.value]){
                const self = board[0].board_to_users.filter( ob => ob.user_id == auth.activeUser.id)
                if(self.length){
                    const data = {
                        active: true,
                        id: self[0].last_message,
                        count: badge.activeUsersBoardBadge[openedBoardId.value]
                    }
                    unreadMessages.value = data
                }   
            }
            
        }
    }
    const boardEditFinished = (id) => {
        const anchorId = getRefreshAnchorId()
        getBoardList('refresh', anchorId, false).then(() => {
            if(id){
                const target = allBoardList.value.find(ob => ob.id == id)
                if(target){
                    openTargetBoard(target)
                }
            }
        })
        activeEditBoard.value = null
        if(openedBoard.value){
            getMessageList()
        }
    }
    const onScroll = (e) => {
        const el = e.currentTarget as HTMLElement
        if (el.scrollTop + el.clientHeight >= el.scrollHeight - 100) {
            getBoardList('scroll')
            if (reachEnd.value) return
            boardLoader.value = true
        }
    }
    const mergeUpsertById = (list: Board[], incoming: Board[]) => {
        const map = new Map<Board['id'], Board>()

        for (const item of list) map.set(item.id, item)

        for (const item of incoming) map.set(item.id, item)

        const baseIds = new Set(list.map(x => x.id))
        const merged: Board[] = []
        for (const item of list) merged.push(map.get(item.id)!)
        for (const item of incoming) {
            if (!baseIds.has(item.id)) merged.push(item)
        }
        return merged
    }
    const getBoardList = async(atr?:string, second_atr?:any, openTarget = true) => {    
        const isRefresh = atr === 'refresh'    
        if (mainLoader.value && !isRefresh) return
        if (reachEnd.value && !isRefresh) return

        if (isRefresh) {
            setReachEnd(false)
            setNextCursor(null)
        }
        
        mainLoader.value = true
        try {
            let chatId: number | null = null
            if (second_atr) {
                chatId = second_atr
            }
            if (atr == 'mounted' && route.params.chatId) {
                chatId = Number(route.params.chatId) ?? null
            }
            console.debug('Fetching board list with atr:', atr, 'and chatId:', chatId);
            
            const payload: any = {}
            
            if (!isRefresh && nextCursor.value) payload.cursor = nextCursor.value
            
            if (chatId) payload.id = chatId
            const res = await api.post('/board_list', payload)
            if(!res) return
            const rows = res.data ?? res ?? []
            const newCursor = res?.next_cursor ?? null
            if (isRefresh) {
                allBoardList.value = rows
            } else {
                allBoardList.value = mergeUpsertById(allBoardList.value, rows)
            }
            setList(allBoardList.value)
            if(second_atr && openTarget){
                const target = allBoardList.value.find(ob => ob.id == second_atr)
                if(target){
                    openTargetBoard(target)
                }                            
            }
            if(atr == 'mounted'){
                if (route.params.hasOwnProperty('chatId')) {
                    const targetBoard = allBoardList.value.find(ob => ob.id == Number(route.params.chatId))
                    if(targetBoard){
                        openTargetBoard(targetBoard)                                                
                    }else{     
                        await ask('チャットが削除されているか、権限がないためアクセスできません。', {answers: [{label: 'OK', value: true}]})
                        router.push({name: 'board'})
                    }
                }
            }
            
            setSkeleton(1)
        
            if (newCursor) {
                setNextCursor(newCursor)
            } else {
                setReachEnd(true)
                setNextCursor(null)
            }
        } finally {
            boardLoader.value = false
            mainLoader.value = false
            if(boardRefreshPending && !boardRefreshInFlight){
                boardRefreshPending = false
                queueBoardListRefresh()
            }
        }
       
    }
    const pinBoard = async(id) => {           
        const data = await api.post('/pin_board_api', {group_id: id})
        refreshBoardList()
        if(data?.pin_flag === 1){
            toast('ピン留めしました。')
        }else if(data?.pin_flag === 0){
            toast('ピン留めを解除しました。')
        }
    }
    const setNotification = async(id) => {
      
        const response = await api.post('/notification_board', {group_id: id})
        refreshBoardList()
        const flag = response?.notification || 0
        const flags = ['OFF', 'ON']
        toast(`通知設定を${flags[flag]}にしました。`)
   
    }
    const leaveBoard = async(board) => {  

        const data = await api.post('/leave_board', {id: board.id}, {
            ask: `<strong>${board.title}</strong> チャットを退出します。よろしいですか?`,
            toast: '退出しました。'
        })
        if(!data) return

        if(openedBoard.value && openedBoard.value.id == board.id){
            closeMessageContainer()
        }
        removeBoardFromList(board.id)
        refreshBoardList()
    }
    const setInvite = (item) => {
        viewingMembersOf.value = null
        setTimeout(() => {
            inviteTarget.value = item
        }, 200);
    }
    const resetReplyQuot = () => {
        const quot_reply = {
            active: false,
            message: null,
            which: null,
            text: null,
            file: false,
            height: 100,
            width: 100
        }
        quoteReply.setQuoteReply(quot_reply)
    }
    const shareToTask = () =>{
        setTrayItem(1)
        trayComponentKey.value ++
    }
    const refreshMessages = (message, oldId?: number) => {
        const targetId = oldId ? oldId : message.id
        const index = messageList.value.map( ob => ob.id).indexOf(targetId)
        if (index > -1) {
            messageList.value[index] = message
        }
    }
    provide(BoardMethodsKey, {
        remove: (item) => boardDelete(item),
        edit: (item) => activeEditBoard.value = item,
        create: () => newBoardWindow.value = true,
        reload: () => refreshBoardList(),
        close: () => closeMessageContainer(),
        open: (item, second_atr) => openBoard(item, second_atr),
        detail: (item) => detailedBoard.value = item,
        invite: (item) => setInvite(item),
        members: (item) => viewingMembersOf.value = item.id,
        pin: (item) => pinBoard(item.id),
        leave: (item) => leaveBoard(item),
        refreshMessages: (message, oldId) => refreshMessages(message, oldId),
        privateSearch: () => startPrivateSearch(),
        messageLoader: (item) => messageLoader.value = item,
        setNotification: (item) => setNotification(item.id)
    })

    provide(MessageMethodsKey, {
        addQueue: (item) => queuedMessages.value.push(item),  
        copy: (item) => copyData.value = item,
        remind: (item) => remindRequest(item),
        check: (item, request) => checkRequest(item, request),
        sent: (item, list, last_message) => sentMessage(item, list, last_message),
        sendError: (item) => sendError(item),
        removeError: (id) => removeError(id),
        resetReplyQuot: () => resetReplyQuot()
    })


    provide('shareToTask', shareToTask)
    provide('closeMessageContainer', closeMessageContainer)   
    provide('reload', refreshBoardList)      
    defineExpose({getBoardList, refreshBoardList, unreadLineTrigger, getMessageList, onPusher, refreshMessages})
</script>
    
    
