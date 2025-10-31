<template>
    <div class="grid grid-cols-1 gap-[20px] sm959:grid-cols-3 mx-[20px] text-[var(--primary-color)]">
        <div @click.stop="router.push({ name: 'contactDetail', params: {contactId: contact.id} })" class="p-[20px] bg-[var(--background-color)] flex flex-col gap-[15px] cursor-pointer" v-for="contact in contacts" :key="contact.id ?? contact.company_name">
            <div class="flex gap-[15px] items-center">
                <ContactIcon :contact="contact"/>
                <p>{{ contact.name }}</p>
                <div v-if="actionTypes(contact).viewer" title="フォロー" class="ml-auto" @click.stop="follow(contact.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true" role="img">
                    <circle cx="9" cy="7" r="3"/>
                    <!-- shoulders centered on x=9 (start at 2, end at 16 gives radius 7) -->
                    <path d="M2 20a7 7 0 0 1 14 0"/>
                    <path d="M19 8v6M22 11h-6"/>
                    </svg>
                </div>
            </div>
            <div class="text-[gray] text-[13px]">{{ contact.company_name }}</div>
            <div class="text-[13px]">{{ contact?.type?.title }}</div>
            <div class="flex items-center">
                <span class="text-xs mr-2">関係者: </span>
                <div v-if="contact?.collaborators && contact?.collaborators.length" v-for="creator in contact.collaborators" :key="creator.id">
                    <UserPanel :disable-instant="true" :user="creator" :size="15"/>
                </div>
                <div v-else-if="contact.creator">
                    <UserPanel :disable-instant="true" :user="contact.creator" :size="15"/>
                </div>
            </div>
            <div class="flex gap-5 items-center justify-end">
                
                <div @click.stop="openMemo(contact)"
                    v-if="contact?.collaborators?.some(co => co.id === auth.id)"
                    title="非公開メモ"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="appIcon" height="15" viewBox="0 0 25.51 22.62">
                        <path d="M25.51,19.04c0-2.15-.06-11.41-.07-13.39,0-.42-.01-1.56-.01-1.96v-.33s0-.08,0-.08c0-.76-.27-1.51-.76-2.08-.61-.72-1.55-1.15-2.5-1.12-.4,0-1.56.02-1.96.01C14.8.14,8.69-.01,3.19,0,1.51.03.06,1.49.04,3.17c0,0,0,.68,0,.68C.03,8.16,0,13.93,0,18.22c0,.2,0,.79,0,.98-.08,1.79,1.38,3.37,3.17,3.42,4.88,0,13.66,0,18.49-.02.1,0,.4,0,.49,0,1.4.05,2.74-.89,3.18-2.23.15-.42.18-.89.17-1.32ZM23.3,18.72s0,.63,0,.63c0,.54-.48,1.01-1.02,1.02-4.07-.02-12.77-.03-16.94-.03h-1.31s-.65,0-.65,0h-.08c-.3,0-.58-.13-.78-.36-.19-.22-.26-.5-.24-.77,0-.19,0-.79,0-.98,0-4.29-.01-10.05-.04-14.37v-.63c0-.32.16-.63.42-.83.17-.13.38-.21.6-.22h1.27c5.06-.03,10.64-.08,15.67-.14v.04s1.31,0,1.31,0h.65c.13,0,.26.01.38.05.45.12.8.55.83,1.01-.01,3.3-.06,12.16-.08,15.57Z"/>
                        <path d="M5.26,7.51c2.46.11,5.05.16,7.51.16,2.47-.01,5.05-.04,7.51-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01-1.88-.13-3.76-.16-5.63-.19-3.09-.03-6.31.01-9.39.15-1.24.11-1.24,1.86,0,1.97Z"/>
                        <path d="M20.36,10.34c-1.89-.13-3.77-.16-5.66-.19-3.1-.03-6.35.01-9.44.15-1.24.11-1.24,1.86,0,1.97,2.47.11,5.07.16,7.55.16,2.49-.01,5.07-.04,7.55-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01Z"/>
                        <path d="M20.29,15.1c-1.88-.13-3.76-.16-5.64-.19-3.09-.03-6.31.01-9.39.15-1.24.11-1.24,1.86,0,1.97,2.46.11,5.05.16,7.52.16,2.48-.01,5.05-.04,7.52-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01Z"/>
                    </svg>
                </div>
                <div v-if="contact?.collaborators?.some(co => co.id === auth.id)" @click.stop="router.push({ name: 'contactDetail', params: {contactId: contact.id}, query: {mention: 'true'} })" class="flex items-center gap-2" title="公開コメント">
                    <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                        <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                    </svg>
                    <span v-if="contact.comments.length > 0" class="text-xs">{{ contact.comments.length }}</span>
                    <span v-if="badge.contactBadge.some(b => b.contact_id === contact.id)" class="side-notification" style="position: static;">{{ badge.contactBadge.find(b => b.contact_id === contact.id).comments }}</span>      
                </div>
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
import { useRouter } from 'vue-router';
import { useApi } from '@/composables/api';

const router = useRouter()
const props = defineProps<{
    contacts: ContactRecord[];
    viewerId: number | null;
}>();
const badge = useBadgeStore()
const auth = useAuthUserStore()
const emit = defineEmits<{
    (e: 'open-memo', contact: ContactRecord): void;
    (e: 'reload'): void
}>();
const api = useApi()
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
const actionTypes = (record: ContactRecord) => {
  const me = auth.activeUser?.id
  const collabs = record?.collaborators ?? []

  const mine = collabs.find(c => c.id === me)
  const role = mine?.pivot?.role ?? null // 'owner' | 'follower' | null

  const owner = role === 'owner'
  const follower = role === 'follower'
 
  const viewer = !owner && !follower

  return { owner, follower, viewer }
}
const follow = async(id: number | null) => {
    if (!id) return
    const message = 'フォローすると、この連絡先に関する更新通知を受け取れます。\nコメントの投稿や個人メモの保存もできます。\nこのコンタクトをフォローしますか？'
    await api.post('/follow_contact', {record_id: id}, {
        ask: message,
        toast: 'コンタクトをフォローしました。',
    })
    emit('reload')
}
const openMemo = (contact: ContactRecord) => {
    emit('open-memo', contact);
};
</script>

