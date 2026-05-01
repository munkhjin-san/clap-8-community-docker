<template>
    <div class="mx-[20px]">
        
        <table id="customers">
            <thead>
                <tr class="sticky top-[-1px] z-[1]">
                    <th scope="col">氏名</th>
                    <th scope="col">会社名</th>
                    <th scope="col">種類</th>
                    <th scope="col">住所</th>
                    <th scope="col">メールアドレス</th>
                    <th scope="col">電話番号</th>
                    <th scope="col">FAX</th>
                    <th scope="col">共同制作者</th>
                    <th scope="col" class="whitespace-nowrap">詳細</th>
                    <th scope="col" class="whitespace-nowrap">メモ</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="contact in contacts" :key="contact.id ?? contact.name">
                    <td class="whitespace-nowrap">{{contact.name}}</td>
                    <td>{{contact.company_name}}</td>
                    <td>{{contact?.type?.title || '未設定'}}</td>
                    <td>{{contact.address}}</td>
                    <td>{{contact.email}}</td>
                    <td>{{contact.phone}}</td>
                    <td>{{contact.fax}}</td>
                    <td>
                        <div class="flex items-center">
                            <div v-for="co in contact.collaborators" :key="co.id">
                                <UserPanel :user="co" :size="15" />
                            </div>
                        </div>
                        
                    </td>
                    <td class="">
                        <div class="flex whitespace-nowrap gap-1 items-center">
                            <router-link :to="{name: 'contactDetail', params: {contactId: contact.id}}">詳細</router-link>
                            <span v-if="badge.contactBadge.some(c => c.contact_id === contact.id)" class="side-notification" style="position: static">
                                {{ badge.contactBadge.find(c => c.contact_id === contact.id)?.comments }}
                            </span>
                        </div>
                        
                    </td>
                    <td class="whitespace-nowrap">
                        <button
                            type="button"
                            class="jump-link !bg-inherit"
                            @click="emit('open-memo', contact)"
                            v-if="contact?.collaborators?.some(co => co.id === auth.id)"
                        >メモ</button>
                    </td>
                </tr>
            </tbody>
            
        </table>
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import { ContactRecord } from '@/interface/contactInterface';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
    const auth = useAuthUserStore()
    defineProps<{
        contacts: ContactRecord[]
    }>()
    const badge = useBadgeStore()
    const emit = defineEmits<{
        (e: 'open-memo', contact: ContactRecord): void;
    }>();
</script>

<style scoped>
#customers {
  
  border-collapse: collapse;
  width: 100%;
  font-size: 14px;
}

#customers td, #customers th {
  border: 1px solid var(--formBorder);
  padding: 8px;
  white-space: break-spaces;
  word-break: auto-phrase;
}

#customers tr{
    background-color:var(--background-color);
    color: var(--primary-color);
    line-height: 1.5;
}

#customers tr:hover {
    background-color: var(--bg3);
    
}

#customers th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: gray;
    color: white;
    font-weight: normal;
}
</style>