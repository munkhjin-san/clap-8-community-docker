<template>
    <div class="max-w-[80%] inline-block p-[20px] leading-[1.8] m-[20px] bg-[var(--bg3)] text-[var(--primary-color)] text-[14px] under960:m-[10px] under960:p-[10px] under960:text-[12px]">                        
        <div v-html="body"></div>
        <p style="border-top: solid thin gray;" class="mt-[20px] pt-[20px]" v-if="item.keywords && item.keywords.length > 0">
            <strong>キーワード:</strong> {{ item.keywords.join('、') }}
        </p>
        <p class="mt-[20px]" v-if="item.source && item.source.length > 0">
            <strong>参考資料:</strong>
            <ul class="list-disc list-inside">
                <li v-for="(src, srcIndex) in item.source" :key="srcIndex">{{ src }}</li>
            </ul>
        </p>
    </div>
</template>
<script lang="ts" setup>
import { SupportConversationItem } from '@/interface/supportInterface';
import { computed } from 'vue';
import {marked} from 'marked'
import DOMPurify from 'dompurify';
const props = defineProps<{
    item: SupportConversationItem
}>()

const body = computed(() => {
    const message = props.item.message || '';
    const markedText = marked(message) as string;
    const saveText = DOMPurify.sanitize(markedText);
    return saveText;
})
</script>