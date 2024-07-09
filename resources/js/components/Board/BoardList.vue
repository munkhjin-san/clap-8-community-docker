<template>
    <div id="leftPanel" class="left-panel" :style="{height: 'calc(100% - 60px)'}"> 
        <div id="leftModal" style="height: 100%;display: flex;flex-direction: column;position:relative">                            
            <div id="searchContainer" class="left-panel-outer" @scroll="scrollListen">      
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
                
                <BoardCreateButton v-if="auth.activeUser && auth.activeUser.partner_flag !== 1" :createHidden="createHidden"/>
            </div>                           
            <SkeletonBoard v-if="skeleton.active == 0"/>
                        
        </div>                    
    </div> 
</template>

<script setup>
import BoardItem from './BoardItem.vue'
import SkeletonBoard from './SkeletonBoard.vue'
import BoardCreateButton from './BoardCreateButton.vue'
import { computed, inject, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'
import { useSharingDataStore } from '@/store/sharingData'
import { useSkeleton } from '@/store/skeleton';
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const skeleton = useSkeleton()
    const props = defineProps(['list', 'failedMessagesList'])
    const { open } = inject('boardItem')
    const scrollPosition = ref(0)
    const createHidden = ref(false)
    const bounceId = ref(null)
    const openedBoard = inject('openedBoard')
    
    const pinnedBoards = computed(() => {
        let res = []
        props.list.forEach((board, index) => {            
            let users = board.board_to_users  
            let pinned = users.filter( obj => obj.user_id == auth.activeUser.id)
            if(pinned.length && pinned[0].pin_flag){
                res.push(board)
            }
        }); 
        return res
    })
    const unPinnedBoards = computed(() => {
        let res = []
        props.list.forEach((board, index) => {
            let users = board.board_to_users      
            let pinned = users.filter( obj => obj.user_id == auth.activeUser.id)
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
            if(!openedBoard || openedBoard.id !== board.id){                        
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
    const scrollListen = (event) => {
        var percent = 100 * event.target.scrollTop / (event.target.scrollHeight - event.target.clientHeight);       
        if(event.target.scrollTop < 0){
            createHidden.value = false            
        }else if(percent > 98){            
            createHidden.value = true
        }else{                 
            createHidden.value = event.target.scrollTop > scrollPosition.value
            scrollPosition.value = event.target.scrollTop;
        }
    }
    const failedMessageLen = (id) => {
        return props.failedMessagesList.filter(ob => ob.record_id == id).length 
    }
    
    
</script>
