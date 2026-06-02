<template>
    <div id="boardAreaBoxInner" :class="['messageAreaContainer', {quotActive: quoteWindow.active}]" :style="{height: `calc(100% - ${keyboardStore.height}px)`}">
        <router-view v-slot="{ Component }">
                <transition name="slideFromRight">
                    <component 
                        :is="Component" 
                        @jumpToMessage="jumpToMessage"
                    />
            </transition>
        </router-view> 
        <Transition name="inputSlide" appear>
            <MessageInput 
                v-if="!messageLoader && openedBoard"
                :replyKey="replyKey"
                :unread="unread"
                :messageListType="messageListType"
                :unreadMessages="unreadMessages"
                @unreadJumped="unreadJumped"
            />
        </Transition> 
        <Transition name="modalFade">         
        <div v-if="openedBoard && messageLoader" id="loaderMini" style="position: absolute;">
            <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
        </div> 
        </Transition>
        
            <div id="boardListInner" 
                v-if="openedBoard && !messageLoader" 
                @scroll="scrollEvent"
                class="messageListInnerContainer"
            >
                <div :class="'reverseContainer'">
                <transition-group name="queueItem" tag="div">
                    <MessageItemQueue                    
                        v-for="(message , index) in queuedMessagesList"
                        :qIndex="index"
                        :key="'queue' + message.id"
                        :message="message" 
                        :messageListType="messageListType"         
                    />
                </transition-group>
                <MessageItem
                    v-for="( message , index) in messageList"
                    :key="message.id"
                    :mIndex="index"
                    :mLength="messageList.length"
                    :message="message"
                    :lastReadMessage="lastReadMessage"
                    :searchTargetId="searchTargetId"
                    :messageListType="messageListType"  
                    :unreadMessages="unreadMessages"  
                    @updateReplyKey="replyKey++"    
                    @unreadJumperOn="unreadJumperOn"        
                />
                </div>
            </div>
            <MessageHeader v-if="responsive.mobile && openedBoard"/>
        <Transition name="modalFade"> 
        <div v-if="microLoader" id="infiniteLoader">
            <div class="spinner-micro color-change"></div>
        </div>
        </Transition>
        <div id="floatButton" class="floatCheck hideout">
            <svg style="margin:auto;fill:#000" version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32">
                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
            </svg>
        </div>
    </div>
</template>

<script setup lang="ts">
import MessageItem from "./MessageItem.vue";
import MessageItemQueue from "./MessageItemQueue.vue";
import MessageInput from './MessageInput.vue';
import MessageHeader from '../../Mobile/MessageHeader.vue';
import { computed, inject, ref } from "vue";
import { onBeforeRouteLeave, useRoute, useRouter } from "vue-router";
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from "@/store/responsive";
import { useQuoteWindow } from "@/store/quoteWindow";
import { useKeyboardStore } from "@/store/keyboardStore";
import { useBoardList } from "@/composables/board";
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const quoteWindow = useQuoteWindow()
    const props = defineProps([
        'messageList',  
        'microLoader', 
        'queuedMessages', 
        'messageLoader', 
        'searchTargetId',
        'messageListType',
        'unreadMessages'
    ])
    const resetInstantUser = <Function>inject('resetInstantUser')
    const { openedBoard } = useBoardList()
    const emit = defineEmits(['closeContainer', 'reachedTop', 'appendSearchResult', 'jumpToMessage'])
    const replyKey = ref(0)
    const unread = ref({
        status: false,
        count: 0,
        id: null
    })
    const keyboardStore = useKeyboardStore()
    onBeforeRouteLeave((to, from, next) => {

        if(to.name == 'board'){
            emit('closeContainer')
        }
        resetUnread()
        next()
       
    })
    const resetUnread = () => {
        unread.value = {
            status: false,
            count: 0,
            id: null
        }
    }

    const queuedMessagesList = computed(() => {
        var width = window.innerWidth
        || document.documentElement.clientWidth
        || document.body.clientWidth;
        if(width > 959){
            return props.queuedMessages
        }else{
            return [...props.queuedMessages].reverse()
        }
        
    })
    const lastReadMessage = computed(() => {
        if(openedBoard.value){
            const me = openedBoard.value.board_to_users.filter( ob => ob.user_id == auth.activeUser.id)
            return me && me.length ? me[0].last_message : null
        }else{
            return null
        }
    })


    const scrollEvent = (event: Event) => {
        var container = event.target as HTMLDivElement          
        var percent = 100 * container.scrollTop / (container.scrollHeight - container.clientHeight);                  
        if(percent < -99 && props.messageListType == 'normal') emit('reachedTop')
        if(unread.value.status ){
            const line = document.getElementById('messageRoot_' + unread.value.id)
            if(line){
                const rect = line.getBoundingClientRect()
                if(rect.y + rect.height > 0){                            
                    unread.value = {
                        status: false,
                        count: 0,
                        id: null
                    }
                }
            }
        }
        resetInstantUser()
        if(percent < -99 && props.messageListType == 'search'){
            emit('appendSearchResult', 'up')
        }
        if(percent > -1 && props.messageListType == 'search'){
            emit('appendSearchResult', 'down')
        }
        if(menu.name == 'boardMessageMenu'){
            menu.setMenu( {name: '', id: null})
        }
    }
    const unreadJumperOn = (data:any) => {
        unread.value = data
    }
    const unreadJumped = () => {
        document.getElementById('unread_line_' + unread.value.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' })    
    }
    const jumpToMessage = (file:any) => {
        emit('jumpToMessage', file)
    }     
    defineExpose({resetUnread})
</script>
