<template>
    <div
        class="pc absolute top-[25px] flex max-h-[50vh] min-w-[280px] max-w-[80vw] flex-col overflow-auto rounded-md border border-solid border-[var(--secondary-background)] bg-[var(--background-color)] text-[13px] text-[var(--primary-color)] shadow-lg"
    >
        <div class="sticky top-0 z-[2] border-b [border-bottom-style:solid] border-[var(--secondary-background)] bg-[var(--background-color)] p-3">
            <div class="flex flex-col gap-3">
                <input
                    v-if="searchable"
                    v-model="keywords"
                    :placeholder="customPlaceHolder || '検索'"
                    type="text"
                    class="border border-solid border-[var(--formBorder)] px-3 py-2 text-[13px] focus:border-[var(--primary-color)]"
                />
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[12px] text-[gray]">{{ selectedCountLabel }}</span>
                    <CommandButton :buttons="commandButtons"/>
                </div>
            </div>
        </div>
        <div v-if="filtered.length" class="flex flex-col gap-1 p-3">
            <label
                v-for="option in filtered"
                :key="option.id"
                class="flex cursor-pointer items-center gap-2 rounded-md p-2 hover:bg-[var(--secondary-background)]"
            >
                <input type="checkbox" class="custom-f-checkbox rounded-[3px]" name="class-selector" v-model="selected" :value="option.id"/>
                <span class="truncate">{{ option.name }}</span>
            </label>
        </div>
        <div v-else class="flex items-center justify-center whitespace-nowrap p-[30px] text-[13px] text-[gray]">
            検索結果はありません。
        </div>
    </div>
</template>
<script lang="ts" setup>
import CommandButton from '@/components/Global/CommandButton.vue';
import { useMenuStore } from '@/store/menu';
import { computed, ref } from 'vue';
import { User } from '@/interface/globalInterface';
type optionType = {
    id: number
    name: string
}
const props = defineProps<{
    options: optionType[] | User[]
    customPlaceHolder?: string
    searchable?:boolean
    includeSelectAll?: boolean
}>()
const selected = defineModel<number[]>('selected')
const keywords = ref<string>('')
const menu = useMenuStore();
const allOptionIds = computed(() => props.options.map(option => option.id))
const selectedCountLabel = computed(() => `${selected.value?.length ?? 0}件選択中`)
const commandButtons = computed(() => {
    const buttons = [
        { title: 'リセット', action: () => { selected.value = []; keywords.value = ''; menu.close() } }
    ]

    if (props.includeSelectAll) {
        buttons.unshift({
            title: '全選択',
            action: () => {
                selected.value = [...allOptionIds.value]
                keywords.value = ''
                menu.close()
            }
        })
    }

    return buttons
})
const filtered = computed(() => {
    if(keywords.value && Array.isArray(props.options)){
        let lowSearch = keywords.value.toLowerCase()
        return props.options.filter(option => 
            option.name?.toLowerCase().includes(lowSearch)
        )
    }else{         
        return props.options
    }
})
</script>
