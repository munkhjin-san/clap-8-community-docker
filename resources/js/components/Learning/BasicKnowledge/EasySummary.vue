<template>
    <div class="overlay">
        <div class="chatCreate scrollable">
            <div class="recordFormTitle" style="display:flex">
                <p>{{ material?.title }}</p>
                <div @click="emit('close')" class="m-close-button" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div v-for="item in summaries" :key="item.id">
                <div v-for="q in item.questions" style="margin-bottom: 20px;">
                    <!-- <div style="margin-bottom: 10px;">{{ q.question }}</div> -->
                    <div v-html="q.content"></div>
                    <div style="padding: 10px; background-color: var(--bg3); margin-top: 20px;">
                        <p><strong>内容を理解できたか？</strong></p>
                        <div v-for="answer in list" style="display: flex;align-items: center;padding: 5px 0;">
                            <input class="fish-eye" v-model="selectedAnswer[q.id]" type="radio" :id="`sum-${q.id}-${answer.value}`" :name="`answer-${q.id}`" :value="answer.value" >
                            <label style="margin-left:10px;cursor:pointer" :for="`sum-${q.id}-${answer.value}`">{{answer.content}}</label>
                        </div>
                        <span v-if="radioError[q.id]" class="form-error" style="font-size: 11px;color:tomato">{{ radioError[q.id] }}</span>
                    </div>
                </div>
                
                
            </div>
            <div class="si-box flex justify-center gap-[30px]">
                <div v-if="understandAll">
                    <LoaderButton @triggered="complete(2)" :content="'完了'"/>
                </div>
                <div v-else>
                    <LoaderButton @triggered="complete(-1)" :content="'個別フォローアップ申請'"/>
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <HasReason 
                v-if="reason"
                @close="reason = false"
                @update="update"
            />
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { Dialog } from '@/interface/globalInterface';
import { computed, inject, ref, watch } from 'vue';
import HasReason from './HasReason.vue';
const props = defineProps(['material', 'summaries'])

const emit = defineEmits(['close', 'updateAnswerStatus'])
const selectedAnswer = ref({})
const radioError = ref({})
const reason = ref(false)
const joined = ref('')
const { notify } = inject<Dialog>('dialog')! 
const list = [
    { value: 2, content: '理解した'},
    { value: 1, content: '理解できなかった'}        
]
const understandAll = computed(() => {
    return props.summaries.every((item: any) => item?.questions.every((q: any) => selectedAnswer.value[q.id] === 2))
})
const complete = async(status: number) => {
    const errors: { [key: string]: string } = {}
    const unansweredSummaries: string[] = []

    for (const item of props.summaries) {
        for (const q of item.questions) {
            if (!selectedAnswer.value[q.id]) {
                errors[q.id] = '必須です。'
            }
            if (selectedAnswer.value[q.id] === 1) {
                unansweredSummaries.push(q.question)
            }
        }
        
    }

    radioError.value = errors
    if (Object.keys(errors).length) return
    joined.value = unansweredSummaries.join('、 ')
    if (status === -1) {
        reason.value = true
        return
        // notify(`理解出来なかった内容について、\n法務から個別フォローアップのため後日ご連絡致します。`)
    }
    
    emit('updateAnswerStatus', status, joined)
}
const update = async(status: number, reason_dnt_und: string) => {
    reason.value = false
    emit('updateAnswerStatus', status, joined.value, reason_dnt_und)
}
watch(selectedAnswer, (newVal) => {
    for (const key in newVal) {
        if (newVal[key]) {
            delete radioError.value[key]
        }
    }
}, { deep: true })
</script>