<template>
    <template
        v-for="(segment, segmentIndex) in projectSegmentRows"
        :key="projectSegmentRowKey(segment, segmentIndex)"
    >
        <tr
            :class="[
                'w-row',
                {
                    'last-row': item.last && segmentIndex === projectSegmentRows.length - 1,
                    'compact-blank-row': isCompactBlankRow,
                    'project-segment-extra-row': segmentIndex > 0,
                    'project-chip-open-row': isProjectDetailVisibleRow(segment, segmentIndex) || isOvertimeApproveVisibleRow(segment, segmentIndex),
                },
            ]"
        >
            <td v-if="segmentIndex === 0" class="mobile-project-segment-card-cell">
                <div
                    class="mobile-project-segment-card"
                    :class="{
                        'mobile-project-segment-card-empty': isCompactBlankRow,
                    }"
                >
                    <div class="mobile-project-segment-person">
                        <div class="mobile-project-segment-person-main">
                            <span class="mobile-project-segment-date">{{ dayFormatter }}</span>
                            <p>{{ item.user_name }}</p>
                        </div>
                        <div class="mobile-project-segment-daily-meta">
                            <span v-if="hasHeader('予定')" :class="getShiftClass">
                                {{ item.shift?.status_flag == 2 ? '申請中' : item.shift?.shift_type?.abbreviation }}
                            </span>
                            <span v-if="breakTimeFormatted">{{ breakTimeFormatted }}</span>
                            <span v-if="dayAllowanceLabel">{{ dayAllowanceLabel }}</span>
                            <span v-if="overTimeFormatted">{{ overTimeFormatted }}</span>
                            <span v-if="hasWeather" class="mobile-project-segment-weather">
                                <WeatherIcon :which="item.weather" :size="17"/>
                            </span>
                        </div>
                    </div>

                    <div class="mobile-project-segment-list">
                        <div
                            v-for="(mobileSegment, mobileSegmentIndex) in projectSegmentRows"
                            :key="projectSegmentRowKey(mobileSegment, mobileSegmentIndex)"
                            class="mobile-project-segment-box"
                            :class="{
                                'mobile-project-segment-box-empty': !mobileSegment,
                            }"
                        >
                            <template v-if="mobileSegment">
                                <div class="mobile-project-segment-head">
                                    <div class="mobile-project-segment-title">
                                        <span v-if="props.item.position_id === 15" class="mobile-project-segment-type" :class="`project-segment-type-${segmentType(mobileSegment)}`">
                                            {{ segmentTypeLabel(mobileSegment) }}
                                        </span>
                                        <p>{{ segmentProjectName(mobileSegment) }}</p>
                                        <div v-for="approver in segmentApprovers(mobileSegment)" :key="approver.id" class="flex items-center">
                                            <UserPanel :size="14" :user="approver"/>

                                        </div>
                                    </div>
                                    <div class="mobile-project-segment-status-stack">
                                        <span class="project-segment-status" :class="[projectSegmentStatusClass(mobileSegment), {'shift-sunday': projectSegmentStatusLabel(mobileSegment) === '申請中'}]">
                                            {{ projectSegmentStatusLabel(mobileSegment) }}
                                        </span>
                                        <div
                                            v-for="overtimeRow in overtimeRowsForSegment(mobileSegment, mobileSegmentIndex)"
                                            :key="overtimeRow.key"
                                            class="overtime-row-status"
                                        >
                                            <button
                                                type="button"
                                                class="overtime-status-trigger mobile-overtime-status-trigger"
                                                @click.stop="openOvertimeApproveBox(overtimeRow)"
                                            >
                                                {{ overtimeTriggerProjectLabel(overtimeRow) }}残業 : <span :class="[overtimeStatusClassForRow(overtimeRow), {'shift-sunday': overtimeStatusForRow(overtimeRow) === 1}]">{{ overtimeDisplayForRow(overtimeRow) }}</span>
                                            </button>
                                            <div
                                                v-if="isMobileLayout && isOvertimeApproveBoxOpen(overtimeRow)"
                                                ref="overtimeApproveBox"
                                                class="project-chip-box overtime-approve-box"
                                                :style="overtimeApproveBoxStyle"
                                                @click.stop
                                            >
                                                <div class="overtime-approve-header">
                                                    <span>残業申請</span>
                                                    <strong :class="[overtimeStatusClassForRow(overtimeRow), {'shift-sunday': overtimeStatusForRow(overtimeRow) === 1}]">{{ overtimeDisplayForRow(overtimeRow) }}</strong>
                                                </div>
                                                <div class="overtime-segment-list">
                                                    <div
                                                        v-for="(detailRow, detailRowIndex) in overtimeDetailRowsForRow(overtimeRow)"
                                                        :key="overtimeDetailRowKey(overtimeRow, detailRow, detailRowIndex)"
                                                        class="overtime-segment-row"
                                                    >
                                                        <div class="overtime-segment-main">
                                                            <span class="overtime-segment-project">{{ overtimeProjectNameForRow(detailRow) }}</span>
                                                            <span class="overtime-segment-minutes">{{ overtimeMinutesLabelForRow(detailRow) }}</span>
                                                            <span class="project-segment-status" :class="overtimeStatusClassForRow(detailRow)">
                                                                {{ overtimeStatusLabelForRow(detailRow) }}
                                                            </span>
                                                        </div>
                                                        <div v-if="overtimeContentForRow(detailRow)" class="overtime-segment-content">
                                                            {{ overtimeContentForRow(detailRow) }}
                                                        </div>
                                                        <div v-if="detailRow.requestSegment && canApproveOvertimeSegment(detailRow.requestSegment)" class="project-segment-actions">
                                                            <button type="button" class="project-segment-action approve" @click.stop="emit('approveOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                                                承認
                                                            </button>
                                                            <button type="button" class="project-segment-action reject" @click.stop="emit('rejectOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                                                差戻
                                                            </button>
                                                        </div>
                                                        <div v-else-if="detailRow.requestSegment && canCancelOvertimeSegment(detailRow.requestSegment)" class="project-segment-actions">
                                                            <button type="button" class="project-segment-action cancel" @click.stop="emit('cancelOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                                                取消
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mobile-project-segment-time-row">
                                    <span :class="mobileSegmentIndex === 0 ? startEarly : ''">
                                        {{ projectSegmentTimeLabel(mobileSegment) }}
                                    </span>
                                    <strong>{{ segmentMinutesLabel(mobileSegment) }}</strong>
                                </div>
                                <div v-if="segmentDetailSummary(mobileSegment).length" class="mobile-project-segment-details">
                                    <button
                                        v-for="detail in segmentDetailSummary(mobileSegment)"
                                        :key="`${segmentKey(mobileSegment)}-${detail.type}`"
                                        type="button"
                                        class="project-segment-detail-column-button mobile-project-segment-detail"
                                        :title="detail.title"
                                        @click.stop="toggleProjectDetailBox(mobileSegment, detail)"
                                    >
                                        <span>{{ detail.label }}</span>
                                        <strong v-if="detail.type !== 'comment' && detail.type !== 'incident'">{{ detail.value }}</strong>
                                    </button>
                                    <div
                                        v-if="isProjectDetailBoxOpen(mobileSegment)"
                                        class="project-chip-box mobile-project-chip-box"
                                        :data-segment-key="segmentKey(mobileSegment)"
                                        :class="`project-chip-box-${activeProjectDetail?.type}`"
                                        :style="projectDetailBoxStyle"
                                        @click.stop
                                    >
                                        <div class="project-chip-box-head">
                                            <div>
                                                <p class="!text-sm !text-[var(--primary-color)]">{{ activeProjectDetailLabel }}</p>
                                                <p>{{ projectDetailBoxCaption(mobileSegment) }}</p>
                                            </div>
                                            <button type="button" class="project-chip-box-close" @click="closeProjectDetailBox">×</button>
                                        </div>
                                        <template v-if="activeProjectDetail?.type === 'expenses'">
                                            <div v-if="projectCostsForSegment(mobileSegment).length" class="project-chip-box-list">
                                                <div v-for="cost in projectCostsForSegment(mobileSegment)" :key="projectCostKey(cost)" class="project-chip-box-item">
                                                    <div class="project-chip-box-item-main">
                                                        <span>{{ projectCostTitle(cost) }}</span>
                                                        <span v-if="projectCostAmountLabel(cost)">{{ projectCostAmountLabel(cost) }}</span>
                                                    </div>
                                                    <p v-if="cost.content">{{ cost.content }}</p>
                                                    <div v-if="isCostImage(cost)" class="project-chip-box-file">
                                                        <img @click="previewCostFile(cost)" loading="lazy" v-if="cost?.file_path" :src="costFileUrl(cost)"/>
                                                    </div>
                                                    <div v-else-if="cost.file_path" class="project-chip-box-file">
                                                        <div class="cursor-pointer" style="position:relative;" @click="previewCostFile(cost)">
                                                            <FileIcon :ext="costFileExtension(cost)"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="project-chip-box-empty">詳細はありません。</div>
                                        </template>
                                        <div v-else-if="activeProjectDetail?.type === 'comment'" class="project-chip-box-body project-chip-box-comment-body">
                                            <template v-if="commentBoxSectionsForSegment(mobileSegment).length">
                                                <div
                                                    v-for="section in commentBoxSectionsForSegment(mobileSegment)"
                                                    :key="section.type"
                                                    class="project-chip-box-comment-section"
                                                >
                                                    <p>{{ section.label }}</p>
                                                    <div>{{ section.text }}</div>
                                                </div>
                                            </template>
                                            <span v-else class="project-chip-box-empty">詳細はありません。</span>
                                        </div>
                                        <div v-else class="project-chip-box-body">
                                            <template v-if="projectDetailBoxText(mobileSegment)">
                                                {{ projectDetailBoxText(mobileSegment) }}
                                            </template>
                                            <span v-else class="project-chip-box-empty">詳細はありません。</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mobile-project-segment-action">
                                    <CommandButton
                                        v-if="mobileSegmentIndex === 0 && mobileStampButtons.length"
                                        :buttons="mobileStampButtons"
                                    />
                                    <CommandButton
                                        v-if="hasReportAction(mobileSegment, mobileSegmentIndex)"
                                        :buttons="[{title: '報告', action:() => emit('procedureStart', item, reportActionSegment(mobileSegment))}]"
                                    />
                                </div>
                            </template>

                            <template v-else>
                                <div class="mobile-project-segment-empty-body">
                                    <span>{{ item?.time_card?.status_flag ? getStatusText : '日報なし' }}</span>
                                    <div
                                        v-for="overtimeRow in overtimeRowsForSegment(mobileSegment, mobileSegmentIndex)"
                                        :key="overtimeRow.key"
                                        class="overtime-row-status mobile-empty-overtime-row"
                                    >
                                        <button
                                            type="button"
                                            class="overtime-status-trigger mobile-overtime-status-trigger"
                                            @click.stop="openOvertimeApproveBox(overtimeRow)"
                                        >
                                            {{ overtimeTriggerProjectLabel(overtimeRow) }}残業 : <span :class="[overtimeStatusClassForRow(overtimeRow), {'shift-sunday': overtimeStatusForRow(overtimeRow) === 1}]">{{ overtimeDisplayForRow(overtimeRow) }}</span>
                                        </button>
                                        <div
                                            v-if="isMobileLayout && isOvertimeApproveBoxOpen(overtimeRow)"
                                            ref="overtimeApproveBox"
                                            class="project-chip-box overtime-approve-box"
                                            :style="overtimeApproveBoxStyle"
                                            @click.stop
                                        >
                                            <div class="overtime-approve-header">
                                                <span>残業申請</span>
                                                <strong :class="[overtimeStatusClassForRow(overtimeRow), {'shift-sunday': overtimeStatusForRow(overtimeRow) === 1}]">{{ overtimeDisplayForRow(overtimeRow) }}</strong>
                                            </div>
                                            <div class="overtime-segment-list">
                                                <div
                                                    v-for="(detailRow, detailRowIndex) in overtimeDetailRowsForRow(overtimeRow)"
                                                    :key="overtimeDetailRowKey(overtimeRow, detailRow, detailRowIndex)"
                                                    class="overtime-segment-row"
                                                >
                                                    <div class="overtime-segment-main">
                                                        <span class="overtime-segment-project">{{ overtimeProjectNameForRow(detailRow) }}</span>
                                                        <span class="overtime-segment-minutes">{{ overtimeMinutesLabelForRow(detailRow) }}</span>
                                                        <span class="project-segment-status" :class="overtimeStatusClassForRow(detailRow)">
                                                            {{ overtimeStatusLabelForRow(detailRow) }}
                                                        </span>
                                                    </div>
                                                    <div v-if="overtimeContentForRow(detailRow)" class="overtime-segment-content">
                                                        {{ overtimeContentForRow(detailRow) }}
                                                    </div>
                                                    <div v-if="detailRow.requestSegment && canApproveOvertimeSegment(detailRow.requestSegment)" class="project-segment-actions">
                                                        <button type="button" class="project-segment-action approve" @click.stop="emit('approveOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                                            承認
                                                        </button>
                                                        <button type="button" class="project-segment-action reject" @click.stop="emit('rejectOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                                            差戻
                                                        </button>
                                                    </div>
                                                    <div v-else-if="detailRow.requestSegment && canCancelOvertimeSegment(detailRow.requestSegment)" class="project-segment-actions">
                                                        <button type="button" class="project-segment-action cancel" @click.stop="emit('cancelOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                                            取消
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <CommandButton
                                        v-if="mobileSegmentIndex === 0 && mobileStampButtons.length"
                                        :buttons="mobileStampButtons"
                                    />
                                    <CommandButton
                                        v-if="hasReportAction(mobileSegment, mobileSegmentIndex)"
                                        :buttons="[{title: '報告', action:() => emit('procedureStart', item, reportActionSegment(mobileSegment))}]"
                                    />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </td>
            <td
                v-if="segmentIndex === 0"
                class="work-date-cell"
                :rowspan="projectSegmentRowspan"
                :class="[getDayClass, {'working' : item.time_card?.stamp_flag == 0}]"
                :data-work-day="displayDayFull"
            >
                <div class="td-first">{{ dayFormatter }}</div>
            </td>
            <td
                v-if="segmentIndex === 0"
                class="work-member-cell"
                :rowspan="projectSegmentRowspan"
            >
                <div class="work-member-content" :class="{'work-member-content-with-weather': hasWeather}">
                    <span class="work-member-name">
                        {{ item.user_name }}
                    </span>
                    <span v-if="hasWeather" class="work-member-weather">
                        <WeatherIcon :which="item.weather" :size="17"/>
                    </span>
                </div>
            </td>
            <!-- <td v-if="segmentIndex === 0" class="daily-condition-cell" :rowspan="projectSegmentRowspan">
                <div v-if="item.weather !== null" class="condition-area">
                    <div>{{ responsive.mobile ? 'コンディション : ' : '' }}</div>
                    <WeatherIcon :which="item.weather" :size="17"/>
                </div>
            </td> -->
            <td
                v-if="segmentIndex === 0 && hasHeader('予定')"
                class="work-shift-cell"
                :rowspan="projectSegmentRowspan"
                :class="getShiftClass"
            >
                {{ item.shift?.status_flag == 2 ? '申請中' : item.shift?.shift_type?.abbreviation }}
            </td>
            
            <td class="project-segment-time-cell" :class="segmentIndex === 0 ? startEarly : ''">
                <div v-if="segmentIndex === 0 && item.ability.start_stamp" class="w-hover-button mb-space">
                    <CommandButton :buttons="[{title: '始業', action:() => start(item)}]"/>
                </div>
                <div v-else>{{ segmentStartTimeLabel(segment) }}</div>
            </td>
            <td class="project-segment-time-cell" :class="segmentIndex === projectSegmentRows.length - 1 ? goLately : ''">
                <div v-if="segmentIndex === 0 && item.ability.end_stamp" class="w-hover-button mb-space">
                    <CommandButton :buttons="[{title: '終業', action: () => end(item)}]"/>
                </div>
                <div v-else>{{ segmentEndTimeLabel(segment) }}</div>
            </td>
            <td class="project-segment-minute-cell">
                {{ segmentWorkMinutesLabel(segment) }}
            </td>
            <td v-if="hasHeader('研修時間')" class="project-segment-minute-cell">{{ segmentTrainingMinutesLabel(segment) }}</td>
            <td v-if="segmentIndex === 0" class="daily-overtime-cell" :rowspan="projectSegmentRowspan">{{ overTimeFormatted }}</td>
            <td v-if="segmentIndex === 0" class="daily-break-cell" :rowspan="projectSegmentRowspan">
                <div style="white-space: pre-wrap;" v-if="item.time_card?.stamp_flag == 1">{{ breakTimeFormatted }}</div>
                <div v-if="item.ability.break_stamp" class="w-hover-button mb-space">
                    <CommandButton :buttons="[{title: item.time_card?.stamp_flag == 0 ? '休憩' : '再開', action:() => takeBreak(item)}]"/>
                </div>
            </td>
            <td
                class="project-segment-cell"
                :class="{'project-segment-cell-empty': !segment}"
            >
                <span v-if="segment" class="project-segment-name">{{ segmentProjectName(segment) }}</span>
            </td>
            <td class="project-segment-cell"
                :class="{'project-segment-cell-empty': !segment}"
            >
                <div v-for="approver in segmentApprovers(segment)" :key="approver.id" class="flex items-center justify-center">
                    <UserPanel :size="18" :user="approver"/>
                </div>
            </td>
            <td
                v-if="segmentIndex === 0"
                class="project-segment-detail-column project-segment-detail-column-allowance"
                :rowspan="projectSegmentRowspan"
            >
                <button
                    v-if="dayAllowanceLabel"
                    type="button"
                    class="project-segment-detail-column-button"
                    :title="dayAllowanceLabel"
                    @click.stop="toggleProjectDetailBox(dayAllowanceSegment, dayAllowanceDetailColumn)"
                >
                    {{ shortDetailText(dayAllowanceLabel) }}
                </button>
                <div
                    v-if="dayAllowanceSegment && isProjectDetailBoxOpen(dayAllowanceSegment, 'allowance')"
                    class="project-chip-box project-chip-box-allowance"
                    :data-segment-key="segmentKey(dayAllowanceSegment)"
                    :style="projectDetailBoxStyle"
                    @click.stop
                >
                    <div class="project-chip-box-head">
                        <div>
                            <p class="!text-sm !text-[var(--primary-color)]">諸手当</p>
                            <p>{{ dayFormatter }} ・ 1日単位</p>
                        </div>
                        <button type="button" class="project-chip-box-close" @click="closeProjectDetailBox">×</button>
                    </div>
                    <pre>{{ dayAllowanceLabel }}</pre>
                </div>
            </td>
            <td
                v-for="detailColumn in segmentLeadDetailColumns"
                :key="detailColumn.type"
                class="project-segment-detail-column"
                :class="`project-segment-detail-column-${detailColumn.type}`"
            >
                <button
                    v-if="segmentDetailColumnValue(segment, detailColumn.type)"
                    type="button"
                    class="project-segment-detail-column-button"
                    :title="segmentDetailColumnTitle(segment, detailColumn.type)"
                    @click.stop="toggleProjectDetailBox(segment, detailColumn)"
                >
                    {{ segmentDetailColumnValue(segment, detailColumn.type) }}
                </button>
                <div
                    v-if="segment && isProjectDetailBoxOpen(segment, detailColumn.type)"
                    class="project-chip-box"
                    :data-segment-key="segmentKey(segment)"
                    :class="`project-chip-box-${detailColumn.type}`"
                    :style="projectDetailBoxStyle"
                    @click.stop
                >
                    <div class="project-chip-box-head">
                        <div>
                            <p class="!text-sm !text-[var(--primary-color)]">{{ activeProjectDetailLabel }}</p>
                            <p>{{ projectDetailBoxCaption(segment) }}</p>
                        </div>
                        <button type="button" class="project-chip-box-close" @click="closeProjectDetailBox">×</button>
                    </div>
                    <template v-if="detailColumn.type === 'expenses'">
                        <div v-if="projectCostsForSegment(segment).length" class="project-chip-box-list">
                            <div v-for="cost in projectCostsForSegment(segment)" :key="projectCostKey(cost)" class="project-chip-box-item">
                                <div class="project-chip-box-item-main">
                                    <span>{{ projectCostTitle(cost) }}</span>
                                    <span v-if="projectCostAmountLabel(cost)">{{ projectCostAmountLabel(cost) }}</span>
                                </div>
                                <p v-if="cost.content">{{ cost.content }}</p>
                                <div v-if="isCostImage(cost)" class="project-chip-box-file">
                                    <img @click="previewCostFile(cost)" loading="lazy" v-if="cost?.file_path" :src="costFileUrl(cost)"/>
                                </div>
                                <div v-else-if="cost.file_path" class="project-chip-box-file">
                                    <div class="cursor-pointer" style="position:relative;" @click="previewCostFile(cost)">
                                        <FileIcon :ext="costFileExtension(cost)"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="project-chip-box-empty">詳細はありません。</div>
                    </template>
                    <div v-else-if="detailColumn.type === 'comment'" class="project-chip-box-body project-chip-box-comment-body">
                        <template v-if="commentBoxSectionsForSegment(segment).length">
                            <div
                                v-for="section in commentBoxSectionsForSegment(segment)"
                                :key="section.type"
                                class="project-chip-box-comment-section"
                            >
                                <p>{{ section.label }}</p>
                                <div>{{ section.text }}</div>
                            </div>
                        </template>
                        <span v-else class="project-chip-box-empty">詳細はありません。</span>
                    </div>
                    <div v-else class="project-chip-box-body">
                        <template v-if="projectDetailBoxText(segment)">
                            {{ projectDetailBoxText(segment) }}
                        </template>
                        <span v-else class="project-chip-box-empty">詳細はありません。</span>
                    </div>
                </div>
            </td>
            <td
                v-for="detailColumn in segmentTailDetailColumns"
                :key="detailColumn.type"
                class="project-segment-detail-column"
                :class="`project-segment-detail-column-${detailColumn.type}`"
            >
                <button
                    v-if="segmentDetailColumnValue(segment, detailColumn.type)"
                    type="button"
                    class="project-segment-detail-column-button"
                    :title="segmentDetailColumnTitle(segment, detailColumn.type)"
                    @click.stop="toggleProjectDetailBox(segment, detailColumn)"
                >
                    {{ segmentDetailColumnValue(segment, detailColumn.type) }}
                </button>
                <div
                    v-if="segment && isProjectDetailBoxOpen(segment, detailColumn.type)"
                    class="project-chip-box"
                    :data-segment-key="segmentKey(segment)"
                    :class="`project-chip-box-${detailColumn.type}`"
                    :style="projectDetailBoxStyle"
                    @click.stop
                >
                    <div class="project-chip-box-head">
                        <div>
                            <p class="!text-sm !text-[var(--primary-color)]">{{ activeProjectDetailLabel }}</p>
                            <p>{{ projectDetailBoxCaption(segment) }}</p>
                        </div>
                        <button type="button" class="project-chip-box-close" @click="closeProjectDetailBox">×</button>
                    </div>
                    <template v-if="detailColumn.type === 'expenses'">
                        <div v-if="projectCostsForSegment(segment).length" class="project-chip-box-list">
                            <div v-for="cost in projectCostsForSegment(segment)" :key="projectCostKey(cost)" class="project-chip-box-item">
                                <div class="project-chip-box-item-main">
                                    <span>{{ projectCostTitle(cost) }}</span>
                                    <span v-if="projectCostAmountLabel(cost)">{{ projectCostAmountLabel(cost) }}</span>
                                </div>
                                <p v-if="cost.content">{{ cost.content }}</p>
                                <div v-if="isCostImage(cost)" class="project-chip-box-file">
                                    <img @click="previewCostFile(cost)" loading="lazy" v-if="cost?.file_path" :src="costFileUrl(cost)"/>
                                </div>
                                <div v-else-if="cost.file_path" class="project-chip-box-file">
                                    <div class="cursor-pointer" style="position:relative;" @click="previewCostFile(cost)">
                                        <FileIcon :ext="costFileExtension(cost)"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="project-chip-box-empty">詳細はありません。</div>
                    </template>
                    <div v-else-if="detailColumn.type === 'comment'" class="project-chip-box-body project-chip-box-comment-body">
                        <template v-if="commentBoxSectionsForSegment(segment).length">
                            <div
                                v-for="section in commentBoxSectionsForSegment(segment)"
                                :key="section.type"
                                class="project-chip-box-comment-section"
                            >
                                <p>{{ section.label }}</p>
                                <div>{{ section.text }}</div>
                            </div>
                        </template>
                        <span v-else class="project-chip-box-empty">詳細はありません。</span>
                    </div>
                    <div v-else class="project-chip-box-body">
                        <template v-if="projectDetailBoxText(segment)">
                            {{ projectDetailBoxText(segment) }}
                        </template>
                        <span v-else class="project-chip-box-empty">詳細はありません。</span>
                    </div>
                </div>
            </td>
        <!-- <td v-if="hasHeader('インセンティブ')">
            <div style="position: relative;word-break: auto-phrase;" class="w-hover-button">
                <div v-if="responsive.mobile && item.time_card?.timecard_incentives.length">インセンティブ : </div>
                <div>{{ incentiveCount}}</div>
                <div @click="menu.close()" class="comment-box" id="incentiveBox" v-if="menu.name == 'incentiveBox' && menu.id == item.time_card?.id">
                    <div v-for="incentive in item.time_card?.timecard_incentives" :key="incentive.id">
                        <div>{{ `${incentive.count ? incentive.count + '件' : ''}` }}</div>
                        <img @click="previewImage(incentive.file)" style="height:120px;cursor: pointer;" v-if="incentive?.file" :src="`/cdn/timecard_files/${incentive?.file?.id}_${incentive?.file?.user_id}_${incentive?.file?.path}.${incentive?.file?.extension}`"/>
                    </div>
                </div>
            </div>
        </td> -->
        
        <td class="report-status-cell">
            <div>
                <div>
                    <div
                        v-for="overtimeRow in overtimeRowsForSegment(segment, segmentIndex)"
                        :key="overtimeRow.key"
                        class="overtime-row-status"
                    >
                        <button
                            type="button"
                            class="overtime-status-trigger"
                            @click.stop="openOvertimeApproveBox(overtimeRow)"
                        >
                            {{ overtimeTriggerProjectLabel(overtimeRow) }}残業 : <span :class="[overtimeStatusClassForRow(overtimeRow)]">{{ overtimeDisplayForRow(overtimeRow) }}</span>
                        </button>
                        <div
                            v-if="!isMobileLayout && isOvertimeApproveBoxOpen(overtimeRow)"
                            ref="overtimeApproveBox"
                            class="project-chip-box overtime-approve-box"
                            :style="overtimeApproveBoxStyle"
                            @click.stop
                        >
                            <div class="overtime-approve-header">
                                <span>残業申請</span>
                                <strong :class="[overtimeStatusClassForRow(overtimeRow)]">{{ overtimeDisplayForRow(overtimeRow) }}</strong>
                            </div>
                            <div class="overtime-segment-list">
                                <div
                                    v-for="(detailRow, detailRowIndex) in overtimeDetailRowsForRow(overtimeRow)"
                                    :key="overtimeDetailRowKey(overtimeRow, detailRow, detailRowIndex)"
                                    class="overtime-segment-row"
                                >
                                    <div class="overtime-segment-main">
                                        <span class="overtime-segment-project">{{ overtimeProjectNameForRow(detailRow) }}</span>
                                        <span class="overtime-segment-minutes">{{ overtimeMinutesLabelForRow(detailRow) }}</span>
                                        <span class="project-segment-status" :class="overtimeStatusClassForRow(detailRow)">
                                            {{ overtimeStatusLabelForRow(detailRow) }}
                                        </span>
                                    </div>
                                    <div v-if="overtimeContentForRow(detailRow)" class="overtime-segment-content">
                                        {{ overtimeContentForRow(detailRow) }}
                                    </div>
                                    <div v-if="detailRow.requestSegment && canApproveOvertimeSegment(detailRow.requestSegment)" class="project-segment-actions">
                                        <button type="button" class="project-segment-action approve" @click.stop="emit('approveOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                            承認
                                        </button>
                                        <button type="button" class="project-segment-action reject" @click.stop="emit('rejectOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                            差戻
                                        </button>
                                    </div>
                                    <div v-else-if="detailRow.requestSegment && canCancelOvertimeSegment(detailRow.requestSegment)" class="project-segment-actions">
                                        <button type="button" class="project-segment-action cancel" @click.stop="emit('cancelOvertimeSegment', detailRow.requestSegment, detailRow.requestSegmentIndex, item)">
                                            取消
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="segment">
                        <span class="project-segment-status" :class="[projectSegmentStatusClass(segment), {'shift-sunday': projectSegmentStatusLabel(segment) === '申請中'}]">
                            {{ projectSegmentStatusLabel(segment) }}
                        </span>
                    </div>
                    <div v-else-if="item?.time_card?.status_flag">
                        <span class="daily-report-status" :class="[dailyReportStatusClass, {'shift-sunday': getStatusText === '申請中'}]">{{ getStatusText }}</span>
                    </div>
                </div>
                
            </div>
            
        </td>
        <td class="report-action-cell">
            <div class="report-action-wrapper center-mobile">
                <CommandButton
                    v-if="hasReportAction(segment, segmentIndex)"
                    :buttons="[{title: '報告', action:() => emit('procedureStart', item, reportActionSegment(segment))}]"
                />
            </div>
        </td>
        </tr>
    </template>
</template>
<script setup>
import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useResponsive } from '@/store/responsive';
import { useMenuStore } from "@/store/menu";
import CommandButton from '../Global/CommandButton.vue';
import { fileExtensionFromPath, filePreviewTypeFromPath, vehicleAsOptions, workFilePreview } from '../../utils/workApi';
import FileIcon from '../Board/Mixed/FileIcon.vue';
import WeatherIcon from '../Global/WeatherIcon.vue';
import { DateTime } from 'luxon';
import { customParser } from '@/utils/tools';
import { useAuthUserStore } from '@/store/auth';
import UserPanel from '../Global/UserPanel.vue';
const menu = useMenuStore()
const responsive = useResponsive()
const auth = useAuthUserStore()
const costOptions = [{label: '交通費', value: 1},
                    {label:'通信費', value: 2},
                    {label:'宿泊費', value: 3},
                    {label: '旅費交通費', value: 4},
                    {label:'消耗品費', value: 5},
                    {label:'交際費', value: 6},
                    {label:'支払手数料', value: 7},
                    {label:'福利厚生費', value: 8}]
const {start, end, takeBreak } = inject('stamps')
const props = defineProps({
    item: {type: Object, default: null},
    hasHeader: {type: Function},
    holidays: {type: Array, default: []},
    wrapper: {type: HTMLDivElement},
    workGroups: {type: Array, default: () => []},
})
const emit = defineEmits([
    'procedureStart',
    'approveProjectSegment',
    'rejectProjectSegment',
    'approveOvertimeSegment',
    'rejectOvertimeSegment',
    'cancelOvertimeSegment',
])
const PROJECT_DETAIL_OPEN_EVENT = 'work-record-project-detail-open'
const projectDetailInstanceId = Math.random().toString(36).slice(2)
const activeProjectDetail = ref(null)
const projectDetailBoxTopOffset = ref(0)
const projectDetailBoxMaxHeight = ref('')
const overtimeApproveBox = ref(null)
const overtimeApproveBoxTopOffset = ref(0)
const activeOvertimeBoxKey = ref(null)
const viewportIsMobile = ref(false)
const syncMobileLayout = () => {
    if (typeof window === 'undefined') return
    viewportIsMobile.value = window.matchMedia('(max-width: 959px)').matches
}
const isMobileLayout = computed(() => responsive.mobile || viewportIsMobile.value)
const projectDetailBoxStyle = computed(() => ({
    top: `calc(100% + 4px + ${projectDetailBoxTopOffset.value}px)`,
    maxHeight: projectDetailBoxMaxHeight.value || undefined,
}))
const overtimeApproveBoxStyle = computed(() => ({
    top: `calc(100% + 4px + ${overtimeApproveBoxTopOffset.value}px)`,
}))
const segmentDetailColumns = [
    { type: 'allowance', label: '諸手当' },
    { type: 'incident', label: 'インシデント' },
    { type: 'comment', label: 'コメント' },
    { type: 'expenses', label: '経費' },
    { type: 'actual', label: '実績' },
    { type: 'vehicle', label: '車両使用' },
    { type: 'mileage', label: 'マイカー使用' },
]
const dayAllowanceDetailColumn = segmentDetailColumns.find(column => column.type === 'allowance')
const segmentLeadDetailColumns = segmentDetailColumns.filter(column => ['incident'].includes(column.type))
const segmentTailDetailColumns = segmentDetailColumns.filter(column => !['allowance', 'incident'].includes(column.type))
const displayDayFull = computed(() => {
    return responsive.mobile ? (props.item.mobile_day_full ?? props.item.day_full) : props.item.day_full
})
const displayDayShow = computed(() => {
    return responsive.mobile ? (props.item.mobile_day_show ?? props.item.day_show) : props.item.day_show
})
const getDayClass = computed(() => {
    const date = displayDayFull.value
    const dateInstance = DateTime.fromISO(date)
    return {
        'shift-saturday': dateInstance.weekday === 6,
        'shift-sunday': dateInstance.weekday === 7,
        'shift-everyholiday' : props.holidays.find(h => DateTime.fromJSDate(h.date).hasSame(dateInstance, 'day')),
        'today' : date === DateTime.now().toISODate(),
    }
})
const timeCard = computed(() => props.item.time_card)
const selectedProject = computed(() => {
    return props.item?.time_card?.department
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
const projectSegmentSortAnchor = (segments) => {
    const start = segmentTimeToMinutes(timeCard.value?.start_time)
    const end = segmentTimeToMinutes(timeCard.value?.end_time)
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
const sortProjectSegmentsByTime = (segments = []) => {
    const anchor = projectSegmentSortAnchor(segments)
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
const projectSegments = computed(() => {
    if (Array.isArray(timeCard.value?.project_segments) && timeCard.value.project_segments.length) {
        return sortProjectSegmentsByTime(timeCard.value.project_segments)
    }
    if (!timeCard.value) return []

    const project = timeCard.value.department ?? props.item?.shift?.department ?? null
    if (!project && !timeCard.value.start_time && !timeCard.value.end_time) return []

    return [{
        id: `legacy-${timeCard.value.id}`,
        project_id: timeCard.value.work_group_id ?? project?.id ?? props.item?.shift?.department_id ?? null,
        project,
        segment_type: 'work',
        start_time: timeCard.value.start_time,
        end_time: timeCard.value.end_time,
        minutes: timeCard.value.work_time,
        status: null,
        details: [],
        comment: null,
        legacy: true,
    }]
})
const projectSegmentRows = computed(() => {
    return projectSegments.value.length ? projectSegments.value : [null]
})
const projectSegmentRowspan = computed(() => projectSegmentRows.value.length)
const projectSegmentRowKey = (segment, index) => {
    return segment ? segmentKey(segment) : `blank-${props.item?.user_id ?? 'user'}-${props.item?.day_full ?? index}`
}
const segmentKey = (segment) => {
    return segment?.id ?? `${segment?.project_id}-${segment?.start_time}-${segment?.end_time}`
}
const segmentMinutesLabel = (segment) => {
    const minutes = Number(segment?.minutes ?? 0)
    if (!minutes) return ''
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60
    if (hours && remainingMinutes) return `${hours}時間${remainingMinutes}分`
    if (hours) return `${hours}時間`
    return `${remainingMinutes}分`
}
const segmentProjectId = (segment) => {
    return Number(segment?.project_id ?? segment?.project?.id ?? 0)
}
const segmentProjectName = (segment) => {
    if (segment?.project?.name) return segment.project.name
    const projectId = segmentProjectId(segment)
    return props.workGroups?.find(group => Number(group?.id) === projectId)?.name ?? '未設定'
}
const segmentProjectManager = (segment) => {
    const manager = segment?.project?.manager

    if (manager?.length) {
        return manager
    }

    const projectId = Number(segmentProjectId(segment))

    const group = props.workGroups?.find(
        ({ id }) => Number(id) === projectId
    )

    return (
        group?.manager ??
        (Number(timeCard.value?.department?.id) === projectId
            ? timeCard.value?.department?.manager
            : null)
    )
}
const normalizedUserList = (users) => {
    if (!users) return []
    return (Array.isArray(users) ? users : [users]).filter(Boolean)
}

const segmentApprovers = (segment) => {
    if (!segment) return []
    if (segment?.approver) return [segment.approver]

    const targetUserId = Number(props.item?.user_id)
    const managers = normalizedUserList(segmentProjectManager(segment))
        .filter(manager => Number(manager?.id) !== targetUserId)

    if (managers.length) return managers

    return Number(props.item?.position_id) === 6
        ? normalizedUserList(props.item?.admin_approvers)
        : []
}

const projectForSegment = (segment) => {
    if (segment?.project) return segment.project
    const projectId = segmentProjectId(segment)
    return props.workGroups?.find(group => Number(group?.id) === projectId)
        ?? (Number(timeCard.value?.department?.id) === projectId ? timeCard.value.department : null)
}
const projectForCase = (actualCase, segment = null) => {
    if (actualCase?.project) return actualCase.project
    const projectId = Number(actualCase?.project_record_id ?? 0)
    if (projectId) {
        return props.workGroups?.find(group => Number(group?.id) === projectId)
            ?? (Number(segment?.project_id ?? segment?.project?.id) === projectId ? projectForSegment(segment) : null)
            ?? (Number(timeCard.value?.department?.id) === projectId ? timeCard.value.department : null)
    }

    return segment ? projectForSegment(segment) : selectedProject.value
}
const unitLabelForProject = (project) => {
    const code = project?.unit_id ?? 'JPY'
    if (code === 'COUNT') return '件'
    if (code === 'HOUR') return '時間'
    if (code === 'CUSTOM') return project?.custom_unit_label || '単位'
    return '円'
}
const unitLabelForCase = (actualCase, segment = null) => {
    return unitLabelForProject(projectForCase(actualCase, segment))
}
const formatActualAmount = (value, unitLabel) => {
    const amount = toNum(value)
    return `${yenFmt.format(amount)}${unitLabel}`
}
const segmentType = (segment) => segment?.segment_type === 'training' ? 'training' : 'work'
const segmentTypeLabel = (segment) => segmentType(segment) === 'training' ? '研修' : '就業'
const segmentWorkMinutesLabel = (segment) => {
    if (!segment) return workTimeFormatted.value
    return segmentType(segment) === 'work' ? segmentMinutesLabel(segment) : ''
}
const segmentTrainingMinutesLabel = (segment) => {
    if (!segment) return trainTimeFormatted.value
    return segmentType(segment) === 'training' ? segmentMinutesLabel(segment) : ''
}
const segmentDetails = (segment) => {
    return Array.isArray(segment?.details) ? segment.details : []
}
const segmentHasDetail = (segment, type) => {
    return segmentDetails(segment).includes(type)
}
const hasAnySegmentDetail = (type) => {
    return projectSegments.value.some(segment => segmentHasDetail(segment, type))
}
const shouldUseLegacyGlobalDetail = (segment, type, hasValue) => {
    return Boolean(hasValue) && !hasAnySegmentDetail(type) && projectSegments.value[0] === segment
}
const segmentHasVisibleDetail = (segment, type, hasValue) => {
    if (segmentHasDetail(segment, type)) {
        return true
    }

    return shouldUseLegacyGlobalDetail(segment, type, hasValue)
}
const segmentDetailValues = (segment) => {
    return segment?.detail_values && typeof segment.detail_values === 'object' ? segment.detail_values : {}
}
const firstSegmentWithDetail = (type) => {
    return projectSegments.value.find(segment => segmentHasDetail(segment, type))
}
const projectSegmentStatusLabel = (segment) => {
    if (segment?.legacy) {
        return dailyReportStatusLabel(timeCard.value?.status_flag)
    }
    const statuses = {
        draft: '作成中',
        submitted: '申請中',
        approved: '承認済',
        rejected: '差戻',
    }
    return statuses[segment?.status] ?? ''
}
const dailyReportStatusLabel = (statusFlag) => {
    const statuses = {
        0: '作成中',
        1: '申請中',
        2: '承認済',
        10: '差戻中',
    }
    return statuses[Number(statusFlag)] ?? ''
}
const projectSegmentStatusClass = (segment) => {
    return `project-segment-status-${segment?.legacy ? `daily-${timeCard.value?.status_flag ?? 'none'}` : (segment?.status ?? 'draft')}`
}
const segmentAbility = (segment) => segment?.ability ?? {}
const canApproveProjectSegment = (segment) => Boolean(segmentAbility(segment).approve)
const canCancelProjectSegment = (segment) => Boolean(segmentAbility(segment).cancel)
const canEditProjectSegment = (segment) => Boolean(segmentAbility(segment).edit)
const firstSegmentForProject = (segment) => {
    const projectId = segmentProjectId(segment)
    return projectSegments.value.find(rowSegment => Number(segmentProjectId(rowSegment)) === projectId)
}
const isFirstSegmentForProject = (segment) => firstSegmentForProject(segment) === segment
const isSegmentLinkedRecord = (record) => Number(record?.timecard_project_segment_id ?? 0) > 0
const projectCostsForSegment = (segment) => {
    const costs = timeCard.value?.timecard_costs ?? []
    const segmentId = Number(segment?.id ?? 0)

    if (segmentId > 0) {
        const exactCosts = costs.filter(cost => Number(cost?.timecard_project_segment_id ?? 0) === segmentId)
        if (exactCosts.length || costs.some(isSegmentLinkedRecord)) {
            return exactCosts
        }
    }

    if (!isFirstSegmentForProject(segment)) return []

    const projectId = segmentProjectId(segment)
    const projectName = segmentProjectName(segment)
    return costs.filter(cost => {
        if (isSegmentLinkedRecord(cost)) return false
        const costProjectId = Number(cost?.project_id ?? 0)
        if (costProjectId > 0) return costProjectId === projectId
        return cost.department === projectName
    })
}
const costSummaryForSegment = (segment) => {
    const costs = projectCostsForSegment(segment)
    if (!costs.length) return ''
    const total = costs.reduce((sum, cost) => sum + toNum(cost.expenses), 0)

    return `${costs.length}件 ${yenFmt.format(total)}円`
}
const costDetailSummaryForSegment = (segment) => {
    const costs = projectCostsForSegment(segment)
    if (!costs.length) return ''

    return costs.map(cost => {
        const label = hasWorkCostLabel(cost) ?? '経費'
        const expense = cost.expenses !== null && cost.expenses !== undefined ? `${yenFmt.format(toNum(cost.expenses))}円` : ''
        return [label, expense].filter(Boolean).join(':')
    }).join('\n')
}
const actualCasesForSegment = (segment) => {
    if (!isFirstSegmentForProject(segment)) return []

    const projectId = segmentProjectId(segment)
    return (timeCard.value?.project_case ?? []).filter(actualCase => Number(actualCase.project_record_id) === projectId)
}
const actualSummaryForSegment = (segment) => {
    const cases = actualCasesForSegment(segment)
    if (!cases.length) return ''

    return cases.map(actualCase => {
        const label = actualCase.status ?? '実績'
        return `${label}:${formatActualAmount(actualCase.amount, unitLabelForCase(actualCase, segment))}`
    }).join('\n')
}
const actualTotalForSegment = (segment) => {
    const cases = actualCasesForSegment(segment)
    if (!cases.length) return ''

    const total = cases.reduce((sum, actualCase) => sum + toNum(actualCase.amount), 0)
    return formatActualAmount(total, unitLabelForProject(projectForSegment(segment) ?? projectForCase(cases[0], segment)))
}
const actualDetailForSegment = (segment) => {
    const cases = actualCasesForSegment(segment)
    if (!cases.length) return ''

    return cases.map(actualCase => {
        const lines = []
        if (actualCase.meta && typeof actualCase.meta === 'object') {
            for (const [key, val] of Object.entries(actualCase.meta)) {
                if (val !== null && val !== undefined && String(val).trim() !== '') {
                    lines.push(`${key}: ${val}`)
                }
            }
        }
        lines.push(`${actualCase.status ?? '実績'}: ${formatActualAmount(actualCase.amount, unitLabelForCase(actualCase, segment))}`)
        return lines.join('\n')
    }).join('\n\n')
}
const allowanceForSegment = (segment) => {
    const values = segmentDetailValues(segment)
    const label = props.item?.allowances ?? ''
    const readableLabel = String(label)
        .split(/\s+/)
        .map(value => value.trim())
        .filter(value => value && Number.isNaN(Number(value)))
        .join(' ')

    if (segmentHasDetail(segment, 'allowance')) {
        if (Array.isArray(values.allowance_labels) && values.allowance_labels.length) {
            const readableLabels = values.allowance_labels
                .filter(value => value !== null && value !== undefined && value !== '')
                .map(value => String(value).trim())
                .filter(value => value && Number.isNaN(Number(value)))

            if (readableLabels.length) {
                return readableLabels.join(' ')
            }
        }
        if (Array.isArray(values.allowance) && values.allowance.length) {
            const readableValues = values.allowance
                .filter(value => value !== null && value !== undefined && value !== '')
                .map(value => String(value).trim())
                .filter(value => value && Number.isNaN(Number(value)))

            if (readableValues.length) {
                return readableValues.join(' ')
            }
        }

        return firstSegmentWithDetail('allowance') === segment ? readableLabel : ''
    }
    return segmentHasVisibleDetail(segment, 'allowance', readableLabel) ? readableLabel : ''
}
const dayAllowanceSegment = computed(() => {
    return projectSegmentRows.value.find(segment => segment && allowanceForSegment(segment)) ?? null
})
const dayAllowanceLabel = computed(() => {
    const labels = projectSegmentRows.value
        .map(segment => segment ? allowanceForSegment(segment) : '')
        .flatMap(label => String(label ?? '').split(/\s+/))
        .map(label => label.trim())
        .filter(Boolean)

    return [...new Set(labels)].join(' ')
})
const incidentForSegment = (segment) => {
    const incident = String(segmentDetailValues(segment).incident ?? '').trim()
    const label = props.item?.incident ?? ''
    if (segmentHasDetail(segment, 'incident') && incident && incident !== 'なし') {
        return incident
    }
    if (label === 'なし') {
        return ''
    }
    if (segmentHasDetail(segment, 'incident')) {
        return firstSegmentWithDetail('incident') === segment ? label : ''
    }

    return segmentHasVisibleDetail(segment, 'incident', label) ? label : ''
}
const commentForSegment = (segment) => {
    const projectComment = String(segment?.comment ?? '').trim()
    if (projectComment) {
        return segmentHasVisibleDetail(segment, 'comment', projectComment) ? projectComment : ''
    }

    return segmentHasVisibleDetail(segment, 'comment', commentFormatted.value) ? commentFormatted.value : ''
}
const commentBoxSectionsForSegment = (segment) => {
    const comment = commentForSegment(segment)
    const overtime = overtimeForSegment(segment)
    const sections = []

    if (comment) {
        sections.push({ type: 'comment', label: 'コメント', text: comment })
    }
    if (overtime) {
        sections.push({ type: 'overtime', label: '時間外業務内容', text: overtime })
    }

    return sections
}
const commentBoxTextForSegment = (segment) => {
    return commentBoxSectionsForSegment(segment)
        .map(section => `${section.label}\n${section.text}`)
        .join('\n\n')
}
const overtimeForSegment = (segment) => {
    if (props.item?.shift?.overtime_request) return ''
    const overtime = String(segmentDetailValues(segment).overtime ?? '').trim()
    if (segmentHasDetail(segment, 'overtime') && overtime) {
        return overtime
    }
    if (segmentHasDetail(segment, 'overtime')) {
        return firstSegmentWithDetail('overtime') === segment ? overTimeReasonFormatted.value : ''
    }
    return segmentHasVisibleDetail(segment, 'overtime', overTimeReasonFormatted.value) ? overTimeReasonFormatted.value : ''
}
const vehicleLabelFromData = (vehicleData, withMobileTitle = false) => {
    if (!vehicleData) return ''
    const vehicle = vehicleAsOptions.find(ob => Number(ob.value) === Number(vehicleData.vehicle))
    if (!vehicle) return ''
    return `${withMobileTitle ? '車両使用 : ' : ''}${vehicle.label}`
}
const vehicleRecordForSegment = (segment) => {
    const records = props.item?.time_card?.vehicle_records
    if (!Array.isArray(records) || !segment) return null

    return records.find(record => Number(record?.timecard_project_segment_id) === Number(segment?.id))
        ?? records.find(record => Number(record?.project_id) === segmentProjectId(segment) && Number(record?.vehicle) === Number(segmentDetailValues(segment).vehicle?.vehicle))
        ?? null
}
const mergeVehicleDataForSegment = (segment) => {
    const detailVehicle = segmentDetailValues(segment).vehicle
    const vehicleRecord = vehicleRecordForSegment(segment)
    const legacyVehicle = props.item?.time_card?.vehicle_data

    if (segmentHasDetail(segment, 'vehicle') && (detailVehicle?.vehicle || vehicleRecord?.vehicle)) {
        return {
            id: detailVehicle?.id ?? vehicleRecord?.id ?? null,
            vehicle: detailVehicle?.vehicle ?? vehicleRecord?.vehicle ?? null,
            alcohol_before_time: detailVehicle?.alcohol_before_time ?? vehicleRecord?.alcohol_before_time ?? null,
            alcohol_after_time: detailVehicle?.alcohol_after_time ?? vehicleRecord?.alcohol_after_time ?? null,
            alcohol_before_value: detailVehicle?.alcohol_before_value ?? vehicleRecord?.alcohol_before_value ?? null,
            alcohol_after_value: detailVehicle?.alcohol_after_value ?? vehicleRecord?.alcohol_after_value ?? null,
            confirm_before_user: detailVehicle?.confirm_before_user ?? vehicleRecord?.confirm_before_user ?? null,
            confirm_after_user: detailVehicle?.confirm_after_user ?? vehicleRecord?.confirm_after_user ?? null,
            before_user: detailVehicle?.before_user ?? vehicleRecord?.before_user ?? null,
            after_user: detailVehicle?.after_user ?? vehicleRecord?.after_user ?? null,
        }
    }

    if (segmentHasDetail(segment, 'vehicle')) {
        return firstSegmentWithDetail('vehicle') === segment ? legacyVehicle : null
    }

    return segmentHasVisibleDetail(segment, 'vehicle', hasVehicle.value) ? legacyVehicle : null
}
const vehicleForSegment = (segment) => {
    return vehicleLabelFromData(mergeVehicleDataForSegment(segment), responsive.mobile)
}
const vehicleDetailForSegment = (segment) => {
    const vehicleData = mergeVehicleDataForSegment(segment)
    const vehicle = vehicleLabelFromData(vehicleData)
    if (!vehicle) return ''

    return `使用車両\n${vehicle}\n\nアルコールチェックした時間\n車両使用前: ${vehicleData.alcohol_before_time ?? ''}\n車両使用後: ${vehicleData.alcohol_after_time ?? ''}\n\nアルコールチェックした値\n車両使用前: ${vehicleData.alcohol_before_value ?? ''}\n車両使用後: ${vehicleData.alcohol_after_value ?? ''}\n\nアルコールチェックした確認者\n車両使用前: ${vehicleData.before_user?.name ?? ''}\n車両使用後: ${vehicleData.after_user?.name ?? ''}`
}
const mileageForSegment = (segment) => {
    const mileage = segmentDetailValues(segment).mileage
    if (segmentHasDetail(segment, 'mileage') && mileage && (toNum(mileage.mileage) || toNum(mileage.gas_full_price))) {
        const km = toNum(mileage.mileage)
        const gas = toNum(mileage.gas_full_price)
        return `マイカー : ${km ? `${km}km` : ''}${gas ? ` ${yenFmt.format(gas)}円` : ''}`.trim()
    }
    if (!segment?.legacy && projectSegments.value.length) return ''

    const tc = timeCard.value
    const matchesProject = Number(tc?.car_used_project) === segmentProjectId(segment)
    return matchesProject && mileageFormatted.value ? mileageFormatted.value : ''
}
const mileageChipLabelForSegment = (segment) => {
    const mileage = segmentDetailValues(segment).mileage
    if (segmentHasDetail(segment, 'mileage') && mileage && (toNum(mileage.mileage) || toNum(mileage.gas_full_price))) {
        const km = toNum(mileage.mileage)
        const gas = toNum(mileage.gas_full_price)
        return `マイカー ${km ? `${km}km` : ''}${gas ? ` ${yenFmt.format(gas)}円` : ''}`.trim()
    }
    if (!segment?.legacy && projectSegments.value.length) return ''

    const tc = timeCard.value
    if (Number(tc?.car_used_project) !== segmentProjectId(segment)) return ''

    const km = toNum(tc?.car_mileage)
    const gas = toNum(tc?.gas_full_price)
    if (!km && !gas) return ''

    return `マイカー ${km ? `${km}km` : ''}${gas ? ` ${yenFmt.format(gas)}円` : ''}`.trim()
}
const mileageChipValueForSegment = (segment) => {
    const label = mileageChipLabelForSegment(segment)
    return label.replace(/^マイカー\s*/, '')
}
const mileageDetailForSegment = (segment) => {
    const mileage = segmentDetailValues(segment).mileage
    if (segmentHasDetail(segment, 'mileage') && mileage && (toNum(mileage.mileage) || toNum(mileage.gas_full_price))) {
        const km = toNum(mileage.mileage)
        const gas = toNum(mileage.gas_full_price)
        const consumption = mileage.gas_consumption ?? ''
        const unit = mileage.gas_unit_price ?? ''
        const parts = []
        if (km) parts.push(`走行距離: ${km}km`)
        if (consumption) parts.push(`実燃費: ${consumption}km/L`)
        if (unit) parts.push(`ガソリン単価: ${unit}円`)
        if (gas) parts.push(`ガソリン代: ${yenFmt.format(gas)}円`)
        return parts.join('\n')
    }

    return segment?.legacy ? mileageDetail.value : ''
}
const shortDetailText = (value, limit = 12) => {
    const text = String(value ?? '').replace(/\s+/g, ' ').trim()
    if (!text) return ''
    return text.length > limit ? `${text.slice(0, limit)}...` : text
}
const segmentDetailSummary = (segment) => {
    const details = []
    const costSummary = costSummaryForSegment(segment)
    const mileageValue = mileageChipValueForSegment(segment)
    const vehicle = vehicleForSegment(segment)
    const incident = incidentForSegment(segment)
    const overtime = overtimeForSegment(segment)
    const actual = actualSummaryForSegment(segment)
    const comment = commentForSegment(segment)

    if (costSummary) details.push({ type: 'expenses', label: '経費', value: costSummary, title: costDetailSummaryForSegment(segment) })
    if (mileageValue) details.push({ type: 'mileage', label: 'マイカー', value: mileageValue, title: mileageDetailForSegment(segment) })
    if (vehicle) details.push({ type: 'vehicle', label: '車両使用', value: shortDetailText(vehicle), title: vehicleDetailForSegment(segment) })
    if (incident) details.push({ type: 'incident', label: 'インシデント', value: 'あり', title: incident })
    if (actual) details.push({ type: 'actual', label: '実績', value: actualTotalForSegment(segment), title: actualDetailForSegment(segment) })
    if (comment || overtime) details.push({
        type: 'comment',
        label: 'コメント',
        value: comment ? 'あり' : '時間外あり',
        title: commentBoxTextForSegment(segment),
    })

    return details
}
const inlineSegmentDetailSummary = (segment) => {
    return segmentDetailSummary(segment).filter(detail => !['incident', 'actual', 'comment'].includes(detail.type))
}
const segmentDetailColumnValue = (segment, type) => {
    if (!segment) return ''
    if (type === 'expenses') return costSummaryForSegment(segment)
    if (type === 'mileage') return mileageChipValueForSegment(segment)
    if (type === 'vehicle') return vehicleForSegment(segment) ? shortDetailText(vehicleForSegment(segment)) : ''
    if (type === 'allowance') return allowanceForSegment(segment) ? shortDetailText(allowanceForSegment(segment)) : ''
    if (type === 'incident') return incidentForSegment(segment) ? shortDetailText(incidentForSegment(segment)) : ''
    if (type === 'overtime') return overtimeForSegment(segment) ? shortDetailText(overtimeForSegment(segment)) : ''
    if (type === 'actual') return actualTotalForSegment(segment)
    if (type === 'comment') {
        const comment = commentForSegment(segment)
        const overtime = overtimeForSegment(segment)
        if (comment) return shortDetailText(comment, 18)
        if (overtime) return '時間外あり'
        return ''
    }
    return ''
}
const segmentDetailColumnTitle = (segment, type) => {
    const detail = segmentDetailSummary(segment).find(item => item.type === type)
    return detail?.title || projectDetailBoxText(segment) || segmentDetailColumnValue(segment, type)
}
const detailSummaryText = (detail) => {
    return [projectDetailLabels[detail.type] ?? detail.label, detail.value].filter(Boolean).join(' ')
}
const projectDetailLabels = {
    expenses: '経費',
    mileage: 'マイカーの走行距離（往復）',
    allowance: '諸手当',
    vehicle: '運転業務',
    incident: 'インシデント',
    overtime: '時間外業務内容',
    actual: '実績',
    comment: 'コメント',
}
const activeProjectDetailLabel = computed(() => projectDetailLabels[activeProjectDetail.value?.type] ?? '')
const floatingBoxLimits = (box) => {
    const wrapper = box.closest('.v-table__wrapper') ?? props.wrapper
    const wrapperRect = wrapper?.getBoundingClientRect?.()
    const top = Math.max(wrapperRect?.top ?? 0, 8)
    const bottom = Math.min(wrapperRect?.bottom ?? window.innerHeight, window.innerHeight - 8)

    return {
        top,
        bottom,
        maxHeight: `${Math.max(120, Math.floor(bottom - top - 16))}px`,
    }
}
const floatingBoxTopOffset = (box) => {
    const rect = box.getBoundingClientRect()
    const limits = floatingBoxLimits(box)
    let offset = 0

    if (rect.bottom > limits.bottom) {
        offset = Math.floor(limits.bottom - rect.bottom)
    }

    if (rect.top + offset < limits.top) {
        offset += Math.ceil(limits.top - (rect.top + offset))
    }

    return {
        offset,
        maxHeight: limits.maxHeight,
    }
}
const positionProjectDetailBox = async() => {
    projectDetailBoxTopOffset.value = 0
    projectDetailBoxMaxHeight.value = ''
    await nextTick()
    await new Promise(resolve => requestAnimationFrame(resolve))

    const box = [...document.querySelectorAll('.project-chip-box')]
        .find(element => element instanceof HTMLElement && element.offsetParent !== null)
    if (!box) return

    const { offset, maxHeight } = floatingBoxTopOffset(box)
    projectDetailBoxTopOffset.value = offset
    projectDetailBoxMaxHeight.value = maxHeight
}
const openProjectDetailBox = async(segment, detail) => {
    const type = typeof detail === 'string' ? detail : detail?.type
    closeOvertimeApproveBox()
    document.dispatchEvent(new CustomEvent(PROJECT_DETAIL_OPEN_EVENT, {
        detail: { sourceId: projectDetailInstanceId },
    }))
    activeProjectDetail.value = {
        segmentId: segmentKey(segment),
        type,
    }
    addProjectDetailOutsideListeners()
    await positionProjectDetailBox()
}
const toggleProjectDetailBox = (segment, detail) => {
    if (isProjectDetailBoxOpen(segment, detail?.type)) {
        closeProjectDetailBox()
        return
    }
    openProjectDetailBox(segment, detail)
}
const closeProjectDetailBox = () => {
    activeProjectDetail.value = null
    projectDetailBoxTopOffset.value = 0
    projectDetailBoxMaxHeight.value = ''
    removeProjectDetailOutsideClick()
}
const addProjectDetailOutsideListeners = () => {
    removeProjectDetailOutsideClick()
    document.addEventListener('click', handleProjectDetailDocumentClick, true)
    window.addEventListener('resize', closeProjectDetailBox)
}
const removeProjectDetailOutsideClick = () => {
    document.removeEventListener('click', handleProjectDetailDocumentClick)
    document.removeEventListener('click', handleProjectDetailDocumentClick, true)
    window.removeEventListener('resize', closeProjectDetailBox)
}
const handleProjectDetailOpenEvent = (event) => {
    if (event.detail?.sourceId === projectDetailInstanceId) return
    if (activeProjectDetail.value) closeProjectDetailBox()
    if (activeOvertimeBoxKey.value) closeOvertimeApproveBox()
}
const handleProjectDetailDocumentClick = (event) => {
    if (!activeProjectDetail.value) return

    const target = event.target
    if (!(target instanceof Element)) {
        closeProjectDetailBox()
        return
    }

    const detailBox = target.closest('.project-chip-box')
    if (detailBox) return

    const detailButton = target.closest('.project-segment-detail-column-button')
    if (detailButton) return

    closeProjectDetailBox()
    event.preventDefault()
    event.stopPropagation()
}
onMounted(() => {
    syncMobileLayout()
    window.addEventListener('resize', syncMobileLayout)
    document.addEventListener(PROJECT_DETAIL_OPEN_EVENT, handleProjectDetailOpenEvent)
})
onBeforeUnmount(() => {
    window.removeEventListener('resize', syncMobileLayout)
    document.removeEventListener(PROJECT_DETAIL_OPEN_EVENT, handleProjectDetailOpenEvent)
    removeProjectDetailOutsideClick()
    removeOvertimeApproveOutsideListeners()
})
const isProjectDetailBoxOpen = (segment, type = null) => {
    const isOpenSegment = activeProjectDetail.value?.segmentId === segmentKey(segment)
    return type ? isOpenSegment && activeProjectDetail.value?.type === type : isOpenSegment
}
const isProjectDetailVisibleRow = (segment, segmentIndex) => {
    if (!activeProjectDetail.value) return false
    if (responsive.mobile && segmentIndex === 0) {
        return projectSegmentRows.value.some(rowSegment => rowSegment && isProjectDetailBoxOpen(rowSegment))
    }
    return !!segment && isProjectDetailBoxOpen(segment)
}
const isOvertimeApproveVisibleRow = (segment, segmentIndex) => {
    if (!activeOvertimeBoxKey.value) return false

    if (isMobileLayout.value) {
        return segmentIndex === 0 && projectSegmentRows.value.some((rowSegment, rowIndex) => {
            return overtimeRowsForSegment(rowSegment, rowIndex).some(row => row.key === activeOvertimeBoxKey.value)
        })
    }

    return overtimeRowsForSegment(segment, segmentIndex).some(row => row.key === activeOvertimeBoxKey.value)
}
const projectDetailBoxCaption = (segment) => {
    return [segmentProjectName(segment), projectSegmentTimeLabel(segment), segmentMinutesLabel(segment)]
        .filter(Boolean)
        .join(' ・ ')
}
const projectDetailBoxText = (segment) => {
    const type = activeProjectDetail.value?.type
    const values = {
        mileage: mileageDetailForSegment(segment),
        allowance: allowanceForSegment(segment),
        vehicle: vehicleDetailForSegment(segment),
        incident: incidentForSegment(segment),
        overtime: overtimeForSegment(segment),
        actual: actualDetailForSegment(segment),
        comment: commentBoxTextForSegment(segment),
        expenses: costDetailSummaryForSegment(segment),
    }

    return values[type] || ''
}
const projectCostKey = (cost) => {
    return cost?.id ?? `${cost?.department ?? ''}-${cost?.type ?? ''}-${cost?.content ?? ''}-${cost?.expenses ?? ''}`
}
const projectCostTitle = (cost) => {
    return hasWorkCostLabel(cost) ?? '経費'
}
const projectCostAmountLabel = (cost) => {
    if (cost?.expenses === null || cost?.expenses === undefined || cost?.expenses === '') return ''
    return `${yenFmt.format(toNum(cost.expenses))}円`
}

const dayFormatter = computed(() => {
    const value = displayDayShow.value
    if(value){
        const date =  DateTime.fromISO(value).toFormat('M / d (ccc)')
        return date
    }
})
const getShiftClass = computed(() => {
    const shift = props.item.shift?.shift_type
    return shift && ['day_off','planned_paid_leave','annual_leave_full','special_leave_condolence','special_leave_transfer','special_leave_oda','comp_holiday','legal_holiday','special_holiday'].includes(shift?.category) ? 'shift-sunday' : ''
})

const startEarly = computed(() => {
    const shift = props.item?.shift
    const timecard = props.item?.time_card
    if(!shift || !timecard) return
    if(timecard.start_time){
        const shiftStart = customParser(`${shift.shift_day} ${shift.start_time}`)
        const cardStart = customParser(`${timecard.day} ${timecard.start_time}`)
        return cardStart > shiftStart ?  'late-class'
        : shiftStart > cardStart ?  'over-class' : ''
    }      
    return ''
})

const goLately = computed(() => {
    const shift = props.item?.shift
    const timecard = props.item?.time_card
    if(!shift || !timecard) return
    if(timecard.end_time){
        const shiftEnd = customParser(`${shift.shift_day} ${shift.end_time}`)
        const cardEnd = customParser(`${timecard.day} ${timecard.end_time}`)
        return cardEnd > shiftEnd ?  'over-class'
        : shiftEnd > cardEnd ?  'late-class' : ''
    }      
    return ''
})
const startTimeFormatted = computed(() => {
    const start = props.item?.time_card?.start_time
    const end = props.item?.time_card?.end_time
    if(!start && !end) return '' 
    const [hour, min] = start.split(':').slice(0, 2);
    return responsive.mobile ?  `出勤 : ${hour}:${min}` : `${hour}:${min}`        
})

const endTimeFormatted = computed(() => {
    const start = props.item?.time_card?.start_time
    const end = props.item?.time_card?.end_time
    if(!start && !end) return ''
    if(start && !end ) return '打刻なし'         
    const [hour, min] = end.split(':').slice(0, 2);
    return responsive.mobile ?  `退勤 : ${hour}:${min}` : `${hour}:${min}`        
})

const workTimeFormatted = computed(() => {
    const timeCard = props.item?.time_card
    if(timeCard?.stamp_flag == 2) return
    if(timeCard){
        const mobileTitle = responsive.mobile ? '労働時間 : ' : ''
        if(timeCard.work_time){
            const hours = Math.floor(timeCard.work_time / 60);
            const minutes = timeCard.work_time % 60;                
            return `${mobileTitle}${hours}時間${minutes}分`;
        }else if(timeCard.stamp_flag == 0){
            return `${mobileTitle}${countdown.value}`
        }            
    }
    return ''
})
const durationJa = (startStr, endStr) => {
  const [sh, sm, ss = 0] = startStr.split(":").map(Number);
  const [eh, em, es = 0] = endStr.split(":").map(Number);

  const start = new Date(0, 0, 0, sh, sm, ss);
  const end = new Date(0, 0, 0, eh, em, es);

  if (end < start) end.setDate(end.getDate() + 1);

  const diffMs = end.getTime() - start.getTime();
  const totalMinutes = Math.round(diffMs / 60000);
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  return `${hours}時間${minutes}分`;
}
const trainTimeFormatted = computed(() => {
    const timeCard = props.item?.time_card
    let label = ""
    if (timeCard){
        const mobileTitle = responsive.mobile ? '研修時間 : ' : ''
        if (timeCard.training_start_time && timeCard.training_end_time) {
            label = durationJa(timeCard.training_start_time, timeCard.training_end_time);
            return mobileTitle + label
        }
    }
})
const countdown = computed(() => {
    const currentTime = DateTime.now();
    const givenTime = props.item?.time_card.start_time;
    const breakMinute = props.item?.total_break_time || 0;
    
    if (!givenTime) return '0時間0分';
    
    const todayWithGivenTime = `${currentTime.toFormat('yyyy-MM-dd')} ${givenTime}`;
    const givenTimeInstance = DateTime.fromFormat(todayWithGivenTime, 'yyyy-MM-dd HH:mm:ss');
    
    // Calculate difference and subtract break time
    let difference = currentTime.diff(givenTimeInstance, ['hours', 'minutes']);
    difference = difference.minus({ minutes: breakMinute });
    
    // Ensure we don't return negative time
    if (difference.hours < 0 || (difference.hours === 0 && difference.minutes < 0)) {
        return '0時間0分';
    }
    
    return `${Math.floor(difference.hours)}時間${Math.floor(difference.minutes)}分`;
})
const overTimeFormatted = computed(() => {
    const data = props.item
    if(data.flex) return ''
    const mobileTitle = responsive.mobile ? '残業時間 : ' : ''
    return data.time_card && data.time_card.over_time && data.time_card.over_time !== '0' ? mobileTitle + data.time_card.over_time + '分' : ''
})

const breakTimeFormatted = computed(() => {
    const data = props.item
    const mobileTitle = responsive.mobile ? '休憩時間 : ' : ''        
    return data.time_card?.break_time ? `${mobileTitle}${data.time_card.break_time}分` : ''
})

const hasAllowance = computed(() => {      
    const mobileTitle = props.item?.allowances && responsive.mobile ? '諸手当 : ' : ''  
    const label = props.item?.allowances
    return `${mobileTitle}${label}`      
})

const incidentFormatted = computed(() => {
    const title = props.item?.incident && responsive.mobile ? 'インシデント : ' : ''
    return title + props.item?.incident
})

const satisfyFormatted = computed(() => {
    
    const title = props.item?.satisfy && responsive.mobile ? '目標達成率 : ' : ''
    return  title + props.item?.satisfy
})
const totalResultFormatted = computed(() => {
    const cases = props.item?.time_card?.project_case || [];
    const title = cases.length && responsive.mobile ? '実績 : ' : '';
    return title + cases.reduce((sum, c) => sum + Number(c.amount || 0), 0);
})
const actualResultFormatted = computed(() => {
  const cases = props.item?.time_card?.project_case || [];

  if (!cases.length) return '';

  return cases
    .filter(c => c.amount !== null && c.amount !== '' && c.amount !== undefined)
    .map(c => {
      const lines = []
      // Meta fields (selector/text extra fields)
      if (c.meta && typeof c.meta === 'object') {
        for (const [key, val] of Object.entries(c.meta)) {
          if (val !== null && val !== undefined && String(val).trim() !== '') {
            lines.push(`${key}: ${val}`)
          }
        }
      }
      lines.push(`${c.status ?? '実績'}: ${formatActualAmount(c.amount, unitLabelForCase(c))}`)
      return lines.join('\n')
    })
    .join('\n\n');
});

const yenFmt = new Intl.NumberFormat('ja-JP');

const toNum = (n) => {
  const v = Number(n);
  return Number.isFinite(v) ? v : 0;
}

const mileageFormatted = computed(() => {
  const tc = props.item?.time_card;
  if (!tc) return '';

  const km = toNum(tc.car_mileage);
  const gas = toNum(tc.gas_full_price);

  const title = km && responsive.mobile ? 'マイカー走行距離 : ' : '';
  const kmPart = km ? `${km}km` : '';
  const gasPart = gas > 0 ? `${yenFmt.format(gas)}円` : '';

  // two-line if gas exists, otherwise one line
  return gas > 0 ? `${title}${kmPart}\n${gasPart}` : `${title}${kmPart}`;
});

const mileageDetail = computed(() => {
  const tc = props.item?.time_card;
  if (!tc) return '';

  const km = toNum(tc.car_mileage);
  const gas = toNum(tc.gas_full_price);
  const dept = tc.car_project?.name ?? '';

  const kmPart = km ? `${km}km` : '';
  const gasPart = `ガソリン代 : ${gas > 0 ? `${yenFmt.format(gas)}円` : '—'}`;

  // department prefix only if it exists
  const deptPart = dept ? `${dept}:` : '';
  return `${deptPart}${kmPart}\n${gasPart}`;
});

const commentFormatted = computed(() => {
    
    const title = props.item?.comment && responsive.mobile ? 'コメント : ' : ''
    return title + props.item?.comment
})
const commentTrim = computed(() => {
    return commentFormatted.value && commentFormatted.value.length > 10 ? commentFormatted.value.slice(0, 10) + "..." : commentFormatted.value
})
const overTimeReasonFormatted = computed(() => {
    
    const content = props.item?.overtime_reason ? '時間外業務内容 : ' +  props.item.overtime_reason : ''
    return content
})

const hasCondition = computed(() => {
    const index = props.item.weather
    const mobileTitle = responsive.mobile ? 'コンディション : ' : ''
    if(index !== null){
        return `<div class="condition-area"><div>${mobileTitle}</div><WeatherIcon :which="${index}" size="17"/></div>`
    }
    return ''
    
})

const hasWorkCost = computed(() => {
    const costs = props.item.time_card?.timecard_costs
    const costText = costs && costs.length ?
    costs.map(ob => {
        const department = ob.department !== null ? ob.department + '\n' : ''
        const costOption = costOptions.find(opt => opt.value === ob.type);
        const expense = ob.expenses !== null ? ob.expenses : 0
        return costOption ? `${department}${costOption.label}:${expense}円`: '';
    }).join(' ') : '';
    const title = costText && responsive.mobile ? '経費 : ' : ''
    return title + costText
})
const hourMinuteFormat = (time) => {
    if (!time) return '--:--'
    const [hour, min] = time.split(':').slice(0, 2);
    return `${hour}:${min}`;
}
const segmentStartTimeLabel = (segment) => {
    if (segment) {
        const value = hourMinuteFormat(segment?.start_time)
        return responsive.mobile ? `出勤 : ${value}` : value
    }
    return startTimeFormatted.value
}
const segmentEndTimeLabel = (segment) => {
    if (segment) {
        const value = hourMinuteFormat(segment?.end_time)
        return responsive.mobile ? `退勤 : ${value}` : value
    }
    return endTimeFormatted.value
}
const projectSegmentTimeLabel = (segment) => {
    return `${hourMinuteFormat(segment?.start_time)} - ${hourMinuteFormat(segment?.end_time)}`
}
const hasWorkCostLabel = (cost) => {
    return costOptions.find(opt => opt.value === cost.type)?.label;
}
const formatCostString = (cost) => {
    let result = '';

    if (cost.department) {
      result += `部門:${cost.department}<br>`;
    }

    if (hasWorkCostLabel(cost)) {
      result += `${hasWorkCostLabel(cost)}:`;
    }

    if (cost.content) {
      result += `${cost.content} `;
    }

    if (cost.expenses) {
      result += `${cost.expenses}円`;
    }

    return result;
}
const costFileExtension = (cost) => {
    return fileExtensionFromPath(cost?.file_path)
}

const costFileType = (cost) => {
    return filePreviewTypeFromPath(cost?.file_path, cost?.file_mime_type)
}

const isCostImage = (cost) => {
    return Boolean(cost?.file_path) && costFileType(cost) === 'image'
}

const costFileUrl = (cost) => {
    return `/cdn/timecard_files/${cost.file_path}`
}

const previewCostFile = (cost) => {
    workFilePreview(cost.file_path, costFileType(cost), '/cdn/timecard_files')
}


const hasWeather = computed(() => props.item?.weather !== null && props.item?.weather !== undefined)
const hasTruthyAbility = (ability) => Object.values(ability ?? {}).some(value => value === true)
const hasRowAction = computed(() => hasTruthyAbility(props.item?.ability))
const hasProjectSegmentAction = (segment) => hasTruthyAbility(segment?.ability)
const hasReportAction = (segment, segmentIndex) => {
    if (segment?.legacy) return hasRowAction.value
    if (segment) return hasProjectSegmentAction(segment)
    if (segmentIndex === 0) return hasRowAction.value
    return false
}
const reportActionSegment = (segment) => {
    return segment && !segment.legacy && hasProjectSegmentAction(segment) ? segment : null
}
const mobileStampButtons = computed(() => {
    const buttons = []

    if (props.item?.ability?.start_stamp) {
        buttons.push({ title: '始業', action: () => start(props.item) })
    }
    if (props.item?.ability?.end_stamp) {
        buttons.push({ title: '終業', action: () => end(props.item) })
    }
    if (props.item?.ability?.break_stamp) {
        buttons.push({ title: props.item?.time_card?.stamp_flag == 0 ? '休憩' : '再開', action: () => takeBreak(props.item) })
    }

    return buttons
})
const isCompactBlankRow = computed(() => {
    return !props.item?.time_card
        && !projectSegments.value.length
        && !props.item?.ability?.start_stamp
        && !props.item?.ability?.end_stamp
        && !props.item?.ability?.break_stamp
})
const openReportMenu = () => {
    if (!hasRowAction.value) return
    emit('procedureStart', props.item)
}

const incentiveCount = computed(() => {
    const costs = props.item.time_card?.timecard_incentives
    const sum = costs && costs.length ? costs.reduce((accumulator, element) => accumulator + element.count, 0) : 0
    return sum !== 0 ? `${sum}件` : ''
})

const overTimeRequestDisplay = computed(() => {
    const overtime = props.item?.shift?.overtime_request
    if(!overtime) return 
    const statuses = ['差戻中', '申請中', '承認済']
    const status = statuses[overtime.status]
    return `${status}${overtime.minutes}分`
})
const overtimeRequest = computed(() => props.item?.shift?.overtime_request ?? null)
const hasSavedProjectSegments = computed(() => {
    return Array.isArray(timeCard.value?.project_segments) && timeCard.value.project_segments.length > 0
})
const firstProjectSegmentIndexForProject = (projectId) => {
    return projectSegmentRows.value.findIndex(rowSegment => {
        return rowSegment && Number(segmentProjectId(rowSegment)) === Number(projectId)
    })
}
const overtimeRowKey = (row) => {
    if (row?.aggregate) {
        return `${overtimeRequest.value?.id ?? 'overtime'}-aggregate-${row.segmentIndex ?? 0}`
    }
    return `${overtimeRequest.value?.id ?? 'overtime'}-${row.requestSegmentIndex ?? 'daily'}-${row.projectId ?? 'project'}-${row.segmentIndex ?? 0}`
}
const overtimeRequestSegmentRow = (requestSegment, requestSegmentIndex, segmentIndex) => ({
    projectId: Number(requestSegment?.project_id ?? 0),
    requestSegment,
    requestSegmentIndex,
    segmentIndex,
})
const aggregateOvertimeRow = (segmentIndex) => {
    const row = {
        projectId: 0,
        requestSegment: null,
        requestSegmentIndex: null,
        requestSegments: overtimeProjectSegments.value,
        segmentIndex,
        requestOnly: true,
        aggregate: true,
    }

    return {
        ...row,
        key: overtimeRowKey(row),
    }
}
const overtimeRowsForSegment = (segment, segmentIndex) => {
    if (!overtimeRequest.value) return []

    if (overtimeProjectSegments.value.length) {
        if (!hasSavedProjectSegments.value) {
            return segmentIndex === 0 ? [aggregateOvertimeRow(segmentIndex)] : []
        }
        if (!segment) return []

        const projectId = segmentProjectId(segment)
        if (firstProjectSegmentIndexForProject(projectId) !== segmentIndex) return []

        return overtimeProjectSegments.value
            .map((requestSegment, requestSegmentIndex) => overtimeRequestSegmentRow(requestSegment, requestSegmentIndex, segmentIndex))
            .filter(row => row.projectId === Number(projectId))
            .map(row => ({
                ...row,
                key: overtimeRowKey(row),
            }))
    }

    if (segmentIndex !== 0) return []

    const row = {
        projectId: segment ? segmentProjectId(segment) : Number(props.item?.shift?.department_id ?? props.item?.shift?.department?.id ?? 0),
        requestSegment: null,
        requestSegmentIndex: null,
        segmentIndex,
        requestOnly: !segment || !hasSavedProjectSegments.value,
    }

    return [{
        ...row,
        key: overtimeRowKey(row),
    }]
}
const overtimeDetailRowsForRow = (row) => {
    if (Array.isArray(row?.requestSegments) && row.requestSegments.length) {
        return row.requestSegments.map((requestSegment, requestSegmentIndex) => ({
            ...overtimeRequestSegmentRow(requestSegment, requestSegmentIndex, row.segmentIndex ?? 0),
            key: `${row.key}-${requestSegmentIndex}`,
        }))
    }

    return [row]
}
const overtimeDetailRowKey = (overtimeRow, detailRow, detailRowIndex) => {
    return detailRow?.key ?? `${overtimeRow?.key ?? 'overtime'}-${detailRowIndex}`
}
const overtimeStatusForRow = (row) => {
    return row?.requestSegment ? overtimeSegmentStatus(row.requestSegment) : overtimeRequestStatus.value
}
const overtimeStatusLabelForRow = (row) => {
    return overtimeStatusLabel(overtimeStatusForRow(row))
}
const overtimeStatusClassForRow = (row) => {
    return `project-segment-status-overtime-${overtimeStatusForRow(row)}`
}
const overtimeMinutesForRow = (row) => {
    if (Array.isArray(row?.requestSegments) && row.requestSegments.length) {
        return row.requestSegments.reduce((total, requestSegment) => {
            return total + Number(requestSegment?.minutes ?? 0)
        }, 0)
    }

    return Number(row?.requestSegment?.minutes ?? overtimeRequest.value?.minutes ?? 0)
}
const overtimeMinutesLabelForRow = (row) => {
    const minutes = overtimeMinutesForRow(row)
    return `${minutes}分`
}
const overtimeDisplayForRow = (row) => {
    return `${overtimeStatusLabelForRow(row)}${overtimeMinutesLabelForRow(row)}`
}
const overtimeContentForRow = (row) => {
    return row?.requestSegment ? overtimeSegmentContent(row.requestSegment) : String(overtimeRequest.value?.content ?? '').trim()
}
const overtimeProjectNameForRow = (row) => {
    return row?.projectId ? overtimeProjectName(row.projectId) : '残業'
}
const overtimeTriggerProjectLabel = (row) => {
    return ''
}
const isOvertimeApproveBoxOpen = (row) => {
    return menu.name === 'approveBox'
        && menu.id === overtimeRequest.value?.id
        && activeOvertimeBoxKey.value === row?.key
}
const closeOvertimeApproveBox = () => {
    activeOvertimeBoxKey.value = null
    overtimeApproveBoxTopOffset.value = 0
    removeOvertimeApproveOutsideListeners()

    if (menu.name === 'approveBox' && menu.id === overtimeRequest.value?.id) {
        menu.close()
    }
}
const addOvertimeApproveOutsideListeners = () => {
    removeOvertimeApproveOutsideListeners()
    document.addEventListener('click', handleOvertimeApproveDocumentClick, true)
    window.addEventListener('resize', closeOvertimeApproveBox)
}
const removeOvertimeApproveOutsideListeners = () => {
    document.removeEventListener('click', handleOvertimeApproveDocumentClick)
    document.removeEventListener('click', handleOvertimeApproveDocumentClick, true)
    window.removeEventListener('resize', closeOvertimeApproveBox)
}
const handleOvertimeApproveDocumentClick = (event) => {
    if (!activeOvertimeBoxKey.value) return

    const target = event.target
    if (!(target instanceof Element)) {
        closeOvertimeApproveBox()
        return
    }

    if (target.closest('.project-chip-box')) return
    if (target.closest('.overtime-status-trigger')) return
    if (target.closest('.project-segment-detail-column-button')) return

    closeOvertimeApproveBox()
    event.preventDefault()
    event.stopPropagation()
}
const openOvertimeApproveBox = async(row) => {
    const id = props.item?.shift?.overtime_request?.id
    if (!id) return

    overtimeApproveBoxTopOffset.value = 0
    closeProjectDetailBox()
    document.dispatchEvent(new CustomEvent(PROJECT_DETAIL_OPEN_EVENT, {
        detail: { sourceId: projectDetailInstanceId },
    }))
    activeOvertimeBoxKey.value = row?.key ?? null
    menu.setMenu({name: 'approveBox', id})
    addOvertimeApproveOutsideListeners()
    await nextTick()
    await new Promise(resolve => requestAnimationFrame(resolve))

    const box = Array.isArray(overtimeApproveBox.value)
        ? overtimeApproveBox.value.find(element => element instanceof HTMLElement)
        : overtimeApproveBox.value
    if (!box) return

    const { offset } = floatingBoxTopOffset(box)
    overtimeApproveBoxTopOffset.value = offset
}
const overtimeProjectSegments = computed(() => {
    const segments = props.item?.shift?.overtime_request?.project_segments
    return Array.isArray(segments) ? segments : []
})
const overtimeRequestStatus = computed(() => {
    const overtime = props.item?.shift?.overtime_request
    return Number(overtime?.status ?? 1)
})
const overtimeStatusLabel = (status) => {
    return {
        0: '差戻中',
        1: '申請中',
        2: '承認済',
    }[Number(status)] ?? ''
}
const overtimeRequestStatusClass = computed(() => `project-segment-status-overtime-${overtimeRequestStatus.value}`)
const overtimeSegmentStatus = (segment) => {
    return Number(segment?.status ?? props.item?.shift?.overtime_request?.status ?? 1)
}
const overtimeSegmentStatusLabel = (segment) => {
    return overtimeStatusLabel(overtimeSegmentStatus(segment))
}
const overtimeSegmentStatusClass = (segment) => {
    return `project-segment-status-overtime-${overtimeSegmentStatus(segment)}`
}
const overtimeSegmentMinutesLabel = (segment) => {
    const minutes = Number(segment?.minutes ?? 0)
    return minutes ? `${minutes}分` : ''
}
const overtimeSegmentContent = (segment) => String(segment?.content ?? '').trim()
const overtimeSegmentKey = (segment, index) => {
    return `${segment?.project_id ?? 'project'}-${segment?.minutes ?? 0}-${index}`
}
const canManageOvertimeSegment = (segment) => {
    const activeUserId = Number(auth.activeUser?.id)
    const isAdmin = Boolean(auth.isAdmin)
    if (Number(props.item?.user_id) === activeUserId && !isAdmin) return false
    if (isAdmin || Number(auth.activeUser?.work_authority) === 1) return true

    const projectId = Number(segment?.project_id)
    if (!projectId) return false

    return props.workGroups?.some((group) => {
        if (Number(group?.id) !== projectId) return false

        return group.manager?.some((manager) => {
            const managerId = typeof manager === 'object' ? manager?.id : manager
            return Number(managerId) === activeUserId
        })
    })
}
const canApproveOvertimeSegment = (segment) => {
    return overtimeSegmentStatus(segment) === 1 && canManageOvertimeSegment(segment)
}
const canCancelOvertimeSegment = (segment) => {
    return overtimeSegmentStatus(segment) === 2 && canManageOvertimeSegment(segment)
}
const overtimeProjectName = (projectId) => {
    return props.workGroups?.find(group => Number(group?.id) === Number(projectId))?.name
        ?? (Number(props.item?.shift?.department?.id) === Number(projectId) ? props.item?.shift?.department?.name : null)
        ?? 'プロジェクト'
}
const overtimeProjectSummary = computed(() => {
    const segments = props.item?.shift?.overtime_request?.project_segments
    if (!Array.isArray(segments) || !segments.length) return ''

    return segments
        .map(segment => {
            const minutes = Number(segment?.minutes ?? 0)
            if (!minutes) return ''
            const content = String(segment?.content ?? '').trim()
            return `${overtimeProjectName(segment?.project_id)} ${minutes}分${content ? `：${content}` : ''}\n`
        })
        .filter(Boolean)
        .join('')
})


const getStatusText = computed(() => {
    const statusFlag = props.item?.time_card?.status_flag
    const statuses = {
        0: '作成中',
        1: '申請中',
        2: '承認済',
        10: '差戻中'
    }
    if (Number(statusFlag) === 10) return statuses[10]
    if (projectSegments.value.length && projectSegments.value.some(segment => segment.status === 'approved')) {
        const allApproved = projectSegments.value.every(segment => segment.status === 'approved')
        if (!allApproved) return '一部承認済'
    }
    return statuses[statusFlag] ?? ''
})
const dailyReportStatusClass = computed(() => {
    const statusFlag = Number(props.item?.time_card?.status_flag)
    if (statusFlag === 1) return 'daily-report-status-submitted'
    if (statusFlag === 2) return 'daily-report-status-approved'
    if (statusFlag === 10) return 'daily-report-status-rejected'
    return 'daily-report-status-draft'
})
const hasVehicle = computed(() => {
    const vehicleData = props.item?.time_card?.vehicle_data
    return vehicleLabelFromData(vehicleData, responsive.mobile)
})
</script>
<style scoped>
.w-row {
    --work-muted-text: var(--primary-color);
    --work-soft-text: var(--primary-color);
    --work-emphasis-text: var(--primary-color);
}

.mobile-project-segment-card-cell {
    display: none;
}

.project-chip-open-row {
    overflow: visible !important;
    position: relative;
    z-index: 15;
}

.project-chip-open-row .mobile-project-segment-card-cell,
.project-chip-open-row .mobile-project-segment-card,
.project-chip-open-row .mobile-project-segment-box,
.project-chip-open-row .mobile-project-segment-details {
    overflow: visible !important;
}

.project-segment-cell {
    min-width: 0 !important;
    width: auto !important;
    max-width: none;
    text-align: center !important;
    position: relative;
}

.work-shift-cell,
.daily-overtime-cell,
.daily-break-cell,
.daily-condition-cell {
    min-width: 0;
    width: auto;
}

.project-segment-time-cell {
    color: var(--primary-color);
    font-size: 12px;
    min-width: 0 !important;
    width: auto !important;
}

.project-segment-time-cell.late-class {
    color: red;
}

.project-segment-time-cell.over-class {
    color: #39f;
}

.project-segment-minute-cell {
    color: var(--primary-color);
    font-size: 12px;
    min-width: 0;
    width: auto;
}

.project-segment-detail-column {
    min-width: 0;
    width: auto;
    color: var(--primary-color);
    font-size: 12px;
    overflow: visible !important;
    position: relative;
}

.project-segment-detail-column-overtime,
.project-segment-detail-column-incident,
.project-segment-detail-column-comment {
    min-width: 0;
    max-width: none;
}

.project-segment-detail-column-expenses,
.project-segment-detail-column-actual,
.project-segment-detail-column-allowance,
.project-segment-detail-column-mileage,
.project-segment-detail-column-vehicle {
    min-width: 0;
    width: auto;
}

.project-segment-detail-column-button {
    display: block;
    width: 100%;
    max-height: 3.1em;
    max-width: 100%;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    font: inherit;
    overflow: hidden;
    padding: 0;
    text-align: center;
    text-overflow: ellipsis;
    white-space: normal;
    word-break: break-word;
    line-height: 1.45;
}

.project-segment-detail-column-button:hover {
    text-decoration: underline;
    text-underline-offset: 2px;
}

.work-member-cell {
    width: 1% !important;
    min-width: 0;
    max-width: none;
    box-sizing: border-box !important;
    font-weight: 500;
    color: var(--work-emphasis-text);
    overflow: visible !important;
    text-align: left !important;
    white-space: nowrap !important;
}

.work-member-content {
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
    gap: 4px;
    min-width: max-content;
    width: max-content;
    max-width: none;
}

.work-member-name {
    display: block;
    flex: 0 0 auto;
    max-width: none;
    min-width: max-content;
    line-height: 1.45;
    overflow: visible;
    overflow-wrap: normal;
    text-align: left;
    text-overflow: clip;
    white-space: nowrap;
}

.work-member-weather {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 17px;
    width: 17px;
    min-width: 17px;
    line-height: 1;
}

.report-status-cell {
    color: var(--work-soft-text);
    font-size: 12px;
    min-width: 112px;
    overflow: visible !important;
    position: relative;
    white-space: nowrap !important;
    width: 112px;
}

.overtime-status-trigger {
    background: transparent;
    border: 0;
    color: var(--primary-color);
    cursor: pointer;
    display: inline-block;
    font: inherit;
    font-size: 11px;
    line-height: 1.45;
    max-width: calc(100% - 2px);
    margin:auto;
    overflow: hidden;
    padding: 0;
    text-overflow: ellipsis;
    vertical-align: middle;
    white-space: nowrap;
}

.overtime-row-status + .overtime-row-status {
    margin-top: 4px;
}

.overtime-status-trigger:hover {
    text-decoration: underline;
    text-underline-offset: 2px;
}

.overtime-approve-box {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.overtime-approve-header,
.overtime-segment-main {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.overtime-approve-header {
    justify-content: space-between;
    color: var(--work-soft-text);
    padding-bottom: 8px;
}

.overtime-approve-header strong {
    font-size: 12px;
    font-weight: 500;
}

.overtime-segment-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.overtime-segment-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px 0;
    border-top: 1px solid var(--calendarBorder);
}

.overtime-segment-project {
    min-width: 0;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--work-soft-text);
}

.overtime-segment-minutes {
    flex: 0 0 auto;
    color: var(--work-muted-text);
}

.overtime-segment-content {
    white-space: pre-wrap;
    color: var(--work-muted-text);
    font-size: 12px;
    line-height: 1.5;
}

.report-action-cell {
    min-width: 0;
    overflow: visible !important;
    width: auto;
}

.report-action-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
}

.report-action-cell .project-segment-action {
    min-width: 26px;
    padding: 1px 2px;
    font-size: 11px;
}


.report-row-action,
.report-more-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 26px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12px;
    line-height: 1;
    cursor: pointer;
}

.report-row-action {
    gap: 4px;
    padding: 0 8px;
}

.report-more-action {
    width: 26px;
    padding: 0;
    font-size: 16px;
}

.report-row-action:hover,
.report-more-action:hover {
    border-color: var(--primary-color);
    background: transparent;
}

.report-row-action-create {
    color: var(--primary-color);
}

.report-row-action-view {
    color: var(--primary-color);
}

.report-action-symbol {
    font-size: 14px;
    line-height: 1;
}

.project-segment-name {
    display: block;
    font-size: 12px;
    line-height: 1.4;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--work-emphasis-text);
}

.project-segment-type {
    flex: 0 0 auto;
    font-size: 11px;
    font-weight: 400;
    color: var(--work-muted-text);
}

.project-segment-type-work {
    color: var(--work-muted-text);
}

.project-segment-type-training {
    color: var(--work-muted-text);
}

.project-segment-status {
    justify-self: end;
    font-size: 11px;
    font-weight: 400;
    white-space: nowrap;
    color: var(--primary-color);
}

.project-segment-status-draft {
    color: var(--primary-color);
}

.project-segment-status-submitted {
    color: red;
}

.project-segment-status-approved {
    color: var(--primary-color);
}

.project-segment-status-rejected {
    color: red;
}

.project-segment-status-daily-0 {
    color: var(--primary-color);
}

.project-segment-status-daily-1 {
    color: red;
}

.project-segment-status-daily-2 {
    color: var(--primary-color);
}

.project-segment-status-daily-10 {
    color: red;
}

.project-segment-status-overtime-0,
.project-segment-status-overtime-1 {
    color: red;
}

.project-segment-status-overtime-2 {
    color: var(--primary-color);
}

.project-segment-actions {
    display: flex;
    justify-self: end;
    justify-content: flex-end;
    gap: 4px;
}

.project-segment-status-actions {
    justify-content: center;
}

.project-segment-action {
    min-width: 34px;
    padding: 2px 7px;
    border: 1px solid currentColor;
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 11px;
    line-height: 1.5;
    cursor: pointer;
}

/* .project-segment-action.approve {
    color: #047857;
} */

.project-segment-action.reject {
    color: var(--primary-color);
}

.project-segment-action.cancel {
    color: var(--primary-color);
}

.project-segment-action:hover {
    background: transparent;
}

.project-segment-detail-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.project-segment-detail {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    width: fit-content;
    max-width: 100%;
    min-height: 20px;
    padding: 0 6px;
    border: 1px solid var(--calendarBorder);
    border-radius: 3px;
    background: transparent;
    color: var(--primary-color);
    font-size: 11px;
    line-height: 1.4;
    white-space: nowrap;
}

.project-segment-detail span,
.project-segment-detail strong {
    display: block;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}

.project-segment-detail strong {
    max-width: 110px;
    font-weight: 500;
    color: var(--primary-color);
}

.project-segment-detail-clickable {
    cursor: pointer;
}

.project-segment-detail-clickable:hover {
    border-color: var(--primary-color);
    background: transparent;
}

.project-segment-detail-active {
    border-color: var(--primary-color);
    background: transparent;
}

.project-segment-detail-expenses,
.project-segment-detail-mileage,
.project-segment-detail-vehicle,
.project-segment-detail-allowance,
.project-segment-detail-overtime,
.project-segment-detail-actual,
.project-segment-detail-incident,
.project-segment-detail-comment {
    color: var(--primary-color);
}

.daily-report-status {
    color: var(--primary-color);
    font-weight: 400;
}

.daily-report-status-approved {
    color: var(--primary-color);
}

.daily-report-status-submitted {
    color: red;
}

.daily-report-status-rejected {
    color: red;
}

.project-chip-box {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    min-width: 280px;
    width: max-content;
    max-width: 380px;
    z-index: 20;
    padding: 12px;
    text-align: left;
    line-height: 1.6;
    color: var(--primary-color);
    background: var(--background-color);
    border: 1px solid var(--calendarBorder);
    border-radius: 4px;
    box-shadow: rgba(60, 64, 67, 0.24) 0 2px 5px 0, rgba(60, 64, 67, 0.14) 0 8px 16px 4px;
    overflow-y: auto;
    white-space: normal;
}

.project-chip-box-expenses,
.project-chip-box-actual,
.project-chip-box-vehicle,
.project-chip-box-mileage,
.overtime-approve-box {
    left: auto;
    right: 0;
}

.project-chip-box-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--calendarBorder);
}

.project-chip-box-head strong {
    display: block;
    font-size: 12px;
    line-height: 1.4;
}

.project-chip-box-head p {
    margin: 2px 0 0;
    color: color-mix(in srgb, var(--primary-color) 70%, transparent);
    font-size: 11px;
    line-height: 1.4;
}

.project-chip-box-close {
    flex: 0 0 auto;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
}

.project-chip-box-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.project-chip-box-item {
    word-break: break-word;
}

.project-chip-box-item + .project-chip-box-item {
    padding-top: 10px;
    border-top: 1px solid var(--calendarBorder);
}

.project-chip-box-item-main {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
}



.project-chip-box-item-main strong {
    flex: 0 0 auto;
    color: var(--primary-color);
    font-weight: 700;
}

.project-chip-box-item p {
    margin: 3px 0 0;
    white-space: pre-wrap;
}

.project-chip-box-file {
    margin-top: 8px;
}

.project-chip-box-file img {
    max-width: 100%;
    max-height: 120px;
    border: 1px solid var(--calendarBorder);
    border-radius: 4px;
    cursor: pointer;
    object-fit: contain;
}

.project-chip-box-body {
    max-height: 240px;
    overflow: auto;
    word-break: break-word;
    white-space: pre-wrap;
}

.project-chip-box-comment-body {
    white-space: normal;
}

.project-chip-box-comment-section {
    word-break: break-word;
}

.project-chip-box-comment-section + .project-chip-box-comment-section {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--calendarBorder);
}

.project-chip-box-comment-section p {
    margin: 0 0 4px;
    color: color-mix(in srgb, var(--primary-color) 70%, transparent);
    font-size: 11px;
    line-height: 1.4;
}

.project-chip-box-comment-section div {
    white-space: pre-wrap;
}

.project-chip-box-empty {
    color: color-mix(in srgb, var(--primary-color) 60%, transparent);
    font-size: 12px;
}

@media (max-width: 959px) {
    .report-status-cell {
        min-width: 0;
        white-space: normal !important;
    }

    .overtime-status-trigger {
        white-space: normal;
    }

    .mobile-project-segment-card-cell {
        display: block !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .mobile-project-segment-card {
        display: flex;
        flex-direction: column;
        gap: 10px;
        position: relative;
        width: 100%;
        padding: 10px 12px 12px;
        box-sizing: border-box !important;
        color: var(--primary-color);
        background: var(--background-color);
    }

    .mobile-project-segment-person {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding-bottom: 8px;
        /* border-bottom: 1px solid var(--calendarBorder); */
    }

    .mobile-project-segment-person-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-width: 0;
    }

    .mobile-project-segment-person-main strong {
        min-width: 0;
        overflow: hidden;
        text-align: right;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mobile-project-segment-date {
        flex: 0 0 auto;
        font-size: 12px;
    }

    .mobile-project-segment-daily-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 10px;
        font-size: 12px;
        line-height: 1.4;
    }

    .mobile-project-segment-weather {
        display: inline-flex;
        align-items: center;
    }

    .mobile-project-segment-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .mobile-project-segment-box {
        display: flex;
        flex-direction: column;
        gap: 6px;
        position: relative;
        width: 100%;
        padding: 9px 10px;
        box-sizing: border-box !important;
        border: 1px solid var(--calendarBorder);
        background: var(--background-color);
    }

    .mobile-project-segment-box-empty {
        min-height: 38px;
        justify-content: center;
    }

    .mobile-project-segment-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .mobile-project-segment-status-stack {
        display: flex;
        flex: 0 0 auto;
        flex-direction: column;
        align-items: flex-end;
        gap: 3px;
        text-align: right;
    }

    .mobile-project-segment-status-stack .overtime-row-status,
    .mobile-empty-overtime-row {
        overflow: visible;
        position: relative;
    }

    .mobile-project-segment-status-stack .overtime-row-status:has(.overtime-approve-box),
    .mobile-empty-overtime-row:has(.overtime-approve-box) {
        z-index: 40;
    }

    .mobile-project-segment-status-stack .overtime-row-status:has(.overtime-approve-box) .overtime-approve-box,
    .mobile-empty-overtime-row:has(.overtime-approve-box) .overtime-approve-box {
        box-sizing: border-box !important;
        left: auto;
        margin-top: 0;
        max-width: calc(100vw - 80px);
        min-width: min(260px, calc(100vw - 80px));
        position: absolute;
        right: 0;
        top: calc(100% + 6px) !important;
        width: min(320px, calc(100vw - 80px));
        z-index: 45;
    }

    .mobile-overtime-status-trigger {
        margin: 0;
        max-width: 120px;
        text-align: right;
        white-space: normal;
    }

    .mobile-project-segment-title {
        display: flex;
        align-items: center;
        gap: 7px;
        min-width: 0;
    }

    .mobile-project-segment-title strong {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mobile-project-segment-type {
        flex: 0 0 auto;
        font-size: 11px;
        font-weight: 400;
    }

    .mobile-project-segment-time-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 12px;
        line-height: 1.5;
    }

    .mobile-project-segment-time-row strong {
        flex: 0 0 auto;
        font-weight: 500;
    }

    .mobile-project-segment-time-row .late-class {
        color: red;
    }

    .mobile-project-segment-time-row .over-class {
        color: #39f;
    }

    .mobile-project-segment-details {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        position: relative;
    }

    .mobile-project-segment-detail {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        width: auto;
        max-width: 100%;
        min-height: 22px;
        padding: 0 6px;
        border: 1px solid var(--calendarBorder);
        background: transparent;
        line-height: 1.4;
        text-align: left;
        white-space: nowrap;
        font-size: 12px;
    }

    .mobile-project-segment-detail strong {
        max-width: 150px;
        overflow: hidden;
        font-weight: 500;
        text-overflow: ellipsis;
    }

    .mobile-project-segment-action,
    .mobile-project-segment-empty-body {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    .mobile-project-segment-empty-body {
        align-items: center;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .mobile-empty-overtime-row {
        display: flex;
        flex: 1 0 100%;
        justify-content: flex-end;
        margin-top: 4px;
        min-width: 0;
    }

    .mobile-project-chip-box {
        box-sizing: border-box !important;
        left: 0;
        right: auto;
        top: calc(100% + 4px);
        width: min(100%, 320px);
        min-width: 0;
        max-width: 100%;
    }

    .work-member-cell {
        width: 1% !important;
        min-width: 0;
        max-width: none;
        white-space: nowrap !important;
    }

    .work-member-content {
        width: max-content;
    }

    .work-member-name {
        max-width: none;
        overflow-wrap: normal;
        white-space: nowrap;
    }

    .project-segment-cell {
        min-width: 0;
    }

    .project-segment-cell-empty {
        display: none !important;
        min-height: 0 !important;
        padding: 0 !important;
    }

    .project-segment-actions {
        width: 100%;
        justify-self: stretch;
    }

    .project-segment-action {
        flex: 1;
    }

    .project-chip-box {
        left: 0;
        min-width: min(280px, 92vw);
        max-width: 92vw;
        right: auto;
    }

    .overtime-approve-box {
        left: auto;
        right: 0;
    }

    .mobile-project-chip-box {
        box-sizing: border-box !important;
        width: min(100%, 320px);
        min-width: 0;
        max-width: 100%;
    }

    .report-action-wrapper {
        justify-content: flex-start;
        opacity: 1;
    }
}
</style>
