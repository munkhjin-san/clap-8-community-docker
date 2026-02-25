<template>
    <div class="post-container scrollable project-table-container" style="height: calc(100% - 126px);">
        <div class="project-table">
            <div class="project-header-row break-keep">
                <div class="project-cell">プロジェクト名</div>
                <div class="project-cell">部門</div>
                <div class="project-cell">期間</div>
                <div class="project-cell">サービスカテゴリ</div>
                <div class="project-cell">顧客企業</div>
                <div class="project-cell">パートナー企業</div>
                <div class="project-cell">概要</div>
                <div class="project-cell">管理者</div>
                <div class="project-cell">メンバー</div>
                <div class="project-cell">アクション</div>
            </div>
            <div class="project-cell-row" v-for="project in searchResults">
                <div class="project-cell">
                    <div>
                        {{ project.name }}
                    </div>
                </div>
                <div class="project-cell whitespace-nowrap">
                    <div>
                        {{ project.is_new ? '新規' : '既存' }}
                    </div>
                </div>
                <div class="project-cell pc">
                    <div v-if="project?.date_start">{{ DateTime.fromISO(project.date_start).toLocaleString(DateTime.DATE_SHORT) }} ~ {{ DateTime.fromISO(project.date_end).toLocaleString(DateTime.DATE_SHORT) }}</div>
                </div>
                <div class="project-cell pc">
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
                </div>
                <div class="project-cell">
                    <div style="position: relative;">
                        <div class="text-wrap" @click.stop="menu.setMenu({name: 'overviewBox', id: project?.id})">
                            {{ project.description }}
                        </div>
                        <div @click="menu.close()" style="width: 100%" class="comment-box" id="overviewBox" v-if="menu.name == 'overviewBox' && menu.id == project?.id">
                            <div style="word-break: break-word;">{{ project.description }}</div>                              
                        </div>
                    </div>
                    
                </div>
                <div class="project-cell">
                    <div style="display: flex;" @click="viewUsers(project.manager)">
                        <UserPanel v-for="member in project.manager" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                    </div>
                </div>
                <div class="project-cell">
                    <div style="display: flex;" @click="viewUsers(project.members)">
                        <div style="display: flex;" >
                            <UserPanel v-for="member in project.members.slice(0, 5)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                        </div>
                        <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="project.members.length > 5">...({{project.members.length}})</span>
                    </div>
                    
                </div>
                
                <div class="project-cell">
                    <div style="display: flex; gap: 10px;">
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
        <FloatButton @action="applyWindow = true">
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
            <ProjectApply 
                v-if="applyWindow"
                @close="(val) => applyWindow = val"
            />
        </Transition>
    </div>
</template>
<script setup lang="ts">
import FloatButton from '@/components/Global/FloatButton.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { computed, ref } from 'vue';
import { Project } from '@/interface/projectInterface';
import { useMenuStore } from '@/store/menu';
import ProjectCreate from './ProjectCreate.vue';
import { useProjectUsers } from '@/store/projectUsers';
import { User } from '@/interface/globalInterface';
import { DateTime } from 'luxon';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import { useProject } from '@/composables/project';
import ProjectApply from './ProjectApply.vue';

const menu = useMenuStore()
const createWindow = ref(false)

const editData = ref<Project | null>(null)
const projectUsers = useProjectUsers()
const props = defineProps(['keywords', 'userList'])
const api = useApi()
const { projectList, getProjects } = useProject()
const applyWindow = ref(false)
const searchResults = computed(() => {
    if(props.keywords){
        const lowSearch = props.keywords.toLowerCase()
        const deepSearch = (obj) => {
            if (typeof obj === 'string' || typeof obj === 'number') {
                return String(obj).toLowerCase().includes(lowSearch);
            } else if (Array.isArray(obj)) {
                return obj.some(item => deepSearch(item));
            } else if (typeof obj === 'object' && obj !== null) {
                return Object.values(obj).some(val => deepSearch(val));
            }
            return false;
        }
        return projectList.value.filter(project => deepSearch(project))
    }
    return projectList.value 
})

const editProject = (project: Project) => {
    editData.value = project
    createWindow.value = true
}
const deleteProject = async(project: Project) => {
    const data = await api.del('/delete_project', {id: project.id}, {
        ask: 'プロジェクトを削除しますか？',
        toast: '削除しました。'
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
</script>