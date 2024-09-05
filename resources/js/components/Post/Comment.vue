<template>
    <div :key="comment.id" class="commentRoot" >
        <div :class="['commentInner']" :style="{position:'relative',padding:'15px',borderRadius: '0', border: editing ? 'solid 2px var(--hoverBorder)' : 'solid 2px transparent', boxSizing: 'border-box', float: comment.user_id == auth.id ? 'right' : 'left'}">
            <div class="message-top-block" style="margin-bottom: 0;">      
                <div style="display: flex;align-items: center;gap:10px">
                    <UserIcon size="30" :user="comment.user" imgClass="userNormalIcon"/>                   
                    <div @click.stop="pushInstantUser($event, comment.user_id)" class="cursor-pointer" style="font-size: 14px;">{{ comment?.user?.name }}</div>     
                </div>     
                <div class="m-date">{{momentMessage}}</div> 
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
            <ClapButton :item="comment" @updateClap="reload()" appName="comment"/>                                                                                     
        </div>
        <div class="clearBoth"></div>
    </div> 
</template>
<script setup>
import UserIcon from '../Board/Mixed/UserIcon.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue'
import ClapButton from './ClapButton.vue';
import moment from 'moment';
import { computed, defineAsyncComponent, ref, inject } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { urlCheck } from '@/utils/tools';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const Editor = defineAsyncComponent(() => import ('./Editor.vue'))
    const props = defineProps(['comment'])
    const emit = defineEmits(['deleteComment', 'editComment', 'editCancel', 'editSend'])
    const editing = ref(false)
    const pushInstantUser = inject('pushInstantUser')
    const reload = inject('reload')
    const momentMessage = computed(() => {
        moment.locale('ja')
        const date = props.comment.created_at
        return moment(props.comment.created_at).isSame(moment(), 'day') ? 
        moment(date).format('HH:mm') : 
        moment(date).isSame(moment(), 'year') ? 
        moment(date).format('M / D (ddd) HH:mm') : 
        moment(date).format('YYYY / M / D (ddd) HH:mm')                       
    })

    const closeMenu = () =>{
        menu.setMenu( { name: '', id : null})
    }   

</script>