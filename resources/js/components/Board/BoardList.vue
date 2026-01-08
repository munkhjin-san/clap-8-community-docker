<template>
    <div id="leftPanel" class="left-panel" :style="{height: 'calc(100% - 60px)'}"> 
        <div id="leftModal" style="height: 100%;display: flex;flex-direction: column;position:relative">                            
            <div id="searchContainer" class="left-panel-outer" ref="panelContainer" @scroll="(e) => emit('onScroll', e)">      
                <div 
                    :key="item.id" 
                    @mouseenter="boardListDropEnterFromFile(item)" 
                    @mouseleave="boardListDropLeaveFromFile(item)" 
                    class="left-panel-inner cursor-pointer" 
                    v-for="(item) in boardList"
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
            <SkeletonBoard v-if="skeletonLoader == 0"/>
                        
        </div>                    
    </div> 
</template>

<script setup lang="ts">
import BoardItem from './BoardItem.vue'
import SkeletonBoard from './SkeletonBoard.vue'
import { inject, ref, useTemplateRef } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'
import { useSharingDataStore } from '@/store/sharingData'
import FloatButton from '../Global/FloatButton.vue';
import AddIcon from '../Form/AddIcon.vue';
import { BoardMethodsKey, BoardMethods } from '@/interface/keys';
import { useBoardList } from '@/composables/board';
import { Message } from '@/interface/globalInterface';
import { useRoute } from 'vue-router';
    interface Props {
        failedMessagesList: Message[];
    }
    const emit = defineEmits<{
        (e: 'onScroll', event: Event): void
    }>()

    const props = defineProps<Props>();
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const { open, create } = inject(BoardMethodsKey) as BoardMethods
    const { boardList, skeletonLoader } = useBoardList()
    const bounceId = ref(null)
    const route = useRoute()
    const panelContainer = useTemplateRef('panelContainer')

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
