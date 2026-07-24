<template>
    <section
        ref="scrollArea"
        class="chat-feed"
        aria-live="polite"
        @scroll.passive="handleScroll"
    >
        <div ref="feedContent" class="chat-feed__content">
            <div v-if="items.length === 0 && !pendingUserMessage" class="chat-empty">
                <div class="chat-empty__mark" aria-hidden="true">
                    <AiIcon :size="20" fill="var(--background-color)" />
                </div>
                <h2>社内情報を、会話で探す</h2>
                <p>同期済みのFAQと社内規定を横断して回答します。</p>
                <div v-if="starterPrompts.length" class="chat-empty__prompts">
                    <button
                        v-for="prompt in starterPrompts"
                        :key="prompt"
                        type="button"
                        @click="$emit('prompt', prompt)"
                    >
                        <span>{{ prompt }}</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </div>

            <article
                v-for="item in items"
                :key="item.id"
                :class="['chat-message', `chat-message--${item.role}`]"
            >
                <div v-if="item.role === 'assistant'" class="chat-message__ai-avatar" aria-hidden="true">
                    <AiIcon :size="15" fill="var(--background-color)" />
                </div>
                <UserPanel
                    v-else-if="user?.id"
                    class="chat-message__user-avatar"
                    :user="user"
                    :size="30"
                    disable-instant
                />
                <div class="chat-message__content">
                    <div
                        v-if="item.role === 'assistant'"
                        class="chat-message__markdown"
                        v-html="renderMarkdown(item.message)"
                    />
                    <p v-else>{{ item.message }}</p>
                    <div v-if="item.source?.length" class="chat-message__sources">
                        <span v-for="source in item.source" :key="source">{{ source }}</span>
                    </div>
                </div>
            </article>

            <article v-if="pendingUserMessage" class="chat-message chat-message--user">
                <UserPanel
                    v-if="user?.id"
                    class="chat-message__user-avatar"
                    :user="user"
                    :size="30"
                    disable-instant
                />
                <div class="chat-message__content">
                    <p>{{ pendingUserMessage }}</p>
                </div>
            </article>

            <article v-if="streaming" class="chat-message chat-message--assistant">
                <div class="chat-message__ai-avatar" aria-hidden="true">
                    <AiIcon :size="15" fill="var(--background-color)" />
                </div>
                <div class="chat-message__content chat-message__content--streaming">
                    <div
                        v-if="streamingText"
                        class="chat-message__markdown"
                        v-html="renderMarkdown(streamingText)"
                    />
                    <div v-else class="chat-message__waiting">
                        <span class="chat-message__dots" aria-hidden="true">
                            <i></i><i></i><i></i>
                        </span>
                        {{ statusLabel }}
                    </div>
                    <span v-if="streamingText" class="chat-message__cursor" aria-hidden="true" />
                </div>
            </article>
        </div>

        <button
            v-if="!atBottom"
            type="button"
            class="chat-feed__latest"
            @click="scrollToBottom(true)"
        >
            ↓ 最新の回答へ
        </button>
    </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { renderMarkdown } from '@/utils/markdown'
import AiIcon from '@/components/Icons/AiIcon.vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import type { User } from '@/interface/globalInterface'
import type { ChatItem, StreamStatus } from './types'

const props = defineProps<{
    items: ChatItem[]
    starterPrompts: string[]
    pendingUserMessage: string
    streaming: boolean
    streamingText: string
    streamStatus: StreamStatus
    user: User
}>()

defineEmits<{
    prompt: [prompt: string]
}>()

const scrollArea = ref<HTMLElement | null>(null)
const feedContent = ref<HTMLElement | null>(null)
const atBottom = ref(true)
let followLatest = true
let resizeObserver: ResizeObserver | null = null

const statusLabel = computed(() => {
    if (props.streamStatus === 'searching') return '社内文書を検索しています'
    if (props.streamStatus === 'answering') return '回答を作成しています'
    return '接続しています'
})

/**
 * Follow new content only while the reader remains near the latest message.
 */
const handleScroll = () => {
    if (!scrollArea.value) return

    const distance =
        scrollArea.value.scrollHeight
        - scrollArea.value.scrollTop
        - scrollArea.value.clientHeight

    atBottom.value = distance < 96
    followLatest = atBottom.value
}

const scrollToBottom = async (force = false) => {
    await nextTick()
    if (!scrollArea.value || (!force && !followLatest)) return

    followLatest = true
    scrollArea.value.scrollTo({
        top: scrollArea.value.scrollHeight,
        behavior: force ? 'smooth' : 'auto',
    })
    atBottom.value = true
}

watch(
    () => [props.items.length, props.pendingUserMessage],
    () => scrollToBottom(),
)

watch(
    () => props.streamingText,
    () => scrollToBottom(),
)

onMounted(() => {
    resizeObserver = new ResizeObserver(() => scrollToBottom())
    if (feedContent.value) {
        resizeObserver.observe(feedContent.value)
    }
})

onBeforeUnmount(() => resizeObserver?.disconnect())

defineExpose({
    scrollToBottom,
})
</script>

<style scoped lang="scss">
.chat-feed {
    position: relative;
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
}

.chat-feed__content {
    width: 100%;
    min-height: 100%;
    padding: 30px clamp(16px, 4vw, 48px) 42px;
}

.chat-empty {
    display: flex;
    width: min(100%, 720px);
    min-height: calc(100% - 28px);
    margin: auto;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;

    h2 {
        margin: 18px 0 7px;
        font-size: clamp(19px, 2.2vw, 24px);
        font-weight: 500;
        letter-spacing: -0.02em;
    }

    > p {
        margin: 0;
        font-size: 12px;
        line-height: 1.7;
        opacity: 0.52;
    }
}

.chat-empty__mark {
    display: grid;
    width: 52px;
    height: 52px;
    place-items: center;
    color: var(--background-color);
    background: var(--primary-color);
    border-radius: 50%;

}

.chat-empty__prompts {
    display: grid;
    width: 100%;
    margin-top: 28px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;

    button {
        display: flex;
        min-height: 52px;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 12px 14px;
        color: inherit;
        text-align: left;
        background: var(--background-color);
        border: 1px solid color-mix(in srgb, var(--primary-color) 16%, transparent);
        border-radius: 7px;
        font-size: 12px;
        line-height: 1.5;
        transition: background 0.15s, border-color 0.15s;

        span:last-child {
            flex-shrink: 0;
            opacity: 0.38;
        }

        &:hover {
            background: var(--bg3);
            border-color: color-mix(in srgb, var(--primary-color) 32%, transparent);
        }
    }
}

.chat-message {
    display: flex;
    width: min(100%, 820px);
    margin: 0 auto 28px;
    align-items: flex-start;
    gap: 12px;
}

.chat-message--user {
    flex-direction: row-reverse;

    .chat-message__content {
        width: auto;
        max-width: min(76%, 640px);
        padding: 10px 14px;
        color: var(--background-color);
        background: var(--primary-color);
        border-radius: 12px 3px 12px 12px;
    }
}

.chat-message__ai-avatar {
    display: grid;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    place-items: center;
    color: var(--background-color);
    background: var(--primary-color);
    border: 1px solid var(--primary-color);
    border-radius: 50%;

    :deep(svg) {
        cursor: default;
    }
}

.chat-message__user-avatar {
    flex-shrink: 0;
    margin-top: 1px;
}

.chat-message__content {
    width: min(100%, 740px);
    min-width: 0;
    padding-top: 5px;
    font-size: 13px;
    line-height: 1.78;

    > p {
        margin: 0;
        white-space: pre-wrap;
    }
}

.chat-message__content--streaming {
    position: relative;
}

.chat-message__markdown {
    overflow-wrap: anywhere;

    :deep(p) {
        margin: 0 0 10px;
    }

    :deep(p:last-child) {
        margin-bottom: 0;
    }

    :deep(ul),
    :deep(ol) {
        margin: 8px 0 12px;
        padding-left: 20px;
    }

    :deep(li) {
        margin: 4px 0;
    }

    :deep(h1),
    :deep(h2),
    :deep(h3) {
        margin: 17px 0 7px;
        font-size: 14px;
        font-weight: 700;
    }

    :deep(code) {
        padding: 2px 4px;
        background: var(--bg3);
        border-radius: 3px;
        font-size: 0.92em;
    }

    :deep(a) {
        color: inherit;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
}

.chat-message__sources {
    display: flex;
    margin-top: 14px;
    flex-wrap: wrap;
    gap: 6px;

    span {
        max-width: 100%;
        padding: 6px 9px;
        background: var(--bg3);
        border: 1px solid color-mix(in srgb, var(--primary-color) 11%, transparent);
        border-radius: 5px;
        font-size: 11px;
        line-height: 1.45;
        opacity: 0.74;
        overflow-wrap: anywhere;
    }
}

.chat-message__waiting {
    display: flex;
    min-height: 26px;
    align-items: center;
    gap: 9px;
    font-size: 11px;
    opacity: 0.52;
}

.chat-message__dots {
    display: inline-flex;
    gap: 3px;

    i {
        width: 4px;
        height: 4px;
        background: currentColor;
        border-radius: 50%;
        animation: chat-pulse 1.1s infinite ease-in-out;

        &:nth-child(2) {
            animation-delay: 0.12s;
        }

        &:nth-child(3) {
            animation-delay: 0.24s;
        }
    }
}

.chat-message__cursor {
    display: inline-block;
    width: 6px;
    height: 14px;
    margin-left: 2px;
    background: currentColor;
    vertical-align: -2px;
    animation: chat-cursor 0.8s infinite;
}

.chat-feed__latest {
    position: sticky;
    bottom: 14px;
    display: block;
    margin: -42px auto 10px;
    padding: 7px 11px;
    color: var(--primary-color);
    background: var(--background-color);
    border: 1px solid color-mix(in srgb, var(--primary-color) 24%, transparent);
    border-radius: 999px;
    box-shadow: 0 5px 16px color-mix(in srgb, var(--primary-color) 10%, transparent);
    font-size: 10px;
}

@keyframes chat-pulse {
    0%,
    70%,
    100% {
        opacity: 0.24;
        transform: translateY(0);
    }

    35% {
        opacity: 1;
        transform: translateY(-2px);
    }
}

@keyframes chat-cursor {
    0%,
    49% {
        opacity: 1;
    }

    50%,
    100% {
        opacity: 0;
    }
}

@media screen and (max-width: 640px) {
    .chat-feed__content {
        padding: 22px 13px 34px;
    }

    .chat-empty__prompts {
        grid-template-columns: 1fr;
    }

    .chat-message {
        gap: 8px;
        margin-bottom: 22px;
    }

    .chat-message__ai-avatar {
        width: 28px;
        height: 28px;
    }

    .chat-message--user .chat-message__content {
        max-width: 82%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .chat-message__dots i,
    .chat-message__cursor {
        animation: none;
    }

    .chat-feed {
        scroll-behavior: auto;
    }
}
</style>
