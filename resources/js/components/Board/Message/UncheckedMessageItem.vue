<template>
    <div :class="boxClass">
        <div>
            <div class="request-container">
                
                <div style="display:flex;align-items:center;position:relative;margin-bottom: 10px;">                      
                    <UserIconPreLoad :disableInstant="true" v-if="message.user" size="30" :user="message.user" imgClass="userNormalIcon"/>    
                    <div v-else>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30" imgClass="userNormalIcon">
                            <circle cx="15" cy="15" r="15" fill="#ddd"/>
                        </svg>
                    </div>                      
                    <p :id="'messageSender_' + message.id" class="userName" @dragstart.prevent style="margin-left:10px;">{{ messageUserName }}</p>     
                    <p @dragstart.prevent class="dateText" style="font-size:12px;color:grey;position:absolute;right:0;top:-5px">{{momentMessage}}</p>                 
                </div>                             
                                
                    
                
                             
                    <MessageQuoteReply 
                        v-if="message.message_reply"
                        :which="'reply'"
                        :message="message.message_reply"
                        :quotMessage="null"
                        :openedBoard="openedBoard"
                        :urlCheck="urlCheck"/>
                    <MessageQuoteReply 
                        v-if="message.message_quot"
                        :which="'quot'"
                        :message="message.message_quot"
                        :quotMessage="message.quot_message"
                        :openedBoard="openedBoard"
                        :urlCheck="urlCheck"/>
                    <MessageQuoteReply 
                        v-if="message.message_forward"
                        :which="'forward'"
                        :message="message.message_forward"
                        :quotMessage="null"
                        :openedBoard="openedBoard"
                        :urlCheck="urlCheck"/>
                    



                    <div v-if="messageBody" class="normal-body" @click="jumpToMessage">
                        <div
                            @dragstart.prevent
                            :id="'editComment_' + message.id"                          
                            v-html="messageBody" 
                            class="messageInnerBody"
                            style="display:block;word-break:break-word;margin-top:10px">
                        </div>                        
                    </div>
                
                    <MessageFiles 
                        v-if="message.message_files && message.message_files.length"
                        :list="message.message_files"
                        :message="message"
                        :reminder="reminder"
                    /> 
                    <button v-if="remindedUsers" style="padding: 5px 10px 5px 10px;
                        font-size: 12px;
                        line-height: 1.5;
                        border-radius: 0px;
                        background: var(--primary-button);
                        color: #fff;
                        margin-top:10px;" @click="emit('remindRequest', message)" >リマインドから外す</button>        
               
                <div style="display: flex;width:100%;position:relative;" v-if="userReacts">
                    <div style="display:flex;width: fit-content;">
                        
                        <div 
                            v-if="reactButtonView(message)" 
                            class="reactButton" 
                            :class="{cursorBlock : message.user_id == auth.activeUser.id, reactOn: reacting}"  
                            @click="checkSendAreaClick($event, message)">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :style="checkSendIconColor(message)">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </div>
                        <div @click.stop="viewReactedUsersList" v-if="message.reacted_users" style="display:flex;padding: 10px;margin: 5px 0 -15px -15px;height: 15px;">
                            <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in message.reacted_users.slice(0,3)">  
                                <UserIconPreLoad :disableInstant="true" :title="user.name" size="30" :user="user" imgClass="userSmallIcon"/>                                         
                            </div>
                            <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="message.reacted_users.length > 3">...({{message.reacted_users.length}})</span>
                        </div>                                    
                    </div>
                    <div v-if="checkFunctionView" style="display: flex;font-size: 12px;margin-left:auto;height:30px">
                        <p @click.stop="viewCheckedUserList" class="cursor-pointer" :class="{activeCheck : menu.name == 'checkedUsersList' && menu.id == message.id && message.checked_users !== null}" style="margin-top: auto;padding: 8px 8px 9px 8px;margin-bottom:-8px;">確認済み  {{checkedUsers.length}}人</p>                                            
                        <Transition name="modalFade">
                        <div id="checkedUsersList" v-if="menu.name == 'checkedUsersList' && menu.id == message.id && message.checked_users !== null" class="checkUsersList" :class="{rightSide : message.checked_users && message.user_id == auth.activeUser.id}" style="top: 38px;right: 89px;">                               
                            <div v-for="(user, index) in checkedUsers" class="boardUsersListInner">
                                <p class="cursor-pointer" style="font-size:small;">{{user.name}}</p>
                            </div>                      
                                                        
                        </div>
                        </Transition>
                    </div>
                    <div v-if="checkFunctionView" style="display: flex;font-size: 12px;padding-left:10px;height:30px">
                        <p @click.stop="viewunCheckedUserList"  class="cursor-pointer" :class="{activeCheck : menu.name == 'uncheckedUsersList' && menu.id == message.id && message.unchecked_users !== null}" style="margin-top: auto;padding: 8px 8px 9px 8px;margin-bottom:-8px;">未確認  {{uncheckedUsers.length}}人</p>                                               
                        <Transition name="modalFade">
                        <div id="uncheckedUsersList" style="top: 38px;"  v-if="menu.name == 'uncheckedUsersList' && menu.id == message.id && message.unchecked_users !== null" class="checkUsersList" :class="{rightSide : message.unchecked_users && message.user_id == auth.activeUser.id}">                                
                            <div v-for="(user, index) in uncheckedUsers" class="boardUsersListInner">   
                                <p class="cursor-pointer" style="font-size:small;">{{user.name}}</p>
                            </div>                       
                                                        
                        </div>
                        </Transition>
                    </div>        
                    <div id="reactedUserListAll" v-if="menu.name == 'reactedUserListAll' && menu.id == message.id" class="taskUsersList" style="left: 0;top: 35px;width: fit-content;">
                        <div @click.stop="pushInstantUser($event, user.id)" :key="user.id" class="mentionBox-inner" v-for="user in reactedUsersList">                                                
                            <div class="column-01"> 
                                <UserIconPreLoad :disableInstant="true" size="25" :user="user" imgClass="userMidIcon"/>   
                            </div> 
                            <p class="cursor-pointer" style="margin: auto auto auto 5px;font-size: 13px;">{{user.name}}</p>                                                                           
                        </div>
                    </div>                                    
                </div>
        
            </div>
        </div>
    </div>
</template>

<script setup>
import MessageQuoteReply from "./MessageQuoteReply.vue";
import MessageFiles from "./MessageFiles.vue";
import moment from 'moment';
import Autolinker from 'autolinker';
import UserIconPreLoad from '../Mixed/UserIcon.vue'
import { computed, inject, onMounted, ref } from "vue";
import { useAuthUserStore } from '@/store/auth'
    const auth = useAuthUserStore()
    const props = defineProps(['message', 'openedBoard', 'reminder', 'boxClass'])
    const emit = defineEmits(['reload', 'remindRequest'])
    const tempHideCheckButton = ref(null)
    const reacting = ref(false)
    const { notify, confirm } = inject('dialog')
    import { useMenuStore } from "@/store/menu";
    const menu = useMenuStore()
    const pushInstantUser = inject('pushInstantUser')
    const remindedUsers = computed(() => {
        return props.message.message_remind_users && props.message.message_remind_users.length ? props.message.message_remind_users.find(val => val.user_id == auth.activeUser.id) : null
    })
    const userReacts = computed(() => {
        return props.message.unchecked_users.length || props.message.checked_users.length || props.message.reacted_users.length
    })
    const messageUserName = computed(() => {                
        return props.message.user && props.message.user.deleted_at == null
        ? props.message.user.name
        : '非アクティブユーザー';
    })
    const reactedUsersList = computed(() => {
        return props.message.reacted_users
    })
    const messageBody = computed(() => {
        const text = props.message.message ? props.message.message : ''
        const to_all = text.replace('<span class="toAll">@全員</span>', '<a class="toAll">@全員</a>');
        const converterd = to_all.replace(/<((?!a )[^>]*)>/g, "&lt;$1&gt;").replace(/&lt;\/a&gt;/g, "</a>");
        const br_remove = converterd.replace(/&lt;br&gt;/g," ");
        return urlCheck(br_remove)
    })
    const checkFunctionView = computed(() => {
        if(props.message.check_flag == 1){
            let checked = props.message.checked_users.map(ob => ob.id)
            let unchecked = props.message.unchecked_users.map(ob => ob.id)
            return checked.indexOf(auth.activeUser.id) > -1 || unchecked.indexOf(auth.activeUser.id) > -1 || props.message.user_id == auth.activeUser.id
        } 
    })
    const uncheckedUsers = computed(() => {          
        return props.message.unchecked_users
    })
    const checkedUsers = computed(() => {       
        return props.message.checked_users
    })
    const momentMessage = computed(() => {
        moment.updateLocale('ja', {
            parentLocale: 'ja', 
            longDateFormat: {
                LT: 'HH:mm', 
                LLLL: 'MMMDo', 
                llll: 'YYYY年M月D日 HH:mm'
            },
        });
        moment.locale('ja');  
        const date = props.message.created_at
        return moment(props.message.created_at).isSame(moment(), 'day') ? 
        moment(date).format('HH:mm') : 
        moment(date).isSame(moment(), 'year') ? 
        moment(date).format('LLLL LT') : 
        moment(date).format('llll')                      
    })
    const urlCheck = (text) => {
        if(text){                
            var linkedText = Autolinker.link(text, {stripPrefix: false});       
            const catch_tag = '<a href=/app/public/user?id=' 
            const rep_tag = '<a class="mntuser" style="cursor:pointer" id=' 
            linkedText = linkedText.replaceAll(catch_tag, rep_tag);
            return linkedText;                
        }            
    }
    const reactButtonView = (msg) => {
        if(msg.user_id == auth.activeUser.id && (!msg.reacted_users || msg.reacted_users == null || msg.reacted_users == '')){
            return false;
        }else{
            return true;
        }
    }
    const checkSendIconColor = (msg) => {
        if(tempHideCheckButton.value == msg.id){
            return 'fill:var(--primary-color);'
        }else{
            var check_list = msg.reacted_users.map(ob => ob.id)
            if(msg.user_id == auth.activeUser.id || check_list.indexOf(auth.activeUser.id) > -1){
                return 'fill:var(--primary-color);'
            }else{
                return 'fill:#c0c0c0;'
            }
        }
        
    }            
    const viewReactedUsersList = () => {
        menu.setMenu( {name: 'reactedUserListAll', id: props.message.id})  
    }
    const viewCheckedUserList = () => {
        menu.setMenu( {name: 'checkedUsersList', id: props.message.id})
    }
    const viewunCheckedUserList = () => {
        menu.setMenu( {name: 'uncheckedUsersList', id: props.message.id})
    }
    const checkSendAreaClick = async(event, msg) => {
        var checked_user = msg.reacted_users
        if(msg.user_id == auth.activeUser.id){
            return
        }
        
        if(checked_user.indexOf(auth.activeUser.id) == -1){
                reacting.value = true
        }else{                    
            reacting.value = false
        }
        try {
            const response = await axios.post('/send_reaction_api', {id: msg.id})
            if(msg.check_flag == 1){
                checkSendConfirm(response.data);
            }else{
                emit('reload')
            }                                      
        } catch (error) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } 
    } 
    const checkSendConfirm = async(msg) => {
        var checked = msg.checked_users.map(ob => ob.id).indexOf(auth.activeUser.id);
        var unchecked = msg.unchecked_users.map(ob => ob.id).indexOf(auth.activeUser.id);
        var reacted =   msg.reacted_users.map(ob => ob.id).indexOf(auth.activeUser.id);
        if(reacted == -1){
            emit('reload')
        }              
        if(unchecked > -1 && reacted > -1){     
            const answer = await confirm('メッセージを確認済みにしますか')
            if(!answer) {
                emit('reload')
                return
            }  
            checkSend(msg.id, 'check')
            
        }
        if(checked > -1 && reacted == -1){                   
            notify('既に確認しています。')
            
        }
    }
    const checkSend = async(id, which) => {            
        const params = {
            message_id: id,
            user_id: auth.activeUser.id,
            pattern: which
        };
        try {
            await axios.post('/check_send_api', params)
            emit('reload')
            tempHideCheckButton.value = null;
        } catch (error) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const jumpToMessage = () => {
        const link = document.createElement('a');
        link.href = `${window.location.origin}/board/${props.message.record_id}?m=${props.message.id}&jump_message=true`;                
        document.body.appendChild(link);            
        link.click();   
        link.remove();
    } 
              
   
</script>
<style scoped lang="scss">
    .request-container{
        background:var(--message-background);
        padding:15px;
        color:var(--primary-color);
        border: solid thin transparent;
    }

    @media screen and (max-width: 959px) {
        .request-container{
            padding: 20px;
        }
    }
</style>