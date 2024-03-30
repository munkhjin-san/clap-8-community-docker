<template>
    <div ref="commentScrollEnd" :id="'queueMessage_' + message.u_id" class="messageBoxRoot" :class="{ selfMessage: message.user_id == auth.activeUser.id}">
        <div 
            ref="scrollBottom" 
            :style="{
                float: auth.activeUser.id == message.user.id ? 'right' : 'left',
                margin: '0 15px',
                maxWidth: message.message == null || !message.message || !message.message.length ? '50%' : '80%',
                width: 'fit-content'
            }" 
            class="mobileMessageBody"
            :class="{ emojiOnly: (message.emoji_flag == 1 || message.emoji_flag == 2) && !message.message_reply && !message.message_quot}"
            >
            <div class="queueBox" :class="{queueBoxError : message.error}" style="z-index:4">
                <div id="loaderMini" v-if="!message.error || resending">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div> 
            </div>
            <div class="queueBox softBg" style="z-index:3">
               
            </div>
            
            <div id="commentBody">
                <div :id="'reply_' + message.id" class="commentHeder" style="position:relative;">
                    <div class="column-01 cursor-pointer" v-if="message.user">                        
                        <UserIcon size="30" :user="message.user" imgClass="userNormalIcon"/>                       
                    </div> 
                    <div class="column-01 cursor-pointer" v-else>                        
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30">
                            <circle cx="15" cy="15" r="15" fill="#ddd"/>
                        </svg>                     
                    </div>                    
                    <div class="column-02 cursor-pointer" style="margin-top: 7px;line-height: unset;">                        
                        <p :id="'messageSender_' + message.id" class="userName" @dragstart.prevent style="margin-left:10px;margin-right:35px;">{{ messageUserName }}</p>   
                    </div> 
                    
                    <div class="column-03" style="position: absolute;top: -40px;right: -17px;display: flex;"> 
                        <div v-if="message.error && !resending" style="font-size: 11px;color: tomato;bottom: -20px;white-space: nowrap;right:0;display:flex;align-items:center">
                            <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                                <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                            </svg>    
                            <span style="margin-left:5px">メッセージの送信に失敗しました</span>
                        </div>                      
                        
                    </div>                   
                    
                    <div class="clearBoth"></div>
                </div>                                            
            </div>    

            
            <div :class="message.user_id == auth.activeUser.id ? 'commentTextBoxRight' : 'commentTextBoxLeft'" style="background:transparent">               
                
                <MessageQuoteReply 
                    v-if="message.message_reply"
                    :which="'reply'"
                    :message="message.message_reply"
                    :quotMessage="null"
                    :urlCheck="urlCheck"/>
                <MessageQuoteReply 
                    v-if="message.message_quot"
                    :which="'quot'"
                    :message="message.message_quot"
                    :quotMessage="message.quot_message"
                    :urlCheck="urlCheck"/>
                <MessageQuoteReply 
                    v-if="message.message_forward"
                    :which="'forward'"
                    :message="message.message_forward"
                    :quotMessage="null"
                    :urlCheck="urlCheck"/>
                



                <div class="normal-body" style="display: flex;flex-direction: column;">
                    <div
                        @blur.prevent 
                        v-if="message.message" 
                        :id="'editComment_' + message.id" 
                        style="line-height: 1.5;white-space: break-spaces;outline:none;display: inline-block;width:100%" 
                        v-html="messageBody" 
                        :class="{ emojiOnlyInner: (message.emoji_flag == 1 || message.emoji_flag == 2) && !message.message_reply && !message.message_quot}">
                    </div>
                    

                    <div v-if="message.attached_temp_files.length" class="file-area-content" :class="{ hasMessage: (message.message && message.message.length)}">
                        <div :key="file.id" class="file-wrap" v-for="file in message.attached_temp_files">   
                            <div class="file-area-container" >
                                <div class="flex-centered">   
                                    <div v-if="file.mime_type == 'image'" style="max-width:65px;height:40px;display: flex;">
                                        <img style="max-width:100%;margin:auto;max-height:100%;" :src="'/cdn/temp_upload/'+ file.id + '.' + file.extension">
                                    </div>
                                    
                                    <FileIcon v-if="file.mime_type !== 'image'" :ext="file.extension"/>
                                    <p class="shared-file-name">{{file.name}}</p>
                                </div>     
                            </div>                                         
                        </div>
                    </div> 
                    <div v-if="message.sharing_files.length" class="file-area-content" :class="{ hasMessage: (message.message && message.message.length)}">
                        <div :key="file.record.id" class="file-wrap" v-for="file in message.sharing_files">   
                            <div class="file-area-container" >
                                <div class="flex-centered">  
                                    <div v-if="file.record.mime_type == 'image'" style="max-width:65px;height:40px;display: flex;">
                                        <img style="max-width:100%;margin:auto;max-height:100%;" :src="file.path">
                                    </div>
                                    <FileIcon v-if="file.record.mime_type !== 'image'" :ext="file.record.extension"/>
                                    <p class="shared-file-name">{{file.record.name}}</p>
                                </div>
                            </div>                                              
                        </div>
                    </div>          
                    
                </div>              
            </div>
            <div v-if="message.error && !resending" style="color: var(--primary-color);
                fill:var(--primary-color);
                position: absolute;
                bottom: -26px;
                right: 0px;
                display: flex;
                font-size: 12px;">
                <div @click="resendMessage" style="display:flex;align-items:center;cursor:pointer">
                    <svg style="margin-top: 1px;" viewBox="0 0 24 24" width="15" height="15" xmlns="http://www.w3.org/2000/svg"> 
                        <path d="M21.91,4.09a1,1,0,0,0-1.07.16L19.48,5.46A9.81,9.81,0,0,0,12,2a10,10,0,1,0,9.42,13.33,1,1,0,0,0-1.89-.66A8,8,0,1,1,12,4a7.86,7.86,0,0,1,6,2.78L16.34,8.25a1,1,0,0,0-.27,1.11A1,1,0,0,0,17,10h4.5a1,1,0,0,0,1-1V5A1,1,0,0,0,21.91,4.09Z"/>
                    </svg>
                    <span style="margin-left:3px">再送信</span>
                </div>
                <div @click="removeError(message.id)" style="display:flex;align-items:center;margin-left:10px;cursor:pointer">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 27 32">
                        <path d="M18.68 10.952c-0.427-0.035-0.797 0.289-0.832 0.716-0.104 1.271-0.173 2.542-0.243 3.812l-0.104 1.906-0.081 1.906-0.069 1.906c-0.023 0.635-0.035 1.271-0.046 1.906-0.023 1.271-0.046 2.553-0.023 3.824 0.012 0.37 0.289 0.693 0.682 0.728 0.416 0.035 0.774-0.266 0.809-0.67 0.116-1.271 0.196-2.542 0.266-3.812 0.035-0.635 0.081-1.271 0.104-1.906l0.081-1.906 0.069-1.906 0.046-1.906c0.023-1.271 0.058-2.553 0.058-3.824-0.012-0.393-0.312-0.739-0.716-0.774zM9.473 21.21l-0.069-1.918-0.081-1.906c-0.023-0.635-0.069-1.271-0.104-1.906-0.069-1.271-0.15-2.542-0.266-3.812-0.035-0.37-0.347-0.67-0.728-0.67-0.416-0.012-0.751 0.323-0.751 0.739-0.023 1.271 0 2.553 0.023 3.824 0.012 0.635 0.023 1.271 0.046 1.906l0.069 1.906 0.081 1.906 0.104 1.906c0.069 1.271 0.15 2.542 0.243 3.812 0.035 0.393 0.37 0.716 0.774 0.716 0.427 0 0.774-0.347 0.774-0.774 0-1.271-0.023-2.553-0.058-3.824l-0.058-1.906zM14.279 15.515c-0.023-1.271-0.046-2.542-0.092-3.824-0.023-0.404-0.335-0.728-0.739-0.739-0.427-0.012-0.786 0.312-0.809 0.739-0.046 1.271-0.069 2.542-0.092 3.824l-0.023 1.906-0.012 1.906 0.012 1.906c0 0.635 0.023 1.271 0.023 1.906 0.023 1.271 0.058 2.542 0.116 3.824 0.023 0.37 0.323 0.682 0.705 0.705 0.416 0.023 0.762-0.289 0.786-0.705 0.069-1.271 0.104-2.542 0.116-3.824 0.012-0.635 0.023-1.271 0.023-1.906l0.012-1.906-0.023-3.812z"></path>
                        <path d="M26.64 7.601v-0.012c0-0.531-0.439-0.97-0.982-0.959-0.127 0-0.3 0.012-0.451 0.023-0.231 0.012-0.451-0.046-0.647-0.162-0.312-0.196-0.682-0.404-0.855-0.485l-0.693-0.323c-0.231-0.104-0.474-0.196-0.705-0.289-0.947-0.37-1.918-0.682-2.9-0.924-0.416-0.104-0.947-0.208-1.282-0.277-0.116-0.023-0.196-0.139-0.196-0.254 0.035-0.451 0.081-1.178 0.092-1.536 0.023-0.439 0.023-0.866 0.046-1.305 0.012-0.554-0.416-1.017-0.97-1.028h-0.058l-1.814-0.046c-0.601-0.023-1.213-0.023-1.814-0.023l-1.814 0.012c-0.601 0.012-1.213 0.023-1.814 0.046h-0.081c-0.543 0.023-0.97 0.485-0.947 1.028l0.023 0.647c0.012 0.22 0.012 0.439 0.023 0.647 0.023 0.358 0.058 1.028 0.081 1.479 0 0.139-0.092 0.254-0.231 0.277-0.335 0.058-0.832 0.162-1.259 0.266-0.994 0.231-1.964 0.531-2.911 0.901-0.751 0.289-1.49 0.612-2.207 1.005-0.196 0.116-0.416 0.162-0.635 0.162h-0.485c-0.624 0-1.132 0.497-1.132 1.121v0.012l-0.023 3.5c0 0.635 0.508 1.155 1.144 1.155h0.751l1.074 18.622v0.023c0.046 0.635 0.578 1.144 1.225 1.132l18.449-0.116c0.578 0 1.063-0.462 1.097-1.051l1.040-18.784h0.901c0.543 0 0.994-0.439 0.982-0.994l-0.023-3.489zM10.755 2.38c0-0.081 0.012-0.162 0.012-0.254 0.277 0.012 0.555 0.012 0.832 0.023l1.814 0.012c0.601 0 1.213 0 1.814-0.012l0.82-0.023c0 0.081 0 0.162 0.012 0.254 0.012 0.393 0.035 0.994 0.035 1.352 0 0.058-0.046 0.104-0.104 0.104-0.543-0.046-1.721-0.116-2.576-0.116-0.832 0-2.091 0.081-2.53 0.116-0.069 0.012-0.116-0.046-0.127-0.104-0.012-0.335-0.023-0.97 0-1.352zM22.816 11.033v0.012l-1.201 18.126c-0.023 0.3-0.266 0.52-0.555 0.52l-15.203 0.023c-0.266 0-0.474-0.208-0.497-0.462l-1.19-18.218v-0.012c-0.035-0.612-0.543-1.086-1.167-1.086h-0.866c-0.116 0-0.208-0.092-0.208-0.208v-0.797c0-0.116 0.081-0.208 0.196-0.208 0.254-0.023 0.612-0.069 0.751-0.15h0.012c0.751-0.474 1.571-0.89 2.414-1.248s1.721-0.647 2.622-0.878c1.791-0.474 3.651-0.705 5.51-0.716 1.86 0 3.72 0.22 5.522 0.67 0.901 0.231 1.791 0.508 2.634 0.855 0.22 0.081 0.427 0.173 0.635 0.266l0.312 0.139 0.312 0.15c0.208 0.092 0.404 0.208 0.601 0.312 0 0 0.254 0.15 0.393 0.22 0.104 0.046 0.22 0.116 0.312 0.162 0.058 0.035 0.127 0.046 0.196 0.046h0.312c0.104 0 0.173 0.081 0.173 0.185l-0.012 1.19c0 0.104-0.081 0.185-0.185 0.185h-0.797c-0.543-0.012-0.982 0.393-1.028 0.924z"></path>
                    </svg>
                    <span style="margin-left:3px">削除</span>
                </div>
            </div>

        </div>
            <div class="clear-both"></div>
    </div>
</template>

<script setup>
import Autolinker from 'autolinker';
import MessageQuoteReply from "./MessageQuoteReply.vue";
import MessageFiles from "./MessageFiles.vue";
import FileIcon from '../Mixed/FileIcon.vue';
import { computed, inject, onMounted, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useTempUnique } from '@/store/tempUnique';
import UserIcon from '../Mixed/UserIcon.vue';
        const auth = useAuthUserStore()
        const tempUnique = useTempUnique()
        const props = defineProps(['message', 'qIndex', 'messageListType'])

        const resending = ref(false)
        const { sent, sendError, removeError, resetReplyQuot } = inject('messageItem')
        onMounted(() => {  
            setTimeout(()=> {
                const container = document.getElementById('boardListInner')                
                if(container){                    
                    container.scrollTop = -1
                }                
            },0)   
            sendMessage();            
        })

            const messageBody = computed(() => {
                const text = props.message.message ? props.message.message : ''
                const to_all = text.replace('<span class="toAll">@全員</span>', '<a class="toAll">@全員</a>'); 
                const converterd = to_all.replace(/<((?!a )[^>]*)>/g, "&lt;$1&gt;").replace(/&lt;\/a&gt;/g, "</a>");
                const br_remove = converterd.replace(/&lt;br&gt;/g," ");
                return urlCheck(br_remove)
            })
            const messageUserName = computed(() => {                
                return props.message.user && props.message.user.deleted_at == null
                ? props.message.user.name
                : '非アクティブユーザー';
            })

            const sendMessage = async() => {
                if(!resending.value && props.message.error) {
                    return
                }              
                const params = {
                    message: props.message.message,
                    app_name: 'board',
                    record_id: props.message.record_id,
                    message_id: null,
                    reply_flag: props.message.reply_flag,
                    reply_id: props.message.reply_id,
                    quot_flag: props.message.quot_flag,
                    quot_id: props.message.quot_id,
                    forward_message_id: props.message.forward_message_id,
                    mentioned_users: props.message.mentioned_users,
                    my_id: auth.activeUser.id,
                    selected_quot_text: props.message.quot_message,
                    attached_temp_files: props.message.attached_temp_files,
                    imported_files: [],
                    forwarded_files: [],
                    u_id: props.message.u_id,
                    sharing_files: props.message.sharing_files
                };       
                let u_list = []
                u_list.push(props.message.u_id);
                tempUnique.setTempUniqueIds(u_list)
                try{
                    const response = await axios.post('/chat_add_api', params)
                    if(response && response.data && response.data.success && response.data.u_id == props.message.u_id){
                        sent(props.message)
                        resetReplyQuot()
                        resending.value = false
                        let u_list = tempUnique.ids
                        u_list = u_list.filter( ob => ob !== props.message.u_id)                                
                        tempUnique.setTempUniqueIds(u_list)
                    }
                }catch (e) {
                    sendError(props.message)
                    console.log(e)
                    resending.value = false
                }               
            }
            const resendMessage = () => {
                resending.value = true
                sendMessage('resend')                

            }
            const urlCheck = (text) => {
                if(text){                
                    var linkedText = Autolinker.link(text, {stripPrefix: false});              
                    return linkedText;                
                }            
            }
              

</script>
