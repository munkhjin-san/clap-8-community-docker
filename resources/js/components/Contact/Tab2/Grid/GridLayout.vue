<template>
    <div class="grid gap-[14px] mx-[20px] text-[var(--primary-color)]" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <div
            v-for="contact in contacts"
            :key="contact.id ?? contact.company_name"
            @click.stop="router.push({ name: 'contactDetail', params: { contactId: contact.id } })"
            class="p-[17px] bg-[var(--message-background)] border border-[var(--normalBorder)] flex flex-col gap-[13px] cursor-pointer transition-colors hover:border-[var(--formBorder)]"
        >
            <!-- Header -->
            <div class="flex items-start gap-[12px]">
                <ContactIcon :contact="contact"/>
                <div class="min-w-0 flex-1">
                    <div class="text-[16px] font-bold leading-[1.3] overflow-hidden text-ellipsis whitespace-nowrap">{{ contact.name }}</div>
                    <div class="text-[13px] text-[gray] mt-[3px] overflow-hidden text-ellipsis whitespace-nowrap">{{ contact.company_name || '—' }}</div>
                    <div v-if="contact.department" class="flex items-center gap-[5px] mt-[5px] text-[gray] text-[12px]">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l5-3 5 3v13M9 21v-4h2v4M9 11h.01M13 11h.01M9 14h.01M13 14h.01"/></svg>
                        {{ contact.department }}
                    </div>
                </div>
                <div class="flex items-center gap-[4px] shrink-0">
                    <button
                        v-if="isCollaborator(contact)"
                        @click.stop="openMemo(contact)"
                        title="非公開メモ"
                        class="w-[30px] h-[30px] flex items-center justify-center text-[gray] hover:bg-[var(--soft-bg)] hover:text-[var(--primary-color)] transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" height="15" viewBox="0 0 25.51 22.62" fill="currentColor">
                            <path d="M25.51,19.04c0-2.15-.06-11.41-.07-13.39,0-.42-.01-1.56-.01-1.96v-.33s0-.08,0-.08c0-.76-.27-1.51-.76-2.08-.61-.72-1.55-1.15-2.5-1.12-.4,0-1.56.02-1.96.01C14.8.14,8.69-.01,3.19,0,1.51.03.06,1.49.04,3.17c0,0,0,.68,0,.68C.03,8.16,0,13.93,0,18.22c0,.2,0,.79,0,.98-.08,1.79,1.38,3.37,3.17,3.42,4.88,0,13.66,0,18.49-.02.1,0,.4,0,.49,0,1.4.05,2.74-.89,3.18-2.23.15-.42.18-.89.17-1.32ZM23.3,18.72s0,.63,0,.63c0,.54-.48,1.01-1.02,1.02-4.07-.02-12.77-.03-16.94-.03h-1.31s-.65,0-.65,0h-.08c-.3,0-.58-.13-.78-.36-.19-.22-.26-.5-.24-.77,0-.19,0-.79,0-.98,0-4.29-.01-10.05-.04-14.37v-.63c0-.32.16-.63.42-.83.17-.13.38-.21.6-.22h1.27c5.06-.03,10.64-.08,15.67-.14v.04s1.31,0,1.31,0h.65c.13,0,.26.01.38.05.45.12.8.55.83,1.01-.01,3.3-.06,12.16-.08,15.57Z"/>
                            <path d="M5.26,7.51c2.46.11,5.05.16,7.51.16,2.47-.01,5.05-.04,7.51-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01-1.88-.13-3.76-.16-5.63-.19-3.09-.03-6.31.01-9.39.15-1.24.11-1.24,1.86,0,1.97Z"/>
                            <path d="M20.36,10.34c-1.89-.13-3.77-.16-5.66-.19-3.1-.03-6.35.01-9.44.15-1.24.11-1.24,1.86,0,1.97,2.47.11,5.07.16,7.55.16,2.49-.01,5.07-.04,7.55-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01Z"/>
                            <path d="M20.29,15.1c-1.88-.13-3.76-.16-5.64-.19-3.09-.03-6.31.01-9.39.15-1.24.11-1.24,1.86,0,1.97,2.46.11,5.05.16,7.52.16,2.48-.01,5.05-.04,7.52-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01Z"/>
                        </svg>
                    </button>
                    <button
                        v-if="isCollaborator(contact)"
                        @click.stop="router.push({ name: 'contactDetail', params: { contactId: contact.id }, query: { mention: 'true' } })"
                        title="公開コメント"
                        class="relative w-[30px] h-[30px] flex items-center justify-center text-[gray] hover:bg-[var(--soft-bg)] hover:text-[var(--primary-color)] transition-colors"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.38 8.38 0 0 1 4 11.5 8.5 8.5 0 0 1 12.5 3 8.38 8.38 0 0 1 21 11.5z"/></svg>
                        <span v-if="badge.contactBadge.some(b => b.contact_id === contact.id)" class="side-notification" style="position:absolute; top:-2px; right:-2px;">{{ badge.contactBadge.find(b => b.contact_id === contact.id)?.comments }}</span>
                    </button>
                    <button
                        v-if="actionTypes(contact).viewer"
                        @click.stop="follow(contact.id)"
                        title="フォロー"
                        class="w-[30px] h-[30px] flex items-center justify-center text-[gray] hover:bg-[var(--soft-bg)] hover:text-[var(--primary-color)] transition-colors"
                    >
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-4A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M19 8v5M21.5 10.5h-5"/></svg>
                    </button>
                    <div v-else-if="actionTypes(contact).follower" title="フォロー中" class="w-[30px] h-[30px] flex items-center justify-center text-[var(--primary-color)]">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-4A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M17 11l2 2 4-4"/></svg>
                    </div>
                </div>
            </div>

            <!-- Types -->
            <div class="flex items-center gap-[6px] flex-wrap">
                <span
                    v-for="t in (contact.types ?? [])"
                    :key="t.id ?? t.title"
                    class="inline-flex items-center rounded-full px-[11px] py-[4px] text-[12.5px] font-medium bg-[var(--kebab-bg1)] text-[var(--primary-color)] whitespace-nowrap"
                >{{ t.title }}</span>
                <span v-if="!(contact.types ?? []).length" class="inline-flex items-center rounded-full px-[11px] py-[4px] text-[12.5px] font-medium bg-[var(--kebab-bg1)] text-[gray] whitespace-nowrap">未設定</span>
            </div>

            <div class="h-px bg-[var(--panel-separate)]"></div>

            <!-- Footer: owner + reg date -->
            <div class="flex items-center justify-between gap-[10px]">
                <div class="flex items-center gap-[7px] min-w-0">
                    <UserPanel v-if="ownerUser(contact)" :disable-instant="true" :user="ownerUser(contact)!" :size="22"/>
                    <span class="text-[12.5px] text-[gray] overflow-hidden text-ellipsis whitespace-nowrap">{{ ownerUser(contact)?.name || '—' }}</span>
                </div>
                <span class="text-[11.5px] text-[gray] shrink-0">登録 {{ fmtDate(contact.created_at) }}</span>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ContactRecord } from '@/interface/contactInterface';
import { User } from '@/interface/globalInterface';
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

// Use the active-account id (same identity as actionTypes and the backend's
// active_user keying) so the memo/comment and follow buttons never disagree.
const isCollaborator = (contact: ContactRecord) => {
    const me = auth.activeUser?.id
    return !!me && !!contact?.collaborators?.some(c => c.id === me)
}

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

const ownerUser = (contact: ContactRecord): User | null => {
    const owner = contact.collaborators?.find(c => c.pivot?.role === 'owner')
    if (owner) return owner
    if (contact.creator) return contact.creator
    return contact.collaborators?.[0] ?? null
}

const fmtDate = (value: string) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleDateString('ja-JP')
}

const follow = async (id: number | null) => {
    if (!id) return
    const message = 'フォローすると、この連絡先に関する更新通知を受け取れます。\nコメントの投稿や個人メモの保存もできます。\nこのコンタクトをフォローしますか？'
    await api.post('/follow_contact', { record_id: id }, {
        ask: message,
        toast: 'コンタクトをフォローしました。',
    })
    emit('reload')
}
const openMemo = (contact: ContactRecord) => {
    emit('open-memo', contact);
};
</script>

<style scoped>
/* Tailwind preflight is disabled app-wide, so border-width utilities need an
   explicit border-style to render. */
[class~="border"],
[class~="border-2"] { border-style: solid; }
[class~="border-t"] { border-top-style: solid; }
[class~="border-b"] { border-bottom-style: solid; }
[class*="border"] { box-sizing: border-box !important; }
</style>
