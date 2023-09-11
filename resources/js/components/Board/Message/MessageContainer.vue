<template>
    <div id="boardAreaBoxInner" :class="['messageAreaContainer', {quotActive: $store.state.qoutWindowActive}]">
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
                v-if="!messageLoader && boardIsActive && hasAccessibleChat && !isBlocked"
                :openedBoard="openedBoard" 
                :replyKey="replyKey"
                :unread="unread"
                :messageListType="messageListType"
                :unreadMessages="unreadMessages"
                @unreadJumped="unreadJumped"
            />
        </Transition> 
        <Transition name="inputSlide" appear>
            <div v-if="isBlocked" class="footAreaContainer" style="width: 100%;height: 60px;font-size: 13px;line-height: 1.5;display: flex;align-items: center;place-content: center;padding: 0;">
                {{ $t('unableToSendMessageDueBlockAction') }}
            </div>
        </Transition>
        <div class="respondBox" style="flex-direction: column;" v-if="openedBoard && invitedByOthers && !boardIsActive">
            <div style="margin-bottom:10px;padding: 0 50px;line-height: 1.5;">
                {{$t('joinOrRemoveRequest')}}
            </div>
            <div style="display:flex; gap:20px;">   
                <button @click="respondRequest(openedBoard, 1)" class="commentEditButton">{{$t('confirmToChat')}}</button>
                <button @click="respondRequest(openedBoard, 0)" class="commentEditButton">{{$t('cancelToChat')}}</button>
            </div>
            
            
            
        </div>
        <div class="respondBox" v-if="openedBoard && selfRequested && !boardIsActive" style="margin-bottom:80px;flex-direction: column;">
            <div>
                {{$t('waitingApproval')}}
            </div>          
            <div style="display:flex; gap:20px;margin-top: 20px;">   
                <button @click="cancelJoinRequest(openedBoard)" class="commentEditButton">{{$t('cancelJoinRequest')}}</button>
            </div>
        </div>
        <div class="respondBox" v-if="openedBoard && correspondMayMiss && !messageList.length" style="position: absolute;margin: auto;top: 0;bottom: 0;line-height: 1.8;">
            <div style="margin-bottom:80px ">
                {{$t('correspondMayMiss')}}
            </div>            
        </div>
        <Transition name="slidePop">
            <div class="deletionAlertHeader" v-if="suspended">
                <svg fill="tomato" style="transform: rotate(180deg);margin: 2px 5px 0 0;min-width: 15px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                    <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                </svg>   
                <div v-html="deletionAlert"></div>            
            </div>
        </Transition>
        <Transition name="modalFade">         
        <div v-if="openedBoard && messageLoader" id="loaderMini" style="position: absolute;">
            <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
        </div> 
        </Transition>
        
            <div id="boardListInner" 
                v-if="openedBoard && !messageLoader && (boardIsActive)" 
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
            :hasAccessibleChat="hasAccessibleChat"
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
import UserIcon from '../Mixed/UserIcon';
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
            'hasAccessibleChat'
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
        mounted(){
            console.log('container_mounted')
        },
        components:{
            MessageItem,
            MessageItemQueue,
            MessageInput,
            UserIcon,
            MessageHeader
        },
        computed:{
            deletionAlert(){
                if(this.suspended){
                    const date = this.openedBoard.last_activity  
                    const deletionDate = moment(date).locale(this.$store.state.local).add(180, 'days').format('LL');
                    return this.$t('chatDeleteBy', {date : deletionDate})

                }
            },
            suspended(){

                return this.openedBoard && this.openedBoard.last_activity ? moment(this.openedBoard.last_activity).add(90, 'days').isBefore(moment(), 'day') : false
            },
            isBlocked(){
                return this.openedBoard && this.openedBoard.private_flag == 1 && (this.openedBoard.is_blocked || this.openedBoard.is_blocked_by)
            },
            correspondMayMiss(){
                return this.openedBoard && this.openedBoard.private_flag == 1 && this.openedBoard.is_waiting && !this.openedBoard.is_blocked && !this.openedBoard.is_blocked_by
            },
            roomId() {
                return this.$route.params.chatId;
            },
            hasApproveMembers(){
                const allMembers = this.openedBoard && this.openedBoard.board_to_users ? this.openedBoard.board_to_users : [];
                const pendingMembers = allMembers.filter(ob => ob.member_status == 0 && ob.user_id == ob.invited_by)
                return pendingMembers
            },
            boardIsActive(){
                if(this.openedBoard && this.openedBoard.board_to_users){
                    const me = this.openedBoard.board_to_users.filter(ob => ob.user_id == this.$store.state.user.id);
                    if(me.length){
                        if(me[0].member_status == 1){
                            return true
                        }else{
                            return false
                        }
                        //     if(this.openedBoard.private_flag == 1){
                        //         return true
                        //     }
                        //     else{
                        //         return false
                        //     }
                        // }
                    }
                }
                return false
                
            },
            selfRequested(){
                if(this.openedBoard && this.openedBoard.board_to_users){
                    const me = this.openedBoard.board_to_users.filter(ob => ob.user_id == this.$store.state.user.id && ob.invited_by == ob.user_id);
                    return me.length
                }
                return false
            },
            invitedByOthers(){
                if(this.openedBoard && this.openedBoard.board_to_users){
                    const me = this.openedBoard.board_to_users.filter(ob => ob.user_id == this.$store.state.user.id && ob.invited_by !== ob.user_id);
                    return me.length
                }
                return false
            },
            invitedBy(){
                if(this.openedBoard && this.openedBoard.board_to_users){
                    const me = this.openedBoard.board_to_users.filter(ob => ob.user_id == this.$store.state.user.id);
                    return me && me.length && me[0].invited_by ? me[0].invited_by : null
                }
                return null
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
            cancelJoinRequest(item){
                if(this.respondLock) return
                const data = {
                    target: item.id,
                }
                this.respondLock = true
                axios.post('/cancel_join_request', data).then(response => {  
                    this.respondLock = false

                    this.$emit('afterRequestHandled', response.data, item.id)
            
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.respondLock = false
            
                }.bind(this));
            },
            respondRequest(item, res){
                if(this.respondLock) return
                const data = {
                    target: item.id,
                    response: res
                }
                this.respondLock = true
                axios.post('/respond_invite_request', data).then(response => {  
                    this.respondLock = false

                    this.$emit('afterRequestHandled', response.data, item.id)
            
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.respondLock = false
            
                }.bind(this));
            },
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
.queueItem-enter-active, .queueItem-leave-active {
    transition: all 0.2s;
}
.queueItem-enter-from, .queueItem-leave-to {
    transform: translateY(50px);
    -ms-transform: translateY(50px);
    -webkit-transform: translateY(50px);
    -moz-transform: translateY(50px);
    -o-transform: translateY(50px);
    opacity: 0;
}
.color-change{
    border: solid 3px var(--primary-color);
    border-top: 3px transparent solid;
}
.approveMembersModal{
    position: absolute;

    width: -webkit-fill-available;
    top: 0;
    left: 0;
    z-index: 2;
    margin: 0 20px;
    color: var(--primary-color);
    

}
.approveMembersModal > div {
    background: var(--message-background);
    box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
    height: fit-content;
}

.approveMemberCard{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:14px;
    background: var(--bg2);
    padding: 10px 20px;
    
}
.hanger{
    height: 30px;
    width: 60px;
    position: absolute;
    bottom: -30px;
    background: var(--message-background);
    margin-left: auto;
    margin-right: auto;
    left: 0;
    right: 0;
}
.hanger-shadow{
    height: 30px;
    width: 60px;
    position: absolute;
    bottom: -30px;
    background: var(--message-background);
    margin-left: auto;
    margin-right: auto;
    left: 0;
    right: 0;
    box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
    z-index: -1;
}
.requestMembers{
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px;
    max-height: 190px;
    overflow: hidden auto;
    transition: max-height 0.1s;
}
.isExpand{
    max-height: 0;
    padding: 0;
    overflow: hidden;
}
</style>
