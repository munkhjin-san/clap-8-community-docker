<template>
    <div class="support-ai-chat">
        <ChatHistorySidebar
            :conversations="conversations"
            :active-id="activeConversation?.id || null"
            :loading="loadingHistory"
            :open="sidebarOpen"
            @close="sidebarOpen = false"
            @new="startNewConversation"
            @select="selectConversation"
        />

        <main class="support-ai-chat__main">
            <header class="support-ai-chat__header">
                <button
                    type="button"
                    class="support-ai-chat__menu"
                    aria-label="履歴を開く"
                    @click="sidebarOpen = true"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
                <div class="support-ai-chat__header-copy">
                    <div class="support-ai-chat__title">
                        <h1>{{ activeConversation?.title || 'AIチャット（BETA）' }}</h1>
                        <button
                            v-if="activeConversation"
                            type="button"
                            class="support-ai-chat__delete"
                            aria-label="このチャットを削除"
                            :disabled="sending || deletingConversationId === activeConversation.id"
                            @click="deleteConversation(activeConversation)"
                        >
                            <Trash :size="14" />
                        </button>
                    </div>
                    <p>FAQ・社内規定を横断検索</p>
                </div>
            </header>

            <ChatMessageList
                ref="messageList"
                :items="activeConversation?.items || []"
                :starter-prompts="starterPrompts"
                :pending-user-message="pendingUserMessage"
                :streaming="sending"
                :streaming-text="streamingText"
                :stream-status="streamStatus"
                :user="auth.activeUser"
                @prompt="submitPrompt"
            />

            <ChatComposer
                v-model="draft"
                :sending="sending"
                :error="errorMessage"
                @submit="sendMessage"
            />
        </main>

        <button
            v-if="sidebarOpen"
            type="button"
            class="support-ai-chat__overlay"
            aria-label="履歴を閉じる"
            @click="sidebarOpen = false"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import ChatComposer from './ChatComposer.vue'
import ChatHistorySidebar from './ChatHistorySidebar.vue'
import ChatMessageList from './ChatMessageList.vue'
import Trash from '@/components/Icons/Trash.vue'
import { streamSupportAiMessage } from './supportAiStream'
import type { ChatStreamEvent, Conversation, StreamStatus } from './types'
import { useAuthUserStore } from '@/store/auth'

interface MessageListExposed {
    scrollToBottom: (force?: boolean) => Promise<void>
}

interface SupportQaItem {
    question?: string | null
}

const props = withDefaults(defineProps<{
    qaList?: SupportQaItem[]
}>(), {
    qaList: () => [],
})

const api = useApi()
const { ask, ping } = useDialog()
const auth = useAuthUserStore()
const conversations = ref<Conversation[]>([])
const activeConversation = ref<Conversation | null>(null)
const draft = ref('')
const sending = ref(false)
const loadingHistory = ref(false)
const sidebarOpen = ref(false)
const errorMessage = ref('')
const pendingUserMessage = ref('')
const streamingText = ref('')
const streamStatus = ref<StreamStatus>('connecting')
const messageList = ref<MessageListExposed | null>(null)
const deletingConversationId = ref<number | null>(null)
let activeRequest: AbortController | null = null

const starterPrompts = computed(() => {
    const questions = [...new Set(
        props.qaList
            .map(item => item.question?.trim())
            .filter((question): question is string => Boolean(question)),
    )]

    for (let index = questions.length - 1; index > 0; index -= 1) {
        const swapIndex = Math.floor(Math.random() * (index + 1))
        ;[questions[index], questions[swapIndex]] = [questions[swapIndex], questions[index]]
    }

    return questions.slice(0, 4)
})

/**
 * Load application-owned test conversations.
 */
const loadConversations = async () => {
    loadingHistory.value = true

    try {
        const data = await api.get('/support/ai-test/conversations', null, { silent: true })

        if (!Array.isArray(data)) {
            throw new Error('The conversation history response is not an array.')
        }

        conversations.value = data.filter((item): item is Conversation =>
            typeof item === 'object'
            && item !== null
            && Number.isInteger(item.id)
            && Array.isArray(item.items),
        )

        if (activeConversation.value) {
            activeConversation.value =
                conversations.value.find(item => item.id === activeConversation.value?.id) || null
        }
    } catch {
        errorMessage.value = 'チャット履歴を読み込めませんでした。'
    } finally {
        loadingHistory.value = false
    }
}

/**
 * Stream a grounded answer while persisting the complete turn in MISO.
 */
const sendMessage = async () => {
    const message = draft.value.trim()
    if (!message || sending.value) return

    sending.value = true
    errorMessage.value = ''
    draft.value = ''
    pendingUserMessage.value = message
    streamingText.value = ''
    streamStatus.value = 'connecting'
    activeRequest = new AbortController()

    await nextTick()
    await messageList.value?.scrollToBottom(true)

    try {
        await streamSupportAiMessage(
            {
                message,
                conversation_id: activeConversation.value?.id || null,
            },
            handleStreamEvent,
            activeRequest.signal,
        )
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
            draft.value = message
            errorMessage.value =
                (error as Error).message || 'AIの回答を取得できませんでした。もう一度お試しください。'
        }
    } finally {
        sending.value = false
        pendingUserMessage.value = ''
        streamingText.value = ''
        activeRequest = null
    }
}

const handleStreamEvent = (event: ChatStreamEvent) => {
    if (event.type === 'conversation' && event.conversation) {
        pendingUserMessage.value = ''
        replaceConversation(event.conversation)
        return
    }

    if (event.type === 'status' && event.status) {
        streamStatus.value = event.status
        return
    }

    if (event.type === 'delta' && event.delta) {
        streamStatus.value = 'answering'
        streamingText.value += event.delta
        return
    }

    if (event.type === 'done' && event.conversation) {
        replaceConversation(event.conversation)
        return
    }

    if (event.type === 'error') {
        throw new Error(event.message || 'AIの回答を取得できませんでした。')
    }
}

const submitPrompt = async (prompt: string) => {
    draft.value = prompt
    await sendMessage()
}

const selectConversation = async (conversation: Conversation) => {
    if (sending.value) return

    activeConversation.value = conversation
    sidebarOpen.value = false
    errorMessage.value = ''
    await messageList.value?.scrollToBottom(true)
}

const startNewConversation = () => {
    if (sending.value) return

    activeConversation.value = null
    draft.value = ''
    errorMessage.value = ''
    sidebarOpen.value = false
}

const deleteConversation = async (conversation: Conversation) => {
    if (sending.value || deletingConversationId.value !== null) return

    deletingConversationId.value = conversation.id
    try {
        const decision = await ask('このチャットを削除しますか？', {
            answers: [
                { value: true, label: '削除する' },
                { value: false, label: 'キャンセル' },
            ],
        })
        if (!decision.value) return

        await api.del(`/support/ai-test/conversations/${conversation.id}`, null, { silent: true })
        conversations.value = conversations.value.filter(item => item.id !== conversation.id)

        if (activeConversation.value?.id === conversation.id) {
            activeConversation.value = null
        }
        ping('チャットを削除しました。')
    } catch {
        ping('チャットを削除できませんでした。')
    } finally {
        deletingConversationId.value = null
    }
}

const replaceConversation = (conversation: Conversation) => {
    conversations.value = [
        conversation,
        ...conversations.value.filter(item => item.id !== conversation.id),
    ]
    activeConversation.value = conversation
}

onMounted(loadConversations)
onBeforeUnmount(() => activeRequest?.abort())
</script>

<style scoped lang="scss">
.support-ai-chat {
    position: relative;
    display: flex;
    height: 100%;
    min-height: 0;
    color: var(--primary-color);
    background: var(--background-color);
    overflow: hidden;
}

.support-ai-chat,
.support-ai-chat * {
    box-sizing: border-box;
}

.support-ai-chat__main {
    display: flex;
    min-width: 0;
    flex: 1;
    flex-direction: column;
}

.support-ai-chat__header {
    display: flex;
    min-height: 64px;
    flex-shrink: 0;
    align-items: center;
    gap: 11px;
    padding: 10px 20px;
    background: var(--background-color);
    border-bottom: 1px solid color-mix(in srgb, var(--primary-color) 10%, transparent);

    h1 {
        overflow: hidden;
        margin: 0;
        font-size: 15px;
        font-weight: 500;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    p {
        margin: 2px 0 0;
        font-size: 10px;
        opacity: 0.45;
    }
}

.support-ai-chat__header-copy {
    min-width: 0;
    flex: 1;
}

.support-ai-chat__title {
    display: flex;
    width: 100%;
    min-width: 0;
    align-items: center;
    gap: 8px;

    h1 {
        min-width: 0;
    }
}

.support-ai-chat__delete {
    display: grid;
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    margin-left: auto;
    place-items: center;
    color: inherit;
    background: var(--bg3);
    border-radius: 5px;
    opacity: 0.56;
    transition: opacity 0.15s, background 0.15s;

    &:hover:not(:disabled) {
        background: color-mix(in srgb, var(--primary-color) 9%, transparent);
        opacity: 1;
    }

    &:disabled {
        cursor: wait;
        opacity: 0.2;
    }
}

.support-ai-chat__menu {
    display: none;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    place-items: center;
    color: inherit;
    background: var(--bg3);
    border-radius: 6px;

    svg {
        width: 17px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-width: 1.7;
    }
}

.support-ai-chat__overlay {
    display: none;
}

@media screen and (max-width: 959px) {
    .support-ai-chat__menu {
        display: grid;
    }

    .support-ai-chat__overlay {
        position: absolute;
        inset: 0;
        z-index: 3;
        display: block;
        background: color-mix(in srgb, var(--primary-color) 28%, transparent);
    }
}
</style>
