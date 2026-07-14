<template>   
    <div>
        <div class="post-header">
            <div class="cursor-pointer" style="display: flex;align-items: center;height: 50px;position: sticky;top: 0;background: var(--bg2);">
                <div @click="router.push({name: 'project'})" style="height: 50px;width: 50px;min-width:50px;display: flex;justify-content: center;align-items: center;fill:var(--primary-color)">
                    <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                
                <div class="project-nav-bar">
                    <div @click="jumpRoute(item)" v-for="(item, index) in pathGenerator" class="flex items-center">
                        <span class="project-path">{{ item.label }}</span>
                        <span v-if="index + 1 !== pathGenerator.length">／</span>
                    </div>
                    <div class="ml-4 text-xs p-1 bg-[var(--bg3)]">
                        {{ PROJECT_STATUS_LABEL[selectedProject?.status ?? ''] ?? '不明' }}
                    </div>
                </div>
                
            </div>  
        </div>
        <div class="mx-[20px] flex whitespace-nowrap overflow-auto hide-scrollbar">
            <router-link :id="tab.path == 'finance' ? 'financeSelection' : ''" :to="{name : tab.path}" v-for="tab in tabs" :key="tab.name" class="tab tab-link flex items-center gap-[5px]" :class="{active: tab.path ? route.fullPath.includes(tab.path) : false}">

                <div class="tab-name">{{ tab.name }}</div>
                
                
                <div class="flex items-center gap-1">
                    <span
                        v-for="b in badgesForTab(tab.path)"
                        :key="b.key"
                        v-show="b.value"
                        class="side-notification"
                        :title="b.title"
                        :style="badgeStyle(b.variant)"
                    >
                        {{ b.value }}
                    </span>
                </div>
            </router-link>
        </div>
    </div>
    <div class="mx-[20px] relative under960:mx-0 h-[calc(100%-100px)] overflow-hidden bg-[var(--background-color)]">
        <div class="cal-month-loader" style="height: 100%; top: 0;" v-if="initialLoader">
            <div id="loaderMini">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </div>
        <RouterView 
            v-if="selectedProject"
            :user-list="userList"
            :has-privilage="hasPrivilage"
            :fileAccess="fileAccess"
            :totalBadge="financeCommentBadge"
            :key="`rt_${route.params.projectId}`"
        />
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { useProject } from '@/composables/project';
import { useTour } from '@/composables/useTour';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import { useTutorialStore } from '@/store/tutorial';
import { PROJECT_STATUS_LABEL } from '@/utils/tools';
import { computed, onMounted, provide, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

    const props = defineProps(['userList', 'maxInterval', 'projects', 'ownProjectIds'])
    const route = useRoute()
    const router = useRouter()
    const api = useApi()
    const badge = useBadgeStore()
    const initialLoader = ref(false)
    const auth = useAuthUserStore()
    const { selectedProject, memberData, projectReportBadge, checkItemConfirmBadge, kintoneContractBadge } = useProject() 
    const userId = computed(() => auth.activeUser?.id ?? auth.id ?? null);
    type Tab = { name: string; path: string };
    type BadgeVariant = "confirm" | "comment";

    type BadgeDef = {
        key: string;
        title: string;
        variant: BadgeVariant;
        value: any; // number/string
    };

    const badgeStyle = (variant: BadgeVariant) => ({
        position: "initial" as const,
        width: "15px",
        zIndex: 1,
        ...(variant === "comment" ? { backgroundColor: "#F28C28" } : {}),
    });

    const badgesByTab = computed<Record<string, BadgeDef[]>>(() => ({
        overview: [
            { key: "overview-confirm", title: "確認バッジ", variant: "confirm", value: checkItemConfirmBadge.value },
            { key: "overview-comment", title: "コメントバッジ", variant: "comment", value: projectReportBadge.value },
        ],
        "project-members": [
            { key: "members-confirm", title: "確認バッジ", variant: "confirm", value: goalBadges.value },
            { key: "members-comment", title: "コメントバッジ", variant: "comment", value: goalCommentBadge.value },
        ],
        "task-calendar": [
            { key: "task-comment", title: "コメントバッジ", variant: "comment", value: taskCommentBadge.value },
        ],
        finance: [
            { key: "finance-comment", title: "コメントバッジ", variant: "comment", value: financeCommentBadge.value },
        ],
        contracts: [
            { key: "contracts-confirm", title: "契約書更新", variant: "confirm", value: kintoneContractBadge.value },
        ],
    }));

    const badgesForTab = (path: string) => badgesByTab.value[path] ?? [];
    const fileAccess = computed<boolean>(() => {
        const sp = selectedProject.value;
        const uid = userId.value;

        if (uid == null) return false;

        return Boolean(
            sp?.manager?.some(m => m.id === uid) ||
            (auth.user?.position_id != null && auth.user.position_id < 6) ||
            uid === 610 || uid === 608 ||
            sp?.members?.some(member => member.id === uid)  
        );
    });
    const baseTabs: Tab[] = [
        { name: '概要', path: 'overview'},
        { name: 'メンバー', path: 'project-members'},
        { name: '業務マニュアル', path: 'operation'},
        { name: '契約書', path: 'contracts'},
        { name: '法務レビュー', path: 'legal'},
        // { name: 'アプリ', path: 'apps'},
        { name: '派遣', path: 'dispatch'},
        { name: '予算・実績', path: 'finance'},
        { name: 'ガントチャート', path: 'task-calendar'},
    ];

    const tabs = computed<Tab[]>(() => {
        const t = [...baseTabs];
        if (fileAccess.value) {
            t.push({ name: 'ファイルストレージ', path: 'file-storage' });
        }
        return t;
    });
    const goalBadges = computed(() => {
        return badge.goalsBadgeByFilter([{by: 'project_id', value: Number(route.params.projectId)}]).length + badge.salaryIssueByFilter([{by: 'project_id', value: Number(route.params.projectId)}]).length
    })
    const goalCommentBadge = computed(() => {
        return badge.goalIssueCommentBadgeByFilter([{by: 'project_id', value: Number(route.params.projectId)}]).length
    })
    const assetBadge = computed(() => {
        return badge.assetsBadgeByFilter([{by: 'project_id', value: Number(route.params.projectId)}]).length
    })
    const taskCommentBadge = computed(() => {
        return badge.taskCommentBadgeByFilter([{by: 'project_id', value: Number(route.params.projectId)}]).length
    })
    const financeCommentBadge = computed(() => {
        return badge.financeCommentBadgeByFilter({by: 'project_id', value: Number(route.params.projectId)})?.total_unread ?? 0
    })
    const hasPrivilage = computed(() => {
        return (selectedProject.value?.manager?.some(manager => manager.id === auth.id) || (auth.user?.position_id && auth.user?.position_id < 6) || auth.activeUser.id == 610 || auth.activeUser.id == 608) ? true : false
    })
    const pathGenerator = computed(() => {
        if(!selectedProject.value) return []
        const paths: { label: string; route: { name: string, params?: any } }[] = []
        const parent = {
            label: selectedProject.value ? selectedProject.value.name : '',
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
    provide('setLoader', (value: boolean) => {
        initialLoader.value = value
    })
    const tutorialStore = useTutorialStore()
    const { startTour } = useTour()  
    onMounted(() => {
        if(tutorialStore.state.active && tutorialStore.state.name.includes('project.details')){
            
            setTimeout(() => {
                startTour('project.details.finance', { version: '2025-09' });
            }, 200);
            tutorialStore.setTutorial({ active: true, name: ['project.details.finance'] });
        }
    })
</script>
<style scoped>
    .tab{
        padding: 0 10px;
        cursor: pointer;
        height: 40px;
    }
    .tab.active{
        background-color: var(--background-color);
        position: sticky;
        left: 0;
        right: 0;
        z-index: 2;
    }
    .tab-link{
        text-decoration: none;
        color: var(--primary-color);
    }
    .tab-link:hover{
        text-decoration: none;
        font-weight: normal;
    }
    .tab-name{
        font-size: 13px;
    }

</style>