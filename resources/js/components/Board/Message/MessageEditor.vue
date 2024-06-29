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
<script setup>
    import { inject, onMounted, ref } from 'vue';
    
    
    const props = defineProps(['message'])
    const emit = defineEmits('cancel')
    const editor = ref(null)
    const active = ref(false)
    const sending = ref(false)
    onMounted(() => {
        editor.value.focus()
        const range = document.createRange();
        range.selectNodeContents(editor.value);
        range.collapse(false);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        active.value = true
    })
    const { refreshMessages } = inject('boardItem')  
    const { info, notify } = inject('dialog')
    const update = async() => {
        const new_text = editor.value.textContent;
        if(sending.value) return
        try{
            sending.value = true          
            await axios.post('/chat_edit_api', {id: props.message.id, message: new_text,})
            await refreshMessages()
            info('保存しました。')
            emit('cancel') 
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            sending.value = false
        }             
        
    }
    </script>