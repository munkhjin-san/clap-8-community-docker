<template>
<div class="trayRoot" :class="['trayRootSmall']" :style="{zIndex: 21}">
    <div class="trayWrap"> 
        <div class="trayRightContainer">
            <div style="display:flex;align-items:center;font-size:12px;">
                <div title="タスク" @click="emit('setTrayItem', 1)" class="footerTabSelector" :class="{selectedMenu : trayItemWhich == 1}" style="position: relative;">
                    <svg class="appIcon" version="1.1" xmlns="http://www.w3.org/2000/svg" width="17" viewBox="0 0 37 32">
                        <path d="M36.297 0.493c-0.529-0.407-1.289-0.312-1.742 0.177l-2.463 2.656-2.479 2.698c-1.644 1.805-3.295 3.607-4.927 5.425-1.633 1.815-3.274 3.625-4.9 5.446-0.906 1.016-1.818 2.030-2.726 3.046-0.293 0.329-0.814 0.303-1.073-0.054-0.061-0.083-0.124-0.169-0.187-0.252l-0.538-0.737-1.64-2.19c-0.726-0.977-1.471-1.94-2.22-2.9l-1.134-1.428c-0.384-0.472-0.767-0.947-1.16-1.413-0.435-0.515-1.21-0.637-1.791-0.225-0.567 0.401-0.704 1.19-0.355 1.792 0.296 0.513 0.607 1.020 0.914 1.528l0.961 1.551c0.652 1.030 1.306 2.056 1.978 3.069l1.509 2.284 0.509 0.755c0.68 1.007 1.366 2.011 2.070 3.003l0.082 0.115c0.095 0.133 0.207 0.252 0.339 0.36 0.794 0.645 1.97 0.495 2.63-0.283 1.569-1.848 3.105-3.724 4.657-5.585 1.564-1.876 3.113-3.766 4.667-5.649 1.558-1.882 3.096-3.779 4.641-5.67l2.304-2.852 2.291-2.858c0.436-0.547 0.358-1.364-0.22-1.809z"></path>
                        <path d="M30.798 13.688c-0.736 0.045-1.297 0.682-1.307 1.417l-0.182 13.496c-0.004 0.298-0.247 0.532-0.545 0.527-1.719-0.029-3.439-0.041-5.158-0.055l-7.281-0.017-7.281-0.001-5.073 0.015c-0.257 0-0.465-0.21-0.462-0.466 0.019-1.7 0.019-3.398 0.019-5.098l-0.026-7.281-0.026-7.279-0.033-5.239c-0.001-0.21 0.168-0.38 0.378-0.381 1.558-0.010 3.114-0.023 4.671-0.031l20.184-0.204c0.809-0.008 1.46-0.691 1.409-1.517-0.046-0.754-0.701-1.326-1.457-1.334l-20.136-0.204c-2.244-0.012-4.486-0.037-6.729-0.038-0.915 0-1.66 0.739-1.667 1.655v0.010l-0.049 7.281-0.024 7.279-0.026 7.281c0 2.427 0 4.854 0.055 7.279l0.001 0.037c0.022 0.925 0.777 1.67 1.709 1.673l7.281 0.022 7.281-0.003 7.281-0.018c2.427-0.018 4.854-0.029 7.281-0.106l0.074-0.003c0.86-0.026 1.542-0.736 1.531-1.603l-0.212-15.725c-0.015-0.787-0.68-1.421-1.482-1.372z"></path>
                    </svg>
                    <p v-if="taskTotal" class="notification" style="top: 3px;right: calc(50% - 17px);z-index: 5;left: auto;top: 5px;height: 15px;min-width: 15px;font-size: 10px;">{{ taskTotal }}</p> 
                </div>
                <div title="ファイル" @click="emit('setTrayItem', 0)" class="footerTabSelector" :class="{selectedMenu : trayItemWhich == 0}">
                    <svg class="appIcon" width="17" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 32">
                        <path d="M38.918 8.499c-0.078-1.516-0.946-2.591-2.41-2.734-3.602-0.35-8.939-0.376-12.813-0.324-0.402 0-0.751-0.259-0.881-0.635-0.324-0.933-0.933-2.669-1.257-3.368-0.453-0.985-1.153-1.347-2.474-1.425-1.594-0.078-9.6 0.013-9.6 0.013h-5.377c-0.363 0.026-0.726 0.13-1.036 0.311s-0.596 0.428-0.803 0.725c-0.104 0.155-0.194 0.311-0.259 0.479l-0.052 0.13-0.013 0.104-0.039 0.117-1.244 3.628c-0.091 0.272-0.233 0.674-0.311 1.036-0.091 0.376-0.155 0.751-0.194 1.14-0.013 0.091-0.013 0.194-0.026 0.285v0.259l-0.026 0.492c-0.026 0.648-0.052 1.296-0.065 1.943-0.026 1.296-0.039 2.591-0.039 3.874 0.013 1.296 0.026 2.578 0.065 3.874 0.065 2.578 0.402 6.517 0.544 7.747s0.181 2.306 0.415 3.226c0.22 0.907 0.79 1.892 1.917 2.034 2.332 0.311 8.045 0.531 10.364 0.557 2.63 0.026 7.929 0.026 11.077-0.052 3.524-0.104 8.486-0.376 11.543-0.583 1.451-0.104 2.073-0.738 2.202-1.892 0.751-6.866 0.998-16.829 0.79-20.962zM22.892 5.441c-0.013 0.013 0 0 0 0zM19.291 29.474c-3.77 0.026-11.427-0.168-15.145-0.35-0.311-0.013-0.868-0.415-0.92-0.894-0.013-0.13-0.026-0.272-0.039-0.376l-0.155-1.879c-0.091-1.257-0.155-2.526-0.207-3.796s-0.078-2.539-0.104-3.809c-0.039-2.539-0.013-5.079 0.052-7.618 0.026-0.635 0.039-1.27 0.078-1.892l0.026-0.479 0.013-0.22c0-0.065 0.013-0.13 0.013-0.194 0.026-0.259 0.078-0.505 0.13-0.764 0.065-0.259 0.13-0.466 0.246-0.803l0.933-2.721 0.168-0.505c0.078-0.22 0.285-0.363 0.518-0.363l13.759 0.013c0.259 0 0.492 0.168 0.583 0.415l0.57 1.529 0.674 1.866c0.13 0.324 0.337 0.622 0.583 0.868s0.544 0.441 0.868 0.57c0.168 0.065 0.648 0.181 1.386 0.181 3.161-0.013 10.196 0.091 12.528 0.207 0.194 0.013 0.324 0.168 0.337 0.35 0.298 3.446 0.104 15.326-0.181 19.718-0.026 0.35-0.298 0.635-0.661 0.635-4.664 0.104-10.934 0.285-16.052 0.311z"></path>
                    </svg>
                </div>
            </div> 
            <div :style="{height: 'calc(100% - 35px)'}">
                <GanttTaskPopup v-if="trayItemWhich == 1 && board.project" :from="'board'" :boardProject="board.project"/>
                <TaskComponent v-else-if="trayItemWhich == 1" :from="'board'" :board="board" :maxInterval="totalSpan"/>
                <FileContainer v-if="trayItemWhich == 0" @jumpToMessage="jumpToMessage"
                />
            </div>            
        </div>  
    </div>
</div>
</template>
<script setup>

import TaskContainer from './Tray/Task/TaskContainer.vue'
import FileContainer from './Tray/File/FileContainer.vue'
import { computed, inject } from 'vue';
import GanttTaskPopup from '../Task/Gantt/GanttTaskPopup.vue';
import TaskComponent from '../Task/TaskComponent.vue';
import { useBadgeStore } from '@/store/badge'
import { DateTime, Interval } from 'luxon'
    const badge = useBadgeStore()
    const props = defineProps([ 'trayItemWhich'])
    const emit = defineEmits(['setTrayItem', 'jumpToMessage'])
    const board = inject('openedBoard')          

    const taskTotal = computed(() => {
        const nm = badge.task && board.value ? badge.task[board.value.id] : null
        return  nm > 99 ? '+99' : nm
    })

    const jumpToMessage = (file) => {
        emit('jumpToMessage', file)
    }    
    const totalSpan = computed(() => {
        let startPoint = DateTime.now().startOf('year');
        let endPoint = DateTime.now().plus({ year: 1 }).endOf('year');

        return Interval.fromDateTimes(startPoint, endPoint)
    })
</script>