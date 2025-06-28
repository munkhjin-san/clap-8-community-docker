<template>
    <div>
        <div class="c-c-text">
            <UserPanel :user="comment.user" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15" style="margin-top: 2px;"/>
            <div :class="['c-c-w', {'c-c-active' : editable && editable == comment.id}]">
                <p :class="['c-c-inner']" ref="editData" :contenteditable="editable && editable == comment.id ? true : false" @click="checkEdit">{{ comment.comment }}</p>
                <div class="c-c-date">{{ DateParser(props.comment.created_at) }}</div>
            </div>
            
            
        </div>
        <div v-if="editable && editable == comment.id" class="c-c-buttons">
            <button @click="update" class="c-c-command">保存</button>
            <button @click="remove" class="c-c-command">削除</button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { TaskComment } from '@/interface/globalInterface';
import { inject, ref, useTemplateRef } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import {  GanttProjectMethods, GanttProjectMethodsKey } from '@/interface/keys';
import UserPanel from '@/components/Global/UserPanel.vue';
import { DateParser } from '@/utils/tools';
import { useApi } from '@/composables/api';
const auth = useAuthUserStore()
    const props = defineProps<{
        comment: TaskComment
        editable: number | null
    }>()
    const emit = defineEmits(['edit'])
    const element = useTemplateRef('editData')

    const sending = ref(false)
    const {refreshProject} = inject(GanttProjectMethodsKey) as GanttProjectMethods
    const checkEdit = () => {
        const editable = props.comment.user.id == auth.id
        if(editable) {
            emit('edit', props.comment.id)
        } 
    }
    const api = useApi()
    const update = async() => {
        const newVal = element.value?.innerText
        if(newVal){
            if(sending.value){
                return
            }
            api.put('/task_comment_update', { 
                id: props.comment.id,
                comment: newVal
            }, {
                loadingRef: sending
            })
        
            refreshProject({})
            emit('edit', null)            
        }
    }
    const remove = async() => {
        api.del('/task_comment', { 
            id: props.comment.id
            
        })
        refreshProject({})
        emit('edit', null)  
    }
</script>