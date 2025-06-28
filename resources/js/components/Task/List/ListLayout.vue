<template>
    <div class="taskOuterContainerBg" style="position: relative; color: var(--primary-color)" >    
        <div class="min-h-[60px] flex items-center w-full justify-between" v-if="calendarHide">
            
            <TaskCategorizer 
                v-if="board"
                v-model:user="selectedUser" 
                v-model:status="selectedStatus"
                class="mr-[20px] ml-auto" 
                :user-options="board?.board_to_users.map(ob => ob.user)"  
                :statusOptions="categoryOptions"
                @update="getBoardTasks"
            />
            
        </div>
    
    
    
        <div ref="taskScrollContainer" style="height:100%;overflow: hidden scroll;position: relative;background:inherit">
            <div class="no-comment-text" v-if="!tasks.length" style="font-size:14px;">
                <p>現在アイテムはありません</p>
            </div>
            <div :class="{collapseTasks : !viewActiveTask}" class="task-list-wrap">                 
                
                <ListBox 
                    boxClass="task-box-container"
                    v-for="item in organizedTasks"
                    :key="item.id" 
                    :item="item"
                    :isBoard="isBoard"
                    @editTask="editTask"
                    @getBoardTasks="getBoardTasks"
                    @setActiveTask="(id) => activeTask = id"
                /> 
            </div>    
        </div>
        <FloatButton type="plus" @click="createTaskPopup = true">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>
        <BoardTaskCreate 
            v-if="createTaskPopup" 
            @close="closeTaskModal"
            :editTaskData="editTaskData"
            @getBoardTasks="getBoardTasks"
        />
        <Transition name="modalFade">
            <div class="cal-month-loader" v-if="initialLoader" style="height: 100%;top: 0;">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
    
    </div>
</template>
<script setup lang="ts">
import ListBox from './ListBox.vue'
import { computed, onMounted, ref, onUnmounted} from 'vue'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive'
import { Board, Task } from '@/interface/globalInterface'
import FloatButton from '@/components/Global/FloatButton.vue';
import BoardTaskCreate from '@/components/Board/Tray/Task/BoardTaskCreate.vue'
import TaskCategorizer from '../Gantt/TaskCategorizer.vue';
import { instance } from '@/utils/broadcaster';
import { useBadgeStore } from '@/store/badge';
import { useSharingDataStore } from '@/store/sharingData';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
    const props = defineProps<{
        board: Board | undefined
        isBoard: boolean
    }>()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const viewActiveTask = ref(true)
    const taskScrollContainer = ref<HTMLElement | null>(null)
    const activeTask = ref(null)
    const createTaskPopup = ref(false)
    const tasks = ref<Task[]>([])
    const initialLoader = ref(true)
    const editTaskData = ref<Task | null>(null)
    const selectedUser = ref<number | null>(auth.activeUser.id)
    const selectedStatus = ref<number>(-1)
    const sharingData = useSharingDataStore()
    const categoryOptions = [
        {value: -1, label: 'すべて'},
        {value: 0, label: '未対応'},
        {value: 1, label: '対応中'},
        {value: 2, label: '完了'},
    ]
    const calendarHide = ref(true)
    const activeListeners = new Set<string>();
    const badge = useBadgeStore()
    const api = useApi()
    onMounted(() => {  
        if (props.board) {
            instance.on(`task:${props.board.id}`, socketTaskHandler)
            activeListeners.add(`task:${props.board.id}`);
        }
        if (sharingData.active && sharingData.to == 'task') {
            createTaskPopup.value = true
        }
        getBoardTasks()
    })
    const clearListeners = () => {
        activeListeners.forEach(listener => {
            if (listener.startsWith('board:') || listener.startsWith('task:')) {
                instance.off(listener, socketTaskHandler);
                activeListeners.delete(listener);
            }
        });
    }
    onUnmounted(() => {
        clearListeners()
    })
    const socketTaskHandler = () => {
        getBoardTasks()
    }

    const editTask = (task:Task) => {
        const usersId = task.executors.map(ob => ob.id);
        const supervisorsId = task.supervisors.map(ob => ob.id);
        if (usersId.indexOf(Number(auth.activeUser.id)) > -1 || supervisorsId.indexOf(Number(auth.activeUser.id)) > -1) {
            editTaskData.value = task;
            createTaskPopup.value = true;
        }
    }
    const organizedTasks = computed(() => {
        const pinnedTasks = tasks.value.filter(task =>
            task.executors.some(executor => executor.id === auth.activeUser.id && executor.pivot.pin_flag === 1)
            || task.supervisors.some(supervisor => supervisor.id === auth.activeUser.id && supervisor.pivot.pin_flag === 1)
        );

        const unpinnedTasks = tasks.value.filter(task =>
            task.executors.some(executor => executor.id === auth.activeUser.id && executor.pivot.pin_flag !== 1)
            || task.supervisors.some(supervisor => supervisor.id === auth.activeUser.id && supervisor.pivot.pin_flag !== 1)
        );
        const notMyTasks = tasks.value.filter(task =>
            !task.executors.some(executor => executor.id === auth.activeUser.id)
            && !task.supervisors.some(supervisor => supervisor.id === auth.activeUser.id)
        );
        return [...pinnedTasks, ...unpinnedTasks, ...notMyTasks];
    })
    const getBoardTasks = async() => {
       
        const response = await api.get("/task_list", { record_id: props.board?.id, user_id: selectedUser.value, progress_flag: selectedStatus.value })
        tasks.value = response
        initialLoader.value = false

        
    }
    const closeTaskModal = (update: boolean) => {
        createTaskPopup.value = false
        if (update) {
            getBoardTasks();
            badge.getTaskBadge(); 
        }
    }


</script>
    