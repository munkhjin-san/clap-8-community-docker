<template>
<div class="border border-solid border-1 border-[var(--primary-color)] p-[15px] text-[14px] leading-normal">
    <div class="flex flex-col gap-[20px]">
        <div v-for="(category, categoryIndex) in mainCategories" :key="category.title">
            <label class="flex items-center gap-[10px] cursor-pointer">                    
                <div class="flex" :style="{ transition: 'transform 0.2s', transform: activeItems.category.includes(`${categoryIndex}_${category.title}`) ? 'rotate(270deg)' : 'rotate(180deg)'}">
                    <Back size="10"/>
                </div>
                <div class="flex items-center gap-[10px]">
                    <div>{{ category.title }}</div>
                    <UserPanel disable-instant size="15" v-if="user && initial?.includes(category.title)" :user="user"/>
                </div>
                <input type="checkbox" class="hidden" v-model="activeItems.category" :value="`${categoryIndex}_${category.title}`"/>
            </label>
            <div v-if="activeItems.category.includes(`${categoryIndex}_${category.title}`)" class="ml-[20px] mt-[15px] flex flex-col gap-[20px]">     
                <div v-for="(job, jobIndex) in category.children" :key="job.title">
                    <label class="flex items-center gap-[10px] cursor-pointer">                    
                        <div class="flex" :style="{ transition: 'transform 0.2s', transform: activeItems.job.includes(`${categoryIndex}_${jobIndex}_${job.title}`) ? 'rotate(270deg)' : 'rotate(180deg)'}">
                            <Back size="10"/>
                        </div>
                        <div class="flex items-center gap-[10px]">
                            <div>{{ job.title }}</div>
                            <UserPanel disable-instant size="15" v-if="user && initial?.includes(`${category.title}_${job.title}`)" :user="user"/>
                        </div>
                        <input type="checkbox" class="hidden" v-model="activeItems.job" :value="`${categoryIndex}_${jobIndex}_${job.title}`"/>
                    </label>
                    <div v-if="activeItems.job.includes(`${categoryIndex}_${jobIndex}_${job.title}`)" class="ml-[20px] mt-[15px] flex flex-col gap-[20px]">
                        <div v-for="(level, levelIndex) in job.children" :key="level.title">
                            <div class="flex items-center gap-[10px]">
                                <label class="flex items-center gap-[10px] cursor-pointer">               
                                    <div class="flex" :style="{ transition: 'transform 0.2s', transform: activeItems.level.includes(`${categoryIndex}_${jobIndex}_${levelIndex}_${level.title}`) ? 'rotate(270deg)' : 'rotate(180deg)'}">
                                        <Back size="10"/>
                                    </div>
                                    <div>{{ level.title }}</div>
                                    <input type="checkbox" class="hidden" v-model="activeItems.level" :value="`${categoryIndex}_${jobIndex}_${levelIndex}_${level.title}`"/>
                                    
                                </label>
                                <div class="flex items-center gap-[10px]">                                    
                                    <div v-if="user && initial?.includes(`${category.title}_${job.title}_${level.title}`)" class="flex items-center gap-[10px]">
                                        <div class="px-[5px] py-[3px] text-[12px] bg-gray-300 text-black">{{ `➞${selectedDate?.name}設定中` }}</div>
                                    </div>
                                    <label class="px-[5px] py-[3px] text-[12px] flex items-center gap-[5px] cursor-pointer">
                                        <input 
                                            type="radio" 
                                            @change="(event) => levelChanged(event, `${categoryIndex}_${jobIndex}_${levelIndex}_${level.title}`, `${category.title}_${job.title}_${level.title}`)" 
                                            class="custom-f-radio" 
                                            name="target_level" 
                                            :value="`${category.title}_${job.title}_${level.title}`"
                                            v-model="selectedLevel"
                                            />
                                        {{ `${selectedDate?.name}➞設定する` }}
                                    </label>
                                </div>

                            </div>
                            <ol v-if="activeItems.level.includes(`${categoryIndex}_${jobIndex}_${levelIndex}_${level.title}`)" class="mt-[15px] ml-[15px] flex flex-col gap-[20px] pl-[45px] list-decimal">
                                <li 
                                    v-for="skill in level.children"
                                    :class="['text-[13px] relative cursor-pointer', {'!cursor-not-allowed opacity-40' : selectedLevel !== `${category.title}_${job.title}_${level.title}`}]" 
                                    :title="selectedLevel !== `${category.title}_${job.title}_${level.title}` ? '選択するには「設定する」を押してください' : ''"
                                    
                                >
                                    <label :class="['cursor-pointer', {'!cursor-not-allowed' : selectedLevel !== `${category.title}_${job.title}_${level.title}`}]">
                                        <input 
                                            type="checkbox" 
                                            class="custom-f-checkbox !absolute -left-[50px] top-[2px] min-w-[15px]" 
                                            v-model="selectedSkills" 
                                            :value="skill"
                                            :disabled="selectedLevel !== `${category.title}_${job.title}_${level.title}`" 
                                        />
                                        {{ skill }}
                                    </label>                                    
                                </li>
                            </ol>  
                        </div>
                    </div>
                </div>
            </div>              
            
        </div>
    </div>

</div>
</template>
<script setup lang="ts">
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import Back from '../Icons/Back.vue';
import { User } from '@/interface/globalInterface';
import UserPanel from '../Global/UserPanel.vue';
import 'styles/customForm.css'
const props = defineProps<{
    initial?: string
    user?: User
    selectedDate?: {
        year: string,
        which_half: string
        name: string
    }
    autoSet?: boolean
}>()
const mainCategories = ref<Evaluation[]>([]);

const activeItems = reactive<{
    category: string[],
    job: string[],
    level: string[],
    skill: string
}>({
    category: [],
    job: [],
    level: [],
    skill: ''
})

const selectedSkills = defineModel()
const selectedLevel = ref<string>('')
type Skill = string

type Level = {
    title: string,
    children: Skill[]
}

type Job = {
    title: string,
    children: Level[]
}

type Unit = {
    title: string,
    children: Job[]
}

type Evaluation = {
    title: string,
    children: Unit[]
}


onMounted(async() => {
    console.log('initial props', props.initial)

    console.log(props.user)
    mainCategories.value = await axios.get('/get_evaluation_levels').then(res => res.data)
    if(props.initial){
        const [cat, job, level] = props.initial.split('_')
        const catIndex = mainCategories.value.findIndex(category => category.title == cat)
        activeItems.category.push(`${catIndex}_${cat}`)
        const jobIndex = mainCategories.value[catIndex].children.findIndex(jobItem => jobItem.title == job)
        activeItems.job.push(`${catIndex}_${jobIndex}_${job}`)
        const levelIndex = mainCategories.value[catIndex].children[jobIndex].children.findIndex(levelItem => levelItem.title == level)
        activeItems.level.push(`${catIndex}_${jobIndex}_${levelIndex}_${level}`)
        if(props.autoSet){
            selectedLevel.value = `${cat}_${job}_${level}`
        }
    }
});

const levelChanged = (event: Event, value: string, selectedValue: string) => {
    const target = event.target as HTMLInputElement
    if(target.checked){
        selectedSkills.value = []
        if(!activeItems.level.includes(value)){
            activeItems.level.push(value)
        }
    }
    
}
const setSelectedLevel = (value: string) => {

    selectedLevel.value = value
    console.log('set', selectedLevel.value)
}

defineExpose({
    selectedLevel
})
</script>