<template>
    <Modal @close="emit('closeModal')" persist :loader="spinner < 1">        
        <template #title>
            <p style="font-size: 18px;">{{formatedDay}}の日報を{{ reportTitleAction }}</p>
        </template>
        <template #content>
            <div class="report-wrapper" style="background:inherit;">
                <div class="report-field registered-mode-field" v-if="isRegisteredStaff">
                    <p class="report-header">勤務区分</p>
                    <div class="attendance-mode-tabs">
                        <label
                            v-for="option in attendanceModeOptions"
                            :key="option.value"
                            class="attendance-mode-tab"
                            :class="{
                                'attendance-mode-tab-active': attendanceMode === option.value,
                                'attendance-mode-tab-disabled': isAttendanceModeOptionDisabled(option.value),
                            }"
                        >
                            <input name="attendanceMode" type="radio" v-model="attendanceMode" :value="option.value" :disabled="isLocked || isAttendanceModeOptionDisabled(option.value)">
                            <span>{{ option.label }}</span>
                        </label>
                    </div>
                </div>
                <div v-if="includesProjectTimeEntries" class="project-block-section">
                    <div class="project-time-header">
                        <p class="report-header">プロジェクト別入力</p>
                        <div class="project-time-summary">
                            <span v-if="includesWorkHours && projectTimeGapMinutes">勤怠範囲 {{ attendanceSpanTotalLabel }}</span>
                            <span v-if="projectTimeUnpaidGapSummary">{{ projectTimeUnpaidGapSummary }}</span>
                            <span v-if="projectTimeBreakSummary">{{ projectTimeBreakSummary }}</span>
                            <span v-if="includesWorkHours">就業 {{ workTimeTotalLabel }}</span>
                            <span v-if="showProjectWorkInputSummary">就業入力 {{ projectWorkTimeTotalLabel }}</span>
                            <span v-if="showCalculatedOvertimeSummary" class="project-time-summary-overtime">残業 {{ calculatedOvertimeLabel }}</span>
                            <span v-if="includesTrainingHours">研修 {{ projectTrainingTimeTotalLabel }}</span>
                        </div>
                    </div>
                    <div v-if="shift?.overtime_request" class="project-time-message">
                        ※申請した残業時間は<strong>{{ requestedOvertimeMinutes }}分</strong>です。退勤は1分単位で入力してください。
                        <span v-if="overtimeRequestProjectSummary">（{{ overtimeRequestProjectSummary }}）</span>
                    </div>
                    <div class="project-block-list">
                        <section
                            v-for="(entry, index) in projectTimeEntries"
                            :key="entry.key"
                                    class="project-block"
                            :class="{
                                'project-block-approved': isApprovedProjectEntry(entry),
                                'project-block-submitted': isSubmittedProjectEntry(entry),
                            }"
                        >
                            <div class="project-block-head">
                                <div class="flex items-center gap-1">
                                    <Project :size="16"/>
                                    <p class="project-block-title">
                                        {{ `プロジェクト ${index + 1}` }}
                                        <span v-if="item.position_id === 15" class="project-type-badge" :class="`project-type-badge-${entryType(entry)}`">{{ projectEntryTypeLabel(entry) }}</span>
                                        <span v-if="hasProjectEntryStatusBadge(entry)" class="project-approved-badge">{{ projectEntryStatusLabel(entry) }}</span>
                                    </p>
                                </div>
                                
                                <div class="op-button-container project-time-actions" v-if="!isProjectEntryLocked(entry)">
                                    <button type="button" class="project-time-action-button" title="プロジェクトを追加" aria-label="プロジェクトを追加" @click="addProjectTimeEntry(index)">＋</button>
                                    <button
                                        type="button"
                                        class="project-time-action-button"
                                        title="プロジェクトを削除"
                                        aria-label="プロジェクトを削除"
                                        :class="{ 'project-time-action-disabled': projectTimeEntries.length === 1 }"
                                        @click="removeProjectTimeEntry(index)"
                                    >−</button>
                                </div>
                            </div>
                            <div class="project-block-main">
                                <label class="project-input-group project-input-group-project">
                                    <span>プロジェクト</span>
                                    <select
                                        class="optionPicker project-time-project"
                                        v-model="entry.project_id"
                                        @change="handleProjectEntryProjectChange(entry)"
                                        :disabled="isProjectEntryLocked(entry)"
                                    >
                                        <option :value="null" disabled>プロジェクトを選択</option>
                                        <option v-for="group in workGroupAsOptions" :key="group.id" :value="group.id">{{ group.name }}</option>
                                    </select>
                                </label>
                                <label v-if="isRegisteredStaff" class="project-input-group project-input-group-type">
                                    <span>区分</span>
                                    <select
                                        v-if="canSelectProjectEntryType"
                                        class="optionPicker project-time-type"
                                        v-model="entry.segment_type"
                                        @change="normalizeProjectEntryForType(entry)"
                                        :disabled="isProjectEntryLocked(entry)"
                                    >
                                        <option v-for="option in projectSegmentTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                    </select>
                                    <strong v-else class="project-type-readonly">{{ projectEntryTypeLabel(entry) }}</strong>
                                </label>
                                <label class="project-input-group">
                                    <span>時間</span>
                                    <div class="project-time-range">
                                        <input
                                            :name="`projectStartTime-${index}`"
                                            class="taskDateTimePicker project-time-input"
                                            :class="{'clock-color' : theme.dark == true }"
                                            type="time"
                                            v-model="entry.start_time"
                                            :step="timeInputStep"
                                            :disabled="isProjectEntryLocked(entry)"
                                        >
                                        <div class="between-line">～</div>
                                        <input
                                            :name="`projectEndTime-${index}`"
                                            class="taskDateTimePicker project-time-input"
                                            :class="{'clock-color' : theme.dark == true }"
                                            type="time"
                                            v-model="entry.end_time"
                                            :step="timeInputStep"
                                            :disabled="isProjectEntryLocked(entry)"
                                        >
                                    </div>
                                </label>
                                <label v-if="isFirstWorkEntry(index)" class="project-input-group">
                                    <span>休憩</span>
                                    <select
                                        class="optionPicker project-break-select"
                                        v-model="breakTimeSelect"
                                        name="breakTimeSelect"
                                        :disabled="isLocked || hasLockedProjectEntry"
                                    >
                                        <option :key="breakIndex" v-for="(item , breakIndex) in breakTimeOptions" :value="item.value">{{ item.label }}</option>
                                    </select>
                                </label>
                                <div class="project-time-duration">
                                    <span>{{ projectEntryDurationTitle(entry) }}</span>
                                    <p>{{ projectTimeDurationLabel(entry, index) }}</p>
                                </div>
                            </div>

                            <div
                                v-if="isProjectDetailVisible(entry, 'expenses')"
                                class="project-detail"
                                :style="{ order: detailDisplayOrder(entry, 'expenses') }"
                            >
                                <div class="project-detail-head">
                                    <div class="flex items-center gap-1">
                                        <WorkReportIcon :size="16" which="expenses" />
                                        <p class="project-detail-title">経費</p>
                                    </div>
                                    <button v-if="!isProjectEntryLocked(entry)" type="button" class="project-time-action-button" title="経費を削除" aria-label="経費を削除" @click="removeProjectDetail(entry, 'expenses')">−</button>
                                </div>
                                <CostField
                                    v-for="costIndex in projectCostIndexes(entry)"
                                    :key="costs[costIndex].draft_uuid ?? costIndex"
                                    v-model:department="costs[costIndex].department"
                                    v-model:content="costs[costIndex].content"
                                    v-model:type="costs[costIndex].type"
                                    v-model:transport_type="costs[costIndex].transport_type"
                                    v-model:departure_place="costs[costIndex].departure_place"
                                    v-model:arrival_place="costs[costIndex].arrival_place"
                                    v-model:expenses="costs[costIndex].expenses"
                                    v-model:file_path="costs[costIndex].file_path"
                                    v-model:receipt_file_id="costs[costIndex].receipt_file_id"
                                    v-model:draft_uuid="costs[costIndex].draft_uuid"
                                    v-model:merchant_name="costs[costIndex].merchant_name"
                                    v-model:receipt_date="costs[costIndex].receipt_date"
                                    v-model:currency="costs[costIndex].currency"
                                    v-model:receipt_source_type="costs[costIndex].receipt_source_type"
                                    v-model:file_original_name="costs[costIndex].file_original_name"
                                    v-model:file_mime_type="costs[costIndex].file_mime_type"
                                    v-model:file_size_bytes="costs[costIndex].file_size_bytes"
                                    v-model:file_sha256="costs[costIndex].file_sha256"
                                    v-model:file_uploaded_at="costs[costIndex].file_uploaded_at"
                                    v-model:scan_dpi="costs[costIndex].scan_dpi"
                                    v-model:scan_color_depth="costs[costIndex].scan_color_depth"
                                    v-model:scan_color_mode="costs[costIndex].scan_color_mode"
                                    v-model:document_size="costs[costIndex].document_size"
                                    v-model:image_width_px="costs[costIndex].image_width_px"
                                    v-model:image_height_px="costs[costIndex].image_height_px"
                                    v-model:ocr_run_id="costs[costIndex].ocr_run_id"
                                    v-model:ocr_applied_fields="costs[costIndex].ocr_applied_fields"
                                    :workGroupAsOptions="workGroupAsOptions.map(ob => ob.name)"
                                    :fieldIndex="costIndex"
                                    :isRegistered="item.position_id === 15"
                                    :subjectUserId="item?.user_id"
                                    :timecardRecordId="timeCard?.id"
                                    :timecardCostRecordId="costs[costIndex].id"
                                    :locked="isProjectEntryLocked(entry)"
                                    :hideDepartment="true"
                                    @addCostField="addCostFieldForProject(entry)"
                                    @removeCostField="removeCostField"
                                    @removeFile="removeFile"
                                />
                            </div>

                            <div
                                v-if="isProjectDetailVisible(entry, 'mileage')"
                                class="project-detail"
                                :style="{ order: detailDisplayOrder(entry, 'mileage') }"
                            >
                                <div class="project-detail-head">
                                    <div class="flex items-center gap-1">
                                        <WorkReportIcon :size="16" which="mileage" />
                                        <p class="project-detail-title">マイカーの走行距離（往復）</p>
                                    </div>
                                    <button v-if="!isProjectEntryLocked(entry)" type="button" class="project-time-action-button" title="マイカーを削除" aria-label="マイカーを削除" @click="removeProjectDetail(entry, 'mileage')">−</button>
                                </div>
                                <div class="flex gap-4 items-center flex-wrap">
                                    <div class="relative w-fit">
                                        <input type="number" style="padding: 0px 40px 0 10px;height: 38px;border: 1px solid var(--primary-color);color: var(--primary-color);max-width: 100px;" name="work-mileage" v-model="entry.detail_values.mileage.mileage" min="0" :disabled="isProjectEntryLocked(entry)" @change="getProjectMyCarData(entry)" @blur="getProjectMyCarData(entry)">
                                        <span style="position: absolute; height: 100%; top: 0px; right: 5px; line-height: 38px;">km</span>
                                    </div>
                                </div>
                                <table v-if="entry.detail_values.mileage.mileage && entry.detail_values.mileage.gas_full_price" class="project-gas-table">
                                    <thead>
                                        <tr>
                                            <td>実燃費</td>
                                            <td>ガソリン単価</td>
                                            <td>ガソリン代</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ entry.detail_values.mileage.gas_consumption }}km/L</td>
                                            <td>{{ entry.detail_values.mileage.gas_unit_price }}円</td>
                                            <td>{{ entry.detail_values.mileage.gas_full_price }}円</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-if="isProjectDetailVisible(entry, 'actual') && projectForEntry(entry)?.has_actual_func"
                                id="performanceReport"
                                class="project-detail"
                                :style="{ order: detailDisplayOrder(entry, 'actual') }"
                            >
                                <div class="project-detail-head">
                                    <div class="flex items-center gap-1">
                                        <WorkReportIcon :size="16" which="actual" />
                                        <p class="project-detail-title">実績報告</p>
                                    </div>
                                    <button v-if="!isProjectEntryLocked(entry)" type="button" class="project-time-action-button" title="実績報告を削除" aria-label="実績報告を削除" @click="removeProjectDetail(entry, 'actual')">−</button>
                                </div>
                                <template v-if="actualStatusDefsForEntry(entry).length">
                                    <div v-for="statusDef in actualStatusDefsForEntry(entry)" :key="statusDef.label ?? statusDef.custom_label" class="mb-[16px]">
                                        <p class="text-[13px] font-medium mb-[8px]">{{ statusDef.label ?? statusDef.custom_label }}</p>
                                        <div
                                            v-for="(row, ri) in rowsForStatus(entry, statusDef.label ?? statusDef.custom_label)"
                                            :key="ri"
                                            class="flex flex-wrap items-center gap-[8px] mb-[8px]"
                                        >
                                            <template v-for="field in (statusDef.extra_fields ?? [])" :key="field.label">
                                                <select
                                                    v-if="field.type === 'select'"
                                                    v-model="row.meta[field.label]"
                                                    :disabled="isProjectEntryLocked(entry)"
                                                    class="project-inline-input"
                                                >
                                                    <option value="" style="color: gray;">{{ field.label }}</option>
                                                    <option v-for="opt in (field.options ?? [])" :key="opt" :value="opt" style="color: var(--primary-color);">{{ opt }}</option>
                                                </select>
                                                <input
                                                    v-else
                                                    type="text"
                                                    v-model="row.meta[field.label]"
                                                    :placeholder="field.label"
                                                    class="project-inline-input"
                                                    :disabled="isProjectEntryLocked(entry)"
                                                />
                                            </template>
                                            <input
                                                v-if="isYenActualEntry(entry)"
                                                name="actual_val"
                                                :placeholder="unitLabelForProject(projectForEntry(entry)) ? `実績値（${unitLabelForProject(projectForEntry(entry))}）` : '実績値'"
                                                type="text"
                                                inputmode="numeric"
                                                class="project-inline-number"
                                                :value="actualInputDisplayValue(row, entry)"
                                                @focus="focusActualInput(row)"
                                                @input="updateActualInputValue(row, $event)"
                                                @blur="blurActualInput"
                                                :disabled="isProjectEntryLocked(entry)"
                                            />
                                            <input
                                                v-else
                                                name="actual_val"
                                                :placeholder="unitLabelForProject(projectForEntry(entry)) ? `実績値（${unitLabelForProject(projectForEntry(entry))}）` : '実績値'"
                                                type="number"
                                                class="project-inline-number"
                                                v-model.number="row.value"
                                                :disabled="isProjectEntryLocked(entry)"
                                            />
                                            <button
                                                v-if="!isProjectEntryLocked(entry) && actualStatusCanRepeat(statusDef) && rowsForStatus(entry, statusDef.label ?? statusDef.custom_label).length > 1"
                                                type="button"
                                                class="project-time-action-button"
                                                title="行を削除"
                                                aria-label="行を削除"
                                                @click="removeActualRowForStatus(entry, statusDef.label ?? statusDef.custom_label, ri)"
                                            >−</button>
                                        </div>
                                        <button
                                            v-if="!isProjectEntryLocked(entry) && actualStatusCanRepeat(statusDef)"
                                            type="button"
                                            class="project-time-action-button project-detail-add"
                                            title="行を追加"
                                            aria-label="行を追加"
                                            @click="addActualRowForStatus(entry, statusDef)"
                                        >＋</button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex items-center gap-4"
                                        v-for="(row, actualIndex) in actualRowsForEntry(entry)"
                                        :key="row.status ?? actualIndex">
                                        <div v-if="row.status" class="min-w-[120px] text-sm">
                                            {{ row.status }}
                                        </div>
                                        <input
                                            v-if="isYenActualEntry(entry)"
                                            name="actual_val"
                                            :placeholder="unitLabelForProject(projectForEntry(entry)) ? `実績値（${unitLabelForProject(projectForEntry(entry))}）` : '実績値'"
                                            type="text"
                                            inputmode="numeric"
                                            class="project-inline-number"
                                            :value="actualInputDisplayValue(row, entry)"
                                            @focus="focusActualInput(row)"
                                            @input="updateActualInputValue(row, $event)"
                                            @blur="blurActualInput"
                                            :disabled="isProjectEntryLocked(entry)"
                                        />
                                        <input
                                            v-else
                                            name="actual_val"
                                            :placeholder="unitLabelForProject(projectForEntry(entry)) ? `実績値（${unitLabelForProject(projectForEntry(entry))}）` : '実績値'"
                                            type="number"
                                            class="project-inline-number"
                                            v-model.number="row.value"
                                            :disabled="isProjectEntryLocked(entry)"
                                        />
                                    </div>
                                </template>
                            </div>

                            <div
                                v-if="isProjectDetailVisible(entry, 'vehicle')"
                                class="project-detail"
                                :style="{ order: detailDisplayOrder(entry, 'vehicle') }"
                            >
                                <div class="project-detail-head">
                                    <div class="flex items-center gap-1">
                                        <WorkReportIcon :size="16" which="vehicle" />
                                        <p class="project-detail-title">運転業務</p>
                                    </div>
                                    <button v-if="!isProjectEntryLocked(entry)" type="button" class="project-time-action-button" title="運転業務を削除" aria-label="運転業務を削除" @click="removeProjectDetail(entry, 'vehicle')">−</button>
                                </div>
                                <VehicleField
                                    :key="`${entry.key}-vehicle`"
                                    v-model:vehicle="entry.detail_values.vehicle"
                                    :compact="true"
                                />
                            </div>

                            <template v-for="detail in customProjectDetails" :key="`${entry.key}-${detail.type}`">
                                <div
                                    v-if="isProjectDetailVisible(entry, detail.type)"
                                    class="project-detail"
                                    :style="{ order: detailDisplayOrder(entry, detail.type) }"
                                >
                                    <div class="project-detail-head">
                                        <div class="flex items-center gap-1">
                                            <WorkReportIcon :size="16" :which="detail.type" /> 
                                            <p class="project-detail-title">
                                                {{ detail.label }}
                                            </p>
                                        </div>
                                        
                                        <button
                                            v-if="!isLocked"
                                            type="button"
                                            class="project-time-action-button"
                                            :title="`${detail.label}を削除`"
                                            :aria-label="`${detail.label}を削除`"
                                            :class="{ 'project-time-action-disabled': !canRemoveProjectDetail(entry, detail.type) }"
                                            :disabled="isProjectEntryLocked(entry) || !canRemoveProjectDetail(entry, detail.type)"
                                            @click="!isProjectEntryLocked(entry) && canRemoveProjectDetail(entry, detail.type) && removeProjectDetail(entry, detail.type)"
                                        >−</button>
                                    </div>
                                    <p v-if="isProjectEntryLocked(entry)" class="project-locked-note">{{ projectLockNote(entry) }}</p>
                                    <template v-if="detail.type === 'incident'">
                                        <LongInput
                                            name="incidentText"
                                            placeHolder="インシデントの内容"
                                            v-model="entry.detail_values.incident"
                                            :disabled="isProjectEntryLocked(entry)"
                                        />
                                        <p class="project-time-message !mt-2">緊急性の高いものは即時に
                                            <router-link target="_blank" class="jump-link" :to="{ name: 'emergency_contact', query: { type: 'incident' } }">報告</router-link>
                                            してください</p>
                                    </template>
                                    <template v-else-if="detail.type === 'allowance'">
                                        <CustomField
                                            v-for="field in projectCustomFields(detail.fieldIds)"
                                            :key="field.id"
                                            :shift_type="shift?.shift_type"
                                            :data="field"
                                            :compact="true"
                                            :idPrefix="entry.key"
                                            v-model:fieldValue="entry.detail_values.allowance"
                                            v-model:vehicle="entry.detail_values.vehicle"
                                            ref="customFieldRef"
                                            :locked="isProjectEntryLocked(entry)"
                                        />
                                    </template>
                                    <template v-else-if="detail.type === 'comment'">
                                        <CustomField
                                            v-if="projectCommentField"
                                            :key="`${entry.key}-comment`"
                                            :shift_type="shift?.shift_type"
                                            :data="projectCommentField"
                                            :compact="true"
                                            :idPrefix="entry.key"
                                            v-model:fieldValue="entry.comment"
                                            v-model:vehicle="entry.detail_values.vehicle"
                                            :locked="isProjectEntryLocked(entry)"
                                        />
                                    </template>
                                    <template v-else>
                                        <CustomField
                                            v-for="field in projectCustomFields(detail.fieldIds)"
                                            :key="field.id"
                                            :shift_type="shift?.shift_type"
                                            :data="field"
                                            :compact="true"
                                            :idPrefix="entry.key"
                                            v-model:fieldValue="customValues[field.id]"
                                            v-model:vehicle="entry.detail_values.vehicle"
                                            ref="customFieldRef"
                                            :locked="isProjectEntryLocked(entry)"
                                        />
                                    </template>
                                </div>
                            </template>

                            <div
                                v-if="overtimeReasonField && isProjectDetailVisible(entry, 'overtime')"
                                class="project-detail"
                                :style="{ order: detailDisplayOrder(entry, 'overtime') }"
                            >
                                <div class="project-detail-head">
                                    <div class="flex items-center gap-1">
                                        <WorkReportIcon :size="16" which="comment" />
                                        <p class="project-detail-title">時間外業務内容</p>
                                    </div>
                                </div>
                                <p v-if="isProjectEntryLocked(entry)" class="project-locked-note">{{ projectLockNote(entry) }}</p>
                                <CustomField
                                    :key="`${entry.key}-overtime-reason`"
                                    :shift_type="shift?.shift_type"
                                    :data="overtimeReasonField"
                                    :compact="true"
                                    :idPrefix="entry.key"
                                    v-model:fieldValue="entry.detail_values.overtime"
                                    v-model:vehicle="entry.detail_values.vehicle"
                                    ref="customFieldRef"
                                    :locked="isProjectEntryLocked(entry)"
                                />
                            </div>

                            <div
                                v-if="!isProjectEntryLocked(entry) && availableProjectDetailOptions(entry).length"
                                class="project-extra-actions"
                            >
                                <button
                                    v-for="option in availableProjectDetailOptions(entry)"
                                    :key="option.type"
                                    type="button"
                                    class="project-extra-button"
                                    @click="addProjectDetail(entry, option.type)"
                                >
                                    <WorkReportIcon :size="16" :which="option.type" /> 
                                    <span>{{ option.label }}</span>
                                </button>
                            </div>
                        </section>
                    </div>
                    <p v-if="projectTimeUnpaidGapMessage" class="project-time-message">{{ projectTimeUnpaidGapMessage }}</p>
                    <p v-if="projectTimeBreakMessage" class="project-time-message">{{ projectTimeBreakMessage }}</p>
                    <p v-if="projectTimeWarning" class="project-time-warning">{{ projectTimeWarning }}</p>
                </div>
                <!-- <IncentiveField v-if="item.position_id === 15" v-model="incentives"/> -->
                <div id="saveButton" class="si-box" style="display: flex; justify-content: center; gap: 20px;">
                    <template v-if="!isLocked">
                        <LoaderButton style="margin: 0" :loading="saveLoading" content="一時保存" @triggered="saveTimeCard('save')" />
                        <LoaderButton style="margin: 0" :loading="applyLoading" content="申請する" @triggered="saveTimeCard('apply')" />
                    </template>
                    <p v-else style="margin: 0; font-size: 13px;">{{ reportLockMessage }}</p>
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup>
import { computed, onMounted, ref, reactive, watch, useTemplateRef } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useTheme } from '@/store/theme';
import CustomField from './CustomField.vue'
import CostField from './CostField.vue';
import VehicleField from './VehicleField.vue';
import LongInput from '../Form/LongInput.vue';
import WorkReportIcon from './WorkReportIcon.vue';
import { useAuthUserStore } from '../../store/auth';
import Modal from '../Global/Modal.vue';
import { DateTime } from 'luxon';
import { useDebouncedRef } from '@/utils/tools';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { getCustomFields, getWorkGroup } from '../../utils/workApi';
import { useTutorialStore } from '@/store/tutorial';
import { useTour } from '@/composables/useTour';
import Project from '../Icons/Project.vue';

    const ATTENDANCE_MODE = {
        WORK_ONLY: 'work_only',
        WORK_AND_TRAINING: 'work_and_training',
        TRAINING_ONLY: 'training_only',
    }
    const attendanceModeOptions = [
        { value: ATTENDANCE_MODE.WORK_ONLY, label: '就業' },
        { value: ATTENDANCE_MODE.WORK_AND_TRAINING, label: '就業 + 研修' },
        { value: ATTENDANCE_MODE.TRAINING_ONLY, label: '研修' },
    ]
    const PROJECT_SEGMENT_TYPE = {
        WORK: 'work',
        TRAINING: 'training',
    }
    const projectSegmentTypeOptions = [
        { value: PROJECT_SEGMENT_TYPE.WORK, label: '就業' },
        { value: PROJECT_SEGMENT_TYPE.TRAINING, label: '研修' },
    ]
    const auth = useAuthUserStore()
    const emit = defineEmits(['reload', 'closeModal'])
    const theme = useTheme()
    
    const props = defineProps([
        'chosenDate', 
        'todayStartTime', 
        'todayEndTime', 
        'todayBreakTime', 
        'customFieldData', 
        'createReport',
        'chosenUserId',
        'notSubmitted',
        'chosenDateShift',
        'item'
    ])
    const customFieldRef = useTemplateRef('customFieldRef')
    const workGroups = ref([])
    const fields = ref([])
    const spinner = ref(0)
    const shift = computed(() => {
        return props.item?.shift
    })
    const hasOvertimeRequest = computed(() => Boolean(shift.value?.overtime_request))
    const timeInputStep = computed(() => hasOvertimeRequest.value ? 60 : 900)
    const timeCard = computed(() => {
        return props.item?.time_card
    })
    const segmentTimeToMinutes = (time) => {
        if (!time) return null
        const [hours, minutes] = String(time).split(':').map(Number)
        if (Number.isNaN(hours) || Number.isNaN(minutes)) return null
        return hours * 60 + minutes
    }
    const segmentDurationMinutes = (segment) => {
        const start = segmentTimeToMinutes(segment?.start_time)
        const end = segmentTimeToMinutes(segment?.end_time)
        if (start === null || end === null) return 0
        return end >= start ? end - start : end + 1440 - start
    }
    const projectSegmentSortAnchor = (segments, startTime = timeCard.value?.start_time, endTime = timeCard.value?.end_time) => {
        const start = segmentTimeToMinutes(startTime)
        const end = segmentTimeToMinutes(endTime)
        if (start !== null && end !== null && start !== end) return start

        const starts = segments
            .map(segment => segmentTimeToMinutes(segment?.start_time))
            .filter(minutes => minutes !== null)
        const lateStarts = starts.filter(minutes => minutes >= 18 * 60)
        const hasEarlyMorningStart = starts.some(minutes => minutes < 5 * 60)

        return hasEarlyMorningStart && lateStarts.length ? Math.min(...lateStarts) : null
    }
    const projectSegmentStartOffset = (segment, anchor) => {
        const start = segmentTimeToMinutes(segment?.start_time)
        if (start === null) return Number.MAX_SAFE_INTEGER
        if (anchor === null) return start
        return start >= anchor ? start - anchor : start + 1440 - anchor
    }
    const sortProjectSegmentsByTime = (segments = [], startTime = timeCard.value?.start_time, endTime = timeCard.value?.end_time) => {
        const anchor = projectSegmentSortAnchor(segments, startTime, endTime)
        return [...segments].sort((first, second) => {
            const firstStart = projectSegmentStartOffset(first, anchor)
            const secondStart = projectSegmentStartOffset(second, anchor)
            if (firstStart !== secondStart) return firstStart - secondStart

            const firstEnd = firstStart + segmentDurationMinutes(first)
            const secondEnd = secondStart + segmentDurationMinutes(second)
            if (firstEnd !== secondEnd) return firstEnd - secondEnd

            return Number(first?.project_id ?? 0) - Number(second?.project_id ?? 0)
        })
    }
    const isRegisteredStaff = computed(() => Number(props.item?.position_id) === 15)
    const canEditReport = computed(() => Boolean(
        props.item?.ability?.daily_report_create
        || props.item?.ability?.daily_report_modify
    ))
    const isApprovedReport = computed(() => Number(timeCard.value?.status_flag) === 2)
    const isLocked = computed(() => isApprovedReport.value || !canEditReport.value)
    const reportLockMessage = computed(() => {
        return isApprovedReport.value ? '承認済みの日報は編集できません。' : 'この日報は表示のみです。'
    })
    const reportTitleAction = computed(() => {
        if (isLocked.value && timeCard.value) return '表示する'
        if (timeCard.value) return '編集する'
        return '作成する'
    })
    const buildVehicleData = (vehicle = {}) => ({
        id: vehicle?.id ?? null,
        vehicle: vehicle?.vehicle ?? null,
        alcohol_before_time: vehicle?.alcohol_before_time ?? null,
        alcohol_after_time: vehicle?.alcohol_after_time ?? null,
        alcohol_before_value: vehicle?.alcohol_before_value ?? null,
        alcohol_after_value: vehicle?.alcohol_after_value ?? null,
        before_user: vehicle?.before_user ?? null,
        after_user: vehicle?.after_user ?? null,
        confirm_before_user: vehicle?.confirm_before_user ?? null,
        confirm_after_user: vehicle?.confirm_after_user ?? null,
    })
    const mergeVehicleData = (primary = {}, fallback = {}) => buildVehicleData({
        id: primary?.id ?? fallback?.id ?? null,
        vehicle: primary?.vehicle ?? fallback?.vehicle ?? null,
        alcohol_before_time: primary?.alcohol_before_time ?? fallback?.alcohol_before_time ?? null,
        alcohol_after_time: primary?.alcohol_after_time ?? fallback?.alcohol_after_time ?? null,
        alcohol_before_value: primary?.alcohol_before_value ?? fallback?.alcohol_before_value ?? null,
        alcohol_after_value: primary?.alcohol_after_value ?? fallback?.alcohol_after_value ?? null,
        before_user: primary?.before_user ?? fallback?.before_user ?? null,
        after_user: primary?.after_user ?? fallback?.after_user ?? null,
        confirm_before_user: primary?.confirm_before_user ?? fallback?.confirm_before_user ?? null,
        confirm_after_user: primary?.confirm_after_user ?? fallback?.confirm_after_user ?? null,
    })
    const workGroupAsOptions = computed(() => {
        let filteredgroups
        let mappedgroups
        if (auth.isAdmin) {
            filteredgroups = workGroups.value
            .filter(group => 
                group.members.some(member => member.id === props.item.user_id) || 
                group.manager?.some(manager => manager.id === props.item.user_id)
            )            
            mappedgroups = filteredgroups.map(group => group.name);

        } else {
            filteredgroups = workGroups.value
            mappedgroups = workGroups.value.map(group => group.name)
        }
        return filteredgroups
    })
    const costs = reactive([])
    const incentives = ref(timeCard.value?.timecard_incentives?.length ? timeCard.value.timecard_incentives : [
        {
            count: null,
            file: null,
        }
    ])
    const loading = ref([false, false])
    const saveLoading = ref(false)
    const applyLoading = ref(false)
    const editStartTime = ref(timeCard.value?.start_time ? timeCard.value.start_time : shift.value?.start_time ? shift.value.start_time : '09:00:00')
    const editEndTime = ref(timeCard.value?.end_time ? timeCard.value.end_time : shift.value?.end_time ? shift.value.end_time : '18:00:00')
    const trainingStartTime = ref(timeCard.value?.training_start_time ? timeCard.value.training_start_time : null)
    const trainingEndTime = ref(timeCard.value?.training_end_time ? timeCard.value.training_end_time : null)
    const breakTimeOptions = ref([{label : 'なし' , value : 0 },
                        {label : '30分' , value : 30 },
                        {label : '45分' , value : 45 },
                        {label : '60分' , value : 60 },
                        {label : '90分' , value : 90 }])
    const breakTimeSelect = ref(timeCard.value?.break_time ? timeCard.value.break_time : 0)
    const customValues = ref({})
    const todayWorkGroup = ref(timeCard.value?.work_group_id ?? shift.value?.department_id ?? '')
    const selectedProject = ref(timeCard.value?.department ?? shift.value?.department ?? {})
    const car_used_project = ref(timeCard.value?.car_used_project ?? todayWorkGroup.value ?? '')
    const car_mileage = useDebouncedRef('')
    const car_data = ref({})
    const projectDetailOptions = [
        { type: 'expenses', label: '経費' },
        { type: 'mileage', label: 'マイカー' },
        { type: 'allowance', label: '諸手当' },
        { type: 'vehicle', label: '運転業務' },
        { type: 'incident', label: 'インシデント' },
        { type: 'comment', label: 'コメント' },
        { type: 'actual', label: '実績報告' },
    ]
    const customProjectDetails = [
        { type: 'allowance', label: '諸手当', fieldIds: [37] },
        { type: 'incident', label: 'インシデント', fieldIds: [40] },
        { type: 'comment', label: 'コメント', fieldIds: [39] },
    ]
    let projectTimeEntryKey = 0
    const buildProjectDetailValues = (values = {}) => ({
        allowance: Array.isArray(values?.allowance) ? [...values.allowance] : [],
        allowance_labels: Array.isArray(values?.allowance_labels) ? [...values.allowance_labels] : [],
        incident: values?.incident ?? '',
        overtime: values?.overtime ?? '',
        mileage: {
            mileage: values?.mileage?.mileage ?? '',
            gas_full_price: values?.mileage?.gas_full_price ?? 0,
            gas_consumption: values?.mileage?.gas_consumption ?? null,
            gas_unit_price: values?.mileage?.gas_unit_price ?? null,
        },
        vehicle: buildVehicleData(values?.vehicle ?? {}),
    })
    const buildProjectTimeEntry = (entry = {}) => ({
        key: `project-time-${projectTimeEntryKey++}`,
        id: entry.id ?? null,
        project_id: entry.project_id ?? entry.work_group_id ?? entry.project?.id ?? null,
        previous_project_id: entry.previous_project_id ?? entry.project_id ?? entry.work_group_id ?? entry.project?.id ?? null,
        segment_type: entry.segment_type ?? PROJECT_SEGMENT_TYPE.WORK,
        start_time: entry.start_time ?? null,
        end_time: entry.end_time ?? null,
        details: Array.isArray(entry.details) ? [...entry.details] : [],
        detail_values: buildProjectDetailValues(entry.detail_values ?? {}),
        comment: entry.comment ?? '',
        status: entry.status ?? null,
        project: entry.project ?? null,
    })
    const vehicleRecordForProjectSegment = (segment) => {
        const records = Array.isArray(timeCard.value?.vehicle_records) ? timeCard.value.vehicle_records : []
        return records.find(record => Number(record?.timecard_project_segment_id) === Number(segment?.id))
            ?? records.find(record => Number(record?.project_id) === Number(segment?.project_id) && Number(record?.vehicle) === Number(segment?.detail_values?.vehicle?.vehicle))
            ?? null
    }
    const detailValuesForProjectSegment = (segment) => {
        const detailValues = buildProjectDetailValues(segment?.detail_values ?? {})
        const vehicleRecord = vehicleRecordForProjectSegment(segment)
        if (Array.isArray(segment?.details) && segment.details.includes('vehicle') && vehicleRecord) {
            detailValues.vehicle = mergeVehicleData(detailValues.vehicle, vehicleRecord)
        }

        return detailValues
    }
    const projectEntryDetailsKey = (entry) => JSON.stringify([...(entry.details ?? [])].sort())
    const projectEntryDetailValuesKey = (entry) => JSON.stringify(entry.detail_values ?? {})
    const mergedProjectEntryComment = (firstComment, secondComment) => {
        return [...new Set([firstComment, secondComment].map(value => String(value ?? '').trim()).filter(Boolean))].join('\n')
    }
    const mergeContinuousSameProjectEntries = (entries) => {
        return entries.reduce((mergedEntries, entry) => {
            const previousEntry = mergedEntries[mergedEntries.length - 1]
            const canMerge = previousEntry
                && previousEntry.project_id
                && entry.project_id
                && Number(previousEntry.project_id) === Number(entry.project_id)
                && previousEntry.segment_type === entry.segment_type
                && previousEntry.status === entry.status
                && previousEntry.end_time === entry.start_time
                && projectEntryDetailsKey(previousEntry) === projectEntryDetailsKey(entry)
                && projectEntryDetailValuesKey(previousEntry) === projectEntryDetailValuesKey(entry)

            if (canMerge) {
                previousEntry.end_time = entry.end_time
                previousEntry.comment = mergedProjectEntryComment(previousEntry.comment, entry.comment)
                return mergedEntries
            }

            mergedEntries.push(entry)
            return mergedEntries
        }, [])
    }
    const projectTimeEntries = ref([
        buildProjectTimeEntry({
            project_id: todayWorkGroup.value || null,
            start_time: editStartTime.value,
            end_time: editEndTime.value,
            details: ['comment'],
        })
    ])
    const actualRows = ref([
        { status: null, value: null, meta: {} },
    ])
    const hasActualValue = (value) => value !== null && value !== undefined && value !== ''
    const entryType = (entry) => entry?.segment_type === PROJECT_SEGMENT_TYPE.TRAINING
        ? PROJECT_SEGMENT_TYPE.TRAINING
        : PROJECT_SEGMENT_TYPE.WORK
    const isWorkProjectEntry = (entry) => entryType(entry) === PROJECT_SEGMENT_TYPE.WORK
    const isTrainingProjectEntry = (entry) => entryType(entry) === PROJECT_SEGMENT_TYPE.TRAINING
    const projectEntryTypeLabel = (entry) => isTrainingProjectEntry(entry) ? '研修' : '就業'
    const projectEntryDurationTitle = (entry) => isTrainingProjectEntry(entry) ? '研修' : '実働'
    const workProjectEntries = computed(() => projectTimeEntries.value.filter(isWorkProjectEntry))
    const trainingProjectEntries = computed(() => projectTimeEntries.value.filter(isTrainingProjectEntry))
    const canSelectProjectEntryType = computed(() => isRegisteredStaff.value && attendanceMode.value === ATTENDANCE_MODE.WORK_AND_TRAINING)
    const isFirstWorkEntry = (index) => {
        return projectTimeEntries.value.findIndex(isWorkProjectEntry) === index
    }
    const replaceProjectEntryDetails = (entry, details) => {
        const nextDetails = [...new Set(details)]
        const currentDetails = Array.isArray(entry.details) ? entry.details : []
        if (currentDetails.length === nextDetails.length && currentDetails.every((detail, index) => detail === nextDetails[index])) {
            return
        }
        entry.details = nextDetails
    }
    const normalizeProjectEntryForType = (entry) => {
        const nextType = attendanceMode.value === ATTENDANCE_MODE.WORK_ONLY
            ? PROJECT_SEGMENT_TYPE.WORK
            : attendanceMode.value === ATTENDANCE_MODE.TRAINING_ONLY
                ? PROJECT_SEGMENT_TYPE.TRAINING
                : entryType(entry)

        if (entry.segment_type !== nextType) {
            entry.segment_type = nextType
        }

        const currentDetails = Array.isArray(entry.details) ? entry.details : []
        if (isTrainingProjectEntry(entry)) {
            const trainingDetails = currentDetails.filter(detail => detail === 'comment')
            if (!trainingDetails.includes('comment')) {
                trainingDetails.push('comment')
            }
            replaceProjectEntryDetails(entry, trainingDetails)
        }
    }
    const normalizeProjectEntriesForMode = () => {
        projectTimeEntries.value.forEach(normalizeProjectEntryForType)
    }
    const setRefIfChanged = (target, value) => {
        if (target.value !== value) {
            target.value = value
        }
    }
    const firstTrainingEntry = computed(() => trainingProjectEntries.value[0] ?? null)
    const lastTrainingEntryWithEnd = computed(() => [...trainingProjectEntries.value].reverse().find(entry => entry.end_time) ?? null)
    const derivedTrainingStartTime = computed(() => firstTrainingEntry.value?.start_time ?? null)
    const derivedTrainingEndTime = computed(() => lastTrainingEntryWithEnd.value?.end_time ?? null)
    const addProjectTimeEntry = (index = projectTimeEntries.value.length - 1) => {
        const currentEntry = projectTimeEntries.value[index]
        const insertIndex = index + 1
        projectTimeEntries.value.splice(insertIndex, 0, buildProjectTimeEntry({
            project_id: currentEntry?.project_id ?? todayWorkGroup.value ?? null,
            segment_type: currentEntry?.segment_type ?? (includesWorkHours.value ? PROJECT_SEGMENT_TYPE.WORK : PROJECT_SEGMENT_TYPE.TRAINING),
            start_time: currentEntry?.end_time ?? null,
            end_time: null,
        }))
    }
    const removeProjectTimeEntry = (index) => {
        const entry = projectTimeEntries.value[index]
        if (projectTimeEntries.value.length > 1 && !isProjectEntryLocked(entry)) {
            clearRemovedProjectEntryDetails(entry)
            projectTimeEntries.value.splice(index, 1)
        }
    }
    const PROJECT_ENTRY_LOCKED_STATUSES = ['submitted', 'approved']
    const projectEntryStatusLabel = (entry) => {
        const labels = {
            draft: '作成中',
            submitted: '申請中',
            approved: '承認済',
            rejected: '差戻',
        }
        return labels[entry?.status] ?? ''
    }
    const isApprovedProjectEntry = (entry) => entry?.status === 'approved'
    const isSubmittedProjectEntry = (entry) => entry?.status === 'submitted'
    const hasProjectEntryStatusBadge = (entry) => PROJECT_ENTRY_LOCKED_STATUSES.includes(entry?.status)
    const isProjectEntryStatusLocked = (entry) => {
        if (isApprovedProjectEntry(entry)) return true
        if (isSubmittedProjectEntry(entry)) return !auth.isAdmin
        return false
    }
    const hasLockedProjectEntry = computed(() => projectTimeEntries.value.some(entry => isProjectEntryStatusLocked(entry)))
    const isProjectEntryLocked = (entry) => isLocked.value || isProjectEntryStatusLocked(entry)
    const projectLockNote = (entry) => {
        if (isApprovedProjectEntry(entry)) return '承認済みのプロジェクトは編集できません。'
        if (isSubmittedProjectEntry(entry)) return '申請中のプロジェクトは編集できません。差戻されたプロジェクトのみ修正してください。'
        return reportLockMessage.value
    }
    const projectName = (projectId) => {
        return projectForId(projectId)?.name ?? ''
    }
    const projectIdFromDepartment = (department) => {
        if (!department) return null
        return workGroupAsOptions.value.find(group => group.name === department)?.id ?? null
    }
    const costProjectId = (cost) => {
        const projectId = Number(cost?.project_id ?? 0)
        if (Number.isFinite(projectId) && projectId > 0) return projectId

        const fallbackProjectId = Number(projectIdFromDepartment(cost?.department) ?? 0)
        return Number.isFinite(fallbackProjectId) && fallbackProjectId > 0 ? fallbackProjectId : null
    }
    const costBelongsToProject = (cost, projectId) => {
        const targetProjectId = Number(projectId)
        const ownedProjectId = costProjectId(cost)
        if (ownedProjectId !== null) {
            return ownedProjectId === targetProjectId
        }

        const name = projectName(projectId)
        return Boolean(name && cost?.department === name)
    }
    const projectSegmentKeyTime = (time) => {
        if (!time) return null
        const [hours, minutes] = String(time).split(':')
        return `${hours}:${minutes}`
    }
    const projectSegmentMatchKey = (entry) => {
        return [
            Number(entry?.project_id ?? 0),
            entryType(entry),
            projectSegmentKeyTime(entry?.start_time),
            projectSegmentKeyTime(entry?.end_time),
        ].join('|')
    }
    const firstEntryForProject = (entry, entries = projectTimeEntries.value) => {
        return entries.find(item => Number(item?.project_id ?? 0) === Number(entry?.project_id ?? 0))
    }
    const costBelongsToProjectEntry = (cost, entry, entries = projectTimeEntries.value) => {
        if (!entry?.project_id) return false

        if (cost?.project_entry_key) {
            return cost.project_entry_key === entry.key
        }

        const segmentId = Number(cost?.timecard_project_segment_id ?? 0)
        if (segmentId > 0) {
            return Number(entry.id ?? 0) === segmentId
        }

        return firstEntryForProject(entry, entries)?.key === entry.key && costBelongsToProject(cost, entry.project_id)
    }
    const projectForId = (projectId) => {
        return workGroupAsOptions.value.find(group => Number(group.id) === Number(projectId))
            ?? (Number(selectedProject.value?.id) === Number(projectId) ? selectedProject.value : null)
    }
    const projectForEntry = (entry) => {
        const project = projectForId(entry?.project_id)
        if (!project) return entry?.project ?? null
        if (!entry?.project) return project

        return {
            ...entry.project,
            ...project,
            has_actual_func: project.has_actual_func ?? entry.project.has_actual_func,
            actual_statuses: project.actual_statuses ?? entry.project.actual_statuses,
        }
    }
    const isMeaningfulCost = (cost) => {
        return Boolean(cost?.content || cost?.expenses || cost?.file_path || cost?.merchant_name || cost?.departure_place || cost?.arrival_place)
    }
    const projectCostIndexes = (entry) => {
        if (!entry?.project_id) return []
        return costs
            .map((cost, index) => costBelongsToProjectEntry(cost, entry) ? index : -1)
            .filter(index => index !== -1)
    }
    const isProjectDetailVisible = (entry, type) => {
        return Array.isArray(entry?.details) && entry.details.includes(type)
    }
    const ensureProjectDetailValues = (entry) => {
        if (!entry.detail_values) {
            entry.detail_values = buildProjectDetailValues()
        }
        if (!Array.isArray(entry.detail_values.allowance)) {
            entry.detail_values.allowance = []
        }
        if (!Array.isArray(entry.detail_values.allowance_labels)) {
            entry.detail_values.allowance_labels = []
        }
        if (!entry.detail_values.mileage || typeof entry.detail_values.mileage !== 'object') {
            entry.detail_values.mileage = buildProjectDetailValues().mileage
        }
        if (!entry.detail_values.vehicle || typeof entry.detail_values.vehicle !== 'object') {
            entry.detail_values.vehicle = buildVehicleData()
        }
        if (entry.detail_values.incident === undefined || entry.detail_values.incident === null) {
            entry.detail_values.incident = ''
        }

        return entry.detail_values
    }
    const allowanceParts = () => {
        return fields.value.find(field => Number(field.id) === 37)?.custom_field_parts_records ?? []
    }
    const allowancePartValue = (part) => Number(part?.value_int ?? part?.parts_value ?? part?.id)
    const allowancePartLabel = (part) => part?.parts_lavel ?? part?.label ?? part?.name ?? part?.title ?? part?.parts_name ?? ''
    const allowanceSubParts = (part) => Array.isArray(part?.sub_parts) ? part.sub_parts : []
    const normalizeAllowanceValues = (values) => {
        if (!Array.isArray(values)) return []

        const selectedValues = values
            .filter(value => value !== null && value !== undefined && value !== '')
            .map(value => Number(value))
            .filter(Number.isFinite)

        if (!selectedValues.length) return []

        const selectedSet = new Set(selectedValues)
        const parts = allowanceParts()
        if (!parts.length) return [...selectedSet]

        const normalized = []
        parts.forEach(part => {
            const parentValue = allowancePartValue(part)
            if (!selectedSet.has(parentValue)) return

            normalized.push(parentValue)
            allowanceSubParts(part).forEach(subPart => {
                const subPartValue = allowancePartValue(subPart)
                if (selectedSet.has(subPartValue)) {
                    normalized.push(subPartValue)
                }
            })
        })

        return [...new Set(normalized)]
    }
    const allowanceLabelForValue = (value) => {
        const numericValue = Number(value)
        for (const part of allowanceParts()) {
            if (allowancePartValue(part) === numericValue) {
                return allowancePartLabel(part) || String(value)
            }

            const subPart = allowanceSubParts(part).find(child => allowancePartValue(child) === numericValue)
            if (subPart) {
                return allowancePartLabel(subPart) || String(value)
            }
        }

        return String(value)
    }
    const allowanceLabelsForValues = (values) => {
        return normalizeAllowanceValues(values).map(allowanceLabelForValue)
    }
    const projectMileage = (entry) => ensureProjectDetailValues(entry).mileage
    const detailDisplayOrder = (entry, type) => {
        const index = Array.isArray(entry?.details) ? entry.details.indexOf(type) : -1
        return index === -1 ? 500 : 10 + index
    }
    const commentDetailCount = computed(() => {
        return projectTimeEntries.value.filter(entry => isProjectDetailVisible(entry, 'comment')).length
    })
    const canRemoveProjectDetail = (entry, type) => {
        if (type !== 'comment') return true
        return commentDetailCount.value > 1
    }
    const availableProjectDetailOptions = (entry) => {
        if (isTrainingProjectEntry(entry)) {
            return projectDetailOptions.filter(option => option.type === 'comment' && !isProjectDetailVisible(entry, option.type))
        }

        return projectDetailOptions.filter(option => {
            if (option.type === 'actual' && !projectForEntry(entry)?.has_actual_func) return false
            return !isProjectDetailVisible(entry, option.type)
        })
    }
    const updateProjectEntryDetails = (entry, updater) => {
        const entryIndex = projectTimeEntries.value.findIndex(item => item.key === entry.key)
        if (entryIndex === -1) return
        const current = projectTimeEntries.value[entryIndex]
        const nextDetails = updater(Array.isArray(current.details) ? current.details : [])
        projectTimeEntries.value.splice(entryIndex, 1, {
            ...current,
            details: [...new Set(nextDetails)],
        })
    }
    const addProjectDetail = (entry, type) => {
        ensureProjectDetailValues(entry)
        updateProjectEntryDetails(entry, details => [...details, type])
        if (type === 'expenses' && projectCostIndexes(entry).length === 0) {
            addCostFieldForProject(entry)
        }
        if (type === 'mileage') {
            if (!car_used_project.value) {
                car_used_project.value = entry.project_id
            }
        }
        if (type === 'vehicle') {
            customValues.value[44] = 1
        }
        if (type === 'actual') {
            ensureActualRowsForProject(entry)
        }
    }
    const syncProjectDetailDefaults = (entry) => {
        if (isProjectDetailVisible(entry, 'expenses') && projectCostIndexes(entry).length === 0) {
            addCostFieldForProject(entry)
        }
        if (isProjectDetailVisible(entry, 'actual')) {
            ensureActualRowsForProject(entry)
        }
    }
    const costHasProjectEntryOwner = (cost) => {
        return Boolean(cost?.project_entry_key) || Number(cost?.timecard_project_segment_id ?? 0) > 0
    }
    const costBelongsExactlyToProjectEntry = (cost, entry) => {
        if (!entry) return false

        if (cost?.project_entry_key) {
            return cost.project_entry_key === entry.key
        }

        const segmentId = Number(cost?.timecard_project_segment_id ?? 0)
        return segmentId > 0 && Number(entry.id ?? 0) === segmentId
    }
    const removeCostsForProjectId = (projectId) => {
        if (!projectId) return

        for (let index = costs.length - 1; index >= 0; index--) {
            if (costBelongsToProject(costs[index], projectId)) {
                costs.splice(index, 1)
            }
        }
    }
    const removeCostsForProjectEntry = (entry, fallbackProjectId = entry?.project_id) => {
        if (!entry && !fallbackProjectId) return

        const fallbackEntry = entry ? { ...entry, project_id: fallbackProjectId } : null
        const shouldRemoveProjectFallback = fallbackProjectId && !hasOtherProjectDetail(fallbackProjectId, 'expenses', entry)
        for (let index = costs.length - 1; index >= 0; index--) {
            const cost = costs[index]
            const exactMatch = costBelongsExactlyToProjectEntry(cost, entry)
            const fallbackMatch = !costHasProjectEntryOwner(cost)
                && (costBelongsToProjectEntry(cost, fallbackEntry) || (shouldRemoveProjectFallback && costBelongsToProject(cost, fallbackProjectId)))
            if (exactMatch || fallbackMatch) {
                costs.splice(index, 1)
            }
        }
    }
    const removeActualRowsForProjectId = (projectId) => {
        actualRows.value = actualRows.value.filter(row => Number(row.project_id) !== Number(projectId))
    }
    const hasOtherProjectDetail = (projectId, type, ignoredEntry = null) => {
        return projectTimeEntries.value.some(projectEntry => {
            return projectEntry !== ignoredEntry
                && Number(projectEntry?.project_id) === Number(projectId)
                && isProjectDetailVisible(projectEntry, type)
        })
    }
    const clearRemovedProjectEntryDetails = (entry) => {
        const projectId = entry?.project_id
        if (!projectId) return

        if (isProjectDetailVisible(entry, 'expenses')) {
            removeCostsForProjectEntry(entry)
        }
        if (isProjectDetailVisible(entry, 'actual') && !hasOtherProjectDetail(projectId, 'actual', entry)) {
            removeActualRowsForProjectId(projectId)
        }
    }
    const clearProjectOwnedDetailsAfterProjectChange = (entry, previousProjectId) => {
        removeCostsForProjectEntry(entry, previousProjectId)
        if (!hasOtherProjectDetail(previousProjectId, 'actual', entry)) {
            removeActualRowsForProjectId(previousProjectId)
        }
        replaceProjectEntryDetails(entry, (Array.isArray(entry.details) ? entry.details : []).filter(detail => {
            return !['expenses', 'actual', 'mileage', 'vehicle'].includes(detail)
        }))

        const detailValues = ensureProjectDetailValues(entry)
        detailValues.mileage = buildProjectDetailValues().mileage
        detailValues.vehicle = buildVehicleData()
    }
    const handleProjectEntryProjectChange = (entry) => {
        const previousProjectId = entry.previous_project_id ?? null
        const nextProjectId = entry.project_id ?? null

        if (previousProjectId && Number(previousProjectId) !== Number(nextProjectId)) {
            clearProjectOwnedDetailsAfterProjectChange(entry, previousProjectId)
        }

        entry.previous_project_id = nextProjectId
        syncProjectDetailDefaults(entry)
    }
    const removeProjectDetail = (entry, type) => {
        if (type === 'comment') {
            entry.comment = ''
        }
        updateProjectEntryDetails(entry, details => details.filter(detail => detail !== type))
        const stillVisible = projectTimeEntries.value.some(projectEntry => isProjectDetailVisible(projectEntry, type))

        if (type === 'expenses') {
            removeCostsForProjectEntry(entry)
        }
        if (type === 'mileage') {
            ensureProjectDetailValues(entry).mileage = buildProjectDetailValues().mileage
        }
        if (type === 'vehicle') {
            ensureProjectDetailValues(entry).vehicle = buildVehicleData()
        }
        if (type === 'allowance') {
            ensureProjectDetailValues(entry).allowance = []
            ensureProjectDetailValues(entry).allowance_labels = []
        }
        if (type === 'incident') {
            ensureProjectDetailValues(entry).incident = ''
        }
        if (!stillVisible && type === 'comment') {
            customValues.value[39] = ''
        }
        if (!stillVisible && type === 'vehicle') {
            customValues.value[44] = 0
        }
        if (type === 'actual') {
            const projectId = Number(entry?.project_id)
            const hasSameProjectActual = projectTimeEntries.value.some(projectEntry => {
                return Number(projectEntry?.project_id) === projectId && isProjectDetailVisible(projectEntry, 'actual')
            })
            if (!hasSameProjectActual) {
                removeActualRowsForProjectId(projectId)
            }
        }
    }
    const projectCustomFields = (fieldIds) => {
        return filterCustomValues.value.filter(field => fieldIds.includes(field.id))
    }
    const overtimeRequestProjectSegments = computed(() => {
        const request = shift.value?.overtime_request
        const segments = Array.isArray(request?.project_segments) ? request.project_segments : []
        const normalized = segments
            .map(segment => ({
                project_id: Number(segment?.project_id ?? 0),
                minutes: Math.max(0, Number(segment?.minutes ?? 0)),
                content: String(segment?.content ?? '').trim(),
            }))
            .filter(segment => segment.project_id > 0 && segment.minutes > 0)

        if (normalized.length || !request?.minutes || !shift.value?.department_id) {
            return normalized
        }

        return [{
            project_id: Number(shift.value.department_id),
            minutes: Number(request.minutes),
            content: String(request?.content ?? '').trim(),
        }]
    })
    const requestedOvertimeMinutes = computed(() => {
        if (overtimeRequestProjectSegments.value.length) {
            return overtimeRequestProjectSegments.value.reduce((total, segment) => total + Number(segment.minutes || 0), 0)
        }

        return Number(shift.value?.overtime_request?.minutes || 0)
    })
    const overtimeRequestContentSummary = computed(() => {
        const segmentContents = overtimeRequestProjectSegments.value
            .map(segment => String(segment.content ?? '').trim())
            .filter(Boolean)

        if (segmentContents.length) {
            return [...new Set(segmentContents)].join("\n")
        }

        return String(shift.value?.overtime_request?.content ?? '').trim()
    })
    const overtimeRequestProjectSummary = computed(() => {
        return overtimeRequestProjectSegments.value
            .map(segment => {
                const projectName = projectForId(segment.project_id)?.name ?? 'プロジェクト'
                return `${projectName} ${formatProjectTimeMinutes(segment.minutes)}`
            })
            .join(' / ')
    })
    const addMinutesToTime = (time, minutes) => {
        if (!time) return null

        const [hour, minute] = String(time).split(':').map(Number)
        if (Number.isNaN(hour) || Number.isNaN(minute)) return null

        const totalMinutes = (((hour * 60) + minute + minutes) % 1440 + 1440) % 1440
        return `${String(Math.floor(totalMinutes / 60)).padStart(2, '0')}:${String(totalMinutes % 60).padStart(2, '0')}`
    }
    const projectEntriesFromOvertimeRequest = () => {
        if (timeCard.value || !hasOvertimeRequest.value || !overtimeRequestProjectSegments.value.length || !includesWorkHours.value) {
            return []
        }

        const shiftStart = formatTime(shift.value?.start_time ?? editStartTime.value)
        const shiftEnd = formatTime(shift.value?.end_time ?? editEndTime.value)
        if (!shiftStart || !shiftEnd) return []

        const entries = []
        const baseProjectId = shift.value?.department_id
            ?? overtimeRequestProjectSegments.value[0]?.project_id
            ?? todayWorkGroup.value
            ?? workGroupAsOptions.value[0]?.id
            ?? null
        if (baseProjectId) {
            entries.push(buildProjectTimeEntry({
                project_id: baseProjectId,
                segment_type: PROJECT_SEGMENT_TYPE.WORK,
                start_time: shiftStart,
                end_time: shiftEnd,
                details: ['comment'],
            }))
        }

        const appendOvertimeEntry = (segment, startTime, endTime) => {
            const previousEntry = entries[entries.length - 1]
            const isSameContinuousProject = previousEntry
                && Number(previousEntry.project_id) === Number(segment.project_id)
                && previousEntry.segment_type === PROJECT_SEGMENT_TYPE.WORK
                && previousEntry.end_time === startTime

            if (isSameContinuousProject) {
                previousEntry.end_time = endTime
                if (!String(previousEntry.comment ?? '').trim() && segment.content) {
                    previousEntry.comment = segment.content
                }
                return
            }

            entries.push(buildProjectTimeEntry({
                project_id: segment.project_id,
                segment_type: PROJECT_SEGMENT_TYPE.WORK,
                start_time: startTime,
                end_time: endTime,
                details: ['comment'],
                comment: segment.content ?? '',
            }))
        }

        let cursor = shiftEnd
        overtimeRequestProjectSegments.value.forEach(segment => {
            const nextEnd = addMinutesToTime(cursor, segment.minutes)
            if (!nextEnd) return

            appendOvertimeEntry(segment, cursor, nextEnd)
            cursor = nextEnd
        })

        return entries
    }
    const setProjectTimeEntryDefaults = () => {
        if (timeCard.value?.project_segments?.length) {
            const entries = sortProjectSegmentsByTime(timeCard.value.project_segments).map(segment => buildProjectTimeEntry({
                project_id: segment.project_id,
                id: segment.id,
                segment_type: segment.segment_type ?? PROJECT_SEGMENT_TYPE.WORK,
                start_time: segment.start_time,
                end_time: segment.end_time,
                details: hasOvertimeRequest.value && Array.isArray(segment.details)
                    ? segment.details.filter(detail => detail !== 'overtime')
                    : segment.details,
                detail_values: detailValuesForProjectSegment(segment),
                comment: segment.comment,
                status: segment.status,
                project: segment.project ?? null,
            }))
            projectTimeEntries.value = hasOvertimeRequest.value ? mergeContinuousSameProjectEntries(entries) : entries
            if (projectTimeEntries.value[0]) {
                const details = Array.isArray(projectTimeEntries.value[0].details) ? projectTimeEntries.value[0].details : []
                if (!details.includes('comment')) {
                    replaceProjectEntryDetails(projectTimeEntries.value[0], [...details, 'comment'])
                }
            }
            return
        }
        const overtimeEntries = projectEntriesFromOvertimeRequest()
        if (overtimeEntries.length) {
            projectTimeEntries.value = overtimeEntries
            const firstWorkEntry = overtimeEntries.find(isWorkProjectEntry)
            const lastWorkEntry = [...overtimeEntries].reverse().find(isWorkProjectEntry)
            if (firstWorkEntry?.start_time) {
                editStartTime.value = firstWorkEntry.start_time
            }
            if (lastWorkEntry?.end_time) {
                editEndTime.value = lastWorkEntry.end_time
            }
            return
        }
        const [firstEntry] = projectTimeEntries.value
        if (!firstEntry) {
            projectTimeEntries.value.push(buildProjectTimeEntry({
                project_id: todayWorkGroup.value || null,
                segment_type: includesWorkHours.value ? PROJECT_SEGMENT_TYPE.WORK : PROJECT_SEGMENT_TYPE.TRAINING,
                start_time: includesWorkHours.value ? editStartTime.value : trainingStartTime.value,
                end_time: includesWorkHours.value ? editEndTime.value : trainingEndTime.value,
                details: ['comment'],
            }))
            return
        }
        firstEntry.project_id = firstEntry.project_id ?? todayWorkGroup.value ?? null
        firstEntry.segment_type = firstEntry.segment_type ?? (includesWorkHours.value ? PROJECT_SEGMENT_TYPE.WORK : PROJECT_SEGMENT_TYPE.TRAINING)
        firstEntry.start_time = firstEntry.start_time ?? (isWorkProjectEntry(firstEntry) ? editStartTime.value : trainingStartTime.value)
        firstEntry.end_time = firstEntry.end_time ?? (isWorkProjectEntry(firstEntry) ? editEndTime.value : trainingEndTime.value)
        const details = Array.isArray(firstEntry.details) ? firstEntry.details : []
        if (!details.includes('comment')) {
            replaceProjectEntryDetails(firstEntry, [...details, 'comment'])
        }
    }
    const getInitialAttendanceMode = () => {
        if (!isRegisteredStaff.value) {
            return ATTENDANCE_MODE.WORK_ONLY
        }

        const hasWorkTimes = Boolean(timeCard.value?.start_time && timeCard.value?.end_time)
        const hasTrainingTimes = Boolean(timeCard.value?.training_start_time && timeCard.value?.training_end_time)

        if (hasWorkTimes && hasTrainingTimes) {
            return ATTENDANCE_MODE.WORK_AND_TRAINING
        }

        if (hasTrainingTimes) {
            return ATTENDANCE_MODE.TRAINING_ONLY
        }

        return ATTENDANCE_MODE.WORK_ONLY
    }
    const attendanceMode = ref(getInitialAttendanceMode())
    const includesWorkHours = computed(() => attendanceMode.value !== ATTENDANCE_MODE.TRAINING_ONLY)
    const includesTrainingHours = computed(() => isRegisteredStaff.value && attendanceMode.value !== ATTENDANCE_MODE.WORK_ONLY)
    const includesProjectTimeEntries = computed(() => includesWorkHours.value || includesTrainingHours.value)
    const isAttendanceModeOptionDisabled = (mode) => {
        return mode === ATTENDANCE_MODE.TRAINING_ONLY && hasLockedProjectEntry.value
    }
    const costDepartment = computed(() => {
        return workGroupAsOptions.value.find(group => group.id === todayWorkGroup.value)?.name
    })
    const api = useApi()
    const { ask, ping } = useDialog()
    const generateDraftUuid = () => {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID()
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
            const random = Math.floor(Math.random() * 16)
            const value = char === 'x' ? random : (random & 0x3) | 0x8
            return value.toString(16)
        })
    }
    const normalizeReceiptDate = (value) => {
        if (!value) return null
        return String(value).split('T')[0]
    }
    const buildEmptyCost = (department = costDepartment.value ?? '', projectId = null, projectSegmentId = null, projectEntryKey = null, projectSegmentKey = null) => ({
        department,
        project_id: projectId,
        timecard_project_segment_id: projectSegmentId,
        project_entry_key: projectEntryKey,
        project_segment_key: projectSegmentKey,
        content: '',
        type: props.item.position_id == 15 ? 1 : 4,
        transport_type: 1,
        departure_place: '',
        arrival_place: '',
        expenses: null,
        file_path: null,
        receipt_file_id: null,
        draft_uuid: generateDraftUuid(),
        merchant_name: '',
        receipt_date: null,
        currency: 'JPY',
        receipt_source_type: 'paper_scan',
        file_original_name: null,
        file_mime_type: null,
        file_size_bytes: null,
        file_sha256: null,
        file_uploaded_at: null,
        scan_dpi: null,
        scan_color_depth: null,
        scan_color_mode: null,
        document_size: null,
        image_width_px: null,
        image_height_px: null,
        ocr_run_id: null,
        ocr_applied_fields: [],
    })
    const clearReceiptFields = (cost) => {
        cost.file_path = null
        cost.receipt_file_id = null
        cost.file_original_name = null
        cost.file_mime_type = null
        cost.file_size_bytes = null
        cost.file_sha256 = null
        cost.file_uploaded_at = null
        cost.scan_dpi = null
        cost.scan_color_depth = null
        cost.scan_color_mode = null
        cost.document_size = null
        cost.image_width_px = null
        cost.image_height_px = null
        cost.ocr_run_id = null
        cost.ocr_applied_fields = []
    }
    watch(car_mileage, (after) => {
        if (after) {
            getMyCarData()
        }
    })
    watch(todayWorkGroup, (newWorkGroup) => {
        costs.forEach(cost => {
            if (!isMeaningfulCost(cost) && !cost.project_id) {
                cost.project_id = newWorkGroup
                cost.department = projectName(newWorkGroup)
            }
        })
        const nextProject = workGroupAsOptions.value.find(group => group.id === newWorkGroup)
        if (Number(selectedProject.value?.id) !== Number(nextProject?.id)) {
            selectedProject.value = nextProject
        }
        const [firstEntry] = projectTimeEntries.value
        if (firstEntry && !firstEntry.project_id) {
            firstEntry.project_id = newWorkGroup
            firstEntry.previous_project_id = newWorkGroup
        }
    })
    watch(projectTimeEntries, (entries) => {
        entries.forEach(entry => {
            if (!entry.previous_project_id && entry.project_id) {
                entry.previous_project_id = entry.project_id
            }
        })
        const firstWorkEntry = entries.find(isWorkProjectEntry)
        const lastWorkEntryWithEnd = [...entries].reverse().find(entry => isWorkProjectEntry(entry) && entry.end_time)
        const firstTrainingEntry = entries.find(isTrainingProjectEntry)
        const lastTrainingEntryWithEnd = [...entries].reverse().find(entry => isTrainingProjectEntry(entry) && entry.end_time)
        const firstProjectEntry = firstWorkEntry ?? firstTrainingEntry ?? entries[0]

        if (firstProjectEntry?.project_id) {
            setRefIfChanged(todayWorkGroup, firstProjectEntry.project_id)
            const nextProject = projectForEntry(firstProjectEntry)
            if (Number(selectedProject.value?.id) !== Number(nextProject?.id)) {
                selectedProject.value = nextProject
            }
        }
        if (firstWorkEntry?.start_time) {
            setRefIfChanged(editStartTime, firstWorkEntry.start_time)
        }
        if (lastWorkEntryWithEnd?.end_time) {
            setRefIfChanged(editEndTime, lastWorkEntryWithEnd.end_time)
        }
        if (firstTrainingEntry?.start_time) {
            setRefIfChanged(trainingStartTime, firstTrainingEntry.start_time)
        }
        if (lastTrainingEntryWithEnd?.end_time) {
            setRefIfChanged(trainingEndTime, lastTrainingEntryWithEnd.end_time)
        }
    }, { deep: true })
    watch([editStartTime, editEndTime], ([startTime, endTime]) => {
        if (workProjectEntries.value.length !== 1) return
        const [firstEntry] = workProjectEntries.value
        if (!firstEntry.start_time) {
            firstEntry.start_time = startTime
        }
        if (!firstEntry.end_time) {
            firstEntry.end_time = endTime
        }
    })
    watch(attendanceMode, (mode) => {
        if (mode === ATTENDANCE_MODE.TRAINING_ONLY) {
            breakTimeSelect.value = 0
            projectTimeEntries.value.forEach(entry => {
                entry.segment_type = PROJECT_SEGMENT_TYPE.TRAINING
            })
            normalizeProjectEntriesForMode()
            setProjectTimeEntryDefaults()
            return
        }

        setProjectTimeEntryDefaults()
        breakTimeCalc()

        if (mode === ATTENDANCE_MODE.WORK_ONLY) {
            projectTimeEntries.value.forEach(entry => {
                entry.segment_type = PROJECT_SEGMENT_TYPE.WORK
            })
            normalizeProjectEntriesForMode()
            trainingStartTime.value = null
            trainingEndTime.value = null
        } else if (mode === ATTENDANCE_MODE.WORK_AND_TRAINING) {
            if (!trainingProjectEntries.value.length) {
                projectTimeEntries.value.push(buildProjectTimeEntry({
                    project_id: todayWorkGroup.value || projectTimeEntries.value[0]?.project_id || null,
                    segment_type: PROJECT_SEGMENT_TYPE.TRAINING,
                    start_time: trainingStartTime.value,
                    end_time: trainingEndTime.value,
                    details: ['comment'],
                }))
            }
        }
    })
    watch(() => customValues.value[44], (val) => {
        if (Number(val) === 0) {
            projectTimeEntries.value.forEach(entry => {
                if (!isProjectDetailVisible(entry, 'vehicle')) {
                    ensureProjectDetailValues(entry).vehicle = buildVehicleData()
                }
            })
        }
    })
    const unitLabelForProject = (project) => {
        const unitCode = project?.unit_id ?? 'JPY'
        if (unitCode === 'COUNT') return '件'
        if (unitCode === 'HOUR') return '時間'
        if (unitCode === 'CUSTOM') return project?.custom_unit_label || '単位'
        return '円'
    }
    const actualNumberFormatter = new Intl.NumberFormat('ja-JP')
    const focusedActualRow = ref(null)
    const isYenActualEntry = (entry) => (projectForEntry(entry)?.unit_id ?? 'JPY') === 'JPY'
    const actualRawValue = (value) => {
        if (value === null || value === undefined || value === '') return ''
        return String(value)
    }
    const actualInputDisplayValue = (row, entry) => {
        if (!isYenActualEntry(entry) || focusedActualRow.value === row) return actualRawValue(row.value)

        const number = Number(row.value)
        return Number.isFinite(number) ? actualNumberFormatter.format(number) : actualRawValue(row.value)
    }
    const focusActualInput = (row) => {
        focusedActualRow.value = row
    }
    const blurActualInput = () => {
        focusedActualRow.value = null
    }
    const updateActualInputValue = (row, event) => {
        const normalized = String(event.target.value ?? '').normalize('NFKC').replace(/,/g, '').trim()
        if (normalized === '') {
            row.value = null
            return
        }

        const number = Number(normalized)
        if (Number.isFinite(number)) {
            row.value = number
        }
    }
    const actualStatusDefsForEntry = (entry) => projectForEntry(entry)?.actual_statuses ?? []
    const actualStatusCanRepeat = (statusDef) => {
        return Array.isArray(statusDef?.extra_fields) && statusDef.extra_fields.length > 0
    }
    const actualRowsForEntry = (entry) => {
        const projectId = Number(entry?.project_id)
        return actualRows.value.filter(row => Number(row.project_id) === projectId)
    }
    const ensureActualRowsForProject = (entry) => {
        if (!entry?.project_id || actualRowsForEntry(entry).length) return
        const project = projectForEntry(entry)
        const statuses = project?.actual_statuses ?? []

        if (statuses.length > 0) {
            statuses.forEach(status => {
                const emptyMeta = {}
                ;(status.extra_fields ?? []).forEach(field => { emptyMeta[field.label] = '' })
                actualRows.value.push({
                    status: status.label ?? status.custom_label ?? null,
                    value: null,
                    meta: emptyMeta,
                    project_id: entry.project_id,
                })
            })
            return
        }

        actualRows.value.push({
            status: null,
            value: null,
            meta: {},
            project_id: entry.project_id,
        })
    }
    const buildRows = () => {
        const cases = timeCard.value?.project_case ?? []

        if (cases.length > 0) {
            actualRows.value = cases.map(c => ({
                status: c.status ?? null,
                value: c.amount ?? null,
                meta: c.meta ?? {},
                project_id: c.project_record_id ?? selectedProject.value?.id ?? null,
            }))
            return
        }

        actualRows.value = []
        ensureActualRowsForProject({ project_id: selectedProject.value?.id ?? todayWorkGroup.value ?? null })
    }

    const rowsForStatus = (entry, statusLabel) => {
        return actualRowsForEntry(entry).filter(r => r.status === statusLabel)
    }

    const addActualRowForStatus = (entry, statusDef) => {
        if (!actualStatusCanRepeat(statusDef)) return
        const emptyMeta = {}
        ;(statusDef.extra_fields ?? []).forEach(f => { emptyMeta[f.label] = '' })
        actualRows.value.push({
            status: statusDef.label ?? statusDef.custom_label,
            value: null,
            meta: emptyMeta,
            project_id: entry?.project_id ?? null,
        })
    }

    const removeActualRowForStatus = (entry, statusLabel, statusRowIndex) => {
        const projectId = Number(entry?.project_id)
        const indices = actualRows.value
            .map((r, i) => r.status === statusLabel && Number(r.project_id) === projectId ? i : -1)
            .filter(i => i !== -1)
        if (indices.length <= 1) return
        const globalIndex = indices[statusRowIndex]
        actualRows.value.splice(globalIndex, 1)
    }

    watch(
        [
            () => selectedProject.value?.id,
            () => selectedProject.value?.actual_statuses?.length,
            () => timeCard.value?.id,
            () => timeCard.value?.project_case?.length,
        ],
        buildRows,
        { immediate: true }
    )
    const unitLabel = computed(() => unitLabelForProject(selectedProject.value));
    const addCostField = (department = costDepartment.value ?? '', projectId = null, projectSegmentId = null, projectEntryKey = null, projectSegmentKey = null) => {
        if(costs.length >= 10){
            ping('上限は10個です。')
            return
        }
        costs.push(buildEmptyCost(department, projectId, projectSegmentId, projectEntryKey, projectSegmentKey))
    }
    const addCostFieldForProject = (entry) => {
        const department = projectName(entry?.project_id)
        if (!department) {
            ping('プロジェクトを選択してください。')
            return
        }
        addCostField(department, entry.project_id, entry.id ?? null, entry.key, projectSegmentMatchKey(entry))
    }
    const removeCostField = async(index) => {
        costs.splice(index, 1)
    }
    const removeFile = async(index) => {
        const targetCost = costs[index]
        if (!targetCost?.file_path) return

        const response = await api.post('/work_file_delete', {
            draft_uuid: targetCost.draft_uuid,
            file_path: targetCost.file_path,
            receipt_file_id: targetCost.receipt_file_id,
            subject_user_id: props.item?.user_id,
            timecard_record_id: timeCard.value?.id,
            timecard_cost_record_id: targetCost.id,
        })
        if (!response) return

        clearReceiptFields(targetCost)
    }
    const getMyCarData = async() => {
        if (car_mileage.value < 2) return
        
        const data = await api.get('/get_my_car_data', { user_code: props.item.user_code, mileage: car_mileage.value})
        if (!data) return
        car_data.value = data
        
    }
    const getProjectMyCarData = async(entry) => {
        const mileageData = projectMileage(entry)
        const mileage = Number(mileageData.mileage || 0)
        if (mileage < 2) {
            mileageData.gas_full_price = 0
            mileageData.gas_consumption = null
            mileageData.gas_unit_price = null
            return
        }

        const data = await api.get('/get_my_car_data', { user_code: props.item.user_code, mileage })
        if (!data || data.status !== 'success') return

        mileageData.gas_full_price = data.gas_full_price ?? 0
        mileageData.gas_consumption = data.gas_consumption ?? null
        mileageData.gas_unit_price = data.gas_unit_price ?? null
    }
    const tutorialStore = useTutorialStore()
    const { startTour } = useTour()
    onMounted(async() => {
        fields.value = await getCustomFields()
        workGroups.value = await getWorkGroup()
        setTimeout(() => {
            if(!timeCard.value || !timeCard.value?.work_group_id){
                todayWorkGroup.value = workGroupAsOptions.value[0]?.id ?? ''
                car_used_project.value = todayWorkGroup.value
                setProjectTimeEntryDefaults()
                initializeProjectDetails()
                spinner.value++
            } else {
                setProjectTimeEntryDefaults()
                initializeProjectDetails()
                spinner.value++
            }
        }, 100);

        
        if(props.item?.total_break_time){
            const newItem = {
                label: props.item?.total_break_time + '分',
                value: props.item?.total_break_time
            }
            breakTimeOptions.value.push(newItem)
            breakTimeSelect.value = props.item?.total_break_time
        } else {
            breakTimeCalc()
        }
        if (timeCard.value?.car_mileage) {
            car_mileage.value = timeCard.value?.car_mileage
        }
        costsFill()
        customFieldFill()
        if (tutorialStore.state.active && tutorialStore.state.name.includes('timesheet.dailyreport')) {
            setTimeout(() => {
                todayWorkGroup.value = workGroupAsOptions.value.find(group => group.has_actual_func === true)?.id
                startTour('timesheet.dailyreport.create.details')
            }, 500)
            tutorialStore.setTutorial({ active: true, name: [] })
        }
    })
    const costsFill = () => {
        if(timeCard.value?.timecard_costs?.length){
            timeCard.value.timecard_costs.forEach(cost => {
                const projectId = cost.project_id ?? projectIdFromDepartment(cost.department)
                const department = cost.department || projectName(projectId) || ''
                const boil = {
                    ...buildEmptyCost(department, projectId, cost.timecard_project_segment_id ?? null),
                    ...cost,
                    department,
                    project_id: projectId,
                    draft_uuid: cost.draft_uuid ?? generateDraftUuid(),
                    receipt_date: normalizeReceiptDate(cost.receipt_date),
                    ocr_applied_fields: Array.isArray(cost.ocr_applied_fields) ? cost.ocr_applied_fields : [],
                }
                costs.push(boil)
            });
        }
    }
    const customFieldFill = () => {
        const storedSegments = Array.isArray(timeCard.value?.project_segments) ? timeCard.value.project_segments : []
        if (storedSegments.length) {
            customValues.value[37] = [...new Set(storedSegments
                .flatMap(segment => Array.isArray(segment?.detail_values?.allowance) ? segment.detail_values.allowance : [])
                .filter(value => value !== null && value !== undefined && value !== '')
                .map(value => Number(value))
            )]
            customValues.value[39] = ''
            customValues.value[40] = [...new Set(storedSegments
                .map(segment => String(segment?.detail_values?.incident ?? '').trim())
                .filter(Boolean)
            )].join("\n")
            customValues.value[42] = [...new Set(storedSegments
                .map(segment => String(segment?.detail_values?.overtime ?? '').trim())
                .filter(Boolean)
            )].join("\n")
            customValues.value[44] = storedSegments.some(segment => Array.isArray(segment?.details) && segment.details.includes('vehicle')) ? 1 : 0
            return
        }

        if(fields.value){
            fields.value.forEach(element => {
                const index = [39, 40, 42].includes(element.id) ? 'value_text' : 'value_int'
                const pre = timeCard.value?.custom_field_data_records.filter(ob => ob.type_id == element.id && ob.user_id == timeCard.value.user_id)
                if(element.id == 37){
                    const allowance = pre && pre.length ? pre.map(ob => ob.value_int) : []
                    customValues.value[element.id] = allowance
                    
                }else if(element.id == 40){
                    customValues.value[element.id] = pre && pre.length ? (pre[0].value_text ?? pre[0].label ?? '') : ''
                }else{
                    customValues.value[element.id] = pre && pre.length ? pre[0][index] !== null ? pre[0][index].toString() : '' : ''
                }               
            });
        }
    }
    const entryForProjectId = (projectId) => {
        return projectTimeEntries.value.find(entry => Number(entry.project_id) === Number(projectId)) ?? projectTimeEntries.value[0]
    }
    const entryForProjectName = (name) => {
        const project = workGroupAsOptions.value.find(group => group.name === name)
        return entryForProjectId(project?.id)
    }
    const initializeProjectDetails = () => {
        const firstEntry = projectTimeEntries.value[0]
        if (!firstEntry) return

        costs
            .filter(isMeaningfulCost)
            .forEach(cost => {
                const entry = entryForProjectName(cost.department)
                if (entry) addProjectDetail(entry, 'expenses')
            })

        if (car_mileage.value) {
            const mileageEntry = projectTimeEntries.value.find(entry => {
                return isProjectDetailVisible(entry, 'mileage') && Number(entry.project_id) === Number(car_used_project.value)
            }) ?? (!projectTimeEntries.value.some(entry => isProjectDetailVisible(entry, 'mileage')) ? entryForProjectId(car_used_project.value) : null)
            if (mileageEntry && !isProjectEntryLocked(mileageEntry) && !isProjectDetailVisible(mileageEntry, 'mileage')) {
                addProjectDetail(mileageEntry, 'mileage')
            }
            if (mileageEntry && !isProjectEntryLocked(mileageEntry) && !Number(ensureProjectDetailValues(mileageEntry).mileage?.mileage || 0)) {
                const mileageData = ensureProjectDetailValues(mileageEntry).mileage
                mileageData.mileage = car_mileage.value
                mileageData.gas_full_price = timeCard.value?.gas_full_price ?? car_data.value?.gas_full_price ?? 0
                mileageData.gas_consumption = car_data.value?.gas_consumption ?? null
                mileageData.gas_unit_price = car_data.value?.gas_unit_price ?? null
            }
        }
        if (Array.isArray(customValues.value[37]) && customValues.value[37].length) {
            const allowanceEntry = projectTimeEntries.value.find(entry => isProjectDetailVisible(entry, 'allowance')) ?? firstEntry
            if (allowanceEntry && !isProjectEntryLocked(allowanceEntry) && !isProjectDetailVisible(allowanceEntry, 'allowance')) {
                addProjectDetail(allowanceEntry, 'allowance')
            }
            if (allowanceEntry && !isProjectEntryLocked(allowanceEntry) && !ensureProjectDetailValues(allowanceEntry).allowance.length) {
                ensureProjectDetailValues(allowanceEntry).allowance = [...customValues.value[37]]
                ensureProjectDetailValues(allowanceEntry).allowance_labels = allowanceLabelsForValues(customValues.value[37])
            }
        }
        if (customValues.value[40]) {
            const incidentEntry = projectTimeEntries.value.find(entry => isProjectDetailVisible(entry, 'incident')) ?? firstEntry
            if (incidentEntry && !isProjectEntryLocked(incidentEntry) && !isProjectDetailVisible(incidentEntry, 'incident')) {
                addProjectDetail(incidentEntry, 'incident')
            }
            if (incidentEntry && !isProjectEntryLocked(incidentEntry) && !String(ensureProjectDetailValues(incidentEntry).incident ?? '').trim()) {
                ensureProjectDetailValues(incidentEntry).incident = customValues.value[40]
            }
        }
        if (!customValues.value[42] && overtimeRequestContentSummary.value) {
            customValues.value[42] = overtimeRequestContentSummary.value
        }
        if (customValues.value[42]) {
            syncOvertimeReasonDetail()
        }
        if (Number(customValues.value[44]) === 1) {
            const vehicleEntry = projectTimeEntries.value.find(entry => isProjectDetailVisible(entry, 'vehicle')) ?? firstEntry
            addProjectDetail(vehicleEntry, 'vehicle')
            if (
                timeCard.value?.vehicle_data
                && !projectTimeEntries.value.some(entry => isProjectDetailVisible(entry, 'vehicle') && ensureProjectDetailValues(entry).vehicle?.vehicle)
            ) {
                ensureProjectDetailValues(vehicleEntry).vehicle = buildVehicleData(timeCard.value.vehicle_data)
            }
        }
        const legacyComment = String(customValues.value[39] ?? '').trim()
        if (legacyComment) {
            const commentEntry = projectTimeEntries.value.find(entry => !isProjectEntryLocked(entry)) ?? (hasLockedProjectEntry.value ? null : firstEntry)
            if (commentEntry) {
                if (!commentEntry.comment) {
                    commentEntry.comment = legacyComment
                }
                addProjectDetail(commentEntry, 'comment')
                customValues.value[39] = ''
            }
        }
        actualRows.value
            .filter(row => hasActualValue(row.value))
            .forEach(row => {
                const actualEntry = projectTimeEntries.value.find(entry => Number(entry.project_id) === Number(row.project_id))
                    ?? (!row.project_id ? firstEntry : null)
                if (actualEntry) {
                    addProjectDetail(actualEntry, 'actual')
                }
            })
    }
    const timeStringToMinutes = (time) => {
        if (!time) return null
        const [hours, minutes] = String(time).split(':').map(Number)
        if (Number.isNaN(hours) || Number.isNaN(minutes)) return null
        return hours * 60 + minutes
    }
    const shiftWorkTime = computed(() => {
        const shiftStartTime = shift.value && shift.value?.start_time ? shift.value?.start_time : '09:00:00'
        const shiftEndTime = shift.value && shift.value?.end_time ? shift.value?.end_time : '18:00:00'
        const start = DateTime.fromFormat(shiftStartTime, 'HH:mm:ss')
        const end = DateTime.fromFormat(shiftEndTime, 'HH:mm:ss')
        return end.diff(start, 'minutes').as('minutes')
    })
    const workedTime = computed(() => {
        if (!includesWorkHours.value || !editStartTime.value || !editEndTime.value) {
            return 0
        }
        const start = timeStringToMinutes(editStartTime.value)
        const end = timeStringToMinutes(editEndTime.value)
        if (start === null || end === null) return 0
        return end >= start ? end - start : end + 1440 - start
    })
    const breakTimeCalc = () => {
        if (!includesWorkHours.value) {
            breakTimeSelect.value = 0
            return
        }
        if(editStartTime.value && editEndTime.value && breakTimeSelect.value == 0){
            const startTimeParts = editStartTime.value.split(":");
            const endTimeParts = editEndTime.value.split(":");
            const startHour = parseInt(startTimeParts[0]);
            const startMinute = parseInt(startTimeParts[1]);
            const endHour = parseInt(endTimeParts[0]);
            const endMinute = parseInt(endTimeParts[1]);

            const workTimeMinutes = (endHour * 60 + endMinute) - (startHour * 60 + startMinute);

            if (workTimeMinutes >= 360) {
                breakTimeSelect.value = 60;
            } else if (workTimeMinutes >= 180 && workTimeMinutes < 360) {
                breakTimeSelect.value = 30;
            } else if (workTimeMinutes < 180) {
                breakTimeSelect.value = 0;
            }
        }
    }
    
    const formatedDay = computed(() => {
        return DateTime.fromISO(props.item?.day_full).toFormat('M月d日')
    })
    
    const showToastIfEmpty = async() => {
        return new Promise ((resolve) => {
            const targets = []
            if (requiresOvertimeReason.value) {
                targets.push(42)
            }
            if (includesWorkHours.value) {
                if (isInvalidTime(formatTime(editStartTime.value)) || isInvalidTime(formatTime(editEndTime.value))) {
                    ping('就業時間は必須です。')
                    resolve(false)
                    return
                }
                if (!workProjectEntries.value.length) {
                    ping('就業のプロジェクト時間を入力してください。')
                    resolve(false)
                    return
                }
            }
            const emptyCommentEntry = projectTimeEntries.value.find(entry => {
                return isProjectDetailVisible(entry, 'comment') && !String(entry.comment ?? '').trim()
            })
            if (emptyCommentEntry) {
                ping('コメントを入力してください。')
                resolve(false)
                return
            }
            const emptyIncidentEntry = projectTimeEntries.value.find(entry => {
                return isProjectDetailVisible(entry, 'incident') && !String(ensureProjectDetailValues(entry).incident ?? '').trim()
            })
            if (emptyIncidentEntry) {
                ping('インシデントの内容を入力してください。')
                resolve(false)
                return
            }
            if (includesTrainingHours.value) {
                if (!trainingProjectEntries.value.length) {
                    ping('研修のプロジェクト時間を入力してください。')
                    resolve(false)
                    return
                }
            }
            const emptyOvertimeEntry = projectTimeEntries.value.find(entry => {
                return isProjectDetailVisible(entry, 'overtime') && !String(ensureProjectDetailValues(entry).overtime ?? '').trim()
            })
            if (emptyOvertimeEntry) {
                ping('時間外業務内容を入力してください。')
                resolve(false)
                return
            }
            // targets.forEach(index => {
            //     const v = customValues.value[index]
            //     if(!v){
            //         const fieldName = fields.value.find(ob => ob.id == index)?.title ?? (index === 42 ? '時間外業務内容' : '必須項目')
            //         const message = `${fieldName}は必須項目です。必ず選択してください。`
            //         ping(message)
            //         resolve(false)
            //     }
            // })
            for (const entry of projectTimeEntries.value.filter(entry => isProjectDetailVisible(entry, 'vehicle'))) {
                if(!vehicleConfirm(entry)){
                    resolve(false)
                    return
                }
            }
            const invalidCustomFields = customFieldRef.value?.filter(field => field.subPartsChecked === false)
            if(invalidCustomFields && invalidCustomFields.length > 0){
                ping('在宅手当の種類を選択してください。')
                resolve(false)
            }
            // Validate extra fields are filled when a value is entered
            for (const row of actualRows.value) {
                if (row.value === null || row.value === '' || row.value === undefined || row.value === 0) continue
                const project = projectForId(row.project_id) ?? selectedProject.value
                const statusDefs = project?.actual_statuses ?? []
                const def = statusDefs.find(s => (s.label ?? s.custom_label) === row.status)
                const extraFields = def?.extra_fields ?? []
                for (const field of extraFields) {
                    const v = row.meta?.[field.label]
                    if (v === null || v === undefined || String(v).trim() === '') {
                        ping(`「${row.status}」の「${field.label}」は必須項目です。入力してください。`)
                        resolve(false)
                        return
                    }
                }
            }
            resolve(true)
        })
    }
    const vehicleConfirm = (entry) => {
        const vehicle = ensureProjectDetailValues(entry).vehicle
        const prefix = projectName(entry?.project_id) ? `${projectName(entry.project_id)}の` : ''
        if (!vehicle) {
            ping('車両の使用に関する情報はありません。');
            return false;
        } else if (vehicle['vehicle'] === null || vehicle['vehicle'] === undefined || vehicle['vehicle'] === '') {
            ping(`${prefix}車両が選択されていません。`);
            return false;
        } else if (!vehicle['alcohol_before_time'] || !vehicle['alcohol_after_time']) {
            ping(`${prefix}前後の時間が選択されていません。`);
            return false;
        } else if (vehicle['alcohol_before_value'] === null || vehicle['alcohol_before_value'] === undefined || vehicle['alcohol_after_value'] === null || vehicle['alcohol_after_value'] === undefined) {
            ping(`${prefix}前後の値が選択されていません。`);
            return false;
        } else if (!vehicle['confirm_before_user'] || !vehicle['confirm_after_user']) {
            ping(`${prefix}前後の確認者が選択されていません。`);
            return false;
        }
        return true;
    };
    const formatTime = (time) => { 
        if(!time) return null
        const [hours, minutes] = time.split(':')
        return `${hours}:${minutes}`
    }
    const overlapMinutes = (firstStart, firstEnd, secondStart, secondEnd) => {
        if ([firstStart, firstEnd, secondStart, secondEnd].some(value => value === null)) return 0
        let aStart = firstStart
        let aEnd = firstEnd >= firstStart ? firstEnd : firstEnd + 1440
        let bStart = secondStart
        let bEnd = secondEnd >= secondStart ? secondEnd : secondEnd + 1440

        if (bEnd <= aStart) {
            bStart += 1440
            bEnd += 1440
        } else if (bStart >= aEnd) {
            bStart -= 1440
            bEnd -= 1440
        }

        return Math.max(0, Math.min(aEnd, bEnd) - Math.max(aStart, bStart))
    }
    const trainingOverlapMinutes = computed(() => {
        if (!includesWorkHours.value || !includesTrainingHours.value || !editStartTime.value || !editEndTime.value) {
            return 0
        }

        const workStart = timeStringToMinutes(editStartTime.value)
        const workEnd = timeStringToMinutes(editEndTime.value)

        return trainingProjectEntries.value.reduce((total, entry) => {
            return total + overlapMinutes(workStart, workEnd, timeStringToMinutes(entry.start_time), timeStringToMinutes(entry.end_time))
        }, 0)
    })
    const formatProjectTimeMinutes = (minutes) => {
        if (!minutes) return '0時間'
        const hours = Math.floor(minutes / 60)
        const remainingMinutes = minutes % 60
        if (hours && remainingMinutes) return `${hours}時間${remainingMinutes}分`
        if (hours) return `${hours}時間`
        return `${remainingMinutes}分`
    }
    const projectTimeEntryRawMinutes = (entry) => {
        const start = timeStringToMinutes(entry.start_time)
        const end = timeStringToMinutes(entry.end_time)
        if (start === null || end === null) return 0
        return end >= start ? end - start : end + 1440 - start
    }
    const projectTimeEntryRange = (entry) => {
        const start = timeStringToMinutes(entry.start_time)
        const end = timeStringToMinutes(entry.end_time)
        if (start === null || end === null) return null

        return {
            entry,
            start,
            end: end >= start ? end : end + 1440,
        }
    }
    const projectTimeOverlapMessage = (firstEntry, secondEntry, typeLabel) => {
        const sameProject = Number(firstEntry?.project_id) === Number(secondEntry?.project_id)
        if (sameProject) {
            return `${typeLabel}プロジェクト時間が重複しています。同じプロジェクトの同じ時間帯は1つにまとめてください。`
        }

        return `${typeLabel}プロジェクト時間が重複しています。同じ時間帯は1つのプロジェクトだけに入力してください。`
    }
    const projectTimeOverlapWarningForEntries = (entries, typeLabel) => {
        const ranges = entries
            .map(projectTimeEntryRange)
            .filter(Boolean)

        for (let firstIndex = 0; firstIndex < ranges.length; firstIndex++) {
            for (let secondIndex = firstIndex + 1; secondIndex < ranges.length; secondIndex++) {
                const first = ranges[firstIndex]
                const second = ranges[secondIndex]
                const overlap = overlapMinutes(first.start, first.end, second.start, second.end)

                if (overlap > 0) {
                    return projectTimeOverlapMessage(first.entry, second.entry, typeLabel)
                }
            }
        }

        return ''
    }
    const projectTimeOverlapWarning = computed(() => {
        return projectTimeOverlapWarningForEntries(workProjectEntries.value, '就業')
            || projectTimeOverlapWarningForEntries(trainingProjectEntries.value, '研修')
    })
    const projectTimeGapBetweenEntries = (previousEntry, entry) => {
        const previousStart = timeStringToMinutes(previousEntry.start_time)
        const previousEnd = timeStringToMinutes(previousEntry.end_time)
        let currentStart = timeStringToMinutes(entry.start_time)

        if (previousStart === null || previousEnd === null || currentStart === null) return 0

        const previousEndOffset = previousEnd >= previousStart ? previousEnd : previousEnd + 1440
        if (currentStart < previousStart) {
            currentStart += 1440
        }

        return Math.max(0, currentStart - previousEndOffset)
    }
    const projectTimeGapRanges = computed(() => {
        const entries = sortProjectSegmentsByTime(workProjectEntries.value, editStartTime.value, editEndTime.value)
        return entries.reduce((ranges, entry, index) => {
            if (index === 0) return ranges
            const previousEntry = entries[index - 1]
            const previousEnd = timeStringToMinutes(previousEntry.end_time)
            const currentStart = timeStringToMinutes(entry.start_time)
            if (previousEnd === null || currentStart === null) return ranges
            const gap = projectTimeGapBetweenEntries(previousEntry, entry)
            if (gap <= 0) return ranges
            const trainingInGap = trainingProjectEntries.value.reduce((trainingTotal, trainingEntry) => {
                return trainingTotal + overlapMinutes(previousEnd, currentStart, timeStringToMinutes(trainingEntry.start_time), timeStringToMinutes(trainingEntry.end_time))
            }, 0)
            const minutes = Math.max(0, gap - trainingInGap)
            if (minutes > 0) {
                ranges.push({
                    start_time: previousEntry.end_time,
                    end_time: entry.start_time,
                    minutes,
                })
            }
            return ranges
        }, [])
    })
    const projectTimeGapMinutes = computed(() => {
        return projectTimeGapRanges.value.reduce((total, range) => total + range.minutes, 0)
    })
    const projectTimeBreakDeductionMinutes = computed(() => Number(breakTimeSelect.value || 0))
    const projectTimeRawMinutesBefore = (index) => {
        return projectTimeEntries.value
            .slice(0, index)
            .filter(isWorkProjectEntry)
            .reduce((total, entry) => total + projectTimeEntryRawMinutes(entry), 0)
    }
    const projectTimeEntryMinutes = (entry, index = 0) => {
        const rawMinutes = projectTimeEntryRawMinutes(entry)
        if (isTrainingProjectEntry(entry)) {
            return rawMinutes
        }
        const remainingDeduction = projectTimeBreakDeductionMinutes.value - projectTimeRawMinutesBefore(index)
        const deductionForEntry = Math.min(rawMinutes, Math.max(0, remainingDeduction))
        return Math.max(0, rawMinutes - deductionForEntry)
    }
    const projectTimeDurationLabel = (entry, index = 0) => {
        const minutes = projectTimeEntryMinutes(entry, index)
        return minutes ? formatProjectTimeMinutes(minutes) : '未入力'
    }
    const syncProjectBoundaryTimesFromWorkTimes = () => {
        const firstEntry = projectTimeEntries.value.find(isWorkProjectEntry)
        const lastEntry = [...projectTimeEntries.value].reverse().find(entry => isWorkProjectEntry(entry) && entry.end_time)

        if (firstEntry) {
            firstEntry.start_time = editStartTime.value
        }
        if (lastEntry) {
            lastEntry.end_time = editEndTime.value
        }
    }
    
    const fifteenMinuteCalc = async() => {
        return new Promise((resolve) => {
            const [endhours, endminutes] = editEndTime.value.split(":");
            let endnearestMinute = Math.floor(endminutes / 15) * 15;
            let endhoursAdjustment = 0;
            if (endnearestMinute === 60) {
                endnearestMinute = 0;
                endhoursAdjustment = 1;
            }
            const adjustedEndHours = parseInt(endhours) + endhoursAdjustment;
            if (!auth.isAdmin) {
                editEndTime.value = `${adjustedEndHours.toString().padStart(2, "0")}:${String(endnearestMinute).padStart(2, "0")}`;
            }
            const [hours, minutes] = editStartTime.value.split(":");
            let nearestMinute = Math.ceil(minutes / 15) * 15;
            let hoursAdjustment = 0;
            if (nearestMinute === 60) {
                nearestMinute = 0;
                hoursAdjustment = 1;
            }
            const adjustedHours = parseInt(hours) + hoursAdjustment;
            editStartTime.value = `${adjustedHours.toString().padStart(2, "0")}:${String(nearestMinute).padStart(2, "0")}`;
            resolve(true)
        })
    }

    const attendanceSpanMinutes = computed(() => {
        if (!includesWorkHours.value || !editStartTime.value || !editEndTime.value) {
            return 0
        }
        const start = timeStringToMinutes(editStartTime.value)
        const end = timeStringToMinutes(editEndTime.value)
        if (start === null || end === null) return 0
        return end >= start ? end - start : end + 1440 - start
    })
    const diffInMinutes = computed(() => {
        return Math.max(
            0,
            attendanceSpanMinutes.value - trainingOverlapMinutes.value - Number(breakTimeSelect.value || 0) - projectTimeGapMinutes.value
        )
    })
    const trainingTimeMinutes = computed(() => {
        if (!includesTrainingHours.value) {
            return 0
        }
        return trainingProjectEntries.value.reduce((total, entry) => total + projectTimeEntryRawMinutes(entry), 0)
    })
    const projectWorkTimeTotalMinutes = computed(() => {
        return projectTimeEntries.value.reduce((total, entry, index) => {
            return isWorkProjectEntry(entry) ? total + projectTimeEntryMinutes(entry, index) : total
        }, 0)
    })
    const projectTrainingTimeTotalMinutes = computed(() => {
        return projectTimeEntries.value.reduce((total, entry, index) => {
            return isTrainingProjectEntry(entry) ? total + projectTimeEntryMinutes(entry, index) : total
        }, 0)
    })
    const attendanceSpanTotalLabel = computed(() => formatProjectTimeMinutes(attendanceSpanMinutes.value))
    const workTimeTotalLabel = computed(() => formatProjectTimeMinutes(diffInMinutes.value))
    const trainingTimeTotalLabel = computed(() => formatProjectTimeMinutes(trainingTimeMinutes.value))
    const projectWorkTimeTotalLabel = computed(() => formatProjectTimeMinutes(projectWorkTimeTotalMinutes.value))
    const projectTrainingTimeTotalLabel = computed(() => formatProjectTimeMinutes(projectTrainingTimeTotalMinutes.value))
    const showProjectWorkInputSummary = computed(() => {
        return includesWorkHours.value
            && projectWorkTimeTotalMinutes.value > 0
            && diffInMinutes.value > 0
            && projectWorkTimeTotalMinutes.value !== diffInMinutes.value
    })
    const regularWorkMinutes = computed(() => {
        const userRegularMinutes = Number(props.item?.work_time_day ?? 0)
        if (userRegularMinutes > 0) return userRegularMinutes
        return 480
    })
    const calculatedOvertimeMinutes = computed(() => {
        if (Number(props.item?.work_type) !== 1 || !includesWorkHours.value) return 0
        return Math.max(0, projectWorkTimeTotalMinutes.value - regularWorkMinutes.value)
    })
    const calculatedOvertimeLabel = computed(() => formatProjectTimeMinutes(calculatedOvertimeMinutes.value))
    const showCalculatedOvertimeSummary = computed(() => Number(props.item?.work_type) === 1 && includesWorkHours.value)
    const requiresOvertimeReason = computed(() => calculatedOvertimeMinutes.value > 0 && !hasOvertimeRequest.value)
    const filterCustomValues = computed(() => {
        if (requiresOvertimeReason.value) return fields.value
        return fields.value.filter(ob => Number(ob.id) !== 42)
    })
    const projectCommentField = computed(() => fields.value.find(ob => Number(ob.id) === 39))
    const overtimeReasonFallbackField = {
        id: 42,
        title: '時間外業務内容',
        form_type: 'textarea',
        custom_field_parts_records: [],
    }
    const overtimeReasonField = computed(() => {
        return filterCustomValues.value.find(ob => Number(ob.id) === 42)
            ?? (requiresOvertimeReason.value ? overtimeReasonFallbackField : null)
    })
    const overtimeProjectEntries = computed(() => {
        if (!requiresOvertimeReason.value) return []

        let elapsedWorkMinutes = 0
        const overtimeEntries = []

        projectTimeEntries.value.forEach((entry, index) => {
            if (!isWorkProjectEntry(entry)) return

            const entryMinutes = projectTimeEntryMinutes(entry, index)
            const previousWorkMinutes = elapsedWorkMinutes
            elapsedWorkMinutes += entryMinutes

            if (entryMinutes > 0 && elapsedWorkMinutes > regularWorkMinutes.value && previousWorkMinutes < elapsedWorkMinutes) {
                overtimeEntries.push(entry)
            }
        })

        return overtimeEntries
    })
    const syncOvertimeReasonDetail = () => {
        const overtimeKeys = new Set(overtimeProjectEntries.value.map(entry => entry.key))

        projectTimeEntries.value.forEach(entry => {
            if (isProjectEntryLocked(entry) || !isWorkProjectEntry(entry)) return

            const hasOvertimeDetail = isProjectDetailVisible(entry, 'overtime')
            const shouldHaveOvertimeDetail = Boolean(overtimeReasonField.value) && overtimeKeys.has(entry.key)

            if (shouldHaveOvertimeDetail && !hasOvertimeDetail) {
                addProjectDetail(entry, 'overtime')
            }
            if (!shouldHaveOvertimeDetail && hasOvertimeDetail) {
                updateProjectEntryDetails(entry, details => details.filter(detail => detail !== 'overtime'))
            }
        })

        if (!requiresOvertimeReason.value && customValues.value[42]) {
            customValues.value[42] = ''
        }
    }
    watch([overtimeReasonField, overtimeProjectEntries], () => {
        syncOvertimeReasonDetail()
    }, { immediate: true })
    const projectTimeBreakSummary = computed(() => {
        const breakMinutes = Number(breakTimeSelect.value || 0)
        if (!breakMinutes) return ''
        return `休憩 ${formatProjectTimeMinutes(breakMinutes)}`
    })
    const projectTimeUnpaidGapSummary = computed(() => {
        if (!projectTimeGapMinutes.value) return ''
        return `中抜け ${formatProjectTimeMinutes(projectTimeGapMinutes.value)}`
    })
    const projectTimeUnpaidGapMessage = computed(() => {
        if (!includesWorkHours.value || !projectTimeGapRanges.value.length) return ''
        const ranges = projectTimeGapRanges.value
            .map(range => `${formatTime(range.start_time)} - ${formatTime(range.end_time)}`)
            .join('、')

        return `${ranges} は中抜けとして自動計算されます。`
    })
    const projectTimeBreakMessage = computed(() => {
        const breakMinutes = Number(breakTimeSelect.value || 0)
        if (!includesWorkHours.value || !breakMinutes) return ''
        return `休憩${formatProjectTimeMinutes(breakMinutes)}は先頭プロジェクトから順に自動控除されます。`
    })
    const hasIncompleteProjectTimeEntry = computed(() => {
        return projectTimeEntries.value.some(entry => entry.project_id || entry.start_time || entry.end_time)
            && projectTimeEntries.value.some(entry => !entry.project_id || !entry.start_time || !entry.end_time)
    })
    const projectTimeWarning = computed(() => {
        if (!includesProjectTimeEntries.value) return ''
        if (hasIncompleteProjectTimeEntry.value) {
            return '未入力のプロジェクト時間があります。'
        }
        if (projectTimeOverlapWarning.value) {
            return projectTimeOverlapWarning.value
        }
        if (includesWorkHours.value && projectWorkTimeTotalMinutes.value && diffInMinutes.value) {
            const difference = projectWorkTimeTotalMinutes.value - diffInMinutes.value;
            if (difference !== 0) {
                const label = formatProjectTimeMinutes(Math.abs(difference))
                return difference > 0 ? `就業時間より${label}多く入力されています。` : `就業時間より${label}少なく入力されています。`
            }
        }
        if (includesTrainingHours.value && projectTrainingTimeTotalMinutes.value && trainingTimeMinutes.value) {
            const difference = projectTrainingTimeTotalMinutes.value - trainingTimeMinutes.value;
            if (difference !== 0) {
                const label = formatProjectTimeMinutes(Math.abs(difference))
                return difference > 0 ? `研修時間より${label}多く入力されています。` : `研修時間より${label}少なく入力されています。`
            }
        }
        return ''
    })
    const confirmOvertime = async() => {
        return new Promise(async(resolve) => {
            const overtime = shift.value.overtime_request.minutes + props.item?.work_time_day
            if(diffInMinutes.value > overtime){
                resolve(await ask(`申請した残業時間を超過しています。<strong>${diffInMinutes.value - props.item?.work_time_day}分</strong>で申請しますか`))
                
            }else if(diffInMinutes.value < overtime){
                const workedOverTime = shift.value?.overtime_request.minutes - (overtime - diffInMinutes.value)
                resolve(await ask(`時間外は<strong>${workedOverTime < 0 ? 0 : workedOverTime}分</strong>になります。よろしいですか。`))               
            } else {
                resolve(await ask('日報を申請します。承認までは修正できます。よろしいですか。'))
            }
        })
    }
    const projectDetailValuesForPayload = (entry) => {
        const values = ensureProjectDetailValues(entry)
        const payload = {}

        if (isProjectDetailVisible(entry, 'allowance')) {
            const allowanceValues = normalizeAllowanceValues(values.allowance)
            values.allowance = allowanceValues
            values.allowance_labels = allowanceLabelsForValues(allowanceValues)

            if (allowanceValues.length) {
                payload.allowance = allowanceValues
                payload.allowance_labels = values.allowance_labels
            }
        }

        if (isProjectDetailVisible(entry, 'incident')) {
            const incident = String(values.incident ?? '').trim()
            if (incident) {
                payload.incident = incident
            }
        }

        if (isProjectDetailVisible(entry, 'overtime')) {
            const overtime = String(values.overtime ?? '').trim()
            if (overtime) {
                payload.overtime = overtime
            }
        }

        if (isProjectDetailVisible(entry, 'mileage')) {
            const mileage = values.mileage ?? {}
            const km = Number(mileage.mileage || 0)
            const gas = Number(mileage.gas_full_price || 0)
            if (km > 0 || gas > 0) {
                payload.mileage = {
                    mileage: km,
                    gas_full_price: gas,
                    gas_consumption: mileage.gas_consumption ?? null,
                    gas_unit_price: mileage.gas_unit_price ?? null,
                }
            }
        }

        if (isProjectDetailVisible(entry, 'vehicle')) {
            const vehicle = values.vehicle ?? {}
            if (vehicle.vehicle !== null && vehicle.vehicle !== undefined && vehicle.vehicle !== '') {
                payload.vehicle = {
                    id: vehicle.id ?? null,
                    vehicle: Number(vehicle.vehicle),
                    alcohol_before_time: vehicle.alcohol_before_time ?? null,
                    alcohol_after_time: vehicle.alcohol_after_time ?? null,
                    alcohol_before_value: vehicle.alcohol_before_value ?? null,
                    alcohol_after_value: vehicle.alcohol_after_value ?? null,
                    confirm_before_user: vehicle.confirm_before_user ?? null,
                    confirm_after_user: vehicle.confirm_after_user ?? null,
                }
            }
        }

        return payload
    }
    const firstVehicleForPayload = () => {
        const entry = projectTimeEntries.value.find(entry => isProjectDetailVisible(entry, 'vehicle'))
        return entry ? (projectDetailValuesForPayload(entry).vehicle ?? null) : null
    }
    const mileageEntriesForPayload = () => projectTimeEntries.value.filter(entry => {
        if (!isProjectDetailVisible(entry, 'mileage')) return false
        return Number(ensureProjectDetailValues(entry).mileage?.mileage || 0) > 0
    })
    const totalProjectMileageForPayload = () => mileageEntriesForPayload().reduce((sum, entry) => {
        return sum + Number(ensureProjectDetailValues(entry).mileage?.mileage || 0)
    }, 0)
    const totalProjectGasForPayload = () => mileageEntriesForPayload().reduce((sum, entry) => {
        return sum + Number(ensureProjectDetailValues(entry).mileage?.gas_full_price || 0)
    }, 0)
    const firstMileageProjectIdForPayload = () => mileageEntriesForPayload()[0]?.project_id ?? null
    const projectIdsWithDetailForPayload = (detailType) => new Set(projectTimeEntries.value
        .filter(entry => isProjectDetailVisible(entry, detailType) && entry.project_id)
        .map(entry => Number(entry.project_id))
    )
    const costsForPayload = () => {
        const expenseEntries = projectTimeEntries.value.filter(entry => isProjectDetailVisible(entry, 'expenses') && entry.project_id)

        return costs
            .map(cost => {
                const entry = expenseEntries.find(expenseEntry => costBelongsToProjectEntry(cost, expenseEntry, expenseEntries))
                if (!entry) return null

                const projectId = Number(entry.project_id)
                return {
                    ...cost,
                    project_id: projectId,
                    timecard_project_segment_id: entry.id ?? cost.timecard_project_segment_id ?? null,
                    project_entry_key: entry.key,
                    project_segment_key: projectSegmentMatchKey(entry),
                    department: cost.department || projectName(projectId),
                }
            })
            .filter(Boolean)
    }
    const actualRowsForPayload = () => {
        const actualProjectIds = projectIdsWithDetailForPayload('actual')

        return actualRows.value.filter(row => actualProjectIds.has(Number(row.project_id)))
    }
    const syncIncentiveCountFromActualRows = () => {
        const incentiveTotal = actualRowsForPayload()
            .filter(row => String(row.status ?? '').includes('インセンティブ'))
            .reduce((sum, row) => sum + Number(row.value ?? 0), 0)

        if (!incentives.value[0]) {
            incentives.value[0] = { count: null }
        }
        incentives.value[0].count = incentiveTotal > 0 ? incentiveTotal : null
    }
    const customValuesForPayload = () => {
        if (includesWorkHours.value) {
            const values = { ...customValues.value }
            const allowanceValues = projectTimeEntries.value
                .filter(entry => isProjectDetailVisible(entry, 'allowance'))
                .flatMap(entry => normalizeAllowanceValues(ensureProjectDetailValues(entry).allowance))
            const incidentValues = projectTimeEntries.value
                .filter(entry => isProjectDetailVisible(entry, 'incident'))
                .map(entry => String(ensureProjectDetailValues(entry).incident ?? '').trim())
                .filter(Boolean)
            const overtimeValues = projectTimeEntries.value
                .filter(entry => isProjectDetailVisible(entry, 'overtime'))
                .map(entry => String(ensureProjectDetailValues(entry).overtime ?? '').trim())
                .filter(Boolean)

            values[37] = allowanceValues.length ? [...new Set(allowanceValues.map(value => Number(value)))] : []
            values[40] = incidentValues.length ? incidentValues.join("\n") : ''
            values[42] = overtimeValues.length ? overtimeValues.join("\n") : ''
            values[44] = projectTimeEntries.value.some(entry => isProjectDetailVisible(entry, 'vehicle')) ? 1 : 0

            return values
        }

        return {
            ...customValues.value,
            37: [],
            40: '',
            42: '',
            44: 0,
        }
    }
    const buildParams = async(status_flag) => {
        return new Promise((resolve) => {
            const firstWorkProject = workProjectEntries.value[0]
            const firstProjectEntry = firstWorkProject ?? projectTimeEntries.value[0]
            const a = {
                customValues: customValuesForPayload(),
                attendance_mode: attendanceMode.value,
                breakTime: includesWorkHours.value ? breakTimeSelect.value : 0,
                start_time: includesWorkHours.value ? formatTime(editStartTime.value) : null,
                end_time: includesWorkHours.value ? formatTime(editEndTime.value) : null,
                training_start_time: includesTrainingHours.value ? formatTime(derivedTrainingStartTime.value) : null,
                training_end_time: includesTrainingHours.value ? formatTime(derivedTrainingEndTime.value) : null,
                day: props.item?.day_full,
                status_flag: status_flag,
                userId: props.item?.user_id,
                overTimeMinute: shift.value?.overtime_request?.minutes,
                costsValues: includesWorkHours.value ? costsForPayload() : [],
                incentiveValues: incentives.value,
                department: firstProjectEntry?.project_id ?? null,
                shiftType: props.item?.shift?.shift_type?.id ?? null,
                vehicleData: includesWorkHours.value ? firstVehicleForPayload() : null,
                car_mileage: includesWorkHours.value ? totalProjectMileageForPayload() : 0,
                car_used_project: includesWorkHours.value ? firstMileageProjectIdForPayload() : null,
                gas_full_price: includesWorkHours.value ? totalProjectGasForPayload() : 0,
                actual_results: includesWorkHours.value ? actualRowsForPayload().map(row => ({
                    ...row,
                    project_id: row.project_id ?? selectedProject.value?.id ?? todayWorkGroup.value ?? null,
                })) : [],
                project_time_entries: includesProjectTimeEntries.value ? projectTimeEntries.value.map((entry, index) => ({
                    id: entry.id,
                    client_key: projectSegmentMatchKey(entry),
                    project_id: entry.project_id,
                    segment_type: entryType(entry),
                    start_time: formatTime(entry.start_time),
                    end_time: formatTime(entry.end_time),
                    minutes: projectTimeEntryMinutes(entry, index),
                    details: Array.isArray(entry.details) ? entry.details : [],
                    detail_values: projectDetailValuesForPayload(entry),
                    comment: entry.comment ?? '',
                    status: entry.status,
                })) : [],
            }
            resolve(a)
        })
    }
    const saveTimeCard = async(action) => {
        if (isLocked.value) {
            ping('承認済みの日報は編集できません。')
            return
        }
        syncOvertimeReasonDetail()
        const validate = await showToastIfEmpty()
        if(!validate) return
        if (includesWorkHours.value && (isInvalidTime(formatTime(editStartTime.value)) || isInvalidTime(formatTime(editEndTime.value)))) {
            ping('就業時間は必須項目です。入力してください。')
            return
        }
        if (action === 'apply' && shift.value?.overtime_request && Number(shift.value.overtime_request.status) !== 2) {
            ping('残業申請の承認が完了してから日報を申請してください。')
            return
        }
        if(includesWorkHours.value && shift.value?.overtime_request){
            if (action === 'apply' && projectTimeWarning.value) {
                ping(`プロジェクト別時間を確認してください。${projectTimeWarning.value}`)
                return
            }
            const confirm = await confirmOvertime()
            if(!confirm.value) return            
        } else if(includesWorkHours.value && action === 'apply'){
            await fifteenMinuteCalc()
            syncProjectBoundaryTimesFromWorkTimes()
            if (projectTimeWarning.value) {
                ping(`プロジェクト別時間を確認してください。${projectTimeWarning.value}`)
                return
            }
            const answer = await ask('日報を申請します。承認までは修正できます。よろしいですか。')
            if(!answer.value) return
        } else if(action === 'apply'){
            if (projectTimeWarning.value) {
                ping(`プロジェクト別時間を確認してください。${projectTimeWarning.value}`)
                return
            }
            const answer = await ask('日報を申請します。承認までは修正できます。よろしいですか。')
            if(!answer.value) return
        }
        syncIncentiveCountFromActualRows()
        // loading.value[status_flag] = true
        const status = action === 'apply' ? 1 : 0
        const params = await buildParams(status)
        await api.post('/save_time_card', params, {
            toast: action === 'save' ? '一時保存しました。' : '申請しました。',
            loadingRef: action === 'save' ? saveLoading : applyLoading,
        })
        
        
        emit('reload')
    }
    const isInvalidTime = (t) => {
        if (!t) return true             // null, undefined, empty
        if (typeof t !== 'string') return true
        if (!/^\d{2}:\d{2}$/.test(t)) return true // format sanity
        const [h, m] = t.split(':').map(Number)
        if (Number.isNaN(h) || Number.isNaN(m)) return true
        if (h > 23 || m > 59) return true
        return false
    }
</script>
<style scoped>
    .project-block-section{
        --work-soft-text: color-mix(in srgb, var(--primary-color) 76%, transparent);
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 35px;
    }
    .registered-mode-field{
        margin-bottom: 18px;
    }
    .attendance-mode-tabs{
        display: flex;
        gap: 8px;
    }
    .attendance-mode-tab{
        padding: 5px 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--calendarBorder);
        background: var(--background-color);
        color: var(--primary-color);
        cursor: pointer;
        font-size: 13px;
    }
    .attendance-mode-tab input{
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .attendance-mode-tab-active{
        border-color: var(--primary-color);
        background: var(--bg3);
    }
    .attendance-mode-tab-disabled{
        cursor: default;
        opacity: .45;
    }
    .project-block-list{
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .project-block{
        display: flex;
        flex-direction: column;
        border: 1px solid var(--calendarBorder);
        background: var(--background-color);
        padding: 16px;
    }
    .project-block-approved{
        border-color: var(--primary-color);
        background: var(--background-color);
    }
    .project-block-head,
    .project-detail-head{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }
    .project-block-title,
    .project-detail-title{
        margin: 0;
        font-size: 14px;
        color: var(--primary-color);
    }
    .project-approved-badge{
        display: inline-flex;
        align-items: center;
        margin-left: 8px;
        padding: 1px 7px;
        border: 1px solid currentColor;
        border-radius: 3px;
        color: var(--primary-color);
        font-size: 11px;
        font-weight: 600;
        vertical-align: middle;
    }
    .project-type-badge{
        display: inline-flex;
        align-items: center;
        margin-left: 8px;
        padding: 1px 6px;
        border: 1px solid currentColor;
        border-radius: 3px;
        background: var(--background-color);
        font-size: 12px;
        font-weight: 400;
        line-height: 1.45;
        vertical-align: middle;
    }
    .project-type-badge-work{
        color: var(--primary-color);
    }
    .project-type-badge-training{
        color: var(--primary-color);
    }
    .project-type-readonly{
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        color: var(--primary-color);
        font-size: 13px;
        font-weight: 400;
    }
    .project-locked-note{
        margin: -4px 0 10px;
        color: var(--primary-color);
        opacity: .72;
        font-size: 12px;
    }
    .project-block-main{
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 12px;
    }
    .project-input-group{
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 0;
        margin: 0;
    }
    .project-input-group span,
    .project-time-duration span{
        font-size: 11px;
        line-height: 1;
        color: var(--primary-color);
        opacity: .65;
    }
    .project-input-group-project{
        flex: 1 1 220px;
        min-width: 220px;
    }
    .project-input-group-type{
        flex: 0 0 110px;
    }
    .project-break-select{
        width: 112px;
    }
    .project-detail{
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid var(--calendarBorder);
    }
    .project-detail-add{
        margin-top: 10px;
    }
    .project-extra-actions{
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid var(--calendarBorder);
        order: 1000;
    }
    .project-extra-button{
        min-height: 30px;
        padding: 0 10px;
        border: 1px solid var(--calendarBorder);
        background: var(--background-color);
        color: var(--work-soft-text);
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        justify-content: center;
        line-height: 1;
    }
    .project-extra-button:hover{
        border-color: color-mix(in srgb, var(--primary-color) 42%, var(--calendarBorder));
        background: var(--bg3);
    }
    .project-inline-input,
    .project-inline-number{
        height: 38px;
        padding: 0 10px;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        box-sizing: border-box !important;
    }
    .project-inline-input{
        min-width: 120px;
    }
    .project-inline-number{
        width: 100px;
    }
    .project-gas-table{
        margin-top: 12px;
    }
    .project-time-section{
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .project-time-header{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .project-time-summary{
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 6px;
        font-size: 13px;
        color: var(--primary-color);
    }
    .project-time-summary span{
        border: 1px solid var(--calendarBorder);
        border-radius: 3px;
        background: var(--background-color);
        padding: 3px 9px;
        white-space: nowrap;
    }
    .project-time-summary-overtime{
        border-color: var(--primary-color) !important;
        font-weight: 500;
    }
    .project-time-project{
        width: 100%;
        max-width: none !important;
    }
    .project-time-range{
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .project-time-input{
        width: 120px;
    }
    .project-time-duration{
        min-width: 84px;
        min-height: 38px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 5px;
        font-size: 13px;
        text-align: right;
        color: var(--primary-color);
        white-space: nowrap;
    }
    .project-time-duration p{
        font-size: 13px;
    }
    .project-time-actions{
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .project-time-action-disabled{
        pointer-events: none;
        opacity: .35;
    }
    .project-time-message{
        margin: 0;
        font-size: 12px;
        color: var(--primary-color);
        opacity: .8;
    }
    .project-time-warning{
        margin: 0;
        font-size: 12px;
        color: #d97706;
    }
    #saveButton{
        padding: 12px 0 4px;
        background: inherit;
    }
    table{
        background-color: var(--background-color);
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
        color: var(--primary-color);
        border-top: 1px solid var(--primary-color);
    }
    table td{
        padding: 10px;
        font-size: 13px;
        border-bottom: 1px solid var(--primary-color);
        border-right: 1px solid var(--primary-color);
    }
    table td:first-child {
        border-left: 1px solid var(--primary-color);
    }
    thead td:first-child{
        border-left: 1px solid var(--primary-color);
    }
    @media (max-width: 768px) {
        .project-time-header{
            align-items: flex-start;
            flex-direction: column;
            gap: 8px;
        }
        .attendance-mode-tabs{
            grid-template-columns: 1fr;
        }
        .project-block-main{
            grid-template-columns: 1fr;
            align-items: stretch;
        }
        .project-input-group-project{
            min-width: 0;
        }
        .project-break-select{
            width: 100%;
        }
        .project-time-range{
            width: 100%;
        }
        .project-time-input{
            flex: 1;
            min-width: 0;
            width: auto;
        }
        .project-time-duration{
            text-align: left;
            min-height: auto;
        }
    }
</style>
