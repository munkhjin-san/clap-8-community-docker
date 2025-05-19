<template>
    <div class="h-full relative overflow-y-auto"> 
        <div class="project-detail flex flex-col gap-[15px]">
            <div v-if="hasPrivilage" class="absolute top-[20px] right-[20px]">
                <ItemMenu :items="[
                    {title: '編集する', action: () => editProjects(selectedProject)},
                ]"/>
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
                <div class="leading-normal mt-[10px]" v-html="sanitized(selectedProject?.private_memo ?? '')"></div>
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
</template>
<script setup lang="ts">
import { inject } from 'vue';
import ItemMenu from '../../Global/ItemMenu.vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { DateTime } from 'luxon';
import { useProject } from '@/composables/project';
import ProjectServiceCategories from 'assets/ProjectServiceCategories.json'
    const props = defineProps(['userList', 'hasPrivilage'])
    const editProjects = inject('editProjects') as (project: any) => void

    const { selectedProject } = useProject()



    const sanitized = (text: string) => {
        const clean = text ?? ''
        if(!clean) return '未設定'
        const markedText = marked.parse(clean) as string
        const saveText = DOMPurify.sanitize(markedText)
        return saveText
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