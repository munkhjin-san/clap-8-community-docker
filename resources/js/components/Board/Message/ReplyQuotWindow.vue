<template>
    <div style="background: #000000a8;
    color: #fff;
    font-size: 14px;
    display: flex;
    align-items: center;">
        <QuoteSelectionWindow 
            v-if="quoteReply.which == 'quot'&& !quoteReply.file && quoteReply.text && selectionView" 
            @closeMe="clearQuotReply"
            @completed="completed"
        />
        <p style="margin-left:15px;white-space: nowrap;line-height: 50px;">{{quoteReply.which == 'quot' ? '引用' : quoteReply.which == 'reply' ? '返信' : ''}}: </p>
        <div style="height:50px;display:flex;max-width: calc(100% - 130px);"> 
            
            <p style="margin-left:15px;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;line-height: 50px;">{{quoteText}}</p>
        </div>         
        <div style="display:flex; gap: 10px; align-items: center; padding:10px;margin-left:auto;">
            <div class="cursor-pointer" @click="replyWithAi" title="AI返事">                                           
                <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 30 30" width="20" height="20" fill="#fff" :class="[{'reply-rotate' : editing}]">
                    <path d="M 14.070312 2 C 11.330615 2 8.9844456 3.7162572 8.0390625 6.1269531 C 6.061324 6.3911222 4.2941948 7.5446684 3.2773438 9.3066406 C 1.9078196 11.678948 2.2198602 14.567816 3.8339844 16.591797 C 3.0745422 18.436097 3.1891418 20.543674 4.2050781 22.304688 C 5.5751778 24.677992 8.2359331 25.852135 10.796875 25.464844 C 12.014412 27.045167 13.895916 28 15.929688 28 C 18.669385 28 21.015554 26.283743 21.960938 23.873047 C 23.938676 23.608878 25.705805 22.455332 26.722656 20.693359 C 28.09218 18.321052 27.78014 15.432184 26.166016 13.408203 C 26.925458 11.563903 26.810858 9.4563257 25.794922 7.6953125 C 24.424822 5.3220082 21.764067 4.1478652 19.203125 4.5351562 C 17.985588 2.9548328 16.104084 2 14.070312 2 z M 14.070312 4 C 15.226446 4 16.310639 4.4546405 17.130859 5.2265625 C 17.068225 5.2600447 17.003357 5.2865019 16.941406 5.3222656 L 12.501953 7.8867188 C 12.039953 8.1527187 11.753953 8.6456875 11.751953 9.1796875 L 11.724609 15.146484 L 9.5898438 13.900391 L 9.5898438 8.4804688 C 9.5898438 6.0104687 11.600312 4 14.070312 4 z M 20.492188 6.4667969 C 21.927441 6.5689063 23.290625 7.3584375 24.0625 8.6953125 C 24.640485 9.696213 24.789458 10.862812 24.53125 11.958984 C 24.470201 11.920997 24.414287 11.878008 24.351562 11.841797 L 19.910156 9.2773438 C 19.448156 9.0113437 18.879016 9.0103906 18.416016 9.2753906 L 13.236328 12.236328 L 13.248047 9.765625 L 17.941406 7.0546875 C 18.743531 6.5915625 19.631035 6.4055313 20.492188 6.4667969 z M 7.5996094 8.2675781 C 7.5972783 8.3387539 7.5898438 8.4087418 7.5898438 8.4804688 L 7.5898438 13.607422 C 7.5898438 14.141422 7.8729844 14.635297 8.3339844 14.904297 L 13.488281 17.910156 L 11.34375 19.134766 L 6.6484375 16.425781 C 4.5094375 15.190781 3.7747656 12.443687 5.0097656 10.304688 C 5.5874162 9.3043657 6.522013 8.5923015 7.5996094 8.2675781 z M 18.65625 10.865234 L 23.351562 13.574219 C 25.490562 14.809219 26.225234 17.556313 24.990234 19.695312 C 24.412584 20.695634 23.477987 21.407698 22.400391 21.732422 C 22.402722 21.661246 22.410156 21.591258 22.410156 21.519531 L 22.410156 16.392578 C 22.410156 15.858578 22.127016 15.364703 21.666016 15.095703 L 16.511719 12.089844 L 18.65625 10.865234 z M 15.009766 12.947266 L 16.78125 13.980469 L 16.771484 16.035156 L 14.990234 17.052734 L 13.21875 16.017578 L 13.228516 13.964844 L 15.009766 12.947266 z M 18.275391 14.853516 L 20.410156 16.099609 L 20.410156 21.519531 C 20.410156 23.989531 18.399687 26 15.929688 26 C 14.773554 26 13.689361 25.54536 12.869141 24.773438 C 12.931775 24.739955 12.996643 24.713498 13.058594 24.677734 L 17.498047 22.113281 C 17.960047 21.847281 18.246047 21.354312 18.248047 20.820312 L 18.275391 14.853516 z M 16.763672 17.763672 L 16.751953 20.234375 L 12.058594 22.945312 C 9.9195938 24.180312 7.1725 23.443687 5.9375 21.304688 C 5.3595152 20.303787 5.2105423 19.137188 5.46875 18.041016 C 5.5297994 18.079003 5.5857129 18.121992 5.6484375 18.158203 L 10.089844 20.722656 C 10.551844 20.988656 11.120984 20.989609 11.583984 20.724609 L 16.763672 17.763672 z"/>
                </svg>
            </div> 
            <div class="cursor-pointer" @click="clearQuotReply">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="#fff" viewBox="0 0 32 32"  style="margin: auto;">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg> 
            </div>
        </div>
        
    </div>
</template>
<script setup>
import { computed, ref } from 'vue';
import QuoteSelectionWindow from '../Message/QuoteSelectionWindow.vue'
import { useQuoteReply } from '@/store/quoteReply';
import { useQuoteWindow } from '@/store/quoteWindow';
import OpenAI from 'openai';
    const quoteReply = useQuoteReply()   
    const quoteWindow = useQuoteWindow()
    const selectionView = ref(true)
    const editing = ref(false)
    const aiResponse = ref('')
    const emit = defineEmits(['replyText'])
    const hasMessage = computed(() => {
        return quoteReply.message.message && quoteReply.message.message.length
    })
    const quoteText = computed(() => {
        let text = '';
        if(hasMessage.value){
            text = quoteReply.text
        }else if(quoteReply.file){
            text = 'ファイルメッセージ'
        }
        const userName = quoteReply.message && quoteReply.message.user ? quoteReply.message.user.name : '非アクティブユーザー'
        return `“${userName} : ${text}”`
    })

    const clearQuotReply = () => {
        const quot_reply = {
            active: false,
            message: null,
            which: null,
            text: null,
            file: false,
            height: 100,
            width: 100
        }
        quoteReply.setQuoteReply(quot_reply)
        setTimeout(() => {
            quoteWindow.setQuoteWindow(false)
        }, 300);
        
    }
    const completed = (text) => {
        selectionView.value = false;
        setTimeout(() => {
            quoteWindow.setQuoteWindow(false)
        }, 300);
    }
    const replyWithAi = async() => {
        if(editing.value) return
        const text = quoteText.value
        if(text && text.length){
            try{
                editing.value = true
                aiResponse.value = ''
                const openai = new OpenAI({
                    apiKey: import.meta.env.VITE_OPENAI_API_KEY,
                    dangerouslyAllowBrowser: true 
                });       
                const assistant = await openai.beta.assistants.retrieve("asst_oT9xAfDpgLEnBI2ybY8YK5Ke");
                const thread = await openai.beta.threads.create();
                await openai.beta.threads.messages.create(thread.id, {role: "user", content: text});
                openai.beta.threads.runs.stream(thread.id, { assistant_id: assistant.id })
                .on('textDelta', (textDelta, snapshot) => {
                    const content = textDelta.value || ''
                    aiResponse.value = aiResponse.value + content
                    emit('replyText', aiResponse.value)
                }).on('end', () => {
                    editing.value = false
                })
            }catch(err){
                if (err instanceof OpenAI.APIError) {
                    console.log(err.status); 
                    console.log(err); 
                    if(err.status == 500){
                        notify('ChatGPT修正に失敗しました。<br>ChatGPTサーバーから反応がありませんでした。しばらく立ってから再度お試しください。')
                    }else{
                        notify('ChatGPT修正に失敗しました。<br>' + err.message)
                    }
                    
                } else {
                    notify('ChatGPT修正に失敗しました。<br>' + err)
                }
                editing.value = false
            }        
        }
    }
</script>