<template>
    <div 
        ref="editor"
        style="font-size: 14px;line-height: 1.5;white-space: break-spaces;outline: none;word-break: break-word;display: inline-block;width: -webkit-fill-available;" 
        v-html="message.message"
        contentEditable="plaintext-only">
    </div>
    <Transition name="slidePop">   
    <div v-if="active" style="position: absolute;bottom: -40px;left: 0;">
        <div style="white-space: nowrap;" class="reset-bullet">
            <div @click="update" :style="{opacity: sending ? '0.5': '1'}" class="commentEditButton">保存</div>
            <div @click="emit('cancel')" class="commentEditButton">キャンセル</div>
        </div>
    </div>
    </Transition>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { BoardMethodsKey, BoardMethods } from '@/interface/keys';
import { inject, onMounted, ref, useTemplateRef } from 'vue';
    
    
    const props = defineProps(['message'])
    const emit = defineEmits(['cancel'])
    const editor = useTemplateRef('editor')
    const active = ref(false)
    const sending = ref(false)
    const api = useApi()
    onMounted(() => {
        editor.value?.focus()
        const range = document.createRange();
        const node = editor.value as Node
        range.selectNodeContents(node);
        range.collapse(false);
        const selection = window.getSelection();
        selection?.removeAllRanges();
        selection?.addRange(range);
        active.value = true
    })
    const { refreshMessages } = inject(BoardMethodsKey) as BoardMethods 
    const update = async() => {
        const new_text = editor.value?.textContent;
        if(sending.value) return

        sending.value = true          
        const data = await api.post('/chat_edit_api', {id: props.message.id, message: new_text }, {
            toast: '保存しました。'
        })
        refreshMessages(data)
        emit('cancel') 

        sending.value = false
          
        
    }
    </script>