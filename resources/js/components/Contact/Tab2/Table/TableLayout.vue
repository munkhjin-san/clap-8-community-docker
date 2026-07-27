<template>
    <div class="mx-[20px] border border-[var(--normalBorder)] overflow-hidden bg-[var(--message-background)]">
        <div class="overflow-x-auto">
            <table id="customers">
                <thead>
                    <tr class="sticky top-[-1px] z-[1]">
                        <th scope="col" class="whitespace-nowrap">氏名</th>
                        <th scope="col">会社名</th>
                        <th scope="col">部署</th>
                        <th scope="col">種類</th>
                        <th scope="col">メールアドレス</th>
                        <th scope="col">電話番号</th>
                        <th scope="col" class="whitespace-nowrap">登録日時</th>
                        <th scope="col">担当</th>
                        <th scope="col" class="whitespace-nowrap">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="contact in contacts"
                        :key="contact.id ?? contact.name"
                        class="cursor-pointer"
                        @click="router.push({ name: 'contactDetail', params: { contactId: contact.id } })"
                    >
                        <td class="whitespace-nowrap">
                            <div class="flex items-center gap-[10px] min-w-0">
                                <ContactIcon :contact="contact" size="28"/>
                                <span class="overflow-hidden text-ellipsis whitespace-nowrap">{{ contact.name }}</span>
                            </div>
                        </td>
                        <td>{{ contact.company_name || '—' }}</td>
                        <td>{{ contact.department || '—' }}</td>
                        <td>
                            <div class="flex gap-[5px] flex-wrap">
                                <span
                                    v-for="t in (contact.types ?? [])"
                                    :key="t.id ?? t.title"
                                    class="inline-flex items-center rounded-full px-[9px] py-[2px] text-[11.5px] font-medium bg-[var(--kebab-bg1)] text-[var(--primary-color)] whitespace-nowrap"
                                >{{ t.title }}</span>
                                <span v-if="!(contact.types ?? []).length" class="text-[gray]">未設定</span>
                            </div>
                        </td>
                        <td>{{ contact.email || '—' }}</td>
                        <td>{{ contact.phone || '—' }}</td>
                        <td class="whitespace-nowrap text-[12px]">{{ fmtDateTime(contact.created_at) }}</td>
                        <td>
                            <UserPanel v-if="ownerUser(contact)" :disable-instant="true" :user="ownerUser(contact)!" :size="22"/>
                            <span v-else>—</span>
                        </td>
                        <td class="whitespace-nowrap">
                            <div class="flex whitespace-nowrap gap-2 items-center">
                                <router-link :to="{ name: 'contactDetail', params: { contactId: contact.id } }" @click.stop>詳細</router-link>
                                <span v-if="badge.contactBadge.some(c => c.contact_id === contact.id)" class="side-notification" style="position: static">
                                    {{ badge.contactBadge.find(c => c.contact_id === contact.id)?.comments }}
                                </span>
                                <button
                                    v-if="isCollaborator(contact)"
                                    type="button"
                                    class="jump-link !bg-inherit"
                                    @click.stop="emit('open-memo', contact)"
                                >メモ</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import ContactIcon from '../ContactIcon.vue';
import { ContactRecord } from '@/interface/contactInterface';
import { User } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import { useRouter } from 'vue-router';

const router = useRouter()
const auth = useAuthUserStore()
defineProps<{
    contacts: ContactRecord[]
}>()
const badge = useBadgeStore()
const emit = defineEmits<{
    (e: 'open-memo', contact: ContactRecord): void;
}>();

const isCollaborator = (contact: ContactRecord) =>
    !!contact?.collaborators?.some(c => c.id === auth.id)

const ownerUser = (contact: ContactRecord): User | null => {
    const owner = contact.collaborators?.find(c => c.pivot?.role === 'owner')
    if (owner) return owner
    if (contact.creator) return contact.creator
    return contact.collaborators?.[0] ?? null
}

const fmtDateTime = (value: string) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    const pad = (n: number) => (n < 10 ? '0' + n : '' + n)
    return `${date.getFullYear()}/${pad(date.getMonth() + 1)}/${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}
</script>

<style scoped>
#customers {
  border-collapse: collapse;
  width: 100%;
  min-width: 1200px;
  font-size: 14px;
}

#customers td, #customers th {
  border-bottom: 1px solid var(--panel-separate);
  padding: 11px 14px;
  white-space: break-spaces;
  word-break: auto-phrase;
  vertical-align: middle;
}

#customers tr {
    background-color: var(--message-background);
    color: var(--primary-color);
    line-height: 1.5;
}

#customers tbody tr:hover {
    background-color: var(--bg3);
}

#customers th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: var(--bg3);
    color: var(--third-color);
    font-weight: 500;
    font-size: 12px;
}
/* Tailwind preflight is disabled app-wide, so border-width utilities need an
   explicit border-style to render. */
[class~="border"] { border-style: solid; }
[class*="border"] { box-sizing: border-box !important; }
</style>
