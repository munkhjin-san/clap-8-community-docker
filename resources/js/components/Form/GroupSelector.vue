<template>
<div class="advanced-user-selector-options">
    <div class="flex" @click="viewAccordian = !viewAccordian" style="padding-bottom: 10px;cursor: pointer;">
        <Back size="11" :class="['selector-accordion-inactive' , {'selector-accordion-active' : viewAccordian}]"/>
        <span style="font-size: 13px;">プロジェクト・グループ選択</span>
    </div>
    <Transition name="selector-accordion">
        <div v-if="viewAccordian" class="group-selector-wrap">    
            <div v-if="groups.length" class="selector-inner">
                <div @click="selectChip(group, group.users ? group.users : [], 'group')" v-for="group in groups" :class="['selector-chip', {'active-chip' : activeChip(group, group.users ? group.users : [], 'group')}]">
                    <div>{{ group.name }}</div>
                </div>
            </div>
            <div v-if="projects.length" class="selector-inner">
                <div @click="selectChip(project, project.users ? project.users.map(ob => ob) : [], 'board')" v-for="project in projects" :class="['selector-chip', {'active-chip' : activeChip(project, project.users ? project.users.map(ob => ob) : [], 'project')}]">      
                    <span>{{ project.name }}</span>              
                </div>
            </div>
        </div>
    </Transition>
    
</div>
</template>
<script setup>
import { onMounted, ref } from 'vue';
import Back from '../Icons/Back.vue';

const members = defineModel()
const groups = ref([])
const projects = ref([])
const viewAccordian = ref(false)
const selected = ref(null)

onMounted(() => {
    getPossibleGroups()
})

const getPossibleGroups = async () => {
    axios.get('/get_possible_groups').then(res => {
        groups.value = res.data.group
        projects.value = res.data.project
    });
}
const selectChip = (item, list, type) => {    
    members.value = selected.value == `${type}_${item.id}` ? [] : list
    selected.value = selected.value == `${type}_${item.id}` ? null : `${type}_${item.id}`  
}

const activeChip = (item, list, type) => {
    if(members.value.length){
        const array1 = members.value.map(ob => ob.id)
        const array2 = list.map(ob => ob.id)
        const areArraysIdentical = array1.every((value, index) => value === array2[index]);
        return areArraysIdentical && selected.value == `${type}_${item.id}`
    }
    return false
}
</script>
<style lang="scss" scoped>
.advanced-user-selector-options{
    margin-top: 20px;
    width: fit-content;
    margin-bottom: -10px;
}
.group-selector-wrap{
    max-height: 130px;
    overflow: hidden auto;
}
.selector-inner{
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 5px 0;
    font-size: 12px;
}
.selector-chip{
    display: flex;
    flex-wrap: nowrap;
    gap: 5px;
    align-items: center;
    background-color: var(--bg3);
    padding: 6px;
    cursor: pointer;
    transition: background-color 0.2s, color 0.2s;
}
.selector-chip:hover{
    background-color: var(--bg2);
}
.active-chip{
    background-color: var(--primary-color);
    color: var(--background-color);
}.active-chip:hover{
    background-color: var(--primary-color);
    color: var(--background-color);
}
.selector-accordion-inactive{
    transform: rotate(180deg);
    transition: transform 0.2s;
    margin-right: 10px;
}
.selector-accordion-active{
    transform: rotate(270deg);
}
.selector-accordion-enter-active,
.selector-accordion-leave-active {
    transition: max-height 0.2s ease;
}

.selector-accordion-enter-from,
.selector-accordion-leave-to {
    max-height: 0;
    overflow: hidden;
}
</style>