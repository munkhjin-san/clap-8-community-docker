<template>
<div 
    ref="editor"
    style="font-size: 14px;line-height: 2;white-space: break-spaces;outline: none;word-break: break-word;display: inline-block;width: -webkit-fill-available;" 
    v-html="urlCheck(comment.messages)"
    contentEditable="plaintext-only">
</div>
<Transition name="slidePop">   
<div v-if="active" style="position: absolute;bottom: -40px;left: 0;">
    <div style="white-space: nowrap;">
        <div @click="update" :style="{opacity: sending ? '0.5': '1'}" class="commentEditButton">保存</div>
        <div @click="emit('cancel')" class="commentEditButton">キャンセル</div>
    </div>
</div>
</Transition>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { PostComment } from '@/interface/postInterface';
import { urlCheck } from '@/utils/tools';
import { inject, onMounted, ref, useTemplateRef } from 'vue';

const props = defineProps<{
    comment: PostComment
}>()
const emit = defineEmits<{
    'cancel': []
}>()
const editor = useTemplateRef('editor')
const active = ref(false)
const sending = ref(false)
const api = useApi()
onMounted(() => {
    editor.value?.focus()
    const range = document.createRange();
    if(!editor.value) return
    range.selectNodeContents(editor.value);
    range.collapse(false);
    const selection = window.getSelection();
    if (selection) {
        selection.removeAllRanges();
        selection.addRange(range);
    }
    active.value = true
})
const reload = inject('reload') as Function
const update = async() => {
    if(!editor.value) return
    const new_text = editor.value.textContent;
    if(sending.value) return

    await api.post('post_comment_edit', {id: props.comment.id, message: new_text }, {
        toast: '保存しました。',
        loadingRef: sending
    })
    await reload()
    emit('cancel') 

               
    
}
</script>