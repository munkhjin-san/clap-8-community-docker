<template>
    <div class="flex flex-col" :class="{'items-end' : comment.author.id === auth.activeUser.id }">
        <div class="c-c-text" >
            <UserPanel :user="comment.author" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15" style="margin-top: 2px;"/>
            <div :class="['c-c-w', {'c-c-active' : editable && editable == comment.id}]">
                <p @click.stop="mentionClick" :class="['c-c-inner']" ref="editData" :contenteditable="editable && editable == comment.id ? true : false" @click="checkEdit" v-html="commentBody"></p>
                <div class="c-c-date">{{ DateParser(comment.created_at) }}</div>
                <div class="flex w-fit">
                    <div v-if="checkView" class="reactButton" style="justify-content: flex-start;" :class="{cursorBlock : comment.user_id == auth.activeUser.id, reactOn: checking}" @click.stop="checkComment">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="12" viewBox="0 0 38 32" :fill="checkSendIconColor ? 'var(--primary-color)' : 'var(--check-inactive)'">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </div>
                    <div v-if="comment.checked_users.length" @click.stop="viewReactedUsersList" style="display:flex;padding: 10px;margin: 5px 0 -15px -15px;height: 12px;">
                        <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in reactedUsersListAll.slice(0,3)">  
                            <UserPanel :title="user.name" :disableInstant="true" size="13" :user="user" imgClass="userSmallIcon"/>                                         
                        </div>
                        <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="comment.checked_users.length > 3">...({{comment.checked_users.length}})</span>
                    </div>
                </div>
                
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
import { useMessageUsers } from '@/store/messageUsers';
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
        (e: 'reload'): void
    }>()
    const element = useTemplateRef('editData')
    const checking = ref(false)
    const pushInstantUser = inject('pushInstantUser') as Function
    const messageUsers = useMessageUsers()
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
    const checkSendIconColor = computed(() => {                
        const check_list = props.comment.checked_users.some(ob => ob.id == auth.activeUser.id)                
        return (props.comment.user_id == auth.activeUser.id || check_list) ? true : false              
        
    })
    const checkView = computed(() => {
        return !(props.comment.user_id == auth.activeUser.id && !props.comment.checked_users.length)
    })
    const checkComment = async() => {
        if(props.comment.user_id == auth.activeUser.id) return    
        checking.value = props.comment.checked_users.some(ob => ob.id === auth.activeUser.id)
        await api.post('/mark_finance_check', {id: props.comment.id})
        emit('reload')
    }
    const viewReactedUsersList = () => {
        const data = {
            active: true,
            userList: reactedUsersListAll.value,
            title: 'チェックしたメンバー'
        }
        messageUsers.setMessageUsers(data)
        
    }
    const reactedUsersListAll = computed(() => {
        return props.comment.checked_users && props.comment.checked_users.length ? Array.from(props.comment.checked_users).reverse() as User[] : []                
    })

</script>