<template>
    <div class="mx-[20px]">
        
        <table id="customers">
            <tr style="position: sticky;top: 0;">
                <th>氏名</th>
                <th>会社名</th>
                <th>種類</th>
                <th>住所</th>
                <th>メールアドレス</th>
                <th>電話番号</th>
                <th>FAX</th>
                <th class="whitespace-nowrap">詳細</th>
            </tr>
            <tr v-for="contact in contacts">
                <td class="whitespace-nowrap">{{contact.name}}</td>
                <td>{{contact.company_name}}</td>
                <td>{{contact?.type?.title || '未設定'}}</td>
                <td>{{contact.address}}</td>
                <td>{{contact.email}}</td>
                <td>{{contact.phone}}</td>
                <td>{{contact.fax}}</td>
                <td class="whitespace-nowrap"><router-link :to="{name: 'contactDetail', params: {contactId: contact.id}}">詳細</router-link></td>
            </tr>
        </table>
    </div>
</template>
<script setup lang="ts">
import { ContactRecord } from '@/interface/contactInterface';

    const props = defineProps<{
        contacts: ContactRecord[]
    }>()
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