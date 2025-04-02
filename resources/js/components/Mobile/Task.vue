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
                        <BoardTitlePre :item="board" titleStyle="font-weight:600;font-size:14px;line-height: 40px;" titleClass="board-title text"/>
                        <span style="font-weight:500;margin-left: 5px;"> / タスク</span>
                    </div>   
                </div>
            </div>
        </div> 
        <GanttTaskPopup v-if="board?.project" :from="'board'" :boardProject="board?.project"/>
        <TaskComponent v-else :from="'board'" :board="board" :maxInterval="totalSpan"/>
    </div>
</Transition>
</template>

<script setup>
import GanttTaskPopup from '../Task/Gantt/GanttTaskPopup.vue';
import TaskComponent from '../Task/TaskComponent.vue';
import BoardTitlePre from '../Board/Mixed/BoardTitle.vue'
import { inject, computed } from 'vue';
import { useRouter } from 'vue-router';
import { DateTime, Interval } from 'luxon';
import Back from '../Icons/Back.vue';

const board = inject('openedBoard')
const router = useRouter()
const totalSpan = computed(() => {
    let startPoint = DateTime.now().startOf('year');
    let endPoint = DateTime.now().plus({ year: 1 }).endOf('year');
    
    return Interval.fromDateTimes(startPoint, endPoint)    
})
</script>
