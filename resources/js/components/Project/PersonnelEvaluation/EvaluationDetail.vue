<template>
    <div class="goals-wrap overflow-auto">
        <div class="absolute w-full h-full bg-[var(--background-color)] z-[10] flex items-center justify-center left-0 top-0" v-if="initialLoader">
            <div id="loaderMini">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </div>
        <div>
            <div class="goals-inner">
                <div v-if="evaluationData && evaluationData">
                    <div class="flex flex-wrap items-start justify-between gap-[20px] p-4 bg-[var(--bg3)]">
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">期間</div>
                            <div class="text-[14px]">{{ date.name }}</div>
                        </div>
                        <div class="">
                            <div class="mb-[10px] text-[12px] text-[gray]">ステータス</div>
                            <div class="text-[14px]">{{ evaluationData ? statuses[evaluationData?.status].name : '' }}</div>
                        </div>
                    </div>

                    <div class="flex gap-[20px] flex-wrap p-4 bg-[var(--bg3)] justify-between">
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">雇用形態</div>
                            <div class="text-[14px]">{{ computedMemberData?.positions?.name || '未設定' }}</div>
                        </div>
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">等級</div>
                            <div class="text-[14px]">{{ gradeSplit(evaluationData?.current_salary_rank) || '未設定' }}</div>
                        </div>
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">職階</div>
                            <div class="text-[14px]">{{ evaluationData?.general_position || '未設定' }}</div>
                        </div>
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">職務</div>
                            <div class="text-[14px]">{{ evaluationData?.current_level || '未設定' }}</div>
                        </div>
                    </div>

                    <div class="flex gap-[20px] flex-wrap p-4 bg-[var(--bg3)] justify-between" v-if="(auth.id === computedMemberData?.id || auth.id === evaluationData?.mentor_id)">

                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">給料（非公開）</div>
                            <div class="text-[14px]">{{ formatSalary(evaluationData?.current_salary_rank) }}</div>
                        </div>
                        <div v-if="currentPosition?.value">
                            <div class="mb-[10px] text-[12px] text-[gray]">役職手当（非公開）</div>
                            <div class="text-[14px]">{{ currentPosition?.value }}</div>
                        </div>
                    </div>
                    <div class="flex gap-[20px] flex-wrap p-4 bg-[var(--bg3)] justify-between">
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">人事計画</div>
                            <div class="text-[14px]" v-if="!evaluationData?.candidate || !evaluationData?.candidate.length">未設定</div>
                            <div class="text-[14px]" v-for="candidate in evaluationData?.candidate">{{ candidate.next_candidate }}</div>
                        </div>
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">能力保有数</div>
                            <div class="text-[14px]">{{ evaluationData?.checklist?.length }}／{{ baseSkills.length }}</div>
                        </div>
                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">能力保有率</div>
                            <div class="text-[14px]">{{  !baseSkills.length ? '情報なし' : `${Math.round(evaluationData?.checklist?.length / baseSkills.length * 100)}%` }}</div>
                        </div>
                    </div>


                    <div class="mt-[30px] flex flex-col gap-[20px] px-2 leading-normal">                        
                        <div>
                            <div class="mb-[10px]">保有能力</div>
                            <div class="flex flex-col gap-5 text-[13px]">
                                <div v-for="skill in baseSkills">
                                    <div class="flex gap-3">
                                        <div>
                                            <svg v-if="Array.isArray(evaluationData?.checklist) && evaluationData.checklist.map(d => d.content.replace(/ /g, '')).includes(skill.replace(/ /g, ''))"
                                                fill="var(--primary-color)" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                                height="10" viewBox="0 0 38 32">
                                                <path
                                                    d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z">
                                                </path>
                                            </svg>
                                            <svg v-else version="1.1" fill="gray" xmlns="http://www.w3.org/2000/svg"
                                                height="10" viewBox="0 0 32 32">
                                                <path
                                                    d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z">
                                                </path>
                                            </svg>
                                        </div>

                                        <div
                                            :class="Array.isArray(evaluationData?.checklist) && evaluationData.checklist.map(d => d.content.replace(/ /g, '')).includes(skill.replace(/ /g, '')) ? 'text-[var(--primary-color)]' : 'text-[gray]'">
                                            {{ skill }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">昇格後のビジョン</div>
                            <div class="text-[14px] leading-normal whitespace-break-spaces">{{ evaluationData?.vision || '未設定' }}</div>
                        </div>

                        <div>
                            <div class="mb-[10px] text-[12px] text-[gray]">メンター記入欄</div>
                            <div class="text-[14px] leading-normal whitespace-break-spaces">{{ evaluationData?.mentor_comment || '未設定' }}</div>
                        </div>
                    </div>
                    <div class="mt-[30px] flex gap-[20px]">
                        <CommandButton v-if="computedMemberData && auth.user?.position_id !== 13 && (evaluationData.status == 0 || evaluationData.status == 1)" :buttons="[
                            {title: evaluationData.status == 0 ? '人事考課開始' : '編集する', action: () => handleClick(1)},
                        ]" />

                        <CommandButton v-if="auth.activeUser.id && computedMemberData && [610, 608, 631 ].includes(auth.activeUser.id) && evaluationData.status == 2" :buttons="[
                            {title:'承認する', action: () => updateStatus(3, '承認')},
                        ]" />
                        <CommandButton v-if="auth.activeUser.id && computedMemberData && [610, 608, 631 ].includes(auth.activeUser.id) && (evaluationData.status == 2 || evaluationData.status == 3)" :buttons="[
                            {title:'差し戻し', action: () => updateStatus(1, '差し戻し')},
                        ]" />

                        <CommandButton v-if="auth.activeUser.id && computedMemberData && [610, 608, 631, evaluationData?.mentor?.id ].includes(auth.activeUser.id) && evaluationData.status == 1" :buttons="[
                            {title:'申請する', action: () => updateStatus(2, '申請')},
                        ]" />
                    </div>

                </div>
                <div v-else class="no-comment-text">
                    現在レコードはありません。
                    または、人事担当者より事前設定を行っていません。
                </div>
            </div>


        </div>
            <Transition name="modalFade">
                <EvaluationCreationWithMentor 
                    v-if="createWindow"  
                    :date="date"
                    @reload="reload" @close="createWindow = false" />
            </Transition>
    </div>
</template>
<script setup lang="ts">
import { formatSalary, generalPositions, } from '@/utils/tools';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import EvaluationCreationWithMentor from './EvaluationCreationWithMentor.vue';
import { EvaluationRecord } from '@/interface/evaluationInterface';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useProject } from '@/composables/project';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
const props = defineProps([
    'date',
    'memberDataRemind'
])
const emit = defineEmits([
    'reload'
])  
const statuses = [
    {id: 0, name: '未開始', success: '作成しました'},
    {id: 1, name: '作成中', success: '保存しました。'},
    {id: 2, name: '申請中', success: '申請しました。'},
    {id: 3, name: '承認済み', success: '承認しました。'},
]
const { memberData } = useProject()
const initialLoader = ref(true)
const positions = generalPositions()
const router = useRouter()
const auth = useAuthUserStore()
const evaluationData = ref<EvaluationRecord | null>(null)
const route = useRoute()
const createWindow = ref(false)
const step = ref(0)
const baseSkills = ref<string[]>([])
const api = useApi()

const loading = ref(0)
onMounted(async () => {
    setTimeout(() => {
        getEvaluations()
    }, 100)
})

const { ping, ask, toast } = useDialog()

const currentPosition = computed(() => {
    return positions.find(ob => ob.name === evaluationData.value?.general_position)
})
const newPosition = computed(() => {
    return positions.find(ob => ob.name === evaluationData.value?.new_position)
})
const handleClick = (num: number) => {
    step.value = num
    createWindow.value = true
}
const reload = async () => {
    await getEvaluations()

}
const computedMemberData = computed(() => {
    return props.memberDataRemind || memberData.value
})
const updateStatus = async (status: number, message: string) => {
    const question = `${message}してもよろしいですか？`
    const confirm = await ask(question)
    if (!confirm.value) return

    await api.post('/set_increase_request', {
        attributes:{
            id: evaluationData.value?.id,
        },            
        params: {
            status: status
        }
    }, {
        toast: statuses[status].success,
    })
    
    emit('reload')
    await getEvaluations()

}
const getEvaluations = async () => {
    const span = `${props.date.year}-${props.date.which_half}` || route.params.span as string
    if (computedMemberData.value && span) {

        const params = {
            year: span?.split('-')[0],
            which_half: span?.split('-')[1],
            user_id: computedMemberData.value?.id
        }
        const data = await api.post('/get_evaluation_data', params, {
            loadingRef: initialLoader,
        })
        evaluationData.value = data.evaluation
        baseSkills.value = data.base_skills

    }

}
const gradeSplit = (rank: string | undefined) => {
    if (!rank) return
    const m = rank.match(/^\s*([０-９0-9一二三四五六七八九十]+等級)/u);
    const grade = m ? m[1] : null;
    return grade
}

</script>