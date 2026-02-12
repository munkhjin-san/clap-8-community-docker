<template>
    <div class="h-full relative overflow-y-auto" ref="scrollContainer"> 
        <div v-if="!checkTab" class="project-detail flex flex-col gap-[15px]">
            <div v-if="hasPrivilage" class="absolute top-[20px] right-[20px]">
                <div class="flex gap-5">
                    <button 
                        v-if="selectedProject && ['director_approved', 'running', 'returned'].includes(selectedProject.status)"
                        class="bg-[var(--primary-button)] text-white p-1 text-xs"
                        @click="checkTab = true"
                    >チェック項目</button>
                    <ItemMenu :items="[
                        {title: '編集する', action: () => editProjects(selectedProject)},
                        {title: '削除する', action: () => deleteProject(selectedProject)}
                    ]"/>
                </div>
            </div>
        
            <div class="project-detail-header">
                <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">部門</span> {{ selectedProject?.is_new ? '新規' : '既存' }}</div>
            </div>
            <div class="project-detail-header">
                <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">期間</span> {{ selectedProject?.date_start && selectedProject.date_end ? `${DateTime.fromISO(selectedProject.date_start).toLocaleString(DateTime.DATE_SHORT)}  ~  ${DateTime.fromISO(selectedProject.date_end).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}</div>
            </div>
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
                <div><span class="p-[5px] text-[12px] bg-[var(--bg3)]">管理者用非公開メモ</span></div> 
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
            <div v-if="isDirector && selectedProject?.status == 'pending_director'" class="flex gap-4 py-4">
                <button @click="statusChange('director_approved')" class="bg-[var(--primary-button)] text-white p-1">
                    承認する
                </button>
                <button @click="statusChange('returned')" class="bg-[var(--primary-button)] text-white p-1">
                    差し戻し
                </button>
            </div>                       
        </div>
        <CheckList 
            v-else
            @close="checkTab = false"
        />
    </div>
</template>
<script setup lang="ts">
import { computed, inject, onMounted, ref, useTemplateRef, watch } from 'vue';
import ItemMenu from '../../Global/ItemMenu.vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { DateTime } from 'luxon';
import { useProject } from '@/composables/project';
import ProjectServiceCategories from 'assets/ProjectServiceCategories.json'
import CommandButton from '@/components/Global/CommandButton.vue';
import { Project } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import CheckList from './CheckList.vue';
import { useRoute } from 'vue-router';
    const props = defineProps(['userList', 'hasPrivilage'])
    const editProjects = inject('editProjects') as (project: any) => void
    const deleteProject = inject('deleteProject') as (project: Project | null) => void
    const { selectedProject, refreshProject } = useProject()
    const checkTab = ref(false)

    const sanitized = (text: string) => {
        const clean = text ?? ''
        if(!clean) return '未設定'
        const markedText = marked.parse(clean) as string
        const saveText = DOMPurify.sanitize(markedText)
        return saveText
    }
    const fullHtml = computed(() => sanitized(selectedProject?.value?.private_memo ?? ''));
    const isExpanded = ref(false);
    const isTruncated = ref(false);
    const excerptHtml = ref('');
    const auth = useAuthUserStore()
    const isDirector = computed(() => auth.activeUser.position_id < 6)
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

    const statusChange = async(status: ProjectStatus) => {
        await api.patch('/project_change_status', {
            status: status,
            id: selectedProject.value?.id
        }, {
            toast: '変更しました'
        })
        refreshProject([{name: 'status'}])
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