<template>
    <div class="chat-box">
        <div class="w-full h-full relative">   
            <div></div>
            <div class="h-full relative msg-container flex flex-col">         
                <Transition name="modalFade">
                    <div v-if="fetching" class="absolute !top-0 !left-0 !w-full !h-full">
                        <div id="loaderMini">
                            <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                        </div>
                    </div>
                </Transition>    
                <div id="title-wrap" class="flex items-end h-[60px] overflow-x-scroll bg-[var(--bg3)]">
                    <div :class="['leading-[40px] cursor-pointer h-[40px] px-[10px]', !selectedConversation ? 'bg-[var(--message-background)]' : 'bg-[var(--bg3)]']">
                        <label class="cursor-pointer">
                            <p class="text-[12px] whitespace-nowrap max-w-[100px] overflow-hidden leading-[40px] text-ellipsis">新しいチャット</p>
                            <input type="radio" class="hidden" :value="null" v-model="selectedConversation"/>
                        </label>
                    </div>
                    <div v-for="conversation in conversationsList" :class="['leading-[40px] cursor-pointer h-[40px] px-[10px] flex items-center', selectedConversation && selectedConversation.id === conversation.id ? 'bg-[var(--message-background)]' : 'bg-[var(--bg3)]']" :key="conversation.id">
                        <label class="cursor-pointer">
                            <p class="text-[12px] whitespace-nowrap max-w-[100px] overflow-hidden leading-[40px] text-ellipsis">{{ conversation.title || 'タイトルがありません' }}</p>
                            <input @change="setSelectedConversation" type="radio" class="hidden" :value="conversation" v-model="selectedConversation"/>
                        </label>
                        <div @click="deleteConversation(conversation.id)" v-if="conversation.id == selectedConversation?.id" class="w-[30px] h-[30px] flex items-center justify-center -mr-[10px] cursor-pointer" title="チャットを削除">
                            <Trash size="15" fill="var(--primary-color)"/>
                        </div>
                    </div>

                </div>
                <div class="flex-1 overflow-y-auto bg-[var(--message-background)] py-5 space-y-3" ref="chatBody">
                    <div v-for="(msg, index) in conversationItemsList" :key="index">
                        <ConversationItem :item="msg" ref="conversationItemsRef"/>
                    </div>
                    <div class="typing-bubble" v-if="loading">
                        <div class="typing-dots">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center p-[20px] w-[calc(100%-40px)] bg-[var(--message-background)]">
                    <div class="flex items-end w-full">
                        <textarea name="user-message" placeholder="メッセージを入力" class="chatbox-input px-[10px] py-[5px] text-[var(--primary-color)] bg-[inherit]" v-model="inputMessage" ref="messageInput" @input="autoResize" rows="1"></textarea>
                        <button @click="sendMessage" class="bg-[inherit] flex items-center ml-[10px] mb-1">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="25" viewBox="0 0 43 32" style="margin: auto; fill: var(--third-color);">
                                <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { computed, onMounted, reactive, ref, useTemplateRef } from 'vue';
import { SupportConversation, SupportConversationItem } from '@/interface/supportInterface';
import Trash from '@/components/Icons/Trash.vue';
import ConversationItem from './ConversationItem.vue';
const emit = defineEmits<{
    close: []
}>()

onMounted(() => {
    getConversationsHistory()
})

const conversationsList = ref<SupportConversation[]>([])
const selectedConversation = ref<SupportConversation | null>(null)
const inputMessage = ref('')
const fetching = ref(true)
const api = useApi()
const loading = ref(false)
const chatBody = useTemplateRef('chatBody')

const conversationItemsRef = useTemplateRef('conversationItemsRef')
const messageInput = useTemplateRef('messageInput')

const autoResize = () => {
    const el = messageInput.value as HTMLTextAreaElement | null
    if (!el) return
    el.style.height = 'auto'
    el.style.height = Math.min(el.scrollHeight, 57) + 'px'
}
const greetingMessage = ref<SupportConversationItem>({
    id: 0,
    role: 'assistant',
    message: 'こんにちは！ご質問があれば何でも聞いてください。',
    source: [],
    keywords: [],
    support_conversation_id: 0,
})

const userPrompt = reactive<SupportConversationItem>({
    role: 'user',
    message: '',
    source: [],
    keywords: [],
    support_conversation_id: 0,
    id: -1,
})

const conversationItemsList = computed(() => {
    const list = selectedConversation.value && selectedConversation?.value?.items ? [greetingMessage.value, ...selectedConversation.value.items] : [greetingMessage.value]
    if(loading.value) {
        list.push(userPrompt)
    }
    return list

    // if(selectedConversation.value) {
    //     return selectedConversation.value.items
    // }
    // else if(loading.value) {
    //     return [greetingMessage.value, userPrompt]
    // }
    // else
    // return [greetingMessage.value]
})
const getConversationsHistory = async() => {
    fetching.value = true;
    conversationsList.value = await api.get('/get_conversations_history');
    fetching.value = false;
}
const sendMessage = async() => {
    if(loading.value) return;
    const message = inputMessage.value.trim();
    if (message) {
        setTimeout(() => {
            scrollToBottom('smooth');
        });
        userPrompt.message = message;
        inputMessage.value = '';
        const el = messageInput.value as HTMLTextAreaElement | null
        if (el) { el.style.height = '30px' }
        loading.value = true;
        const conversationId = selectedConversation.value ? selectedConversation.value.conversation_id : null;
        const reply = await api.post('/support_add_message', { message, conversationId });
        inputMessage.value = '';
        loading.value = false;
        selectedConversation.value = reply;
        const exist = conversationsList.value.find(c => c.id === reply.id);
        if(!exist) {
            conversationsList.value.unshift(reply);
        } else {
            Object.assign(exist, reply);
        }

        setTimeout(() => {
            const lastItem = conversationItemsRef.value?.[conversationItemsRef.value.length - 1];
            if (lastItem && lastItem.$el) {
                lastItem.$el.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
}

const scrollToBottom = (behavior: ScrollBehavior) => {
    if(chatBody.value) {
    // chatBody.value.scrollTop = chatBody.value.scrollHeight;
        chatBody.value.scrollTo({
            top: chatBody.value.scrollHeight,
            behavior: behavior,
        });
    }
};
const deleteConversation = async(id: number) => {
    const res = await api.post('/delete_conversation', { id }, { ask: 'チャットを削除しますか？', toast: 'チャットを削除しました。' });
    if(res == null) return;
    if(selectedConversation.value && selectedConversation.value.id === id) {
        selectedConversation.value = null;
    }
    await getConversationsHistory();
}

const setSelectedConversation = () => {
    setTimeout(() => {
        scrollToBottom('instant');
    });
}
</script>
<style scoped>
#title-wrap::-webkit-scrollbar-track {
    background-color: var(--message-background); 
}
.chat-box {
    width: 100%;
    height: 100%;
}

.typing-bubble {
    border-radius: 4px;
    padding: 16px 20px;
    display: inline-block;
    position: relative;
    width: fit-content;
    margin: 20px;
    background: var(--bg3);
}



.typing-dots {
    display: flex;
    align-items: center;
    gap: 4px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #999;
    animation: bounce 1.4s infinite ease-in-out;
}

.dot:nth-child(1) {
    animation-delay: -0.32s;
}

.dot:nth-child(2) {
    animation-delay: -0.16s;
}

.dot:nth-child(3) {
    animation-delay: 0s;
}
.chatbox-input{
    border: solid thin var(--formBorder);
    width: calc(100% - 30px);
    min-height: 25px;
    max-height: 100px;
    height: 25px;
    resize: none;
    overflow-y: auto;
    line-height: 1.5;
    box-sizing: border-box;
    padding: 15px;
}
.chatbox-input::placeholder {
    color: var(--placeholder-color);
    opacity: 0.5;
    padding-top: 5px;;
}
@keyframes bounce {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}
@media screen and (max-width: 959px) {

}
</style>