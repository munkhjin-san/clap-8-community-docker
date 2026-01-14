<template>
    <div class="flex flex-col" :class="{'items-end' : comment.author.id === auth.activeUser.id }" @click.stop="emit('edit', null)">
        <div class="c-c-text" >
            <UserPanel :user="comment.author" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15" style="margin-top: 2px;"/>
            <div :class="['c-c-w', {'c-c-active' : editable && editable == comment.id}]">
                <p @click.stop="checkEdit" :class="['c-c-inner']" ref="editData" :contenteditable="editable && editable == comment.id ? true : false" v-html="commentBody"></p>
                <div class="c-c-date">{{ DateParser(comment.created_at) }}</div>
            </div>
        </div>
        <div v-if="editable && editable == comment.id" class="c-c-buttons">
            <button @click="update" class="c-c-command">保存</button>
            <button @click="remove" class="c-c-command">削除</button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, ref, useTemplateRef } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import UserPanel from '@/components/Global/UserPanel.vue';
import { DateParser } from '@/utils/tools';
import { useApi } from '@/composables/api';
import { ResourceComment } from '@/interface/projectInterface';
    const auth = useAuthUserStore()
    const props = defineProps<{
        comment: ResourceComment
        editable: number | null
    }>()
    const emit = defineEmits<{
        (e: 'edit', val: number | null): void,
        (e: 'deleted', id: number): void
        (e: 'reload'): void
    }>()
    const element = useTemplateRef('editData')
    const commentBody = computed(() => {
        return props.comment.comment
    })
   
    const sending = ref(false)
    const checkEdit = () => {
        const editable = props.comment.author.id == auth.activeUser.id
        if(editable) {
            emit('edit', props.comment.id)
        }
    }
    const api = useApi()
    const update = async () => {
        const newVal = element.value?.innerText?.trim()
        if (!newVal || sending.value) return

        const normalized = newVal

        await api.put('/resource_comment_update', {
        id: props.comment.id,
        comment: normalized
        }, { loadingRef: sending })

        emit('edit', null)
    }

    const remove = async() => {
        await api.del('/resource_comment_delete', {
            id: props.comment.id

        }, {
            ask: '削除しますか？',
            toast: '削除しました。'
        })
        emit('deleted', props.comment.id)
        emit('edit', null)
    }
   

</script>
