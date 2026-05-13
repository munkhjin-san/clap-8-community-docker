<template>
    <div :class="['msg-row', item.role === 'user' ? 'msg-row--user' : 'msg-row--assistant']">
        <div :class="['msg-bubble', item.role === 'user' ? 'msg-bubble--user' : 'msg-bubble--assistant']">
            <div class="msg-body" v-html="body"></div>
            <div v-if="item.keywords && item.keywords.length > 0" class="msg-meta">
                <strong>キーワード:</strong> {{ item.keywords.join('、') }}
            </div>
            <div v-if="item.source && item.source.length > 0" class="msg-meta">
                <strong>参考資料:</strong>
                <ul>
                    <li v-for="(src, srcIndex) in item.source" :key="srcIndex">{{ src }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { SupportConversationItem } from '@/interface/supportInterface';
import { computed } from 'vue';
import {marked} from 'marked'
import DOMPurify from 'dompurify';
const props = defineProps<{
    item: SupportConversationItem
}>()

const body = computed(() => {
    const message = props.item.message || '';
    const markedText = marked(message) as string;
    const saveText = DOMPurify.sanitize(markedText);
    return saveText;
})
</script>

<style scoped>
/* ── Row ──────────────────────────────────────────────── */
.msg-row {
    display: flex;
    padding: 4px 16px;
}
.msg-row--user      { justify-content: flex-end; }
.msg-row--assistant { justify-content: flex-start; }

/* ── Bubble ───────────────────────────────────────────── */
.msg-bubble {
    max-width: 72%;
    padding: 10px 14px;
    font-size: 13px;
    line-height: 1.75;
    word-break: break-word;
    position: relative;
    border-radius: 10px;
}

.msg-bubble--user {
    background: var(--primary-color);
    color: var(--background-color);
}
.msg-bubble--user::after {
    content: '';
    position: absolute;
    bottom: 0;
    right: -9px;
    width: 4px;
    height: 0px;
    border-style: solid;
    border-width: 15px 0 0 12px;
    border-color: transparent transparent transparent var(--primary-color);
}

.msg-bubble--assistant {
    background: var(--bg3);
    color: var(--primary-color);
}
.msg-bubble--assistant::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: -5px;
    width: 0;
    height: 0px;
    border-style: solid;
    border-width: 15px 12px 0px 0px;
    border-color: transparent var(--bg3) transparent transparent;
}

/* ── Markdown content ─────────────────────────────────── */
.msg-body { overflow-wrap: break-word; }
.msg-body :deep(p)          { margin: 0 0 0.5em; overflow-wrap: break-word; }
.msg-body :deep(p:last-child) { margin-bottom: 0; }
.msg-body :deep(ul),
.msg-body :deep(ol)         { padding-left: 1.4em; margin: 0.4em 0; }
.msg-body :deep(li)         { margin: 0.2em 0; }
.msg-body :deep(strong)     { font-weight: 700; }
.msg-body :deep(code) {
    font-family: monospace;
    font-size: 12px;
    padding: 1px 5px;
    border-radius: 4px;
    background: rgba(128,128,128,0.15);
}
.msg-bubble--user .msg-body :deep(code) {
    background: rgba(255,255,255,0.2);
}
.msg-body :deep(pre) {
    border-radius: 8px;
    padding: 10px 12px;
    overflow-x: auto;
    font-size: 12px;
    margin: 0.5em 0;
    background: rgba(128,128,128,0.15);
}
.msg-bubble--user .msg-body :deep(pre) {
    background: rgba(255,255,255,0.15);
}
.msg-body :deep(pre code) {
    background: none;
    padding: 0;
}
.msg-body :deep(h1),
.msg-body :deep(h2),
.msg-body :deep(h3) {
    font-weight: 700;
    margin: 0.6em 0 0.3em;
    font-size: 1em;
}
.msg-body :deep(blockquote) {
    border-left: 3px solid rgba(128,128,128,0.35);
    margin: 0.4em 0;
    padding-left: 10px;
    opacity: 0.8;
}

/* ── Meta (keywords / source) ─────────────────────────── */
.msg-meta {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(128,128,128,0.2);
    font-size: 12px;
    line-height: 1.6;
    opacity: 0.8;
}
.msg-meta ul {
    padding-left: 1.2em;
    margin: 4px 0 0;
}
.msg-meta li { margin: 2px 0; }

/* ── Mobile ───────────────────────────────────────────── */
@media (max-width: 959px) {
    .msg-row   { padding: 3px 10px; }
    .msg-bubble {
        max-width: 85%;
        font-size: 12px;
        padding: 9px 12px;
    }
}
</style>