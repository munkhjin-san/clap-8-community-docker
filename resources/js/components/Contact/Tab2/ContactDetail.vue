<template>
<Modal @close="router.push({name: 'contact'})" size="large">
    <template #title>
        <div class="sub-tab-container">
            <div @click="activeTab = 'detail'" :class="['sub-tab-item', { 'selected-sub-tab': activeTab == 'detail'}]">基本情報</div>
            <div v-if="actionTypes.follower || actionTypes.owner" @click="activeTab = 'comment'" :class="['sub-tab-item flex', { 'selected-sub-tab': activeTab == 'comment'}]">
                公開コメント
                <span v-if="badge.contactBadge.some(c => c.contact_id === contact.id)" class="side-notification" style="position: static; width: 12px; height: 12px; min-width: 12px;">
                    {{ badge.contactBadge.find(c => c.contact_id === contact.id).comments }}
                </span>
            </div>              
        </div>
    </template>
    <template #menu>
        
        <div class="ml-auto flex gap-2">
            <CommandButton
                v-if="actionTypes.viewer" 
                :buttons="[
                    { title: 'フォロー', action: () => follow()}
                ]"
            />
            <CommandButton 
                v-if="actionTypes.follower"
                :buttons="[
                    {title: 'フォロー中', action: () => unfollow()}
                ]"
            />
            <ItemMenu v-if="actionTypes.owner" :items="[
                { title: '編集', action: () => emit('edit', contact) },
                { title: '削除', action: () => emit('delete', Number(contact.id))}
            ]"/>

        </div>
    </template>
    <template #content>
        <div v-if="contact && activeTab == 'detail'" class="">
             
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
                    <td>関係者</td>
                    <td>
                        <div v-for="co in contact.collaborators" :key="co.id">
                            {{ co?.name }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>最終更新者</td>
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
                        <img style="max-height: 500px;" loading="lazy" v-if="contact.card_path" :src="`/cdn/${contact.card_path}`"/>
                        <p v-else>名刺データはありません。</p>
                    </td>
                </tr>

            </table>

        </div>
        <div v-else-if="activeTab == 'comment'">
            <ContactComment :item="contact" @refresh="emit('closeCreate', true)"/>
        </div>
    </template>
</Modal>
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import Modal from '@/components/Global/Modal.vue';
import { ContactRecord } from '@/interface/contactInterface';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { computed, ref } from 'vue';
import ContactComment from './Comment/ContactComment.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { useBadgeStore } from '@/store/badge';

const router = useRouter()
const props = defineProps<{
    contact: ContactRecord
}>()
const emit = defineEmits<{
    edit: [data: ContactRecord]
    delete: [id: number]
    closeCreate: [flag: boolean]
}>()
const badge = useBadgeStore()
const auth = useAuthUserStore()
const actionTypes = computed(() => {
  const me = auth.activeUser?.id
  const collabs = props.contact?.collaborators ?? []

  const mine = collabs.find(c => c.id === me)
  const role = mine?.pivot?.role ?? null // 'owner' | 'follower' | null

  const owner = role === 'owner'
  const follower = role === 'follower'
 
  const viewer = !owner && !follower

  return { owner, follower, viewer }
})

const api = useApi()
const activeTab = ref(useRoute().query.mention ? 'comment' : 'detail')
const follow = async() => {
    const message = 'フォローすると、この連絡先に関する更新通知を受け取れます。\nコメントの投稿や個人メモの保存もできます。\nこのコンタクトをフォローしますか？'
    await api.post('/follow_contact', {record_id: props.contact?.id}, {
        ask: message,
        toast: 'コンタクトをフォローしました。',
    })
    emit('closeCreate', true)
}
const unfollow = async() => {
    const message = 'フォローを解除しますか？\n通知やコメント、個人メモの機能が使えなくなります。'
    await api.del(`/unfollow_contact/${props.contact.id}`, {}, {
        ask: message,
        toast: 'フォローを解除しました。'
    })
    emit('closeCreate', true)
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