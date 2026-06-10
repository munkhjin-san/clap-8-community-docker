<template>
    <div :key="comment.id" class="commentRoot" >
        <div class="commentInner relative" :style="{ float: comment.user_id == auth.id ? 'right' : 'left'}">
            <div class="bg-[var(--message-background)] p-4" :style="{ border: editing ? 'solid 2px var(--hoverBorder)' : 'solid 2px transparent', boxSizing: 'border-box', }">
                <div class="message-top-block" style="margin-bottom: 0;">      
                    <div style="display: flex;align-items: center;gap:10px">
                        <UserPanel size="30" :user="comment.user" imgClass="userNormalIcon"/>                   
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <div @click.stop="pushInstantUser($event, comment.user_id)" class="cursor-pointer" style="font-size: 14px;">{{ comment?.user?.name }}</div>
                            <span v-if="commentTypeLabel" class="progress-report-label">
                                {{ commentTypeLabel }}
                            </span>
                        </div>
                    </div>     
                    <div class="m-date">{{DateParser(comment.created_at)}}</div> 
                    <div class="messageIconContainer">
                        <ItemMenu v-if="comment.user_id == auth.id && !editing" :items="[
                            {title: '編集する', action: () => editing = true},
                            {title: '削除する', action: () => emit('deleteComment', comment.id)}
                        ]"/>
                    </div>
                </div>
                <div class="commentBox" style="margin-bottom: 10px;">
                    <Editor v-if="editing" :comment="comment" :urlCheck="urlCheck" @cancel="editing = false"/>
                    <p  
                        v-else
                        :class="{emojiOnlyInner : comment.emoji_flag == 1}" 
                        style="font-size: 14px;line-height: 2;white-space: break-spaces;outline: none;word-break: break-word;display: inline-block;" 
                        v-html="urlCheck(comment.messages)">
                    </p>
                </div>
                <PostFiles v-if="comment.progress_files && comment.progress_files.length" :slidesCount="5" :items="comment.progress_files"/>
            </div>
            <div class="flex w-fit relative items-center gap-2">
                <div class="cursor-pointer mt-1" @click.stop="emoteAction">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18" height="19" viewBox="0 0 30 30" style="fill: var(--check-inactive)">
                        <path d="M14.977,0C6.735-0.056-0.127,6.93,0.002,15.153c-0.028,8.165,6.816,14.938,14.975,14.811v-0.04c0.967,0.013,1.936-0.067,2.889-0.242c4.817-0.863,9.055-4.275,10.937-8.8C32.985,11.039,25.688-0.021,14.977,0 M14.977,27.902C6.08,27.658-0.075,18.755,3.433,10.373C7.814,0.291,22.13,0.293,26.49,10.386C30.002,18.61,23.886,27.788,14.977,27.902"></path><path d="M22.441,18.263c-0.623-0.436-1.479-0.284-1.917,0.338c0.007-0.011,0.002-0.006-0.001-0.004c-0.002,0.002-0.006,0.005-0.011,0.01l-0.027,0.025c-0.734,0.658-1.568,1.264-2.479,1.639c-0.291,0.123-0.596,0.222-0.9,0.292c-0.67,0.185-1.332,0.349-2.043,0.376c-2.039,0.059-4.107-0.841-5.435-2.355c-1.226-1.563-3.443,0.199-2.196,1.769c0.199,0.27,0.418,0.529,0.646,0.772c1.784,1.911,4.359,3.094,6.986,3.106c1.119,0.021,2.305-0.08,3.354-0.525c1.753-0.72,3.36-1.896,4.362-3.526C23.214,19.556,23.063,18.698,22.441,18.263"></path><path d="M18.513,14.558c0.905,0.201,1.834-0.509,2.073-1.585c0.239-1.076-0.302-2.111-1.208-2.313c-0.904-0.201-1.833,0.509-2.072,1.585C17.065,13.322,17.606,14.357,18.513,14.558"></path><path d="M11.44,14.558c0.906-0.201,1.446-1.236,1.208-2.313c-0.239-1.076-1.167-1.786-2.074-1.585c-0.906,0.203-1.446,1.238-1.208,2.313C9.605,14.049,10.534,14.759,11.44,14.558"></path>
                    </svg>
                </div>
                <Transition name="downShiftPop">
                    <div class="w-max absolute p-4 bg-[var(--background-color)] z-10 top-[35px] shadow-xl" :id="`iokawaReactionPop_comment_${comment.id}`" v-if="menu.parent == `iokawaReactionPop_comment_${comment.id}`">
                        <div class="grid grid-cols-5 gap-2">
                            <div class="flex items-end justify-center transition-transform duration-200 ease-out hover:scale-105" v-for="oikawa in oikawaMap" :key="oikawa.name" @click="sendEmote(oikawa.name)">
                                <Character  :size="40" :emoteName="oikawa.name" :multiple="multiple"/>
                            </div>
                        </div>
                    </div>
                </Transition>
                <div class="mt-2" @click="setEmoteUsers(comment.emoted_users)" v-if="comment.emoted_users && comment.emoted_users.length">
                    <div class="flex items-end cursor-pointer text-[var(--primary-color)] flex-wrap">
                        <TransitionGroup name="downShiftPop">
                            <Character v-for="emote in emotes" :key="emote" :size="40" :emoteName="emote"/>
                        </TransitionGroup>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="clearBoth"></div>
        
    </div> 
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import Character from '@/components/Global/Character.vue'
import { defineAsyncComponent, ref, inject, computed } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from '@/store/menu'
import { DateParser, urlCheck, oikawaMap } from '@/utils/tools';
import { useApi } from '@/composables/api';
import { useModal } from '@/composables/modal';
import { PostComment } from '@/interface/postInterface';
import Error from '@/components/Global/Error.vue'
import PostFiles from './PostFiles.vue';
   const auth = useAuthUserStore()
    const menu = useMenuStore()
    const api = useApi()
    const { setEmoteUsers } = useModal()
    const Editor = defineAsyncComponent({ loader: () => import ('./Editor.vue'), errorComponent: Error })
    const multiple = computed(() => {
        if(window.innerWidth < 480) return 0.7
        return 1
    })
    const props = defineProps<{
        comment: PostComment
    }>()
    const emit = defineEmits<{
        deleteComment: [number],
        editComment: [PostComment],
        editCancel: [],
        editSend: [],
        updateComment: [PostComment],
    }>()
    const editing = ref(false)
    const pushInstantUser = inject('pushInstantUser') as Function

    const emotes = computed(() => {
        if (!props.comment.emoted_users?.length) return []
        return props.comment.emoted_users.map(item => item.pivot.emote_name)
    })
    const commentTypeLabel = computed(() => {
        if (props.comment.comment_type === 'progress_report') return '進捗報告'
        if (props.comment.comment_type === 'result') {
            const statusLabels: Record<number, string> = {
                1: '達成',
                2: '未達成',
                3: '中止',
            }
            const statusLabel = props.comment.status_to ? statusLabels[props.comment.status_to] : ''
            return statusLabel ? `結果報告：${statusLabel}` : '結果報告'
        }

        return ''
    })

    const emoteAction = () => {
        menu.setMenu({ parent: `iokawaReactionPop_comment_${props.comment.id}` })
    }

    const sendEmote = async (name: string) => {
        menu.close()
        const data = await api.post('/comment_send_emote', { id: props.comment.id, reaction: name })
        emit('updateComment', data)
    }

</script>
<style scoped>
.progress-report-label {
    display: inline-flex;
    align-items: center;
    padding: 2px 6px;
    font-size: 11px;
    line-height: 1.4;
    color: var(--sub-color);
    background: var(--bg3);
}
</style>
