<template>
    <div class="routeposition" style="z-index: 24;">
        <Transition name="modalFade">
            <div class="cal-month-loader" style="height: calc(100% - 60px); top: 60px;" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        
       
        <div class="post-header">
            <div class="cursor-pointer" style="display: flex;align-items: center;height: 50px;position: sticky;top: 0;background: var(--bg2);">
                <div @click="router.go(-1)" style="height: 50px;width: 50px;min-width:50px;display: flex;justify-content: center;align-items: center;fill:var(--primary-color)">
                    <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                
                <div class="project-nav-bar">
                    <div @click="jumpRoute(item)" v-for="(item, index) in pathGenerator">
                        <span class="project-path">{{ item.label }}</span>
                        <span v-if="index + 1 !== pathGenerator.length">／</span>
                    </div>
                </div>
                
            </div>  
        </div>
        <div class="project-wrapper">
             <router-view v-slot="{ Component }">
                <component
                    :key="memberData"
                    :is="Component"
                    :selectedProject="selectedProject"
                    :memberData="memberData"
                    v-model="initialLoader"
                />
            </router-view>
            <!-- <RouterView 
                :selectedProject="selectedProject"
            /> -->
            <div class="project-detail" style="position: relative;white-space: break-spaces;line-height: 1.5;" v-if="route.name === 'projectdetail'">
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">プロジェクト名</div> 
                    <div class="flex items-center gap-2">
                        {{ selectedProject?.name }}
                        <WeatherIcon v-if="selectedProject?.project_conditions.length" :which="selectedProject.project_conditions[0].value" size="20"/>
                    </div>
                </div>
                
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">概要</div> 
                    <div class="leading-normal" v-html="sanitized(selectedProject?.overview)"></div>
                </div>
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">ミッション</div> 
                    <div class="leading-normal" v-html="sanitized(selectedProject?.mission)"></div>
                </div>
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">イノベーション</div> 
                    <div class="leading-normal" v-html="sanitized(selectedProject?.innovation)"></div>
                </div>
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">ソリューション</div> 
                    <div class="leading-normal" v-html="sanitized(selectedProject?.solution)"></div>
                </div>
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">オペレーション</div> 
                    <div class="leading-normal" v-html="sanitized(selectedProject?.operation)"></div>
                </div>
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">KPI</div> 
                    <div class="leading-normal">{{ selectedProject?.kpi }}</div>
                </div>
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px;">KGI</div>
                    <div class="leading-normal">{{ selectedProject?.kgi }}</div>
                </div>

                
                <div class="project-detail-header">
                    <div style="margin-bottom: 10px">期間</div>
                    <div v-if="selectedProject?.date_start">
                        {{ selectedProject?.date_start }} ～ {{ selectedProject?.date_end }}
                    </div>
                </div>
                <div class="project-detail-header" style="margin-bottom: 0;">
                    <div class="project-table">
                        <div class="project-header-row">
                            <div class="project-cell cell-width">メンバー</div>
                            <div class="project-cell cell-width">雇用形態</div>
                            <div class="project-cell cell-width">職階</div>
                            <div class="project-cell cell-width">メンター</div>
                            <div class="project-cell cell-width">職務レベル</div>
                            
                            <div class="project-cell cell-width">成果目標・昇給課題</div>
                            <div class="project-cell cell-width">人事考課</div>
                        </div>
                        <div class="project-cell-row" v-for="member in [...(selectedProject?.manager || []), ...(selectedProject?.members || [])]">
                            <div class="project-cell cell-width" data-label="メンバー">
                                <div style="position: relative; width: fit-content;">
                                    {{ member.name }}
                                </div>
                                
                            </div>
                            <div class="project-cell cell-width" data-label="雇用形態">{{ member?.positions?.name }}</div>
                            <div class="project-cell cell-width" data-label="職階">{{ member?.evaluation?.general_position }}</div>
                            <div class="project-cell cell-width" data-label="メンター">{{ member?.evaluation?.mentor?.name }}</div>
                            <div class="project-cell cell-width" data-label="職務評価基準">{{ member?.evaluation?.current_level }}</div>
                            
                            <div class="project-cell cell-width" style="position: relative;" data-label="成果目標・昇給課題">
                                <router-link class="user-link" :to="{name: 'outcomegoal', params: { projectId: route.params.projectId, memberId: member.id}}">閲覧</router-link>
                                <span class="side-notification from-left" v-if="goalBadge?.[selectedProject?.id]?.[member?.id]">{{ goalBadge[selectedProject.id][member.id] }}</span>
                            </div>
                            <div class="project-cell cell-width" data-label="人事考課">
                                <div>
                                    <router-link class="user-link" :to="{name: 'evaluation', params: { projectId: route.params.projectId, memberId: member.id }}">閲覧</router-link >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="isManagerOrDirector" style="position: absolute; top: 20px; right: 20px;">
                    <ItemMenu :items="[
                        {title: '編集する', action: () => emit('edit', selectedProject)},
                    ]"/>
                </div>
                <div class="flex gap-[10px] mt-[20px] w-fit">
                    <router-link :to="{name: 'assets', query: { project_id: route.params.projectId }}" class="c-bar-button">物品</router-link>
                    <div class="relative">
                        <router-link :to="{name: 'projectGanttDetail', params: {projectId: route.params.projectId }}" class="c-bar-button">ガントチャート</router-link>
                        <span class="side-notification" style="left: auto; right: -5px; top: -5px;" v-if="ganttBadge?.[selectedProject?.id]">{{ ganttBadge[selectedProject.id] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import CommandButton from '../Global/CommandButton.vue';
import { useAuthUserStore } from '@/store/auth';
import { RouteLocationMatched } from 'vue-router';
import ItemMenu from '../Global/ItemMenu.vue';
import ProjectEdit from './ProjectEdit.vue';
import { useBadgeStore } from '@/store/badge';
import WeatherIcon from '../Global/WeatherIcon.vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
    const props = defineProps(['selectedProject', 'userList'])
    const router = useRouter()
    const route = useRoute()
    const initialLoader = ref(false)
    const auth = useAuthUserStore()
    const editWindow = ref(false)
    const badge = useBadgeStore()
    const emit = defineEmits(['edit'])

    const isManagerOrDirector = computed(() => {
        return props.selectedProject?.manager?.some(manager => manager.id === auth.id) || (auth.user?.position_id && auth.user?.position_id < 6)
    })
    const memberData = computed(() => {
        const memberId = route.params.memberId
        if (memberId){
            return props.selectedProject?.members.find(ob => ob.id == memberId) || props.selectedProject?.manager.find(ob => ob.id == memberId)
        }
        
    })
    const goalBadge = computed(() => {
        return badge.project.goal_counts || {}
    })
    const ganttBadge = computed(() => {
        return badge.project.by_projects || {}
    })
    const jumpToGoal = (member: any) => {
        router.push({name: 'outcomegoal', params: { projectId: route.params.projectId, memberId: member.id}})
    }
    const jumpToEvaluation = (member: any) => {
        router.push({name: 'evaluation', params: { projectId: route.params.projectId, memberId: member.id }})
    }
    const sanitized = (text: string) => {
        const clean = text ?? ''
        if(!clean) return ''
        const markedText = marked.parse(clean) as string
        const saveText = DOMPurify.sanitize(markedText)
        return saveText
    }
    const pathGenerator = computed(() => {
        const paths: { label: string; route: { name: string, params?: any } }[] = []
        const parent = {
            label: props.selectedProject ? props.selectedProject.name : '',
            route: { name: 'projectdetail', params: { projectId: route.params.projectId } }
        }
        const outecome = {
            label: `成果目標・昇給課題 : ${memberData.value?.name}`,
            route: { name: 'outcomegoal', params: { projectId: route.params.projectId, memberId: memberData.value?.id } }
        }
        const evaluation = {
            label: `人事考課 : ${memberData.value?.name}`,
            route: { name: 'evaluation', params: { projectId: route.params.projectId, memberId: memberData.value?.id } }
        }

        paths.push(parent)
        if(route.matched.some(rt => rt.name === 'outcomegoal')){            
            paths.push(outecome)         
            if(route.params.span){
                const span = route.params.span as string
                const [year, half] = span.split('-')
                const name = `${year}年${half == 'first' ? '上期' : '下期'}`
                paths.push({
                    label: name,
                    route: { name: 'outcomegoal', params: { projectId: route.params.projectId, memberId: memberData.value?.id } }
                })
            }   
        }
        if(route.matched.some(rt => rt.name === 'evaluation')){            
            paths.push(evaluation)    
            if(route.params.span){
                const span = route.params.span as string
                const [year, half] = span.split('-')
                const name = `${year}年${half == 'first' ? '上期' : '下期'}`
                paths.push({
                    label: name,
                    route: { name: 'evaluation', params: { projectId: route.params.projectId, memberId: memberData.value?.id } }
                })
            }          
        }
        
        return paths
    }); 
    const jumpRoute = (item: any) => {
        if(item.route.name === 'psuedo') return
        router.push(item.route)
    }
</script>
<style lang="scss">
.from-left {
    left: 55px !important;
}
.project-nav-bar{
    display: flex;
    width: calc(100% - 50px);
    overflow: auto hidden;
    height: 100%;
    align-items: center;
}
.project-nav-bar::-webkit-scrollbar {
  width: 0.0; /* Adjust as needed */
  height: 0;
}

.project-nav-bar::-webkit-scrollbar-track {
  background-color: transparent; /* Make the track invisible */
}

.project-nav-bar::-webkit-scrollbar-thumb {
  background-color: transparent; /* Hide the thumb */
}
.routeposition{
    position:absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 100%;
    z-index: 6;
    background: var(--bg2);
    color:var(--primary-color);
}
.project-wrapper{
    margin: 0 20px;
    height: calc(100% - 60px);
    overflow: hidden auto;
    scrollbar-width: none;
    font-size: 14px;
}
.project-detail{
    background-color: var(--background-color);
    padding: 20px;
    margin-bottom: 20px;
    line-height: 2.5;
}
.project-detail-header{
    line-height: normal;
    margin-bottom: 20px;
}
.cell-width {
    width: 100px;
}
.project-path {
    font-size: 16px;
}
.kadaiSwitch{
        padding: 10px;
        background: var(--background-color);
        cursor: pointer;
        font-size: 13px;
    }
    .kadai-content{
        white-space: break-spaces;
        line-height: 2;
    }
    .kadai-active{
        background: var(--bg3);
    }
    .member-wrap{
        display: flex;
        gap: 10px;
        align-items: center;
        width: fit-content;
        padding: 10px;
    }
    .goals-wrap{
        /* display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px; */
        height: 100%;
    }
    .goals-inner {
        overflow: hidden auto;
        height: 100%;
        background: var(--background-color);
        padding: 0 20px;
    }
    .goal-detail{
        background-color: var(--bg3);
        line-height: 1.5;
        word-break: break-word;
        white-space: break-spaces;
        padding: 10px;
        margin-bottom: 30px;
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    .kadaiCreate{
        width: 90% !important;
        height: 90%!important;
    }
    .kadai-root{
        width: 100%;
        height: auto;
        left: 0;
        top: 0;
        font-size: 14px;
        line-height: 1.5;
    }
    @media screen and (max-width: 959px) {
        .from-left {
            left: auto !important;
            right: -17px;
        }
        
        .kadaiCreate{
            width: 100% !important;
            height: calc(100% - 20px) !important;
        }
        .cell-width {
            width: 100%;
            
        }
        .project-path {
            font-size: 14px;
        }
        .cell-width::before {
            content: attr(data-label);
            // font-weight: bold;
            flex: 1;
            color: var(--secondary-color);
            margin-right: 10px;
            text-transform: capitalize;
        }
    }
</style>