<template>
<div :class="['form-wrapper', {focused: selectedItem || focus}]">
    <span style="z-index:5" class="form-plc">{{ placeHolder || 'グループ・プロジェクト選択' }}</span> 
    <drop-selector
        class="one-selector"
        :options="unifiedOptions"
        label="name"
        :components="{Deselect}"
        :multiple="false"
        :closeOnSelect="true"
        @search:focus="focus = true"
        @search:blur="focus = false"
        v-model="selectedItem"
    >
    <template #no-options="{ search, searching, loading }">
        <div style="font-size: 14px;opacity: 0.8;padding:10px 0;">アイテムはありません。</div>        
    </template>
    </drop-selector> 
    
    <p v-if="error" class="i-error">{{error}}</p>
</div>
</template>
<script setup>
import { useApi } from '@/composables/api';
import { onMounted, ref, markRaw, watch, computed } from 'vue';
const props = defineProps(['placeHolder'])
const members = defineModel()
const groups = ref([])
const projects = ref([])
const selectedItem = ref(null)
const focus = ref(false)
const api = useApi()
watch(selectedItem, (value) => {  
    members.value = value ? value.users : []
})

onMounted(() => {
    getPossibleGroups()
})
const Deselect = markRaw({
    template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
})  
const unifiedOptions = computed(() => {
    const groupItem =  groups.value.map(group => {
        return {
            id: group.id,
            name: group.name,
            users: group.users
        }
    })
    const projectItem = projects.value.map(project => {
        return {
            id: project.id,
            name: project.name,
            users: [...project.manager, ...project.members]
        }
    })
    return [...groupItem, ...projectItem]
})
const getPossibleGroups = async () => {
    const data = await api.get('/get_possible_groups')  
    groups.value = data.group
    projects.value = data.project
}
</script>
<style scoped>
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
.one-selector {
        width: 100%;
        border: 1px solid var(--primary-color) !important;
    }
    .one-selector{
        .vs__actions {
            display: flex; 
            margin-right: 10px;
            padding: 0;
            align-items: center;
            margin-top: -10px;
        }
        .vs__clear{
            fill: var(--primary-color);
            svg{
                width: 10px;
                height: 10px;
            }
        }
    }
    .one-selector > .vs__dropdown-menu > .vs__dropdown-option{
        padding: 10px;
    }
    .vs__dropdown-option--disabled{
        background: inherit !important;
        color: inherit !important;
        opacity: 0.4;

    }
</style>