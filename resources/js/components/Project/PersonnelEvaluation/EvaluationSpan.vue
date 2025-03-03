<template>
    <div class="w-full h-full">
        <Transition name="slidePop">        
            <div v-if="!route.params.span || picker" class="fixed top-0 left-0 w-full h-full bg-[var(--overlay)] z-[50] flex items-center justify-center">
                <div class="p-[30px] bg-[var(--background-color)] flex flex-col gap-[20px] relative">
                    <div class="flex items-center justify-between">
                        <p>期間を選択してください。</p>
                        <div class="cursor-pointer w-[30px] min-w-[30px] h-[30px] flex items-center justify-center" @click="router.back()">   
                            <CloseIcon size="10"/>
                        </div>                        
                    </div>
                    
                    <button @click="setOption(option)" v-for="option in dateOptionsData" class="border border-solid border-[var(--formBorder)] px-[8px] py-[5px] cursor-pointer relative">
                        {{ option.name }}
                        <span class="side-notification" style="right: -5px; top: -5px; left: auto;" v-if="badgeByHalf?.[selectedProject?.id]?.[memberData?.id]?.[`${option.year}-${option.which_half}`]">{{ badgeByHalf?.[selectedProject?.id]?.[memberData?.id]?.[`${option.year}-${option.which_half}`] }}</span>
                    </button>
                </div>
            </div>
        </Transition>
        <div v-if="dateOptions.name" class="absolute right-[20px] top-[15px]">
            <button @click="picker = true" class="border border-solid border-[var(--formBorder)] px-[8px] py-[5px] cursor-pointer">{{ dateOptions.short_name }}</button>
        </div>
        <router-view v-slot="{ Component }">
            <component
                :selected-project="selectedProject"
                :member-data="memberData"
                :date="dateOptions"
                :is="Component"
                :key="route.fullPath"
            />
            </router-view>
    </div>
</template>
<script setup lang="ts">
import CloseIcon from '@/components/Form/CloseIcon.vue';
import { useBadgeStore } from '@/store/badge';
import { detailedDateOptions } from '@/utils/tools';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
const props = defineProps([
    'selectedProject',
    'memberData'
])
const router = useRouter()
const route = useRoute()
const picker = ref(route.params.span ? false : true)
const dateOptionsData = detailedDateOptions()
const badge = useBadgeStore()
onMounted(() => {
    const span = route.params.span as string
    if(span){
        const option = dateOptionsData.find(option => option.year + '-' + option.which_half == span)
        if(option){
            dateOptions.name = option.name
            dateOptions.year = option.year
            dateOptions.which_half = option.which_half
            dateOptions.short_name = option.short_name
        }
    }
})
const badgeByHalf = computed(() => {
    return badge.project.year_half_counts || {}
})
const dateOptions = reactive({
    name: '',
    year: '',
    which_half: '',
    short_name: ''
})
const setOption = (option) => {
    dateOptions.name = option.name
    dateOptions.year = option.year
    dateOptions.which_half = option.which_half
    dateOptions.short_name = option.short_name
    picker.value = false
    router.push({name: route.meta.pushTo as string, params: {span: `${option.year}-${option.which_half}`}})
}

</script>