<template>
    <BaseLayout 
        :title="`成果目標`" 
        :count="goalActionCount"
        :fullscreen="fullscreen" 
        :type="data.type"
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) => emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <template #icon>
            <svg class="side-app-icon mr-2" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 45 32" style="height: 16px;overflow: visible;">
                <path d="M32.084 18.983c0.433 0.409 0.938 0.722 1.492 0.95 0.433 0.18 1.191 0.265 1.732 0.265 0.553 0 1.191-0.096 1.72-0.277 0.553-0.192 1.059-0.529 1.492-0.938s0.782-0.914 1.023-1.456c0.241-0.553 0.373-1.155 0.373-1.756 0.024-1.203-0.457-2.43-1.323-3.284-0.433-0.433-0.938-0.782-1.504-1.011-0.565-0.241-1.179-0.361-1.78-0.361s-1.215 0.12-1.78 0.361c-0.565 0.241-1.083 0.577-1.504 1.011-0.854 0.866-1.347 2.081-1.323 3.284-0.012 1.203 0.517 2.394 1.383 3.212zM33.829 14.28c0.397-0.385 0.926-0.601 1.48-0.601s1.083 0.217 1.48 0.601c0.397 0.385 0.638 0.914 0.65 1.48s-0.18 1.131-0.577 1.552c-0.385 0.433-1.071 0.686-1.564 0.698-0.481 0.012-1.155-0.289-1.54-0.71-0.192-0.217-0.349-0.457-0.445-0.722s-0.144-0.541-0.132-0.83c0-0.553 0.253-1.083 0.65-1.468zM35.308 23.471c1.107 0 2.262 0.289 3.164 0.902 0.926 0.626 1.78 1.684 2.202 2.37 0.325 0.517 0.866 0.77 1.395 0.553 0.517-0.204 0.686-0.83 0.553-1.371-0.12-0.541-0.577-1.395-1.059-2.045-0.505-0.662-1.167-1.323-2.25-1.949-1.215-0.71-2.454-1.035-4.018-1.035s-2.803 0.325-4.018 1.035c-1.083 0.626-1.744 1.287-2.25 1.949-0.493 0.65-0.938 1.504-1.059 2.045s0.048 1.167 0.553 1.371c0.541 0.217 1.083-0.036 1.395-0.553 0.421-0.686 1.275-1.756 2.201-2.37s2.081-0.902 3.188-0.902z"></path><path d="M43.898 29.931c-1.768-0.18-3.537-0.253-5.305-0.301-0.89-0.024-1.78-0.024-2.671-0.036l-13.353-0.12-10.683-0.048h-8.782c-0.337 0-0.602-0.265-0.602-0.602 0.012-1.889-0.096-7.122-0.144-10.298-0.060-3.188-0.144-7.916-0.204-10.43-0.072-2.514-0.084-5.029-0.325-7.531-0.024-0.301-0.277-0.541-0.589-0.553-0.325-0.012-0.602 0.253-0.638 0.577-0.265 2.49-0.265 4.992-0.337 7.507-0.060 2.514-0.204 8.469-0.241 11.116-0.036 2.635-0.024 8.974 0.060 11.489 0 0-0.096-3.176 0 0.036 0.024 0.686 0.577 1.215 1.263 1.227 1.78 0.012 3.429 0.012 5.209 0.012h5.341l10.683-0.048 13.353-0.12c0.89-0.012 1.78-0.012 2.671-0.036 1.78-0.048 3.561-0.12 5.341-0.301 0.373-0.036 0.662-0.337 0.686-0.722 0-0.433-0.325-0.77-0.734-0.818z"></path><path d="M6.953 20.547l0.926-1.095 0.938-1.119c0.614-0.758 1.239-1.504 1.853-2.262s1.227-1.504 1.829-2.274c0.313-0.385 0.626-0.782 0.926-1.167 0.072-0.096 0.217-0.096 0.301-0.012l4.439 4.451c0.445 0.421 1.155 0.409 1.588-0.036l0.048-0.060c1.023-1.083 2.033-2.201 3.020-3.308 0.986-1.119 1.961-2.25 2.923-3.392 0.77-0.914 2.37-2.875 3.056-3.717 0.096-0.108 0.277-0.036 0.265 0.108-0.060 0.517-0.144 1.299-0.18 1.72-0.060 0.662-0.156 1.323-0.060 2.021 0.024 0.168 0.144 0.313 0.325 0.361 0.204 0.048 0.397-0.072 0.493-0.253 0.301-0.614 0.421-1.263 0.577-1.901 0.156-0.65 0.301-1.299 0.445-1.949s0.289-1.299 0.421-1.961c0.072-0.325 0.313-1.227 0.385-1.997s-0.361-1.167-0.95-1.059c-0.589 0.108-1.54 0.505-2.165 0.734l-1.877 0.722-1.865 0.734c-0.614 0.265-1.516 0.65-1.72 0.794s-0.313 0.385-0.253 0.589c0.060 0.205 0.217 0.385 0.577 0.349 0.361-0.048 1.191-0.241 1.829-0.409l1.72-0.469c0.144-0.036 0.253 0.132 0.156 0.241-0.734 0.818-2.406 2.719-3.188 3.633-0.962 1.143-1.925 2.286-2.863 3.441-0.614 0.746-1.215 1.504-1.817 2.262-0.12 0.156-0.349 0.168-0.493 0.036l-4.391-4.211c-0.048-0.036-0.084-0.072-0.144-0.108-0.469-0.313-1.095-0.18-1.432 0.277-0.577 0.77-1.143 1.54-1.708 2.322-0.577 0.782-1.143 1.576-1.708 2.358-0.565 0.794-1.131 1.588-1.684 2.382l-0.83 1.203-0.818 1.203c-0.217 0.313-0.144 0.758 0.205 0.974 0.277 0.18 0.674 0.108 0.902-0.156z"></path>
            </svg>
        </template>
        <template #default="{ parentElement }">
            <div v-if="loading" class="fixed h-full w-full z-[-1] flex items-center justify-center">
                <div class="spinner-mini"></div>
            </div>
            <div>
                <div class="space-y-2" v-if="requiredGoalData && (requiredGoalData.this_span.needed_count || requiredGoalData.previous_span.needed_count) && !auth.isBoss">
                    <div class="text-sm p-3 bg-[var(--bg3)] m-5 rounded-md leading-normal" >
                        <div v-if="requiredGoalData?.previous_span?.needed_count" class="flex items-center gap-2">
                            <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                            {{ `${requiredGoalData.previous_span.year}年${requiredGoalData.previous_span.half == 'first' ? '上期' : '下期'}の成果目標：${requiredGoalData.previous_span.needed_count}件未作成` }}
                        </div>
                        <div v-if="requiredGoalData?.this_span?.needed_count" class="flex items-center gap-2">
                            <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                            {{ `${requiredGoalData.this_span.year}年${requiredGoalData.this_span.half == 'first' ? '上期' : '下期'}の成果目標：${requiredGoalData.this_span.needed_count}件未作成` }}
                        </div>
                    </div>
                </div>
                <div v-if="!fullscreen" class="m-5" :class="{ 'pulse-border': highlightGoals }">
                    <div v-for="item in approvaNeeded" class="mb-4">
                        <p class="my-2 text-sm overflow-hidden whitespace-nowrap text-ellipsis flex items-center gap-2">
                            <span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 py-0.5">{{ item.chip }}</span>
                            {{ item.title }} ({{ item.users.length }})
                        </p>
                        <ExpansionGrid class="gap-x-4" :col="Number(data.col.split('-')[2] ?? 1)">
                            <ExpansionPanelItem
                                hide-actions
                                static
                                :tile="true"
                                class="rm-p"
                                v-for="member in item.users" 
                                :key="member.id" 
                                :value="member.id"
                            >
                                <template #title="{ expanded }">
                                    <PanelTitle :expanded="expanded">
                                        <div v-if="approvalUserHasOverWeekGoal(member)" class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                        <UserPanel size="25" :user="member" disable-instant/>
                                        <div class="overflow-hidden whitespace-nowrap text-ellipsis ml-2">{{ member.name }}</div>
                                        <div class="ml-1 text-[12px] text-[gray] whitespace-nowrap">({{ member.outcome_goals.length }}件)</div>                       
                                    </PanelTitle>
                                </template>
                                <template #body>
                                    <PanelData>
                                        <div
                                            v-for="group in groupOutcomeGoals(member.outcome_goals)"
                                            :key="`${group.year}-${group.which_half}`"
                                            class="mb-2"
                                        >
                                            <div :class="['text-[12px] text-[gray] mb-1 flex gap-1 justify-between', {'text-[tomato]' : approvalGroupHasOverWeekGoal(group)}]">
                                                {{ group.label }}（{{ group.goals.length }}件）
                                                <div class="jump-link ml-auto whitespace-nowrap" @click="jumpToGoal(member, group, parentElement)">対応</div>
                                            </div>
                                        </div>
                                    </PanelData>
                                </template>
                            </ExpansionPanelItem>
                        </ExpansionGrid>
                    </div>  
                    <div v-if="myGoals.length" class="mt-6">
                        <div class="flex text-sm my-2 flex-wrap gap-2 items-center">
                            <p v-if="approvaNeeded.length" class="text-sm">自分の目標 ({{ myGoals.length }})</p>
                            <p class="ml-auto text-[12px] text-[gray]">期間：{{ selectedDate.short_name }}</p>
                        </div>                        
                        <ExpansionGrid class="gap-x-4" :col="Number(data.col.split('-')[2] ?? 1)">
                            <ExpansionPanelItem
                                hide-actions
                                static
                                :tile="true"
                                class="rm-p"
                                v-for="goal in myGoals"
                                :key="goal.id"
                                :value="goal.id"
                            >
                                <template #title="{ expanded }">
                                    <PanelTitle :expanded="expanded">
                                        <div class="flex items-center h-full text-[13px] leading-normal overflow-hidden whitespace-nowrap">
                                            <div class="mr-1 ml-[-5px]" v-if="goal.status === 9">
                                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" viewBox="0 0 38 32" style="fill: rgb(100, 188, 68);; margin-left: 4px;">
                                                    <path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                                </svg>
                                            </div>
                                            <div v-if="goalIsOverWeek(goal)" class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                            <div class="overflow-hidden whitespace-nowrap text-ellipsis">{{ goal.title || goal.outcome_goal }}</div>
                                            <div class="flex items-center gap-1 ml-2">
                                                <div v-if="goal.user_id == auth.activeUser.id && (goal.status == 1 || goal.status == 8 || goal?.salary_issue?.status == 1 || goal?.salary_issue?.status == 8)" class="text-[11px] bg-[tomato] text-[white] px-1 rounded-full">差戻中</div>
                                                <div class="relative flex" v-if="goal?.goal_notifications_count || goal?.salary_issue?.issue_notifications_count">
                                
                                                    <svg fill="orange" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 30.88051 24.9735">
                                                    <path d="M30.72814,8.8769c-.14532-.82959-.40253-1.64972-.77496-2.4184-.37347-.76801-.86078-1.48114-1.43018-2.11041-.56958-.63019-1.21985-1.17505-1.91077-1.64008-.69165-.46552-1.42749-.84625-2.17938-1.16577-1.5072-.63647-3.08105-1.02167-4.65607-1.25201C18.1997.06067,16.61914-.02142,15.04528.00464c-1.57648.02826-3.16119.16687-4.73059.47339-1.56677.30853-3.12598.77979-4.58923,1.52222-.73016.37158-1.43451.81073-2.08917,1.32697-.65393.51624-1.25677,1.11188-1.7735,1.78302-.51813.66943-.9433,1.41797-1.25366,2.21051-.31232.7923-.4989,1.63013-.57269,2.46863-.03809.41821-.04175.84344-.03156,1.24939.01123.41052.04254.82294.0976,1.23492.11224.82324.32281,1.6463.65656,2.427.33209.7807.78845,1.51337,1.34021,2.15607.55261.64252,1.19427,1.19592,1.88171,1.6568,1.37878.92578,2.68457,1.41705,4.21594,1.83752,1.40436.38562,3.01337.61237,4.42383.68085.11499.00562.22223.05609.29999.14099.35828.39093.73218.8374,1.12903,1.18121.52246.45294,1.09735.87909,1.70001,1.23297.59595.34991,1.21814.62427,1.8606.87347.67725.2442,1.7251.4682,2.2804.51007.54651.0412.61255-.37128.435-.73407s-.21918-.43036-.29242-.58905c-.07404-.16064-.14563-.32257-.21429-.48541-.13745-.3255-.26355-.65436-.37738-.98267-.09088-.26556-.22833-.73004-.30035-1.09607-.02545-.12921.06171-.25269.19214-.27081,1.26611-.17621,2.52991-.42755,3.77478-.80463.76044-.23096,1.51337-.50958,2.24554-.85553.73206-.34485,1.44232-.76208,2.10303-1.26599.65881-.50543,1.26453-1.10352,1.7677-1.78918.25061-.34308.4754-.70667.67157-1.0849.19421-.37921.35907-.77295.49432-1.17499.26868-.80518.41492-1.64044.46771-2.46826.05145-.82404.01685-1.66162-.12994-2.49219Z" />
                                                    </svg>
                                                    <div class="text-[white] absolute top-0 left-0 right-0 text-[10px] w-fit mx-auto">{{ (goal?.goal_notifications_count ?? 0) + (goal?.salary_issue?.issue_notifications_count ?? 0) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </PanelTitle>
                                </template>
                                <template #body>
                                    <PanelData>
                                        <MonthlyGoalItemCompact :goal="goal" />
                                    </PanelData>
                                </template>
                            </ExpansionPanelItem>
                        </ExpansionGrid>
                    </div>


                    <div v-if="unfinishedPreviousSpanGoals.length" class="mt-6">
                        <div class="flex text-sm my-2 flex-wrap gap-2 items-center">
                            <p class="ml-auto text-[12px] text-[gray]">期間：{{ unfinishedPreviousSpanGoals[0]?.year }}年{{ unfinishedPreviousSpanGoals[0]?.which_half == 'first' ? '上期' : '下期' }}</p>
                        </div>                        
                        <ExpansionGrid class="gap-x-4" :col="Number(data.col.split('-')[2] ?? 1)">
                            <ExpansionPanelItem
                                hide-actions
                                static
                                :tile="true"
                                class="rm-p"
                                v-for="goal in unfinishedPreviousSpanGoals"
                                :key="goal.id"
                                :value="goal.id"
                            >
                                <template #title="{ expanded }">
                                    <PanelTitle :expanded="expanded">
                                        <div class="flex items-center h-full text-[13px] leading-normal overflow-hidden whitespace-nowrap">
                                            <div class="mr-1 ml-[-5px]" v-if="goal.status === 9">
                                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" viewBox="0 0 38 32" style="fill: rgb(100, 188, 68);; margin-left: 4px;">
                                                    <path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                                </svg>
                                            </div>
                                            <div v-if="goalIsOverWeek(goal)" class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                            <div class="overflow-hidden whitespace-nowrap text-ellipsis">{{ goal.title || goal.outcome_goal }}</div>
                                            <div class="flex items-center gap-1 ml-2">
                                                <div v-if="goal.user_id == auth.activeUser.id && (goal.status == 1 || goal.status == 8 || goal?.salary_issue?.status == 1 || goal?.salary_issue?.status == 8)" class="text-[11px] bg-[tomato] text-[white] px-1 rounded-full">差戻中</div>
                                                <div class="relative flex" v-if="goal?.goal_notifications_count || goal?.salary_issue?.issue_notifications_count">
                                
                                                    <svg fill="orange" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 30.88051 24.9735">
                                                    <path d="M30.72814,8.8769c-.14532-.82959-.40253-1.64972-.77496-2.4184-.37347-.76801-.86078-1.48114-1.43018-2.11041-.56958-.63019-1.21985-1.17505-1.91077-1.64008-.69165-.46552-1.42749-.84625-2.17938-1.16577-1.5072-.63647-3.08105-1.02167-4.65607-1.25201C18.1997.06067,16.61914-.02142,15.04528.00464c-1.57648.02826-3.16119.16687-4.73059.47339-1.56677.30853-3.12598.77979-4.58923,1.52222-.73016.37158-1.43451.81073-2.08917,1.32697-.65393.51624-1.25677,1.11188-1.7735,1.78302-.51813.66943-.9433,1.41797-1.25366,2.21051-.31232.7923-.4989,1.63013-.57269,2.46863-.03809.41821-.04175.84344-.03156,1.24939.01123.41052.04254.82294.0976,1.23492.11224.82324.32281,1.6463.65656,2.427.33209.7807.78845,1.51337,1.34021,2.15607.55261.64252,1.19427,1.19592,1.88171,1.6568,1.37878.92578,2.68457,1.41705,4.21594,1.83752,1.40436.38562,3.01337.61237,4.42383.68085.11499.00562.22223.05609.29999.14099.35828.39093.73218.8374,1.12903,1.18121.52246.45294,1.09735.87909,1.70001,1.23297.59595.34991,1.21814.62427,1.8606.87347.67725.2442,1.7251.4682,2.2804.51007.54651.0412.61255-.37128.435-.73407s-.21918-.43036-.29242-.58905c-.07404-.16064-.14563-.32257-.21429-.48541-.13745-.3255-.26355-.65436-.37738-.98267-.09088-.26556-.22833-.73004-.30035-1.09607-.02545-.12921.06171-.25269.19214-.27081,1.26611-.17621,2.52991-.42755,3.77478-.80463.76044-.23096,1.51337-.50958,2.24554-.85553.73206-.34485,1.44232-.76208,2.10303-1.26599.65881-.50543,1.26453-1.10352,1.7677-1.78918.25061-.34308.4754-.70667.67157-1.0849.19421-.37921.35907-.77295.49432-1.17499.26868-.80518.41492-1.64044.46771-2.46826.05145-.82404.01685-1.66162-.12994-2.49219Z" />
                                                    </svg>
                                                    <div class="text-[white] absolute top-0 left-0 right-0 text-[10px] w-fit mx-auto">{{ (goal?.goal_notifications_count ?? 0) + (goal?.salary_issue?.issue_notifications_count ?? 0) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </PanelTitle>
                                </template>
                                <template #body>
                                    <PanelData>
                                        <MonthlyGoalItemCompact :goal="goal" />
                                    </PanelData>
                                </template>
                            </ExpansionPanelItem>
                        </ExpansionGrid>
                    </div>
                    <div v-else class="text-center text-sm text-[gray] py-3">
                        成果目標が設定されていません。
                    </div>
                </div>
                <div v-show="fullscreen" class="px-4 pb-4">
                    <div>
                        <div class="flex justify-between mb-4 flex-wrap gap-3">
                            <div class="flex-1">
                                <div class="w-fit min-w-[150px]">
                                    <GoalUserPicker 
                                        v-if="selectedDate.year && selectedDate.which_half"
                                        v-model="selectedUser" 
                                        :year="selectedDate.year"
                                        :which_half="selectedDate.which_half"
                                    />
                                </div>
                                
                            </div>
                            <div class="flex items-center">
                                <div class="text-sm border px-3 py-1 w-fit">
                                    <p class="text-[13px]">現時点で達成評価点 ： <strong>{{ totalOverallScore }}</strong>点</p>
                                </div>  
                            </div>
                            <div class="evaluation-date">
                                <select name="locales" v-model="selectedDate" class="dropDownSelector cursor-pointer" style="width: fit-content; padding: 5px 10px;">
                                    <option :value="date" v-for="date in targetDates">{{ date.name}}</option>
                                </select>
                            </div>
                            
                        </div>
                        <MonthlyGoalContainer 
                            :user-id="selectedUser ? selectedUser.id : undefined" 
                            :span="selectedDate ? `${selectedDate.year}-${selectedDate.which_half}` : undefined"                         
                            :scrollParent="parentElement"
                            from="dashboard"
                            class="!p-0 !h-[unset] max-w-[100%]"
                        />
                    </div>
                </div>
            </div>
        </template>
    </BaseLayout>
</template>
<script setup lang="ts">
import { User, UserWithGoals } from '@/interface/globalInterface';
import BaseLayout from './BaseLayout.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import { detailedDateOptions } from '@/utils/tools';
import { DateTime } from "luxon";
import { useDashboardGoalsStore } from '@/store/dashboardGoals';
import { ProjectGoal } from '@/interface/projectInterface';
import MonthlyGoalContainer from '@/components/Project/MonthlyGoal/MonthlyGoalContainer.vue';
import GoalUserPicker from '@/components/Project/MonthlyGoal/GoalUserPicker.vue';
import MonthlyGoalItemCompact from '@/components/Project/MonthlyGoal/MonthlyGoalItemCompact.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import { storeToRefs } from 'pinia';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';

type OutcomeGoalGroup = {
    year: number;
    which_half: string;
    label: string;
    goals: ProjectGoal[];
}

const props = defineProps<{
    data: {
        title: string,
        data: any[],
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col: string
    },
    fullscreen: boolean,
    highlightGoals?: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const goalsStore = useDashboardGoalsStore()
const {totalOverallScore, loading, pendingMembers, myGoals, managersGoals, mentorApprovalNeededGoalsWithSalaryIssue, adminApprovalNeededGoalsWithSalaryIssue, adminApprovalNeededGoals, requiredGoalData, unfinishedPreviousSpanGoals, pulseBadgeCount, normalBadgeCount } = storeToRefs(goalsStore)
const { getGoals } = goalsStore

const auth = useAuthUserStore()
const selectedUser = ref<User | null>(auth.user)

const targetDates = detailedDateOptions()
const selectedDate = ref<typeof targetDates[0]>({ name: '', year: '0', which_half: '', short_name: '' })
onMounted(() => {

    const now = DateTime.local();
    const start = now.set({ month: 4, day: 1 }).startOf("day");   // Apr 1
    const end   = now.set({ month: 9, day: 30 }).endOf("day");    // Sep 30
    const span = (now >= start && now <= end) ? "first" : "second";
    const year = span === "first" ? now.year : now.year - 1;
    const selected = targetDates.find( date => date.year.toString() === year.toString() && date.which_half === span )
    if(selected){
        selectedDate.value = selected
    }

    if(auth.id){
        selectedUser.value = auth.user
    }
    console.log('yyy')
    // getGoals(selectedUser.value?.id ?? 0 ,year, span)
})

const approvaNeeded = computed(() => {
    const items:{
        chip: string,
        title: string,
        users: UserWithGoals[]
    }[] = []
    if(pendingMembers.value.length){
        items.push({
            chip: 'PM',
            title: '承認依頼【メンバー】',
            users: pendingMembers.value
        })
    }
    if(managersGoals.value.length){
        items.push({
            chip: '役員',
            title: '承認依頼【PM】',
            users: managersGoals.value
        })
    }
    if(mentorApprovalNeededGoalsWithSalaryIssue.value.length){
        items.push({
            chip: 'メンター',
            title: '承認依頼【メンティー】',
            users: mentorApprovalNeededGoalsWithSalaryIssue.value
        })
    }
    if(adminApprovalNeededGoalsWithSalaryIssue.value.length){
        items.push({
            chip: '人事',
            title: '承認依頼【昇給課題】',
            users: adminApprovalNeededGoalsWithSalaryIssue.value    
        })
    }
    if(adminApprovalNeededGoals.value.length){
        items.push({
            chip: '人事',
            title: '承認依頼【成果目標】',
            users: adminApprovalNeededGoals.value    
        })
    }

    return items

})
const goalActionCount = computed(() => pulseBadgeCount.value + normalBadgeCount.value)
const goalIsOverWeek = (goal: ProjectGoal) => {
    return goalsStore.isGoalOverWeek(goal)
}
const approvalGroupHasOverWeekGoal = (group: OutcomeGoalGroup) => {
    return (group.goals ?? []).some(goalIsOverWeek)
}
const approvalUserHasOverWeekGoal = (user: UserWithGoals) => {
    return (user.outcome_goals ?? []).some(goalIsOverWeek)
}

const halfLabel = (whichHalf: string) => {
    if (whichHalf === 'first') return '上期'
    if (whichHalf === 'second') return '下期'
    return whichHalf
}

const groupOutcomeGoals = (goals: ProjectGoal[]): OutcomeGoalGroup[] => {
    const map = new Map<string, OutcomeGoalGroup>()

    for (const goal of goals ?? []) {
        const key = `${goal.year}-${goal.which_half}`
        const existing = map.get(key)
        if (existing) {
            existing.goals.push(goal)
            continue
        }

        map.set(key, {
            year: goal.year,
            which_half: goal.which_half,
            label: `${goal.year}${halfLabel(goal.which_half)}`,
            goals: [goal],
        })
    }

    const halfRank = (whichHalf: string) => {
        if (whichHalf === 'first') return 0
        if (whichHalf === 'second') return 1
        return 99
    }

    return Array.from(map.values()).sort((a, b) => {
        if (a.year !== b.year) return b.year - a.year
        return halfRank(a.which_half) - halfRank(b.which_half)
    })
}

const jumpToGoal = (member: UserWithGoals, group: OutcomeGoalGroup, parentElement: HTMLElement | null) => {
    if(!selectedUser.value) return
    selectedUser.value = member; 
    const selected = targetDates.find( date => date.year.toString() === group.year.toString() && date.which_half === group.which_half )
    if(selected){
        selectedDate.value = selected
    }
    emit('toggle', parentElement, props.data.type)
}
defineExpose({
    cardType: props.data.type,
})

watch([selectedUser, selectedDate], () => {
    if(selectedUser.value && selectedDate.value){
        getGoals(selectedUser.value.id, Number(selectedDate.value.year), selectedDate.value.which_half)
    }
})
</script>
<style>
.compact-selector .vs--single.vs--open .vs__selected, .vs--single.vs--loading .vs__selected {
    position: relative;
    opacity: .4;
}
</style>
