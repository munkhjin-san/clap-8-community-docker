<template>
    <p class="text-center py-[40px]">
        出発報告が未送信です。
    </p>
    <LoaderButton @triggered="send" content="送信" :loading="sending" />
</template>
<script setup lang="ts">
import { ref } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useApi } from '@/composables/api';
const emit = defineEmits(['sent']);
const api = useApi();
const sending = ref(false);
const send = async () => {
    const data = await api.post('/send_departure_report', {}, {
        toast: '送信しました。',
        loadingRef: sending,
    });
    if(data && data.status === 'success') {
        emit('sent');
    }
};

</script>