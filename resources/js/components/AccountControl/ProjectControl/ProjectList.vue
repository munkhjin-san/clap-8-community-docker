<template>
    <div class="flex flex-col gap-5 mx-5" style="height: calc(100% - 120px);">
        <div class="flex justify-between">
            <MonthPickerNew 
                v-model:month="selectedMonth"
                v-model:year="selectedYear"
                left="0"
            />
            <div class="flex">
                <div class="admin-button" @click="generateWorkTimeCsv">CSV出力</div>
            </div>
        </div>
        <Transition name="modalFade">
            <div class="cal-month-loader" style="background:var(--overlay);height: calc(100% - 170px); top: 170px;" v-if="loader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="scrollable w-full h-full">
            
            <div class="project-table">
                
                <div class="project-header-row break-keep">
                    <div class="project-cell">プロジェクト</div>
                    <!-- <div class="project-cell">部門</div> -->
                    <div class="project-cell">期間</div>
                    <!-- <div class="project-cell">サービスカテゴリ</div>
                    <div class="project-cell">顧客企業</div>
                    <div class="project-cell">パートナー企業</div> -->
                    <!-- <div class="project-cell">概要</div> -->
                    <div class="project-cell">PM</div>
                    <div class="project-cell">メンバー</div>
                    <div class="project-cell">労働日数</div>
                    <div class="project-cell">労働時間</div>
                    <div class="project-cell">ステータス</div>
                    <div class="project-cell">アクション</div>
                </div>
                <div class="project-cell-row" @click="selectedProject = project" v-for="project in searchResults">
                    <div class="project-cell">
                        <div>
                            {{ project.name }}
                        </div>
                    </div>
                    <!-- <div class="project-cell whitespace-nowrap">
                        <div>
                            {{ project.is_new ? '新規' : '既存' }}
                        </div>
                    </div> -->
                    <div class="project-cell pc">
                        <div v-if="project?.date_start">{{ DateTime.fromISO(project.date_start).toLocaleString(DateTime.DATE_SHORT) }} ~ {{ DateTime.fromISO(project.date_end).toLocaleString(DateTime.DATE_SHORT) }}</div>
                    </div>
                    <!-- <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="cat in project.category || []">{{ cat }}</p>
                            </div>
                        </div>                        
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="customer in project.customers || []">{{ customer }}</p>
                            </div>
                        </div>                        
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="partner in project.partners || []">{{ partner }}</p>
                            </div>
                        </div>                        
                    </div> -->
                    <!-- <div class="project-cell">
                        <div style="position: relative;">
                            <div class="text-wrap" @click.stop="menu.setMenu({name: 'overviewBox', id: project?.id})">
                                {{ project.description }}
                            </div>
                            <div @click="menu.close()" style="width: 100%" class="comment-box" id="overviewBox" v-if="menu.name == 'overviewBox' && menu.id == project?.id">
                                <div style="word-break: break-word;">{{ project.description }}</div>                              
                            </div>
                        </div>
                        
                    </div> -->
                    <div class="project-cell">
                        <div style="display: flex;" @click.stop="viewUsers(project.manager)">
                            <UserPanel v-for="member in project.manager" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                        </div>
                    </div>
                    <div class="project-cell">
                        <div style="display: flex;" @click.stop="viewUsers(project.members)">
                            <div style="display: flex;" >
                                <UserPanel v-for="member in project.members.slice(0, 5)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                            </div>
                            <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="project.members.length > 5">...({{project.members.length}})</span>
                        </div>
                        
                    </div>
                    <div class="project-cell">
                        {{ project.total_work_day ?? 0 }}
                    </div>
                    <div class="project-cell">
                        {{ numberTime(project.total_work_time ?? 0) }}
                    </div>
                    
                    <div class="project-cell">
                        <select 
                            :value="project.status" 
                            @change="changeStatus(project, $event)"
                            @click.stop
                            class="p-1 bg-[var(--bg2)] text-[var(--primary-color)] w-fit rounded-md text-xs border border-transparent hover:border-[var(--normalBorder)] cursor-pointer"
                        >
                            <option v-for="(label, key) in PROJECT_STATUS_LABEL" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div class="project-cell">
                        <div style="display: flex; gap: 10px;" @click.stop>
                            <CommandButton 
                                :buttons="[
                                    { title: '変更', action: () => editProject(project)},
                                    { title: '削除', action: () => deleteProject(project)}
                                ]"
                            />
                        </div>
                        
                    </div>
                </div>
                
            </div>
            <FloatButton @action="createWindow = true">
                <template #icon>
                    <AddIcon size="15" fill="black"/>
                </template>
            </FloatButton>
            <Transition name="modalFade">
                <ProjectCreate 
                    v-if="createWindow"
                    @close="createWindow = false, editData = null"
                    @getProjects="getProjects"
                    :userList="userList"
                    :edit-data="editData"
                />
            </Transition>
            <Transition name="modalFade">
                <div v-if="completeStatusModal" class="overlay" @mousedown="cancelCompleteStatusChange">
                    <div class="p-5 bg-[var(--background-color)]" @mousedown.stop>
                        <p class="font-bold text-base">完了日を選択</p>
                        <p class="text-xs mt-2">ステータスを「完了」に変更する日付を指定してください。</p>
                        <input
                            v-model="completedAtInput"
                            :class="[{'date-color' : theme.dark }]" 
                            type="date"
                            class="custom-a-input mt-4 !w-full !box-border"
                        />
                        <div class="flex justify-end gap-2 mt-5">
                            <button
                                type="button"
                                class="text-xs px-3 py-2 rounded border border-solid border-[var(--normalBorder)]"
                                @click="cancelCompleteStatusChange"
                            >
                                キャンセル
                            </button>
                            <button
                                type="button"
                                class="text-xs px-3 py-2 rounded bg-[var(--primary-button)] !text-white"
                                :disabled="!completedAtInput"
                                @click="applyCompleteStatusChange"
                            >
                                変更する
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
            <Transition name="smLoad" >
                <div @mousedown="selectedProject = null" class="gantt-overlay" v-if="selectedProject">
                    <div @mousedown.stop class="text-sm gantt-overlay-inner space-y-5 !p-8 !h-[calc(100%-64px)] !w-1/2 overflow-auto">
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-base">{{selectedProject.name}}</p>
                            <CloseIcon class="cursor-pointer" size="12" @click="selectedProject = null"/>
                        </div>
                        <!-- <div class="flex gap-3 text-sm items-center">
                            <p class="bg-[var(--bg3)] p-1">部門</p>
                            <p>{{selectedProject.is_new ? '新規' : '既存'}}</p>
                        </div> -->
                        <div class="flex gap-3 text-sm items-center">
                            <p class="bg-[var(--bg3)] p-1">プロジェクト種別</p>
                            <p>{{ selectedProject.projectType?.label ?? selectedProject.project_type?.label ?? '未設定' }}</p>
                        </div>
                        <div class="flex gap-2 text-sm items-center">
                            <p class="bg-[var(--bg3)] p-1">期間</p>
                            <p>{{ DateTime.fromISO(selectedProject.date_start).toLocaleString(DateTime.DATE_SHORT) }} ~ {{ DateTime.fromISO(selectedProject.date_end).toLocaleString(DateTime.DATE_SHORT) }}</p>
                        </div>
                         <div class="flex gap-2 text-sm items-center">
                            <p class="bg-[var(--bg3)] p-1">完了日</p>
                            <p>{{ selectedProject?.completed_at ? DateTime.fromISO(selectedProject.completed_at).toLocaleString(DateTime.DATE_SHORT) : '未設定' }}</p>
                        </div>
                        <div class="flex gap-2 text-sm items-center">
                            <div>
                                <span class="bg-[var(--bg3)] p-1">サービスカテゴリー</span>
                                <div v-if="selectedProject.category && selectedProject.category.length" class="flex flex-col gap-[15px] mt-[15px]">
                                    <div v-for="cat in selectedProject?.category">
                                        <div>{{ ProjectServiceCategories.find( c => c.value == cat)?.title }}</div>
                                        <div class="text-[12px] text-[gray] mt-[5px]">{{ ProjectServiceCategories.find( c => c.value == cat)?.subtitle }}</div>
                                    </div>
                                </div>
                                <span v-else class="ml-2">未設定</span>
                            </div>
                            
                        </div>
                        <div class="project-detail-header">
                        <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">顧客企業</span>
                            <span >{{ selectedProject?.customers && selectedProject.customers.length ? selectedProject.customers.join("、") : '未設定' }}</span>
                        </div> 
                    </div>
                    <div class="project-detail-header">
                        <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">パートナー企業</span>
                            <span >{{ selectedProject?.partners && selectedProject.partners.length ? selectedProject.partners.join("、") : '未設定' }}</span>
                        </div> 
                    </div>
                    <div class="project-detail-header">
                        <div><span class="p-[5px] text-[12px] bg-[var(--bg3)] mr-[10px]">業種区分</span>
                            <span >{{ selectedProject?.industry_type && selectedProject.industry_type.length ? selectedProject.industry_type.join("、") : '未設定' }}</span>
                        </div> 
                    </div>

                    <div class="project-detail-header">
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
                    </div>
                </div>
            </Transition>
            
        </div>
    </div>
    
</template>
<script setup lang="ts">
import FloatButton from '@/components/Global/FloatButton.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { computed, onMounted, ref, watch } from 'vue';
import type { Project } from '@/interface/projectInterface';
import { useMenuStore } from '@/store/menu';
import ProjectCreate from './ProjectCreate.vue';
import { useProjectUsers } from '@/store/projectUsers';
import { User } from '@/interface/globalInterface';
import { DateTime } from 'luxon';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import { useProject } from '@/composables/project';
import ProjectServiceCategories from 'assets/ProjectServiceCategories.json'
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { PROJECT_STATUS_LABEL, timeFormat } from '@/utils/tools';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import { useTheme } from '@/store/theme';
import MonthPickerNew from '@/components/Global/MonthPickerNew.vue';
import { download, generateCsv, mkConfig } from 'export-to-csv';
const fullHtml = computed(() => sanitized(selectedProject?.value?.private_memo ?? ''));
const isExpanded = ref(false);
const excerptHtml = ref('');
const isTruncated = ref(false);
const toggleFull = () => { isExpanded.value = !isExpanded.value; };
const displayHtml = computed(() => isExpanded.value ? fullHtml.value : excerptHtml.value);
const theme = useTheme()
const sanitized = (text: string) => {
    const clean = text ?? ''
    if(!clean) return '未設定'
    const markedText = marked.parse(clean) as string
    const saveText = DOMPurify.sanitize(markedText)
    return saveText
}
const loader = ref(false)
const menu = useMenuStore()
const createWindow = ref(false)
const selectedYear = ref(DateTime.now().year)
const selectedMonth = ref(DateTime.now().month)
const editData = ref<Project | null>(null)
const projectUsers = useProjectUsers()
const props = defineProps(['keywords', 'userList'])
const api = useApi()
const { projectList, getProjects } = useProject()
const applyWindow = ref(false)
const selectedProject = ref<Project | null>(null)
const selectedProjectTypeId = ref('')
const completeStatusModal = ref(false)
const completedAtInput = ref(DateTime.now().toFormat('yyyy-LL-dd'))
const pendingCompletedStatus = ref<{
    project: Project
    selectElement: HTMLSelectElement
    oldStatus: string
    newStatus: string
} | null>(null)
watch([selectedMonth, selectedYear], async() => {
    loader.value = true
    await getProjects(undefined, undefined, undefined, selectedMonth.value, selectedYear.value)
    loader.value = false
})
const updateProjectStatus = async (project: Project, selectElement: HTMLSelectElement, newStatus: string, oldStatus: string, completedAt?: string) => {
    const payload: Record<string, string | number> = {
        id: project.id,
        status: newStatus
    }

    if (completedAt) {
        payload.completed_at = completedAt
    }
    const data = await api.patch('/project_change_status', payload, {
        ask: `プロジェクト「${project.name}」のステータスを「${PROJECT_STATUS_LABEL[newStatus]}」に変更しますか？`,
        toast: 'ステータスを更新しました。',
        loadingRef: loader
    })

    if (data) {
        getProjects()
        return
    }
    selectElement.value = oldStatus
}
const numberTime = (time: number) => {
    return time / 60
}
const generateWorkTimeCsv = () => {
    const selectedDate = DateTime.fromObject({ year: selectedYear.value, month: selectedMonth.value }).toFormat('yyyy-MM')
    const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `プロジェクト労働時間_${selectedDate}`});
    const data: any[] = []
    projectList.value.forEach((project: Project) => {
        const managers = project.manager.map(m => m.name).join('\n')
        const members = project.members.map(m => m.name).join('\n')
        data.push({
            'プロジェクト': project.name,
            'PM': managers,
            'メンバー': members,
            '労働日数': project.total_work_day ?? 0,
            '労働時間': numberTime(project.total_work_time ?? 0),
        })
    })
    if(data && data.length){
        const csv = generateCsv(csvConfig)(data)
        download(csvConfig)(csv);
    } 
}
const cancelCompleteStatusChange = () => {
    if (pendingCompletedStatus.value) {
        pendingCompletedStatus.value.selectElement.value = pendingCompletedStatus.value.oldStatus
    }
    pendingCompletedStatus.value = null
    completeStatusModal.value = false
    completedAtInput.value = DateTime.now().toFormat('yyyy-LL-dd')
}

const applyCompleteStatusChange = async () => {
    if (!pendingCompletedStatus.value || !completedAtInput.value) return

    const { project, selectElement, oldStatus, newStatus } = pendingCompletedStatus.value
    completeStatusModal.value = false
    pendingCompletedStatus.value = null

    await updateProjectStatus(project, selectElement, newStatus, oldStatus, completedAtInput.value)
    completedAtInput.value = DateTime.now().toFormat('yyyy-LL-dd')
}

const searchResults = computed(() => {
    let results = projectList.value

    if (selectedProjectTypeId.value) {
        results = results.filter(project => String(project.project_type_id ?? project.projectType?.id ?? project.project_type?.id ?? '') === selectedProjectTypeId.value)
    }

    if(props.keywords){
        const lowSearch = props.keywords.toLowerCase()
        const deepSearch: (obj: Project) => boolean = (obj: Project) => {
            if (typeof obj === 'string' || typeof obj === 'number') {
                return String(obj).toLowerCase().includes(lowSearch);
            } else if (Array.isArray(obj)) {
                return obj.some(item => deepSearch(item));
            } else if (typeof obj === 'object' && obj !== null) {
                return Object.values(obj).some(val => deepSearch(val));
            }
            return false;
        }
        return results.filter(project => deepSearch(project))
    }
    return results
})

const editProject = (project: Project) => {
    editData.value = project
    createWindow.value = true
}
const deleteProject = async(project: Project) => {
    const data = await api.del('/delete_project', {id: project.id}, {
        ask: 'プロジェクトを削除しますか？',
        toast: '削除しました。',
        loadingRef: loader
    })
    data && getProjects()
}
const viewUsers = (members: User[]) => {
    const data = {
        active: true,
        userList: members,
        title: 'プロジェクトメンバー'
    }
    projectUsers.setProjectUsers(data)
    
}
const changeStatus = async (project: Project, event: Event) => {
    const selectElement = event.target as HTMLSelectElement;
    const newStatus = selectElement.value;
    const oldStatus = project.status;
    if (newStatus === 'completed') {
        completedAtInput.value = DateTime.now().toFormat('yyyy-LL-dd')
        pendingCompletedStatus.value = { project, selectElement, oldStatus, newStatus }
        completeStatusModal.value = true
        return
    }

    await updateProjectStatus(project, selectElement, newStatus, oldStatus)
}
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
</script>
