<template>
    <div>
        <div class="c-c-text">
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
    // const {refreshProject} = inject(GanttProjectMethodsKey) as GanttProjectMethods
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

        // convert @mentions to tokens before sending
        const normalized = convertMentionsToTokens(newVal, props.mentionableUsers /*, true */)
        //            ^ set the third arg to true if you want [To:Name|id:] tokens

        await api.put('/finance_comment_update', {
        id: props.comment.id,
        comment: normalized
        }, { loadingRef: sending })

        // optionally update the local UI model so it re-renders immediately
        // props.comment.comment = normalized

        emit('edit', null)
        
    }

    const remove = async() => {
        api.del('/finance_comment_delete', { 
            id: props.comment.id
            
        })
        emit('deleted', props.comment.id)
        emit('edit', null)  
    }
    // props.mentionableUsers?: { id: number; name: string }[]
    function convertMentionsToTokens(
        text: string,
        users?: User[],
        withIds = false // set true if you want [To:Name|123:]
        ): string {
        if (!text) return ''

        // 1) Mask emails so we don't replace the @ inside them
        const emailPlaceholders: string[] = []
        text = text.replace(/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g, m => {
            emailPlaceholders.push(m)
            return `__EMAIL_${emailPlaceholders.length - 1}__`
        })

        // 2) Build a quick lookup by lowercased name (optional, but safer)
        const nameToUser = new Map<string, User>()
        if (users && users.length) {
            for (const u of users) {
            if (u?.name) nameToUser.set(u.name.toLowerCase(), u)
            }
        }

        // 3) Replace @mentions not already tokenized
        //    - supports ASCII @ and fullwidth ＠
        //    - stops at whitespace or punctuation we don't want inside names
        //    - leaves existing [To:...:] alone
        text = text.replace(/[@＠]([^\s@＠\[\]:|]{1,50})/gu, (full, rawName: string) => {
            const name = rawName.trim()
            if (!name) return full

            // if we have a known list, only convert known names
            if (nameToUser.size) {
            const u = nameToUser.get(name.toLowerCase())
            if (!u) return full
            return withIds ? `[To:${u.name}|${u.id}:]` : `[To:${u.name}:]`
            }

            // no list: convert blindly, but allow @全員 as well
            return `[To:${name}:]`
        })

        // 4) Restore emails
        text = text.replace(/__EMAIL_(\d+)__/g, (_, i) => emailPlaceholders[Number(i)] || '')

        return text
    }

</script>