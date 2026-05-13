<template>
    <div class="h-[calc(100%-75px)] relative overflow-y-auto" ref="scrollContainer" @scroll="handleScroll"> 
        <div class="project-detail flex flex-col gap-[15px]" :class="{'pb-[70px]': hasPrivilage && (auth.isBoss || auth.isAdmin) && selectedProject?.status == 'pending_director'}">
            <div v-if="hasPrivilage" class="ml-auto sticky top-0 z-10">
                <div class="flex gap-4 items-center">
                    <!-- Desktop: inline approval buttons -->
                    <div v-if="(auth.isBoss || auth.isAdmin) && selectedProject?.status == 'pending_director'" class="hidden sm:flex gap-4">
                        <button @click="statusChange('director_approved')" class="bg-[var(--primary-button)] text-white p-1">
                            承認する
                        </button>
                        <button @click="statusChange('returned')" class="bg-[var(--primary-button)] text-white p-1">
                            差し戻し
                        </button>
                    </div>
                    <ItemMenu :items="[
                        {title: '編集する', action: () => editProjects(selectedProject)},
                        {title: '削除する', action: () => deleteProject(selectedProject)}
                    ]"/>
                </div>
            </div>
            <!-- Mobile: sticky bottom bar for approval buttons only -->
            <Teleport to="body">
                <div v-if="hasPrivilage && (auth.isBoss || auth.isAdmin) && selectedProject?.status == 'pending_director'"
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50 flex border-t border-[var(--calendarBorder)] bg-[var(--bg1)]">
                    <button @click="statusChange('director_approved')" class="flex-1 py-3 text-sm font-medium bg-[var(--primary-button)] text-white">
                        承認する
                    </button>
                    <button @click="statusChange('returned')" class="flex-1 py-3 text-sm font-medium bg-[var(--bg2)] text-[var(--primary-color)]">
                        差し戻し
                    </button>
                </div>
            </Teleport>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">部門</span> {{ selectedProject?.is_new ? '新規' : '既存' }}</div>
                </div> -->
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">プロジェクト種別</span> {{ selectedProject?.projectType?.label ?? selectedProject?.project_type?.label ?? '未設定' }}</div>
                </div>
                <div class="project-detail-header" :class="{'text-[tomato]' : checkItemConfirmBadge}">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px] text-[var(--primary-color)]">ステータス</span>{{ PROJECT_STATUS_LABEL[selectedProject?.status ?? ''] ?? '不明' }}</div>

                </div>
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">プロジェクト期間</span> {{ selectedProject?.date_start && selectedProject.date_end ? `${DateTime.fromISO(selectedProject.date_start).toLocaleString(DateTime.DATE_SHORT)}  ~  ${DateTime.fromISO(selectedProject.date_end).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}</div>

                </div>
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">契約開始日</span> {{ selectedProject?.contract_started_at ? `${DateTime.fromISO(selectedProject.contract_started_at).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}</div>

                </div>
                <!-- <div class="project-detail-header">
                </div> -->
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">サービスカテゴリー</span>
                        <div v-if="selectedProject?.category && selectedProject.category.length" class="flex flex-col gap-[15px] mt-[15px]">
                            <div v-for="cat in selectedProject?.category">
                                <div>{{ ProjectServiceCategories.find( c => c.value == cat)?.title }}</div>
                                <div class="text-[12px] text-[gray] mt-[5px]">{{ ProjectServiceCategories.find( c => c.value == cat)?.subtitle }}</div>
                            </div>
                        </div>
                        <span v-else>{{ selectedProject?.category && selectedProject.category.length ? selectedProject.category.join("、") : '未設定' }}</span>
                    </div> 
                </div>
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">顧客企業</span>
                        <span >{{ selectedProject?.customers && selectedProject.customers.length ? selectedProject.customers.join("、") : '未設定' }}</span>
                    </div> 
                </div>
                <!-- <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">パートナー企業</span>
                        <span >{{ selectedProject?.partners && selectedProject.partners.length ? selectedProject.partners.join("、") : '未設定' }}</span>
                    </div> 
                </div> -->
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">業種区分</span>
                        <span >{{ selectedProject?.industry_type && selectedProject.industry_type.length ? selectedProject.industry_type.join("、") : '未設定' }}</span>
                    </div> 
                </div>

                <div v-if="hasPrivilage" class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">PM用非公開メモ</span></div> 
                    <div class="leading-normal mt-[10px]">
                        <div v-html="displayHtml"></div>

                        <div
                            v-if="isTruncated"
                            @click="toggleFull"
                            class="mt-[10px] cursor-pointer text-sm"
                            role="button"
                            :aria-expanded="isExpanded ? 'true' : 'false'"
                        >
                            <CommandButton :buttons="[{title: isExpanded ? '閉じる' : '続きを表示する', action:() => ''}]"/>

                        </div>
                    </div>
                </div> 

                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">概要</span></div> 
                    <div class="leading-normal mt-[10px]" v-html="sanitized(selectedProject?.description ?? '')"></div>
                </div> 

                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">ミッション</span></div> 
                    <div class="leading-normal mt-[10px]" v-html="sanitized(selectedProject?.mission ?? '')"></div>
                </div>
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">イノベーション</span></div> 
                    <div class="leading-normal mt-[10px]" v-html="sanitized(selectedProject?.innovation ?? '')"></div>
                </div>
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">ストラテジー</span></div> 
                    <div class="leading-normal mt-[10px]" v-html="sanitized(selectedProject?.strategy_miso ?? '')"></div>
                </div>
                <div class="project-detail-header">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">オペレーション</span></div> 
                    <div class="leading-normal mt-[10px]" v-html="sanitized(selectedProject?.operation ?? '')"></div>
                </div> 
                <!-- <div class="project-detail-header" v-if="isManager || auth.isBoss || auth.isAdmin">
                    <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">年間収支計画（概要）</span></div> 
                    <div class="leading-normal mt-[10px] space-y-3">
                        <div class="flex justify-between">
                            <span>売上高</span>
                            <span>{{ yenFmt(planData?.revenue) }}</span>
                        </div>
                        <div v-for="expense in EXPENSE_ITEMS" class="flex justify-between">
                            <span>{{ expense.label }}</span>
                            <span>{{ yenFmt(planData?.[expense.key]) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>年間利益（見込み）</span>
                            <span
                                :class="profit >= 0 ? 'text-profit-positive' : 'text-profit-negative'"
                            >{{ yenFmt(profit) }}</span>
                        </div>
                        <div class="flex flex-col gap-2" v-if="planData?.remarks">
                            <span>備考</span>
                            <span>{{ planData?.remarks }}</span>
                        </div>
                    </div>
                </div> -->
            </div>
            <div>
                <MessageArea 
                    type="詳細"
                    :passing-data="passingData"
                    :item="{
                        ...selectedProject,
                        reports: selectedProject?.reports?.filter(report => report.type === '詳細') ?? []
                    }"
                    @refresh="updateProject([
                        { name: 'reports', include: ['user', 'files'] },
                    ])"
                />
            </div>
            
                                  
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, inject, onMounted, ref, useTemplateRef, watch } from 'vue';
import ItemMenu from '../../../Global/ItemMenu.vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { DateTime } from 'luxon';
import { useProject } from '@/composables/project';
import ProjectServiceCategories from 'assets/ProjectServiceCategories.json'
import CommandButton from '@/components/Global/CommandButton.vue';
import { Project } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { useRoute } from 'vue-router';
import { EXPENSE_ITEMS, parseNumber, PROJECT_STATUS_LABEL, yenFmt } from '@/utils/tools';
import { useBadgeStore } from '@/store/badge';
import { useDashboardStore } from '@/store/dashboard';
import MessageArea from '../../MessageArea.vue';
    const props = defineProps(['hasPrivilage'])
    const editProjects = inject('editProjects') as (project: any) => void
    const deleteProject = inject('deleteProject') as (project: Project | null) => void
    const { selectedProject, updateProject, checkItemConfirmBadge, readProjectMessage } = useProject()
    const auth = useAuthUserStore()
    const checkTab = ref(false)
    const passingData = {
        path: '/project_checkitem_comment_add',
        title: 'メッセージ',
        file_path: 'project_checkitem_report_files'
    }
    const sanitized = (text: string) => {
        const clean = text ?? ''
        if(!clean) return '未設定'
        const markedText = marked.parse(clean) as string
        const saveText = DOMPurify.sanitize(markedText)
        return saveText
    }
    const { getBatchDashboardData } = useDashboardStore()
    const fullHtml = computed(() => sanitized(selectedProject?.value?.private_memo ?? ''));
    const isExpanded = ref(false);
    const isTruncated = ref(false);
    const excerptHtml = ref('');
    const route = useRoute()
    const scrollRef = useTemplateRef('scrollContainer')
    onMounted(() => {
        // if(memoBody.value && memoBody.value?.clientHeight > 42){
        //     dynamicHeight.value = '42px'
        // }
        if (route.query.check) {
            checkTab.value = String(route.query.check) === 'true'
            const container = scrollRef.value
            if (container) {
                setTimeout(() => {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    }); 
                }, 100);
                
            }
        }   
    })
    const handleScroll = (event: Event) => {
        const el = event.target as HTMLElement

        const isBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10; // 10px threshold
        if (isBottom) {
            readProjectMessage('詳細')
        }
    }
    const planData = computed(() => {
        const raw = selectedProject.value?.specs?.plan_data
        if (!raw) return
        let parsed: any = raw
        if (typeof raw === 'string') {
            try {
                parsed = JSON.parse(raw)
            } catch {
                return
            }
        }
        if (!parsed || typeof parsed !== 'object') return
        return {
            ...parsed,
            lease: parsed.lease ?? parsed.leasing
        }
    })
    const totalExpenses = computed(() => EXPENSE_ITEMS.reduce((s, i) => s + parseNumber(planData.value?.[i.key]), 0) )
    const totalRevenue = computed(() => parseNumber(planData.value?.revenue))
    const profit = computed(() => {
        return totalRevenue.value - totalExpenses.value
    })
    const displayHtml = computed(() => isExpanded.value ? fullHtml.value : excerptHtml.value);
    const toggleFull = () => { isExpanded.value = !isExpanded.value; };
    const buildHtmlExcerpt = (html: string, opts?: { maxChars?: number; maxBlocks?: number; skipTags?: string[] }): { html: string; truncated: boolean } => {
        const maxChars = opts?.maxChars ?? 280;
        const maxBlocks = opts?.maxBlocks ?? 5;
        const skipSet = new Set((opts?.skipTags ?? []).map(t => t.toUpperCase()));

        // trivial cases
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        if (!tmp.textContent || tmp.textContent.trim() === '' || html === '未設定') {
            return { html, truncated: false };
        }

        let used = 0;
        let blocks = 0;
        let truncated = false;

        const out = document.createElement('div');

        // Walk top-level blocks only; keeps structure tidy
        for (const child of Array.from(tmp.childNodes)) {
            if (blocks >= maxBlocks || used >= maxChars) break;

            const { clone, added, hitLimit } = cloneWithLimit(child, maxChars - used, skipSet);
            if (!clone) continue;

            out.appendChild(clone);
            used += added;
            blocks += 1;
            if (hitLimit) { truncated = true; break; }
        }

        // If we didn’t reach limits, no need to truncate
        if (!truncated && used < maxChars && blocks < maxBlocks) {
            return { html, truncated: false };
        }

        // Add ellipsis politely
        out.append('…');
        return { html: out.innerHTML, truncated: true };
    }

    const cloneWithLimit = (node: Node, remaining: number, skipSet: Set<string>): { clone?: Node; added: number; hitLimit: boolean } => {
        // Text node
        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.nodeValue ?? '';
            if (text.length <= remaining) {
            return { clone: document.createTextNode(text), added: text.length, hitLimit: false };
            }
            const sliced = text.slice(0, Math.max(0, remaining));
            return { clone: document.createTextNode(sliced), added: sliced.length, hitLimit: true };
        }

        // Element node
        if (node.nodeType === Node.ELEMENT_NODE) {
            const el = node as HTMLElement;
            if (skipSet.has(el.tagName)) {
            // skip entirely in preview
            return { clone: undefined, added: 0, hitLimit: false };
            }

            const clone = el.cloneNode(false) as HTMLElement;

            // Inline elements: treat like text container
            const isInline = getComputedStyle(el).display === 'inline' || ['A','EM','STRONG','SPAN','SMALL','S','U','I','B','SUB','SUP'].includes(el.tagName);

            let added = 0;
            let hitLimit = false;

            for (const child of Array.from(el.childNodes)) {
            if (added >= remaining) { hitLimit = true; break; }
            const res = cloneWithLimit(child, remaining - added, skipSet);
            if (res.clone) clone.appendChild(res.clone);
            added += res.added;
            if (res.hitLimit) { hitLimit = true; break; }
            }

            // If an inline became empty and we didn’t add anything, drop it
            if (isInline && !clone.textContent) {
            return { clone: undefined, added: 0, hitLimit };
            }
            return { clone, added, hitLimit };
        }

        // Ignore comments/others
        return { clone: undefined, added: 0, hitLimit: false };
    }
    watch(fullHtml, (html) => {const { html: ex, truncated } = buildHtmlExcerpt(html, {
            maxChars: 280,          // tweak to taste
            maxBlocks: 6,           // limit number of top-level blocks
            skipTags: ['TABLE','PRE','CODE','IFRAME','VIDEO'] // avoid heavy stuff in preview
        });
        excerptHtml.value = ex;
        isTruncated.value = truncated;
        isExpanded.value = false;
    }, { immediate: true });

    const api = useApi()
    type ProjectStatus = 'director_approved' | 'returned'
    const badge = useBadgeStore()
    const statusChange = async(status: ProjectStatus) => {
        const question = status === 'director_approved' ? '承認しますか？' : '差し戻しますか？'
        const inform =  status === 'director_approved' ? '承認しました。' : '差し戻ししました。'
        await api.patch('/project_change_status', {
            status: status,
            id: selectedProject.value?.id
        }, {
            toast: inform,
            ask: question
        })
        updateProject([{name: 'status'}])
        badge.clearProjectConfirmBadge()
        getBatchDashboardData(['projects'])
    }
</script>
<style scoped>
    @media screen and (max-width: 959px) {
        .project-cell-row:last-child .project-cell {
            border-bottom: 1px solid var(--calendarBorder) !important;
        }
        .project-table-container{
            border: none !important;
        }
        .project-cell:last-child{
            border-bottom: none !important;
        }
        .project-cell-row{
            margin-bottom: 20px !important;
            box-shadow: none !important;
        }
    }
</style>
