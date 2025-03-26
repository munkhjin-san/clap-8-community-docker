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
import { inject, reactive, ref } from 'vue';
import { DialogMethods } from '@/interface/globalInterface';
import axios from 'axios';
import { useRoute } from 'vue-router';

const props = defineProps<{
    editData: Manual | null;
}>();

const emit = defineEmits<{
    close: [flag: boolean]
}>()

const route = useRoute()
const { notify, info, confirm } = inject('dialog') as DialogMethods
const params = reactive<Partial<Manual>>(props.editData ? { ...props.editData} : {})
const loading = ref(false)
const save = async() => {


    loading.value = true
    try {
        const data = {
            id: params?.id || '',
            title: params.title,
            project_id: route.params.projectId
        }
        await axios.post('/create_manual_record', data)
        info('保存しました。')
        loading.value = false
        emit('close', true)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        loading.value = false
    }
}

</script>