<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ messageText: string }>()

const EMOJI_MAP: Record<string, string> = {
  'oikawa-angry': '/images/reactions/v3/light_6.webp',
  'oikawa-like' : '/images/reactions/v3/light_2.webp',
  'oikawa-pray' : '/images/reactions/v3/light_3.webp',
  'oikawa-sorry': '/images/reactions/v3/light_5.webp',
}

type Token =
  | { type: 'text'; value: string }
  | { type: 'emoji'; key: string; src: string }
  | { type: 'br' }

const EMOJI_RE = /:([a-z0-9_+-]{2,32}):/gi

function pushText(tokens: Token[], text: string) {
  if (!text) return
  const parts = text.split('\n')
  parts.forEach((part, idx) => {
    if (part) tokens.push({ type: 'text', value: part })
    if (idx < parts.length - 1) tokens.push({ type: 'br' })
  })
}

function tokenizeMessage(input: string, emojiMap: Record<string, string>): Token[] {
  const tokens: Token[] = []
  let lastIndex = 0

  input.replace(EMOJI_RE, (match, key, offset) => {
    // text before emoji
    if (offset > lastIndex) {
      pushText(tokens, input.slice(lastIndex, offset))
    }

    const src = emojiMap[key]
    if (src) {
      tokens.push({ type: 'emoji', key, src })
    } else {
      // unknown shortcode stays as text (and can include newlines, so use pushText)
      pushText(tokens, match)
    }

    lastIndex = offset + match.length
    return match
  })

  // remaining text after last emoji
  if (lastIndex < input.length) {
    pushText(tokens, input.slice(lastIndex))
  }

  return tokens
}
const tokens = computed(() => tokenizeMessage(props.messageText ?? '', EMOJI_MAP))
const emojiOnly = computed(() => {
  const trimmed = props.messageText.replace(/\s+/g, '')
  if (!trimmed) return false
  // If after removing emoji tokens nothing remains, it's emoji-only
  return trimmed.replace(/:([a-z0-9_+-]{2,32}):/gi, '').length === 0
})
</script>

<template>
  <span class="messageInnerBody" :class="{ 'emoji-only': emojiOnly }">
    <template v-for="(t, i) in tokens" :key="i">
        <span v-if="t.type === 'text'">{{ t.value }}</span>
        <br v-else-if="t.type === 'br'" />
        <img v-else class="chat-emoji" :src="t.src" :alt="`:${t.key}:`" />
    </template>
  </span>
</template>

<style scoped>
.messageInnerBody {
  line-height: 1.5;
  white-space: break-spaces; /* ok */
  width: 100%;
  display: inline-block;
}

.messageInnerBody .chat-emoji {
  display: inline-block;
  height: 2.5em;
  vertical-align: -0.2em; /* pull it up */
}
.messageInnerBody.emoji-only .chat-emoji {
  height: 2em;
  vertical-align: middle;
}
</style>