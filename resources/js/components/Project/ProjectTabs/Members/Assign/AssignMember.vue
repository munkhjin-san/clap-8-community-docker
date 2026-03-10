<template>
    <Teleport to="body">
        <Modal @close="close">
            <template #title>
                メンバー詳細
            </template>
            <template #content>
                <Teleport to="body">
                    <AiLoader v-if="loading" message="適正評価中...この処理には数分かかる場合があります" />
                </Teleport>
                <UserPanel :user="member" :size="30" with-name/>
                <div class="mt-5 flex items-center">
                    役割: 
                    <select v-model="selectedRole" @change="saveRole" class="text-[var(--primary-color)] border border-solid border-[var(--formBorder)] ml-3 px-2 py-1">
                        <option :value="null">未割当</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">
                            {{ role.title }}
                        </option>
                    </select>
                    <div v-if="savingRole" class="ml-3">
                        <div class="spinner-nano" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
                <div class="mt-5 flex items-center">
                    適正度: {{ member?.pivot?.compatibility_number || '未評価' }} 
                    <span class="text-[11px] text-[gray] ml-2 mt-1" v-if="!member?.pivot?.role_record">(役割を設定してください)</span>
                    
                </div>
                <div class="mt-2">
                    <LoaderButton @click="evaluateMember" v-if="member?.pivot?.role_record" class="!m-0" content="評価する" :loading="loading">
                        <template #icon>
                            <AiIcon class="mr-2" fill="var(--primary-color)"/>
                        </template>
                    </LoaderButton>
                </div>
                <div v-if="asignEvaluationResult" class="mt-5 border border-solid border-[var(--formBorder)] rounded-lg p-4 bg-[var(--background-color)]">
                    <!-- ヘッダー -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">適正評価結果</h3>
                        <span class="text-xs text-gray-500">v{{ asignEvaluationResult.version }}</span>
                    </div>

                    <!-- 対象者・配属情報 -->
                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-gray-500">対象者:</span>
                            <span class="ml-2 font-medium">{{ asignEvaluationResult.employee.name }}</span>
                            <span v-if="asignEvaluationResult.employee.employee_id" class="ml-1 text-gray-400">({{ asignEvaluationResult.employee.employee_id }})</span>
                        </div>
                        <div>
                            <span class="text-gray-500">配属先:</span>
                            <span class="ml-2 font-medium">{{ asignEvaluationResult.assignment.project_name }} / {{ asignEvaluationResult.assignment.role_name }}</span>
                        </div>
                    </div>

                    <!-- 総合スコア・最終判定 -->
                    <div class="flex items-center gap-4 mb-4 p-3 rounded-lg" :class="getDecisionClass(asignEvaluationResult.final_judgement.decision)">
                        <div class="text-center">
                            <div class="text-3xl font-bold">{{ asignEvaluationResult.overall.score.toFixed(1) }}</div>
                            <div class="text-xs text-gray-500">総合スコア</div>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-bold">{{ asignEvaluationResult.final_judgement.decision }}</div>
                            <div class="text-sm mt-1">{{ asignEvaluationResult.final_judgement.rationale }}</div>
                        </div>
                    </div>

                    <!-- 条件（条件付き適正の場合） -->
                    <div v-if="asignEvaluationResult.final_judgement.conditions?.length" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="font-medium text-yellow-800 mb-2">条件事項</div>
                        <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                            <li v-for="(condition, index) in asignEvaluationResult.final_judgement.conditions" :key="index">
                                <span class="font-medium">{{ condition.title }}:</span> {{ condition.detail }}
                            </li>
                        </ul>
                    </div>

                    <!-- 評価項目一覧 -->
                    <div class="mb-4">
                        <h4 class="font-medium mb-3">評価項目</h4>
                        <div class="space-y-3">
                            <!-- 必須条件 -->
                            <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium">必須条件</span>
                                    <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(asignEvaluationResult.evaluations.must_conditions.score)">
                                        {{ asignEvaluationResult.evaluations.must_conditions.score }}/10
                                    </span>
                                </div>
                                <p class="text-sm">{{ asignEvaluationResult.evaluations.must_conditions.reason }}</p>
                                <div v-if="asignEvaluationResult.evaluations.must_conditions.evidence?.length" class="mt-2">
                                    <div class="text-xs text-gray-500">根拠:</div>
                                    <ul class="list-disc list-inside text-xs text-gray-500">
                                        <li v-for="(ev, i) in asignEvaluationResult.evaluations.must_conditions.evidence" :key="i">{{ ev }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- 職務適合性 -->
                            <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium">職務適合性</span>
                                    <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(asignEvaluationResult.evaluations.job_fit.score)">
                                        {{ asignEvaluationResult.evaluations.job_fit.score }}/10
                                    </span>
                                </div>
                                <p class="text-sm">{{ asignEvaluationResult.evaluations.job_fit.reason }}</p>
                                <div v-if="asignEvaluationResult.evaluations.job_fit.evidence?.length" class="mt-2">
                                    <div class="text-xs text-gray-500">根拠:</div>
                                    <ul class="list-disc list-inside text-xs text-gray-500">
                                        <li v-for="(ev, i) in asignEvaluationResult.evaluations.job_fit.evidence" :key="i">{{ ev }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- パフォーマンス履歴 -->
                            <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium">パフォーマンス履歴</span>
                                    <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(asignEvaluationResult.evaluations.performance_history.score)">
                                        {{ asignEvaluationResult.evaluations.performance_history.score }}/10
                                    </span>
                                </div>
                                <p class="text-sm">{{ asignEvaluationResult.evaluations.performance_history.reason }}</p>
                                <div v-if="asignEvaluationResult.evaluations.performance_history.evidence?.length" class="mt-2">
                                    <div class="text-xs text-gray-500">根拠:</div>
                                    <ul class="list-disc list-inside text-xs text-gray-500">
                                        <li v-for="(ev, i) in asignEvaluationResult.evaluations.performance_history.evidence" :key="i">{{ ev }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- リスク履歴 -->
                            <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium">リスク履歴</span>
                                    <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(asignEvaluationResult.evaluations.risk_history.score)">
                                        {{ asignEvaluationResult.evaluations.risk_history.score }}/10
                                    </span>
                                </div>
                                <p class="text-sm">{{ asignEvaluationResult.evaluations.risk_history.reason }}</p>
                                <div v-if="asignEvaluationResult.evaluations.risk_history.evidence?.length" class="mt-2">
                                    <div class="text-xs text-gray-500">根拠:</div>
                                    <ul class="list-disc list-inside text-xs text-gray-500">
                                        <li v-for="(ev, i) in asignEvaluationResult.evaluations.risk_history.evidence" :key="i">{{ ev }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 重み配分 -->
                    <div class="mb-4 text-xs text-gray-500">
                        <span>重み配分: </span>
                        <span>必須条件 {{ asignEvaluationResult.overall.weights.must_conditions * 100 }}% / </span>
                        <span>職務適合性 {{ asignEvaluationResult.overall.weights.job_fit * 100 }}% / </span>
                        <span>パフォーマンス {{ asignEvaluationResult.overall.weights.performance_history * 100 }}% / </span>
                        <span>リスク {{ asignEvaluationResult.overall.weights.risk_history * 100 }}%</span>
                    </div>

                    <!-- 注意事項 -->
                    <div v-if="asignEvaluationResult.notes.limitations?.length" class="text-xs text-gray-400 border-t border-[var(--formBorder)] pt-3">
                        <div class="font-medium mb-1">注意事項:</div>
                        <ul class="list-disc list-inside space-y-1">
                            <li v-for="(limitation, index) in asignEvaluationResult.notes.limitations" :key="index">{{ limitation }}</li>
                        </ul>
                    </div>

                    <!-- プロジェクトマネジャー確認項目 -->
                    <div v-if="asignEvaluationResult.project_manager_check_items?.length" class="pt-4 mt-4">
                        <h4 class="font-medium mb-3">確認項目</h4>
                        <div class="space-y-4">
                            <div v-for="(item, index) in asignEvaluationResult.project_manager_check_items" :key="index" class="p-3">
                                <!-- Checkbox Type -->
                                <div v-if="item.type === 'checkbox'" class="flex items-start gap-3">
                                    <input type="checkbox" v-model="item.answer" class="mt-1 w-4 h-4 cursor-pointer" />
                                    <label class="w-full text-[13px] leading-normal flex-1 cursor-pointer">{{ item.content }}</label>
                                </div>
                                
                                <!-- Short Text Type -->
                                <div v-else-if="item.type === 'shorttext'" class="flex flex-col gap-2">
                                    <p class="text-[13px] leading-normal w-full mb-2">{{ item.content }}</p>
                                    <input type="text" v-model="item.answer" class="text-[var(--primary-color)] border border-solid border-[var(--formBorder)] px-3 py-2 text-sm" placeholder="具体的な対応策を入力してください" />
                                </div>
                                
                                <!-- Long Text Type -->
                                <div v-else-if="item.type === 'longtext'" class="flex flex-col gap-2">
                                    <p class="text-[13px] leading-normal w-full mb-2">{{ item.content }}</p>
                                    <textarea :value="(item.answer as string) || ''" @input="item.answer = ($event.target as HTMLTextAreaElement).value" class="text-[var(--primary-color)] border border-solid border-[var(--formBorder)] px-3 py-2 text-sm" rows="3" placeholder="具体的な対応策を入力してください"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <LoaderButton @triggered="saveAssignData" :loading="savingAssignData" content="この内容で保存" />
                    </div>
                </div>
            </template>
        </Modal>
    </Teleport>
</template>
<script setup lang="ts">
import { ProjectMember } from "@/interface/projectInterface";
import Modal from '@/components/Global/Modal.vue';
import UserPanel from "@/components/Global/UserPanel.vue";
import { useProject } from "@/composables/project";
import { computed, ref } from "vue";
import { useApi } from "@/composables/api";
import LoaderButton from "@/components/Global/LoaderButton.vue";
import AiIcon from "@/components/Icons/AiIcon.vue";
import { AssignmentFitEvaluationResponse, Decision, Score1to10 } from "@/interface/assign";
import { useDialog } from "@/composables/dialog";
import AiLoader from "@/components/Global/AiLoader.vue";

const props = defineProps<{
    member: ProjectMember
}>();

const emit = defineEmits<{
    close: [flag:boolean]
}>();

const { selectedProject, refreshProject } = useProject()
const selectedRole = ref<number | null>(props.member?.pivot?.role_record?.id || null);
const asignEvaluationResult = ref<AssignmentFitEvaluationResponse | null>(props.member?.pivot?.assign_data || null);
const savingAssignData = ref(false);
const changedAssignData = ref<boolean>(false);
const safeExit = ref(true)

const roles = computed(() => {
    return selectedProject.value?.member_roles || [];
})

const api = useApi()
const { ask, ping, toast } = useDialog()
const savingRole = ref(false);
const loading = ref(false);
const close = async () => {
    if (!safeExit.value) {
        const confirmed = await ask('評価データが保存されていません。本当に閉じますか？')
        if(confirmed.value) {
            emit('close', true);
        }
    } else {
        emit('close', true);
    }
};
const saveRole = async (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const value = target.value;
    const roleId = target.value ? parseInt(target.value) : null;
    await api.post('/update_project_member_role', {
        project_id: selectedProject.value?.id,
        user_id: props.member.id,
        role_id: roleId
    }, {
        toast: '役割を更新しました。',
        loadingRef: savingRole
    });
    refreshProject()
    
};

const evaluateMember = async () => {
    loading.value = true;
    try {
        const data = await api.post('/evaluate_member', {
            project_id: selectedProject.value?.id,
            user_id: props.member.id,
            role_id: selectedRole.value
        });
        const parsedData = JSON.parse(data);
        asignEvaluationResult.value = parsedData;
        console.log(parsedData);
        // refreshProject();
    } finally {
        loading.value = false;
        changedAssignData.value = true;
        safeExit.value = false;
    }
};

const getScoreClass = (score: Score1to10): string => {
    if (score >= 8) return 'bg-green-100 text-green-800';
    if (score >= 6) return 'bg-blue-100 text-blue-800';
    if (score >= 4) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
};

const getDecisionClass = (decision: Decision): string => {
    switch (decision) {
        case '適正あり':
            return 'bg-green-50 border border-green-200 text-[black]';
        case '条件付き適正':
            return 'bg-blue-50 border border-blue-200 text-[black]';
        case '要再検討':
            return 'bg-yellow-50 border border-yellow-200 text-[black]';
        case '不適':
            return 'bg-red-50 border border-red-200 text-[black]';
        default:
            return 'bg-gray-50 border border-gray-200 text-gray-800';
    }
};

const saveAssignData = async () => {
    if (!asignEvaluationResult.value) return;
    savingAssignData.value = true;

    console.log('Saving assign data:', asignEvaluationResult.value);
    try {
        const res = await api.post('/save_member_assign_data', {
            project_id: selectedProject.value?.id,
            user_id: props.member.id,
            assign_data: asignEvaluationResult.value
        }, {
            toast: '保存しました。'
        });
        refreshProject();
        if(res){
            toast('保存しました。');
        }        
    } finally {
        savingAssignData.value = false;
        safeExit.value = true;
        changedAssignData.value = false;
    }
};
</script>