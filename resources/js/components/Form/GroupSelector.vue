<template>
    <div ref="selectorRef" class="group-selector-shell">
        <v-autocomplete
            v-model="selectedItem"
            :clear-icon="CloseIcon"
            :items="unifiedOptions"
            :label="placeHolder || 'グループ・プロジェクト選択'"
            :menu-props="{ scrollStrategy: 'close', maxWidth: selectorRef ? selectorRef.clientWidth : undefined }"
            autocomplete="off"
            class="one-selector"
            clearable
            flat
            hide-details
            item-title="name"
            :multiple="false"
            return-object
            tile
        >
            <template #selection="{ item }">
                <v-chip
                    v-if="item.raw"
                    :close-icon="CloseIcon"
                    :text="item.raw.name"
                    closable
                    density="compact"
                    rounded="0"
                    size="small"
                    @click:close.stop="selectedItem = null"
                ></v-chip>
            </template>
            <template #item="{ item, props }">
                <v-list-item
                    v-bind="props"
                    :ripple="false"
                    :text="item.raw.name"
                    class="group-selector-option"
                    density="compact"
                    rounded="0"
                    variant="flat"
                ></v-list-item>
            </template>
            <template #no-data>
                <div class="text-[12px] text-[gray] p-3">アイテムはありません。</div>
            </template>
        </v-autocomplete>
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import 'styles/selector.css';
import { User } from '@/interface/globalInterface';



type GroupItem = {
    id: number
    name: string
    users: User[]
}

type ProjectItem = {
    id: number
    name: string
    manager: User[]
    members: User[]
}

type UnifiedOption = {
    id: number
    name: string
    users: User[]
}

const props = defineProps<{
    placeHolder?: string
}>()
const members = defineModel<User[]>({ default: [] })
const groups = ref<GroupItem[]>([])
const projects = ref<ProjectItem[]>([])
const selectedItem = ref<UnifiedOption | null>(null)
const selectorRef = useTemplateRef<HTMLElement>('selectorRef')
const api = useApi()

watch(selectedItem, (value) => {
    members.value = value ? value.users : []
})

onMounted(() => {
    getPossibleGroups()
})

const unifiedOptions = computed<UnifiedOption[]>(() => {
    const groupItem = groups.value.map(group => {
        return {
            id: group.id,
            name: group.name,
            users: group.users,
        }
    })
    const projectItem = projects.value.map(project => {
        return {
            id: project.id,
            name: project.name,
            users: [...project.manager, ...project.members],
        }
    })
    return [...groupItem, ...projectItem]
})

const getPossibleGroups = async () => {
    const data = await api.get('/get_possible_groups')
    groups.value = data?.group ?? []
    projects.value = data?.project ?? []
}
</script>
<style scoped lang="scss">
.group-selector-shell {
    background: inherit;
    border: 1px solid var(--primary-color);
    position: relative;
}

.one-selector {
    background: inherit !important;
    border: none !important;
    width: 100%;

    :deep(.v-field),
    :deep(.v-field__field),
    :deep(.v-field__input),
    :deep(.v-field__overlay) {
        background: inherit !important;
        background-color: inherit !important;
    }

    :deep(.v-field__clearable) {
        color: var(--primary-color);
    }
}

.group-selector-option {
    color: var(--primary-color) !important;

    &:after {
        pointer-events: none;
        z-index: 0;
    }

    :deep(.v-list-item__content),
    :deep(.v-list-item-title) {
        color: var(--primary-color) !important;
        position: relative;
        z-index: 1;
    }
}
</style>
