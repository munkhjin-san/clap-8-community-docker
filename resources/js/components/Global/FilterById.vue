<template>
    <div 
        class="pc 
            shadow-me 
            absolute  
            bg-[var(--bg3)] 
            text-[var(--primary-color)] 
            gap-[10px] 
            text-[13px] 
            pb-[10px]
            px-[10px]
            top-[25px] 
            max-h-[50vh] 
            overflow-auto
            flex
            flex-col"
            >
            <div style="position: sticky; top:0;background: var(--bg3);z-index: 2;">
                <div :class="['searchBarInner', { 'min-w-64': searchable}]" style="margin-left: 0;width: auto; padding-top:10px;"> 
                    <PostSearchBar
                        v-if="searchable"
                        className="newChatMemberSearch" 
                        @search-start="(word) => {keywords = word}"
                        :custom-place-holder="customPlaceHolder"
                    />
                    <CommandButton :buttons="[{title: 'リセット', action: () => {selected = []; keywords = ''; menu.close()}}]"/>
                </div> 
            </div>         
            <div class="flex flex-col gap-[10px]" v-if="filtered.length">
                <div v-for="option in filtered">
                    <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                        <input type="checkbox" class="custom-f-checkbox rounded-[3px]" name="class-selector"  v-model="selected" :value="option.id"/>
                        {{ option.name }}
                    </label>
                </div>
            </div>
            <div v-else class="h-[calc(100%-128px)] flex items-center justify-center whitespace-nowrap text-[13px] p-[30px] text-[var(--primary-color)]">
                検索結果はありません。
            </div>                      
        </div>
</template>
<script lang="ts" setup>
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
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
}>()
const selected = defineModel<number[]>('selected')
const keywords = ref<string>('')
const menu = useMenuStore();
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
