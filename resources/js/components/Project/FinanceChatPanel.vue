<template>
    <!-- Floating trigger button -->
    <Teleport to="body">
        <button v-if="!open" class="mcp-fab" @click="open = true">
            <AiIcon size="16" fill="#fff"/>
            <span>チャット</span>
        </button>

        <!-- Chat panel -->
        <Transition name="mcp-slide">
            <div v-if="open" class="mcp-panel">
                <!-- Header -->
                <div class="mcp-header">
                    <div class="mcp-header-brand">
                        <div class="mcp-brand-icon">
                            <div class="ai-generation-loader-icon" aria-hidden="true">
                                <AiIcon size="16" fill="#fff"/>
                            </div>
                        </div>
                        <div>
                            <span class="mcp-brand-name">財務AIチャット</span>
                            <span class="mcp-brand-sub">実績 · 着地 · PM別 · 前年比</span>
                        </div>
                    </div>
                    <div class="mcp-header-btns">
                        <button class="mcp-hbtn" title="履歴をクリア" @click="clearChat">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                            </svg>
                        </button>
                        <button class="mcp-hbtn mcp-hbtn--close" title="閉じる" @click="open = false">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="mcp-messages" ref="messagesEl">
                    <!-- Empty state -->
                    <div v-if="messages.length === 0" class="mcp-empty">
                        <div class="mcp-empty-glyph">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                                <path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/>
                            </svg>
                        </div>
                        <p class="mcp-empty-h">何でも聞いてください</p>
                        <p class="mcp-empty-p">今期の着地、プロジェクト別リスク、<br>PM別、前年比、売上集中度など</p>
                        <div class="mcp-starters">
                            <div class="mcp-starter" @click="sendMessage('今期の財務状況と着地見込みを教えて')">今期の着地見込み</div>
                            <div class="mcp-starter" @click="sendMessage('全プロジェクトの健全度を🔴🟡🟢で一覧で見せて。赤信号の案件はPM名と乖離額も教えて')">健全度一覧</div>
                            <div class="mcp-starter" @click="sendMessage('PM別に今期の着地見込みと計画差分をランキングして。利益リスクが大きい順で見せて')">PM別ランキング</div>
                        </div>
                    </div>
                    <!-- Message list -->
                    <template v-else>
                        <div
                            v-for="(msg, i) in messages"
                            :key="i"
                            :class="['mcp-row', msg.role]"
                        >
                            <div v-if="msg.role === 'assistant'" class="mcp-ai-avatar">
                                <AiIcon size="12" fill="#fff"/>
                            </div>
                            <div class="mcp-bubble" v-html="msg.html"></div>
                        </div>
                        <div v-if="loading" class="mcp-row assistant">
                            <div class="mcp-ai-avatar">
                                <AiIcon size="12" fill="#fff"/>
                            </div>
                            <div class="mcp-bubble mcp-typing">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </template>
                </div>
                <!-- Quick chips (scrollable) -->
                <div class="mcp-chips-wrap">
                    <div
                        class="mcp-chips"
                        ref="chipsEl"
                        @mousedown="chipsMousedown"
                        @mousemove="chipsMousemove"
                        @mouseup="chipsMouseup"
                        @mouseleave="chipsMouseup"
                    >
                        <button
                            v-for="q in quickQueries"
                            :key="q.label"
                            class="mcp-chip"
                            :disabled="loading"
                            @click.prevent="chipClick($event, q.text)"
                        >{{ q.label }}</button>
                    </div>
                </div>
                <!-- Input bar -->
                <div class="mcp-footer">
                    <input
                        v-model="inputText"
                        class="mcp-input"
                        placeholder="財務について質問する…"
                        :disabled="loading"
                        @keydown.enter.prevent="handleSubmit"
                    />
                    <button class="mcp-send" :disabled="loading || !inputText.trim()" @click="handleSubmit">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue'
import axios from 'axios'
import AiIcon from '../Icons/AiIcon.vue'

const open        = ref(false)
const loading     = ref(false)
const inputText   = ref('')
const messagesEl  = ref<HTMLElement | null>(null)
const chipsEl     = ref<HTMLElement | null>(null)

// ─── Chip drag-scroll ────────────────────────────────────────────────────────
let chipsDragging = false
let chipsDragStartX = 0
let chipsScrollStart = 0
let chipsDragMoved = false

function chipsMousedown(e: MouseEvent) {
    if (!chipsEl.value) return
    chipsDragging = true
    chipsDragMoved = false
    chipsDragStartX = e.pageX
    chipsScrollStart = chipsEl.value.scrollLeft
    chipsEl.value.style.cursor = 'grabbing'
    chipsEl.value.style.userSelect = 'none'
}
function chipsMousemove(e: MouseEvent) {
    if (!chipsDragging || !chipsEl.value) return
    const delta = e.pageX - chipsDragStartX
    if (Math.abs(delta) > 4) chipsDragMoved = true
    e.preventDefault()
    chipsEl.value.scrollLeft = chipsScrollStart - delta
}
function chipsMouseup() {
    chipsDragging = false
    if (chipsEl.value) {
        chipsEl.value.style.cursor = 'grab'
        chipsEl.value.style.userSelect = ''
    }
}
function chipClick(e: MouseEvent, text: string) {
    if (chipsDragMoved) { chipsDragMoved = false; return }
    sendMessage(text)
}

// Display messages (HTML) shown in the chat panel
type DisplayMsg = { role: 'user' | 'assistant'; html: string }
// History sent to GPT (plain text)
type HistoryMsg  = { role: 'user' | 'assistant'; content: string }

const messages = ref<DisplayMsg[]>([])
const history  = ref<HistoryMsg[]>([])

const quickQueries = [
    { label: '今期の着地',      text: '今期の財務状況と着地見込みを教えて' },
    { label: '利益リスク',      text: '今期、利益が年間計画より悪くなりそうなプロジェクトは？' },
    { label: '月次乖離',        text: '最新の実績反映月でGoogle Sheets実績と損益の乖離が一番大きいプロジェクトは？' },
    { label: 'データ確認',      text: '今期の財務データ品質を確認して。実績・損益・年間計画の欠損や重複はある？' },
    { label: '実績反映月',      text: '最新の実績反映月と、実績が未反映の月を教えて' },
    { label: 'トレンド',        text: '今期の着地見込みと計画のギャップは月ごとにどう変化してる？改善してる？悪化してる？' },
    { label: '健全度一覧',      text: '全プロジェクトの健全度を🔴🟡🟢で一覧で見せて。赤信号の案件はPM名と乖離額も教えて' },
    { label: 'PM別リスク',      text: 'PM別に今期の着地見込みと計画差分をランキングして。利益リスクが大きい順で見せて' },
    { label: '集中リスク',      text: '売上の集中リスクは？トップ3社のシェアとリスク判定を教えて' },
    { label: 'YoY比較',         text: '前期FY2025と今期FY2026の着地見込みを比較して。売上・利益の前年比は？' },
]

// ─── Core send function ───────────────────────────────────────────────────────

async function sendMessage(text: string) {
    const trimmed = text.trim()
    if (!trimmed) return

    pushDisplay('user', escapeHtml(trimmed))
    history.value.push({ role: 'user', content: trimmed })
    loading.value = true

    try {
        const res = await axios.post('/mcp/chat', {
            messages: compactHistory(history.value),
        })
        const reply = normalizeReply(res.data.reply)
        history.value = compactHistory(history.value)
        history.value.push({ role: 'assistant', content: reply })
        pushDisplay('assistant', mdToHtml(reply))
    } catch (e: any) {
        const msg = e.response?.data?.message ?? e.message ?? '不明なエラー'
        pushDisplay('assistant', `<span class="mcp-error">⚠ エラー: ${escapeHtml(msg)}</span>`)
    } finally {
        loading.value = false
        if (history.value.length > 20) history.value = history.value.slice(-20)
    }
}

async function handleSubmit() {
    const text = inputText.value
    inputText.value = ''
    await sendMessage(text)
}

function clearChat() {
    messages.value = []
    history.value  = []
}

function compactHistory(items: HistoryMsg[]): HistoryMsg[] {
    return items
        .map(item => ({
            role: item.role,
            content: String(item.content ?? '').trim(),
        }))
        .filter(item => item.content.length > 0)
}

function normalizeReply(value: unknown): string {
    const reply = String(value ?? '').trim()
    return reply || '回答を生成できませんでした。もう一度お試しください。'
}

function pushDisplay(role: DisplayMsg['role'], html: string) {
    messages.value.push({ role, html })
    nextTick(() => {
        if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
    })
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function escapeHtml(s: string): string {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
}

/** Markdown → safe HTML for AI replies */
function mdToHtml(md: string): string {
    let s = escapeHtml(md)
    // Headings
    s = s.replace(/^### (.+)$/gm, '<div class="mcp-h4">$1</div>')
    s = s.replace(/^## (.+)$/gm,  '<div class="mcp-h3">$1</div>')
    s = s.replace(/^# (.+)$/gm,   '<div class="mcp-h2">$1</div>')
    // **bold**
    s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    // `inline code`
    s = s.replace(/`([^`]+)`/g, '<code class="mcp-code">$1</code>')
    // bullet lines: "- text" or "・text"
    s = s.replace(/^(?:[-•・]\s*)(.+)$/gm, '<li class="mcp-li">$1</li>')
    // numbered lines: "1. text"
    s = s.replace(/^\d+\.\s+(.+)$/gm, '<li class="mcp-li mcp-ol">$1</li>')
    // wrap <li> runs in <ul>
    s = s.replace(/(<li[^>]*>[\s\S]*?<\/li>(?:\s*<li[^>]*>[\s\S]*?<\/li>)*)/g, '<ul class="mcp-ul">$1</ul>')
    // paragraph breaks
    s = s.replace(/\n\n/g, '<br><br>')
    s = s.replace(/\n/g, '<br>')
    return s
}
</script>

<style scoped>
.ai-generation-loader-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: ai-loader-pulse 1.4s ease-in-out infinite;
}
.ai-eye {
  animation: blink 2.4s infinite;
  transform-box: fill-box;
  transform-origin: center;
}
@keyframes blink {
  0%, 88%, 100% { transform: scaleY(1); }
  92%, 96% { transform: scaleY(.15); }
}
/* ── FAB ────────────────────────────────────────────────────────────── */
.mcp-fab {
    position: fixed;
    bottom: 20px;
    right: 30px;
    z-index: 1050;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 12px 20px;
    background: var(--primary-button);
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0,0,0,.28);
    transition: transform .15s, box-shadow .15s;
}
.mcp-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,.35);
}

/* ── Panel ──────────────────────────────────────────────────────────── */
.mcp-panel {
    position: fixed;
    bottom: 80px;
    right: 18px;
    z-index: 1051;
    width: 500px;
    height: 700px;
    max-height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
    background: var(--bg2);
    border: 1px solid var(--bg3);
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(0,0,0,.28);
    overflow: hidden;
}

@media (max-width: 540px) {
    .mcp-panel {
        right: 0; left: 0; bottom: 56px;
        width: 100%;
        height: 80vh;
        max-height: 80vh;
        border-radius: 14px 14px 0 0;
    }
    .mcp-fab { bottom: 66px; right: 14px; }
}

/* ── Header ─────────────────────────────────────────────────────────── */
.mcp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 14px 12px;
    border-bottom: 1px solid var(--bg3);
    flex-shrink: 0;
}
.mcp-header-brand {
    display: flex;
    align-items: center;
    gap: 10px;
}
.mcp-brand-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--primary-button);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mcp-brand-name {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary-color);
    line-height: 1.2;
}
.mcp-brand-sub {
    display: block;
    font-size: 10px;
    color: gray;
    margin-top: 2px;
}
.mcp-header-btns {
    display: flex;
    align-items: center;
    gap: 3px;
}
.mcp-hbtn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: gray;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background .12s, color .12s;
}
.mcp-hbtn:hover { background: var(--bg3); color: var(--primary-color); }
.mcp-hbtn--close:hover { background: rgba(255,80,80,.12); color: tomato; }

/* ── Chips ──────────────────────────────────────────────────────────── */
.mcp-chips-wrap {
    border-top: 1px solid var(--bg3);
    padding: 5px 0;
    flex-shrink: 0;
    /* Fade hints there are more chips beyond the edges */
    -webkit-mask-image: linear-gradient(to right, transparent 0px, black 12px, black calc(100% - 28px), transparent 100%);
    mask-image: linear-gradient(to right, transparent 0px, black 12px, black calc(100% - 28px), transparent 100%);
}
.mcp-chips {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 0 12px;
    cursor: grab;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.mcp-chips::-webkit-scrollbar { display: none; }
.mcp-chip {
    white-space: nowrap;
    flex-shrink: 0;
    padding: 4px 10px;
    border: 1px solid var(--bg3);
    border-radius: 100px;
    background: transparent;
    font-size: 10.5px;
    color: var(--primary-color);
    cursor: pointer;
    transition: background .12s, border-color .12s, color .12s;
}
.mcp-chip:hover:not(:disabled) {
    background: var(--primary-button);
    border-color: var(--primary-button);
    color: #fff;
}
.mcp-chip:disabled { opacity: .4; cursor: not-allowed; }

/* ── Messages ───────────────────────────────────────────────────────── */
.mcp-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scrollbar-width: thin;
    scrollbar-color: var(--bg3) transparent;
}

/* Empty state */
.mcp-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 20px 16px;
    gap: 8px;
}
.mcp-empty-glyph {
    width: 54px;
    height: 54px;
    border-radius: 15px;
    background: var(--bg3);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
}
.mcp-empty-h {
    font-size: 14px;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0;
}
.mcp-empty-p {
    font-size: 11.5px;
    color: gray;
    line-height: 1.6;
    margin: 0;
}
.mcp-starters {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 10px;
    width: 100%;
    max-width: 340px;
}
.mcp-starter {
    padding: 9px 14px;
    background: var(--bg3);
    border-radius: 8px;
    font-size: 12px;
    color: var(--primary-color);
    cursor: pointer;
    text-align: left;
    transition: background .12s;
}
.mcp-starter:hover { background: var(--primary-button); color: #fff; }

/* Message rows */
.mcp-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
.mcp-row.user { flex-direction: row-reverse; }

.mcp-ai-avatar {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: var(--primary-button);
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    letter-spacing: 0;
}
.mcp-bubble {
    max-width: 84%;
    padding: 10px 13px;
    border-radius: 14px;
    font-size: 12.5px;
    line-height: 1.6;
}
.mcp-row.user .mcp-bubble {
    background: var(--primary-button);
    color: #fff;
    border-bottom-right-radius: 3px;
}
.mcp-row.assistant .mcp-bubble {
    background: var(--background-color);
    color: var(--primary-color);
    border-bottom-left-radius: 3px;
}

/* Typing indicator */
.mcp-typing { display: flex; gap: 5px; align-items: center; padding: 12px 16px !important; }
.mcp-typing span {
    width: 7px; height: 7px;
    background: var(--primary-color);
    border-radius: 50%;
    opacity: .4;
    animation: mcpBounce 1.3s ease-in-out infinite;
}
.mcp-typing span:nth-child(2) { animation-delay: .18s; }
.mcp-typing span:nth-child(3) { animation-delay: .36s; }
@keyframes mcpBounce {
    0%, 70%, 100% { transform: translateY(0); opacity: .4; }
    35% { transform: translateY(-5px); opacity: 1; }
}

/* Markdown content inside bubbles */
:deep(.mcp-h2)  { font-size: 14px; font-weight: 700; margin: 8px 0 4px; }
:deep(.mcp-h3)  { font-size: 13px; font-weight: 700; margin: 6px 0 3px; }
:deep(.mcp-h4)  { font-size: 12px; font-weight: 600; margin: 5px 0 3px; }
:deep(.mcp-ul)  { margin: 4px 0 4px 2px; padding: 0; list-style: none; }
:deep(.mcp-li)  { padding-left: 14px; position: relative; margin-bottom: 2px; }
:deep(.mcp-li::before) { content: '·'; position: absolute; left: 3px; font-weight: 700; }
:deep(.mcp-ol)  { list-style: decimal inside; }
:deep(.mcp-code) {
    background: rgba(0,0,0,.12);
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 11px;
    font-family: monospace;
}
:deep(.mcp-error) { color: tomato; }

/* Stats grid inside assistant bubbles (server-rendered HTML) */
:deep(.mcp-stat-grid) {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px;
    margin: 8px 0;
}
:deep(.mcp-stat) {
    background: rgba(0,0,0,.1);
    border-radius: 8px;
    padding: 7px 5px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
:deep(.mcp-stat-val) { font-size: 15px; font-weight: 700; display: block; }
:deep(.mcp-stat-lbl) { font-size: 9px; color: gray; display: block; margin-top: 2px; }
:deep(.mcp-goal-row) {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    border-bottom: 1px solid var(--bg3);
}
:deep(.mcp-badge-red) {
    background: rgba(255,99,71,.15);
    color: tomato;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
}

/* ── Input footer ───────────────────────────────────────────────────── */
.mcp-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px 12px;
    border-top: 1px solid var(--bg3);
    flex-shrink: 0;
}
.mcp-input {
    flex: 1;
    background: var(--bg3);
    border: 1px solid transparent;
    border-radius: 100px;
    padding: 9px 16px;
    font-size: 13px;
    color: var(--primary-color);
    outline: none;
    transition: border-color .15s;
}
.mcp-input:focus { border-color: var(--primary-color); }
.mcp-input::placeholder { color: gray; }
.mcp-send {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 50%;
    background: var(--primary-button);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: opacity .15s, transform .1s;
}
.mcp-send:hover:not(:disabled) { transform: scale(1.08); }
.mcp-send:disabled { opacity: .35; cursor: not-allowed; }

/* ── Panel transition ───────────────────────────────────────────────── */
.mcp-slide-enter-active, .mcp-slide-leave-active {
    transition: transform .22s cubic-bezier(.4,0,.2,1), opacity .22s ease;
}
.mcp-slide-enter-from, .mcp-slide-leave-to {
    transform: translateY(18px) scale(.98);
    opacity: 0;
}
</style>
