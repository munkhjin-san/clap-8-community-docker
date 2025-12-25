<template>
<div class="relative flex flex-col gap-2 min-h-[70px]" :id="`daily-expanded-${user.id}`">
    <div class="mb-overlay" v-if="expanded" @click.stop="menu.close()"></div>
    <div :class="{'expanded': expanded}" ref="commentBox"  @click.stop="toggleComment">
        <div class="bg-[var(--background-color)]" :class="[expanded ? 'p-5 shade' : 'p-3']">
            <div class="flex items-center" v-if="comment">
                <UserPanel :user="user" size="25" with-name :disable-instant="!expanded"/>
                <WeatherIcon v-if="comment.value_int !== null" :which="comment.value_int" size="15" class="min-w-[15px] ml-1" />
            </div>
            
            <div class="cursor-pointer min-w-0 mt-[10px]" v-if="comment">
                <p v-if="!expanded" class="text-xs leading-snug line-clamp-2 min-w-0 text-ellipsis">
                    {{ comment.value_text ?? ''}}
                </p>
                <div v-else class="text-sm leading-normal break-words">
                    {{ comment.value_text ?? '' }}
                </div>
            </div>  
            <div class="mt-2 w-fit" @click="setEmoteUsers(comment.emoted_users)" v-if="comment.emoted_users && comment.emoted_users.length && (!emoteArea || !expanded)">
                <div class="flex items-end cursor-pointer text-[var(--primary-color)] w-fit overflow-hidden">
                    <TransitionGroup name="downShiftPop">
                        <Character v-for="emote in emotes" :key="emote" :size="20" :emoteId="emote"/>
                    </TransitionGroup>
                </div>
            </div> 
        </div>


        <div v-if="expanded" class="bg-[var(--bg3)] grid grid-cols-5 shade p-3 gap-3">
            <div @click="sendEmote(num)" v-for="num in 10" :key="num" class="flex items-end justify-center">
                <div>
                    <Character :emote-id="num" :size="30" />
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

const props = defineProps<{
    user: DailyMessageUser
    leftEdge: number
    rightEdge: number
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
const comment = computed(() => {
    return props.user?.custom_field_data_records?.[0] || null
})

const expanded = computed(() => {
    return menu.parent === `daily-expanded-${props.user.id}`
})  

const toggleComment = () => {
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
        }
    })
}
const fastPreCheckEmote = (num) => {
    if(!comment.value || !comment.value.emoted_users) return;
        // pretend to send emote api for fast response
    const checkExist = comment.value.emoted_users.find(ob => ob.id == auth.activeUser.id)
    if(checkExist){
        if(checkExist.pivot.emote_id == num) {
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
                                    emote_id: num
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
                    pivot: {emote_id: num   }
                },...comment.value.emoted_users]
            }]
        })
    }
}
const sendEmote = async (emoteId: number) => {
    if(!comment.value) return;
    emoteArea.value = false;
    fastPreCheckEmote(emoteId);
    const data = await api.post('/create_custom_field_emote_user', {
        user_id: props.user.id,
        custom_field_data_record_id: comment.value.id,
        emote_id: emoteId
    });
    emit('refresh', data)
}

const emotes = computed(() => {
    if(comment.value === null || comment.value.emoted_users === undefined || comment.value.emoted_users.length === 0) return [];
    return comment.value.emoted_users.map(item => item.pivot.emote_id)
})
</script>
<style scoped>
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