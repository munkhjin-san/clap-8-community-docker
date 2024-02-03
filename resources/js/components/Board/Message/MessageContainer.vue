<template>
    <div id="boardAreaBoxInner" :class="['messageAreaContainer', {quotActive: $store.state.qoutWindowActive}]" :style="{height: `calc(100% - ${$store.state.keyboardOffset}px)`}">
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
                :openedBoard="openedBoard" 
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
                        :openedBoard="openedBoard"    
                        :messageListType="messageListType"         
                    />
                </transition-group>
                <MessageItem
                    v-for="( message , index) in messageList"
                    :key="message.id"
                    :mIndex="index"
                    :mLength="messageList.length"
                    :message="message"
                    :openedBoard="openedBoard"  
                    :lastReadMessage="lastReadMessage"
                    :searchTargetId="searchTargetId"
                    :messageListType="messageListType"  
                    :unreadMessages="unreadMessages"  
                    @updateReplyKey="replyKey++"    
                    @unreadJumperOn="unreadJumperOn"        
                />
                </div>
            </div>
            <MessageHeader 
                v-if="$store.state.mobile && openedBoard"
                :openedBoard="openedBoard" 
                @closeMe="$router.push({name: 'board'})" 
                @startPrivateSearch="$emit('startPrivateSearch')"       
            />
        <Transition name="modalFade"> 
        <div v-if="microLoader" id="infiniteLoader">
            <div class="spinner-micro color-change" style=""></div>
        </div>
        </Transition>
        <div id="floatButton" class="floatCheck hideout">
            <svg style="margin:auto;fill:#000" version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32">
                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
            </svg>
        </div>
    </div>
</template>

<script>
import MessageItem from "./MessageItem.vue";
import MessageItemQueue from "./MessageItemQueue.vue";
import MessageInput from './MessageInput.vue';
import UserIcon from '../Mixed/UserIcon.vue';
import MessageHeader from '../../Mobile/MessageHeader.vue';
import moment from "moment";
    export default {
        beforeRouteLeave(to, from, next) {
            if (from.params.roomId) {
                next(false)
            } else {
                if(to.name == 'board'){
                    this.$emit('closeContainer')
                }else if(to.name == 'user'){
                    const data = {
                        active: false,
                        userList: [],
                        title: ''
                    }
                    this.$store.commit('setMessageUsers', data)
                }
                
                this.unread = {
                    status: false,
                    count: 0,
                    id: null
                }
                console.log('resetunred', this.unread)
                next()
            }
        },
        props: [
            'messageList', 
            'openedBoard', 
            'microLoader', 
            'zIndexTable', 
            'queuedMessages', 
            'messageLoader', 
            'failedMessagesList',
            'searchTargetId',
            'messageListType',
            'totalKey',
            'unreadMessages',
            'slideLeft',
            'from',
        ],
        data(){
            return{
                activeMenu: null,
                scrollCounter: 0,
                currentLen: 0,
                replyKey: 0,
                unread: {
                    status: false,
                    count: 0,
                    id: null
                },
                traySelectorToggle: false,
                respondLock: false,
                transitionCounter: 1
            }
        },
        watch:{
            '$route.params.chatId'(chatId) {
                console.log('watcherincontainer')
                this.unread = {
                    status: false,
                    count: 0,
                    id: null
                }
            }
        },
        components:{
            MessageItem,
            MessageItemQueue,
            MessageInput,
            UserIcon,
            MessageHeader
        },
        computed:{
            roomId() {
                return this.$route.params.chatId;
            },
            queuedMessagesList(){
                var width = window.innerWidth
                || document.documentElement.clientWidth
                || document.body.clientWidth;
                if(width > 959){
                    return this.queuedMessages
                }else{
                    return this.queuedMessages.reverse()
                }
                
            },
            lastReadMessage(){
                if(this.openedBoard){
                    const me = this.openedBoard.board_to_users.filter( ob => ob.user_id == this.$store.state.user.id)
                    return me && me.length ? me[0].last_message : null
                }else{
                    return null
                }
            },
        },
        methods: {
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: true, 
                    autoClose: true,

                })   
            },
            scrollEvent(){
                var container = event.target             
                var percent = 100 * container.scrollTop / (container.scrollHeight - container.clientHeight);                  
                if(percent < -99 && this.messageListType == 'normal') this.$emit('reachedTop')
                if(this.unread.status ){
                    const line = document.getElementById('messageRoot_' + this.unread.id)
                    if(line){
                        const rect = line.getBoundingClientRect()
                        if(rect.y + rect.height > 0){                            
                            this.unread = {
                                status: false,
                                count: 0,
                                id: null
                            }
                        }
                    }
                }
                if(percent < -99 && this.messageListType == 'search'){
                    this.$emit('appendSearchResult', 'up')
                }
                if(percent > -1 && this.messageListType == 'search'){
                    this.$emit('appendSearchResult', 'down')
                }
                if(this.$store.state.instantUser.id){ 
                    const data = {
                        id: null,
                        cX: null,
                        cY: null
                    }
                    this.$store.commit('setInstantUser', data)   
                }
                if(this.$store.state.menu.name == 'boardMessageMenu'){
                    this.$store.commit('setMenu', {name: '', id: null})
                }
            },
            unreadJumperOn(data){
                console.log('unread_junper-tiri')
                this.unread = data
            },
            unreadJumped(){
                // this.unread.id?.scrollIntoView({ behavior: 'smooth', block: 'center' })  
                document.getElementById('unread_line_' + this.unread.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' })    
            },
            jumpToMessage(file){
                console.log('passMessage')
                this.$emit('jumpToMessage', file)
            }
               
                
            
        }
    }
</script>
<style lang="scss">

</style>
