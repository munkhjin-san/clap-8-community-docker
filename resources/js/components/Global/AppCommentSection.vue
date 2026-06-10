<template>
    <section class="app-comment-section">
        <div class="app-comment-header">
            <h3>{{ title }}</h3>
            <span>{{ comments.length }}件</span>
        </div>

        <div ref="commentContainer" class="app-comment-list">
            <div v-if="loading" class="py-6 flex justify-center">
                <div class="spinner-micro"></div>
            </div>
            <div v-else-if="!comments.length" class="app-comment-empty">現在メッセージはありません。</div>
            <template v-else>
                <div
                    v-for="comment in comments"
                    :key="comment.id"
                    class="app-comment-item"
                    :class="{ 'is-mine': comment.user_id === auth.activeUser.id }"
                >
                    <div class="app-comment-meta">
                        <UserPanel v-if="comment.user" :user="comment.user" with-name size="24" disable-instant />
                        <span>{{ DateParser(comment.created_at) }}</span>
                    </div>
                    <div class="app-comment-body" v-html="mentionFormatter(comment.content, true)"></div>
                    <div v-if="comment.files?.length" class="app-comment-files">
                        <button
                            v-for="(file, index) in comment.files"
                            :key="file.id"
                            type="button"
                            class="app-comment-file"
                            @click="previewFile(comment, file, index)"
                        >
                            <img
                                v-if="file.mime_type === 'image'"
                                :src="filePath(comment, file)"
                                alt=""
                                draggable="false"
                            />
                            <FileIcon v-else :ext="file.extension" />
                            <span>{{ file.name }}</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="app-comment-compose relative">
            <Transition name="modalFade">
                <MentionBox
                    v-if="mentionBoxForced || mentionBoxOpen"
                    :forced="mentionBoxForced"
                    :style="{position:  mentionBoxForced ? 'absolute' : 'fixed'}"
                    :mention-able-list="filteredMentionableUsers"
                    @mention-user="insertMention"
                    @close="closeMentionBox"
                    ref="mentionBox"
                />
            </Transition>

            <div class="typeContainer app-comment-type-container">
                <div v-show="uploadedFiles.length">
                    <div class="preUploadImage">
                        <div
                            v-for="file in uploadedFiles"
                            :key="file.id"
                            class="cursor-pointer"
                            style="margin: auto 10px 10px 0;min-height: 40px;user-select:none"
                        >
                            <div class="preImgWrapper" @click="previewTempFile(file)">
                                <img
                                    v-if="file.mime_type === 'image'"
                                    :src="`/cdn/temp_upload/${file.id}.${file.extension}`"
                                    alt=""
                                    draggable="false"
                                />
                                <FileIcon v-else :ext="file.extension" />
                                <p class="shared-file-name">{{ file.name }}</p>
                            </div>
                            <button type="button" @click="removeAttachment(file)">
                                <svg @click.prevent xmlns="http://www.w3.org/2000/svg" width="7" height="7" viewBox="0 0 32 32" fill="var(--primary-color)" style="pointer-events: none;">
                                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-.362-.387-.964-1.006-1.363-1.412-.227-.23-.227-.594-.001-.826.397-.408.993-1.023 1.355-1.409l3.378-3.667 3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709.569-.64.583-1.621 0-2.278-.629-.712-1.715-.779-2.426-.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-.49.456-.967.925-1.447 1.394-.211.206-.551.206-.765 0-.48-.469-.957-.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375L6.336 5.144C5.106 4.023 3.871 2.91 2.625 1.806 1.984 1.24 1.004 1.224.346 1.806c-.712.63-.779 1.717-.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667.36.385.957 1.002 1.354 1.409.227.232.225.597-.001.826-.401.406-1.002 1.024-1.363 1.412L6.887 22.586l-3.388 3.661-1.682 1.841-1.668 1.855c-.6.669-.615 1.707 0 2.392.661.732 1.789.792 2.522.131l1.855-1.667 1.841-1.682 7.318-6.776c.487-.455.959-.922 1.432-1.389.214-.209.557-.209.769 0 .476.466.949.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c.671.602 1.707.618 2.392 0 .736-.659.796-1.789.135-2.522z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="typeAreaOuter">
                    <div
                        ref="editor"
                        class="typeBoxArea boardTypeArea hasText app-comment-editor"
                        contenteditable="plaintext-only"
                        @click="rememberCaret"
                        @keyup="handleEditorKeyup"
                        @keydown.enter="handleEnter"
                        @compositionupdate="handleComposition"
                    ></div>
                    <div class="typeCommandBar">
                        <div class="message-icon-outer">
                            <div title="メンション" class="message-icon-wrapper" style="position: relative;">
                                <svg @click.stop="openForcedMention" height="19" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 32" style="fill: var(--third-color);">
                                    <path d="M7.073 10.146c0.051 0.267 0.127 0.533 0.19 0.787 0.076 0.254 0.178 0.521 0.279 0.775 0.406 1.003 1.029 1.93 1.816 2.692 0.775 0.762 1.714 1.359 2.717 1.74 1.003 0.394 2.298 0.559 3.149 0.559 1.143 0 2.133-0.19 3.149-0.584 1.003-0.381 1.93-0.978 2.717-1.727 0.775-0.749 1.41-1.664 1.841-2.667s0.648-2.095 0.66-3.187c0.013-1.092-0.19-2.197-0.597-3.213-0.406-1.029-1.016-1.968-1.778-2.768-0.775-0.787-1.702-1.435-2.73-1.879s-2.146-0.673-3.264-0.673-2.222 0.229-3.251 0.673c-1.016 0.432-1.943 1.092-2.717 1.879-1.524 1.587-2.387 3.797-2.349 5.968l0.051 0.813c0.025 0.267 0.076 0.533 0.114 0.813zM10.197 6.438c0.292-0.648 0.711-1.232 1.232-1.727 0.508-0.483 1.117-0.864 1.765-1.117s1.333-0.381 2.032-0.381 1.384 0.14 2.032 0.394 1.244 0.635 1.752 1.117c0.508 0.483 0.927 1.067 1.219 1.714s0.444 1.359 0.457 2.070c0.025 1.435-0.559 2.857-1.549 3.924-0.495 0.533-1.105 0.952-1.765 1.27-0.673 0.305-1.537 0.47-2.146 0.47-0.432 0-1.473-0.203-2.133-0.495-0.66-0.305-1.27-0.724-1.765-1.244-0.99-1.041-1.625-2.451-1.6-3.898 0.025-0.737 0.178-1.448 0.47-2.095zM15.264 19.048c4.064 0 6.54 1.168 8.444 2.387 2.171 1.384 3.708 3.073 4.686 4.457s1.702 2.984 2.019 4.127c0.292 1.054 0 1.829-0.673 1.981-0.622 0.127-1.232-0.33-1.524-0.927-0.419-0.851-1.168-2.337-1.93-3.352-0.838-1.13-1.981-2.235-3.124-3.060-0.978-0.711-2.489-1.384-3.822-1.765-1.054-0.305-2.565-0.483-4.089-0.483-1.537 0-3.187 0.229-4.14 0.508-1.333 0.381-2.806 1.041-3.771 1.74-1.13 0.825-2.273 1.917-3.124 3.060-0.749 1.016-1.498 2.502-1.93 3.352-0.292 0.597-0.902 1.054-1.524 0.927-0.673-0.14-0.952-0.927-0.673-1.981 0.317-1.143 1.041-2.743 2.019-4.127s2.514-3.086 4.686-4.457c1.93-1.219 4.406-2.387 8.47-2.387z"></path>
                                </svg>
                            </div>
                            <div title="添付ファイル" class="message-icon-wrapper">
                                <label class="cursor-pointer flex">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="17" viewBox="0 0 27 32" style="fill: var(--third-color);">
                                        <path d="M25.954 7.013c-0.479-0.575-4.378-4.56-5.978-5.816-0.623-0.489-1.284-0.853-2.127-0.949-1.178-0.125-2.97-0.182-4.091-0.22-1.36-0.029-2.472-0.029-3.832-0.029-1.36 0.010-3.008 0.077-4.474 0.172-1.36 0.077-2.328 0.134-2.845 0.22-0.69 0.105-1.188 0.489-1.265 1.303-0.077 0.805-0.172 4.905-0.172 7.454 0.010 2.558 0.115 5.835 0.201 6.822 0.096 0.987 0.556 1.447 1.083 1.504 0.527 0.067 0.843-0.537 0.853-1.159 0.019-0.623 0.019-1.226 0.019-1.734s-0.048-2.913-0.019-5.432c0.029-2.098 0.086-4.206 0.192-6.304 0.010-0.134 0.115-0.24 0.249-0.24 0.92-0.029 1.849-0.048 2.778-0.058 1.341-0.019 2.683-0.019 4.024-0.010s2.683 0.029 4.024 0.058c0.987 0.019 1.983 0.048 2.96 0.086 0.153 0.010 0.268 0.134 0.268 0.287-0.010 0.901-0.019 3.612-0.019 3.612 0 0.546 0.010 1.083 0.019 1.629v0.010c0.010 0.546 0.45 0.987 0.996 0.987l1.705 0.019h1.705c0.441 0 1.428-0.019 1.926-0.029 0.153 0 0.287 0.125 0.297 0.278 0.048 1.399 0.067 2.807 0.077 4.216 0.010 1.878 0 3.756-0.029 5.634s-0.077 3.756-0.153 5.624c-0.067 1.514-0.144 3.037-0.268 4.532-0.019 0.201-0.182 0.355-0.383 0.364-1.418 0.038-2.778 0.067-4.302 0.077-1.648 0.010-6.266 0.010-8.163 0-1.964-0.010-5.365-0.029-7.042-0.086-0.125 0-0.24-0.153-0.259-0.278-0.058-0.374-0.105-0.834-0.163-1.389-0.067-0.623-0.469-1.092-1.035-1.025-0.45 0.048-0.824 0.45-0.891 1.198-0.067 0.738-0.019 1.619 0.067 2.213s0.441 1.016 1.198 1.14c1.006 0.163 5.72 0.249 8.057 0.268 2.347 0.019 6.275-0.019 8.259-0.019 1.974-0.010 3.286-0.019 4.857-0.182 1.121-0.115 1.408-0.747 1.552-1.715 0.24-1.667 0.345-3.325 0.469-4.982 0.134-1.887 0.24-3.775 0.326-5.672 0.086-1.887 0.144-3.784 0.192-5.672 0.029-1.428 0.038-3.21 0.019-4.235-0.010-0.948-0.287-1.782-0.862-2.472zM19.832 7.023c-0.019-0.537-0.077-2.060-0.096-2.692 0-0.096 0.105-0.144 0.182-0.086 0.537 0.489 2.491 2.271 3.152 2.874 0.077 0.067 0.029 0.192-0.077 0.182-0.719-0.029-2.434-0.086-2.98-0.105-0.096 0.010-0.182-0.077-0.182-0.172z"></path>
                                        <path d="M18.405 25.61l2.050-6.189c0.029-0.086 0.048-0.182 0.048-0.268 0-0.45-0.383-0.843-0.881-0.843h-18.74c-0.24 0-0.46 0.096-0.623 0.249s-0.259 0.364-0.259 0.604v6.189c0 0.23 0.096 0.441 0.259 0.594s0.383 0.249 0.623 0.249h16.69c0.374-0.010 0.709-0.24 0.834-0.584zM22.41 11.89c0.019-0.383-0.278-0.719-0.671-0.738-1.284-0.067-2.568-0.096-3.842-0.115-0.642-0.010-1.284-0.029-1.926-0.029l-1.926-0.010-1.926 0.010-1.926 0.029c-1.284 0.019-2.568 0.038-3.842 0.086-0.374 0.019-0.69 0.316-0.699 0.699-0.010 0.402 0.297 0.738 0.699 0.757 1.284 0.048 2.568 0.067 3.842 0.086l1.926 0.019 1.926 0.010 1.926-0.010c0.642 0 1.284-0.019 1.926-0.029 1.284-0.019 2.568-0.048 3.842-0.115 0.364-0.010 0.651-0.287 0.671-0.652zM15.875 14.63c-0.527-0.010-1.054-0.029-1.581-0.029l-1.59-0.010-1.581 0.010-1.59 0.029c-1.054 0.019-2.117 0.038-3.171 0.086-0.374 0.019-0.68 0.316-0.69 0.699-0.019 0.402 0.297 0.738 0.69 0.757 1.054 0.048 2.117 0.067 3.171 0.086l1.59 0.029 1.581 0.010 1.59-0.010c0.527 0 1.054-0.019 1.581-0.029 1.054-0.019 2.117-0.048 3.171-0.115 0.345-0.019 0.632-0.297 0.661-0.661 0.019-0.383-0.268-0.719-0.661-0.738-1.054-0.057-2.117-0.086-3.171-0.115z"></path>
                                    </svg>  
                                    <input type="file" multiple hidden @change="addAttachment" />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div @mousedown.prevent.stop @click="sendComment" class="sendAreaBox app-comment-send-area" style="display:flex;bottom:6px;">
                    <div style="display:flex;position:relative;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="33" viewBox="0 0 43 32" style="margin:auto;fill: var(--third-color);">
                            <path d="M40.638.087c-1.842.361-6.097 1.292-9.435 2.047L1.157 9.025c-.419.096-.793.374-1.003.793-.364.728-.058 1.585.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56.287.157.487.439.542.762 0 0 .711 4.473.921 5.891.21 1.417.714 4.465 1.184 6.482.168.726.631 1.335 1.215 1.512.495.152 1.03.037 1.43-.285 1.394-1.128 5.787-5.445 7.388-7.272.133-.152.355-.19.531-.085l6.184 3.646s.439.294.919.519c1.283.601 2.479.625 3.062-.829.325-.813 4.316-12.627 4.316-12.627l4.466-13.209c.053-.152.082-.321.082-.492 0-.844-.654-1.675-2.496-1.312zM20.045 24.741c-.475.477-1.473 1.473-2.284 2.197-.155.137-.385-.002-.313-.195l1.796-4.842c.051-.157.236-.226.378-.142l1.796 1.054c.157.091.161.294.041.432-.401.458-.975 1.058-1.413 1.495zM32.151 25.117c-.106.325-.482.47-.777.301l-1.447-.824-3.554-2.014-7.121-4.024c-.067-.037-.138-.068-.214-.094-.677-.232-1.411.13-1.64.808l-1.944 7.086c-.053.166-.229.143-.251-.046-.13-1.23-.328-3.178-.467-4.759-.13-1.459-.366-3.357-.494-4.434-.111-.931-.427-1.423-1.131-1.837-.704-.415-6.489-3.354-7.668-4.049-.241-.142-.166-.415.065-.463 0 0 13.334-2.689 16.022-3.304 2.689-.617 10.513-2.447 10.513-2.447.103-.025.152.118.056.161l-5.127 2.281-2.961 1.459c-.987.487-7.32 3.516-9.259 4.562-.477.258-.665.871-.373 1.36.255.429.808.574 1.265.374 2.004-.882 16.208-7.766 17.651-8.441.345-.162.376-.012.287.049-.89.615-9.43 6.896-10.25 7.528l-2.448 1.905c-.432.342-.519.976-.173 1.42.335.432.965.497 1.413.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66s5.775-4.365 6.187-4.682c.166-.128.397.033.331.234l-2.517 7.675-3.585 10.965z"></path>
                        </svg>
                    </div>
                    <div v-if="sending" class="z-[10] absolute bg-[var(--background-color)] w-full h-full left-0 top-0 flex items-center justify-center pointer-events-none">
                        <div class="spinner-micro" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import { useFilePreview } from '@/store/filePreview';
import { AppComment } from '@/interface/appComment';
import { CommonFile, MessageFile, User } from '@/interface/globalInterface';
import { DateParser, mentionFormatter } from '@/utils/tools';
import UserPanel from '@/components/Global/UserPanel.vue';
import MentionBox from '@/components/Board/Message/MentionBox.vue';
import FileIcon from '@/components/Board/Mixed/FileIcon.vue';

const props = withDefaults(defineProps<{
    commentableType: string;
    commentableId: number;
    title?: string;
    users?: User[];
}>(), {
    title: 'コメント',
})

const emit = defineEmits<{
    countChanged: [count: number]
}>()

const api = useApi()
const auth = useAuthUserStore()
const filePreview = useFilePreview()
const editor = useTemplateRef<HTMLElement>('editor')
const commentContainer = useTemplateRef<HTMLElement>('commentContainer')
const comments = ref<AppComment[]>([])
const uploadedFiles = ref<MessageFile[]>([])
const mentionableUsers = ref<User[]>([])
const mentionBox = useTemplateRef<any>('mentionBox')
const mentionBoxOpen = ref(false)
const mentionBoxForced = ref(false)
const mentionKeyword = ref('')
const caretPosition = ref(0)
const loading = ref(false)
const sending = ref(false)

const filteredMentionableUsers = computed(() => {
    const keyword = mentionKeyword.value.replace(/[@＠]/g, '').toLowerCase()
    const users = mentionableUsers.value.filter(user => user.id !== auth.activeUser.id)

    if (!keyword) return users

    return users.filter(user => (user.name ?? '').toLowerCase().includes(keyword))
})

onMounted(async () => {
    await Promise.all([fetchComments(), fetchMentionableUsers()])
})

watch(
    () => [props.commentableType, props.commentableId],
    () => fetchComments(),
)
watch(
    () => props.users,
    () => fetchMentionableUsers(),
)

const fetchComments = async () => {
    loading.value = true
    try {
        const data = await api.get('/app_comments', {
            type: props.commentableType,
            id: props.commentableId,
        })
        comments.value = data ?? []
        emit('countChanged', comments.value.length)
        scrollToBottom('instant')
    } finally {
        loading.value = false
    }
}

const fetchMentionableUsers = async () => {
    if(props.users === undefined) {
        mentionableUsers.value = await api.get('/app_comment_mentionable_users', null, { silent: true }) ?? []
    } else {
        mentionableUsers.value = props.users
    }
}

const sendComment = async () => {
    const content = editor.value?.textContent?.trim() ?? ''
    if (!content || sending.value) return

    sending.value = true
    try {
        const data = await api.post('/app_comments', {
            type: props.commentableType,
            id: props.commentableId,
            content,
            attached_temp_files: uploadedFiles.value,
        })

        if (data) {
            comments.value.push(data)
            emit('countChanged', comments.value.length)
            if (editor.value) editor.value.textContent = ''
            uploadedFiles.value = []
            closeMentionBox()
            scrollToBottom()
        }
    } finally {
        sending.value = false
    }
}

const addAttachment = async (event: Event) => {
    const target = event.target as HTMLInputElement
    if (!target.files?.length) return

    const formData = new FormData()
    Array.from(target.files).forEach((file, index) => formData.append(String(index), file))
    const files = await api.post('/attach_upload_api', formData)
    uploadedFiles.value.push(...(files ?? []))
    target.value = ''
}

const removeAttachment = async (file: MessageFile) => {
    uploadedFiles.value = uploadedFiles.value.filter(item => item.id !== file.id)
    await api.post('/remove_temp_file', { id: file.id }, { silent: true })
}

const previewFile = (comment: AppComment, file: MessageFile, index: number) => {
    const files = (comment.files ?? []).map(fileData => ({
        ...fileData,
        file_path: filePath(comment, fileData),
        doc_path: filePath(comment, fileData),
    }))

    filePreview.setFilePreview({
        active: true,
        files,
        target: file,
        source: 'message',
        index,
        message: null,
    })
}

const previewTempFile = (file: MessageFile) => {
    filePreview.setFilePreview({
        active: true,
        files: [{
            ...file,
            file_path: `/cdn/temp_upload/${file.id}.${file.extension}`,
            doc_path: `/temp_upload/${file.id}.${file.extension}`,
        }],
        target: file,
        source: 'message',
        index: 0,
        message: null,
    })
}

const filePath = (comment: AppComment, file: CommonFile) => {
    return `/cdn/app_comment_files/${comment.id}/${file.id}_${file.user_id}.${file.extension}`
}

const formatDate = (date?: string | null) => {
    if (!date) return ''
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/M/d HH:mm') : date
}

const handleEnter = (event: KeyboardEvent) => {
    const highlighted = mentionBox.value?.highlighted ?? -1
    if (mentionBoxOpen.value && highlighted > -1) {
        const user = filteredMentionableUsers.value[highlighted]
        if (user) insertMention(user)
        event.preventDefault()
        return
    }

    if (event.altKey || event.metaKey) {
        event.preventDefault()
        sendComment()
    }
}

const handleEditorKeyup = () => {
    rememberCaret()
    const text = editor.value?.textContent ?? ''
    const beforeCaret = text.slice(0, caretPosition.value)
    const match = beforeCaret.match(/[@＠]([^@＠\s]*)$/)

    mentionBoxOpen.value = !!match
    mentionKeyword.value = match?.[1] ?? ''
}

const handleComposition = (event: CompositionEvent) => {
    if (event.data === '@' || event.data === '＠') {
        mentionBoxOpen.value = true
        mentionKeyword.value = ''
    }
}

const rememberCaret = () => {
    const selection = window.getSelection()
    if (!selection || !editor.value || selection.rangeCount === 0) return

    const range = selection.getRangeAt(0).cloneRange()
    range.selectNodeContents(editor.value)
    range.setEnd(selection.getRangeAt(0).endContainer, selection.getRangeAt(0).endOffset)
    caretPosition.value = range.toString().length
}

const openForcedMention = () => {
    rememberCaret()
    mentionBoxForced.value = true
    mentionBoxOpen.value = false
    mentionKeyword.value = ''
}

const closeMentionBox = () => {
    mentionBoxOpen.value = false
    mentionBoxForced.value = false
    mentionKeyword.value = ''
}

const insertMention = (user: User) => {
    if (!editor.value) return

    const text = editor.value.textContent ?? ''
    const mentionSyntax = `[To:${user.name}:]`
    const beforeCaret = text.slice(0, caretPosition.value)
    const match = beforeCaret.match(/[@＠]([^@＠\s]*)$/)
    const start = mentionBoxForced.value || !match ? caretPosition.value : caretPosition.value - match[0].length
    const nextText = `${text.slice(0, start)}${mentionSyntax}${text.slice(caretPosition.value)}`

    editor.value.textContent = nextText
    closeMentionBox()
    setCaret(start + mentionSyntax.length)
}

const setCaret = async (position: number) => {
    await nextTick()
    if (!editor.value?.firstChild) return

    const textNode = editor.value.firstChild
    const range = document.createRange()
    range.setStart(textNode, Math.min(position, textNode.textContent?.length ?? 0))
    range.collapse(true)
    const selection = window.getSelection()
    selection?.removeAllRanges()
    selection?.addRange(range)
    caretPosition.value = position
}

const scrollToBottom = async (behavior: ScrollBehavior = 'smooth') => {
    await nextTick()
    commentContainer.value?.scrollTo({
        top: commentContainer.value.scrollHeight,
        behavior,
    })
}
</script>

<style scoped lang="scss">
.app-comment-section{
    border-top: 1px solid var(--calendarBorder);
    padding-top: 18px;
}

.app-comment-header{
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;

    h3{
        font-size: 14px;
        font-weight: 700;
    }

    span{
        color: gray;
        font-size: 12px;
    }
}

.app-comment-list{
    background: var(--bg2);
    max-height: 42vh;
    overflow: auto;
    padding: 24px 18px;
}

.app-comment-empty{
    color: gray;
    font-size: 13px;
    text-align: center;
}

.app-comment-item{
    background: var(--message-background);
    margin-bottom: 22px;
    max-width: 78%;
    min-width: 34%;
    padding: 14px;

    &.is-mine{
        margin-left: auto;
    }
}

.app-comment-meta{
    align-items: center;
    display: flex;
    gap: 16px;
    justify-content: space-between;

    span{
        color: gray;
        font-size: 11px;
        white-space: nowrap;
    }
}

.app-comment-body{
    font-size: 13px;
    line-height: 1.7;
    margin-top: 12px;
    white-space: break-spaces;
}

.app-comment-files,
.app-comment-uploaded-files{
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.app-comment-file,
.app-comment-uploaded-file{
    align-items: center;
    background: var(--bg3);
    border: 1px solid var(--calendarBorder);
    display: inline-flex;
    gap: 8px;
    max-width: 220px;
    min-height: 38px;
    padding: 6px 8px;
    text-align: left;

    img{
        max-height: 30px;
        max-width: 42px;
    }

    span{
        font-size: 12px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.app-comment-uploaded-file button{
    background: inherit;
    color: gray;
}

.app-comment-compose{
    position: relative;
}

.app-comment-type-container{
    margin: 10px -10px;
    width: calc(100% + 20px);
}

.app-comment-editor{
    min-height: 78px;
}

.app-comment-send-area{
    cursor: pointer;
}

</style>
