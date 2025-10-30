<template>
    <div class="grid grid-cols-1 gap-[20px] sm959:grid-cols-3 mx-[20px] text-[var(--primary-color)]">
        <div class="p-[20px] bg-[var(--background-color)] flex flex-col gap-[15px]" v-for="contact in contacts" :key="contact.id ?? contact.company_name">
            <div class="flex gap-[15px] items-center">
                <ContactIcon :contact="contact"/>
                <p>{{ contact.name }}</p>
            </div>
            <div class="text-[gray] text-[13px]">{{ contact.company_name }}</div>
            <div class="text-[13px]">{{ contact?.type?.title }}</div>
            <div class="flex items-center">
                <span class="text-xs mr-2">共同制作者: </span>
                <div v-if="contact?.collaborators && contact?.collaborators.length" v-for="creator in contact.collaborators" :key="creator.id">
                    <UserPanel :disable-instant="true" :user="creator" :size="15"/>
                </div>
                <div v-else-if="contact.creator">
                    <UserPanel :disable-instant="true" :user="contact.creator" :size="15"/>
                </div>
            </div>
            <div class="flex gap-2 items-center">
                <router-link :to="{name: 'contactDetail', params: {contactId: contact.id}}">詳細</router-link>
                <span v-if="badge.contactBadge.some(b => b.contact_id === contact.id)" class="side-notification" style="position: static;">{{ badge.contactBadge.find(b => b.contact_id === contact.id).comments }}</span>      
                <button
                    type="button"
                    class="jump-link"
                    @click="openMemo(contact)"
                    v-if="contact?.collaborators?.some(co => co.id === auth.id)"
                >
                    <span class="text-base">メモ</span>
                    <!-- <svg
                        v-if="hasPrivateMemo(contact)"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        class="h-3.5 w-3.5 text-yellow-400"
                        aria-hidden="true"
                    >
                        <path
                            fill="currentColor"
                            d="M10.75 2.047a.75.75 0 0 0-1.5 0l-.26 2.604a4.62 4.62 0 0 1-3.84 4.094l-2.6.37a.75.75 0 0 0-.415 1.276l1.884 1.87a4.62 4.62 0 0 1 1.329 4.095l-.446 2.58a.75.75 0 0 0 1.088.792l2.317-1.217a4.62 4.62 0 0 1 4.301 0l2.317 1.217a.75.75 0 0 0 1.087-.792l-.445-2.58a4.62 4.62 0 0 1 1.329-4.095l1.884-1.87a.75.75 0 0 0-.414-1.277l-2.6-.37a4.62 4.62 0 0 1-3.84-4.094z"
                        />
                    </svg> -->
                </button>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ContactRecord } from '@/interface/contactInterface';
import ContactIcon from '../ContactIcon.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';

const props = defineProps<{
    contacts: ContactRecord[];
    viewerId: number | null;
}>();
const badge = useBadgeStore()
const auth = useAuthUserStore()
const emit = defineEmits<{
    (e: 'open-memo', contact: ContactRecord): void;
}>();

const hasPrivateMemo = (contact: ContactRecord) => {
    if (!props.viewerId) {
        return false;
    }
    return Boolean(
        contact?.collaborators?.some(
            collaborator =>
                collaborator.id === props.viewerId &&
                collaborator?.pivot?.private_memo &&
                collaborator.pivot.private_memo.trim().length > 0,
        ),
    );
};

const openMemo = (contact: ContactRecord) => {
    emit('open-memo', contact);
};
</script>

