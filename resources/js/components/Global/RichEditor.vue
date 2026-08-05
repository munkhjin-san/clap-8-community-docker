<template>
    <div class="editor-root" style="padding: 0;overflow: hidden auto;" @click="colorPickerView = null, filePickerView = false, activeFile = null">
        <div class="toolbar-root" v-if="editor">
            <button @click="editor.chain().focus().toggleBold().run()" :class="['toolbar-button', {'command-active': editor.isActive('bold')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="inherit"><path d="M8 11H12.5C13.8807 11 15 9.88071 15 8.5C15 7.11929 13.8807 6 12.5 6H8V11ZM18 15.5C18 17.9853 15.9853 20 13.5 20H6V4H12.5C14.9853 4 17 6.01472 17 8.5C17 9.70431 16.5269 10.7981 15.7564 11.6058C17.0979 12.3847 18 13.837 18 15.5ZM8 13V18H13.5C14.8807 18 16 16.8807 16 15.5C16 14.1193 14.8807 13 13.5 13H8Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleItalic().run()"  :class="['toolbar-button', {'command-active': editor.isActive('italic')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M15 20H7V18H9.92661L12.0425 6H9V4H17V6H14.0734L11.9575 18H15V20Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleUnderline().run()"  :class="['toolbar-button', {'command-active': editor.isActive('underline')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 3V12C8 14.2091 9.79086 16 12 16C14.2091 16 16 14.2091 16 12V3H18V12C18 15.3137 15.3137 18 12 18C8.68629 18 6 15.3137 6 12V3H8ZM4 20H20V22H4V20Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleStrike().run()" :class="['toolbar-button', {'command-active': editor.isActive('strike')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M13 9H11V6H5V4H19V6H13V9ZM13 15V20H11V15H13ZM3 11H21V13H3V11Z"></path></svg>
            </button>

            <button @click="setLink" :class="['toolbar-button', {'command-active': editor.isActive('link')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18.3638 15.5355L16.9496 14.1213L18.3638 12.7071C20.3164 10.7545 20.3164 7.58866 18.3638 5.63604C16.4112 3.68341 13.2453 3.68341 11.2927 5.63604L9.87849 7.05025L8.46428 5.63604L9.87849 4.22182C12.6122 1.48815 17.0443 1.48815 19.778 4.22182C22.5117 6.95549 22.5117 11.3876 19.778 14.1213L18.3638 15.5355ZM15.5353 18.364L14.1211 19.7782C11.3875 22.5118 6.95531 22.5118 4.22164 19.7782C1.48797 17.0445 1.48797 12.6123 4.22164 9.87868L5.63585 8.46446L7.05007 9.87868L5.63585 11.2929C3.68323 13.2455 3.68323 16.4113 5.63585 18.364C7.58847 20.3166 10.7543 20.3166 12.7069 18.364L14.1211 16.9497L15.5353 18.364ZM14.8282 7.75736L16.2425 9.17157L9.17139 16.2426L7.75717 14.8284L14.8282 7.75736Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="['toolbar-button', {'command-active': editor.isActive('heading', {level: 2})}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M4 4V11H11V4H13V20H11V13H4V20H2V4H4ZM18.5 8C20.5711 8 22.25 9.67893 22.25 11.75C22.25 12.6074 21.9623 13.3976 21.4781 14.0292L21.3302 14.2102L18.0343 18H22V20H15L14.9993 18.444L19.8207 12.8981C20.0881 12.5908 20.25 12.1893 20.25 11.75C20.25 10.7835 19.4665 10 18.5 10C17.5818 10 16.8288 10.7071 16.7558 11.6065L16.75 11.75H14.75C14.75 9.67893 16.4289 8 18.5 8Z"></path></svg>
            </button>
            <button @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="['toolbar-button', {'command-active': editor.isActive('heading', {level: 3})}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 8L21.9984 10L19.4934 12.883C21.0823 13.3184 22.25 14.7728 22.25 16.5C22.25 18.5711 20.5711 20.25 18.5 20.25C16.674 20.25 15.1528 18.9449 14.8184 17.2166L16.7821 16.8352C16.9384 17.6413 17.6481 18.25 18.5 18.25C19.4665 18.25 20.25 17.4665 20.25 16.5C20.25 15.5335 19.4665 14.75 18.5 14.75C18.214 14.75 17.944 14.8186 17.7056 14.9403L16.3992 13.3932L19.3484 10H15V8H22ZM4 4V11H11V4H13V20H11V13H4V20H2V4H4Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleOrderedList().run()" :class="['toolbar-button', {'command-active': editor.isActive('orderedList')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M5.75024 3.5H4.71733L3.25 3.89317V5.44582L4.25002 5.17782L4.25018 8.5H3V10H7V8.5H5.75024V3.5ZM10 4H21V6H10V4ZM10 11H21V13H10V11ZM10 18H21V20H10V18ZM2.875 15.625C2.875 14.4514 3.82639 13.5 5 13.5C6.17361 13.5 7.125 14.4514 7.125 15.625C7.125 16.1106 6.96183 16.5587 6.68747 16.9167L6.68271 16.9229L5.31587 18.5H7V20H3.00012L2.99959 18.8786L5.4717 16.035C5.5673 15.9252 5.625 15.7821 5.625 15.625C5.625 15.2798 5.34518 15 5 15C4.67378 15 4.40573 15.2501 4.37747 15.5688L4.3651 15.875H2.875V15.625Z"></path></svg>
            </button>
            <button @click="editor.chain().focus().toggleBulletList().run()" :class="['toolbar-button', {'command-active': editor.isActive('bulletList')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 4H21V6H8V4ZM3 3.5H6V6.5H3V3.5ZM3 10.5H6V13.5H3V10.5ZM3 17.5H6V20.5H3V17.5ZM8 11H21V13H8V11ZM8 18H21V20H8V18Z"></path></svg>
            </button>
            
            <div style="display: flex;position: relative;">
                <button @click.stop="colorPickerView = 55" :class="['toolbar-button', {'command-active': editor.isActive('textStyle', 'color')}]">
                    <svg style="color:#ff8787" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M5.55397 22H3.3999L10.9999 3H12.9999L20.5999 22H18.4458L16.0458 16H7.95397L5.55397 22ZM8.75397 14H15.2458L11.9999 5.88517L8.75397 14Z"></path></svg>
                </button>
                <button @click.stop="colorPickerView = 20" :class="['toolbar-button', {'command-active': editor.isActive('highlight')}]">
                    <svg style="background: #fcffa6;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M5.55397 22H3.3999L10.9999 3H12.9999L20.5999 22H18.4458L16.0458 16H7.95397L5.55397 22ZM8.75397 14H15.2458L11.9999 5.88517L8.75397 14Z"></path></svg>
                </button>
                <Transition name="slidePop">
                    <div 
                        id="ckpick" 
                        class="color-grid" 
                        v-if="colorPickerView"
                        :style="{left: `-${colorPickerView}px`}"
                        >              
                        <div class="my-[15px]">
                            <CommandButton :buttons="[{title: 'auto', action: () => {autoColor()}}]"/>     
                        </div>
                           
                        <div v-for="row in displayedColorShadesArray" :key="row[0]" class="color-row">
                            <div 
                            v-for="color in row"
                                :key="color"
                                class="color-item"
                                :style="{ backgroundColor: color }"
                                @click.stop="selectColor(color)"
                            ></div>
                        </div>
                        <button style="background: var(--bg2);width: fit-content;padding: 5px;margin-top: 10px;font-size: 12px;" @click="resetColor">リセット</button>
                    </div>
                </Transition>
                <Teleport to="body">
                <Transition name="modalFade">
                    <div v-if="filePickerView" class="file-picker-overlay" @click.stop="filePickerView = false">  
                        <div class="file-picker-modal" @click.stop>
                            <div class="file-picker-header">
                                <p>{{ filePickerTitle }}</p>
                                <CloseIcon size="13" class="cursor-pointer" @click.stop="filePickerView = false" />
                            </div>
                            
                            <div v-if="uploading" class="overlay" style="position: absolute;color: white;z-index: 10;"><strong>アップロード中</strong></div>
                            
                            <div class="file-picker-content">
                                <div class="file-table-wrapper">
                                    <table class="file-table">
                                        <thead>
                                            <tr>
                                                <th>名前</th>
                                                <th>拡張子</th>
                                                <th>作成日</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr 
                                                v-for="file in fileList" 
                                                :key="file.path" 
                                                @click.stop="activeFile = file"
                                                :class="{'file-row-active': activeFile?.path == file.path}"
                                                class="file-row"
                                            >
                                                <td class="file-name">{{ getFileName(file) }}</td>
                                                <td class="file-ext">{{ fileExtension(file) }}</td>
                                                <td class="file-date">{{ formatDate(file) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="file-preview">
                                    <div v-if="activeFile" class="preview-content">
                                        <h4>プレビュー</h4>
                                        <div class="preview-media">
                                            <img v-if="activeFileType === 'image'" :src="`/${activeFile.path}`" alt="Preview" />
                                            <video v-else-if="activeFileType === 'video'" :src="`/${activeFile.path}`" controls></video>
                                            <object
                                                v-else
                                                :data="`/${activeFile.path}`"
                                                type="application/pdf"
                                                width="100%"
                                                height="100%"
                                            >
                                                <p>PDFプレビューを表示できません。</p>
                                            </object>
                                        </div>
                                        <p class="preview-filename">{{ getFileName(activeFile) }}</p>
                                    </div>
                                    <div v-else class="preview-empty">
                                        <p>ファイルを選択してください</p>
                                    </div>
                                </div>
                            </div>
                            
                            <PostSearchPager 
                                v-if="totalPages > 1"
                                :possiblePage="totalPages"
                                :activePath="currentPage"
                                @setNavi="handlePageNavi"
                                @setActivePage="handleSetPage"
                            />
                            
                            <div class="file-picker-actions">
                                <input
                                    @click.stop
                                    @change.stop="uploadImage"
                                    ref="filePicker"
                                    type="file"
                                    style="display: none;"
                                    name="lessonFilePicker"
                                    :id="`lessonFilePicker-${activeFileType}`"
                                    :accept="filePickerAccept"
                                />
                                <button class="action-button" @click.stop="uploadStart">アップロード</button>
                                <button v-if="activeFile" class="action-button" @click.stop="applyFile()">適用</button>
                                <button v-if="activeFile" class="action-button danger" @click.stop="deleteFile()">削除</button>
                            </div>
                        </div>                       
                    </div>
                </Transition>
                </Teleport>
            </div>

            <button
                title="画像を追加"
                @click.stop="viewFilePicker('image')"
                :class="['toolbar-button', {'command-active': filePickerView && activeFileType === 'image'}]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M21 19V5H3V19H21ZM21 3C22.1046 3 23 3.89543 23 5V19C23 20.1046 22.1046 21 21 21H3C1.89543 21 1 20.1046 1 19V5C1 3.89543 1.89543 3 3 3H21ZM8.5 11.5L11 14.51L14.5 10L19 16H5L8.5 11.5ZM8 10C6.89543 10 6 9.10457 6 8C6 6.89543 6.89543 6 8 6C9.10457 6 10 6.89543 10 8C10 9.10457 9.10457 10 8 10Z"></path></svg>
            </button>
            <button
                title="動画を追加"
                @click.stop="viewFilePicker('video')"
                :class="['toolbar-button', {'command-active': filePickerView && activeFileType === 'video'}]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M17 10.5V6C17 5.44772 16.5523 5 16 5H4C3.44772 5 3 5.44772 3 6V18C3 18.5523 3.44772 19 4 19H16C16.5523 19 17 18.5523 17 18V13.5L21 17.5V6.5L17 10.5ZM15 17H5V7H15V17ZM7 9H13V11H7V9Z"></path></svg>
            </button>
            <button
                title="PDFを追加"
                @click.stop="viewFilePicker('pdf')"
                :class="['toolbar-button', {'command-active': filePickerView && activeFileType === 'pdf'}]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M3 8L9.00319 2H19.9978C20.5513 2 21 2.45531 21 2.9918V21.0082C21 21.556 20.5551 22 20.0066 22H3.9934C3.44476 22 3 21.5501 3 20.9932V8ZM10 4V9H5V20H19V4H10Z"></path></svg>
            </button>
            <button
                :title="`${otherThemeName}で確認`"
                @click.stop="previewOtherTheme = !previewOtherTheme"
                :class="['toolbar-button', {'command-active': previewOtherTheme}]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM12 20V4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z"></path></svg>
            </button>
        </div>
        <div
            class="editor-wrap"
            :class="{'editor-wrap-preview': previewOtherTheme}"
            :style="previewPalette"
        >
            <editor-content :editor="editor" />
        </div>
        <p class="theme-check-note">
            ※ 文字色とマーカーは選んだ色がそのまま保存されます。{{ otherThemeName }}での見え方も確認してください。
        </p>
    </div>
</template>
<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import { Color } from '@tiptap/extension-color'
import TextStyle from '@tiptap/extension-text-style'
import Highlight from '@tiptap/extension-highlight'
import Image from '@tiptap/extension-image'
import { computed, ref } from 'vue'
import CommandButton from './CommandButton.vue'
import PostSearchPager from '@/components/Post/PostSearchPager.vue'
import { useApi } from '@/composables/api'
import CloseIcon from '../Form/CloseIcon.vue'
import { useTheme } from '@/store/theme'
import theme from 'assets/theme.json'

const props = defineProps(['initilaValue'])
const emit = defineEmits(['content-updated'])
const editor = useEditor({
    content: props.initilaValue,
    extensions: [
        StarterKit,
        Underline,
        Link,
        TextStyle,
        Color,
        Highlight.configure({
            multicolor: true,
        }),
        Image
    ],
    onUpdate: ({ editor }) => {
        const html = editor.getHTML()
        emit('content-updated', html)
    }
})
interface FileItem {
    path: string
    name: string
    last_modified: number
}
type LessonFileType = 'image' | 'video' | 'pdf'

const colorPickerView = ref<number| null>(null)
const fileList = ref<FileItem[]>([])
const activeFile = ref<FileItem | null>(null)
const filePicker = ref<HTMLInputElement| null>(null)
const uploading = ref(false)
const activeFileType = ref<LessonFileType>('image')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)
const api = useApi()
defineExpose({editor})

// 文字色とマーカーは選んだ時点のHEXで保存されるため、書き手のテーマでしか
// 見え方を確認できない。編集領域だけ反対テーマの配色に差し替えて確認する。
const themeStore = useTheme()
const previewOtherTheme = ref(false)
const otherThemeName = computed(() => themeStore.dark ? 'ライトテーマ' : 'ダークテーマ')

// theme.json のカスタムプロパティを編集領域にだけ上書きする。
// カスタムプロパティは継承するので、配下の var(--…) がまとめて切り替わる。
const previewPalette = computed(() => {
    if (!previewOtherTheme.value) return {}

    return Object.fromEntries(
        theme.map(palette => [
            palette.className,
            themeStore.dark ? palette.light : palette.dark,
        ]),
    )
})
const baseColorShadesArray = [
  ['var(--primary-color)', '#666666', '#999999', '#cccccc', '#d9d9d9', '#f3f3f3', '#ffffff'],
  ['#980000', '#ff9900', '#ffff00', '#00ffff', '#4a86e8', '#9900ff', '#ff00ff'],
  ['#e6b8af', '#fce5cd', '#fff2cc', '#d0e0e3', '#c9daf8', '#d9d2e9', '#ead1dc'],
  ['#dd7e6b', '#f9cb9c', '#ffe599', '#a2c4c9', '#a4c2f4', '#b4a7d6', '#d5a6bd'],
  ['#cc4125', '#f6b26b', '#ffd966', '#76a5af', '#6d9eeb', '#8e7cc3', '#c27ba0'],
  ['#a61c00', '#e69138', '#f1c232', '#45818e', '#3c78d8', '#674ea7', '#a64d79'],
  ['#85200c', '#b45f06', '#bf9000', '#134f5c', '#1155cc', '#351c75', '#741b47'],
  ['#5b0f00', '#783f04', '#7f6000', '#0c343d', '#1c4587', '#20124d', '#4c1130']
]

const displayedColorShadesArray = computed(() => {
  const copied = baseColorShadesArray.map(row => [...row])

  if (colorPickerView.value === 55) {
    copied[0][0] = 'var(--primary-color)'
  } else if (colorPickerView.value === 20) {
    copied[0][0] = '#000000'
  }

  return copied
})

const uploadStart = () => {
    filePicker.value?.click()
}
const resetColor = () => {
    if(colorPickerView.value == 55){
        editor.value?.chain().focus().unsetColor().run()
    }else if(colorPickerView.value == 20){
        editor.value?.chain().focus().unsetHighlight().run()
    }
    colorPickerView.value = null
}
const autoColor = () => {
    if(colorPickerView.value == 55){
        editor.value?.chain().focus().setColor('var(--primary-color)').run()
    }else if(colorPickerView.value == 20){
        editor.value?.chain().focus().setHighlight({ color: 'var(--background-color)' }).run()
    }
    colorPickerView.value = null
}
const selectColor = (color: string) => {
    console.log(color)
    if(colorPickerView.value == 55){
        editor.value?.chain().focus().setColor(color).run()
    }else if(colorPickerView.value == 20){
        editor.value?.chain().focus().setHighlight({ color: color }).run()
    }
    colorPickerView.value = null
}
const getFileList = async() => {
    const response = await api.get(`/get_lesson_files?page=${currentPage.value}&per_page=${perPage.value}&type=${activeFileType.value}`)
    fileList.value = response.data
    totalPages.value = response.last_page
    currentPage.value = response.current_page
}

const filePickerTitle = computed(() => {
    const labels: Record<LessonFileType, string> = {
        image: '画像を選択',
        video: '動画を選択',
        pdf: 'PDFを選択',
    }

    return labels[activeFileType.value]
})

const filePickerAccept = computed(() => {
    const accept: Record<LessonFileType, string> = {
        image: 'image/*',
        video: 'video/*',
        pdf: 'application/pdf',
    }

    return accept[activeFileType.value]
})

const getFileName = (file: FileItem) => {
    return file.path.replace('lesson_files/', '')
}

const formatDate = (file: FileItem) => {
    const date = new Date(file.last_modified * 1000)
    return date.toLocaleString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const handlePageNavi = (direction: number) => {
    currentPage.value = Math.max(1, Math.min(totalPages.value, currentPage.value + direction))
    getFileList()
}

const handleSetPage = (page: number) => {
    currentPage.value = page
    getFileList()
}
const setLink = () => {
    const previousUrl = editor.value?.getAttributes('link').href
    const url = window.prompt('URL', previousUrl)
    if (url === null) {
    return
    }
    if (url === '') {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run()
    return
    }
    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}
const fileExtension = (file: FileItem | null) => {
    if (!file) return ''
    return (file.path.split('.').pop() || '').toLowerCase()
}
const uploadingProgress = ref(0)
const filePickerView = ref(false)
const uploadImage = async(event:Event) => {
    const target = event.target as HTMLInputElement
    const files = target.files
    if(files){
        const formData = new FormData()                   
      
        formData.append('file', files[0])
        formData.append('type', activeFileType.value)
    
        await api.post('/upload_lesson_file', formData , {
            loadingRef: uploading,
        }, { 
            onUploadProgress: (e) => uploadingProgress.value = e.total ? Math.floor((e.loaded * 100) / e.total) : 0 
        })       
        getFileList()         
    }
}   
const applyFile = () => {
    if (!activeFile.value) return
    if(activeFileType.value === 'image'){
        editor.value?.chain().focus().setImage({ src: `/${activeFile.value.path}` }).run()
    }else if (activeFileType.value === 'video'){
        editor.value?.chain().insertContentAt(editor.value.state.selection.anchor, `[[learning_video src="/${activeFile.value.path}" learning_video]]`).focus().run()
    } else {
        editor.value?.chain().insertContentAt(editor.value.state.selection.anchor, `[[learning_pdf src="/${activeFile.value.path}" learning_pdf]]`).focus().run()
    }
    filePickerView.value = false
    activeFile.value = null
}

const deleteFile = async() => {
    if (!activeFile.value) return
    await api.del(`/remove_lesson_file?path=${activeFile.value.path}`)  
    getFileList()
    activeFile.value = null  
}
const viewFilePicker = (type: LessonFileType) => {
    const shouldOpen = !filePickerView.value || activeFileType.value !== type

    activeFileType.value = type
    filePickerView.value = shouldOpen
    activeFile.value = null

    if(filePickerView.value){
        currentPage.value = 1
        getFileList()
    }
}
</script>
<style scoped>
.file-picker-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: var(--primary-color);
}

.file-picker-modal {
    background: var(--background-color);
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.file-picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--side-menu-border);
}

.file-picker-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.close-button {
    background: transparent;
    border: none;
    font-size: 28px;
    color: var(--primary-color);
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.close-button:hover {
    background: var(--bg2);
}

.file-picker-content {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    padding: 20px;
    overflow: hidden;
    flex: 1;
    min-height: 0;
    color: var(--primary-color);
}

.file-table-wrapper {
    overflow-y: auto;
    border: 1px solid var(--side-menu-border);
    border-radius: 4px;
}

.file-table {
    width: 100%;
    border-collapse: collapse;
}

.file-table thead {
    position: sticky;
    top: 0;
    background: var(--bg2);
    z-index: 1;
}

.file-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid var(--side-menu-border);
    font-size: 14px;
}

.file-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--side-menu-border);
    font-size: 13px;
}

.file-row {
    cursor: pointer;
    transition: background-color 0.2s;
}

.file-row:hover {
    background: var(--bg2);
}

.file-row-active {
    background: var(--bg3);
}

.file-name {
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-ext {
    text-transform: uppercase;
    font-weight: 500;
    color: var(--primary-color);
}

.file-date {
    color: #888;
    font-size: 12px;
}

.file-preview {
    border: 1px solid var(--side-menu-border);
    border-radius: 4px;
    padding: 15px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.preview-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.preview-content h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
    font-weight: 600;
}

.preview-media {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg2);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
    min-height: 200px;
}

.preview-media img,
.preview-media video {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.preview-media object {
    border: 0;
    min-height: 260px;
    width: 100%;
}

.preview-filename {
    font-size: 12px;
    color: #888;
    word-break: break-word;
    margin: 0;
}

.preview-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #888;
}

.file-picker-actions {
    display: flex;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid var(--side-menu-border);
}

.action-button {
    background: var(--bg2);
    color: var(--primary-color);
    border: 1px solid var(--side-menu-border);
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.action-button:hover {
    background: var(--bg3);
}

.action-button.danger {
    background: #dc2626;
    color: white;
    border-color: #dc2626;
}

.action-button.danger:hover {
    background: #b91c1c;
}

.overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.file-picker{
    position: absolute;
    background: var(--background-color);
    z-index: 5;
    width: 230px;
    top: 30px;
    box-shadow: 0 1px 2px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.07), 0 4px 8px rgba(0,0,0,.07), 0 8px 16px rgba(0,0,0,.07), 0 16px 32px rgba(0,0,0,.07), 0 32px 64px rgba(0,0,0,.07);
}
.file-grid{
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* Three columns with equal width */
    grid-auto-rows: 1fr; 
    padding: 10px;
    max-height: 150px;
    overflow: hidden auto;
}
.lesson-file-item{
    display: flex;
    align-items: center;
    justify-content: center;    
    border: solid thin transparent;
    padding: 5px;
}
.lesson-file-active{
    border: solid thin var(--primary-color);
}
.color-grid {
    top: 30px;
    display: flex;
    flex-direction: column;
    position: absolute;
    background: var(--background-color);
    z-index: 5;
    padding: 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.07), 0 4px 8px rgba(0,0,0,.07), 0 8px 16px rgba(0,0,0,.07), 0 16px 32px rgba(0,0,0,.07), 0 32px 64px rgba(0,0,0,.07);

}

.color-row {
  display: flex;
  gap: 5px;
}

.color-item {
  width: 15px;
  height: 15px;
  cursor: pointer;
}

.selected-color {
  width: 50px;
  height: 50px;
  margin-top: 10px;
  border-radius: 4px;
}
.tiptap{
    outline: none;
    min-height: 200px;
}
.editor-toolbar{
    padding: 10px;
    background: var(--bg2);
}
.editor-wrap{
    line-height: 1.5;
    padding: 15px;
}
/* 確認中だけ配色を固定する。var(--…) は :style で上書きした反対テーマの値を拾う */
.editor-wrap-preview{
    background: var(--background-color);
    color: var(--primary-color);
}
.theme-check-note{
    margin: 0;
    padding: 0 15px 12px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--sub-color);
}
.editor-wrap h1, h2, h3 {
    font-size: revert !important;
    font-weight: normal !important;
}
.ql-container {
    font-family: 'Noto Sans JP', sans-serif !important;
    font-size: 16px;
}
.ql-editor {
    min-height: 300px;
}
.toolbar-button{
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 35px;
    min-height: 35px;
    color: var(--primary-color);
    fill: var(--primary-color);
    background: var(--bg3);
}
.toolbar-button:hover {
    background: var(--bg2);
}
.command-active{
    background: var(--side-menu-border);
}
.toolbar-root{
    display: flex;
    flex-wrap: wrap;
    background: var(--bg3);
    position: relative;
    position: sticky;
    top: 0;
    z-index: 5;
}
.editor-root{
    border: 1px solid var(--primary-color);
}
</style>
