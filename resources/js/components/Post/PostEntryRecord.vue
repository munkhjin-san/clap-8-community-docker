<template>
<div class="flex flex-col gap-2 p-4 bg-[var(--message-background)]">
    <div class="flex gap-2 items-center">
        <UserPanel :user="entry.user" with-name/>
        <div class="ml-[10px] text-sm">🔥 {{ amountOfMoneyParser(entry.calories) }} kcal</div>  
         <div @click="expand = !expand" class="jump-link ml-auto">詳細</div>
    </div>
    <div v-if="expand" class="flex flex-col gap-2.5">
        <div class="text-xs text-[gray]">作成日：{{ DateParser(entry.created_at) }}</div>
        <div class="text-[14px] whitespace-break-spaces leading-normal">{{ entry.comment }}</div>
        <div>
            <PostEntryFiles :items="entry.files" />
        </div>
    </div>
     
</div>
</template>
<script setup lang="ts">
import { PostEntry } from '@/interface/postInterface';
import UserPanel from '../Global/UserPanel.vue';
import { amountOfMoneyParser, DateParser } from '@/utils/tools';
import { ref } from 'vue';
import PostEntryFiles from './PostEntryFiles.vue';

defineProps<{
    entry: PostEntry
}>()

const expand = ref(false);

</script>