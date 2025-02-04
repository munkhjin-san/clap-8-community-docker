<template>
    <div class="w-full h-full">
        <Transition name="slidePop">        
            <div v-if="!route.params.span || picker" class="fixed top-0 left-0 w-full h-full bg-[var(--overlay)] z-[50] flex items-center justify-center">
                <div class="p-[30px] bg-[var(--background-color)] flex flex-col gap-[20px] relative">
                    <p>期間を選択してください。</p>
                    <button @click="setOption(option)" v-for="option in dateOptionsData" class="border border-solid border-[var(--formBorder)] px-[8px] py-[5px] cursor-pointer">
                        {{ option.name }}
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