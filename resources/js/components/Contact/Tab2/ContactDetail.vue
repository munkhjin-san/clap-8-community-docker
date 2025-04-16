<template>
<Modal @close="router.back()">
    <template #menu>
        <div class="ml-auto">
            <ItemMenu :items="[
                { title: '編集', action: () => emit('edit', contact) },
                { title: '削除', action: () => emit('delete', Number(contact.id))}
            ]"/>

        </div>
    </template>
    <template #content>
        <div v-if="contact" class="">
            <table class="contact-detail-table">
                <tr>
                    <td>コンタクト種類</td>
                    <td>{{ contact?.type?.title || '未設定' }}</td>
                </tr>
                <tr>
                    <td>氏名</td>
                    <td>{{ contact.name }}</td>
                </tr>
                <tr>
                    <td>会社名</td>
                    <td>{{ contact.company_name }}</td>
                </tr>
                <tr>
                    <td>住所</td>
                    <td>{{ contact.address }}</td>
                </tr>
                <tr>
                    <td>メールアドレス</td>
                    <td>{{ contact.email }}</td>
                </tr>
                <tr>
                    <td>電話番号</td>
                    <td>{{ contact.phone }}</td>
                </tr>
                <tr>
                    <td>FAX</td>
                    <td>{{ contact.fax }}</td>
                </tr>
                <tr>
                    <td>詳細</td>
                    <td>{{ contact.description }}</td>
                </tr>
                <tr>
                    <td>作成者</td>
                    <td>{{ contact.creator?.name }}</td>
                </tr>
                <tr>
                    <td>更新者</td>
                    <td>{{ contact.updater?.name }}</td>
                </tr>
                <tr>                    
                    <td colspan="2" style="white-space: normal;">
                        <h3 class="mb-[15px]">企業情報</h3>
                        <div v-html="contact.data"></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="white-space: normal;">
                        <h3 class="mb-[15px]">名刺データ</h3>
                        <img v-if="contact.card_path" :src="`/cdn/card_files/${contact.card_path}.webp`"/>
                        <p v-else>名刺データはありません。</p>
                    </td>
                </tr>

            </table>

        </div>
    </template>
</Modal>
</template>
<script setup lang="ts">
import { useRouter } from 'vue-router';
import Modal from '@/components/Global/Modal.vue';
import { ContactRecord } from '@/interface/contactInterface';
import ItemMenu from '@/components/Global/ItemMenu.vue';

const router = useRouter()
const props = defineProps<{
    contact: ContactRecord
}>()

const emit = defineEmits<{
    edit: [data: ContactRecord]
    delete: [id: number]
}>()
const edit = () => {

}
const deleteContact = () => {

}
</script>
<style scoped>
.contact-detail-table{
    tr{
        border: solid thin lightgray;
        color: var(--primary-color);
    }
    td{
        padding: 15px;
        font-size: 14px;
        line-height: 1.5;
    }
    td:first-of-type{
        white-space: nowrap;
        border-right: solid thin lightgray;
        vertical-align: baseline;
        
    }
    width: calc(100% - 2px);
}
@media screen and (max-width: 959px) {
    .contact-detail-table{
        display: flex;
        flex-direction: column;
       
        tr{
            display: flex;
            flex-direction: column;
            width: 100%;
            border-bottom: none;
            &:last-child{
                border-bottom: solid thin lightgray;
            }
            td{
                border: none;
            }
            td:first-of-type{
                border: none;    
                padding-bottom: 0;            
            }
            
        }
    }
    
}
</style>