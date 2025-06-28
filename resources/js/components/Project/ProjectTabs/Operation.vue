<template>
<div class="h-full overflow-y-auto relative bg-[var(--background-color)]">
    <div class="bg-[var(--background-color)] p-[20px]">        
        <div class="text-[13px] leading-normal">
            <div v-if="manualData.length" class="flex flex-col gap-[20px]">
                <div v-for="manual in manualData" class="border-[1px] border-solid border-[var(--border-color)] p-[20px]">
                    <div class="mb-[15px] relative">
                        <strong>{{ manual.title }}</strong>
                        <div class="absolute top-0 right-0 flex" v-if="isManager || isMember">
                            <div title="作業追加" @click="editJob.manual = manual, editJob.job = null" class="w-[25px] min-w-[25px] h-[25px] flex items-center justify-center cursor-pointer hover:bg-[var(--bg3)] rounded-full">
                                <AddIcon size="13"/>
                            </div>
                            <ItemMenu :items="[
                                {title: '編集', action: () => {createWindow = true; editData = manual}},
                                {title: '削除', action: () => { deleteManual(manual.id) }},
                            ]"/>
                        </div>

                    </div>
                    <div class="ml-[15px] flex flex-col gap-[15px]">
                        <div v-for="rule in manual.rules">
                            <div class="flex items-center gap-[10px]">
                                <label class="flex items-center gap-[10px] cursor-pointer">
                                    <div class="flex" :style="{ transition: 'transform 0.2s', transform: activeRules.includes(rule.id) ? 'rotate(270deg)' : 'rotate(180deg)'}">
                                        <Back size="10"/>
                                    </div>
                                    <input type="checkbox" v-model="activeRules" class="hidden" :value="rule.id">
                                    <span>{{ rule.job['作業'] }}</span>
                                </label>
                                <div class="flex gap-[10px]" v-if="activeRules.includes(rule.id) && (isManager || isMember)">
                                    <CommandButton :buttons="[
                                        {title: '編集', action: () => { editJob.job = rule; editJob.manual = manual}},
                                        {title: '削除', action: () => { deleteJob(manual.id, rule.id) }},

                                    ]"/>
                                </div>
                            </div>
                            <div class="ml-[30px] my-[20px] flex flex-col gap-[10px]" v-if="activeRules.includes(rule.id)">
                                <div v-for="(value, key) in rule.job">
                                    <div class="whitespace-break-spaces">
                                        <p v-html="value ? `${key} : ${urlCheck(value)}` : `${key} : `"></p>                                
                                    </div>
                                </div>
                                
                            </div>   
                            
                                        
                        </div>   
                        <!-- <CommandButton v-if="isManager" :buttons="[
                            {title: '新規作業作成', action: () => { editJob.manual = manual, editJob.job = null}},
                        ]"/> -->

                        <div class="mt-[15px]" v-if="manual.files.length">
                            <p class="mb-[10px]">添付ファイル</p>
                            <div class="flex flex-col gap-[10px]]">
                                <div v-for="file in manual.files">
                                    <a class="jump-link text-[12px] p-[5px] bg-[var(--bg3)] w-fit" :href="kintoneFileUrlBuilder(file)" target="_blank">{{ file.name }} ({{ fileSizeParser(Number(file.size)) }})</a>
                                </div>
                            </div>
                        </div>                      

                    </div>
                </div>   
            </div>  
            <div v-else-if="fetchCount > 0">
                <p class="no-comment-text">現在データはありません。</p>
            </div>       
        </div>     
    </div>
    <FloatButton v-if="isManager || isMember" :style="{position: 'fixed', bottom: auth.user?.footer_view && responsive.mobile ? '65px' : '20px'}" @action="createWindow = true">
        <template #icon>
            <AddIcon size="15" fill="black"/>
        </template>
    </FloatButton>
    <ManualCreate v-if="createWindow" @close="(flag) => manualCreateFinish(flag)" :edit-data="editData"/>
    <RuleCreate v-if="editJob.manual" @close="(flag) => ruleCreateFinish(flag)" :edit-data="editJob"/>
</div>
</template>
<script setup lang="ts">
import FloatButton from '@/components/Global/FloatButton.vue';
import Back from '@/components/Icons/Back.vue';
import { useAuthUserStore } from '@/store/auth';
import { useResponsive } from '@/store/responsive';
import { urlCheck } from '@/utils/tools';
import { computed, inject, onMounted, ref } from 'vue';   
import ManualCreate from './Manual/ManualCreate.vue';
import { Manual, Rule } from '@/interface/operation';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import RuleCreate from './Manual/RuleCreate.vue';
import { fileSizeParser, kintoneFileUrlBuilder } from '@/utils/tools';
import AddIcon from '@/components/Form/AddIcon.vue';
import { useProject } from '@/composables/project';
import { useApi } from '@/composables/api';

const props = defineProps<{
    userList: any;
}>();
const auth = useAuthUserStore();
const responsive = useResponsive()

const manualData = ref<Manual[]>([])
const editData = ref<Manual | null>(null)

const activeRules = ref<string[]>([])
const fetchCount = ref(0)
const setLoader = inject('setLoader') as (flag: boolean) => void
const { selectedProject } = useProject()
const api = useApi()

const createWindow = ref(false)
const editJob = ref<{
    job: Rule | null;
    manual: Manual | null;
}>({
    job: null,
    manual: null
})

onMounted(async() => {    
    setLoader(true)
    await getManuals();
    setTimeout(() => {
        setLoader(false)
    }, 100);
});
   
const getManuals = async() => {
    const response = await api.get('/get_manuals',  {project_name: selectedProject.value?.name})
    manualData.value = response;
    fetchCount.value++
}

const ruleCreateFinish = (flag:boolean) => {
    editJob.value.manual = null
    editJob.value.job = null
    if(flag){
        getManuals()
    }
}
const manualCreateFinish = (flag:boolean) => {
    createWindow.value = false; 
    editData.value = null
    if(flag){
        getManuals()
    }
}

const deleteJob = async(manualId: string, ruleId: string) => {
    await api.post('/delete_manual_rule', {manual_id: manualId, rule_id: ruleId}, {
        toast: '削除しました。',
        ask: '削除しますか？',
    })
    getManuals()
}
const deleteManual = async(manualId: string) => {
    await api.post('/delete_manual_record', {manual_id: manualId}, {
        toast: '削除しました。',
        ask: '削除しますか？',
    })
    getManuals()
}

const isManager = computed(() => {
    const activeUserId = auth.activeUser?.id;
    const projectManagers = selectedProject.value?.manager || [];
    
    if (!activeUserId) {
        return false;
    }
    
    const managerIds = projectManagers.map(manager => manager.id);
    const mergedManagerIds = [ ...managerIds, ...[608, 610]]
    return mergedManagerIds.includes(activeUserId);
});

const isMember = computed(() => {
    const activeUserId = auth.activeUser?.id;
    const projectMembers = selectedProject.value?.members || [];
    
    if (!activeUserId) {
        return false;
    }
    
    const memberIds = projectMembers.map(member => member.id);
    return memberIds.includes(activeUserId);
});
</script>