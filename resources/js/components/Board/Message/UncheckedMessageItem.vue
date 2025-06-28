<template>
    <div :class="boxClass">
        <div>
            <div :class="['request-container', {editIsOn:editing}]">
                
                <div style="display:flex;align-items:center;position:relative;margin-bottom: 10px;">                      
                    <UserPanel :disableInstant="true" v-if="message.user" size="30" :user="message.user" imgClass="userNormalIcon"/>    
                    <div v-else>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30" imgClass="userNormalIcon">
                            <circle cx="15" cy="15" r="15" fill="#ddd"/>
                        </svg>
                    </div>                      
                    <p :id="'messageSender_' + message.id" class="userName" @dragstart.prevent style="margin-left:10px;">{{ messageUserName }}</p>     
                    <p @dragstart.prevent class="dateText" style="font-size:12px;color:grey;position:absolute;right:-10px;top:-10px">{{DateParser(message.created_at)}}</p>
                </div>                             
                                
                    
                    <div v-if="messageBody" class="normal-body" style="position: relative;">
                        <div
                            v-if="!editing"
                            @click="jumpToMessage"
                            @dragstart.prevent
                            :id="'editComment_' + message.id"                          
                            :style="{height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease'}"
                            class="messageInnerBody"
                            style="display:block;word-break:break-word;margin-top:10px"
                            
                            
                        >
                        <p ref="messageBodyRef" v-html="mentionFormatter(messageBody, true)"></p>
                        </div>
                    </div>
                     
                    <div @click="setTruncate" class="jump-link" style="margin-top:10px; font-size: 14px;" v-if="dynamicHeight !== 'auto'">{{ dynamicHeight == '170px' ? '続きを表示する' : '閉じる' }}</div>
                   
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
                        margin-top:10px;" @click="remindRequest" >リマインドから外す</button>        
               
               <div v-if="message.deleted_at == null" class="message-foot-area">
                    <div style="display:flex;width: fit-content;">                    
                        <div v-if="reactButtonView" class="reactButton" :class="{cursorBlock : message.user_id == auth.activeUser.id, reactOn: reacting}" @click="reactOrCheck(message)">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 38 32" :fill="checkSendIconColor ? 'var(--primary-color)' : 'var(--check-inactive)'">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </div>
                        <div v-if="message.reacted_users.length" @click.stop="viewReactedUsersList" style="display:flex;padding: 10px;margin: 5px 0 -15px -15px;height: 15px;">
                            <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in reactedUsersListAll.slice(0,3)">  
                                <UserPanel :title="user.name" :disableInstant="true" size="15" :user="user" imgClass="userSmallIcon"/>                                         
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

<script setup lang="ts">
import MessageFiles from "./MessageFiles.vue";
import { computed, onMounted, ref, useTemplateRef } from "vue";
import { useAuthUserStore } from '@/store/auth'
import { useMessageUsers } from "../../../store/messageUsers";
import { DateParser, mentionFormatter } from "@/utils/tools";
import UserPanel from "@/components/Global/UserPanel.vue";
import { useBadgeStore } from "@/store/badge";
import { useApi } from "@/composables/api";
import { useDialog } from "@/composables/dialog";
const { toast, ping, ask } = useDialog()
    const auth = useAuthUserStore()
    const messageUsers = useMessageUsers()
    const props = defineProps(['message', 'openedBoard', 'boxClass'])
    const emit = defineEmits(['getRemindMessages', 'getUncheckedMessages'])
    const reacting = ref(false)
    const editing = ref(false)
    const dynamicHeight = ref('auto')
    const messageBodyRef = useTemplateRef('messageBodyRef')
    const badge = useBadgeStore()
    const api = useApi()
    onMounted(() => {
        if (messageBodyRef.value) {
            if(messageBodyRef.value?.clientHeight > 170){
                dynamicHeight.value = '170px'
            }
        }
    })
    const setTruncate = () => {
        dynamicHeight.value = dynamicHeight.value == '170px' ? `${messageBodyRef.value?.clientHeight}px` : '170px'
    }
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
        return mentionedMessage
        
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

        const message = await api.post('/send_reaction_api', {id: msg.id})
        emit('getUncheckedMessages')
        emit('getRemindMessages')
        const checkedMessage = message
        if(checkedMessage.check_flag == 1){
            const checked = checkedMessage.checked_users.filter(ob => ob.id == auth.activeUser.id).length
            const unchecked = checkedMessage.unchecked_users.filter(ob => ob.id == auth.activeUser.id).length
            const reacted =   checkedMessage.reacted_users.filter(ob => ob.id == auth.activeUser.id).length          
            if(unchecked && reacted){     
                const confirmed = await ask('確認済みにしますか')
                if(confirmed.value){
                    await api.post('/check_send_api', { message_id: msg.id, user_id: auth.activeUser.id, pattern: 'check' })                              
                    emit('getUncheckedMessages')    
                    toast('確認済みにしました。') 
                    badge.getRemindBadge()     
                }                                  
            }
            if(checked && reacted){                  
                ping('既に確認しています。')  
            }
        } 
           
    }  
    const jumpToMessage = (event) => {
        if (event.target.tagName === 'A') return
        const link = document.createElement('a');
        link.href = `${window.location.origin}/board/${props.message.record_id}?m=${props.message.id}&jump_message=true`;                
        document.body.appendChild(link);            
        link.click();   
        link.remove();
    } 
    const remindRequest = async(message) => {
        const data = await api.post('/remind_add', { id: message.id })
        const inf = data === true ? 'リマインドしました。' : 'リマインドを取り消しました。'
        toast(inf)
        if(data !== null){
            badge.getRemindBadge()
            emit('getRemindMessages')
        } 
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