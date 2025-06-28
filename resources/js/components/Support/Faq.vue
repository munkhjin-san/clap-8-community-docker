<template>
<div class="support-content">
    <div class="support-title">よくある質問</div>
    <div class="support-content-inner">
        <div style="margin-bottom: 20px;">カテゴリーから選ぶ</div>
        <div class="support-tag-selector">
            <div @click="setText(item)" :class="['support-tag', {'tag-selected' : selectedTag == item.id}]" v-for="item in tagList">{{ item.text }}</div>
        </div>
    </div>
    <div class="support-content-inner" style="margin-top: 20px;padding: 0;">
        <div @click="selectedItem = item" v-for="item in qaList" class="qandaContent">
            <div><strong>Q : {{ item.question }}</strong></div>
            <div style="margin-top: 10px;">A : {{ item.answer }}</div>
        </div>
    </div>
    <Transition name="modalFade">
        <div class="overlay" v-if="selectedItem" @mousedown="reset">
            <div class="chatCreate scrollable" @mousedown.stop>
                <div class="recordFormTitle" style="display:flex">                        
                    <div class="cursor-pointer" @click="reset" style="position:unset; margin:auto 0 auto auto">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
                <div class="answerBox">
                    <div>Q : {{ selectedItem.question }}</div>
                    <div class="si-box">
                        <div style="margin-top: 10px;">A : {{ selectedItem.answer }}</div>
                    </div>
                    <div v-html="selectedItem.content" style="background: var(--bg3);padding: 15px;margin-top: 15px;white-space: normal;"></div>
                </div>
                <div class="si-box" style="display: flex;align-items: center;flex-direction: column;gap: 20px;">
                    <div><strong>問題は解決しましたか？</strong></div>
                    <div>
                        <div style="margin-right: 30px;" @click="feedBack(true)" class="commentEditButton">はい</div>
                        <div @click="feedBack(false)" class="commentEditButton">いいえ</div>
                    </div>
                </div>
                <div ref="advancedFeedBackRef" class="si-box" v-if="advancedFeedBack"> 
                    <p style="margin-bottom: 30px;">解決しなかった理由をお聞かせください。</p>
                    <LongInput
                        :initialValue="feedBackContent"   
                        ref="feedBackBody"
                        :placeHolder="`解決しなかった理由`"
                        uId="feedBackBody"
                        name="feedBackBody"
                        rules="required|max:2000"
                        label="タイトル"
                        v-model="feedBackContent"
                    />
                    <div class="si-box">
                        <LoaderButton content="送信する" @triggered="sendFeedBack" :loading="sending"/>
                    </div>
                </div>
                
            </div>
        </div>
    </Transition>
</div>
            

        

</template>
<script setup>
import { ref } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue'
import LongInput from '../Form/LongInput.vue';
import { useApi } from '@/composables/api';
    const props = defineProps(['qaList', 'tagList'])
    const emit = defineEmits(['setKeyWord'])
    const selectedTag = ref(0)
    const selectedItem = ref(null)
    const advancedFeedBack = ref(false)
    const advancedFeedBackRef = ref(null)
    const sending = ref(false)
    const feedBackContent = ref('')
    const api = useApi()
  
    const reset = () => {
        selectedItem.value = null
        advancedFeedBack.value = false
        sending.value = false
        feedBackContent.value = ''
    }
    const setText = (item) => {
        selectedTag.value = item.id
        const text = item.id == 0 ? '' : item.text
        emit('setKeyWord', text)
    }
    const feedBack = async(value) => {
        if(value == false){
            advancedFeedBack.value = true
            setTimeout(() => {
                advancedFeedBackRef.value?.scrollIntoView({ behavior: "smooth", block: "center"})
            }, 0);
        }else{
   
            await api.post('/support_resolve_decision', {id: selectedItem.value.id }, {
                toast: '送信しました。'
            })
            reset()

        }
    }
    const sendFeedBack = async() => {

        await api.post('/support_feedback', {
            consultation_content: feedBackContent.value,
            contact_address: null,
            kind_value: 99,
            id: selectedItem.value.id
        }, {
            toast: '送信しました。'
        })
        reset()

    }
</script>