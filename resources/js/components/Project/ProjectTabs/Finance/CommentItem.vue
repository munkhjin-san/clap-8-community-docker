<template>
    <div class="flex flex-col" :class="{'items-end' : comment.author.id === auth.activeUser.id }">
        <div class="c-c-text" >
            <UserPanel :user="comment.author" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15" style="margin-top: 2px;"/>
            <div :class="['c-c-w', {'c-c-active' : editable && editable == comment.id}]">
                <p @click.stop="mentionClick" :class="['c-c-inner']" ref="editData" :contenteditable="editable && editable == comment.id ? true : false" @click="checkEdit" v-html="commentBody"></p>
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
import { computed, inject, ref, useTemplateRef } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import UserPanel from '@/components/Global/UserPanel.vue';
import { DateParser, mentionFormatter } from '@/utils/tools';
import { useApi } from '@/composables/api';
import { FinanceComment } from '@/interface/projectInterface';
import { useMenuStore } from '@/store/menu';
import { User } from '@/interface/globalInterface';
    const auth = useAuthUserStore()
    const props = defineProps<{
        comment: FinanceComment
        editable: number | null
        mentionableUsers: User[]
    }>()
    const menu = useMenuStore()
    const emit = defineEmits<{
        (e: 'edit', val: number | null): void,
        (e: 'deleted', id: number): void
    }>()
    const element = useTemplateRef('editData')
    const pushInstantUser = inject('pushInstantUser') as Function
    const commentBody = computed(() => {
        return mentionFormatter(props.comment.comment, true)
    })
    const mentionClick = (event) => {            
        const target = event.target;
        if (target.classList.contains('mntuser')) {
            const username = target.getAttribute('data-username');
            const striped = username ? username.replace(/@/g, "") : '';
            const userid =  target.getAttribute('data-userid');
            if(username == '全員') return
            pushInstantUser(event, userid, striped)
        }
        menu.close()   
    }
    const sending = ref(false)
    const checkEdit = () => {
        const editable = props.comment.author.id == auth.id
        if(editable) {
            emit('edit', props.comment.id)
        } 
    }
    const api = useApi()
    const update = async () => {
        const newVal = element.value?.innerText?.trim()
        if (!newVal || sending.value) return

        const normalized = convertMentionsToTokens(newVal, props.mentionableUsers /*, true */)

        await api.put('/finance_comment_update', {
        id: props.comment.id,
        comment: normalized
        }, { loadingRef: sending })



        emit('edit', null)
        
    }

    const remove = async() => {
        api.del('/finance_comment_delete', { 
            id: props.comment.id
            
        })
        emit('deleted', props.comment.id)
        emit('edit', null)  
    }
    const convertMentionsToTokens = (
        text: string,
        users?: User[],
        withIds = false
    ): string => {
        if (!text) return ''
        const emailPlaceholders: string[] = []
        text = text.replace(/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g, m => {
            emailPlaceholders.push(m)
            return `__EMAIL_${emailPlaceholders.length - 1}__`
        })
        const nameToUser = new Map<string, User>()
        if (users && users.length) {
            for (const u of users) {
            if (u?.name) nameToUser.set(u.name.toLowerCase(), u)
            }
        }
        text = text.replace(/[@＠]([^\s@＠\[\]:|]{1,50})/gu, (full, rawName: string) => {
            const name = rawName.trim()
            if (!name) return full

            if (nameToUser.size) {
            const u = nameToUser.get(name.toLowerCase())
            if (!u) return full
            return withIds ? `[To:${u.name}|${u.id}:]` : `[To:${u.name}:]`
            }

            return `[To:${name}:]`
        })

        text = text.replace(/__EMAIL_(\d+)__/g, (_, i) => emailPlaceholders[Number(i)] || '')

        return text
    }

</script>