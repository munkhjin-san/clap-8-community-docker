<template>
    <div class="gantt-overlay" @click="emit('close')">
        <div class="gantt-overlay-inner" ref="createModal" @click.stop>
            <div class="flex items-center h-[60px]">
                <div class="mobile w-[40px] m-h-[60px] flex items-center justify-center cursor-pointer" @click="emit('close')">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg"><path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path></svg>
                </div>
                <h4 class="max-w-[calc(100% - 120px)] overflow-hidden overflow-ellipsis whitespace-nowrap">{{ task.remarks }}</h4>
                <div @click="emit('close')" class="flex items-center justify-end cursor-pointer w-[60px] h-[60px] pc ml-auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32" fill="currentColor">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>                
            </div>
            <div class="c-b">
                <div class="c-b-l" ref="commentParent">
                    <div class="post-no-comment-text" v-if="!commentsList.length">現在メッセージはありません。</div>
                    <GanttTaskCommentItem 
                        v-for="comment in commentsList" 
                        :editable="editingCommentId" 
                        :comment="comment" 
                        @edit="(val) => editingCommentId = val"
                    />
                </div>
                
                <div class="c-b-i">
                    <div id="typeArea" ref="commentText" contenteditable="plaintext-only" class="typeBoxArea taskCommentArea"></div>
                    <div @click="send" style="background:transparent;padding-left: 5px;display: flex;cursor: pointer;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="33" viewBox="0 0 43 32" style="margin: auto; fill: var(--third-color);margin-bottom: 5px;">
                            <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Task, TaskComment } from '@/interface/globalInterface';
import {  GanttProjectMethods, GanttProjectMethodsKey } from '@/interface/keys';
import axios from 'axios';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue'
import { inject } from 'vue';

import GanttTaskCommentItem from '@/components/Task/Gantt/GanttTaskCommentItem.vue'
import { useBadgeStore } from '@/store/badge';
const props = defineProps<{
  task: Task
}>()
const emit = defineEmits(['close'])
const sending = ref(false)
const {refreshProject} = inject(GanttProjectMethodsKey) as GanttProjectMethods
const editingCommentId = ref(null)
const commentParent = useTemplateRef('commentParent')
const commentText = useTemplateRef('commentText')
const badge = useBadgeStore()
const commentsList = ref<TaskComment[]>([])
onMounted(() => {
    
    updateChecked()
    getComments()
})
const getComments = async() => {
    const res = await axios.get('/get_task_comment_list', {params: {task_record_id: props.task.id}})
    commentsList.value = res.data
    setTimeout(() => {
        scrollToEnd('instant')
    }, 100);
    
}
const updateChecked = async() => {
    await axios.post('/update_task_comment_check', {task_id: props.task.id})
    // refreshProject({})
    badge.getTaskCommentBadge()
}
const send = async() => {
    const text = commentText.value?.innerText
    if(sending.value || !text){
        return
    }
    sending.value = true
    axios.put('/task_comment', { 
        task_record_id: props.task.id,
        comment: text
    }).then( async () => {
        refresh()
        getComments()

    }).finally(() => {
        sending.value = false
        
    })
}
const refresh = async() => {
    commentText.value!.innerText = ''
    refreshProject({})
    nextTick(() => {
        scrollToEnd()
    })
}
const scrollToEnd = (behavior?:ScrollBehavior) => {
    const h = commentParent.value?.scrollHeight
    commentParent.value?.scrollTo({
        top: h,
        left: 0,
        behavior: behavior ?? 'smooth',
    });
}
</script>

<style>
.c-c-date{
    color: gray;
    font-size: 10px;
    margin-top: 5px;
}
.c-c-w{
    padding: 10px;
    background: var(--background-color);
    min-width: 100px;
    width: fit-content;
    cursor: text;
    font-size: 14px;
}
.c-c-inner{
    outline: none;
    word-break: break-all;
    white-space: break-spaces;
    line-height: 1.3;

}
.c-c-active{
    outline: solid thin;
}
.c-c-command{
    padding: 5px;
    background: var(--primary-color);
    color: var(--background-color);
    font-size: 12px;
    
}
.c-c-text{

    display: flex;
    align-items: flex-start;
    gap: 5px;
    position: relative;
}
.c-c-buttons {
    display: flex;
    gap: 5px;
    margin: 10px 0 10px auto;
    margin-left: 20px;
}
.c-b-l{
    height: -webkit-fill-available;
    overflow: hidden auto;
    display: flex;
    flex-direction: column;
    gap: 15px;
    background: var(--bg3);
    padding: 15px;
    position: relative;
}
.c-b-i{
    display: flex;
    font-size: 11px;
    margin: 20px 0;
    align-items: flex-end;
}
.c-b{
    width: 100%;
    height: calc(100% - 60px);
    display: flex;
    flex-direction: column;
}
.c-c-input{
    padding: 10px;
    width: calc(100% - 50px);
    border: 1px solid var(--third-color);
}
.taskCommentArea{
    border: 1px solid var(--third-color);
    margin-left: 0;
    max-height: 150px;
    width: -webkit-fill-available !important;
    padding: 5px !important;
}
</style>