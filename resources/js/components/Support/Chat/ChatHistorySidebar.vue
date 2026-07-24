<template>
    <aside :class="['chat-history', { 'chat-history--open': open }]">
        <div class="chat-history__header">
            <div class="chat-history__heading">
                <h2>チャット履歴</h2>
                <span>{{ conversations.length }}</span>
            </div>
            <button
                type="button"
                class="chat-history__close"
                aria-label="履歴を閉じる"
                @click="$emit('close')"
            >
                ×
            </button>
        </div>

        <button type="button" class="chat-history__new" @click="$emit('new')">
            <span aria-hidden="true">＋</span>
            新しいチャット
        </button>

        <div v-if="conversations.length > 4" class="chat-history__search">
            <PostSearchBar
                class-name="newChatMemberSearch"
                custom-place-holder="履歴を検索"
                :initial-value="search"
                @search-start="search = $event"
            />
        </div>

        <p v-if="loading" class="chat-history__state">履歴を読み込み中...</p>
        <p v-else-if="conversations.length === 0" class="chat-history__state">
            テストチャットの履歴はまだありません。
        </p>
        <p v-else-if="groups.length === 0" class="chat-history__state">
            一致するチャットはありません。
        </p>
        <div v-else class="chat-history__list">
            <section v-for="group in groups" :key="group.key" class="chat-history__group">
                <h3>{{ group.label }}</h3>
                <div class="chat-history__group-rows">
                    <button
                        v-for="conversation in group.conversations"
                        :key="conversation.id"
                        type="button"
                        :class="[
                            'chat-history__row',
                            { 'chat-history__row--active': conversation.id === activeId },
                        ]"
                        @click="$emit('select', conversation)"
                    >
                        <span class="chat-history__title">{{ conversation.title || '新しいチャット' }}</span>
                        <span class="chat-history__meta">
                            {{ formatRowDate(conversation.updated_at) }}
                            <i aria-hidden="true">·</i>
                            {{ conversation.items.length }}件
                        </span>
                    </button>
                </div>
            </section>
        </div>
    </aside>
</template>

<script setup lang="ts">
import PostSearchBar from '@/components/Post/PostSearchBar.vue'
import { computed, ref } from 'vue'
import type { Conversation } from './types'

const props = defineProps<{
    conversations: Conversation[]
    activeId: number | null
    loading: boolean
    open: boolean
}>()

defineEmits<{
    close: []
    new: []
    select: [conversation: Conversation]
}>()

interface ConversationGroup {
    key: 'today' | 'yesterday' | 'week' | 'older'
    label: string
    conversations: Conversation[]
}

const search = ref('')

const groups = computed<ConversationGroup[]>(() => {
    const normalizedSearch = search.value.trim().toLocaleLowerCase('ja-JP')
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()
    const buckets: Record<ConversationGroup['key'], Conversation[]> = {
        today: [],
        yesterday: [],
        week: [],
        older: [],
    }

    props.conversations
        .filter((conversation) => {
            if (!normalizedSearch) return true
            return (conversation.title || '新しいチャット')
                .toLocaleLowerCase('ja-JP')
                .includes(normalizedSearch)
        })
        .forEach((conversation) => {
            const date = parseDate(conversation.updated_at)
            const day = date
                ? new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime()
                : 0
            const ageInDays = Math.floor((today - day) / 86_400_000)

            if (ageInDays <= 0) buckets.today.push(conversation)
            else if (ageInDays === 1) buckets.yesterday.push(conversation)
            else if (ageInDays < 7) buckets.week.push(conversation)
            else buckets.older.push(conversation)
        })

    const labels: Record<ConversationGroup['key'], string> = {
        today: '今日',
        yesterday: '昨日',
        week: '過去7日間',
        older: '以前',
    }

    return (Object.keys(buckets) as ConversationGroup['key'][])
        .filter(key => buckets[key].length > 0)
        .map(key => ({
            key,
            label: labels[key],
            conversations: buckets[key],
        }))
})

const parseDate = (value?: string | null): Date | null => {
    if (!value) return null

    const date = new Date(value.replace(/(\.\d{3})\d+Z$/, '$1Z'))
    return Number.isNaN(date.getTime()) ? null : date
}

const formatRowDate = (value?: string | null) => {
    const date = parseDate(value)
    if (!date) return ''
    const now = new Date()
    const sameDay =
        date.getFullYear() === now.getFullYear()
        && date.getMonth() === now.getMonth()
        && date.getDate() === now.getDate()

    return new Intl.DateTimeFormat('ja-JP', {
        ...(sameDay ? {} : { month: 'numeric', day: 'numeric' }),
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}
</script>

<style scoped lang="scss">
.chat-history {
    display: flex;
    width: 252px;
    min-width: 252px;
    min-height: 0;
    flex-direction: column;
    padding: 18px 12px 12px;
    background: var(--background-color);
    border-right: 1px solid color-mix(in srgb, var(--primary-color) 10%, transparent);
}

.chat-history__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 6px 16px;

    h2 {
        margin: 0;
        font-size: 14px;
        font-weight: 500;
    }
}

.chat-history__heading {
    display: flex;
    align-items: center;
    gap: 7px;

    span {
        display: grid;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        place-items: center;
        background: color-mix(in srgb, var(--primary-color) 7%, transparent);
        border-radius: 999px;
        font-size: 9px;
        opacity: 0.58;
    }
}

.chat-history__close {
    display: none;
    width: 30px;
    height: 30px;
    place-items: center;
    color: inherit;
    font-size: 18px;
    opacity: 0.55;
}

.chat-history__new {
    display: flex;
    min-height: 34px;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: var(--primary-color);
    background: var(--bg3);
    border: 1px solid color-mix(in srgb, var(--primary-color) 8%, transparent);
    border-radius: 6px;
    font-size: 12px;
    font-weight: 400;
    transition: background 0.15s, border-color 0.15s;

    &:hover {
        background: color-mix(in srgb, var(--primary-color) 7%, var(--background-color));
        border-color: color-mix(in srgb, var(--primary-color) 14%, transparent);
    }
}

.chat-history__search {
    margin-top: 10px;
}

.chat-history__state {
    margin: 0;
    padding: 22px 8px;
    font-size: 12px;
    line-height: 1.7;
    opacity: 0.52;
}

.chat-history__list {
    display: flex;
    min-height: 0;
    margin-top: 12px;
    flex: 1;
    flex-direction: column;
    gap: 16px;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
}

.chat-history__group {
    display: flex;
    flex-direction: column;
    gap: 6px;

    h3 {
        margin: 0;
        padding: 0 5px;
        font-size: 9px;
        font-weight: 500;
        letter-spacing: 0.04em;
        opacity: 0.42;
    }
}

.chat-history__group-rows {
    overflow: hidden;
    background: var(--background-color);
    border: 1px solid color-mix(in srgb, var(--primary-color) 8%, transparent);
    border-radius: 7px;
}

.chat-history__row {
    display: flex;
    width: 100%;
    min-height: 54px;
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
    padding: 10px;
    color: inherit;
    text-align: left;
    background: var(--background-color);
    border: 0;
    border-bottom: 1px solid color-mix(in srgb, var(--primary-color) 7%, transparent);
    border-radius: 0;
    transition: background 0.15s, box-shadow 0.15s;

    &:last-child {
        border-bottom: 0;
    }

    &:hover {
        background: var(--bg3);
    }

    &--active {
        background: var(--bg3);
    }
}

.chat-history__title {
    overflow: hidden;
    width: 100%;
    font-size: 12px;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chat-history__meta {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    opacity: 0.42;

    i {
        font-style: normal;
    }
}

@media screen and (max-width: 959px) {
    .chat-history {
        position: absolute;
        inset: 0 auto 0 0;
        z-index: 4;
        width: min(86vw, 320px);
        min-width: 0;
        transform: translateX(-105%);
        box-shadow: 12px 0 30px color-mix(in srgb, var(--primary-color) 15%, transparent);
        transition: transform 0.2s ease;

        &--open {
            transform: translateX(0);
        }
    }

    .chat-history__close {
        display: grid;
    }

}
</style>
