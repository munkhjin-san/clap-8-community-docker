<template>
    <div ref="scrollParentLocal" class="px-5 pb-5 overflow-auto h-full ">
        <div v-if="from !== 'dashboard'" class="sticky top-0 bg-[var(--background-color)] z-[2] h-[60px] flex items-center">
            <div class="text-sm border border-solid border-[var(--calendarBorder)] bg-[var(--bg3)] px-3 py-1 w-fit">
                <p class="under960:text-[12px]">現時点で達成評価点 ： <strong>{{ totalOverallScore }}</strong>点</p>
            </div>  
        </div>

        <div v-if="goals.length" id="goals-parent" class="grid gap-5 grid-cols-[repeat(auto-fill,minmax(clamp(0px,100%,350px),1fr))]">
            <MonthlyGoalItem
                v-for="item in goals" 
                :key="item.id" 
                :item="item"
                :project-id="activeProjectId"
                @edit-goal="(item) => {
                    editData = item
                    createWindow = true
                }"
            />            
        </div>  
        <div v-else>
            <div class="text-center text-sm text-[gray] py-3">
                成果目標が設定されていません。
            </div>
        </div>
        <Teleport to="body">
            <MonthlyGoalMoreDetail 
                v-if="selectedGoal && activeSpan" 
                :goal="selectedGoal" 
                :theme-records="[]" 
                :selected-date="activeSpan"
                :evaluation-data="null"
                
            />
        </Teleport>
        <Teleport to="body">
            <MonthlyGoalCreate 
                :edit-goal-data="editData" 
                v-if="activeSpan && activeUser && createWindow"
                :active-span="activeSpan" 
                :user-id="activeUser"

                @close="createWindow = false"
            />
        </Teleport>
        <FloatButton @action="checkCreate" class="fixed" :hide-on="scrollParent ? scrollParent : scrollParentLocal">
            <template #icon>
                <div v-if="searching" class="spinner-nano"></div>
                <AddIcon v-else/>
            </template>
        </FloatButton>
    </div>
</template>
<script setup lang="ts">
import { useDashboardGoalsStore } from '@/store/dashboardGoals';
import { storeToRefs } from 'pinia';
import { Project, ProjectGoal } from '@/interface/projectInterface';
import MonthlyGoalItem from './MonthlyGoalItem.vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import { useRoute } from 'vue-router';
import MonthlyGoalMoreDetail from './MonthlyGoalMoreDetail.vue';
import MonthlyGoalCreate from './MonthlyGoalCreate.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';

const props = defineProps<{
    userId?: number
    span?: string
    project?: Project
    from?: string
    scrollParent?: HTMLElement | null
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()
const api = useApi()
const route = useRoute()
const { askData, ask, ping } = useDialog()
const goalsStore = useDashboardGoalsStore()
const { goals, totalScore, evaluationData, totalOverallScore, loading } = storeToRefs(goalsStore)
const { getGoals } = goalsStore

const auth = useAuthUserStore()

const editData = ref<ProjectGoal | null>(null)
const createWindow = ref(false)
const searching = ref(false)
const scrollParentLocal = useTemplateRef('scrollParentLocal')
onMounted(() => {   
    if(activeProjectId.value && activeSpan.value && activeUser.value){
        const [ year, span ] = activeSpan.value.split('-') 
        if(year && span){
            getGoals(activeUser.value , Number(year), span)
        }        
    }    
})
const activeUser = computed(() => {
    return props.userId ? props.userId : route.params.memberId ? Number(route.params.memberId) : undefined
})

const activeSpan = computed(() => {
    return props.span ? props.span : route.params.span ? String(route.params.span) : undefined
})

const activeProjectId = computed(() => {
    return props.project ? props.project.id : route.params.projectId ? Number(route.params.projectId) : undefined
})

const selectedGoal = computed(() => {
    const itemId = route.params.itemId || route.params.goalId
    console.log('itemId', itemId)

    if(!itemId) return null

    return goals.value.find((goal) => goal.id === Number(itemId)) || null

})

const checkCreate = async() => {
    
    if(!activeUser.value || !activeSpan.value) return 
    if(activeUser.value == auth.activeUser.id || auth.isAdmin) {
        createGoal()
    } else if (auth.user.position_id == 6){
        searching.value = true
        const check = await api.get('/check_goal_create_permission', { user_id: activeUser.value })
        check === true ? createGoal() : deny()
        searching.value = false
    }else{
        deny()
    }
}
const createGoal = () => {
    createWindow.value = true
    editData.value = null
}

const deny = () => {
    ping('権限がありません。')
}

watch(() => route.query.span, (newVal) => {
    if(newVal && activeUser.value){
        const [ year, span ] = String(newVal).split('-') 
        if(year && span){
            getGoals(activeUser.value , Number(year), span)
        }        
    }else {
        getGoals(activeUser.value!, Number(activeSpan.value?.split('-')[0]), activeSpan.value?.split('-')[1]!)
    }
})

</script>