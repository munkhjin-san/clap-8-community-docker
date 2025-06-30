<template>
<Transition :name="'smLoad'">
    <div class="mobileMessageWrap">
        <div class="boardHeader" style="border-bottom:none;max-width: 100%;overflow: hidden;height:40px;box-shadow: rgba(0, 0, 0, 0.04) 0px 3px 5px;position:unset;">
            <div class="mb-header">
                <div @click="router.go(-1)"  style="width: 40px;
                    height: 40px;
                    min-width: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-left:5px;">      
                                                    
                    <Back/>                                       
                </div>
                <div style="max-width: calc(100% - 60px)">
                    <div style="font-weight:600;font-size:14px;line-height: 40px;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;display:flex">
                        <BoardTitlePre v-if="openedBoard" :item="openedBoard" titleStyle="font-weight:600;font-size:14px;line-height: 40px;" titleClass="board-title text"/>
                        <span style="font-weight:500;margin-left: 5px;"> / フォーム</span>
                    </div>   
                </div>
            </div>
        </div> 
        <div class="w-full h-[calc(100%-40px)]">
            <FormContainer />
        </div>
        
    </div>
</Transition>
</template>

<script setup lang="ts">
import BoardTitlePre from '../Board/Mixed/BoardTitle.vue'
import { useRouter } from 'vue-router';
import Back from '../Icons/Back.vue';
import { useBoardList } from '@/composables/board';
import FormContainer from '../Board/Tray/Form/FormContainer.vue';

const { openedBoard } = useBoardList()
const router = useRouter()
</script>
