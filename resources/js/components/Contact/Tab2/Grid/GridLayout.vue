<template>
    <div class="grid grid-cols-1 gap-[20px] sm959:grid-cols-3 mx-[20px] text-[var(--primary-color)]">
        <div class="p-[20px] bg-[var(--background-color)] flex flex-col gap-[15px]" v-for="contact in contacts">
            <div class="flex gap-[15px] items-center">
                <ContactIcon :contact="contact"/>
                <p>{{ contact.name }}</p>
            </div>
            <div class="text-[gray] text-[13px]">{{ contact.company_name }}</div>
            <div class="text-[13px]">{{ contact?.type?.title }}</div>
            <router-link :to="{name: 'contactDetail', params: {contactId: contact.id}}">詳細</router-link>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ContactRecord } from '@/interface/contactInterface';
import ContactIcon from '../ContactIcon.vue';

    const props = defineProps<{
        contacts: ContactRecord[]
    }>()

</script>