<template>
    <div class="post-container scrollable" style="height: calc(100% - 126px);">
        <div class="project-table">
            <div class="project-header-row">
                <div class="project-cell">プロジェクト名</div>
                <div class="project-cell">概要</div>
                <div class="project-cell">戦略</div>
                <div class="project-cell">期間</div>
                <div class="project-cell">取締役</div>
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
                <div class="project-cell">
                    <div style="position: relative;">
                        <div class="text-wrap" @click.stop="menu.setMenu({name: 'overviewBox', id: project?.id})">
                            {{ project.overview }}
                        </div>
                        <div @click="menu.close()" style="width: 100%" class="comment-box" id="overviewBox" v-if="menu.name == 'overviewBox' && menu.id == project?.id">
                            <div style="word-break: break-word;">{{ project.overview }}</div>                              
                        </div>
                    </div>
                    
                </div>
                <div class="project-cell">
                    <div style="position: relative">
                        <div class="text-wrap" @click.stop="menu.setMenu({name: 'strategyBox', id: project?.id})">
                            {{ project.strategy }}
                        </div>
                        <div @click="menu.close()" style="width: 100%" class="comment-box" id="strategyBox" v-if="menu.name == 'strategyBox' && menu.id == project?.id">
                            <div style="word-break: break-word;">{{ project.strategy }}</div>                              
                        </div>
                    </div>
                    
                </div>
                <div class="project-cell">
                    <div v-if="project?.date_start">{{ project.date_start }} ～ {{ project.date_end }}</div>
                </div>
                <div class="project-cell">
                    <div>
                        <UserIcon v-if="project?.director" imgClass="u_icon_20" :user="project?.director" size="30"/>
                    </div>
                </div>
                <div class="project-cell">
                    <div style="display: flex;" @click="viewUsers(project.manager)">
                        <UserIcon v-for="member in project.manager" :disable-instant="true" imgClass="u_icon_20" :user="member" size="30"/>
                    </div>
                </div>
                <div class="project-cell">
                    <div style="display: flex;" @click="viewUsers(project.members)">
                        <div style="display: flex;" >
                            <UserIcon v-for="member in project.members.slice(0, 5)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="30"/>
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
        <FloatButton type="plus" @action="createWindow = true"/>
        <Transition name="modalFade">
            <ProjectCreate 
                v-if="createWindow"
                @close="createWindow = false, editData = null"
                @getProjects="getProjects"
                :userList="userList"
                :edit-data="editData"
            />
        </Transition>
    </div>
</template>
<script setup lang="ts">
import FloatButton from '@/components/Global/FloatButton.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import UserIcon from '@/components/Board/Mixed/UserIcon.vue';
import { computed, inject, onMounted, ref } from 'vue';

import { Project } from '@/interface/projectInterface';
import { useMenuStore } from '@/store/menu';
import ProjectCreate from './ProjectCreate.vue';
import axios from 'axios';
import { useProjectUsers } from '@/store/projectUsers';
import { Dialog, User } from '@/interface/globalInterface';
const projects = ref<Project[]>([])
const menu = useMenuStore()
const createWindow = ref(false)

const editData = ref<Project | null>(null)
const projectUsers = useProjectUsers()
const props = defineProps(['keywords', 'userList'])
const { confirm } = inject<Dialog>('dialog')!
onMounted(async() => {
    getProjects() 
})
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
        return projects.value.filter(project => deepSearch(project))
    }
    return projects.value 
})
const getProjects = async() => {
    try {
        projects.value = await axios.get('/get_projects').then(res => res.data)
    } catch (e) {

    }
}

const editProject = (project: Project) => {
    editData.value = project
    createWindow.value = true
}
const deleteProject = async(project: Project) => {
    const answer = await confirm('プロジェクトを削除しますよろしいでか？')
    if (!answer) return
    try {
        await axios.delete(`/delete_project?id=${project?.id}`)
        getProjects()
    } catch (e) {

    }
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
<style scoped lang="scss">
    .project-table {
        display: table;
        border-collapse: collapse;
        width: 100%;
        font-size: 13px;
        background-color: var(--background-color);
    }

    .project-cell {
        display: table-cell;
        border: 1px solid var(--calendarBorder);
        text-align: left;
        padding: 5px;
        line-height: normal;
        max-width: 250px;
        height: 25px;
        vertical-align: middle;
    }
    .text-wrap {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
    }
    .project-header-row {

        display: table-row;
    }
    .project-cell-row {
        display: table-row;
    }
    .project-cell-row:hover{
        background-color: var(--bg3);
        cursor: pointer;
    }
</style>