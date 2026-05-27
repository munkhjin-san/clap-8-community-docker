<template>
    <div class="support-content text-[14px] px-4 under960:px-1">
        <div class="support-content-inner">            
            <div class="si-box">
                <LongInput
                    v-model="content"
                    placeHolder="緊急連絡内容"
                    name="emergencyContactContent"
                    rules="max:2000"
                />
            </div>
            <p class="text-[12px] text-[gray] mt-3">入力した内容は経営管理本部の担当者及び取締役員に優先的に通知されます。</p>
            <div class="si-box">
                <LoaderButton content="送信" :loading="sending" @triggered="send" />
            </div>
            <router-link v-if="hasPrivilage" class="jump-link" :to="{ name: 'emergency_contact_history' }">緊急連絡履歴</router-link>
        </div>
        <div>
            <router-view />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import LongInput from '../Form/LongInput.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';

const contactType = ref('emergency');

const content = ref('');
const sending = ref(false);
const { ping, toast } = useDialog();
const api = useApi();
const auth = useAuthUserStore()
const hasPrivilage = computed(() => {
    return auth.isAdmin || auth.isBoss
});
const send = async () => {
    if (content.value === '') {
        ping('内容を入力してください。');
        return;
    }

    sending.value = true;

    try {
        await api.post('/add_emergency_contact', { content: content.value, type: contactType.value });
        toast('緊急連絡が送信されました。');
        content.value = '';
    } catch (error) {
        console.error(error);
        ping('緊急連絡の送信に失敗しました。');
    } finally {
        sending.value = false;
    }
};
</script>
