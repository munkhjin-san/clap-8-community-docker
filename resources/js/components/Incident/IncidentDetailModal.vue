<template>
    <Modal size="large" custom-class="incident-detail-modal" @close="emit('close', false)">
        <template #title>{{ isCreateMode ? 'インシデント報告【新規作成】' : editMode ? 'インシデント報告【編集】' : 'インシデント報告' }}</template>
        <template #menu>
            <div class="incident-detail-menu">
                <button
                    v-if="unreadBadgeVisible && unreadBadge"
                    type="button"
                    class="incident-read-badge"
                    :class="{ 'incident-read-badge--new': unreadBadge.type === 'new' }"
                    @click="handleUnreadBadgeClick"
                >
                    {{ unreadBadge.type === 'new' ? '新規インシデント' : `更新 ${unreadBadge.count}件` }}
                </button>
                <ItemMenu v-if="!isCreateMode && menuItems.length" :items="menuItems" />
            </div>
        </template>
        <template #content>
            <div v-if="viewMode === 'detail'" class="incident-detail-shell">
                <aside class="incident-detail-side">
                    <div v-if="canViewIncidentPoint" class="incident-detail-score" :style="{ borderColor: riskLevelColor(localIncident) }">
                        <div class="flex items-center gap-2 py-4">
                            <span>{{ incidentPoint(localIncident) || '-' }}</span>
                            <small>ポイント</small>
                        </div>                       
                    </div>
                    <div class="incident-detail-facts">
                        <div v-if="canViewIncidentStatus">
                            <span>ステータス</span>
                            <div v-if="canEditField('status')" class="mt-3 w-full">
                                <select v-model="mutableParams.status" class="custom-a-input max-w-[140px]">
                                    <option value="" disabled>ステータスを選択</option>
                                    <option
                                        v-for="status in incidentOptions.statuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
                                    </option>
                                </select>
                            </div>
                            <strong v-else>{{ localIncident.status || '未設定' }}</strong>
                        </div>
                        <div v-if="canUseField('occurred_date')">
                            <span>発生日</span>
                            <div v-if="canEditField('occurred_date')" class="mt-3 w-full">
                                <ShortInput
                                    ref="occurredDateRef"
                                    v-model="mutableParams.occurred_date"
                                    type="date"
                                    rules="required"
                                    place-holder="発生日"
                                />
                            </div>
                            <strong v-else>{{ formatDate(localIncident.occurred_date) }}</strong>
                        </div>
                        <div v-if="canUseField('reported_date')">
                            <span>報告日</span>
                            <div v-if="canEditField('reported_date')" class="mt-3 w-full">
                                <ShortInput
                                    ref="reportedDateRef"
                                    v-model="mutableParams.reported_date"
                                    type="date"
                                    rules="required"
                                    place-holder="報告日"
                                />
                            </div>
                            <strong v-else>{{ formatDate(localIncident.reported_date) }}</strong>
                        </div>
                        <div v-if="canUseField('incident_category_id')">
                            <span>区分</span>
                            <div v-if="canEditField('incident_category_id')" class="mt-3 w-full">
                                <select
                                    v-model="mutableParams.incident_category_id"
                                    class="custom-a-input max-w-[140px]"
                                >
                                    <option :value="null">未設定</option>
                                    <option
                                        v-for="category in incidentOptions.categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name || `区分 ${category.id}` }}
                                    </option>
                                </select>
                            </div>
                            <strong v-else>{{ localIncident.category?.name || '未設定' }}</strong>
                        </div>
                        <div v-if="canUseField('incident_punishment_id')">
                            <span>懲罰区分</span>
                            <div v-if="canEditField('incident_punishment_id')" class="mt-3 w-full">
                                <select
                                    v-model="mutableParams.incident_punishment_id"
                                    class="custom-a-input max-w-[140px]"
                                >
                                    <option :value="null">未設定</option>
                                    <option
                                        v-for="punishment in incidentOptions.punishments"
                                        :key="punishment.id"
                                        :value="punishment.id"
                                    >
                                        {{ punishment.name || `懲罰区分 ${punishment.id}` }}
                                    </option>
                                </select>
                            </div>
                            <strong v-else>{{ localIncident.punishment?.name || '未設定' }}</strong>
                        </div>
                    </div>
                </aside>

                <div class="incident-detail-content">
                <main class="incident-detail-main">
                    <section class="incident-detail-section incident-permission-area incident-permission-area--staff">
                        <!-- <h3>関係者</h3> -->
                        <div class="post-separetor"><div>関係者</div></div>
                        <div class="incident-people-grid">
                            <div v-if="canUseField('caused_by')" class="flex flex-col gap-3">
                                <span v-if="!editMode">当事者</span>
                                <MemberSelector
                                    v-if="canEditField('caused_by')"
                                    v-model="selectedCausedByUser"
                                    :multiple="false"
                                    :close-on-select="true"
                                    :options="userOptions"
                                    place-holder="当事者を選択"
                                    class="bg-[var(--background-color)]"
                                />
                                <template v-else>
                                    <UserPanel v-if="localIncident.caused_by_user" :user="localIncident.caused_by_user" with-name size="25" disable-instant/>
                                    <strong v-else>-</strong>
                                </template>
                            </div>
                            <div v-if="isCreateMode || canEditManagerFields" class="flex flex-col gap-3">
                                <span v-if="!isCreateMode">報告者</span>
                                <MemberSelector
                                    v-if="isCreateMode"
                                    v-model="selectedReportedByUser"
                                    :multiple="false"
                                    :close-on-select="true"
                                    :options="userOptions"
                                    place-holder="報告者を選択"
                                    class="bg-[var(--background-color)]"
                                />
                                <template v-else>
                                    <UserPanel v-if="localIncident.reported_by_user" :user="localIncident.reported_by_user" with-name size="25" disable-instant/>
                                    <strong v-else>-</strong>
                                </template>
                            </div>
                            <div v-if="canUseField('project_record_id')">
                                <span v-if="!editMode">プロジェクト</span>
                                <ItemSelector
                                    v-if="canEditField('project_record_id')"
                                    v-model="mutableParams.project_record_id"
                                    :multiple="false"
                                    :clearable="true"
                                    :close-on-select="true"
                                    :options="projectOptions"
                                    :reduce="option => option?.id ?? option"
                                    label="name"
                                    place-holder="プロジェクトを選択"
                                    class="bg-[var(--background-color)]"
                                />
                                <strong v-else>{{ localIncident.project_record?.name || '-' }}</strong>
                            </div>
                            <div v-if="canUseField('related_parties')" class="flex flex-col">
                                <span v-if="!editMode">関係者</span>
                                <ShortInput
                                    v-if="canEditField('related_parties')"
                                    v-model="mutableParams.related_parties"
                                    place-holder="関係者"
                                />
                                <strong v-else>{{ localIncident.related_parties || '-' }}</strong>
                            </div>
                        </div>
                    </section>

                    

                    <section v-if="canShowSection(['description', 'occured_location', 'reason', 'files'])" class="incident-detail-section incident-permission-area incident-permission-area--staff">
                        <div class="post-separetor"><div>発生内容</div></div>
                        <div v-if="!editMode" class="incident-field-stack">
                            <DetailItem v-if="canUseField('description')" label="概要" :value="localIncident.description" />
                            <DetailItem v-if="canUseField('occured_location')" label="発生場所" :value="localIncident.occured_location" />
                            <DetailItem v-if="canUseField('reason')" label="原因" :value="localIncident.reason" />
                        </div>
                        <div v-else class="flex flex-col gap-6">
                            <div v-if="canEditField('description')" class="bg-[var(--background-color)]">
                                <LongInput
                                    ref="descriptionRef"
                                    v-model="mutableParams.description"
                                    place-holder="インシデントの概要"
                                    rules="required"
                                />
                            </div>
                            <div v-if="canEditField('occured_location')" class="bg-[var(--background-color)]">
                                <ShortInput v-model="mutableParams.occured_location" place-holder="発生場所" />
                            </div>
                            <div v-if="canEditField('reason')" class="bg-[var(--background-color)]">
                                <LongInput v-model="mutableParams.reason" place-holder="インシデントの原因" />
                            </div>
                        </div>
                        <div v-if="canUseField('files')" class="incident-file-block">
                            <h3>添付ファイル</h3>
                            <FileUploader
                                v-if="canEditField('files')"
                                v-model="uploadedFiles"
                                path="/incident_files"
                                custom-place-holder="ファイルを添付"
                                class="bg-[var(--background-color)]"
                            />
                            <PostFiles
                                v-else-if="localIncident.files?.length"
                                :items="localIncident.files"
                                path="incident_files"
                            />
                            <div v-else class="text-[12px] text-[gray]">添付ファイルはありません。</div>
                        </div>
                    </section>
                    <section v-if="canUseIncidentAdvice" class="incident-detail-section incident-ai-advice-section">
                        <div class="post-separetor"><div>AIアドバイス</div></div>
                        <div class="incident-ai-advice-head mb-4">
                            <div class="mb-4">
                                <strong>解決方針のアドバイス</strong>
                                <p>インシデント内容をもとに、対応方針の案を生成して保存します。</p>
                            </div>
                            <LoaderButton
                                content="AIアドバイス生成"
                                :loading="adviceLoading"
                                @triggered="generateResolutionAdvice"
                                style="margin: 0"
                            >
                                <template #icon>
                                    <AiIcon size="20" fill="#fff" class="mr-3"/>
                                </template>
                            </LoaderButton>
                        </div>
                        <div v-if="adviceLoading || adviceDraft" class="incident-ai-advice-preview">
                            <span>{{ adviceLoading ? '生成中...' : '生成結果' }}</span>
                            <div v-html="sanitizedAdviceDraft"></div>
                        </div>
                        <div v-if="resolutionAdvices.length" class="incident-ai-advice-history">
                            <div
                                v-for="(advice, index) in resolutionAdvices"
                                :key="advice.id"
                                class="incident-ai-advice-version"
                                :class="{ 'incident-ai-advice-version--open': selectedAdviceId === advice.id }"
                            >
                                <div class="incident-ai-advice-version-head" @click="toggleAdviceExpansion(advice.id)">
                                    <span
                                        class="incident-ai-advice-arrow"
                                        :style="{ transform: selectedAdviceId === advice.id ? 'rotate(270deg)' : 'rotate(180deg)' }"
                                    >
                                        <Back size="12" />
                                    </span>
                                    <div class="incident-ai-advice-version-title">
                                        <strong>{{ `AIアドバイス（${resolutionAdvices.length - index}）` }}</strong>
                                        <span>{{ formatDateTime(advice.created_at) }}</span>
                                    </div>
                                    <UserPanel v-if="advice.creator" :user="advice.creator" with-name size="22" disable-instant/>
                                    <div class="incident-ai-advice-menu" @click.stop>
                                        <ItemMenu :items="adviceMenuItems(advice)" />
                                    </div>
                                </div>
                                <div v-if="selectedAdviceId === advice.id" class="incident-ai-advice-preview incident-ai-advice-version-body">
                                    <div class="incident-ai-advice-meta">
                                        <span>{{ formatDateTime(advice.created_at) }}</span>
                                        <button type="button" class="incident-ai-advice-copy" @click.stop="copyAdviceContent(advice)">
                                            コピー
                                        </button>
                                    </div>
                                    <div v-html="adviceHtml(advice)"></div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="!adviceLoading && !adviceDraft" class="incident-assignment-empty">
                            保存済みのAIアドバイスはありません。
                        </div>
                    </section>
                    <section v-if="canShowSection(['prevention', 'prevention_apply_status', 'resolution', 'memo', 'amount_of_damage', 'payee', 'expense_details'])" class="incident-detail-section incident-permission-area incident-permission-area--manager">
                        <div class="post-separetor"><div>対応・再発防止</div></div>
                        <div v-if="!editMode" class="incident-field-stack">                            
                            <DetailItem v-if="canUseField('prevention')" label="再発防止策" :value="localIncident.prevention" />
                            <DetailItem v-if="canUseField('prevention_apply_status')" label="再発防止策の実施状況" :value="localIncident.prevention_apply_status" />
                            <DetailItem v-if="canUseField('resolution')" label="是正対応" :value="localIncident.resolution" />
                            <DetailItem v-if="canUseField('memo')" label="メモ" :value="localIncident.memo" />
                            <DetailItem v-if="canUseField('amount_of_damage')" label="損害額" :value="formatAmount(localIncident.amount_of_damage)" />
                            <DetailItem v-if="canUseField('payee')" label="支払先" :value="localIncident.payee" />
                            <DetailItem v-if="canUseField('expense_details')" label="費用詳細" :value="localIncident.expense_details" />
                        </div>
                        <div v-else class="flex flex-col gap-6">                            
                            <div v-if="canEditField('prevention')" class="bg-[var(--background-color)]">
                                <LongInput v-model="mutableParams.prevention" place-holder="再発防止策" />
                            </div>
                            <div v-if="canEditField('prevention_apply_status')" class="bg-[var(--background-color)]">
                                <ShortInput v-model="mutableParams.prevention_apply_status" place-holder="再発防止策の実施状況" />
                            </div>
                            <div v-if="canEditField('resolution')" class="bg-[var(--background-color)]">
                                <LongInput v-model="mutableParams.resolution" place-holder="是正対応" />
                            </div>
                            <div v-if="canEditField('memo')" class="bg-[var(--background-color)]">
                                <LongInput v-model="mutableParams.memo" place-holder="メモ" />
                            </div>
                            <div class="incident-admin-grid">
                                <div v-if="canEditField('amount_of_damage')" class="under960:col-span-1">
                                    <ShortInput
                                        v-model="mutableParams.amount_of_damage"
                                        type="number"
                                        place-holder="損害額"
                                    />
                                </div>
                                <div v-if="canEditField('payee')" class="under960:col-span-1">
                                    <ShortInput v-model="mutableParams.payee" place-holder="支払先" />
                                </div>
                                <div v-if="canEditField('expense_details')" class="col-span-2 under960:col-span-1">
                                    <ShortInput v-model="mutableParams.expense_details" place-holder="費用詳細" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="canEditAdminFields" class="incident-detail-section incident-permission-area incident-permission-area--full">
                        <div class="post-separetor"><div>管理情報</div></div>
                        <div v-if="!editMode" class="flex gap-6 mb-4 w-fit">
                            <div class="flex flex-col gap-2 bg-[--bg3] p-4 rounded-lg">
                                <DetailItem label="リスクレベル" :value="localIncident.risk_level" />
                            </div>
                            <div class="flex flex-col gap-2 bg-[--bg3] p-4 rounded-lg">
                                <DetailItem label="損害レベル" :value="localIncident.severity_level" />
                            </div>
                            <div class="flex flex-col gap-2 bg-[--bg3] p-4 rounded-lg">
                                <DetailItem label="合計ポイント" :value="incidentPoint(localIncident) || '-'" />
                            </div>
                        </div>
                        <div v-if="editMode" class="flex mb-4 w-fit gap-2 flex-wrap">
                            <div class="flex flex-col gap-2 bg-[var(--background-color)] p-3">
                                <span class="text-[12px]">リスクレベル</span>
                                <select
                                    v-model="mutableParams.risk_level"
                                    class="custom-a-input"
                                    rules="min:1|max:3"
                                >
                                    <option value="">未定</option>
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="my-auto">×</div>
                            <div class="flex flex-col gap-2 bg-[var(--background-color)] p-3">
                                <span class="text-[12px]">損害レベル</span>
                                <select
                                    v-model="mutableParams.severity_level"
                                    class="custom-a-input"
                                    rules="min:1|max:3"
                                >
                                    <option value="">未定</option>
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="my-auto">=</div>
                            <div class="flex flex-col gap-2 bg-[var(--background-color)] p-3">
                                <span class="text-[12px] text-center">合計ポイント</span>
                                <strong class="text-[20px] text-center mt-2">{{ (mutableParams.severity_level || 0) * (mutableParams.risk_level || 0) || '-' }}</strong>
                            </div>
                            <div v-if="canUseField('incident_punishment_id')" class="flex flex-col gap-2 bg-[var(--background-color)] p-3">
                                <span class="text-[12px]">懲罰区分</span>
                                <div v-if="canEditField('incident_punishment_id')" class="w-full">
                                    <select
                                        v-model="mutableParams.incident_punishment_id"
                                        class="custom-a-input"
                                    >
                                        <option :value="null">未設定</option>
                                        <option
                                            v-for="punishment in incidentOptions.punishments"
                                            :key="punishment.id"
                                            :value="punishment.id"
                                        >
                                            {{ punishment.name || `懲罰区分 ${punishment.id}` }}
                                        </option>
                                    </select>
                                </div>
                                <strong v-else>{{ localIncident.punishment?.name || '未設定' }}</strong>
                            </div>
                        </div>
                        <div class="incident-punishment-table-wrap">
                            <table class="incident-punishment-table">
                                <thead>
                                    <tr>
                                        <th>懲戒レベル</th>
                                        <th>処分</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1点以上</td>
                                        <td>注意処分</td>
                                    </tr>
                                    <tr>
                                        <td>2点以上</td>
                                        <td>厳重注意</td>
                                    </tr>
                                    <tr>
                                        <td>4点以上</td>
                                        <td>訓戒</td>
                                    </tr>
                                    <tr>
                                        <td>6点以上</td>
                                        <td>減給</td>
                                    </tr>
                                    <tr>
                                        <td>9点以上</td>
                                        <td>出勤停止</td>
                                    </tr>
                                    <tr>
                                        <td>9点以上</td>
                                        <td>降給・降格</td>
                                    </tr>
                                    <tr>
                                        <td>9点以上</td>
                                        <td>諭旨退職</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6">
                            <div v-if="!editMode" class="incident-field-stack">
                                <DetailItem label="委員会メンバー" :value="localIncident.committee_members" />
                                <DetailItem label="委員会決定" :value="localIncident.committee_decision" />
                                <DetailItem label="委員会決定日" :value="formatDate(localIncident.committee_decision_date)" />
                                <DetailItem label="指導日" :value="formatDate(localIncident.instruction_date)" />
                                <DetailItem label="指導内容" :value="localIncident.instruction" />
                                <DetailItem label="顛末コメント" :value="localIncident.aftermath_comment" />
                            </div>
                            <div v-else class="flex flex-col gap-6">
                                <div class="incident-admin-grid">
                                     <ShortInput
                                        v-model="mutableParams.committee_decision_date"
                                        type="date"
                                        place-holder="委員会決定日"
                                    />
                                    <ShortInput v-model="mutableParams.committee_members" place-holder="委員会メンバー" />
                                </div>
                                <div class="incident-admin-grid">
                                    <ShortInput
                                        v-model="mutableParams.instruction_date"
                                        type="date"
                                        place-holder="指導日"
                                    />
                                </div>
                                <div class="bg-[var(--background-color)]">
                                    <LongInput v-model="mutableParams.instruction" place-holder="指導内容" />
                                </div>
                                <div class="bg-[var(--background-color)]">
                                    <LongInput v-model="mutableParams.committee_decision" place-holder="委員会決定" />
                                </div>
                                <div class="bg-[var(--background-color)]">
                                    <LongInput v-model="mutableParams.aftermath_comment" place-holder="顛末コメント" />
                                </div>
                            </div>
                        </div>
                        <section v-if="canUseIncidentConclusion" class="incident-detail-section incident-ai-advice-section mt-6">
                            <div class="post-separetor"><div>AI総括</div></div>
                            <div class="incident-ai-advice-head mb-4">
                                <div class="mb-4">
                                    <strong>完了インシデントの共有用まとめ</strong>
                                    <p>完了済みの内容をもとに、社員へ共有できる再発防止の学びを生成して保存します。</p>
                                </div>
                                <LoaderButton
                                    content="AI総括生成"
                                    :loading="conclusionAdviceLoading"
                                    @triggered="generateConclusionAdvice"
                                    style="margin: 0"
                                >
                                    <template #icon>
                                        <AiIcon size="20" fill="#fff" class="mr-3"/>
                                    </template>
                                </LoaderButton>
                            </div>
                            <div v-if="conclusionAdviceLoading || conclusionAdviceDraft" class="incident-ai-advice-preview">
                                <span>{{ conclusionAdviceLoading ? '生成中...' : '生成結果' }}</span>
                                <div v-html="sanitizedConclusionAdviceDraft"></div>
                            </div>
                            <div v-if="conclusionAdvices.length" class="incident-ai-advice-history">
                                <div
                                    v-for="(advice, index) in conclusionAdvices"
                                    :key="advice.id"
                                    class="incident-ai-advice-version"
                                    :class="{ 'incident-ai-advice-version--open': selectedConclusionAdviceId === advice.id }"
                                >
                                    <div class="incident-ai-advice-version-head" @click="toggleConclusionAdviceExpansion(advice.id)">
                                        <span
                                            class="incident-ai-advice-arrow"
                                            :style="{ transform: selectedConclusionAdviceId === advice.id ? 'rotate(270deg)' : 'rotate(180deg)' }"
                                        >
                                            <Back size="12" />
                                        </span>
                                        <div class="incident-ai-advice-version-title">
                                            <strong>{{ `AI総括（${conclusionAdvices.length - index}）` }}</strong>
                                            <span>{{ formatDateTime(advice.created_at) }}</span>
                                        </div>
                                        <UserPanel v-if="advice.creator" :user="advice.creator" with-name size="22" disable-instant/>
                                        <div class="incident-ai-advice-menu" @click.stop>
                                            <ItemMenu :items="adviceMenuItems(advice, 'conclusion')" />
                                        </div>
                                    </div>
                                    <div v-if="selectedConclusionAdviceId === advice.id" class="incident-ai-advice-preview incident-ai-advice-version-body">
                                        <div class="incident-ai-advice-meta">
                                            <span>{{ formatDateTime(advice.created_at) }}</span>
                                            <button type="button" class="incident-ai-advice-copy" @click.stop="copyAdviceContent(advice)">
                                                コピー
                                            </button>
                                        </div>
                                        <div v-html="adviceHtml(advice)"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="!conclusionAdviceLoading && !conclusionAdviceDraft" class="incident-assignment-empty">
                                保存済みのAI総括はありません。
                            </div>
                        </section>
                    </section>
                    <AppCommentSection
                        v-if="!editMode && !isCreateMode"
                        commentable-type="incident"
                        :commentable-id="localIncident.id"
                        :users="mentionableUsers"
                        title="コメント"
                        @count-changed="handleCommentCountChanged"
                    />
                </main>
                <aside v-if="!isCreateMode || canCreateNextAssignment" class="incident-detail-assignment">
                    <section class="incident-detail-section incident-assignment-section">
                        <div v-if="!isCreateMode && incidentReports.length" class="incident-assignment-steps">
                            <div
                                v-for="reportStep in incidentReports"
                                :key="reportStep.id"
                                class="incident-assignment-step"
                                :class="{ 'incident-assignment-step--current': reportStep.id === latestIncidentReport?.id }"
                            >
                                <div class="incident-assignment-step-head">
                                    <div
                                        class="incident-assignment-status-chip"
                                        :class="reportStep.completed_at ? 'incident-assignment-status-chip--complete' : 'incident-assignment-status-chip--active'"
                                    >
                                        {{ reportStep.completed_at ? '完了' : '対応中' }}
                                    </div>
                                    <small>{{ formatDateTime(reportStep.created_at) }}</small>
                                </div>
                                <p v-if="reportStep.request" class="incident-assignment-request">{{ reportStep.request }}</p>
                                <div class="incident-assignee-list">
                                    <div
                                        v-for="assignee in reportStep.assignees ?? []"
                                        :key="assignee.id"
                                        class="incident-assignee-row"
                                    >
                                        <div class="incident-assignee-user">
                                            <UserPanel v-if="assignee.user" :user="assignee.user" with-name size="25" disable-instant/>
                                            <strong v-else>担当者 {{ assignee.user_id }}</strong>
                                            <span>{{ assignee.completed_at ? '完了' : '未完了' }}</span>
                                        </div>
                                        <div v-if="canRespondToAssignee(assignee)" class="incident-assignee-response-editor">
                                            <LongInput
                                                v-model="assigneeResponses[assignee.id]"
                                                place-holder="対応内容"
                                            />
                                            <div class="incident-assignee-actions">
                                                <LoaderButton
                                                    content="保存"
                                                    :loading="savingAssigneeId === assignee.id"
                                                    @click="saveAssigneeReport(assignee)"
                                                />
                                                <LoaderButton
                                                    content="完了"
                                                    :loading="completingAssigneeId === assignee.id"
                                                    @click="completeAssigneeReport(assignee)"
                                                />
                                            </div>
                                        </div>
                                        <p v-else class="incident-assignee-report">{{ assignee.report || '対応内容は未入力です。' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="canCreateNextAssignment" class="incident-next-assignment">
                            <p v-if="isCreateMode" class="incident-assignment-empty">作成時に最初の対応担当として設定されます。</p>
                            <LongInput
                                v-model="nextAssignmentRequest"
                                :place-holder="isCreateMode ? '担当者への依頼内容' : '次の担当者への依頼内容'"
                            />
                            <MemberSelector
                                v-model="nextAssigneeUsers"
                                :multiple="true"
                                :options="userOptions"
                                :place-holder="isCreateMode ? '担当者を選択' : '次の担当者を選択'"
                            />
                            <LoaderButton
                                v-if="!isCreateMode"
                                content="次の担当者を設定"
                                :loading="creatingNextAssignment"
                                @click="createNextAssignment"
                            />
                        </div>
                    </section>
                </aside>
                </div>
            </div>
            <div v-else class="incident-history">
                <div class="incident-history-header">
                    <div>
                        <h3>更新履歴</h3>
                    </div>
                    <button type="button" class="jump-link bg-inherit text-sm" @click="viewMode = 'detail'">
                        詳細へ戻る
                    </button>
                </div>
                <div v-if="historyLoading" class="py-8 flex justify-center">
                    <div class="spinner-micro"></div>
                </div>
                <div v-else-if="incidentLogs.length" class="incident-history-list">
                    <div v-for="log in incidentLogs" :key="log.id" class="incident-history-item">
                        <div class="incident-history-meta">
                            <div>
                                <strong class="inline-flex items-center gap-2">
                                    <span v-if="log.is_unread" class="incident-history-unread-dot"></span>
                                    {{ actionLabel(log.action) }}
                                </strong>
                                <span>{{ formatDateTime(log.created_at) }}</span>
                            </div>
                            <UserPanel v-if="log.user" :user="log.user" with-name size="22" disable-instant/>
                            <span v-else class="text-[12px] text-[gray]">System</span>
                        </div>
                        <div v-if="log.note" class="incident-history-note">{{ log.note }}</div>
                        <div v-if="displayLogChanges(log)" class="incident-history-changes">
                            <div v-for="(change, field) in displayLogChanges(log)" :key="field" class="incident-history-change">
                                <span>{{ fieldLabel(String(field)) }}</span>
                                <div>
                                    <del>{{ formatLogValue(change?.display_old ?? change?.old) }}</del>
                                    <strong>→</strong>
                                    <ins>{{ formatLogValue(change?.display_new ?? change?.new) }}</ins>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="incident-history-empty">
                    更新履歴はありません。
                </div>
            </div>
            <div v-if="editMode" class="incident-detail-actions">
                <button type="button" class="jump-link bg-inherit text-sm" @click="cancelEdit">
                    キャンセル
                </button>
                <LoaderButton
                    style="margin: 0"
                    :loading="saving"
                    :content="isCreateMode ? '作成する' : hasChanges ? '保存する' : '変更なし'"
                    @triggered="saveChanges"
                />
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { DateTime } from 'luxon';
import { computed, h, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import Back from '@/components/Icons/Back.vue';
import { Incident, IncidentAdvice, IncidentAssignee, IncidentCategory, IncidentPunishment, IncidentReport } from '@/interface/incident';
import { UpdateLog, UpdateLogAction } from '@/interface/updateLog';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { CommonFile, MenuList, User } from '@/interface/globalInterface';
import { useDialog } from '@/composables/dialog';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import type { Project } from '@/interface/projectInterface';
import LongInput from '../Form/LongInput.vue';
import ShortInput from '../Form/ShortInput.vue';
import AppCommentSection from '@/components/Global/AppCommentSection.vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import PostFiles from '@/components/Post/PostFiles.vue';
import { useTheme } from '@/store/theme.js';
import { useSSE } from '@/composables/sse';
import AiIcon from '../Icons/AiIcon.vue';

const props = defineProps<{
    incident?: Incident
    createMode?: boolean
    reporterMode?: boolean
    initialIncident?: Partial<Incident>
}>()

const emit = defineEmits<{
    close: [refresh: boolean]
    updated: [incident: Incident]
    created: [incident: Incident]
    deleted: [incident: Incident]
}>()
const api = useApi()
const auth = useAuthUserStore()
const dialog = useDialog()
const canEditAdminFields = computed(() => auth.isAdmin || auth.isBoss)
const canEditManagerFields = computed(() => canEditAdminFields.value || auth.isPM)
const isCreateMode = computed(() => props.createMode ?? false)
const createBlankIncident = (): Incident => ({
    id: 0,
    title: null,
    description: null,
    caused_by: null,
    incident_category_id: null,
    occurred_date: null,
    reported_date: null,
    project_record_id: null,
    status: null,
    reported_by: auth.activeUser?.id ?? null,
    reported_by_user: auth.activeUser,
    comments_count: 0,
    files: [],
    advices: [],
    ...props.initialIncident,
})
const editMode = ref(isCreateMode.value)
const viewMode = ref<'detail' | 'history'>('detail')
const saving = ref(false)
const deleting = ref(false)
const historyLoading = ref(false)
const localIncident = ref<Incident>({ ...(props.incident ?? createBlankIncident()) })
const mutableParams = ref<Partial<Incident>>({ ...(props.incident ?? createBlankIncident()) })
const selectedCausedByUser = ref<User | null>(props.incident?.caused_by_user ?? null)
const selectedReportedByUser = ref<User | null>(props.incident?.reported_by_user ?? auth.activeUser ?? null)
const occurredDateRef = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
const reportedDateRef = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
const descriptionRef = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
const uploadedFiles = ref<CommonFile[]>([...(props.incident?.files ?? [])])
const incidentLogs = ref<UpdateLog[]>([])
const markedReadIncidentIds = ref<Set<number>>(new Set())
const assigneeResponses = ref<Record<number, string>>({})
const nextAssigneeUsers = ref<User[]>([])
const nextAssignmentRequest = ref<string | null>(null)
const savingAssigneeId = ref<number | null>(null)
const completingAssigneeId = ref<number | null>(null)
const creatingNextAssignment = ref(false)
const adviceLoading = ref(false)
const adviceDraft = ref('')
const selectedAdviceId = ref<number | null>(null)
const conclusionAdviceLoading = ref(false)
const conclusionAdviceDraft = ref('')
const selectedConclusionAdviceId = ref<number | null>(null)
const deletingAdviceId = ref<number | null>(null)
type IncidentUnreadBadge = {
    type: 'new' | 'updated'
    count: number
    readBefore: string | null
}
const unreadBadge = ref<IncidentUnreadBadge | null>(null)
const unreadBadgeVisible = ref(false)
let unreadBadgeHideTimer: ReturnType<typeof setTimeout> | undefined
type IncidentProjectOption = Pick<Project, 'id' | 'name' | 'date_start' | 'date_end' | 'category'>
const mentionableUsers = ref<User[]>([])
const incidentOptions = ref<{
    categories: IncidentCategory[]
    punishments: IncidentPunishment[]
    users: User[]
    projects: IncidentProjectOption[]
    statuses: string[]
}>({
    categories: [],
    punishments: [],
    users: [],
    projects: [],
    statuses: [],
})
const staffEditableKeys = [
    'occurred_date',
    'reported_date',
    'incident_category_id',
    'caused_by',
    'project_record_id',
    'related_parties',
    'description',
    'occured_location',
    'reason',
    'files',
] as const

const managerEditableKeys = [
    ...staffEditableKeys,
    'prevention',
    'prevention_apply_status',
    'resolution',
    'memo',
    'amount_of_damage',
    'payee',
    'expense_details',
] as const

const fullEditableKeys = [
    'status',
    'occurred_date',
    'reported_date',
    'instruction_date',
    'incident_category_id',
    'incident_punishment_id',
    'caused_by',
    'project_record_id',
    'related_parties',
    'description',
    'occured_location',
    'reason',
    'files',
    'instruction',
    'prevention',
    'prevention_apply_status',
    'resolution',
    'risk_level',
    'severity_level',
    'amount_of_damage',
    'payee',
    'expense_details',
    'committee_decision_date',
    'committee_members',
    'committee_decision',
    'memo',
    'aftermath_comment',
] as const

type IncidentEditableKey = typeof fullEditableKeys[number] | 'files'
const editableKeys = fullEditableKeys.filter(key => key !== 'files' && key !== 'caused_by') as readonly Exclude<IncidentEditableKey, 'files' | 'caused_by'>[]
const isPendingIncident = computed(() => !localIncident.value.status || localIncident.value.status === '処分未決定')
const canSelfManagePendingIncident = computed(() => {
    return !isCreateMode.value
        && localIncident.value.reported_by === auth.activeUser?.id
        && isPendingIncident.value
})
const canEditIncident = computed(() => isCreateMode.value || canEditManagerFields.value || canSelfManagePendingIncident.value)
const canDeleteIncident = computed(() => !isCreateMode.value && (canEditAdminFields.value || canSelfManagePendingIncident.value))
const canViewIncidentHistory = computed(() => canEditManagerFields.value)
const canViewIncidentPoint = computed(() => !isCreateMode.value)
const canViewIncidentStatus = computed(() => !isCreateMode.value || canUseField('status'))
const allowedEditableKeys = computed<readonly IncidentEditableKey[]>(() => {
    if (!canEditIncident.value) return []
    if (canEditAdminFields.value) return fullEditableKeys
    if (auth.isPM) return managerEditableKeys
    return staffEditableKeys
})
const isCausedByActiveUser = computed(() => {
    return !isCreateMode.value
        && Boolean(auth.activeUser?.id)
        && localIncident.value.caused_by === auth.activeUser?.id
})
const isReportedByActiveUser = computed(() => {
    return !isCreateMode.value
        && Boolean(auth.activeUser?.id)
        && localIncident.value.reported_by === auth.activeUser?.id
})
const allowedViewableKeys = computed<readonly IncidentEditableKey[]>(() => {
    if (isCreateMode.value) return allowedEditableKeys.value
    if (canEditAdminFields.value) return fullEditableKeys
    if (auth.isPM) return managerEditableKeys
    if (isCausedByActiveUser.value || isReportedByActiveUser.value) return managerEditableKeys
    return []
})
const canUseField = (key: IncidentEditableKey) => allowedViewableKeys.value.includes(key)
const canSubmitField = (key: IncidentEditableKey) => allowedEditableKeys.value.includes(key)
const canEditField = (key: IncidentEditableKey) => editMode.value && canEditIncident.value && canSubmitField(key)
const canShowSection = (keys: readonly IncidentEditableKey[]) => keys.some(key => editMode.value ? canEditField(key) : canUseField(key))
const incidentReports = computed<IncidentReport[]>(() => {
    return [...(localIncident.value.reports ?? [])]
        .sort((a, b) => ((a.step ?? 0) - (b.step ?? 0)) || (a.id - b.id))
})
const latestIncidentReport = computed<IncidentReport | null>(() => incidentReports.value.at(-1) ?? null)
const isIncidentCompleted = computed(() => localIncident.value.status === '完了')
const latestIncidentReportComplete = computed(() => {
    const assignees = latestIncidentReport.value?.assignees ?? []
    return assignees.length > 0 && assignees.every(assignee => Boolean(assignee.completed_at))
})
const activeUserLatestAssignee = computed(() => {
    const activeUserId = auth.activeUser?.id
    if (!activeUserId || isIncidentCompleted.value) return null

    return latestIncidentReport.value?.assignees?.find(assignee => assignee.user_id === activeUserId) ?? null
})
const canCreateNextAssignment = computed(() => {
    if (isCreateMode.value) return canEditManagerFields.value
    if (isIncidentCompleted.value) return false
    if (!latestIncidentReport.value) return canEditManagerFields.value
    return latestIncidentReportComplete.value
        && (canEditManagerFields.value || Boolean(activeUserLatestAssignee.value))
})
const canUseIncidentAdvice = computed(() => !isCreateMode.value && canEditManagerFields.value)
const canUseIncidentConclusion = computed(() => !isCreateMode.value && canEditAdminFields.value && isIncidentCompleted.value)
const resolutionAdvices = computed<IncidentAdvice[]>(() => {
    return [...(localIncident.value.advices ?? [])]
        .filter(advice => advice.type === 'resolution')
        .sort((a, b) => (Date.parse(b.created_at ?? '') || 0) - (Date.parse(a.created_at ?? '') || 0))
})
const conclusionAdvices = computed<IncidentAdvice[]>(() => {
    return [...(localIncident.value.advices ?? [])]
        .filter(advice => advice.type === 'conclusion')
        .sort((a, b) => (Date.parse(b.created_at ?? '') || 0) - (Date.parse(a.created_at ?? '') || 0))
})
const sanitizedAdviceDraft = computed(() => DOMPurify.sanitize(marked(adviceDraft.value) as string))
const sanitizedConclusionAdviceDraft = computed(() => DOMPurify.sanitize(marked(conclusionAdviceDraft.value) as string))
const adviceHtml = (advice: IncidentAdvice) => {
    return DOMPurify.sanitize(marked(advice.content ?? '') as string)
}
const copyAdviceContent = async (advice: IncidentAdvice) => {
    const content = advice.content ?? ''
    if (!content) return

    try {
        await navigator.clipboard.writeText(content)
    } catch {
        const textarea = document.createElement('textarea')
        textarea.value = content
        textarea.style.position = 'fixed'
        textarea.style.left = '-9999px'
        document.body.appendChild(textarea)
        textarea.select()
        document.execCommand('copy')
        textarea.remove()
    }

    dialog.toast('コピーしました。')
}
const theme = useTheme()
const {
    on: onAdviceStream,
    start: startAdviceStream,
    stop: stopAdviceStream,
} = useSSE({ autoReconnect: false })
const {
    on: onConclusionAdviceStream,
    start: startConclusionAdviceStream,
    stop: stopConclusionAdviceStream,
} = useSSE({ autoReconnect: false })

const appendAdviceStreamChunk = (payload: string) => {
    try {
        const parsed = JSON.parse(payload)
        if (parsed?.type === 'text_delta') {
            adviceDraft.value += parsed.delta ?? ''
        } else if (parsed?.event === 'response.output_text.delta') {
            adviceDraft.value += parsed.response?.delta ?? ''
        }
    } catch {}
}

const appendConclusionAdviceStreamChunk = (payload: string) => {
    try {
        const parsed = JSON.parse(payload)
        if (parsed?.type === 'text_delta') {
            conclusionAdviceDraft.value += parsed.delta ?? ''
        } else if (parsed?.event === 'response.output_text.delta') {
            conclusionAdviceDraft.value += parsed.response?.delta ?? ''
        }
    } catch {}
}

onAdviceStream('message', appendAdviceStreamChunk)
onAdviceStream('update', appendAdviceStreamChunk)
onConclusionAdviceStream('message', appendConclusionAdviceStreamChunk)
onConclusionAdviceStream('update', appendConclusionAdviceStreamChunk)

onAdviceStream('error', () => {
    adviceLoading.value = false
    dialog.ping('AIアドバイスの生成に失敗しました。しばらくしてから再度お試しください。')
})

onConclusionAdviceStream('error', () => {
    conclusionAdviceLoading.value = false
    dialog.ping('AI総括の生成に失敗しました。しばらくしてから再度お試しください。')
})

onAdviceStream('complete', async () => {
    adviceLoading.value = false
    await refreshResolutionAdvices()
})

onConclusionAdviceStream('complete', async () => {
    conclusionAdviceLoading.value = false
    await refreshConclusionAdvices()
})

const toggleAdviceExpansion = (adviceId: number) => {
    selectedAdviceId.value = selectedAdviceId.value === adviceId ? null : adviceId
}

const toggleConclusionAdviceExpansion = (adviceId: number) => {
    selectedConclusionAdviceId.value = selectedConclusionAdviceId.value === adviceId ? null : adviceId
}

const adviceMenuItems = (advice: IncidentAdvice, type: 'resolution' | 'conclusion' = 'resolution'): MenuList[] => [{
    title: deletingAdviceId.value === advice.id ? '削除中...' : '削除',
    action: () => deleteIncidentAdvice(advice.id, type),
}]

const deleteIncidentAdvice = async (adviceId: number, type: 'resolution' | 'conclusion') => {
    if (deletingAdviceId.value) return

    const decision = await dialog.ask(type === 'conclusion' ? 'このAI総括を削除しますか？' : 'このAIアドバイスを削除しますか？')
    if (!decision.value) return

    deletingAdviceId.value = adviceId
    try {
        const res = await api.del('/incident_advice', {
            id: adviceId,
        }, { silent: true })

        if (res?.deleted) {
            if (selectedAdviceId.value === adviceId) {
                selectedAdviceId.value = null
            }
            if (selectedConclusionAdviceId.value === adviceId) {
                selectedConclusionAdviceId.value = null
            }

            if (type === 'conclusion') {
                await refreshConclusionAdvices()
                dialog.toast('AI総括を削除しました。')
            } else {
                await refreshResolutionAdvices()
                dialog.toast('AIアドバイスを削除しました。')
            }
        }
    } finally {
        deletingAdviceId.value = null
    }
}

const menuItems = computed<MenuList[]>(() => {
    const items: MenuList[] = [
        ...(canViewIncidentHistory.value ? [{
            title: viewMode.value === 'history' ? '詳細を見る' : '更新履歴',
            action: () => viewMode.value === 'history' ? viewMode.value = 'detail' : showHistory(),
        }] : []),
    ]

    return [
        ...(canEditIncident.value ? [{
            title: editMode.value ? '編集をキャンセル' : '編集',
            action: () => editMode.value ? cancelEdit() : startEdit(),
        }] : []),
        ...(canDeleteIncident.value ? [{
            title: deleting.value ? '削除中...' : '削除',
            action: deleteIncident,
        }] : []),
        ...items,
    ]
})

const buildPayload = () => {
    const payload: Partial<Incident> = {}

    if (isCreateMode.value) {
        for (const key of editableKeys) {
            if (!canSubmitField(key)) continue
            const nextValue = normalizeUpdateValue(key, mutableParams.value[key])
            if (nextValue !== null && nextValue !== '') {
                ;(payload as any)[key] = nextValue
            }
        }

        if (canSubmitField('caused_by')) {
            const nextCausedBy = selectedCausedByUser.value?.id ?? null
            if (nextCausedBy) {
                payload.caused_by = nextCausedBy
            }
        }

        const nextReportedBy = selectedReportedByUser.value?.id ?? null
        if (nextReportedBy) {
            ;(payload as Partial<Incident> & { reported_by: number }).reported_by = nextReportedBy
        }

        if (canSubmitField('files')) {
            const nextFileIds = uploadedFiles.value.map(file => file.id).sort((a, b) => a - b)
            if (nextFileIds.length) {
                ;(payload as Partial<Incident> & { file_ids: number[] }).file_ids = nextFileIds
            }
        }

        if (canCreateNextAssignment.value) {
            const assigneeIds = nextAssigneeUsers.value.map(user => user.id)
            if (assigneeIds.length) {
                ;(payload as Partial<Incident> & { assignee_ids: number[]; assignment_request?: string | null }).assignee_ids = assigneeIds
                ;(payload as Partial<Incident> & { assignee_ids?: number[]; assignment_request: string | null }).assignment_request = nextAssignmentRequest.value
            }
        }

        return payload
    }

    for (const key of editableKeys) {
        if (!canSubmitField(key)) continue
        const nextValue = normalizeUpdateValue(key, mutableParams.value[key])
        const currentValue = normalizeUpdateValue(key, localIncident.value[key])

        if (nextValue !== currentValue) {
            ;(payload as any)[key] = nextValue
        }
    }

    if (canSubmitField('caused_by')) {
        const nextCausedBy = selectedCausedByUser.value?.id ?? null
        const currentCausedBy = localIncident.value.caused_by ?? null
        if (nextCausedBy !== currentCausedBy) {
            payload.caused_by = nextCausedBy
        }
    }

    if (canSubmitField('files')) {
        const nextFileIds = uploadedFiles.value.map(file => file.id).sort((a, b) => a - b)
        const currentFileIds = (localIncident.value.files ?? []).map(file => file.id).sort((a, b) => a - b)
        if (JSON.stringify(nextFileIds) !== JSON.stringify(currentFileIds)) {
            ;(payload as Partial<Incident> & { file_ids: number[] }).file_ids = nextFileIds
        }
    }

    return payload
}

const hasChanges = computed(() => isCreateMode.value || Object.keys(buildPayload()).length > 0)

const userOptions = computed(() => {
    const users = [...incidentOptions.value.users]
    const extraUsers = [localIncident.value.caused_by_user, selectedReportedByUser.value]

    for (const extra of extraUsers) {
        if (extra && !users.some(user => user.id === extra.id)) {
            users.push(extra)
        }
    }

    return users
})

const projectOptions = computed(() => {
    const projects = [...incidentOptions.value.projects]
    const currentProject = localIncident.value.project_record

    if (currentProject && !projects.some(project => project.id === currentProject.id)) {
        projects.push(currentProject)
    }

    return projects
})

onMounted(() => {
    loadIncidentOptions()
    initializeAssigneeResponses(localIncident.value)
    initializeUnreadBadge(localIncident.value)
    markIncidentRead()
    loadMentionableUsers()
})

onBeforeUnmount(() => {
    if (unreadBadgeHideTimer) clearTimeout(unreadBadgeHideTimer)
    stopAdviceStream()
    stopConclusionAdviceStream()
})

watch(
    () => props.incident,
    (incident) => {
        const nextIncident = incident ?? createBlankIncident()
        const isSameIncident = nextIncident.id === localIncident.value.id
        const shouldKeepUnreadBadge = isSameIncident && Boolean(unreadBadge.value)

        localIncident.value = { ...nextIncident }
        mutableParams.value = { ...nextIncident }
        selectedCausedByUser.value = nextIncident.caused_by_user ?? null
        selectedReportedByUser.value = nextIncident.reported_by_user ?? auth.activeUser ?? null
        uploadedFiles.value = [...(nextIncident.files ?? [])]
        nextAssigneeUsers.value = []
        nextAssignmentRequest.value = null
        selectedAdviceId.value = nextIncident.advices?.find(advice => advice.type === 'resolution')?.id ?? null
        selectedConclusionAdviceId.value = nextIncident.advices?.find(advice => advice.type === 'conclusion')?.id ?? null
        adviceDraft.value = ''
        adviceLoading.value = false
        conclusionAdviceDraft.value = ''
        conclusionAdviceLoading.value = false
        stopAdviceStream()
        stopConclusionAdviceStream()
        initializeAssigneeResponses(nextIncident)
        editMode.value = isCreateMode.value
        viewMode.value = 'detail'
        incidentLogs.value = []

        if (!shouldKeepUnreadBadge) {
            initializeUnreadBadge(nextIncident)
        }

        markIncidentRead()
    },
)

watch(
    resolutionAdvices,
    (advices) => {
        if (!advices.length) {
            selectedAdviceId.value = null
            return
        }

        if (!advices.some(advice => advice.id === selectedAdviceId.value)) {
            selectedAdviceId.value = advices[0].id
        }
    },
    { immediate: true },
)

watch(
    conclusionAdvices,
    (advices) => {
        if (!advices.length) {
            selectedConclusionAdviceId.value = null
            return
        }

        if (!advices.some(advice => advice.id === selectedConclusionAdviceId.value)) {
            selectedConclusionAdviceId.value = advices[0].id
        }
    },
    { immediate: true },
)
const loadMentionableUsers = async () => {
    const data = await api.get('/incident_related_mentionable_users', {
        incident_id: localIncident.value.id,
    }, { silent: true })
    if (!data) return

    mentionableUsers.value = data ?? []
}
const initializeAssigneeResponses = (incident: Incident) => {
    const responses: Record<number, string> = {}

    for (const report of incident.reports ?? []) {
        for (const assignee of report.assignees ?? []) {
            responses[assignee.id] = assignee.report ?? ''
        }
    }

    assigneeResponses.value = responses
}

const getIncidentLastReadAt = (incident: Incident) => {
    return incident.last_read_at ?? incident.read_histories?.[0]?.last_read_at ?? null
}

const initializeUnreadBadge = (incident: Incident) => {
    if (isCreateMode.value || !incident.id || incident.status === '完了') {
        unreadBadge.value = null
        unreadBadgeVisible.value = false
        return
    }

    const readBefore = getIncidentLastReadAt(incident)
    const updateCount = incident.unread_update_logs_count ?? 0

    if (!readBefore) {
        unreadBadge.value = { type: 'new', count: updateCount, readBefore: null }
        unreadBadgeVisible.value = true
        return
    }

    if (updateCount > 0) {
        unreadBadge.value = { type: 'updated', count: updateCount, readBefore }
        unreadBadgeVisible.value = true
        return
    }

    unreadBadge.value = null
    unreadBadgeVisible.value = false
}

const scheduleUnreadBadgeHide = () => {
    if (unreadBadgeHideTimer) clearTimeout(unreadBadgeHideTimer)
    unreadBadgeHideTimer = setTimeout(() => {
        unreadBadgeVisible.value = false
    }, 3000)
}

const handleUnreadBadgeClick = async () => {
    if (!unreadBadge.value) return
    if (unreadBadge.value.type === 'updated' && canViewIncidentHistory.value) {
        await showHistory(true)
    }
    scheduleUnreadBadgeHide()
}

const markIncidentRead = async () => {
    if (isCreateMode.value || !localIncident.value.id) return
    if (markedReadIncidentIds.value.has(localIncident.value.id)) return

    markedReadIncidentIds.value.add(localIncident.value.id)
    let response
    try {
        response = await api.post('/incident_read_history', { id: localIncident.value.id }, { silent: true })
    } catch {
        markedReadIncidentIds.value.delete(localIncident.value.id)
        return
    }

    if (!response?.last_read_at) return

    localIncident.value.last_read_at = response.last_read_at
    localIncident.value.unread_update_logs_count = 0
    localIncident.value.unread_comments_count = 0
    mutableParams.value.last_read_at = response.last_read_at
    mutableParams.value.unread_update_logs_count = 0
    mutableParams.value.unread_comments_count = 0
    emit('updated', { ...localIncident.value })

    if (unreadBadge.value?.type === 'new') {
        scheduleUnreadBadgeHide()
    }
}
const loadIncidentOptions = async () => {
    const data = await api.get('/incident_options', null, { silent: true })
    if (!data) return

    incidentOptions.value = {
        categories: data.categories ?? [],
        punishments: data.punishments ?? [],
        users: data.users ?? [],
        projects: data.projects ?? [],
        statuses: data.statuses ?? [],
    }
}

const DetailItem = (props: { label: string; value?: string | number | null }) => {
    const value = props.value === null || props.value === undefined || props.value === '' ? '-' : String(props.value)

    return h('div', { class: 'incident-detail-item' }, [
        h('span', props.label),
        h('p', value),
    ])
}
const formatDate = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/M/d') : date
}

const formatDateTime = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/M/d HH:mm') : date
}

const normalizeDate = (date?: string | null) => {
    if (!date) return null
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toISODate() : date
}

const normalizeUpdateValue = (key: keyof Incident, value: unknown) => {
    if (key === 'occurred_date' || key === 'reported_date' || key === 'instruction_date' || key === 'committee_decision_date') {
        return normalizeDate(value as string | null | undefined)
    }

    if (key === 'risk_level' || key === 'severity_level' || key === 'amount_of_damage') {
        if (value === '' || value === null || value === undefined) return null
        const numericValue = Number(value)
        return Number.isFinite(numericValue) ? numericValue : value
    }

    return value === undefined ? null : value
}

const formatAmount = (amount?: number | null) => {
    if (amount === null || amount === undefined) return '-'
    return amount.toLocaleString()
}

const incidentPoint = (incident: Incident) => {
    return (incident.risk_level ?? 0) * (incident.severity_level ?? 0)
}

const RISK_LEVEL_COLORS = [
    { min: 9, color: '#ff6347' },
    { min: 6, color: '#ff826c' },
    { min: 4, color: '#ffa191' },
    { min: 2, color: '#ffc1b5' },
    { min: 1, color: '#ffe0da' },
]

const riskLevelColor = (incident: Incident) => {
    const riskLevel = incidentPoint(incident)
    return RISK_LEVEL_COLORS.find(l => riskLevel >= l.min)?.color ?? 'var(--bg2)'
}
const startEdit = () => {
    if (!canEditIncident.value) return

    mutableParams.value = { ...localIncident.value }
    selectedCausedByUser.value = localIncident.value.caused_by_user ?? null
    editMode.value = true
}

const cancelEdit = () => {
    if (isCreateMode.value) {
        emit('close', false)
        return
    }

    mutableParams.value = { ...localIncident.value }
    selectedCausedByUser.value = localIncident.value.caused_by_user ?? null
    uploadedFiles.value = [...(localIncident.value.files ?? [])]
    editMode.value = false
}

const showHistory = async (forceReload = false) => {
    if (isCreateMode.value || !canViewIncidentHistory.value) return
    editMode.value = false
    viewMode.value = 'history'

    if (incidentLogs.value.length && !forceReload) return

    historyLoading.value = true
    try {
        const data = await api.get('/incident_logs', {
            id: localIncident.value.id,
            read_before: unreadBadge.value?.readBefore ?? undefined,
        })
        incidentLogs.value = data ?? []
    } finally {
        historyLoading.value = false
    }
}

const saveChanges = async () => {
    if (saving.value || !canEditIncident.value || !hasChanges.value) return

    const requiredFields = [occurredDateRef.value, reportedDateRef.value, descriptionRef.value].filter(
        (field): field is { validate: () => Promise<{ valid: boolean }> } => Boolean(field)
    )
    let valid = true
    for (const field of requiredFields) {
        const result = await field?.validate()
        valid = valid && Boolean(result?.valid)
    }
    if (!valid) {
        dialog.ping('必須項目を入力してください。')
        return
    }

    saving.value = true
    try {
        const payload = buildPayload()
        const res = isCreateMode.value
            ? await api.post('/incident_record_create', payload, { toast: 'インシデントを作成しました。' })
            : await api.post('/incident_record_update', {
                id: localIncident.value.id,
                ...payload,
            }, { toast: 'インシデントを更新しました。' })

        if (res?.incident) {
            localIncident.value = res.incident
            mutableParams.value = { ...res.incident }
            uploadedFiles.value = [...(res.incident.files ?? [])]
            editMode.value = false
            incidentLogs.value = []
            if (isCreateMode.value) {
                emit('created', res.incident)
            } else {
                emit('updated', res.incident)
            }
        }
    } finally {
        saving.value = false
    }
}

const handleCommentCountChanged = (count: number) => {
    localIncident.value.comments_count = count
    emit('updated', { ...localIncident.value })
}

const generateResolutionAdvice = async () => {
    if (!canUseIncidentAdvice.value || adviceLoading.value || !localIncident.value.id) return

    stopAdviceStream()
    adviceDraft.value = ''
    adviceLoading.value = true

    try {
        startAdviceStream('/incident_advice_stream', {
            incident_id: localIncident.value.id,
            type: 'resolution',
        }, { endSignal: '[DONE]' })
    } catch (error) {
        adviceLoading.value = false
        dialog.ping('AIアドバイスの準備に失敗しました。')
    }
}

const generateConclusionAdvice = async () => {
    if (!canUseIncidentConclusion.value || conclusionAdviceLoading.value || !localIncident.value.id) return

    stopConclusionAdviceStream()
    conclusionAdviceDraft.value = ''
    conclusionAdviceLoading.value = true

    try {
        startConclusionAdviceStream('/incident_advice_stream', {
            incident_id: localIncident.value.id,
            type: 'conclusion',
        }, { endSignal: '[DONE]' })
    } catch (error) {
        conclusionAdviceLoading.value = false
        dialog.ping('AI総括の準備に失敗しました。')
    }
}

const refreshResolutionAdvices = async () => {
    if (!localIncident.value.id) return

    try {
        const res = await api.get('/incident_advice', {
            incident_id: localIncident.value.id,
            type: 'resolution',
        }, { silent: true })

        const advices = res?.advices
        if (!Array.isArray(advices)) return

        localIncident.value.advices = [
            ...advices,
            ...(localIncident.value.advices ?? []).filter(advice => advice.type !== 'resolution'),
        ]
        mutableParams.value.advices = localIncident.value.advices
        selectedAdviceId.value = advices[0]?.id ?? selectedAdviceId.value
        adviceDraft.value = ''
        emit('updated', { ...localIncident.value })
    } catch (error) {
        dialog.ping('保存済みAIアドバイスの取得に失敗しました。')
    }
}

const refreshConclusionAdvices = async () => {
    if (!localIncident.value.id) return

    try {
        const res = await api.get('/incident_advice', {
            incident_id: localIncident.value.id,
            type: 'conclusion',
        }, { silent: true })

        const advices = res?.advices
        if (!Array.isArray(advices)) return

        localIncident.value.advices = [
            ...advices,
            ...(localIncident.value.advices ?? []).filter(advice => advice.type !== 'conclusion'),
        ]
        mutableParams.value.advices = localIncident.value.advices
        selectedConclusionAdviceId.value = advices[0]?.id ?? selectedConclusionAdviceId.value
        conclusionAdviceDraft.value = ''
        emit('updated', { ...localIncident.value })
    } catch (error) {
        dialog.ping('保存済みAI総括の取得に失敗しました。')
    }
}

const refreshIncidentFromWorkflowResponse = (incident: Incident) => {
    localIncident.value = incident
    mutableParams.value = { ...incident }
    uploadedFiles.value = [...(incident.files ?? [])]
    initializeAssigneeResponses(incident)
    emit('updated', { ...incident })
}

const canRespondToAssignee = (assignee: IncidentAssignee) => {
    return activeUserLatestAssignee.value?.id === assignee.id
        && !assignee.completed_at
        && !isIncidentCompleted.value
}

const saveAssigneeReport = async (assignee: IncidentAssignee) => {
    if (!canRespondToAssignee(assignee) || savingAssigneeId.value) return

    savingAssigneeId.value = assignee.id
    try {
        const res = await api.post('/incident_assignee_report', {
            id: assignee.id,
            report: assigneeResponses.value[assignee.id] ?? '',
        }, { toast: '対応内容を保存しました。' })

        if (res?.incident) {
            refreshIncidentFromWorkflowResponse(res.incident)
        }
    } finally {
        savingAssigneeId.value = null
    }
}

const completeAssigneeReport = async (assignee: IncidentAssignee) => {
    if (!canRespondToAssignee(assignee) || completingAssigneeId.value) return

    completingAssigneeId.value = assignee.id
    try {
        const res = await api.post('/incident_assignee_complete', {
            id: assignee.id,
            report: assigneeResponses.value[assignee.id] ?? '',
        }, { toast: '対応を完了しました。' })

        if (res?.incident) {
            refreshIncidentFromWorkflowResponse(res.incident)
        }
    } finally {
        completingAssigneeId.value = null
    }
}

const createNextAssignment = async () => {
    if (!canCreateNextAssignment.value || creatingNextAssignment.value) return

    const assigneeIds = nextAssigneeUsers.value.map(user => user.id)
    if (!assigneeIds.length) return

    creatingNextAssignment.value = true
    try {
        const res = await api.post('/incident_report_assignment', {
            incident_id: localIncident.value.id,
            request: nextAssignmentRequest.value,
            assignee_ids: assigneeIds,
        }, { toast: '次の担当者を設定しました。' })

        if (res?.incident) {
            nextAssigneeUsers.value = []
            nextAssignmentRequest.value = null
            refreshIncidentFromWorkflowResponse(res.incident)
        }
    } finally {
        creatingNextAssignment.value = false
    }
}

const deleteIncident = async () => {
    if (deleting.value || !canDeleteIncident.value) return

    const answer = await dialog.ask('このインシデントを削除しますか？', {
        answers: [
            { value: true, label: '削除する' },
            { value: false, label: 'キャンセル' },
        ],
    })

    if (!answer.value) return

    deleting.value = true
    try {
        const res = await api.post('/incident_record_delete', {
            id: localIncident.value.id,
        }, { toast: 'インシデントを削除しました。' })

        if (res?.deleted) {
            emit('deleted', localIncident.value)
            emit('close', true)
        }
    } finally {
        deleting.value = false
    }
}

const actionLabel = (action: UpdateLogAction) => {
    const labels: Record<string, string> = {
        created: '作成',
        updated: '更新',
        status_changed: 'ステータス変更',
        deleted: '削除',
        restored: '復元',
    }

    return labels[action] ?? action
}

const fieldLabel = (field: string) => {
    const labels: Record<string, string> = {
        status: 'ステータス',
        occurred_date: '発生日',
        reported_date: '報告日',
        instruction_date: '指導日',
        incident_category_id: '区分',
        incident_punishment_id: '懲罰区分',
        caused_by: '当事者',
        project_record_id: 'プロジェクト',
        files: '添付ファイル',
        related_parties: '関係者',
        description: '概要',
        occured_location: '発生場所',
        reason: '原因',
        instruction: '指導内容',
        prevention: '再発防止策',
        prevention_apply_status: '再発防止策の実施状況',
        resolution: '解決内容',
        risk_level: 'リスクレベル',
        severity_level: '損害レベル',
        amount_of_damage: '損害額',
        payee: '支払先',
        expense_details: '費用詳細',
        committee_decision_date: '委員会決定日',
        committee_members: '委員会メンバー',
        committee_decision: '委員会決定',
        memo: 'メモ',
        aftermath_comment: '顛末コメント',
        deleted_at: '削除日時',
    }

    return labels[field] ?? field
}

const displayLogChanges = (log: UpdateLog) => {
    return log.display_changes ?? log.changes
}

const formatLogValue = (value: unknown) => {
    if (value === null || value === undefined || value === '') return '未設定'
    if (Array.isArray(value)) return value.length ? value.join('、') : '未設定'
    if (typeof value === 'object') return JSON.stringify(value)
    return String(value)
}
</script>

<style lang="scss">

.incident-detail-title{
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.incident-detail-mark{
    width: 8px;
    height: 42px;
    flex-shrink: 0;
}

.incident-detail-kicker{
    font-size: 11px;
    color: gray;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.incident-detail-title h2{
    font-size: 17px;
    font-weight: 700;
    line-height: 1.35;
    max-width: 760px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.incident-detail-menu{
    display: flex;
    align-items: center;
    gap: 10px;
}

.incident-read-badge{
    border: 1px solid tomato;
    color: tomato;
    background: rgba(255, 99, 71, 0.08);
    padding: 4px 8px;
    font-size: 11px;
    line-height: 1;
    letter-spacing: 0.02em;
}

.incident-read-badge--new{
    background: tomato;
    color: white;
}

.incident-detail-shell{
    display: grid;
    grid-template-columns: 160px minmax(0, 1fr);
    gap: 24px;
    color: var(--primary-color);
}



.incident-detail-score{
    border-left: 6px solid var(--calendarBorder);
    background: var(--bg3);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-left: 14px;
    margin-bottom: 18px;
}

.incident-detail-score span{
    font-size: 28px;
    line-height: 1;
}

.incident-detail-score small,
.incident-detail-facts span,
.incident-people-grid span,
.incident-detail-item span{
    font-size: 11px;
    color: gray;
}

.incident-detail-facts{
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.incident-detail-facts strong{
    display: block;
    margin-top: 4px;
    font-size: 13px;
    font-weight: 700;
}

.incident-permission-legend{
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 20px;
    font-size: 11px;
    color: gray;
}

.incident-detail-main{
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
    border-left: solid thin var(--calendarBorder);
    border-right: solid thin var(--calendarBorder);
    padding: 0 24px;
}

.incident-detail-content{
    min-width: 0;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(290px, 290px);
    align-items: start;
    gap: 20px;
}

.incident-detail-assignment{
    min-width: 0;
}

.incident-detail-assignment .incident-assignment-section{
    position: sticky;
    top: 12px;
}


.incident-detail-section h3{
    margin: 0 0 12px;
    font-size: 14px;
}




.incident-file-block{
    margin-top: 24px;
    padding-top: 18px;
}

.incident-people-grid,
.incident-admin-grid{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 30px;
}

.incident-people-grid strong{
    display: block;
    margin-top: 5px;
    font-size: 13px;
}

.incident-field-stack{
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.incident-detail-item{
    min-width: 0;
}

.incident-field--staff,
.incident-field--manager,
.incident-field--full{
    width: fit-content;
    padding: 4px 8px;
    border: 1px solid transparent;
    font-size: 11px;
}

.incident-field--staff{
    background: rgba(79, 140, 255, 0.1);
    border-color: rgba(79, 140, 255, 0.25);
}

.incident-field--manager{
    background: rgba(245, 158, 11, 0.12);
    border-color: rgba(245, 158, 11, 0.28);
}

.incident-field--full{
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.25);
}

.incident-detail-item p{
    margin-top: 4px;
    white-space: pre-wrap;
    line-height: 1.6;
    font-size: 13px;
}

.incident-ai-advice-section{
    border: 1px solid var(--calendarBorder);
    padding: 18px;
}


.incident-ai-advice-head strong{
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
}

.incident-ai-advice-head p{
    margin: 0;
    color: gray;
    font-size: 12px;
    line-height: 1.6;
}

.incident-ai-advice-preview{
    background: var(--background-color);
    padding: 14px;
    font-size: 13px;
    line-height: 1.7;
}

.incident-ai-advice-preview > span,
.incident-ai-advice-meta span{
    display: block;
    margin-bottom: 8px;
    color: gray;
    font-size: 11px;
}

.incident-ai-advice-preview :deep(p){
    margin: 0 0 10px;
}

.incident-ai-advice-preview :deep(ul),
.incident-ai-advice-preview :deep(ol){
    margin: 8px 0 10px;
    padding-left: 20px;
}

.incident-ai-advice-history{
    margin-top: 16px;
}

.incident-ai-advice-version{
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    cursor: default;
    display: block;
}

.incident-ai-advice-version + .incident-ai-advice-version{
    margin-top: -1px;
}

.incident-ai-advice-version--open{
    position: relative;
    z-index: 1;
}

.incident-ai-advice-version-head{
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    cursor: pointer;
    user-select: none;
}

.incident-ai-advice-version-body{
    user-select: text;
}

.incident-ai-advice-arrow{
    color: gray;
    line-height: 0;
    transition: transform .2s ease;
}

.incident-ai-advice-version-title{
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.incident-ai-advice-version-title strong{
    font-size: 13px;
}

.incident-ai-advice-version-title span{
    color: gray;
    font-size: 11px;
}

.incident-ai-advice-menu{
    display: flex;
    justify-content: flex-end;
}

.incident-ai-advice-copy{
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    color: var(--primary-color);
    padding: 4px 10px;
    font-size: 11px;
    line-height: 1;
}

.incident-ai-advice-copy:hover{
    background: var(--secondary-background);
}

.incident-ai-advice-meta{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
}

.incident-detail-actions{
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid var(--calendarBorder);
}

.incident-punishment-table-wrap{
    margin-bottom: 20px;
    overflow-x: auto;
}

.incident-punishment-table{
    width: min(360px, 100%);
    border-collapse: collapse;
    background: var(--background-color);
    font-size: 12px;
}

.incident-punishment-table th,
.incident-punishment-table td{
    border: 1px solid var(--calendarBorder);
    padding: 8px 10px;
    text-align: left;
    line-height: 1.4;
}

.incident-punishment-table th{
    background: var(--bg3);
    font-weight: 700;
}


.incident-assignment-steps{
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.incident-assignment-step{
    border: 1px solid var(--calendarBorder);
    padding: 14px;
}

.incident-assignment-step-head{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.incident-assignment-step-head div{
    display: flex;
    align-items: center;
    gap: 8px;
}

.incident-assignment-status-chip{
    min-width: 52px;
    justify-content: center;
    padding: 4px 10px;
    border: 1px solid transparent;
    font-size: 11px;
    line-height: 1;
    border-radius: 999px;
}

.incident-assignment-status-chip--active{
    background: rgba(245, 158, 11, 0.12);
    border-color: rgba(245, 158, 11, 0.28);
    color: #b45309;
}

.incident-assignment-status-chip--complete{
    background: rgba(22, 163, 74, 0.11);
    border-color: rgba(22, 163, 74, 0.25);
    color: #15803d;
}

.incident-assignment-step-head span,
.incident-assignment-step-head small,
.incident-assignee-user span,
.incident-assignee-report,
.incident-assignment-empty{
    font-size: 12px;
    color: gray;
}

.incident-assignment-request{
    margin-bottom: 12px;
    padding: 10px;
    border-left: 3px solid var(--calendarBorder);
    white-space: pre-wrap;
    font-size: 13px;
}

.incident-assignee-list{
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.incident-assignee-row{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.incident-assignee-user,
.incident-assignee-actions{
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.incident-assignee-response-editor{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.incident-assignee-report{
    white-space: pre-wrap;
}

.incident-next-assignment{
    margin-top: 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.incident-history-header{
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--calendarBorder);
    margin-bottom: 16px;
}

.incident-history-kicker{
    color: gray;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.incident-history-header h3{
    font-size: 17px;
    font-weight: 700;
}

.incident-history-list{
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.incident-history-item{
    border-left: 3px solid var(--calendarBorder);
    background: var(--bg3);
    padding: 12px 14px;
}

.incident-history-meta{
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 10px;
}

.incident-history-meta strong{
    display: block;
    font-size: 13px;
    margin-bottom: 3px;
}

.incident-history-meta span,
.incident-history-change span,
.incident-history-note{
    color: gray;
    font-size: 11px;
}

.incident-history-unread-dot{
    display: inline-block;
    width: 6px;
    min-width: 6px;
    height: 6px;
    border-radius: 999px;
    background: tomato;
}

.incident-history-note{
    margin-bottom: 10px;
    white-space: pre-wrap;
}

.incident-history-changes{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.incident-history-change{
    display: grid;
    grid-template-columns: 130px minmax(0, 1fr);
    gap: 12px;
    font-size: 12px;
    line-height: 1.5;
}

.incident-history-change div{
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.incident-history-change del{
    color: gray;
    text-decoration: line-through;
    overflow-wrap: anywhere;
}

.incident-history-change ins{
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.incident-history-empty{
    color: gray;
    text-align: center;
    padding: 36px 0;
    font-size: 13px;
}

@media screen and (max-width: 959px) {
    .incident-detail-shell{
        grid-template-columns: 1fr;
    }

    .incident-detail-side{
        border-right: none;
        border-bottom: 1px solid var(--calendarBorder);
        padding-right: 0;
        padding-bottom: 16px;
    }

    .incident-detail-content{
        grid-template-columns: 1fr;
    }

    .incident-detail-assignment .incident-assignment-section{
        position: static;
    }

    .incident-ai-advice-head,
    .incident-ai-advice-meta{
        align-items: stretch;
        flex-direction: column;
    }

    .incident-ai-advice-version-head{
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 10px;
    }

    .incident-ai-advice-version-title{
        white-space: normal;
    }

    .incident-ai-advice-version-head > .incident-ai-advice-menu{
        grid-column: 3;
        grid-row: 1;
    }

    .incident-ai-advice-version-head > :deep(.user-panel),
    .incident-ai-advice-version-head > :deep(.user-panel-wrapper){
        grid-column: 2 / 4;
        grid-row: 2;
        justify-self: start;
    }

    .incident-people-grid,
    .incident-admin-grid{
        grid-template-columns: 1fr;
    }

    .incident-history-change{
        grid-template-columns: 1fr;
        gap: 4px;
    }
}
</style>
