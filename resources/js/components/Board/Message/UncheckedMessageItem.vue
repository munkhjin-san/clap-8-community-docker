<template>
    <div :class="boxClass">
        <div>
            <div :class="['request-container', {editIsOn:editing}]">
                
                <div style="display:flex;align-items:center;position:relative;margin-bottom: 10px;">                      
                    <UserIconPreLoad :disableInstant="true" v-if="message.user" size="30" :user="message.user" imgClass="userNormalIcon"/>    
                    <div v-else>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30" imgClass="userNormalIcon">
                            <circle cx="15" cy="15" r="15" fill="#ddd"/>
                        </svg>
                    </div>                      
                    <p :id="'messageSender_' + message.id" class="userName" @dragstart.prevent style="margin-left:10px;">{{ messageUserName }}</p>     
                    <p @dragstart.prevent class="dateText" style="font-size:12px;color:grey;position:absolute;right:0;top:-5px">{{momentMessage}}</p>
                    <!-- <ItemMenu v-if="remindedUsers" style="margin-left: auto;" :items="messageMenuItems" fit="boardListInner"/>                  -->
                </div>                             
                                
                    
                    <div v-if="messageBody" class="normal-body" style="position: relative;">
                        <div
                            v-if="!editing"
                            @click="jumpToMessage"
                            @dragstart.prevent
                            :id="'editComment_' + message.id"                          
                            v-html="mentionFormatter(messageBody, true)" 
                            class="messageInnerBody"
                            style="display:block;word-break:break-word;margin-top:10px">
                        </div>
                        <!-- <MessageEditor 
                            v-else
                            :message="message" 
                            @cancel="editing = false"
                        />                         -->
                    </div>
                    <div v-if="messageBody.length > 50" style="display: flex;justify-content: center;gap: 10px;align-items: center;margin-top:10px;" @click="setTruncate">                                      
                        <div title="すべて表示する" class="selector-accordion-el">
                            <svg class="dot-menu" version="1.1" width="11" height="11" :class="['selector-accordion-inactive' , {'selector-accordion-active' : menu.id === message.id}]" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>
                        </div>
                    </div>
                    <MessageFiles 
                        v-if="message.message_files && message.message_files.length"
                        :list="message.message_files"
                        :message="message"
                        :unchecked="true"
                    /> 
                    <button v-if="remindedUsers" style="padding: 5px 10px 5px 10px;
                        font-size: 12px;
                        line-height: 1.5;
                        border-radius: 0px;
                        background: var(--primary-button);
                        color: #fff;
                        margin-top:10px;" @click="emit('remindRequest', message)" >リマインドから外す</button>        
               
               <div v-if="message.deleted_at == null" class="message-foot-area">
                    <div style="display:flex;width: fit-content;">                    
                        <div v-if="reactButtonView" class="reactButton" :class="{cursorBlock : message.user_id == auth.activeUser.id, reactOn: reacting}" @click="reactOrCheck(message)">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :fill="checkSendIconColor ? 'var(--primary-color)' : 'var(--check-inactive)'">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </div>
                        <div v-if="message.reacted_users.length" @click.stop="viewReactedUsersList" style="display:flex;padding: 10px;margin: 5px 0 -15px -15px;height: 15px;">
                            <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in reactedUsersListAll.slice(0,3)">  
                                <UserIconPreLoad :title="user.name" :disableInstant="true" size="30" :user="user" imgClass="userSmallIcon"/>                                         
                            </div>
                            <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="reactedUsersListAll.length > 3">...({{reactedUsersListAll.length}})</span>
                        </div>                                    
                    </div>
                    <div v-if="checkFunctionView" style="display: flex;margin-top: auto;gap: 15px;min-height: 25px;align-items: end;">
                        <div @click.stop="viewCheckedUserList" style="display: flex;font-size: 12px;cursor: pointer">確認済み ({{ message.checked_users.length}})</div>
                        <div @click.stop="viewunCheckedUserList" style="display: flex;font-size: 12px;cursor: pointer">未確認 ({{ message.unchecked_users.length}})</div> 
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
import { computed, inject, ref } from "vue";
import { useAuthUserStore } from '@/store/auth'
import { useMessageUsers } from "../../../store/messageUsers";
import { mentionFormatter } from "@/utils/tools";
import ItemMenu from "@/components/Global/ItemMenu.vue";
import MessageEditor from "./MessageEditor.vue";
import { useMenuStore } from "@/store/menu";
    const auth = useAuthUserStore()
    const messageUsers = useMessageUsers()
    const props = defineProps(['message', 'openedBoard', 'boxClass'])
    const emit = defineEmits(['remindRequest'])
    const reacting = ref(false)
    const { notify, confirm, info } = inject('dialog')
    const get_incomplete = inject('getUncheckedMessages')
    const editing = ref(false)
    const truncate = ref(null)
    const menu = useMenuStore()
    const authorized = computed(() => {
        return props.message.user_id == auth.activeUser.id
    })
    const messageMenuItems = computed(() => {
        const list= []; 
        function addItem(title, action) {
            list.push({ title, action });
        }
        if(authorized.value){
            addItem('編集する', () => editing.value = true )
        } 
        addItem('リマインドから外す', () => emit('remindRequest', props.message))     

        return list
    })
    const remindedUsers = computed(() => {
        return props.message.message_remind_users && props.message.message_remind_users.length ? props.message.message_remind_users.find(val => val.user_id == auth.activeUser.id) : null
    })
    const messageUserName = computed(() => {                
        return props.message.user && props.message.user.deleted_at == null
        ? props.message.user.name
        : '非アクティブユーザー';
    })
    const messageBody = computed(() => {
        const mentionedMessage = props.message.message
        if (menu.id === props.message.id) {
            return mentionedMessage;
        } else {
            return mentionedMessage.length > 50 
            ? mentionedMessage.slice(0, 50) + '...'
            : mentionedMessage;
        }
        
    })
    const checkFunctionView = computed(() => {
        if(props.message.check_flag == 1){
            let checked = props.message.checked_users.filter(ob => ob.id == auth.activeUser.id).length
            let unchecked = props.message.unchecked_users.filter(ob => ob.id == auth.activeUser.id).length
            return checked || unchecked || props.message.user_id == auth.activeUser.id
        }
        return false
        
    })
    const reactedUsersListAll = computed(() => {
        return props.message.reacted_users && props.message.reacted_users.length ? props.message.reacted_users : []                
    }) 

    const momentMessage =computed(() => {
        moment.locale('ja')
        const date = props.message.created_at
        return moment(props.message.created_at).isSame(moment(), 'day') ? 
        moment(date).format('HH:mm') : 
        moment(date).isSame(moment(), 'year') ? 
        moment(date).format('M / D (ddd) HH:mm') : 
        moment(date).format('YYYY / M / D (ddd) HH:mm')                       
    })
    const setTruncate = () => {
        if (menu.id === props.message.id) {
            menu.setMenu({name: null, id: null})
        } else {
            menu.setMenu({name: 'remindMessage', id: props.message.id})
        }
    }
    const urlCheck = (text) => {
        if(text){                
            var linkedText = Autolinker.link(text, {stripPrefix: false});       
            const catch_tag = '<a href=/app/public/user?id=' 
            const rep_tag = '<a class="mntuser" style="cursor:pointer" id=' 
            linkedText = linkedText.replaceAll(catch_tag, rep_tag);
            return linkedText;                
        }            
    }
    const reactButtonView = computed(() => {
        return !(props.message.user_id == auth.activeUser.id && !props.message.reacted_users.length)
    })
    const checkSendIconColor = computed(() => {                
        const check_list = props.message.reacted_users.filter(ob => ob.id == auth.activeUser.id).length                
        return (props.message.user_id == auth.activeUser.id || check_list) ? true : false              
        
    })           
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
    const reactOrCheck = async(msg) => {        
        if(msg.user_id == auth.activeUser.id) return    
            reacting.value = msg.reacted_users.filter(ob => ob.id == auth.activeUser.id).length ? false : true    
        try{
            const response = await axios.post('/send_reaction_api', {id: msg.id})
            await get_incomplete()
            const checkedMessage = response.data
            if(checkedMessage.check_flag == 1){
                const checked = checkedMessage.checked_users.filter(ob => ob.id == auth.activeUser.id).length
                const unchecked = checkedMessage.unchecked_users.filter(ob => ob.id == auth.activeUser.id).length
                const reacted =   checkedMessage.reacted_users.filter(ob => ob.id == auth.activeUser.id).length          
                if(unchecked && reacted){     
                    const confirmed = await confirm('確認済みにしますか')
                    if(confirmed){
                        await axios.post('/check_send_api', { message_id: msg.id, user_id: auth.activeUser.id, pattern: 'check' })                              
                        await get_incomplete() 
                        info('確認済みにしました。')      
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
        min-height: 100px;
    }

    @media screen and (max-width: 959px) {
        .request-container{
            padding: 20px;
        }
    }
</style>