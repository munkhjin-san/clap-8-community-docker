<template>
    <div class="overlay" v-if="goal">
        <div class="chatCreate kadaiCreate scrollable">
            <div class="recordFormTitle" style="display:flex;"> 
                <div class="admin-command-bar">            
                    <div class="sub-tab-container">
                        <div @click="sub_tab = 0" :class="['sub-tab-item flex gap-[3px]', { 'selected-sub-tab': sub_tab == 0 }]">成果目標
                            <span 
                                class="side-notification" 
                                style="position: unset;width:15px" 
                                v-if="badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length"
                                :class="{
                                    'side-notification--comment-only': !badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length && badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length
                                }"
                            >
                                {{ badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length }}
                            </span>
                        </div>
                        <div @click="sub_tab = 1, badge.clearGoalIssue({column: 'salary_issue_id', value: goal?.salary_issue?.id})" :class="['sub-tab-item flex gap-[3px]', { 'selected-sub-tab': sub_tab == 1 }]">昇給課題
                            <span 
                                class="side-notification" 
                                style="position: unset;width:15px" 
                                v-if="badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'salary_issue_id', value: goal?.salary_issue?.id}]).length"
                                :class="{
                                    'side-notification--comment-only': !badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}]).length && badge.goalIssueCommentBadgeByFilter([{by: 'salary_issue_id', value: goal?.salary_issue?.id}]).length
                                }"
                            >
                                {{ badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'salary_issue_id', value: goal?.salary_issue?.id}]).length }}
                            </span>
                        </div>
                    </div>       
                </div>
                <div class="cursor-pointer" @click="router.back()" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div class="kadai-root !w-[calc(100%-2px)] ml-[1px]">                
                <div v-if="sub_tab === 0" class="flex flex-col gap-[30px] relative">

                    <div>
                        <div class="text-[13px] font-semibold">該当部門 ／ 職能レベル ／ 担当者</div>
                        <div class="kadai-content">{{ goal?.project?.name }} ／ {{ evaluationData?.current_level ?? '未設定' }} ／ {{ memberData?.name }}</div>
                    </div>
                    <div class="pc absolute top-0 right-0">
                        <div class="px-[10px] py-[5px] bg-[var(--bg3)] text-[12px]">{{ statuses[goal?.status] }}</div>
                    </div>
                    <div class="mobile w-fit">
                        <div class="text-[13px] font-semibold">ステータス</div>
                        <div class="px-[10px] py-[5px] bg-[var(--bg3)] text-[12px] mt-[10px]">{{ statuses[goal?.status] }}</div>
                    </div>
                    <div 
                        v-if="canManageStatus" 
                        class="flex flex-wrap items-center gap-[10px] bg-[var(--bg3)] px-[10px] py-[8px]"
                    >
                        <div class="text-[12px] font-semibold whitespace-nowrap">人事専用ステータス変更</div>
                        <select 
                            v-model.number="selectedGoalStatus" 
                            class="py-[6px] text-[var(--primary-color)] px-[10px] text-[13px] bg-[var(--background-color)] border border-solid border-[var(--formBorder)] min-w-[180px]"
                        >
                            <option v-for="(label, index) in statuses" :key="index" :value="index">
                                {{ label }}
                            </option>
                        </select>
                        <LoaderButton style="margin: 0;" :content="'更新'" @click="updateGoalStatusDirectly" />
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">タイトル</div>
                        <div class="kadai-content">{{ goal?.title }}</div>
                    </div>
                    <div v-if="goal?.stakeholder_name">
                        <div class="text-[13px] font-semibold">主なステークホルダー（価値提供先）</div>
                        <div class="kadai-content">{{ goal?.stakeholder_name }}</div>
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">期間</div>
                        <div class="kadai-content">{{ `${DateTime.fromISO(goal.start_date).toLocaleString()} ~ ${DateTime.fromISO(goal.end_date).toLocaleString()}` }}</div>
                    </div>
                    <div v-if="goal?.outcome_goal">
                        <div class="text-[13px] font-semibold">成果目標</div>
                        <div class="kadai-content">{{ goal?.outcome_goal }}</div>
                    </div>
                    <div v-if="goal?.private_memo">
                        <div class="text-[13px] font-semibold">メモ</div>
                        <div class="kadai-content">{{ goal?.private_memo }}</div>
                    </div>                    
                    <div v-if="goal?.miso">
                        <div class="text-[13px] font-semibold">MISO</div>
                        <div class="kadai-content">{{ goal?.miso }}</div>
                    </div>                    
                    <!-- <div v-if="goal?.kgi">
                        <div class="text-[13px] font-semibold">KGI</div>
                        <div class="kadai-content">{{ goal?.kgi }}</div>
                    </div>                     -->
                    <div v-if="goal?.situation_analysis">
                        <div class="text-[13px] font-semibold">現状分析</div>
                        <div class="kadai-content">{{ goal?.situation_analysis }}</div>
                    </div>                    
                    <div v-if="goal?.action_plan">
                        <div class="text-[13px] font-semibold">行動計画</div>
                        <div class="kadai-content">{{ goal?.action_plan }}</div>
                    </div>
                    <div v-if="goal?.expected_effect">
                        <div class="text-[13px] font-semibold">期待される効果</div>
                        <div class="kadai-content">{{ goal?.expected_effect }}</div>
                    </div>
                    <div v-if="goal?.ai_review">
                        <div class="text-[13px] font-semibold">AI判定とフィードバック</div>
                        <div class="kadai-content">{{ goal?.ai_review }}</div>
                    </div>
                    <!-- <div>
                        <div class="text-[13px] font-semibold">KGI達成率</div>
                        <div class="mt-[10px]">
                            <ProgressSlider :goal-id="goal.id" :disabled="!reviewReport" :progress="goal.achievement_rate" type="kgi"/>
                        </div>                         
                    </div>
                    <div v-if="goal.steps && goal.steps.length">
                        <div class="mb-[10px] text-[13px] font-semibold">KPI達成率 {{ kpiCalculation(goal.steps) }}%</div>
                        <div class="flex flex-col gap-[15px]">
                            <div v-for="step in goal.steps" :key="step.id" class="kadai-content flex gap-[10px]">
                                <ProgressSlider :goal-id="goal.id" :disabled="!reviewReport" :progress="step.progress" type="kpi" :step-id="step.id"/>
                                <div>{{ step.content }}</div>
                            </div>
                        </div>
                    </div> -->
                    <div v-if="goal.steps && goal.steps.length">
                        <div class="text-[13px] font-semibold mb-[10px]">成果指標</div>
                        <table>
                            <tbody>
                                <tr>
                                    <td>KGI</td>
                                    <td>{{ goal?.kgi }}</td>
                                    <td>
                                        <ProgressSlider :goal-id="goal.id" :disabled="!reviewReport" :progress="goal.achievement_rate" type="kgi"/>
                                    </td>
                                </tr>                          
                                <tr v-for="(step, index) in goal.steps" :key="index">
                                    <td v-if="index == 0" :rowspan="goal.steps.length">KPI</td>
                                    <td>{{ step.content }}</td>
                                    <td>
                                        <ProgressSlider :goal-id="goal.id" :disabled="!reviewReport" :progress="step.progress" type="kpi" :step-id="step.id"/>
                                    </td>                                    
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">評価点</div>
                        <div class="kadai-content">{{ overallScore }}点</div>
                    </div>
                    <div v-if="goal?.report">
                        <div class="mb-[10px] text-[13px] font-semibold">結果報告</div>
                        <div class="kadai-content">{{ goal?.report }}</div>
                        <Files style="margin-top: 15px;" v-if="goal?.files?.length" :items="goal?.files" :path="'project_files'"/>
                    </div>
                    <div v-if="goal?.result">
                        <div class="text-[13px] font-semibold">結果報告</div>
                        <div class="kadai-content">{{ goal?.result }}</div>
                    </div>
                    <div v-if="goal?.stakeholder_review">
                        <div class="text-[13px] font-semibold">ステークホルダーからの反応</div>
                        <div v-if="goal?.stakeholder_point" class="mt-[10px]">{{ scoreMap[goal.stakeholder_point] }}</div>
                        <div class="kadai-content">{{ goal?.stakeholder_review }}</div>
                    </div>
                    <MessageArea which="goal" :item="goal" :key="`message-area-goal-${goal.id}`" @refresh="() => refresh()"/>
                        
                    <div style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <!-- <LoaderButton v-if="reviewReport" @click="projectGoalReportCreate = goal" style="margin: 0;" :content="'進捗報告'"/> -->
                        <LoaderButton v-if="reviewReport" @click="progressReport(false)" style="margin: 0;" :content="'結果申請'"/>
                        <LoaderButton v-if="managerOrDirector && goal?.status === 7" @click="progressReport(true)" style="margin: 0;" :content="'結果承認'"/>
                    </div>

                    <div v-if="(selectedProject?.id === goal?.project?.id && isManagerOrMember || ( (auth.user?.position_id && auth.user?.position_id < 6) || (auth.activeUser.id === 610 || auth.activeUser.id === 608))) && (goal?.status == 2)" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openGoalApproveWindow(1)" :content="'差戻'"/>
                        <LoaderButton v-if="goal?.status == 2" style="margin: 0;" @click="openGoalApproveWindow(3)" :content="'承認'"/>
                    </div>
                    <div v-if="goal?.status == 4 && (auth.activeUser.id === 610 || auth.activeUser.id === 631)">
                        <LoaderButton style="margin: 0;" @click="openGoalApproveWindow(1)" :content="'差戻'"/>
                    </div>
                    <div v-if="631 === auth.id && goal?.status == 3" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openGoalApproveWindow(1)" :content="'人事差戻'"/>
                        <LoaderButton style="margin: 0;" @click="openGoalApproveWindow(5)" :content="'人事承認'"/>
                    </div>
                    <div v-if="610 === auth.activeUser.id && goal?.status == 5" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openGoalApproveWindow(3)" :content="'人事承認取消'"/>
                    </div>
                    <div v-if="610 === auth.activeUser.id && goal?.status > 6" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openGoalApproveWindow(6)" :content="'差戻（管理本部用）'"/>
                    </div>
                </div>
                <div class="flex flex-col gap-[30px] relative" v-if="salaryIssueRecord && sub_tab === 1">
                    <div class="pc absolute top-0 right-0">
                        <div class="px-[10px] py-[5px] bg-[var(--bg3)] text-[12px]">{{ salaryIssueStatus[salaryIssueRecord.status] }}</div>
                    </div>
                    <div class="mobile w-fit">
                        <div class="text-[13px] font-semibold">ステータス</div>
                        <div class="px-[10px] py-[5px] bg-[var(--bg3)] text-[12px] mt-[10px]">{{ salaryIssueStatus[salaryIssueRecord.status] }}</div>
                    </div>
                    <div 
                        v-if="canManageStatus" 
                        class="flex flex-wrap items-center gap-[10px] bg-[var(--bg3)] px-[10px] py-[8px]"
                    >
                        <div class="text-[12px] font-semibold whitespace-nowrap">人事専用ステータス変更</div>
                        <select 
                            v-model.number="selectedSalaryIssueStatus" 
                            class="py-[6px] px-[10px] text-[13px] text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--formBorder)] min-w-[180px]"
                        >
                            <option v-for="(label, index) in salaryIssueStatus" :key="index" :value="index">
                                {{ label }}
                            </option>
                        </select>
                        <LoaderButton style="margin: 0;" :content="'更新'" @click="updateSalaryIssueStatusDirectly" />
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">テーマ</div>
                        <div>{{ salaryIssueRecord.theme }}</div>
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">メンター</div>
                        <div class="kadai-content">{{ evaluationData?.mentor?.name ?? '未設定' }}</div>
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">タイトル</div>
                        <div class="kadai-content">{{ salaryIssueRecord.title }}</div>
                    </div>
                    <div v-if="salaryIssueRecord.content">
                        <div class="text-[13px] font-semibold">内容・詳細</div>
                        <div class="kadai-content">{{ salaryIssueRecord.content }}</div>
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">開発能力</div>
                        <div class="kadai-content">{{ salaryIssueRecord.ability }}</div>
                    </div>      
                    <div>
                        <div class="text-[13px] font-semibold mb-[10px]">修得要件</div>
                        <div v-if="salaryIssueRecord.actions" class="flex flex-col gap-[15px]">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="w-[80px]">修得状況</th>
                                        <th>修得要件</th>
                                        <th>ガイドライン</th>
                                    </tr>
                                </thead>
                                <tbody>                                    
                                    <tr v-for="action in salaryIssueRecord.actions">                                        
                                        <td class="w-[80px] max-w-[110px] text-center">
                                            <select 
                                                :disabled="!salaryIssueReport" 
                                                :value="action.status" 
                                                @change="salaryIssueActionComplete(action)" 
                                                class="py-[5px] px-[10px]"
                                                :class="{'!cursor-not-allowed appearance-none': !salaryIssueReport}"
                                                :style="{ background: action.status == 1 ? '#64bc44' : 'var(--bg3)', color: action.status == 1 ? 'white' : 'var(--primary-color)' }"
                                            >
                                                <option :value="1">修得済み</option>
                                                <option :value="0">未修得</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="leading-normal text-[13px]">{{ action.content }}</div>
                                        </td>
                                        <td>
                                            <div class="leading-normal text-[13px]">{{ action.learning_content }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>                                  
                    <div v-if="salaryIssueRecord.comment">
                        <div class="text-[13px] font-semibold">コメント</div>
                        <div class="kadai-content">{{ salaryIssueRecord.comment }}</div>
                    </div>
                    <div v-if="salaryIssueRecord.review">
                        <div class="text-[13px] font-semibold">AI添削結果</div>
                        <div class="kadai-content">{{ salaryIssueRecord.review }}</div>
                    </div>
                    <div v-if="salaryIssueRecord?.status >= 6">
                        <div class="post-separetor mt-[10px]"></div>
                        <div class="mb-[10px] font-semibold text-[13px]">開発能力検証報告</div>
                        <div class="kadai-content">{{ salaryIssueRecord.result }}</div>                        
                        <Files style="margin-top: 15px;" v-if="salaryIssueRecord?.files?.length" :items="salaryIssueRecord?.files" :path="'project_files'"/>
                    </div>
                    <MessageArea which="salary_issue" :item="salaryIssueRecord" :key="`message-area-goal-${goal.id}`" @refresh="() => refresh()"/>
                    <div v-if="salaryIssueRecord.status < 2 && (auth.id === memberData?.id || evaluationData?.mentor_id === auth.id)" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="editIssue(goal.salary_issue)" :content="'変更'"/>
                        <LoaderButton style="margin: 0;" @click="deleteIssue(goal.salary_issue)" :content="'削除'"/>
                    </div>
                    <div v-if="evaluationData?.mentor_id === auth.id && salaryIssueRecord?.status == 2" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openSalaryIssueApproveWindow(salaryIssueRecord, 1)" :content="'差戻'"/>
                        <LoaderButton style="margin: 0;" @click="openSalaryIssueApproveWindow(salaryIssueRecord, 3)" :content="'承認'"/>
                    </div>
                    <div v-if="631 === auth.id && salaryIssueRecord?.status == 3" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openSalaryIssueApproveWindow(salaryIssueRecord, 1)" :content="'人事差戻'"/>
                        <LoaderButton style="margin: 0;" @click="openSalaryIssueApproveWindow(salaryIssueRecord, 5)" :content="'人事承認'"/>
                    </div>
                    <div v-if="631 === auth.id && salaryIssueRecord?.status == 9" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openSalaryIssueApproveWindow(salaryIssueRecord, 6)" :content="'結果人事差戻'"/>
                        <LoaderButton style="margin: 0;" @click="openSalaryIssueApproveWindow(salaryIssueRecord, 10)" :content="'結果人事承認'"/>
                    </div>
                    <div v-if="610 === auth.activeUser.id && salaryIssueRecord?.status == 5" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="openSalaryIssueApproveWindow(salaryIssueRecord, 3)" :content="'人事承認取消'"/>
                    </div>
                    <div style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton v-if="salaryIssueReport" style="margin: 0;" :content="'開発能力検証報告'" @click="addIssueReport(false, goal)"/>
                        <LoaderButton style="margin: 0;" v-if="evaluationData?.mentor_id === auth.id && salaryIssueRecord?.status === 7" :content="'進捗報告承認'" @click="addIssueReport(true, goal)"/>
                    </div>
                </div>
                <div v-else-if="canCreateIssue && sub_tab === 1">
                    <div v-if="(auth.id === memberData?.id || evaluationData?.mentor_id === auth.id)">
                        <LoaderButton style="margin: 0;" @click="salaryIssue = true" :content="'作成'"/>
                    </div>
                    <div v-else>
                        権限がありません。
                    </div>
                </div>
                 
                <div v-else-if="sub_tab === 1">
                    選択された成果目標は、昇給課題作成の要件を満たしていません。［期間］
                </div>                 
            </div>
        </div>
        <Transition name="modalFade">
            <ProjectGoalResult 
                v-if="openReport"
                :chosenGoal="goal"
                :reviewing="reviewing"
                @close="openReport = false"
                @reload="openReport = false, emit('close')"
            />
        </Transition>
        <Transition name="modalFade">
            <ProjectGoalReportCreate 
                v-if="projectGoalReportCreate"
                :projectGoal="projectGoalReportCreate"
                @close="projectGoalReportCreate = null"
                @reload="projectGoalReportCreate = null, refresh(), emit('close')"
            />
        </Transition>
        <Transition name="modalFade">
            <ProjectSalaryIssueCreation
                v-if="salaryIssue" 
                @close="salaryIssue = false, emit('close')"
                @selectThemeConfirm="selectThemeConfirm"
                :getIssues="getIssues"
                @goback="selectedTheme = null"
                :selectedTheme="selectedTheme"
                :selectedDate="selectedDate"
                :editData="editData"
                :chosenGoal="goal"
                :evaluation="evaluationData"
            />
        </Transition>
        <Transition name="modalFade">
            <Report 
                v-if="issueReport"
                :chosenIssue="issueReport"
                :reviewing="reviewing"
                @close="issueReport = null"
                @reload="issueReport = null, emit('close')"
            />
        </Transition>
        <Transition name="modalFade">
            <Modal v-if="goalDecisionData.active && goalDecisionData.status" @close="goalDecisionData.active = false, goalDecisionData.status = null">
                <template #title>
                </template>
                <template #content>
                    <div>判断: <strong>{{ [1,6].includes(goalDecisionData.status) ? '差戻' : [5,3].includes(goalDecisionData.status) ? '承認' : ''  }}</strong></div>
                    <div class="si-box">
                        <LongInput v-model="goalDecisionData.comment" name="comment" place-holder="コメント" />
                    </div>
                    <div class="si-box">
                        <LoaderButton @triggered="approveOutComeGoal(goalDecisionData.status)" :content="'保存'" />
                    </div>
                </template>

            </Modal>
        </Transition>
        <Transition name="modalFade">
            <Modal v-if="salaryIssueData.active && salaryIssueData.status && salaryIssueData.id" @close="salaryIssueData.active = false, salaryIssueData.status = null">
                <template #title>
                </template>
                <template #content>
                    <div>判断: <strong>{{ [1,6].includes(salaryIssueData.status) ? '差戻' : [5,3,10].includes(salaryIssueData.status) ? '承認' : ''  }}</strong></div>
                    <div class="si-box">
                        <LongInput v-model="salaryIssueData.comment" name="comment" place-holder="コメント" />
                    </div>
                    <div class="si-box">
                        <LoaderButton @triggered="approveSalaryIssue(salaryIssueData.id, salaryIssueData.status)" :content="'保存'" />
                    </div>
                </template>

            </Modal>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { useAuthUserStore } from '@/store/auth';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import ProjectGoalResult from './ProjectGoalResult.vue';
import ProjectSalaryIssueCreation from './ProjectSalaryIssueCreation.vue';
import Files from '../Global/Files.vue';
import Report from './SalaryIssue/Report.vue';
import { ProjectGoal, SalaryIssue } from '@/interface/projectInterface';
import { useBadgeStore } from '@/store/badge'
import { useRouter } from 'vue-router';
import Modal from '../Global/Modal.vue';
import LongInput from '../Form/LongInput.vue';
import ProjectGoalReportCreate from './ProjectGoalReportCreate.vue';
import { DateTime } from 'luxon';
import { useProject } from '@/composables/project';
import ProgressSlider from './ProgressSlider.vue';
import { useApi } from '@/composables/api';
import MessageArea from './MessageArea.vue';
const props = defineProps([
    'goal', 
    'themeRecords',
    'selectedDate',
    'statuses',
    'evaluationData',
    'salaryIssueStatus'
])
const emit = defineEmits(['close'])
const auth = useAuthUserStore()
const openReport = ref(false)
const sub_tab = ref(0)
const salaryIssue = ref(false)
const selectedTheme = ref(null)
const editData = ref({})
const reviewing = ref(false)
const refresh = inject('refresh') as Function
const refreshRemind = inject('refreshRemind') as Function
const issueReport = ref(null)
const { memberData, isManagerOrMember, selectedProject } = useProject()
const api = useApi()
const canManageStatus = computed(() => auth.activeUser.id === 610)
const selectedGoalStatus = ref<number | null>(props.goal?.status ?? null)
const selectedSalaryIssueStatus = ref<number | null>(props.goal?.salary_issue?.status ?? null)


const projectGoalReportCreate = ref<ProjectGoal | null>(null)
const badge = useBadgeStore()
const router = useRouter()   
const scoreMap = {
    1: '明確に悪化',
    2: '悪化傾向',
    3: '変化なし・未確認',
    4: '好転傾向',
    5: '明確に好転'
} 
onMounted(() => {
    setTimeout(() => {
        badge.clearGoalIssue({column: 'project_goal_id', value: props.goal?.id})
    }, 3000);
})
const canCreateIssue = computed(() => {
    const start = props.goal?.start_date ? DateTime.fromSQL(props.goal.start_date) : null;
    const end = props.goal?.end_date ? DateTime.fromSQL(props.goal.end_date) : null
    if (start?.isValid && end?.isValid) {
        const differenceInMonths = end.diff(start, 'months').as('months'); 
        const differenceInDays = end.diff(start, 'days').as('days');
        if (differenceInMonths >= 2.9 || differenceInDays >= 89) { 
            return true;
        }
    }
    return false
})
const salaryIssueRecord = computed(() => {
    return props.goal?.salary_issue
})
watch(
    () => props.goal?.status,
    (value) => {
        selectedGoalStatus.value = typeof value === 'number' ? value : null
    },
    { immediate: true }
)
watch(
    () => props.goal?.salary_issue?.status,
    (value) => {
        selectedSalaryIssueStatus.value = typeof value === 'number' ? value : null
    },
    { immediate: true }
)
const reviewReport = computed(() => {
    return (memberData.value && auth.id === memberData.value.id 
            || managerOrDirector.value) 
            && (props.goal?.status >= 5 && props.goal?.status < 9 && props.goal?.status !== 7)
})
const managerOrDirector = computed(() => {
    return (auth.user?.position_id && auth.user?.position_id < 6) || isManagerOrMember.value
})
const salaryIssueReport = computed(() => {
    return (salaryIssueRecord.value?.status >= 5 && salaryIssueRecord.value?.status < 9 && salaryIssueRecord.value?.status !== 7) 
        && (auth.id === memberData.value?.id || props.evaluationData?.mentor_id === auth.id)
})
const selectThemeConfirm = (level, theme) => {
    selectedTheme.value = getIssues(level, theme)[0]
}
const evalutionsValues = computed(() => {
    return props.themeRecords
})
const goalDecisionData = reactive({
    comment: '',
    status: <number| null>null,
    active: false
})

const salaryIssueData = reactive({
    id: <number | null>null,
    comment: '',
    status: <number| null>null,
    active: false
})
const openGoalApproveWindow = (status: number) => {
    goalDecisionData.status = status
    goalDecisionData.active = true
}
const openSalaryIssueApproveWindow = (issue: SalaryIssue, status: number) => {
    salaryIssueData.status = status
    salaryIssueData.id = issue.id
    salaryIssueData.active = true    
}
const approveOutComeGoal = async(status: number) => {
    let content = ''
    let info_message = ''
    switch (status) {
        case 1: 
            content = 'この成果目標を差し戻してもよろしいですか'
            info_message = '差戻しました。'
            break
        case 3: 
            content = 'この成果目標を承認してもよろしいですか？'
            info_message = '承認しました。'
            break
        case 5: 
            content = 'この成果目標を人事承認でよろしいですか？'
            info_message = '人事承認しました。'
            break
        case 6: 
            content = '差戻しますか。'
            break
        default:
            content = 'よろしいですか。'
            break
    }

    await api.put('/approve_outcome_goal', {id: props.goal.id, status: status, comment: goalDecisionData.comment}, {
        ask: content,
        toast: info_message,
    })
    if (typeof refresh === 'function') {
        refresh()
    }
    emit('close')
    badge.getMembersGoalsBadge()
    if(auth.user && auth.user?.position_id && auth.user?.position_id < 6){
        badge.getManagersGoalsBadge()
    }
    if (auth.id === 631) {
        badge.getRemindBadge()
        refreshRemind('remind_project_not_approved')
    }
    goalDecisionData.active = false
    goalDecisionData.status = null
    goalDecisionData.comment = ''

}
const approveSalaryIssue = async(id: number, status: number) => {
    let content = ''
    let info_message = ''
    switch (status) {
        case 1: 
            content = 'この昇給課題を差し戻してもよろしいですか'
            info_message = '差戻しました。'
            break
        case 3: 
            content = 'この昇給課題を承認してもよろしいですか？'
            info_message = '承認しました。'
            break
        case 5: 
            content = 'この昇給課題は人事承認でよろしいですか？'
            info_message = '人事承認しました。'
            break
        case 6: 
            content = 'この昇給課題は達成でよろしいですか？'
            break
        default:
            content = 'エラーが発生しました'
            break
    }
    if(!id) return

    await api.put('/approve_salary_issue', { id: id, status: status, comment: salaryIssueData.comment }, {
        ask: content,
        toast: info_message,
    })
    if (typeof refresh === 'function') {
        refresh()
    }
    emit('close')
    badge.getSalaryIssueBadge()
    if (auth.id === 631) {
        badge.getRemindBadge()
        refreshRemind('remind_project_not_approved')
    }
    salaryIssueData.active = false
    salaryIssueData.status = null
    salaryIssueData.comment = ''
    salaryIssueData.id = null
} 
const getIssues = (level, theme) => {
    if(evalutionsValues.value){
        const foundItem = evalutionsValues.value.find((item) => item.level === level);
        if (foundItem) {
            return foundItem.issues.filter((issue) => issue.theme === theme);
        }
    }
    return []
}

const editIssue = (issue: SalaryIssue) => {
    if(issue) {
        let template = issue
        let theme = null;
        for (const item of evalutionsValues.value) {
            const issue = item.issues.find((issue) => issue.title_full === template.theme);
            if (issue) {
                theme = issue;
            }
        }
        if(theme){
            selectedTheme.value = theme
            editData.value = template
        }
    }
    salaryIssue.value = true    
}
const deleteIssue = async(issue: SalaryIssue) => {
    api.del(`/delete_issue`, {
        id: issue.id,
    }, {
        ask: '昇給課題を削除します。よろしいですか？',
        toast: '削除しました。',
    })
    if (typeof refresh === 'function') {
        refresh()
    }
    emit('close')

}
const progressReport = (report: boolean) => {
    reviewing.value = report
    openReport.value = true
}
const addIssueReport = (report: boolean, goal: any) => {
    reviewing.value = report
    issueReport.value = goal?.salary_issue
}
const updateGoalStatusDirectly = async () => {
    if (selectedGoalStatus.value === null || !props.goal?.id) return
    await api.put('/approve_outcome_goal', { id: props.goal.id, status: selectedGoalStatus.value, comment: '' }, {
        ask: 'この成果目標のステータスを変更しますか？',
        toast: 'ステータスを更新しました。',
    })
    if (typeof refresh === 'function') {
        refresh()
    }
}
const updateSalaryIssueStatusDirectly = async () => {
    if (selectedSalaryIssueStatus.value === null || !props.goal?.salary_issue?.id) return
    await api.put('/approve_salary_issue', { id: props.goal.salary_issue.id, status: selectedSalaryIssueStatus.value, comment: '' }, {
        ask: 'この昇給課題のステータスを変更しますか？',
        toast: 'ステータスを更新しました。',
    })
    if (typeof refresh === 'function') {
        refresh()
    }
}
const kpiCalculation = (steps: any) => {
    if(steps && steps.length){
        const totalProgress = steps.reduce((acc: number, step: any) => {
            return acc + step.progress
        }, 0)
        
        const maxProgress = steps.length * 100
        return Math.round((totalProgress / maxProgress) * 100)
    }
    return 0
}
const overallScore = computed(() => {
    if(!props.goal.steps || props.goal.steps.length === 0) {
        return props.goal.achievement_rate
    }
    const kpi = kpiCalculation(props.goal.steps)
    const kgi = props.goal.achievement_rate
    const sum = kpi + kgi
    return Math.round(sum / 2)
})

const salaryIssueActionComplete = async(record) => {
    const status = record.status
    const confirmMessage = status == 1 ? '修得要件を未修得にします。よろしいですか？' : '修得要件を修得済みにします。よろしいですか？'
    const successMessage = status == 1 ? '未修得にしました。' : '修得済みしました。'
    await api.post('/salary_issue_action_complete', { action_id: record.id, issue_id: record.salary_issue_id }, {
        ask: confirmMessage,
        toast: successMessage,
    })
    if (typeof refresh === 'function') {
        refresh()
    }
    emit('close')
}
</script>
<style>
.admin-command-bar{
    width: fit-content;
    min-width: 300px;      
}
.sub-tab-item{
    padding: 10px 15px;
    font-size: 14px;
    border-bottom: solid thin transparent;
    box-sizing: border-box;
    cursor: pointer;
}
.selected-sub-tab{
    border-bottom: solid thin var(--primary-color);
}
.sub-tab-container{
    display: flex;
}
</style>
<style scoped>
table{
    width: 100%;
    border-collapse: collapse;
}
td, th{
    padding: 10px;
    border: solid thin var(--formBorder);
}
th {
    font-weight: normal;
}
</style>
