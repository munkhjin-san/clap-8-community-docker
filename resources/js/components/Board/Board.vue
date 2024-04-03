<template>
        <div class="boardOuterContainer" style="width: 100%;height: 100%;display:flex;flex-grow: 1;overflow: hidden;">     
            <div class="boardInnerContainer">        
                <Transition name="searchHide">
                <BoardSearchBar 
                    v-if="searchView"
                    :allBoardList="filteredAllBoard" 
                    @openBoard="openTargetBoard"
                    @openMessageSearch="openMessageSearch"
                />
                </Transition>
                <BoardList 
                    v-show="!responsive.mobile || (responsive.mobile && route.name == 'board')"
                    :list="filteredAllBoard"   
                    :openedBoard="openedBoard"     
                    :failedMessagesList="failedMessagesList"  
                    :key="listKey"  
                />
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
                :key="trayComponentKey" 
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
    
<script setup>
import BoardList from './BoardList.vue'
import { defineAsyncComponent, onMounted, onUnmounted, watch, computed, nextTick, ref, provide, inject, onBeforeUnmount } from 'vue'
import TrayComponent from './Tray.vue'
import BoardSearchBar from './Search/BoardSearchBar.vue'
import InviteMember from './InviteMember.vue'
import BoardCreateWindow from './BoardCreateWindow.vue'
import BoardMembers from './BoardMembers.vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'
import { useMenuStore } from "@/store/menu";
import { useQuoteReply } from '@/store/quoteReply'
import { useFocused } from '@/store/focused'
import { useUrlTask } from '@/store/urlTask'
import { useUrlMessage } from '@/store/urlMessage'
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import { useSkeleton } from '@/store/skeleton'
import { useBadgeStore } from '@/store/badge'

import BoardDetails from './BoardDetails.vue'
import SearchMessage from './Search/SearchMessage.vue'
import BoardEdit from './BoardEdit.vue'
import CopyWindow from './Message/CopyWindow.vue'
import ConfirmWindow from './Message/ConfirmWindow.vue'
    const badge = useBadgeStore()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const quoteReply = useQuoteReply()
    const focused = useFocused()
    const urlTask = useUrlTask()
    const urlMessage = useUrlMessage()
    const urlTaskEdit = useUrlTaskEdit()
    const skeleton = useSkeleton()
    // const BoardDetails = defineAsyncComponent(() => import('./BoardDetails.vue'))
    // const SearchMessage = defineAsyncComponent(() => import('./Search/SearchMessage.vue'))
    // const BoardEdit = defineAsyncComponent(() => import('./BoardEdit.vue'))
    // const CopyWindow = defineAsyncComponent(() => import('./Message/CopyWindow.vue'))
    // const ConfirmWindow = defineAsyncComponent(() => import('./Message/ConfirmWindow.vue'))
    const route = useRoute()
    const router = useRouter()
    const mainLoader = ref(false)
    const allBoardList = ref([])
    const activeEditBoard = ref(null)
    const pageIndex = ref(1)
    const pageLimiter = ref(false)
    const messageList = ref([])
    const openedBoardId = ref(null)
    const copyData = ref(null)
    const checkRequestData = ref(null)
    const microLoader = ref(false)
    const currentLen = ref(0)
    const infiniteLock = ref(false)
    const messageContainerKey = ref(0)
    const queuedMessages = ref([])
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
    const detailedBoard = ref(null)
    const unreadMessages = ref({
        active: false,
        id: null,
        count: 0
    })
    const listKey = ref(986)
    const searchView = ref(true)
    const inviteTarget = ref(null)
    const newBoardWindow = ref(false)
    const viewingMembersOf = ref(null)
    const requestType = ref('')
    const routeWatchLock = ref(false)
    const messageContainerRef = ref(null)
    const { confirm, notify, info } = inject('dialog');
    const keyboardHeight = ref(0)
    watch(() => focused.active, (after) => {
        if(after){
            if(openedBoard.value && badge.activeUsersBoardBadge && badge.activeUsersBoardBadge[openedBoard.value.id]){
                setTimeout(()=>{
                    badge.updateBoardBadge(openedBoard.value.id)
                },3000)
            }
        }
    })
    watch(() => badge.activeUsersBoardBadge, (after) => {   
        if(focused.active && openedBoard.value && after[openedBoard.value.id]){
            setTimeout(() =>{
                badge.updateBoardBadge(openedBoard.value.id)
            },3000)
        }                    
    })
    watch(() => route.params.chatId, (chatId) => {
        if(messageContainerRef.value.resetUnread){
            messageContainerRef.value.resetUnread()
        } 
        if(chatId){
            const targetBoard = filteredAllBoard.value.filter(ob => ob.id == chatId)             
            if(routeWatchLock.value){
                return
            }
            if(targetBoard.length){
                openBoard(targetBoard[0], 'watch')   
            }
        }else{
            closeMessageContainer()
        }
         
        
    })
    onBeforeUnmount(() => {
        menu.setMenu( {name: '', id: null})
    })        
    onUnmounted(() => {        
        if(navigator.virtualKeyboard){
            navigator.virtualKeyboard.removeEventListener('geometrychange', keyboardHeightListener);
        }        
    })
    onMounted(() => {
        const trayIndex = localStorage.getItem('favorite_tray');
        trayItemWhich.value = trayIndex ? parseInt(trayIndex) : 1
        if(navigator.virtualKeyboard){
            navigator.virtualKeyboard.addEventListener('geometrychange', keyboardHeightListener);
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
            console.log(t_edit)
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
    })        

    const onPusher = (e) => {
        if(e.message.board_id && e.message.sender !== auth.activeUser.id){
            const index = filteredAllBoard.value.map( ob => ob.id).indexOf(e.message.board_id);                  
            if(index > -1){
                getBoardList('pusher');
                badge.getBoardBadge('pusher')
            }
            if(openedBoard.value && openedBoard.value.id == e.message.board_id && listType.value == 'normal' && e.message.sender !== auth.activeUser.id){
                getMessageList('pusher'); 
            }
        }
        if(e.message.new_board_members){
            const index = e.message.new_board_members.indexOf(auth.activeUser.id);                  
            if(index > -1){
                getBoardList('pusher');
                badge.getBoardBadge('pusher')
            }
            
        }
        if(e.message.board_updated){                    
            getBoardList('pusher');
            badge.getBoardBadge('pusher')                
            
        }
        if(e.message && e.message.updateId){  
            
        }
    }
    const openedBoard = computed(() =>{
        if(filteredAllBoard.value && filteredAllBoard.value.length && openedBoardId.value){
            const active = filteredAllBoard.value.filter(ob => ob.id == openedBoardId.value)
            return active && active.length ? active[0] : null
        }
        return null
    })
    const reactiveMemberList = computed(() =>{
        return filteredAllBoard.value ? filteredAllBoard.value.filter(ob => ob.id == viewingMembersOf.value)[0] : null
        
    })
    const myBoard = computed(() =>{
        if(filteredAllBoard.value){                 
            var res = filteredAllBoard.value.filter(obj=>obj.private_flag == 3)[0];
            return res                 
        }
    })
    const filteredAllBoard = computed(() =>{
        
        return allBoardList.value
    })

    const keyboardHeightListener = (event) => {
        const { height } = event.target.boundingRect;
        keyboardHeight.value = height
    }
    const boardDelete = async(item) => {       
        const confirmed = await confirm(`ボードを削除しますか。`);
        if(!confirmed) return 
        try{
            await axios.post('/board_delete', { id: item.id })
            if(openedBoard.value && openedBoard.value.id == item.id){                                
                closeMessageContainer()
            }
            getBoardList()
            info('削除しました。')
        } catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } 
    }

    const afterRequestHandled = (response, id) => {
        console.log(response)
        if(response === 'respondDeleted'){
            closeMessageContainer()
            getBoardList()
        }else if(response === 'respondConfirmed'){
            getBoardList(null, id)
        }
    }
    const setTrayItem = (val) => {
        trayItemWhich.value = val
        localStorage.setItem('favorite_tray', val)
    }
    
    const appendSearchResult = (dir) => {
        if(scrllDir.value !== dir){
            scrllDir.value = dir
            appendLock.value = false 
        }
        if(appendLock.value || !messageList.value.length) return
        appendLock.value = true
        let lastMessage = null
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
        var currentPos = container1.scrollHeight; 
        microLoader.value = true
        axios.post('/get_bottom_messages', data).then(response => {  
            if(scrllDir.value == 'up'){
                messageList.value = messageList.value.concat(response.data)
                if(messageList.value.length !== currentLength){
                    appendLock.value = false 
                }
            }else if(scrllDir.value == 'down'){
                messageList.value = response.data.concat(messageList.value)
                if(messageList.value.length !== currentLength){
                    appendLock.value = false 
                }
                nextTick(() => {                   
                    var cont = document.getElementById('boardListInner')            
                    cont.scrollTop = currentPos - cont.scrollHeight               
                });                  
            }           
            
            setTimeout(() => {microLoader.value = false}, 200)
    
        }).catch(function (error) {                
            setTimeout(() => {microLoader.value = false}, 200)                    
        });
    }
    const jumpMessageFromFile = (file) => {                  
        const target = {
            id: file.message_id,
            record_id: file.board_id
        }                  
        jumpToMessage(target)                
    }
    const jumpToMessage = (message) => {
        
        messageLoader.value = true
        axios.post('/get_target_message', message).then(response => {  
            
            let board = filteredAllBoard.value.filter( obj => obj.id == message.record_id);
            if(board.length){
                openBoard(board[0], 'search')
                setTimeout(() => {
                    document.getElementById('board_item_' + board[0].id)?.scrollIntoView({ behavior: 'smooth', block: 'center' }) 
                },100)                         
                messageList.value = response.data;
                messageContainerKey.value ++;
                messageLoader.value = false
                searchMessageView.value = false
                searchTargetId.value = message.id                        
                listType.value = 'search'                        
            }
            appendLock.value = false              
    
        }).catch(function (error) {
            if (error.response) notify(error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。')   
            urlMessage.setUrlMessageId(null) 
            messageLoader.value = false                        
        });
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

    const openTargetBoard = (item, hasPush) => {
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
                getUnsentMessages(openedBoard.value.id);
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
            let data = []
            data.push(err)
            localStorage.setItem('failed_messages', JSON.stringify(data));
        }
        getUnsentMessages(openedBoard.value.id)        
    }
    const sentMessage = (item) => {
        pageIndex.value = 1;
        pageLimiter.value = false
        getMessageList('queue', item);
        getBoardList()
        if(item && item.attached_temp_files && item.attached_temp_files.length){
            trayComponentKey.value ++;
        }        
    }
    const closeMessageContainer = () => {
        openedBoardId.value = null;
        messageList.value = [];
        messageContainerKey.value ++
    }
    const reachedTop = () => {
        currentLen.value = messageList.value.length
        if(!pageLimiter.value && !infiniteLock.value){
            pageLimiter.value = true
            pageIndex.value ++ 
            getMessageList('infiniteLoader')
            microLoader.value = true
        }       
    }
    const remindRequest = async (data) => {
        try {
            const response = await axios.post('/remind_add', { id: data.id }).then(res => res.data)
            const message = response ? 'リマインドしました。' : 'リマインドを取り消しました。'
            info(message)
        } catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            getMessageList()
        }     
    }
    const checkRequest = (data, request) => {
        checkRequestData.value = data
        requestType.value = request
    }
    
    const getUnsentMessages = (id) => {
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
    const openBoard = (item, second_atr) => {
        messageLoader.value = true
        openedBoardId.value = item.id           
        pageIndex.value = 1;
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
            const self = item.board_to_users.filter( ob => ob.user_id == auth.activeUser.id)
            if(self.length){
                const data = {
                    active: true,
                    id: self[0].last_message,
                    count: badge.activeUsersBoardBadge[item.id]
                }
                unreadMessages.value = data
            }                                      
        }
        infiniteLock.value = false
        queuedMessages.value = []
        getUnsentMessages(item.id)
        resetReplyQuot()
        if(second_atr !== 'search'){
            if(urlMessage.id){
                const atr = {
                    id: urlMessage.id,
                    record_id: item.id
                }
                jumpToMessage(atr)
            }else{                
                getMessageList('first_load');
            }            
        }           
           
        trayComponentKey.value ++;
        searchWindowKey.value ++;
        routeWatchLock.value = true
        router.push(`/board/${item.id}`);
        setTimeout(() => {
            routeWatchLock.value = false
        }, 100);

        const mentionable = item.board_to_users.filter(ob => ob.user_id !== auth.activeUser.id && ob.user)
    }

    const getMessageList = async(source, queue) => {
        if(!openedBoard.value) return
        try {

            const response = await axios.post('/get_messages', { record_id: openedBoard.value.id, page_index: pageIndex.value })
            if(queue){
                removeError(queue.id)
                let box = document.getElementById('queueMessage_' + queue.u_id);                       
                if(box){                            
                    box.style.display = 'none'
                }
                getUnsentMessages(openedBoard.value.id)
                const data = {
                    active: false,
                    id: null,
                    count: 0
                }
                unreadMessages.value = data
            }
            listType.value = 'normal' 
            messageList.value = response.data;                    
            if(source == 'infiniteLoader'){                        
                setTimeout(() => { 
                    pageLimiter.value = false
                    microLoader.value = false
                },500)
            }
            infiniteLock.value = currentLen.value == messageList.value.length
            if(source == 'first_load'){                    
                badge.updateBoardBadge(openedBoard.value.id)
            }                    

        }catch (e) {
            messageLoader.value = false
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            messageLoader.value = false
        }
    }
    const unreadLineTrigger = () => {
        if(openedBoard.value){
            const board = filteredAllBoard.value.filter(ob => ob.id == openedBoard.value.id)
            if(board.length && badge.activeUsersBoardBadge[openedBoard.value.id]){
                const self = board[0].board_to_users.filter( ob => ob.user_id == auth.activeUser.id)
                if(self.length){
                    const data = {
                        active: true,
                        id: self[0].last_message,
                        count: badge.activeUsersBoardBadge[openedBoard.value.id]
                    }
                    unreadMessages.value = data
                }   
            }
            
        }
    }
    const boardEditFinished = (id) => {
        getBoardList('', id)
        activeEditBoard.value = null
        if(openedBoard.value){
            getMessageList()
        }
    }
    const getBoardList = async(atr, second_atr) => {        
        if (mainLoader.value) return
        
        mainLoader.value = true
        try {                  
            allBoardList.value = await axios.post('/board_list').then(res => res.data)
            if(second_atr){
                const target = allBoardList.value.filter(ob => ob.id == second_atr)
                if(target.length){
                    openTargetBoard(target[0])
                }                            
            }
            if(atr == 'mounted'){
                if (route.params.hasOwnProperty('chatId')) {
                    const targetBoard = allBoardList.value.filter(ob => ob.id == route.params.chatId)
                    if(targetBoard.length){
                        openTargetBoard(targetBoard[0], false)                                                
                    }else{     
                        await confirm('ボードが削除されているか、権限がないためアクセスできません。', {answers: [{label: 'OK', value: true}]})
                        router.push({name: 'board'})
                    }
                }
            }
            skeleton.setSkeleton(skeleton.active + 1)
        
        } catch (e) {
            mainLoader.value = false
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            mainLoader.value = false
        }        
    }
    const pinBoard = async(id) => {           
        await axios.post('/pin_board_api', {group_id: id})
        getBoardList()
    }
    const leaveBoard = async(board) => {
        try {
            const confirmed = await confirm(`<strong>${board.title}</strong> ボードを退出します。よろしいですか?`)
            if(!confirmed) return
            await axios.post('/leave_board', {id: board.id})
            if(openedBoard.value && openedBoard.value.id == board.id){
                closeMessageContainer()
            }
            info('退出しました。')
            getBoardList()
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
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
    provide('boardItem', {
        remove: (item) => boardDelete(item),
        edit: (item) => activeEditBoard.value = item,
        create: () => newBoardWindow.value = true,
        reload: () => getBoardList(),
        close: () => closeMessageContainer(),
        open: (item, second_atr) => openBoard(item, second_atr),
        detail: (item) => detailedBoard.value = item,
        invite: (item) => setInvite(item),
        members: (item) => viewingMembersOf.value = item.id,
        pin: (item) => pinBoard(item.id),
        leave: (item) => leaveBoard(item),
        refreshMessages: () => getMessageList(),
        privateSearch: () => startPrivateSearch()
    })

    provide('messageItem', {
        addQueue: (item) => queuedMessages.value.push(item),  
        copy: (item) => copyData.value = item,
        remind: (item) => remindRequest(item),
        check: (item, request) => checkRequest(item, request),
        sent: (item) => sentMessage(item),
        sendError: (item) => sendError(item),
        removeError: (id) => removeError(id),
        resetReplyQuot: () => resetReplyQuot()
    })

    provide('taskItem', {
        shareToTask: (data) => shareToTask()
    })
    provide('closeMessageContainer', closeMessageContainer)   
    provide('openedBoard', openedBoard)
    provide('reload', getBoardList)      
    provide('keyboardHeight', keyboardHeight)
    defineExpose({getBoardList, unreadLineTrigger, getMessageList, onPusher})
</script>
    
    