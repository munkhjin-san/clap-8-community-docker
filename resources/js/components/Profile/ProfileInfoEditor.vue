<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>プロフィール編集</p>
        </template>     
        <template #content>
            <div class="si-box">
                <ShortInput
                    placeHolder="電話番号"
                    uId="userPhone"
                    name="userPhone"
                    v-model="params.phone_number"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    placeHolder="メールアドレス"
                    uId="userMail"
                    name="userMail"
                    v-model="params.work_email"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    placeHolder="好きな言葉"
                    uId="userMotto"
                    name="userMotto"
                    rules=""
                    label="好きな言葉"
                    v-model="params.motto"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    placeHolder="私の「楽」"
                    uId="userEnjoy"
                    name="userEnjoy"
                    v-model="params.enjoy"
                />
            </div>
            <div class="si-box">
                <LongInput
                    placeHolder="自己紹介"
                    uId="userIntro"
                    name="userIntro"
                    v-model="params.intro"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    placeHolder="推し"
                    uId="userRecommend"
                    name="userRecommend"
                    customClass="full"
                    v-model="params.recommend"
                />
            </div>

            <div class="si-box">
                <LoaderButton
                    @triggered="profileEditSend()" 
                    :loading="processing" 
                    content="保存する"
                />
            </div>     
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { User } from '@/interface/globalInterface';
import LongInput from '../Form/LongInput.vue';
import ShortInput from '../Form/ShortInput.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import Modal from '../Global/Modal.vue';
import { ref } from 'vue';
import { useApi } from '@/composables/api';
const emit = defineEmits<{
    close: [boolean]
}>()

const props = defineProps<{
    userData: User
}>()

const processing = ref(false)

const params = ref({
    phone_number: props.userData?.phone_number ?? '',
    work_email: props.userData?.email ?? '',
    motto: props.userData?.motto ?? '',
    enjoy: props.userData?.enjoy ?? '',
    intro: props.userData?.intro ?? '',
    recommend: props.userData?.recommend ?? '',
})
const api = useApi()
const profileEditSend = async () => {
    if (processing.value) return;
    processing.value = true;
    const response = await api.post('/profile_profile_edit_api', params.value, {
        toast: '保存しました。',
        loadingRef: processing,
    });
    if (response) {
        emit('close', true);
    }
}
</script>