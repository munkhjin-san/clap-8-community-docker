<template>
    <Transition name="modalFade">
        <div class="overlay">
            <div class="chatCreate scrollable">
                <div class="recordFormTitle" style="display:flex">
                    <p>タスク申請</p>
                    <div class="m-close-button" style="position:unset; margin:auto 0 auto auto;width:auto;gap:30px">
                        <svg @click="close"  version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
                
                <div class="si-box" style="line-height: normal;margin-top:0;">
                    <p><strong>メモ</strong></p>
                    <p style="white-space: break-spaces;font-size: 14px;">{{ taskRequest.data.remarks }}</p>
                </div>
                <div class="si-box" style="line-height: normal;">
                    <p><strong>期限</strong></p>
                    <p style="font-size: 14px;">{{ dateDetail(taskRequest.data.end_at) }}</p>
                </div>
                <div class="si-box">
                    <LongInput 
                        :initialValue="comment"   
                        :placeHolder="`コメント`"
                        ref="commentRef"
                        name="comment"
                        v-model="comment"
                    />
                </div>
                <div class="si-box">
                    <FileUploader 
                        v-model="uploadedFiles"
                        path="/task_files"
                    />
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="approveRequest" :loading="loading" content="申請する"/>
                </div>
            </div>
        </div>
    </Transition>
</template>
<script setup lang="ts">
import { ref, computed, inject } from 'vue';
import LongInput from '../../../Form/LongInput.vue';
import LoaderButton from '../../../Global/LoaderButton.vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import { useAuthUserStore } from '@/store/auth';
import { useTaskRequest } from '@/store/taskRequest';
import { dateDetail } from '@/utils/workApi';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
    const taskRequest = useTaskRequest()
    const auth = useAuthUserStore()
    const myTask = computed(() => {
        return taskRequest.data.executors.find(ob => ob.id == auth.activeUser.id)
    })
    const file = computed(() => {
        return taskRequest.data.files.filter(ob => ob.user_id == auth.activeUser.id)
    })
    const comment = ref(myTask.value?.pivot.comment ? myTask.value.pivot.comment : '')
    const loading = ref(false)
    const uploadedFiles = ref(file.value ? file.value : [])
    const api = useApi()
    const { ping } = useDialog()
    

    const approveRequest = async() => {
        if(uploadedFiles.value.length > 1){
            ping('ファイルを 1 つのみアップロードしてください。')
            return
        }
        await api.put('/task_approve_request', {
            file_ids: uploadedFiles.value ? uploadedFiles.value.map(ob => ob.id) : [],
            comment: comment.value,
            task_id: taskRequest.data.id,
            board_id: taskRequest.data.board_id,
            status_flag: 1,
        }, {
            ask: 'タスクを申請しますか。',
            toast: '申請しました。',
        })
        close()
    }
    const close = () => {
        const data = {
            active: false,
            data: null,
        }
        taskRequest.setTaskRequest(data)
    }
</script>
