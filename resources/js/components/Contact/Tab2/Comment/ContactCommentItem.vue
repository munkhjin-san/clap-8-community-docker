<template>
    <div class="p-[15px] bg-[var(--message-background)] max-w-[80%] w-fit min-w-[40%]" :class="{'self-end': comment.user?.id == auth.activeUser.id}">
        <div class="flex justify-between gap-[20px]">
            <UserPanel :user="comment.user" with-name size="25"/>
            <p class="text-[gray] text-[12px]">{{DateParser(comment.created_at)}}</p>
        </div>
        <div class="whitespace-break-spaces leading-normal mt-[15px]" v-html="mentionFormatter(comment.comment, true)"></div>
        <div>
            <ContactCommentFiles v-if="comment.files && comment.files.length" :list="comment.files"/>
        </div>
    </div>    
</template>
<script lang="ts" setup>
import UserPanel from '@/components/Global/UserPanel.vue';
import { useAuthUserStore } from '@/store/auth';
import { DateParser, mentionFormatter } from '@/utils/tools';
import ContactCommentFiles from './ContactCommentFiles.vue';
import { ContactComment } from '@/interface/contactInterface';

const props = defineProps<{
    comment: ContactComment
}>();
const auth = useAuthUserStore()
</script>