<template>
<div class="relative flex flex-col gap-2 min-h-[70px] daily-item-root" @mouseover="handleMouseOver" @mouseleave="handleMouseLeave" :id="`daily-expanded-${user.id}`">
    <div class="daily-edit-overlay" v-if="editable && !isEditing && user.id === auth.activeUser?.id">
        <div class="daily-edit-button" @click.stop="openEditor">
            <Edit size="15" color="white"/>
        </div>
        
    </div>

    <div class="mb-overlay" v-if="expanded" @click.stop="menu.close()"></div>
    <div :class="{'expanded': expanded}" ref="commentBox"  @click.stop="toggleComment">
        <div class="bg-[var(--background-color)]" :class="[expanded ? 'p-5 shade' : 'p-3']">
            <div class="flex items-center" v-if="comment">
                <UserPanel :user="user" size="25" with-name :disable-instant="!expanded"/>
                <WeatherIcon v-if="comment.value_int !== null" :which="comment.value_int" size="15" class="min-w-[15px] ml-1" />
            </div>
            
            <div class="cursor-pointer min-w-0 mt-[10px]" v-if="comment && !isEditing">
                <p v-if="!expanded" class="text-xs leading-snug line-clamp-2 min-w-0 text-ellipsis">
                    {{ comment.value_text ?? ''}}
                </p>
                <div v-else class="text-sm leading-normal break-words">
                    {{ comment.value_text ?? '' }}
                </div>
            </div>  
            <div v-if="isEditing" class="daily-inline-editor" @click.stop>
                <textarea
                    v-model="editText"
                    class="daily-edit-textarea"
                    rows="4"
                    maxlength="200"
                    placeholder="今日のひとことを入力"
                />
                <div class="daily-edit-actions">
                    <button type="button" class="daily-edit-btn ghost" @click.stop="cancelEdit" :disabled="editLoading">キャンセル</button>
                    <button type="button" class="daily-edit-btn" @click.stop="saveEdit" :disabled="editLoading || !editText.trim()">保存</button>
                </div>
            </div>
            <div class="mt-2 w-fit relative z-[15]" @click="setEmoteUsers(comment.emoted_users)" v-if="!isEditing && comment.emoted_users && comment.emoted_users.length && (!emoteArea || !expanded)">
                <div class="flex items-end cursor-pointer text-[var(--primary-color)] w-fit overflow-hidden">
                    <TransitionGroup name="downShiftPop">
                        <Character v-for="emote in emotes" :key="emote" :multiple="0.5" :emote-name="emote"/>
                    </TransitionGroup>
                </div>
            </div> 
        </div>


        <div v-if="expanded" class="bg-[var(--bg3)] grid grid-cols-5 shade p-3 gap-3">
            <div @click="sendEmote(oikawa.name)" v-for="oikawa in oikawaMap" :key="oikawa.name" class="flex items-end justify-center">
                <div>
                    <Character :emote-name="oikawa.name" :multiple="0.75" />
                </div>
                
            </div>
        </div>
    </div>
</div>  
</template>
<script setup lang="ts">
import { DailyMessageUser, User } from '@/interface/globalInterface';
import UserPanel from './UserPanel.vue';
import WeatherIcon from './WeatherIcon.vue';
import { useMenuStore } from '@/store/menu';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import Character from './Character.vue';
import { useApi } from '@/composables/api';
import { useModal } from '@/composables/modal';
import { useAuthUserStore } from '@/store/auth';
import Smile from '../Icons/Smile.vue';
import { oikawaMap } from '@/utils/tools';
import Edit from '../Icons/Edit.vue';

const props = defineProps<{
    user: DailyMessageUser
    leftEdge: number
    rightEdge: number
    isLast: boolean
}>()
const emit = defineEmits<{
    refresh: [data: DailyMessageUser]
}>()
const menu = useMenuStore()
const commentBox = useTemplateRef('commentBox');
const api = useApi();
const { setEmoteUsers } = useModal()
const auth = useAuthUserStore()
const emoteArea = ref(false);
const editable = ref(false)
const isEditing = ref(false)
const editText = ref('')
const editLoading = ref(false)
const comment = computed(() => {
    return props.user?.custom_field_data_records?.[0] || null
})

const expanded = computed(() => {
    return menu.parent === `daily-expanded-${props.user.id}`
})  

const toggleComment = () => {
    if (isEditing.value) return;
    if (props.user.id === auth.activeUser?.id) return;
    if(menu.parent && menu.parent.includes('daily-expanded-')){
        menu.close()
        return;
    }
    menu.setMenu({parent: `daily-expanded-${props.user.id}`})
    nextTick(() => {
        if(commentBox.value){
            const rect = commentBox.value.getBoundingClientRect();
            if(rect.right > props.rightEdge){
                commentBox.value.style.left = 'auto';
                commentBox.value.style.right = '-10px';
            } else if(rect.left < props.leftEdge){
                commentBox.value.style.left = '-10px';
                commentBox.value.style.right = 'auto';
            }
            commentBox.value.scrollIntoView({behavior: 'smooth', block: 'center'})
            if(props.isLast){
                setTimeout(() => {
                    scrollToBottom();
                }, 300)
            }
        }
    })
}
const scrollToBottom = () => {
    if(commentBox.value){
        commentBox.value.scrollIntoView({behavior: 'smooth', block: 'end'})
    }
}
const handleMouseOver = () => {
    if (isEditing.value) {
        editable.value = false
        return
    }
    editable.value = true
}

const handleMouseLeave = () => {
    editable.value = false
}

const openEditor = () => {
    if (props.user.id !== auth.activeUser?.id) return
    editable.value = false
    isEditing.value = true
    editText.value = comment.value?.value_text ?? ''
}

const cancelEdit = () => {
    isEditing.value = false
    editText.value = ''
}

const saveEdit = async () => {
    if (editLoading.value) return

    const message = editText.value.trim()
    if (!message) return

    editLoading.value = true
    const response = await api.post('/create_comment', { comment: message })
    if (response) {
        const nextRecords = props.user.custom_field_data_records?.length
            ? props.user.custom_field_data_records.map((record, index) => {
                if (index !== 0) return record
                return {
                    ...record,
                    ...response,
                    emoted_users: record.emoted_users ?? []
                }
            })
            : [{
                ...response,
                emoted_users: []
            }]

        emit('refresh', {
            ...props.user,
            custom_field_data_records: nextRecords
        })
        isEditing.value = false
    }
    editLoading.value = false
}
const fastPreCheckEmote = (name: string) => {
    if(!comment.value || !comment.value.emoted_users) return;
        // pretend to send emote api for fast response
    const checkExist = comment.value.emoted_users.find(ob => ob.id == auth.activeUser.id)
    if(checkExist){
        if(checkExist.pivot.emote_name == name) {
            const user:DailyMessageUser = {...props.user, custom_field_data_records: [{
                ...comment.value,
                emoted_users: comment.value.emoted_users.filter(ob => ob.id != auth.activeUser.id)
            }]}
            emit('refresh', user)
        }else{           

            const mutatedData = props.user.custom_field_data_records?.map(record => {
                if(record.id === comment.value?.id && record.emoted_users){
                    const newEmotedUsers = record.emoted_users.map(emotedUser => {
                        if(emotedUser.id === auth.activeUser.id){
                            return {
                                ...emotedUser,
                                pivot: {
                                    ...emotedUser.pivot,
                                    emote_name: name
                                }
                            }
                        }
                        return emotedUser;
                    });
                    return {
                        ...record,
                        emoted_users: newEmotedUsers
                    }
                }
                return record;
            }) || [];
            const mutatedDataUser: DailyMessageUser = {
                ...props.user,
                custom_field_data_records: mutatedData
            }
            emit('refresh', mutatedDataUser)
        }
        
        
    } else {
        emit('refresh', {
            ...props.user,
            custom_field_data_records: [{
                ...comment.value,
                emoted_users: [{
                    ...auth.activeUser as User,
                    pivot: {emote_name: name, message_id: comment.value.id, user_id: auth.activeUser.id }
                },...comment.value.emoted_users]
            }]
        })
    }
}
const sendEmote = async (emoteName: string) => {
    if(!comment.value) return;
    emoteArea.value = false;
    fastPreCheckEmote(emoteName);
    const data = await api.post('/create_custom_field_emote_user', {
        user_id: props.user.id,
        custom_field_data_record_id: comment.value.id,
        emote_name: emoteName
    });
    // emit('refresh', data)
}

const emotes = computed(() => {
    if(comment.value === null || comment.value.emoted_users === undefined || comment.value.emoted_users.length === 0) return [];
    return comment.value.emoted_users.map(item => item.pivot.emote_name)
})
</script>
<style scoped>
    .daily-item-root {
        transition: transform 0.2s ease;
    }
    .daily-edit-overlay {
        background: transparent;
        position: absolute;
        inset: 0;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease, background-color 0.2s ease;
    }
    .daily-item-root:hover .daily-edit-overlay {
        opacity: 1;
        /* background: #00000073; */
    }
    .daily-edit-button {
        border-radius: 999px;
        padding: 8px;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: transform 0.18s ease, background-color 0.18s ease;
        background: #00000052;
    }
    /* .daily-edit-button:hover {
        background: var(--bg2);
        transform: translateY(-1px) scale(1.03);
    } */
    .daily-inline-editor {
        margin-top: 10px;
    }
    .daily-edit-textarea {
        width: 100%;
        resize: vertical;
        min-height: 88px;
        border: 1px solid var(--check-inactive);
        background: var(--background-color);
        color: var(--primary-color);
        padding: 8px 10px;
        font-size: 13px;
        line-height: 1.5;
        transition: border-color 0.18s ease, box-shadow 0.2s ease;
        box-sizing: border-box !important;
    }
    
    .daily-edit-actions {
        margin-top: 8px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
    .daily-edit-btn {
        border: 1px solid var(--primary-color);
        background: var(--primary-color);
        color: var(--bg2);
        font-size: 12px;
        padding: 4px 10px;
        transition: opacity 0.18s ease, transform 0.18s ease;
    }
    .daily-edit-btn:not(:disabled):hover {
        transform: translateY(-1px);
    }
    .daily-edit-btn.ghost {
        background: transparent;
        color: var(--primary-color);
    }
    .daily-edit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .expanded {
        position:  absolute;
        left: -10px;
        top: -10px;
        width: 120%;
        height: max-content;
        z-index: 23;
        min-height: 100%;
    }
    .shade {
        box-shadow: #00000059 0 5px 15px;
    }
    .mb-overlay {
        display: none;
    }
    @media screen and (max-width: 959px) {
        .expanded {
            width: 100% !important;
            left: 0 !important;
            right: 0 !important;
        }
        .mb-overlay {
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000000a3;
            z-index: 22;
        }
    }
</style>