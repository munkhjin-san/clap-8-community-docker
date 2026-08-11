<template>   
    <header class="pd-header">
        <div class="pd-header__top">
            <button type="button" class="pd-back" title="プロジェクト一覧へ" @click="router.push({name: 'project'})">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 3 5 8l5 5" />
                </svg>
            </button>

            <div class="pd-header__title">
                <div class="pd-header__name-row">
                    <span class="pd-name">{{ selectedProject?.name }}</span>
                    <span class="pd-status">
                        <span class="pd-status__dot"></span>{{ PROJECT_STATUS_LABEL[selectedProject?.status ?? ''] ?? '不明' }}
                    </span>
                </div>
                <!-- 成果目標・人事考課など下位ルートに入ったときだけ、戻り先を示す。 -->
                <div v-if="pathGenerator.length > 1" class="pd-crumbs">
                    <template v-for="(item, index) in pathGenerator" :key="index">
                        <button type="button" class="pd-crumb" @click="jumpRoute(item)">{{ item.label }}</button>
                        <span v-if="index + 1 !== pathGenerator.length" class="pd-crumb__sep">／</span>
                    </template>
                </div>
            </div>

            <div v-if="hasPrivilage" class="pd-header__actions">
                <ItemMenu :items="[
                    { title: '編集する', action: () => editProjects(selectedProject) },
                    { title: '削除する', action: () => deleteProject(selectedProject) },
                ]" />
            </div>
        </div>

        <nav class="pd-tabs">
            <router-link
                v-for="tab in tabs"
                :id="tab.path == 'finance' ? 'financeSelection' : ''"
                :key="tab.name"
                :to="{ name: tab.path }"
                class="pd-tab"
                :class="{ 'pd-tab--active': tab.path ? route.fullPath.includes(tab.path) : false }"
            >
                <span class="pd-tab__name">{{ tab.name }}</span>
                <Badge v-for="b in badgesForTab(tab.path)" :key="b.key" v-show="b.value" :title="b.title" :style="badgeStyle(b.variant)" :count="b.value" />
            </router-link>
        </nav>
    </header>

    <div class="relative under960:mx-0 h-[calc(100%-88px)] overflow-hidden bg-[var(--background-color)]">
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
import { computed, inject, onMounted, provide, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Badge from '../Global/Badge.vue';
import ItemMenu from '../Global/ItemMenu.vue';
import { DateTime } from 'luxon';

    const props = defineProps(['userList', 'maxInterval', 'projects', 'ownProjectIds'])
    const route = useRoute()
    const router = useRouter()
    const api = useApi()
    const badge = useBadgeStore()
    const initialLoader = ref(false)
    const auth = useAuthUserStore()
    const { selectedProject, memberData, projectReportBadge, checkItemConfirmBadge, kintoneContractBadge } = useProject() 
    // 編集・削除は ProjectContainer が provide しているものをそのまま使う。
    const editProjects = inject('editProjects') as (project: any) => void
    const deleteProject = inject('deleteProject') as (project: any) => void

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
/*
 * ヘッダーとタブ。デザイン（Project page redesign）に合わせつつ、
 * 色はテーマ変数に置き換えている（#111/#fff 直書きだとダークモードで反転しないため）。
 */
.pd-header{
    padding: 18px 20px 0;
    border-bottom: 1px solid var(--calendarBorder);
    background: var(--background-color);
}
.pd-header__top{
    display: flex;
    align-items: flex-start;
    gap: 16px;
}
.pd-back{
    display: flex;
    margin-top: 4px;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--third-color);
    cursor: pointer;
}
.pd-back:hover{
    color: var(--primary-color);
}
.pd-header__title{
    flex: 1;
    min-width: 0;
}
.pd-header__name-row{
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.pd-name{
    font-size: 16px;
    letter-spacing: .01em;
    line-height: 1.4;
}
.pd-status{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: var(--primary-color);
    color: var(--background-color);
    font-size: 11.5px;
    white-space: nowrap;
}
.pd-status__dot{
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--background-color);
}
.pd-crumbs{
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
    font-size: 12px;
}
.pd-crumb{
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--third-color);
    font-size: inherit;
    cursor: pointer;
}
.pd-crumb:hover{
    color: var(--primary-color);
}
.pd-crumb__sep{
    color: var(--calendarBorder);
}
.pd-header__actions{
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 2px;
}

.pd-tabs{
    display: flex;
    margin-top: 18px;
    overflow-x: auto;
    scrollbar-width: none;
    margin-bottom: -1px;
}
.pd-tabs::-webkit-scrollbar{
    display: none;
}
.pd-tab{
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 0 10px 10px;
    border-bottom: 1px solid transparent;
    color: var(--third-color);
    font-size: 13.5px;
    white-space: nowrap;
    text-decoration: none;
}
.pd-tab:hover{
    color: var(--primary-color);
    text-decoration: none;
}
/* `is-active` は使わない。admin.css のグローバル指定
   （.is-active{background:#000 !important}）に乗っ取られてタブが黒く潰れる。 */
.pd-tab--active{
    border-bottom-color: var(--primary-color);
    color: var(--primary-color);
}
.pd-tab__name{
    font-size: 13.5px;
}

@media screen and (max-width: 959px){
    .pd-header{
        padding: 14px 15px 0;
    }
    .pd-name{
        font-size: 15px;
    }
}
</style>