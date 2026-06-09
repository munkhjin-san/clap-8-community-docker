<template>
    <div class="support-content text-[14px] px-4 under960:px-1">
        <div class="support-content-inner">            
            <div class="si-box">
                <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input class="custom-f-radio" type="radio" name="contactType" value="emergency" v-model="contactType" />
                    緊急連絡
                </label>
                <label class="ml-4 inline-flex items-center gap-1 cursor-pointer">
                    <input class="custom-f-radio" type="radio" name="contactType" value="incident" v-model="contactType" />
                    インシデント報告
                </label>
            </div>
            <div class="si-box">
                <LongInput
                    v-model="content"
                    :placeHolder="contactType === 'emergency' ? '緊急連絡内容' : 'インシデント報告内容'"
                    name="emergencyContactContent"
                    rules="max:2000"
                />
            </div>
            <p v-if="contactType === 'emergency'" class="text-[12px] text-[gray] mt-3">入力した内容は経営管理本部の担当者及び取締役員に優先的に通知されます。</p>
            <button
                v-if="contactType === 'incident'"
                type="button"
                class="jump-link mt-4"
                @click="detailModalOpen = true"
            >
                詳細情報入力する
            </button>
            <div class="si-box">
                <LoaderButton content="送信" :loading="sending" @triggered="send" />
            </div>
            <router-link v-if="hasPrivilage" class="jump-link" :to="{ name: 'emergency_contact_history' }">緊急連絡履歴</router-link>
        </div>
        <div>
            <router-view />
        </div>
        <Teleport to="body">
            <Transition name="modalFade">
                <IncidentDetailModal
                    v-if="detailModalOpen"
                    create-mode
                    reporter-mode
                    :initial-incident="{ description: content || null }"
                    @created="handleIncidentCreated"
                    @close="detailModalOpen = false"
                />
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import LongInput from '../Form/LongInput.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import IncidentDetailModal from '@/components/Incident/IncidentDetailModal.vue';
import type { Incident } from '@/interface/incident';

type ContactType = 'emergency' | 'incident';

const route = useRoute();
const routeContactType = () => route.query.type === 'incident' ? 'incident' : 'emergency';
const contactType = ref<ContactType>(routeContactType());

const content = ref('');
const sending = ref(false);
const detailModalOpen = ref(false);
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
        toast(contactType.value === 'emergency' ? '緊急連絡が送信されました。' : 'インシデント報告が送信されました。');
        content.value = '';
    } catch (error) {
        console.error(error);
        ping(contactType.value === 'emergency' ? '緊急連絡の送信に失敗しました。' : 'インシデント報告の送信に失敗しました。');
    } finally {
        sending.value = false;
    }
};

const handleIncidentCreated = (_incident: Incident) => {
    detailModalOpen.value = false;
    content.value = '';
    toast('インシデント報告が送信されました。');
};

watch(
    () => route.query.type,
    () => {
        contactType.value = routeContactType();
    },
);
</script>
