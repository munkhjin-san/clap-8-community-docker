<template>
<div class="advanced-user-selector-options">
    <div @click="viewAccordian = !viewAccordian" style="padding-bottom: 10px;cursor: pointer;">
        <svg class="dot-menu" version="1.1" width="11" height="11" :class="['selector-accordion-inactive' , {'selector-accordion-active' : viewAccordian}]" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
        </svg>
        <span style="font-size: 13px;">ボード・グループ選択</span>
    </div>
    <Transition name="selector-accordion">
        <div v-if="viewAccordian" class="group-selector-wrap">    
            <div v-if="groups.length" class="selector-inner">
                <div @click="selectChip(group, group.users ? group.users : [], 'group')" v-for="group in groups" :class="['selector-chip', {'active-chip' : activeChip(group, group.users ? group.users : [], 'group')}]">
                    <div>{{ group.name }}</div>
                </div>
            </div>
            <div v-if="groups.length" class="selector-inner">
                <div @click="selectChip(board, board.board_to_users ? board.board_to_users.map(ob => ob.user) : [], 'board')" v-for="board in boards" :class="['selector-chip', {'active-chip' : activeChip(board, board.board_to_users ? board.board_to_users.map(ob => ob.user) : [], 'board')}]">      
                    <span>{{ board.title }}</span>              
                </div>
            </div>
        </div>
    </Transition>
    
</div>
</template>
<script setup>
import { onMounted, ref } from 'vue';

const members = defineModel()
const groups = ref([])
const boards = ref([])
const viewAccordian = ref(false)
const selected = ref(null)

onMounted(() => {
    getPossibleGroups()
})

const getPossibleGroups = async () => {
    axios.get('/get_possible_groups').then(res => {
        groups.value = res.data.group
        boards.value = res.data.board
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