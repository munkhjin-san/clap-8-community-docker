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
                <div class="flex items-center">
                    <span
                        class="side-notification"
                        style="position: unset;width:15px;"
                        
                        v-if="checkItemConfirmBadge && tab.path == 'overview'"
                    >
                        {{ checkItemConfirmBadge }}
                    </span>
                    
                </div>
                <div v-for="b in badgesForTab(tab.path)" v-show="b.value" class="flex relative items-center justify-center font-normal" title="コメントバッジ">
                    <svg fill="#F28C28" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 30.88051 24.9735">
                        <path d="M30.72814,8.8769c-.14532-.82959-.40253-1.64972-.77496-2.4184-.37347-.76801-.86078-1.48114-1.43018-2.11041-.56958-.63019-1.21985-1.17505-1.91077-1.64008-.69165-.46552-1.42749-.84625-2.17938-1.16577-1.5072-.63647-3.08105-1.02167-4.65607-1.25201C18.1997.06067,16.61914-.02142,15.04528.00464c-1.57648.02826-3.16119.16687-4.73059.47339-1.56677.30853-3.12598.77979-4.58923,1.52222-.73016.37158-1.43451.81073-2.08917,1.32697-.65393.51624-1.25677,1.11188-1.7735,1.78302-.51813.66943-.9433,1.41797-1.25366,2.21051-.31232.7923-.4989,1.63013-.57269,2.46863-.03809.41821-.04175.84344-.03156,1.24939.01123.41052.04254.82294.0976,1.23492.11224.82324.32281,1.6463.65656,2.427.33209.7807.78845,1.51337,1.34021,2.15607.55261.64252,1.19427,1.19592,1.88171,1.6568,1.37878.92578,2.68457,1.41705,4.21594,1.83752,1.40436.38562,3.01337.61237,4.42383.68085.11499.00562.22223.05609.29999.14099.35828.39093.73218.8374,1.12903,1.18121.52246.45294,1.09735.87909,1.70001,1.23297.59595.34991,1.21814.62427,1.8606.87347.67725.2442,1.7251.4682,2.2804.51007.54651.0412.61255-.37128.435-.73407s-.21918-.43036-.29242-.58905c-.07404-.16064-.14563-.32257-.21429-.48541-.13745-.3255-.26355-.65436-.37738-.98267-.09088-.26556-.22833-.73004-.30035-1.09607-.02545-.12921.06171-.25269.19214-.27081,1.26611-.17621,2.52991-.42755,3.77478-.80463.76044-.23096,1.51337-.50958,2.24554-.85553.73206-.34485,1.44232-.76208,2.10303-1.26599.65881-.50543,1.26453-1.10352,1.7677-1.78918.25061-.34308.4754-.70667.67157-1.0849.19421-.37921.35907-.77295.49432-1.17499.26868-.80518.41492-1.64044.46771-2.46826.05145-.82404.01685-1.66162-.12994-2.49219Z" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-white text-[10px]">{{ b.value }}</span>
                </div>
                <!-- <div class="flex items-center gap-1">
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
                </div> -->
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
    const { selectedProject, memberData, projectReportBadge, checkItemConfirmBadge } = useProject() 
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
            // { key: "overview-confirm", title: "確認バッジ", variant: "confirm", value: checkItemConfirmBadge.value },
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