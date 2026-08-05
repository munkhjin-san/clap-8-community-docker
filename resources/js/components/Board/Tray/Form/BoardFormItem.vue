<template>
    <div class="p-[10px] bg-[var(--message-background)] flex flex-col gap-[10px] text-[14px] text-[var(--primary-color)]">
        <div class="flex justify-between">
            <p class="leading-normal">{{ form.title }}</p>
            <ItemMenu v-if="isAdmin" :items="[
                {title: '回答の確認', action: () => { emit('setViewAnswers', form) }},
                {title: '編集', action: () => { emit('edit', form) }},
                {title: '削除', action: () => { emit('delete', form) }},
                {title: '再利用', action: () => { emit('duplicate', form) }},
            ]"/>
        </div>
        
        <div class="flex cursor-pointer" @click="emit('setViewUsers', form)">
            <div class="text-[12px]">該当者（{{form.users?.length ?? 0}}）</div>
        </div>
        <div v-if="isTarget">
            <CommandButton :buttons="[
                {title: 'フォームを回答', action: () => { emit('fill', form) } },
            ]"/>
        </div>
    </div>
</template>
<script setup lang="ts">
import CommandButton from '@/components/Global/CommandButton.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { CustomForm } from '@/interface/customFormInterface';
import { useAuthUserStore } from '@/store/auth';
import { computed } from 'vue';

const props = defineProps<{
    form: CustomForm
}>()


const emit = defineEmits<{
    'setViewUsers': [form: CustomForm]
    'fill': [form: CustomForm]
    'edit': [form: CustomForm]
    'delete': [form: CustomForm]
    'duplicate': [form: CustomForm]
    'setViewAnswers': [form: CustomForm]
}>()

const auth = useAuthUserStore()
const isTarget = computed(() => {
    return props.form.users?.some(user => user.id === auth.activeUser.id) ?? false;
})

const isAdmin = computed(() => props.form.admins?.some(admin => admin.id === auth.activeUser.id) ?? false)
</script>