<template>
    <div id="leftPanel" class="left-panel" :style="{height: 'calc(100% - 60px)'}"> 
        <div id="leftModal" style="height: 100%;display: flex;flex-direction: column;position:relative">                            
            <div id="searchContainer" class="left-panel-outer" ref="panelContainer">      
                <div 
                    :key="item.id" 
                    @mouseenter="boardListDropEnterFromFile(item)" 
                    @mouseleave="boardListDropLeaveFromFile(item)" 
                    class="left-panel-inner cursor-pointer" 
                    v-for="(item) in pinnedBoards"
                >                  
                    <BoardItem 
                        :item="item"               
                        :hasFailedMessage="failedMessageLen(item.id)"
                    />
                </div>
                <div 
                    :key="item.id" 
                    @mouseenter="boardListDropEnterFromFile(item)" 
                    @mouseleave="boardListDropLeaveFromFile(item)" 
                    class="left-panel-inner cursor-pointer" 
                    v-for="(item) in unPinnedBoards"
                >
                    <BoardItem 
                        :item="item"               
                        :hasFailedMessage="failedMessageLen(item.id)"
                    />
                </div>
                
                 <FloatButton 
                    v-if="auth.user && auth.user.partner_flag !== 1"
                    :hide-on="panelContainer"
                    @click="create()"
                >
                    <template #icon>
                        <AddIcon size="15" fill="black"/>
                    </template>
                 </FloatButton>
            </div>                           
            <SkeletonBoard v-if="skeleton.active == 0"/>
                        
        </div>                    
    </div> 
</template>

<script setup lang="ts">
import BoardItem from './BoardItem.vue'
import SkeletonBoard from './SkeletonBoard.vue'
import { computed, inject, ref, useTemplateRef } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'
import { useSharingDataStore } from '@/store/sharingData'
import { useSkeleton } from '@/store/skeleton';
import FloatButton from '../Global/FloatButton.vue';
import AddIcon from '../Form/AddIcon.vue';
import { BoardMethodsKey, BoardMethods } from '@/interface/keys';
import { useBoardList } from '@/composables/board';
import { Board, Message } from '@/interface/globalInterface';
import { useRoute } from 'vue-router';
    interface Props {
        failedMessagesList: Message[];
    }
    const props = defineProps<Props>();
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const skeleton = useSkeleton()
    const { open, create } = inject(BoardMethodsKey) as BoardMethods
    const { boardList } = useBoardList()
    const bounceId = ref(null)
    const route = useRoute()
    const panelContainer = useTemplateRef('panelContainer')
    const pinnedBoards = computed(() => {
        let res: Board[] = [];
        boardList.value.forEach((board, index) => {            
            let users = board.board_to_users  
            let pinned = users.filter( obj => obj.user_id == auth.id)
            if(pinned.length && pinned[0].pin_flag){
                res.push(board)
            }
        }); 
        return res
    })
    const unPinnedBoards = computed(() => {
        let res: Board[] = [];
        boardList.value.forEach((board, index) => {
            let users = board.board_to_users      
            let pinned = users.filter( obj => obj.user_id == auth.id)
            if(pinned.length && !pinned[0].pin_flag){                    
                res.push(board)
            }
        }); 
        return res
    })
    const boardListDropEnterFromFile = (board) => {
        if(responsive.mobile) return 
        bounceId.value = board.id
        if(sharingData.active && sharingData.drag){
            if(!route.params.chatId || Number(route.params.chatId) !== board.id){                        
                setTimeout(() => {
                    if(bounceId.value == board.id){
                        open(board)
                    }
                }, 400)
                
            }
        }
    }
    const boardListDropLeaveFromFile = (board) => {
        if(responsive.mobile) return 
        if(sharingData.active && sharingData.drag){
            bounceId.value = null
        }
    }

    const failedMessageLen = (id: number) => {
        return props.failedMessagesList.filter(ob => ob.record_id == id).length 
    }
    
    
</script>
