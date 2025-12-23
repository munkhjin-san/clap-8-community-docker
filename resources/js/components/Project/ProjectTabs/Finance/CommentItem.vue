<template>
    <div class="flex flex-col" :class="{'items-end' : comment.author.id === auth.activeUser.id }" @click.stop="emit('edit', null)">
        <div class="c-c-text" >
            <UserPanel :user="comment.author" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15" style="margin-top: 2px;"/>
            <div :class="['c-c-w', {'c-c-active' : editable && editable == comment.id}]">
                <div
                    v-if="comment.reply"
                    class="border-b [border-bottom-style:solid] border-[var(--primary-color)] pb-1 mb-1 text-[gray] leading-normal"
                >
                    <UserPanel :user="comment.reply.author" imgClass="userSmallIcon" size="15" />

                    <!-- 表示部分 -->
                    <Transition name="reply-expand" mode="out-in">
                        <p v-if="isExpanded" key="expanded" v-html="comment.reply.comment"></p>
                        <p v-else key="truncated">
                            {{ truncatedReply }}
                        </p>
                    </Transition>

                    <!-- ボタンは下に -->
                    <button
                        v-if="isTruncatable"
                        type="button"
                        class="mt-1 text-xs jump-link cursor-pointer"
                        @click.stop="toggleExpand"
                    >
                        {{ isExpanded ? '閉じる' : '続きを表示する' }}
                    </button>
                </div>


                <p @click.stop="mentionClick" :class="['c-c-inner']" ref="editData" :contenteditable="editable && editable == comment.id ? true : false" @click="checkEdit" v-html="commentBody"></p>
                <div class="c-c-date">{{ DateParser(comment.created_at) }}</div>
                <div class="flex justify-between">
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
                    <div v-if="comment.author.id !== auth.activeUser.id" @click="emit('replyComment', comment)" title="返信" class="reactButton" style="margin: 5px -10px -10px -10px">
                        <svg class="dot-menu" version="1.1" viewBox="0 0 91 91" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" height="12">
                            <g><path class="st0" d="M53.3,3.9c1.9-0.2,2-2.8,0-3C30-1.4,4.7,23.9,11.9,47.5C17.6,65.8,37.4,71.8,55.1,69   c-5.9,3.8-11.6,7.8-16.6,12.7c-4.5,4.4,2.4,11.3,6.9,6.9c9.5-9.4,20.1-17,31.2-24.3c2.7-1.7,2.2-5.7-0.9-6.7   C63,53.6,53,46.6,41.8,39.6c-2.8-1.7-5.3,2.1-3.4,4.4c5.3,6.2,12.3,10.9,19.8,14.6c-12.7,3.2-29.8,1.9-36.4-9.9   C11.1,29.2,34.5,6.1,53.3,3.9z"/></g>
                        </svg>
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
        (e: 'replyComment', val: FinanceComment | null): void
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
        const editable = props.comment.author.id == auth.activeUser.id
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
    const isExpanded = ref(false)

    const plainReply = computed(() => {
        const raw = props.comment.reply?.comment ?? ''
        // HTML入りの場合はタグを削る
        return raw.replace(/<[^>]*>/g, '')
    })

    const isTruncatable = computed(() => plainReply.value.length > 20)

    const truncatedReply = computed(() => {
        if (!isTruncatable.value) return plainReply.value
        return plainReply.value.slice(0, 20) + '…'
    })

    const toggleExpand = () => {
        isExpanded.value = !isExpanded.value
    }
</script>
<style scoped>
.reply-expand-enter-active,
.reply-expand-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.reply-expand-enter-from,
.reply-expand-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
