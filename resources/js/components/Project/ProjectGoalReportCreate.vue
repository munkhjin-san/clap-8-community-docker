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
import { ref } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useApi } from '@/composables/api';

const emit = defineEmits(['close', 'reload']);
const props = defineProps<{
    projectGoal: ProjectGoal
}>()
const content = ref('')
const loading = ref(false)
const api = useApi()
const save = async () => {

    await api.post('/project_goal_report_create', {
        project_goal_id: props.projectGoal.id,
        content: content.value
    }, {
        toast: '進捗報告を保存しました。',
        loadingRef: loading
    })
    emit('close')
    emit('reload')
}
</script>