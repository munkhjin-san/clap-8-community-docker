<template>
    <div class="gantt-overlay" @click="emit('close')">
        <div class="gantt-overlay-inner" ref="createModal" @click.stop>
            <div class="flex items-center h-[60px]">
                <div class="mobile w-[40px] m-h-[60px] flex items-center justify-center cursor-pointer" @click="emit('close')">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg"><path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path></svg>
                </div>
                <div class="comment-title-block max-w-[calc(100%-120px)] overflow-hidden">
                  <div v-if="currentProjectName" class="comment-title-eyebrow" :title="currentProjectName">{{ currentProjectName }}</div>
                  <p class="overflow-hidden overflow-ellipsis whitespace-nowrap text-xs">{{DateTime.fromFormat(period, 'yyyy-MM').toFormat('yyyy年M月')}}コメント
                    <span v-if="commentsList.length">({{ commentsList.length }})</span>
                  </p>
                </div>
                <button
                    v-if="nextUnread"
                    type="button"
                    class="next-unread-btn"
                    :title="nextUnread.sameProject ? '次の未読コメントへ' : `${nextUnread.project_name} の未読コメントへ`"
                    @click="goToNextUnread"
                >
                    次の未読コメント
                    <svg width="9" height="9" viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M8.835 31.913c-0.769 0-1.538-0.293-2.124-0.879-1.173-1.173-1.173-3.075 0-4.248l10.786-10.786-10.786-10.786c-1.173-1.173-1.173-3.075 0-4.248s3.075-1.173 4.248 0l12.91 12.91c1.173 1.173 1.173 3.075 0 4.248l-12.91 12.91c-0.586 0.586-1.355 0.879-2.124 0.879z"></path></svg>
                </button>
                <div @click="emit('close')" class="flex items-center justify-end cursor-pointer w-[60px] h-[60px] pc ml-auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32" fill="currentColor">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>                
            </div>
            <div class="c-b">
                <div class="c-b-l" ref="commentParent">
                    <Transition name="modalFade">
                      <div class="work-loader" style="height: 100%; background-color: var(--bg3);" v-if="loading">
                          <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                      </div> 
                    </Transition>
                    <div class="post-no-comment-text" v-if="!commentsList.length">現在メッセージはありません。</div>
                    <CommentItem 
                        v-for="comment in commentsList" 
                        :comment="comment"
                        :editable="editingCommentId"
                        :mentionableUsers="mentionableUsers" 
                        @replyComment="(val) => replyComment = val"
                        @edit="(val) => editingCommentId = val"
                        @deleted="onDeletedComment"
                        @reload="getComments"
                    />
                </div>
                <div v-if="replyComment" class="bg-[rgba(0,_0,_0,_0.66)] p-2 text-white flex items-center">
                  <p class="text-ellipsis whitespace-nowrap overflow-hidden text-xs">編集 : {{ replyComment?.comment }}</p>
                  
                  <div @click="replyComment = null" class="ml-auto cursor-pointer">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" :width="`12px`" :height="`12px`" fill="white" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                  </div>
                </div>
                <div class="c-b-i">
                    <div id="typeArea" @keydown.enter="entersend" @input="onInput" @keydown="onKeydown" ref="commentText" contenteditable="plaintext-only" class="typeBoxArea taskCommentArea"></div>
                    <div @click="send" style="background:transparent;padding-left: 5px;display: flex;cursor: pointer;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="33" viewBox="0 0 43 32" style="margin: auto; fill: var(--third-color);margin-bottom: 5px;">
                            <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                        </svg>
                    </div>
                    <Transition name="modalFade">
                        <MentionBox 
                            :fromProject="true"
                            v-if="showMentionBox" 
                            :mentionAbleList="filteredMentionable"
                            @mentionUser="insertMention"
                            @close="closeMention"
                            ref="mentionBox"
                        />
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import MentionBox from '@/components/Board/Message/MentionBox.vue';
import { useApi } from '@/composables/api';
import { User } from '@/interface/globalInterface';
import { computed, nextTick, onBeforeUnmount, onMounted, Ref, ref, useTemplateRef, watch } from 'vue';
import CommentItem from './CommentItem.vue';
import { FinanceComment } from '@/interface/projectInterface';
import { useBadgeStore } from '@/store/badge';
import { useDashboardStore } from '@/store/dashboard';
import { DateTime } from 'luxon';
type Props = {
    type: string;
    currentProjectId: number;
    currentProjectName?: string;
    period: string;
}
const emit = defineEmits<{
    (e: 'close'): void,
    (e: 'getCommentCounts'): void,
    (e: 'goToPeriod', period: string): void,
    (e: 'navigateUnread', target: { projectId: number, period: string }): void
}>()
const api = useApi()
const props = defineProps<Props>()
const keyCharacters = ref('')
const showMentionBox = ref(false)
const commentsList = ref<FinanceComment[]>([])
const isSending = ref(false)
const loading = ref(true)
const mentionableUsers = ref<User[]>([])
const commentParent = useTemplateRef('commentParent')
type MentionBoxExpose = {
  highlighted: Ref<number>;
  mentionUser: (user: User, index: number) => void;
}
const commentText = useTemplateRef<HTMLElement>('commentText')
const mentionBox = useTemplateRef<MentionBoxExpose>('mentionBox')
const editingCommentId = ref<number | null>(null)
const replyComment = ref<FinanceComment | null>(null)
const badge = useBadgeStore()
const dashboardStore = useDashboardStore()
// 次の未読コメントの遷移先（同じプロジェクトの次の月 → 他プロジェクト）を求める。
// バッジは markRead() で更新されるため、開いた月は対象から外れる。
const nextUnread = computed(() => badge.nextFinanceUnread(props.currentProjectId, props.period))
const goToNextUnread = () => {
  const target = nextUnread.value
  if (!target) return
  if (target.sameProject) {
    emit('goToPeriod', target.period) // 同一プロジェクト内は再マウントせず月だけ切り替え
  } else {
    emit('navigateUnread', { projectId: target.project_id, period: target.period })
  }
}
const mentionNameToId = computed<Record<string, number>>(() => {
  const map: Record<string, number> = {}
  for (const u of mentionableUsers.value ?? []) {
    if (!u?.name) continue
    map[u.name.toLowerCase()] = u.id
  }
  map['全員'] = -1
  map['all'] = -1
  return map
})
const onDeletedComment = (id: number) => {
  const idx = commentsList.value.findIndex(c => c.id === id)
  if (idx !== -1) commentsList.value.splice(idx, 1)
  setTimeout(() => {
    emit('getCommentCounts')
  }, 500)
}
const extractMentionIdsFromText = (text: string): { ids: number[]; hasAll: boolean } => {
  const re = /\[To:([^:\]]+):\]/g
  const ids = new Set<number>()
  let hasAll = false
  let m: RegExpExecArray | null
  while ((m = re.exec(text)) !== null) {
    const raw = (m[1] || '').trim()
    const key = raw.toLowerCase()
    if (key === '全員' || key === 'all') {
      hasAll = true
      continue
    }
    const uid = mentionNameToId.value[key]
    if (typeof uid === 'number') ids.add(uid)
  }
  return { ids: [...ids], hasAll }
}
const getPlainText = (): string => {
  return (commentText.value?.innerText || '').trim()
}
const send = async () => {
  if (isSending.value) return

  const text = getPlainText()
  if (!text) return
  const { ids: mentioned_user_ids, hasAll } = extractMentionIdsFromText(text)
  const finalMentions = hasAll
    ? Array.from(
        new Set([
          ...mentioned_user_ids,
          ...(mentionableUsers.value?.map(u => u.id) ?? []),
        ])
      ).filter(id => id !== -1)
    : mentioned_user_ids

  isSending.value = true

  const payload = {
    project_record_id: props.currentProjectId,
    comment: text,
    type: '実績',
    period: props.period,
    mentioned_user_ids: finalMentions,
    reply_id: replyComment.value?.id
  }
  const data = await api.post('/project_finance_comment', payload)

  commentsList.value.push(data)

  if (commentText.value) commentText.value.innerText = ''
  closeMention()

  isSending.value = false
  replyComment.value = null
  emit('getCommentCounts')
  scrollToEnd()
}
const closeMention = () => {
  keyCharacters.value = ''
  showMentionBox.value = false
  const highlight = mentionBox.value?.highlighted
  if (highlight?.value) {
    highlight.value = -1
  }
}
const filteredUsers = computed<User[]>(() => {
  const base = (mentionableUsers.value ?? []).slice() // clone
  return [{ id: -1, name: '全員', icon_path: null } as User, ...base]
})

const filteredMentionable = computed(() => {
  const q = keyCharacters.value.replace(/^[@＠]/, '').toLowerCase()
  if (!q) return filteredUsers.value
  return filteredUsers.value.filter(u =>
    u.name && u.name.toLowerCase().includes(q)
  )
})
let lastRange: Range | null = null
const updateRangeFromSelection = () => {
  const editorEl = commentText.value
  if (!editorEl) return
  const sel = window.getSelection()
  if (!sel?.rangeCount) return
  const range = sel.getRangeAt(0)
  if (!editorEl.contains(range.startContainer)) return
  lastRange = range.cloneRange()
}
const onInput = () => {
  const editorEl = commentText.value
  if (!editorEl) return
  const sel = window.getSelection()
  if (!sel || sel.rangeCount === 0) {
    closeMention()
    return
  }
  const range = sel.getRangeAt(0).cloneRange()
  if (!editorEl.contains(range.startContainer)) {
    closeMention()
    return
  }
  lastRange = range.cloneRange()
  const pre = range.cloneRange()
  pre.setStart(editorEl, 0)
  const textUpToCaret = pre.toString()

  const match = textUpToCaret.match(/([@＠][^\s@＠]*)$/);
  if (match) {
    keyCharacters.value = match[1]
    showMentionBox.value = true
    nextTick(() => {
      const highlight = mentionBox.value?.highlighted
      if (highlight && highlight.value < 0) {
        highlight.value = 0
      }
    })
  } else {
    closeMention()
  }
}

const normalizeRangeToText = (range: Range): Range => {
  const r = range.cloneRange();
  r.collapse(true);

  if (r.startContainer.nodeType === Node.TEXT_NODE) return r;

  const el = r.startContainer as Element;
  let n: Node | null =
    el.childNodes[r.startOffset] ??
    el.childNodes[r.startOffset - 1] ??
    el.lastChild ?? el.firstChild ?? el;

  // dive to rightmost text node
  let cur: Node | null = n;
  while (cur && cur.nodeType !== Node.TEXT_NODE) {
    cur = (cur.lastChild ?? cur.firstChild) as Node | null;
  }
  if (!cur || cur.nodeType !== Node.TEXT_NODE) {
    const t = document.createTextNode('');
    el.appendChild(t);
    cur = t;
  }
  r.setStart(cur, (cur as Text).data.length);
  r.collapse(true);
  return r;
}

const getAtTokenRangeAcrossNodes = (rootEl: HTMLElement, caret: Range): Range | null => {
  const r = normalizeRangeToText(caret);
  const endNode = r.startContainer as Text;
  const endOffset = r.startOffset;

  const anchors: Array<{ node: Text; start: number; end: number }> = [];
  let collected = '';
  const MAX_BACK = 200;

  const pushSeg = (t: Text, from = 0, to = t.data.length) => {
    anchors.unshift({ node: t, start: from, end: to });
    collected = t.data.slice(from, to) + collected;
  };

  pushSeg(endNode, 0, endOffset);

  let cur: Node | null = endNode;
  while (collected.length < MAX_BACK) {
    while (cur && !cur.previousSibling && cur !== rootEl) cur = cur.parentNode;
    if (!cur || cur === rootEl) break;
    cur = cur.previousSibling!;

    let n: Node | null = cur;
    while (n && n.nodeType !== Node.TEXT_NODE) n = n.lastChild;
    if (!n || n.nodeType !== Node.TEXT_NODE) continue;

    const t = n as Text;
    if (t.data.length === 0) continue;
    pushSeg(t);

    if (/[\s([{>]/.test(t.data.slice(-1))) break;
  }

  const m = /(?:^|[\s([{>])@([A-Za-z0-9._-]*)$/.exec(collected);
  if (!m) return null;

  const tokenLen = m[1].length + 1;

  let toConsume = tokenLen;
  let startNode = anchors[anchors.length - 1]!.node;
  let startOffset = anchors[anchors.length - 1]!.end;

  for (let i = anchors.length - 1; i >= 0 && toConsume > 0; i--) {
    const seg = anchors[i];
    const segLen = seg.end - seg.start;
    const take = Math.min(segLen, toConsume);
    startNode = seg.node;
    startOffset = seg.end - take;
    toConsume -= take;
  }

  const token = document.createRange();
  token.setStart(startNode, startOffset);
  token.setEnd(endNode, endOffset);

  if (!/^@[\S]*$/.test(token.toString())) return null;
  return token;
}

const replaceRangeWithText = (range: Range, text: string): void => {
  let target = range.cloneRange();

  if (target.collapsed && target.startContainer.nodeType !== Node.TEXT_NODE) {
    target = normalizeRangeToText(target);
  }

  target.deleteContents();

  const tn = document.createTextNode(text);
  target.insertNode(tn);

  const prev = tn.previousSibling;
  if (prev?.nodeType === Node.TEXT_NODE) {
    const t = prev as Text;
    if (t.data.endsWith('@') || t.data.endsWith('＠')) {
      t.deleteData(t.length - 1, 1);
      if (t.length === 0) prev.parentNode?.removeChild(prev);
    }
  }

  const sel = window.getSelection();
  if (!sel) return;
  const after = document.createRange();
  after.setStart(tn, tn.length);
  after.collapse(true);
  sel.removeAllRanges();
  sel.addRange(after);

  lastRange = after.cloneRange();
}

const insertMention = (user: User): void => {
  const editorEl = commentText.value;
  if (!editorEl) return;

  let caret: Range | null = null;
  const sel = window.getSelection();

  if (lastRange) {
    caret = lastRange.cloneRange();
  } else if (sel && sel.rangeCount > 0) {
    caret = normalizeRangeToText(sel.getRangeAt(0));
  }
  if (!caret) return;
  if (!editorEl.contains(caret.startContainer)) return;

  const tokenRange =
    getAtTokenRangeAcrossNodes(editorEl, caret) ?? caret;

  const label = user.id === -1 ? '全員' : (user.name ?? '');
  if (!label) {
    closeMention()
    return
  }
  replaceRangeWithText(tokenRange, `[To:${label}:] `);
  closeMention();
}
const selectMentionFromKeyboard = () => {
  const candidates = filteredMentionable.value
  if (!candidates.length) {
    closeMention()
    return
  }
  const highlightedIndex = mentionBox.value?.highlighted?.value ?? 0
  const safeIndex = highlightedIndex >= 0 && highlightedIndex < candidates.length ? highlightedIndex : 0
  insertMention(candidates[safeIndex])
}

const onKeydown = (e: KeyboardEvent) => {
  if (showMentionBox.value) {
    if (e.key === 'Enter') {
      e.preventDefault()
      selectMentionFromKeyboard()
      return
    }
    if (e.key === 'Escape') {
      e.preventDefault()
      closeMention()
      return
    }
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
      e.preventDefault()
    }
    return
  }
  if (e.key === 'Escape') {
    e.preventDefault()
    closeMention()
  }
}
const cleanupFns: Array<() => void> = []
const addEditorListeners = () => {
  const editorEl = commentText.value
  if (!editorEl) return
  const events: Array<keyof HTMLElementEventMap> = ['keyup', 'mouseup', 'input']
  events.forEach(evt => editorEl.addEventListener(evt, updateRangeFromSelection))
  cleanupFns.push(() => {
    events.forEach(evt => editorEl.removeEventListener(evt, updateRangeFromSelection))
  })
}
const getMentionableUsers = async() => {
    const data = await api.get('/mentionable_users', {projectId: props.currentProjectId})
    if (data) {
        mentionableUsers.value = data
    }
}
const getComments = async () => {
  const data = await api.get('/get_project_finance_comments', { project_record_id: props.currentProjectId, period: props.period })
  commentsList.value = data
  loading.value = false
  scrollToEnd()
  

}
const scrollToEnd = async() => {
  await nextTick()
  const el = commentParent.value
  if (el) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
}
const markRead = async () => {
  await api.post(`/projects/${props.currentProjectId}/finance/mark-read`, { period: props.period })
  await badge.getFinanceCommentBadge()
  await dashboardStore.getBatchDashboardData(['projects'])
}

const entersend = (event: KeyboardEvent) => {
  if (event.altKey) {
    send()
  }
}
onMounted(async() => {
  getMentionableUsers()
  getComments()
  await markRead()
  nextTick(() => {
    addEditorListeners()
    document.addEventListener('selectionchange', updateRangeFromSelection)
    cleanupFns.push(() => document.removeEventListener('selectionchange', updateRangeFromSelection))
  })
})
onBeforeUnmount(() => {
  cleanupFns.splice(0).forEach(unbind => unbind())
})
watch(() => props.period, async () => {
  loading.value = true
  await getComments()
  await markRead()
})
</script>
<style>
.next-unread-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 12px;
    padding: 5px 11px;
    font-size: 12px;
    line-height: 1;
    white-space: nowrap;
    border: 1px solid var(--primary-color);
    border-radius: 999px;
    background: transparent;
    cursor: pointer;
    transition: background-color .15s ease, color .15s ease;
    color: var(--primary-color);
}
.next-unread-btn:hover {
    background: var(--bg3);
    color: var(--primary-color);
}
@media (max-width: 480px) {
    .next-unread-btn {
        margin-left: 6px;
        padding: 5px 8px;
        gap: 4px;
    }
}
.comment-title-block {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.2;
    min-width: 0;          /* let the title yield room to the button on narrow screens */
}
.comment-title-eyebrow {
    font-size: 11px;
    color: var(--third-color, #888);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

</style>
