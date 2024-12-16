<template>
    <div class="overlay" @mousedown="emit('close')">
        <div class="chatCreate scrollable" @mousedown.stop>
            <div class="recordFormTitle" style="display:flex;">
                <p>昇給課題結果</p>
                <div class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div>
                <div class="si-box" style="margin-top: 0;">
                    <LongInput 
                        placeHolder="結果状況"
                        v-model="result"
                        ref="resultRef"
                        rules="required"
                    />
                </div>
                <div class="si-box">
                    <FileUploader 
                        v-model="uploadedFiles"
                        path="/project_files"
                    />
                </div>
                <div v-if="reviewing" class="si-box" style="display: flex; gap: 20px; justify-content: center;">
                    <LoaderButton style="margin: 0;" content="未達成" @triggered="progressReport(7)" :loading="loading[1]"/>
                    <LoaderButton style="margin: 0;" content="達成" @triggered="progressReport(8)" :loading="loading[2]"/>
                </div>
                <div v-else class="si-box">
                    <LoaderButton content="報告する" @triggered="progressReport(6)" :loading="loading[0]"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { inject, ref } from 'vue';
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import axios from 'axios';
import { Dialog } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
import { File } from '@/interface/trayInterface';
import { useBadgeStore } from '@/store/badge';
const props = defineProps(['chosenIssue', 'memberData', 'isManagerOrMember', 'reviewing'])
const emit = defineEmits(['close', 'reload'])
const result = ref(props.chosenIssue?.result ?? '')
const resultRef = ref<InstanceType<typeof LongInput> | null>(null)
const loading = ref([false, false, false])
const auth = useAuthUserStore()
const refresh = inject('refresh') as Function
const uploadedFiles = ref<File[]>(props.chosenIssue?.files ?? [])
const badge = useBadgeStore()
const { notify, info } = inject<Dialog>('dialog')!

const progressReport = async(status: number) => {
    const val = await resultRef.value?.validate() || {valid: false}

    if(!val.valid) return

    const loadstatus = status === 6 ? 0 : status === 7 ? 1 : 2
    try {
        let info_message = status === 6 ? '報告' : status === 7 ? '未達成' : '達成'
        loading.value[loadstatus] = true
        const params = {
            id: props.chosenIssue.id,
            status: status,
            result: result.value,
            file_ids: uploadedFiles.value.length ? uploadedFiles.value.map(ob => ob.id) : [], 
        }
        await axios.put('/update_issue_report', params)
        loading.value[loadstatus] = false
        info(`${info_message}しました`)
        emit('reload')
        refresh()
        badge.getProjectBadge()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
</script>