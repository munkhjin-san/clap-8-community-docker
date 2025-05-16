<template>
<Modal @close="emit('close')">
    <template #title>
        <p>進捗報告</p>
    </template>
    <template #content>
        <div>
            <LongInput 
                placeHolder="進捗内容"
                v-model="content"
                ref="resultRef"
                rules="required"
            />
        </div>
        <div class="si-box">
            <LoaderButton content="保存する" @triggered="save" :loading="loading"/>
        </div>

    </template>
</Modal>
</template>
<script lang="ts" setup>
import { ProjectGoal } from '@/interface/projectInterface';
import Modal from '../Global/Modal.vue';
import LongInput from '../Form/LongInput.vue';
import { inject, ref } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { Dialog, DialogMethods } from '@/interface/globalInterface';
import axios from 'axios';

const emit = defineEmits(['close', 'reload']);
const props = defineProps<{
    projectGoal: ProjectGoal
}>()
const { confirm, notify, info } = inject('dialog') as DialogMethods;
const content = ref('')
const loading = ref(false)
const save = async () => {
    loading.value = true
    try {
        const response = await axios.post('/project_goal_report_create', {
            project_goal_id: props.projectGoal.id,
            content: content.value
        })
        info('進捗報告が完了しました。')
        emit('close')
        emit('reload')
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        loading.value = false
    }
}
</script>