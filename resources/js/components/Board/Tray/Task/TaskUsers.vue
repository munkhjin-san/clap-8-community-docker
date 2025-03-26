<template>
    <div @mousedown="close" class="overlay">
        <div class="users-list-popup" @mousedown.stop>
            <div style="display:flex">
                <p style="font-weight:600;margin-right:20px;">{{ title }}</p>
                <div style="margin-left:auto;display: flex;align-items: center;">                                          
                    <div class="cursor-pointer" @click="close" style="position:unset;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>
            </div>
            <div style="position:relative; margin-top: 20px;">
                <div v-if="userList.length" class="suggested-list">
                    <div :key="user.id" v-for="user in userList">
                        <div class="user-wrapper">
                            <div>
                                <div class="user-wrap"> 
                                    <UserPanel :user="user" imgClass="userNormalIcon" size="30"/>
                                    <router-link :to="`/user/${user.id}`" class="suggested-user-name user-link" style="margin:0">{{ user.name }}</router-link>
                                    <div title="タスクが完了しました" v-if="user.pivot.progress_flag == 2" style="width: 15px;height: 15px;display: flex;border-radius: 50%;margin:auto 3px;min-width:15px; background-color: rgb(100, 188, 68);">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill:#fff;margin:auto;">
                                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                        </svg>                                           
                                    </div>
                                    
                                    <div title="タスク承認待ち" v-if="user.pivot.status_flag == 1" style="width: 15px;height: 15px;display: flex;border-radius: 50%;margin:auto 3px;min-width:15px;background-color: tomato;">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill:#fff;margin:auto;">
                                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                        </svg>                                           
                                    </div>
                                </div>
                                <div style="padding: 0 10px 10px 10px;" v-if="user.pivot.status_flag >= 1 && (user.pivot.comment || task.files.length)">
                                    <div style="white-space: break-spaces;margin-bottom: 10px;" v-if="user.pivot.comment"><strong>コメント: </strong>{{ user.pivot.comment }}</div>
                                    <div v-for="file in task.files" v-if="task.files.length">
                                        <div v-if="file.user_id == user.id"  class="cursor-pointer" style="width: fit-content;" @click="viewFile(file)">
                                            <strong>ファイル: </strong>
                                            <div v-if="file.mime_type == 'image'" style="max-width:65px;height:40px;display: flex;">                   
                                                <img
                                                    style="max-width:100%;margin:auto;max-height:100%;" 
                                                    class="list-image-mobile" 
                                                    :src="`/cdn/task_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`" 
                                                    loading="lazy"
                                                />
                                            </div>
                                            <div v-if="file.mime_type !== 'image'" style="position:relative;">
                                                <FileIcon :ext="file.extension"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="isSupervisor && user.pivot.status_flag == 1" style="display: flex; gap: 5px; padding: 10px;">
                                <CommandButton 
                                    :buttons="[
                                        {title: '承認', action:() => taskApprove(user.id, 2, 2)}, 
                                        {title: '差戻', action:() =>  taskApprove(user.id, 0, 0)}
                                    ]" 
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <span v-else>現在いません</span>                 
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, inject } from 'vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { useTaskUsers } from '@/store/taskUsers';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useAuthUserStore } from '@/store/auth';
import axios from 'axios';
import { useFilePreview } from '@/store/filePreview';
import FileIcon from '../../Mixed/FileIcon.vue';
    const taskUsers = useTaskUsers()
    const filePreview = useFilePreview()
    const auth = useAuthUserStore()
    const { confirm, info } = inject('dialog')
    const refresh = inject('refreshRemind')
    const userList = computed(() => {
        return taskUsers.userList
    })
    const title = computed(() => {
        return taskUsers.title
    })
    const task = computed(() => {
        return taskUsers.task
    })
    const isSupervisor = computed(() => {
        return task.value.supervisors.some(ob => ob.id === auth.activeUser.id)
    })           
    const close = () => {
        const data = {
            active: false,
            userList: [],
            title: '',
            task: {},
        }
        taskUsers.setTaskUsers(data)
    }
    const taskApprove = async(userId, status, progress_flag) => {
        if(status == 0){
            const answer = await confirm('申請を差し戻しますか。差し戻した場合、申請社員に連絡してください。')
            if(!answer.value) return
        }
        let info_message = status == 2 ? '承認' : '差戻'
        try {
            const params = {
                user_id: userId,
                task_id: task.value.id,
                status_flag: status,
                progress_flag: progress_flag
            }
            await axios.put('/task_approve', params)
            info(`${info_message}しました。`)
            refresh('not_approved_tasks')
            close()
        } catch (e) {
            console.log(e)
        }
    }
    const viewFile = (file) => {
        let target_data = file
        let file_path = `/cdn/task_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`
        let doc_path = `/task_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`
        target_data['file_path'] = file_path
        target_data['doc_path'] = doc_path
        const data = {
            active: true,
            files: [target_data],
            source: 'message',
            index: 0,
            message: null,
        }
        filePreview.setFilePreview(data)
        
    }
</script>
<style scoped lang="scss"> 
.user-wrap {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    padding: 10px;
    white-space: nowrap;    
    position: relative;
}  
.user-wrapper {
    display: flex;
    justify-content: space-between;
}
.user-wrapper:hover{
    background: var(--bg2);
    color: var(--primary-color);
}

.sibling-task-cbar{
    display: flex;
    gap: 20px;
    align-items: center;
    margin: 15px 0;
}

@media screen and (max-width: 959px) {
    
    .user-wrapper{
        flex-wrap: wrap;
    }
}
</style>
