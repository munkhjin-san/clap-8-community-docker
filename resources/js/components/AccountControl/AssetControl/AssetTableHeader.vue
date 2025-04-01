<template>
<thead ref="assetHeader">
    <tr style="border:1px solid rgb(102, 102, 102);">
        <td v-if="columns.includes('GL番号')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'idPick'})">                                        
                    GL番号
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <div v-if="menu.parent == 'idPick'" id="idPick" class="shadow-me absolute bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px] max-h-[40vh] overflow-auto min-w-[150px] left-0" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                        <input name="model-selector" type="text" class="custom-o-input" v-model="idQuery" placeholder="GL番号検索（GLなし）"/>
                    </div>
                </Transition>
            </div>
        </td>
        <td v-if="columns.includes('品名')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'namePick'})">                                        
                    品名
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <div v-if="menu.parent == 'namePick'" id="namePick" class="shadow-me absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px] max-h-[40vh] min-w-[150px] overflow-auto" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                        <input name="model-selector" type="text" class="custom-o-input" v-model="nameQuery" placeholder="品名検索"/>
                    </div>
                </Transition>
            </div>
        </td>
        <td v-if="columns.includes('型番')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'modelPick'})">                                        
                    型番
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <div v-if="menu.parent == 'modelPick'" id="modelPick" class="shadow-me absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px] min-w-[150px] max-h-[40vh] overflow-auto" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                        <input name="model-selector" type="text" class="custom-o-input" v-model="modelQuery" placeholder="型番検索"/>
                    </div>
                </Transition>
            </div>
        </td>
        <td v-if="columns.includes('使用プロジェクト')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'projectPick'})">                                        
                    使用プロジェクト
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <div v-if="menu.parent == 'projectPick'" id="projectPick" class="shadow-me absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px] max-h-[40vh] overflow-auto" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                        <div class="flex items-center">
                            <input name="model-selector" type="text" class="custom-o-input" v-model="projectNameSearch" placeholder="プロジェクト名検索"/>
                            <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="officeQuery = [], menu.close(), projectNameSearch = ''">リセット</button>
                        </div>
                        <div v-for="projectData in projects.filter(project => project.name.toLowerCase().includes(projectNameSearch.toLowerCase()))">
                            <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                                <input type="checkbox" class="custom-f-checkbox" name="project-selector" v-model="projectQuery" :value="projectData.id"/>
                                {{ projectData.name }}
                            </label>
                        </div>
                    </div>
                </Transition>
            </div>
        </td>
        <td v-if="columns.includes('使用者')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'assetUsersPick'})">                                        
                    使用者
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <ProjectMemberSort
                        v-if="menu.parent == 'assetUsersPick'" 
                        id="assetUsersPick" 
                        :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}"
                        :members="users" 
                        v-model:selected-users="userQuery"
                        custom-place-holder="使用者検索"
                    />
                </Transition>
            </div>
        </td>
        <td v-if="columns.includes('分類')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'classPick'})">                                        
                    分類
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <div v-if="menu.parent == 'classPick'" id="classPick" class="shadow-me absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[12px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                        <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="classQuery = [], menu.close()">リセット</button>
                        <div v-for="classification in AssetClass">
                            <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                                <input type="checkbox" class="custom-f-checkbox" name="class-selector"  v-model="classQuery" :value="classification.value"/>
                                {{ classification.label }}
                            </label>
                        </div>
                    </div>
                </Transition>
            </div>
        </td>
        <td v-if="columns.includes('価値')">価値</td>
        <td v-if="columns.includes('ステータス')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'statusPick'})">                                        
                    ステータス
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <div v-if="menu.parent == 'statusPick'" id="statusPick" class="shadow-me absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                        <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="statusQuery = [], menu.close()">リセット</button>
                        <div v-for="statusData in AssetStatus">
                            <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                                <input type="checkbox" name="class-selector" v-model="statusQuery" :value="statusData.value" class="custom-f-checkbox"/>
                                {{ statusData.label }}
                            </label>
                        </div>
                    </div>
                </Transition>
            </div>
        </td>
        <td v-if="columns.includes('保管場所')">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'officePick'})">                                        
                    保管場所
                    <Filter class="filter-icon" size="12"/>
                </div>
                <Transition name="slidePop">
                    <div v-if="menu.parent == 'officePick'" id="officePick" class="shadow-me absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                        <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="officeQuery = [], menu.close()">リセット</button>
                        <div v-for="officeData in offices">
                            <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                                <input type="checkbox" class="custom-f-checkbox" name="office-selector" v-model="officeQuery" :value="officeData.id"/>
                                {{ officeData.name }}
                            </label>
                        </div>
                    </div>
                </Transition>
            </div>
        </td>
        <td>詳細</td>
    </tr>
</thead>
</template>
<script setup lang="ts">
import ProjectMemberSort from '@/components/Project/ProjectMemberSort.vue';
import { Office, User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import { useMenuStore } from '@/store/menu';
import { ref, useTemplateRef } from 'vue';
import AssetClass from 'assets/AssetClass.json'
import AssetStatus from 'assets/AssetStatus.json'
import Filter from '@/components/Icons/Filter.vue';
import 'styles/customForm.css'
const props = defineProps<{
    projects: Project[]
    users: User[]
    offices: Office[]
    columns: string[]
}>()

const menu = useMenuStore()
const assetHeader = useTemplateRef('assetHeader')

const userQuery = defineModel<number[]>('user_id', {required: true})
const classQuery = defineModel<number[]>('classification', {required: true})
const statusQuery = defineModel<number[]>('status', {required: true})
const officeQuery = defineModel<number[]>('office_id', {required: true})
const projectQuery = defineModel<number[]>('project_id', {required: true})
const modelQuery = defineModel<string>('model_number', {required: true})
const nameQuery = defineModel<string>('item_name', {required: true})
const idQuery = defineModel<string>('gl_number', {required: true})
const projectNameSearch = ref('')
</script>
<style scoped>
    thead {
        background: var(--third-color);
        color: var(--background-color);
        position: sticky;
        top: 0px;
        z-index: 1;
    }
    td {
        padding: 10px;
        font-size: 13px;
    }
    .filter-icon {
        fill: #fff !important;
        opacity: 0;
    }
    .h-p:hover .filter-icon {
        fill: var(--primary-color);
        opacity: 1;
    }
    
</style>