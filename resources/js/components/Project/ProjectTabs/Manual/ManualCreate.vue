<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>{{ editData ? 'マニュアルを編集する' : '新しいマニュアルを作成する' }}</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput 
                    v-model="params.title"
                    place-holder="タイトル"
                    type="text"
                />
            </div>
            <div></div>

            <div class="si-box">
                <LoaderButton content="保存する" :loading="loading" @triggered="save"/>
            </div>            
        </template>

    </Modal>
</template>
<script setup lang="ts">
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '../../../Global/Modal.vue';
import { Manual } from '@/interface/operation';
import ShortInput from '@/components/Form/ShortInput.vue';
import { reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '@/composables/api';

const props = defineProps<{
    editData: Manual | null;
}>();

const emit = defineEmits<{
    close: [flag: boolean]
}>()

const route = useRoute()
const params = reactive<Partial<Manual>>(props.editData ? { ...props.editData} : {})
const loading = ref(false)
const api = useApi()
const save = async() => {

    const data = {
        id: params?.id || '',
        title: params.title,
        project_id: route.params.projectId
    }
    await api.post('/create_manual_record', data, {
        toast: '保存しました。',
        loadingRef: loading
    })
    emit('close', true)

}

</script>