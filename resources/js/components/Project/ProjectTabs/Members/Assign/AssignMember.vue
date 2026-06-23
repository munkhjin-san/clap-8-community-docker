<template>
    <Teleport to="body">
        <Transition name="modalFade">            
        <Modal @close="close" size="large" :loader="initializing">
            <template #title>
                メンバーの役割と適合評価
            </template>
            <template #content>
                <Teleport to="body">
                    <AiLoader v-if="loading" message="適合評価中...この処理には数分かかる場合があります" />
                </Teleport>
                <Teleport to="body">
                    <div v-if="refreshing" class="fixed inset-0 flex items-center justify-center z-50">
                        <div class="spinner-mini"></div>
                    </div>
                </Teleport>
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <UserPanel v-if="member" :user="member" :size="30" with-name>
                        <template #details>
                            <p class="ml-2 text-[tomato] text-[11px] mt-1" v-if="!isProjectMember">プロジェクトメンバーでないユーザーです</p>
                        </template>
                    </UserPanel>
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
                </div>
                <div v-if="!assignData" class="flex flex-col w-full items-center bg-[var(--bg3)] my-6 rounded py-6 justify-center gap-4">                
                    <div class="flex items-center text-sm text-[gray]">                        
                        現在、適合評価データがありません。                        
                    </div>
                    <div class="mt-2">
                        <LoaderButton @click="evaluateMember" v-if="member && selectedRole" class="!m-0" content="評価を行う" :loading="loading">
                            <template #icon>
                                <AiIcon class="mr-2" fill="currentColor"/>
                            </template>
                        </LoaderButton>
                        <div v-else class="text-sm text-[gray]">
                            適合評価を行うには、まず役割を割り当ててください。
                        </div>
                    </div>
                </div>
                <div v-if="assignData" class="my-6 bg-[var(--background-color)]">                  
                    <div v-if="assignData.assign_data" class="mt-5">
                        <div class="flex items-center flex-wrap justify-between mb-4 gap-2">   
                            <div class="text-right">
                                <span class="text-sm text-[gray]">ステータス:</span>
                                <span class="ml-2 px-2 py-1 rounded text-sm font-bold" :class="{
                                    'bg-green-100 text-green-800': assignData.status === '完了',
                                    'bg-yellow-100 text-yellow-800': assignData.status === '作成中',
                                    'bg-gray-100 text-gray-800': assignData.status === '人事対応中',
                                    'bg-blue-100 text-blue-800': assignData.status === '本人確認中',
                                    'bg-red-100 text-red-800': assignData.status === '本人取り下げ',
                                }">
                                    {{ assignData.status }}
                                </span>
                            </div>
                            <div class="flex gap-3 items-center">
                                <label :for="`sel-input=${assignData.id}`" class="text-xs cursor-pointer text-[gray]">評価日時:
                                    <span class="ml-2">{{ DateTime.fromISO(assignData.created_at).toLocaleString(DateTime.DATETIME_MED) }}</span>
                                </label>
                                <button
                                    type="button"
                                    @click="removeAssignment"
                                    :disabled="removingAssignment"
                                    class="py-1 px-3 bg-[var(--bg3)] rounded text-xs text-[gray] cursor-pointer border-none disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <Trash size="15"/>
                                </button>
                            </div>
                            
                        </div>             
                        <div class="post-separetor"><div>AIによる評価結果</div></div>        
                        <div class="flex items-center gap-4 mb-7 p-3 rounded-lg" :class="getDecisionClass(assignData.assign_data.final_judgement.decision)">
                            <div class="text-center">
                                <div class="text-3xl font-bold">{{ assignData.assign_data.overall.score.toFixed(1) }}</div>
                                <div class="text-xs text-[gray]">総合スコア</div>
                            </div>
                            <div class="flex-1">
                                <div class="text-lg font-bold">{{ assignData.assign_data.final_judgement.decision }}</div>
                                <div class="text-sm mt-1">{{ assignData.assign_data.final_judgement.rationale }}</div>
                            </div>
                        </div>

                        <!-- 条件（条件付き適合の場合） -->
                        <div v-if="assignData.assign_data.final_judgement.conditions?.length" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="font-medium text-yellow-800 mb-2">条件事項</div>
                            <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                                <li v-for="(condition, index) in assignData.assign_data.final_judgement.conditions" :key="index">
                                    <span class="font-medium">{{ condition.title }}:</span> {{ condition.detail }}
                                </li>
                            </ul>
                        </div>
                        

                        <!-- 評価項目一覧 -->
                        <div class="mb-4">
                            <div class="space-y-7">
                                <!-- 必須条件 -->
                                <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium">必須条件</span>
                                        <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(assignData.assign_data.evaluations.must_conditions.score)">
                                            {{ assignData.assign_data.evaluations.must_conditions.score }}/10
                                        </span>
                                    </div>
                                    <p class="text-sm">{{ assignData.assign_data.evaluations.must_conditions.reason }}</p>
                                    <div v-if="assignData.assign_data.evaluations.must_conditions.evidence?.length" class="mt-2">
                                        <div class="text-xs text-[gray]">根拠:</div>
                                        <ul class="list-disc list-inside text-xs text-[gray]">
                                            <li v-for="(ev, i) in assignData.assign_data.evaluations.must_conditions.evidence" :key="i">{{ ev }}</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- 職務適合性 -->
                                <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium">職務適合性</span>
                                        <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(assignData.assign_data.evaluations.job_fit.score)">
                                            {{ assignData.assign_data.evaluations.job_fit.score }}/10
                                        </span>
                                    </div>
                                    <p class="text-sm">{{ assignData.assign_data.evaluations.job_fit.reason }}</p>
                                    <div v-if="assignData.assign_data.evaluations.job_fit.evidence?.length" class="mt-2">
                                        <div class="text-xs text-[gray]">根拠:</div>
                                        <ul class="list-disc list-inside text-xs text-[gray]">
                                            <li v-for="(ev, i) in assignData.assign_data.evaluations.job_fit.evidence" :key="i">{{ ev }}</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- パフォーマンス履歴 -->
                                <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium">パフォーマンス履歴</span>
                                        <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(assignData.assign_data.evaluations.performance_history.score)">
                                            {{ assignData.assign_data.evaluations.performance_history.score }}/10
                                        </span>
                                    </div>
                                    <p class="text-sm">{{ assignData.assign_data.evaluations.performance_history.reason }}</p>
                                    <div v-if="assignData.assign_data.evaluations.performance_history.evidence?.length" class="mt-2">
                                        <div class="text-xs text-[gray]">根拠:</div>
                                        <ul class="list-disc list-inside text-xs text-[gray]">
                                            <li v-for="(ev, i) in assignData.assign_data.evaluations.performance_history.evidence" :key="i">{{ ev }}</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- リスク履歴 -->
                                <div class="border border-solid border-[var(--formBorder)] rounded p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium">リスク履歴</span>
                                        <span class="px-2 py-1 rounded text-sm font-bold" :class="getScoreClass(assignData.assign_data.evaluations.risk_history.score)">
                                            {{ assignData.assign_data.evaluations.risk_history.score }}/10
                                        </span>
                                    </div>
                                    <p class="text-sm">{{ assignData.assign_data.evaluations.risk_history.reason }}</p>
                                    <div v-if="assignData.assign_data.evaluations.risk_history.evidence?.length" class="mt-2">
                                        <div class="text-xs text-[gray]">根拠:</div>
                                        <ul class="list-disc list-inside text-xs text-[gray]">
                                            <li v-for="(ev, i) in assignData.assign_data.evaluations.risk_history.evidence" :key="i">{{ ev }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 重み配分 -->
                        <div class="mb-4 text-xs text-[gray]">
                            <span>重み配分: </span>
                            <span>必須条件 {{ assignData.assign_data.overall.weights.must_conditions * 100 }}% / </span>
                            <span>職務適合性 {{ assignData.assign_data.overall.weights.job_fit * 100 }}% / </span>
                            <span>パフォーマンス {{ assignData.assign_data.overall.weights.performance_history * 100 }}% / </span>
                            <span>リスク {{ assignData.assign_data.overall.weights.risk_history * 100 }}%</span>
                        </div>

                        <!-- 注意事項 -->
                        <div v-if="assignData.assign_data.notes.limitations?.length" class="text-xs text-[gray] border-t border-[var(--formBorder)] pt-3">
                            <div class="font-medium mb-1">注意事項:</div>
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="(limitation, index) in assignData.assign_data.notes.limitations" :key="index">{{ limitation }}</li>
                            </ul>
                        </div>
                        
                       <ManagerArea 
                            v-if="assignData"
                            :assign-data="assignData" 
                            @update="refreshData"
                        />
                        <!-- 対応レベル -->
                        <div class="post-separetor mt-5"><div>人事対応</div></div>  
                        <div v-if="auth.isAdmin" class="mb-4 p-5 bg-[var(--selected-background)] border border-blue-200 rounded-lg">
                            
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-medium">対応必要性</span>
                                <select
                                    v-model="assignData.support_level"
                                    @change="updateSupportLevel"
                                    :disabled="!auth.isAdmin"
                                    class="px-2 py-1 rounded text-sm font-bold focus:outline-none min-w-[90px] cursor-pointer"
                                    :class="{
                                        'bg-green-100 text-green-800 border border-green-200': assignData.support_level === 'green',
                                        'bg-orange-100 text-orange-800 border border-orange-200': assignData.support_level === 'orange',
                                        'bg-red-100 text-red-800 border border-red-200': assignData.support_level === 'red',
                                    }"
                                >
                                    <option value="green">対応完了</option>
                                    <option value="orange">通常対応</option>
                                    <option value="red">重点対応</option>
                                </select>
                            </div>
                            <div>
                                <span class="text-sm">対応提案:</span>
                                <ul v-if="assignData.assign_data.support_level?.support_suggestions?.length" class="list-disc list-inside text-[13px] leading-normal text-[gray] mt-1 space-y-1">
                                    <li v-for="(suggestion, idx) in assignData.assign_data.support_level?.support_suggestions" :key="idx">
                                        {{ suggestion }}
                                    </li>
                                </ul>
                                <span v-else class="text-xs text-[gray] ml-2">特に提案はありません</span>
                            </div>
                            <div class="mb-4">対応履歴（非公開）</div>
                            <div v-if="assignData.actions?.length" class="my-4">
                                
                                <div class="space-y-9 text-sm">
                                    <div v-for="message in assignData.actions">
                                        <div class="bg-[var(--message-background)] p-4"  v-if="message.action_type === 'message'">
                                            <div class="flex gap-4 flex-wrap items-center">
                                                <UserPanel size="20" v-if="message.user" :user="message.user" with-name />
                                                <div class="text-[12px] text-[gray]">{{ DateParser(message.created_at) }}</div>
                                            </div>
                                            
                                            <div v-if="message.action_type === 'message'" class="mt-2">
                                                <p class="whitespace-break-spaces" v-html="urlCheck(message.content)"></p>
                                            </div>
                                        </div>
                                        
                                        <div v-else-if="message.action_type === 'support_level_change'" class="mt-2 text-[12px]">
                                            <p v-if="message.additional_data">
                                                <span>【{{DateParser(message.created_at)}}】</span>
                                                <span class="text-[gray]">対応レベルが <span :class="message.additional_data.previous_level.class">{{ message.additional_data.previous_level.label }}</span> から <span :class="message.additional_data.new_level.class">{{ message.additional_data.new_level.label }}</span> に変更されました。</span>
                                                <span>【{{message.user?.name}}】</span>
                                            </p>
                                        </div>

                                        <div v-else-if="message.action_type === 'member_confirmation_items'" class="mt-2 text-[12px] bg-[var(--message-background)] p-4">
                                            <p>
                                                <span>【{{DateParser(message.created_at)}}】</span>
                                                <span class="text-[gray]">本人確認事項を本人へ申請しました（本人共有）。</span>
                                                <span>【{{message.user?.name}}】</span>
                                            </p>
                                            <div class="mt-2 ">
                                                <p class="whitespace-break-spaces" v-html="urlCheck(message.content)"></p>
                                            </div>
                                        </div>
                                        <div v-else-if="message.action_type === 'member_decision'">
                                            <div v-if="message.additional_data.decision == 'rejected'" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                                                <div class="mb-2 text-[gray] text-[12px]">{{DateParser(message.created_at)}}</div>
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="font-bold text-yellow-800">
                                                        {{ message.additional_data.decision == 'rejected' ? '申請内容を取り下げました' : '' }}
                                                    </span>
                                                </div>
                                                <div v-if="message.additional_data.comment" class="text-sm text-yellow-700 whitespace-pre-wrap">
                                                    <span class="font-semibold">取り下げ理由:</span>
                                                    {{ message.additional_data.comment }}
                                                </div>
                                            </div> 
                                            <div v-else-if="message.additional_data.decision == 'approved'" class="mb-6 p-4 bg-green-50 border border-green-200 rounded">
                                                <div class="mb-2 text-[gray] text-[12px]">{{DateParser(message.created_at)}}</div>
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="font-bold text-green-800">
                                                        {{ message.additional_data.decision == 'approved' ? '本人が申請内容を承認しました' : '' }}
                                                    </span>
                                                </div>
                                                <div v-if="message.additional_data.comment" class="text-sm text-green-700 whitespace-pre-wrap">
                                                    <span class="font-semibold">本人コメント:</span>
                                                    {{ message.additional_data.comment }}
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-end">
                                <textarea
                                    v-model="actionText"
                                    :disabled="!auth.isAdmin"
                                    class="text-[var(--primary-color)] border border-solid border-[var(--formBorder)] px-3 py-2 text-sm mt-3 w-full"
                                    rows="4"
                                    placeholder="具体的な対応策を入力（非公開）"
                                ></textarea>
                                <button @click="addAction" class="bg-inherit ml-2">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="33" viewBox="0 0 43 32" style="margin: auto; fill: var(--third-color);">
                                        <path d="M40.638 0.087c-1.842 0.361-6.097 1.292-9.435 2.047l-30.046 6.891c-0.419 0.096-0.793 0.374-1.003 0.793-0.364 0.728-0.058 1.585 0.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56 0.287 0.157 0.487 0.439 0.542 0.762 0 0 0.711 4.473 0.921 5.891 0.21 1.417 0.714 4.465 1.184 6.482 0.168 0.726 0.631 1.335 1.215 1.512 0.495 0.152 1.030 0.037 1.43-0.285 1.394-1.128 5.787-5.445 7.388-7.272 0.133-0.152 0.355-0.19 0.531-0.085l6.184 3.646c0 0 0.439 0.294 0.919 0.519 1.283 0.601 2.479 0.625 3.062-0.829 0.325-0.813 4.316-12.627 4.316-12.627l4.466-13.209c0.053-0.152 0.082-0.321 0.082-0.492 0-0.844-0.654-1.675-2.496-1.312zM20.045 24.741c-0.475 0.477-1.473 1.473-2.284 2.197-0.155 0.137-0.385-0.002-0.313-0.195l1.796-4.842c0.051-0.157 0.236-0.226 0.378-0.142l1.796 1.054c0.157 0.091 0.161 0.294 0.041 0.432-0.401 0.458-0.975 1.058-1.413 1.495zM32.151 25.117c-0.106 0.325-0.482 0.47-0.777 0.301l-1.447-0.824-3.554-2.014-7.121-4.024c-0.067-0.037-0.138-0.068-0.214-0.094-0.677-0.232-1.411 0.13-1.64 0.808l-1.944 7.086c-0.053 0.166-0.229 0.143-0.251-0.046-0.13-1.23-0.328-3.178-0.467-4.759-0.13-1.459-0.366-3.357-0.494-4.434-0.111-0.931-0.427-1.423-1.131-1.837-0.704-0.415-6.489-3.354-7.668-4.049-0.241-0.142-0.166-0.415 0.065-0.463 0 0 13.334-2.689 16.022-3.304 2.689-0.617 10.513-2.447 10.513-2.447 0.103-0.025 0.152 0.118 0.056 0.161l-5.127 2.281-2.961 1.459c-0.987 0.487-7.32 3.516-9.259 4.562-0.477 0.258-0.665 0.871-0.373 1.36 0.255 0.429 0.808 0.574 1.265 0.374 2.004-0.882 16.208-7.766 17.651-8.441 0.345-0.162 0.376-0.012 0.287 0.049-0.89 0.615-9.43 6.896-10.25 7.528l-2.448 1.905c-0.432 0.342-0.519 0.976-0.173 1.42 0.335 0.432 0.965 0.497 1.413 0.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66c0 0 5.775-4.365 6.187-4.682 0.166-0.128 0.397 0.033 0.331 0.234l-2.517 7.675-3.585 10.965z"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="mt-10" v-if="assignData.status === '人事対応中'" >
                                <div class="post-separetor mt-5"><div>本人確認事項</div></div>
                                <LongInput v-model="memberConfirmationItems" place-holder="本人に確認事項を入力してください"/>
                                <div class="flex justify-center gap-5 flex-wrap mt-5 mb-3">
                                    <LoaderButton style="margin:0" @triggered="applyToMember" :loading="savingAssignData" content="本人へ申請する" /> 
                                </div>
                            </div>

                            <div class="mt-10" v-if="assignData.status === '本人取り下げ'" >
                                <div class="post-separetor mt-5"><div>本人確認事項（修正用）</div></div>
                                <LongInput v-model="memberConfirmationItems" place-holder="修正内容を入力して再申請してください"/>
                                <div class="flex justify-center gap-5 flex-wrap mt-5 mb-3">
                                    <LoaderButton style="margin:0" @triggered="reapplyToMember" :loading="savingAssignData" content="再申請する" /> 
                                </div>
                            </div>
                        </div>                        
                    </div>
                </div>
            </template>
        </Modal>
        </Transition>   
    </Teleport>
</template>
<script setup lang="ts">
import { ProjectAssignRecord, ProjectMember } from "@/interface/projectInterface";
import Modal from '@/components/Global/Modal.vue';
import UserPanel from "@/components/Global/UserPanel.vue";
import { useProject } from "@/composables/project";
import { computed, onMounted, reactive, ref } from "vue";
import { useApi } from "@/composables/api";
import LoaderButton from "@/components/Global/LoaderButton.vue";
import AiIcon from "@/components/Icons/AiIcon.vue";
import { Decision, Score1to10 } from "@/interface/assign";
import { useDialog } from "@/composables/dialog";
import AiLoader from "@/components/Global/AiLoader.vue";
import { DateTime } from "luxon";
import { useAuthUserStore } from "@/store/auth";
import { DateParser, urlCheck } from "@/utils/tools";
import ManagerArea from "./ManagerArea.vue";
import Trash from "@/components/Icons/Trash.vue";
import LongInput from "@/components/Form/LongInput.vue";
import { useRoute } from "vue-router";

const props = defineProps<{
    assignDataList: ProjectAssignRecord[]
}>();

const emit = defineEmits<{
    close: [flag:boolean]
    update: []
}>();

const assignData = ref<ProjectAssignRecord | null>(null);
const member = ref<ProjectMember | null>(null);
const auth = useAuthUserStore()
const { selectedProject, refreshProject } = useProject()
const selectedRole = ref<number | null>(null);
const savingAssignData = ref(false);
const changedAssignData = ref<boolean>(false);
const safeExit = ref(true)
const actionText = ref('');
const removingAssignment = ref(false);
const memberConfirmationItems = ref('');



const roles = computed(() => {
    return selectedProject.value?.member_roles || [];
})

const isProjectMember = computed(() => {
    if(!member.value || !selectedProject.value) return false;
    return selectedProject.value.members.some(m => m.id === member.value?.id);
})

const api = useApi()
const { ask, ping, toast } = useDialog()
const savingRole = ref(false);
const loading = ref(false);
const initializing = ref(true);
const refreshing = ref(false);
const route = useRoute();
onMounted(() => {
    console.log(route.params.memberId)
    if(route.params.memberId){
        initialize(Number(route.params.memberId));
    }
});
const initialize = async(memberId: number) => {
    if(!memberId || !selectedProject.value) return;
    refreshing.value = true;
    await getMemberData();
    const findRole = props.assignDataList.find(assignData => assignData.user_id === memberId);
    if(findRole && findRole.project_member_role){
        selectedRole.value = findRole.project_member_role.id;
    } else {
        selectedRole.value = null;
    }   
    refreshing.value = false;
    initializing.value = false;
}
const getMemberData = async () => {
    if(!selectedProject.value) return;
    try {
        const data = await api.post('/get_non_member_assign_data', {

            project_id: selectedProject.value.id,
            user_id: Number(route.params.memberId)
        });

        member.value = data.user;
        assignData.value = data.assign_records;
        
    } catch (error) {
        console.error('Failed to fetch non-member assign data:', error);
    }
};

const close = async () => {    
    emit('close', true);    
};
const saveRole = async (event: Event) => {
    if(!member.value || !isProjectMember.value) return;
    const target = event.target as HTMLSelectElement;
    const value = target.value;
    const roleId = target.value ? parseInt(target.value) : null;
    await api.post('/update_project_member_role', {
        project_id: selectedProject.value?.id,
        user_id: member.value.id,
        role_id: roleId
    }, {
        toast: '役割を更新しました。',
        loadingRef: savingRole
    });
    refreshProject()    
};

const evaluateMember = async () => {
    if(!member.value) return;
    if(assignData.value){
        const reEvaluate = await ask('既に適合評価データがあります。再評価を行うと上書きされますが、よろしいですか？');
        if(!reEvaluate.value){
            return;
        }
    }
    loading.value = true;
    try {
        const data = await api.post('/evaluate_member', {
            project_id: selectedProject.value?.id,
            user_id: member.value.id,
            role_id: selectedRole.value
        });
        if(data !== null){
            toast('適合評価が完了しました。');
            refreshData();          
        }
        // refreshProject();
    } finally {
        loading.value = false;
        changedAssignData.value = true;
        safeExit.value = false;
    }
};

const removeAssignment = async () => {
    if (!assignData.value) {
        ping('削除対象の評価データがありません。');
        return;
    }

    const confirmed = await ask('この適合評価データを削除しますか？');
    if (!confirmed.value) {
        return;
    }

    try {
        const res = await api.del(`/delete_assign_record/${assignData.value.id}`, null, {
            toast: '適合評価データを削除しました。',
            loadingRef: removingAssignment,
        });

        if (res !== null) {
            safeExit.value = true;
            await refreshProject();
            
            refreshData();
            emit('close', true);
        }
    } catch (error) {
        console.error('Failed to delete assignment record:', error);
    }
};

const updateSupportLevel = async (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const value = target.value as 'green' | 'orange' | 'red';
    if(!assignData.value) return;
    try {
        await api.post('/update_assign_support_level', {
            assign_record_id: assignData.value.id,
            support_level: value
        }, {
            toast: '対応必要性を更新しました。'
        });
        refreshData();
    } catch (error) {
        console.error('Failed to update support level:', error);
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


const addAction = async () => {
    if(!actionText.value.trim()) {
        ping('対応策を入力してください。');
        return;
    }
    if(!assignData.value) return;
    try {
        await api.post('/add_assign_action', {
            assign_record_id: assignData.value.id,
            content: actionText.value
        }, {
            toast: '保存しました'
        });
        actionText.value = '';
        refreshData();
    } catch (error) {
        console.error('Failed to add action:', error);
    }
};

const applyToMember = async () => {
    if (!assignData.value) return;
    if (assignData.value.status !== '人事対応中') {
        ping('現在のステータスでは申請できません。');
        return;
    }
    if (!memberConfirmationItems.value.trim()) {
        ping('本人に共有する確認事項を入力してください。');
        return;
    }

    try {
        await api.post('/apply_assign_data_to_member', {
            assign_record_id: assignData.value.id,
            member_confirmation_items: memberConfirmationItems.value,
        }, {
            toast: '本人へ申請しました。',
            loadingRef: savingAssignData,
        });
        memberConfirmationItems.value = '';
        refreshData();
    } catch (error) {
        console.error('Failed to apply assignment to member:', error);
    }
};

const reapplyToMember = async () => {
    if (!assignData.value) return;
    if (assignData.value.status !== '本人取り下げ') {
        ping('現在のステータスでは再申請できません。');
        return;
    }
    if (!memberConfirmationItems.value.trim()) {
        ping('修正内容を入力してください。');
        return;
    }

    try {
        await api.post('/reapply_assign_data_to_member', {
            assign_record_id: assignData.value.id,
            member_confirmation_items: memberConfirmationItems.value,
        }, {
            toast: '本人へ再申請しました。',
            loadingRef: savingAssignData,
        });
        memberConfirmationItems.value = '';
        refreshData();
    } catch (error) {
        console.error('Failed to reapply assignment to member:', error);
    }
};
const refreshData = async () => {
    emit('update');
    if(member.value){
        await initialize(member.value.id);
    }

}
</script>
<style scoped>
.support_green{
    background-color: rgb(220 252 231);
    color: rgb(21 128 61);
    padding: 3px 8px;
}
.support_orange{
    background-color: rgb(255 243 224);
    color: rgb(194 65 12);
    padding: 3px 8px;
}
.support_red{
    background-color: rgb(254 226 226);
    color: rgb(153 27 27);
    padding: 3px 8px;
}
</style>
