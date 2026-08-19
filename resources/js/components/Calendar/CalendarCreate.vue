<template>    
    <Modal @close="closeModal(false)" persist>
        <template #title>
            <p>{{ editTarget ? `スケジュールを編集する` : `新しいスケジュールを作成する`}}</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput 
                    name="calendarTitle" 
                    placeHolder="タイトルを入力（必須）" 
                    :rules="'required|max:50'"
                    :initialValue="formTarget ? formTarget.title : ''"
                    customClass="full"
                    ref="calendarTitle"
                    type="text"
                    v-model="title"
                />
            </div>
            <div class="my-[50px]">
                <p>メンバー選択</p>
                <div class="mt-[20px]">
                    <GroupSelector v-model="calendar_users" place-holder="グループ・プロジェクトから選択"/>
                </div>
                <div class="mt-[20px]">
                    <MemberSelector 
                        placeHolder="メンバー選択"
                        rules="required"
                        name="calendarUsers"
                        ref="calendarUsers"
                        path="calendar_more_users"
                        :multiple="true"
                        :closeOnSelect="false"
                        v-model="calendar_users"
                    />
                </div>
            </div>
            <div class="si-box">
                <ItemSelector 
                    placeHolder="部門選択"
                    :multiple="false"
                    :clearable="false"
                    :options="departmentsList"
                    :close-on-select="true"
                    v-model="department_id" 
                />
            </div>
            <div class="schedule-policy-grid">
                <section class="schedule-policy-field">
                    <div class="schedule-policy-field__heading">
                        <span class="schedule-policy-field__icon" aria-hidden="true">
                            <svg viewBox="0 0 131.33753 184.97316">
                                <path d="M47.86982,184.92075c-9.71497-.01469-18.85238-1.00871-27.54502-3.15382-8.68864-2.14412-15.1054-8.27555-17.29105-16.87081C.86783,156.37852-.00262,147.55584,0,138.62963l.00738-25.05799c.00597-20.26566,2.60808-40.68635,23.57663-43.26792l-.04074-27.75646C23.50925,19.36384,41.39428.86933,63.99879.02842c22.5911-.84041,43.04006,17.05437,43.25041,40.5371l.26654,29.75607c11.45221,1.47182,19.71464,9.43705,21.37176,21.02786,3.28571,22.98201,3.22513,46.52245.06766,69.5253-1.50807,10.98661-8.71111,18.8574-19.16177,21.16117-8.99576,1.98305-18.05433,2.95118-27.37892,2.93708l-34.54466-.05225ZM91.18356,67.70545l.04403-26.16306c-.39053-14.52709-12.35718-25.67967-26.29416-25.35694-14.05561.32548-25.31017,11.75799-25.2428,26.38252l.11607,25.19547,51.37686-.05798ZM109.20198,165.09569c2.50188-.95457,4.13452-6.4965,4.41532-9.35038,1.6356-16.62325,3.17936-64.30753-3.58814-67.70978-12.14006-6.10322-54.61585-4.82905-73.59241-3.53294-5.61578.38356-11.48876,1.18892-16.19185,4.03764-1.9157,2.92333-3.15088,8.71529-3.37596,12.42814-.96557,15.92802-2.39526,58.91781,3.82202,63.16492,4.45981,3.04656,10.16768,4.03807,15.96951,4.03807,19.64829,1.02064,58.28379,2.36424,72.5415-3.07567Z" />
                                <path d="M77.13115,147.96725c-8.3707.47272-14.02415.4392-23.07377.1641l6.02413-21.54667c-6.48011-3.53987-8.31091-11.68447-4.28844-17.26369,4.57977-6.35221,13.41887-6.80651,18.52648-1.14053,4.89122,5.42593,3.81553,14.57023-3.36444,18.39393l6.17605,21.39286Z" />
                            </svg>
                        </span>
                        <label for="visibility_setting">非公開設定</label>
                    </div>
                    <div class="schedule-policy-field__control">
                        <select id="visibility_setting" v-model="visibilityLevel" :class="{ 'permission-text--warning': visibilityLevel === 'private' }">
                            <option value="public">公開</option>
                            <option value="private">非公開</option>
                        </select>
                        <svg class="schedule-policy-field__chevron" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M2 4L6 8L10 4" />
                        </svg>
                    </div>
                    <p v-if="visibilityLevel === 'public'" class="schedule-policy-field__hint">全員がスケジュール内容を見られます。</p>
                    <p v-else class="schedule-policy-field__hint permission-text--warning">メンバーのみ内容を見られます。メンバー以外には「予定」と表示します。</p>
                </section>

                <section class="schedule-policy-field">
                    <div class="schedule-policy-field__heading">
                        <span class="schedule-policy-field__icon" aria-hidden="true"><Edit :size="18" /></span>
                        <label for="edit_permission">編集権限</label>
                    </div>
                    <div class="schedule-policy-field__control">
                        <select
                            id="edit_permission"
                            v-model="permissionLevel"
                            :class="{
                                'permission-text--warning': permissionLevel === 'limited',
                                'permission-text--danger': permissionLevel === 'all',
                            }"
                        >
                            <option value="members">スケジュールメンバーのみ</option>
                            <option value="limited">スケジュールメンバー ＆ 限定メンバー</option>
                            <option value="all" :disabled="visibilityLevel === 'private'">全員</option>
                        </select>
                        <svg class="schedule-policy-field__chevron" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M2 4L6 8L10 4" />
                        </svg>
                    </div>
                    <p v-if="permissionLevel === 'members'" class="schedule-policy-field__hint">スケジュールに登録したメンバーのみ編集できます。</p>
                    <p v-else-if="permissionLevel === 'limited'" class="schedule-policy-field__hint permission-text--warning">限定メンバーにも編集権限を付与します。</p>
                    <p v-else class="schedule-policy-field__hint permission-text--danger">⚠ 閲覧者全員編集可能になります。</p>
                </section>
            </div>
            <div v-if="permissionLevel === 'limited'" class="my-[50px]">
                <div class="flex items-center gap-[8px]">
                    <p>限定メンバー選択</p>
                    <span class="permission-badge--warning px-[7px] py-[3px] text-[10px] ">必須</span>
                </div>
                <div  class="mt-[20px]">
                    <GroupSelector place-holder="グループ・プロジェクトから選択" v-model="calendar_view_users"/>
                </div>
                <div class="mt-[20px]">
                    <MemberSelector 
                        placeHolder="限定メンバー選択"
                        rules="required"
                        name="calendarViewUsers"
                        ref="calendarviewUsers"
                        path="calendar_more_users"
                        :multiple="true"
                        :closeOnSelect="false"
                        v-model="calendar_view_users"
                    />
                </div>
            </div>

             
            <div class="repeat-settings__heading">
                <div>
                    <p class="repeat-settings__title">繰り返し設定</p>
                </div>
            </div>
            <div class="repeat-frequency-panel">
                <div v-if="!editTarget || edit_all_record" class="repeat-frequency" role="radiogroup" aria-label="繰り返し頻度">
                    <button type="button" role="radio" :aria-checked="repetition_type === 0" :class="['repeat-frequency__button', { active: repetition_type === 0 }]" @click="repetition_type = 0">1回のみ</button>
                    <button type="button" role="radio" :aria-checked="repetition_type === 1" :class="['repeat-frequency__button', { active: repetition_type === 1 }]" @click="repetition_type = 1">毎週</button>
                    <button type="button" role="radio" :aria-checked="repetition_type === 2" :class="['repeat-frequency__button', { active: repetition_type === 2 }]" @click="repetition_type = 2">毎月</button>
                    <button type="button" role="radio" :aria-checked="repetition_type === 3" :class="['repeat-frequency__button', { active: repetition_type === 3 }]" @click="repetition_type = 3">毎年</button>
                </div>
            </div>
            <div class="si-box repeat-settings__body">
                <div class="repeat-schedule-row">
                    <div v-if="repetition_type == 0" class="repeat-pattern">
                        <p class="repeat-field-label">開催日</p>
                        <ShortInput 
                            name="calendarNormalDate" 
                            :rules="'required'"
                            :initialValue="once_date ?? ''"
                            customClass="date"
                            ref="calendarNormalDate"
                            type="date"
                            v-model="once_date"
                        />
                    </div>
                    <div v-if="repetition_type == 3" class="repeat-pattern">
                        <p class="repeat-field-label">月・日</p>
                        <div class="repeat-control-row">
                            <OptionSelector 
                                :initialValue="repeat_span.yearly.selected_month"
                                :options="12"
                                unit="月"
                                ref="yearSelectorSelectedMonth"
                                name="yearSelectorSelectedMonth"
                                rules="required"
                                v-model="repeat_span.yearly.selected_month"
                            />
                            <OptionSelector 
                                :initialValue="repeat_span.yearly.selected_day"
                                :options="avialableDay"
                                unit="日"
                                ref="yearSelectorSelectedDay"
                                name="yearSelectorSelectedDay"
                                rules="required"
                                v-model="repeat_span.yearly.selected_day"
                            />
                        </div>

                    </div>
                    <div v-if="repetition_type == 1" class="repeat-pattern">
                        <p class="repeat-field-label">曜日</p>
                        <div class="repeat-weekdays">
                            <button
                                v-for="day in week"
                                :key="day.num"
                                type="button"
                                @click="repeat_span.weekly.selected_days[day.num] = !repeat_span.weekly.selected_days[day.num]" 
                                :aria-pressed="repeat_span.weekly.selected_days[day.num]"
                                :class="[
                                    'repeat-weekday',
                                    {
                                        active: repeat_span.weekly.selected_days[day.num],
                                        saturday: day.num === 6,
                                        sunday: day.num === 0,
                                    },
                                ]"
                            >
                                {{ day.name }}
                            </button>
                            <span v-if="!selectedDaysValid" class="repeat-field-error">曜日を選択してください。</span>
                        </div>
                    </div>
                    <div v-if="repetition_type == 2" class="repeat-pattern">
                        <p class="repeat-field-label">日にち</p>
                        <OptionSelector 
                            :initialValue="repeat_span.monthly.selected_day"
                            :options="31"
                            unit="日"
                            ref="monthlyDaySelector"
                            name="monthlyDaySelector"
                            rules="required"
                            v-model="repeat_span.monthly.selected_day"
                        />

                    </div>
                    <div class="repeat-all-day-wrap">
                        <p class="repeat-field-label">終日設定</p>
                        <label for="all_day_on" class="repeat-all-day">
                            <input @change="setAllDay" v-model="all_day" id="all_day_on" name="all_day_on" type="checkbox">
                            <span class="repeat-all-day__box" aria-hidden="true"></span>
                            <span>終日</span>
                        </label>  
                    </div>
                    <div class="repeat-time-field">
                        <p class="repeat-field-label">時間</p>
                        <div v-if="!all_day" class="repeat-time-range">
                        <ShortInput 
                            name="calendarNormalTimeStart" 
                            :rules="'required'"
                            :initialValue="time_start"
                            customClass="date"
                            ref="calendarNormalTimeStart"
                            type="time"
                            v-model="time_start"
                        />
                        <span class="repeat-range-separator" aria-hidden="true">〜</span>
                        <ShortInput 
                            name="calendarNormalTimeEnd" 
                            :rules="'required'"
                            :initialValue="time_end"
                            customClass="date"
                            ref="calendarNormalTimeEnd"
                            type="time"
                            v-model="time_end"
                            @change="onEndChange"
                            @input="onEndChange"
                        />
                        </div>
                        <p v-else class="repeat-all-day__message">終日予定として登録されます。</p>
                    </div>
                </div>
                <div v-if="repetition_type > 0" class="repeat-expiration">
                    <div v-if="repetition_type == 1 || repetition_type == 2">
                        <p class="repeat-field-label">有効期間</p>
                        <div class="repeat-expiration__range">
                            <ShortInput 
                                name="calendarRepeatSpanStart" 
                                :rules="'required'"
                                :initialValue="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_from ?? ''"
                                customClass="date"
                                ref="calendarRepeatSpanStart"
                                type="date"
                                v-model="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_from"
                            />
                            <span class="repeat-range-separator" aria-hidden="true">〜</span>
                            <ShortInput 
                                name="calendarRepeatSpanEnd" 
                                :rules="'required'"
                                :initialValue="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_to"
                                customClass="date"
                                ref="calendarRepeatSpanEnd"
                                type="date"
                                v-model="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_to"
                            />
                        </div>
                    </div>
                    <div v-if="repetition_type == 3">
                        <p class="repeat-field-label">有効期間</p>
                        <div class="repeat-expiration__range">
                            <OptionSelector 
                                :initialValue="repeat_span.yearly.year_from"
                                :options="avialabeStartYear"
                                unit="年"
                                ref="yearSelectorStart"
                                name="yearSelectorStart"
                                rules="required"
                                v-model="repeat_span.yearly.year_from"
                            />
                            <span class="repeat-range-separator" aria-hidden="true">〜</span>
                            <OptionSelector
                                :initialValue="repeat_span.yearly.year_to"
                                :options="avialabeEndYear"
                                unit="年"
                                ref="yearSelectorEnd"
                                name="yearSelectorEnd"
                                rules="required"
                                v-model="repeat_span.yearly.year_to"
                            />
                        </div>
                    </div>

                </div>
            </div>
            <div class="calendar-create-fields">
                <section class="si-box facility-selection-panel">
                    <div class="facility-selection-panel__heading">
                        <div>
                            <p class="text-[14px] ">施設・設備</p>
                        </div>
                    </div>
                    <div class="facility-selection-panel__grid">
                        <FacilitySelector
                            v-model="facility.qualified_institution"
                            :repeatSpan="repeat_span"
                            :repetitionFlag="repetition_type"
                            :time_end="time_end"
                            :time_start="time_start"
                            :once_date="once_date"
                            :editId="editTarget ? editTarget.id : null"
                            :edit_all_record="edit_all_record"
                            target="qualified_institution"
                            placeHolder="会議室"
                        />
                        <FacilitySelector
                            v-model="facility.zoom_value"
                            :repeatSpan="repeat_span"
                            :repetitionFlag="repetition_type"
                            :time_end="time_end"
                            :time_start="time_start"
                            :once_date="once_date"
                            :editId="editTarget ? editTarget.id : null"
                            :edit_all_record="edit_all_record"
                            target="zoom_value"
                            placeHolder="Web会議"
                        >
                            <template #details>
                                <div v-if="facility.zoom_value !== null" class="web-meeting-settings">
                                    <button
                                        type="button"
                                        class="calendar-fg-toggle"
                                        role="switch"
                                        :aria-checked="zoom_ai_companion"
                                        @click="zoom_ai_companion = !zoom_ai_companion"
                                    >
                                        <span class="calendar-fg-toggle__switch" :class="{ on: zoom_ai_companion }" aria-hidden="true"></span>
                                        <span>AIコンパニオン機能</span>
                                    </button>
                                    <p v-if="zoom_ai_companion" class="web-meeting-settings__warning">
                                        ※ホスト参加後にAIコンパニオンがオンになっていることを確認してください。
                                    </p>
                                    <button
                                        type="button"
                                        class="calendar-fg-toggle calendar-fg-toggle--locked"
                                        role="switch"
                                        aria-checked="true"
                                        disabled
                                    >
                                        <span class="calendar-fg-toggle__switch on" aria-hidden="true"></span>
                                        <span>文字起こし</span>
                                    </button>
                                    <p class="web-meeting-settings__note">
                                        ※すべてのWeb会議で文字起こしを既定で有効にしています。個別に無効にはできません。
                                    </p>
                                </div>
                            </template>
                        </FacilitySelector>
                        <FacilitySelector
                            v-model="facility.qualified_car"
                            :repeatSpan="repeat_span"
                            :repetitionFlag="repetition_type"
                            :time_end="time_end"
                            :time_start="time_start"
                            :once_date="once_date"
                            :editId="editTarget ? editTarget.id : null"
                            :edit_all_record="edit_all_record"
                            target="qualified_car"
                            placeHolder="車両"
                        />
                    </div>
                </section>
                <div class="si-box">                  
                    <LongInput
                        :initialValue="remarks"
                        v-model="remarks"  
                        ref="calendarRemark"
                        :placeHolder="`メモ`"
                        uId="calendarRemark"
                        name="calendarRemark"
                        rules="max:2000"
                        label="メモ"
                    />              
                </div>
                <div class="si-box">
                    <ShortInput 
                        name="calendarUrl" 
                        placeHolder="URL" 
                        rules=""
                        :initialValue="formTarget ? formTarget.referrer : ''"
                        customClass="full"
                        ref="calendarUrl"
                        type="text"
                        v-model="referrer"
                    />
                </div>  

                <div class="si-box">
                    <FileUploader
                        v-model="uploadedFiles"
                        path="/calendar_files"
                    />
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="checkConfirm" :loading="processing" content="保存する"/>
                </div>  
            </div>
        </template>
    </Modal>
    
</template>
<script setup lang="ts">
import LoaderButton from '../Global/LoaderButton.vue'
import FacilitySelector from '../Form/FacilitySelector.vue';
import { computed, onMounted, ref, watch, useTemplateRef } from 'vue';
import ShortInput from '../Form/ShortInput.vue';
import OptionSelector from '../Form/OptionSelector.vue';
import MemberSelector from '../Form/MemberSelector.vue';
import GroupSelector from '../Form/GroupSelector.vue';
import LongInput from '../Form/LongInput.vue';
import FileUploader from '../Form/FileUploader.vue';
import { useSharingDataStore } from '@/store/sharingData'
import ItemSelector from '../Form/ItemSelector.vue';
import { DateTime } from 'luxon';
import { CalendarGroupUser, RepeatDataType } from '@/interface/calendarInterface';
import { useCalendar } from '@/composables/calendar';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import Modal from '../Global/Modal.vue';
import { CommonFile } from '@/interface/globalInterface';
import Edit from '@/components/Icons/Edit.vue';
    const api = useApi()
    const sharingData = useSharingDataStore()

    const props = defineProps([
        'editTarget', 
        'duplicateTarget',
        'preSelected', 
        'edit_all_record', 
        'preSelectedMembers', 
        'preSelectedDepartment'
    ])
    const emit = defineEmits(['close'])
    const formTarget = computed(() => props.editTarget ?? props.duplicateTarget)

    const title = ref(formTarget.value?.title ?? "")
    const remarks = ref(formTarget.value?.remarks ? formTarget.value.remarks : sharingData.active ? sharingData.text : '')
    const calendar_users = ref(formTarget.value?.calendar_users ?? props.preSelectedMembers)
    const calendar_view_users = ref(formTarget.value?.calendar_view_users ?? [])
    const referrer = ref(formTarget.value?.referrer ?? "")
    type VisibilityLevel = 'public' | 'private'
    const visibilityLevel = ref<VisibilityLevel>(formTarget.value?.release_flag ? 'private' : 'public')
    type PermissionLevel = 'members' | 'limited' | 'all'
    const permissionStorageKey = 'calendarEditPermissionDefault'
    const isPermissionLevel = (value: string | null): value is PermissionLevel => {
        return value === 'members' || value === 'limited' || value === 'all'
    }
    const permissionLevel = ref<PermissionLevel>(
        formTarget.value?.release_flag
            ? formTarget.value?.calendar_view_users?.length ? 'limited' : 'members'
            : formTarget.value?.edit_all
            ? 'all'
            : formTarget.value?.members_only || formTarget.value?.calendar_view_users?.length
                ? 'limited'
                : 'members'
    )
    const zoom_ai_companion = ref(formTarget.value?.zoom_ai_companion ? true : false)
    const repetition_type = ref(formTarget.value?.repetition_type && (!props.editTarget || props.edit_all_record) ? formTarget.value.repetition_type : 0)            
    const all_day = ref(formTarget.value &&  Math.abs(DateTime.fromSQL(formTarget.value.date_start).diff(DateTime.fromSQL(formTarget.value.date_end), 'hours').as('hour')) >= 23 ? true : false)   
    
    const time_start = ref(formTarget.value?.date_start ? DateTime.fromSQL(formTarget.value.date_start).toFormat('HH:mm'): props.preSelected  ? DateTime.fromSQL(props.preSelected).toFormat('HH:mm'): DateTime.now().plus({hour: 1}).startOf('hour').toFormat('HH:mm'))
    const time_end = ref(formTarget.value?.date_end ? DateTime.fromSQL(formTarget.value.date_end).toFormat('HH:mm'): props.preSelected  ? DateTime.fromSQL(props.preSelected ).plus({hour: 1}).toFormat('HH:mm'): DateTime.now().plus({hour: 2}).startOf('hour').toFormat('HH:mm'))
    const once_date = ref<string>(formTarget.value?.date_end ? DateTime.fromSQL(formTarget.value.date_start).toISODate() as string : props.preSelected  ? DateTime.fromSQL(props.preSelected).toISODate() as string : DateTime.now().toISODate())
    const repeat_span = ref<RepeatDataType>({
        weekly: {
            selected_days: [false, true, false, false, false, false, false],
            repeat_date_from: formTarget.value && formTarget.value.repetition_type > 0 ? DateTime.fromFormat(formTarget.value.expiration_start, "yyyy-MM-dd HH:mm:ss").toISODate() as string : DateTime.now().toISODate(),
            repeat_date_to: formTarget.value && formTarget.value.repetition_type > 0 ? DateTime.fromFormat(formTarget.value.expiration_end, "yyyy-MM-dd HH:mm:ss").toISODate() as string : DateTime.now().plus({week: 1}).toISODate(),
        },
        monthly: {
            selected_day: formTarget.value && formTarget.value.repeat_days !== null ? parseInt(formTarget.value.repeat_days) : DateTime.now().day, 
            repeat_date_from: formTarget.value && formTarget.value.repetition_type > 0 ? DateTime.fromISO(formTarget.value.expiration_start).toISODate() as string : DateTime.now().toISODate(),
            repeat_date_to: formTarget.value && formTarget.value.repetition_type > 0 ? DateTime.fromISO(formTarget.value.expiration_end).toISODate() as string : DateTime.now().plus({month: 1}).toISODate(),
        },
        yearly: {
            selected_month: formTarget.value && formTarget.value.repeat_month !== null ? parseInt(formTarget.value.repeat_month) : DateTime.now().plus({month: 1}).month,
            selected_day: formTarget.value && formTarget.value.repeat_days !== null ? parseInt(formTarget.value.repeat_days) : DateTime.now().day,
            year_from: formTarget.value && formTarget.value.repetition_type > 0 ? DateTime.fromISO(formTarget.value.expiration_start).year : DateTime.now().year,
            year_to: formTarget.value && formTarget.value.repetition_type > 0 ? DateTime.fromISO(formTarget.value.expiration_end).year : DateTime.now().plus({year: 1}).year
        }
    })
    type FacilityForm = {
        qualified_institution: string | null
        qualified_car: string | null
        zoom_value: string | null
    }

    type ConvertedFacilityForm = {
        qualified_institution: number | null
        qualified_car: number | null
        zoom_value: number | null
    }
    const facility = ref<FacilityForm>({
        qualified_institution: formTarget.value?.qualified_institution != null
            ? formTarget.value.qualified_institution.toString()
            : null,
        qualified_car: formTarget.value?.qualified_car != null
            ? formTarget.value.qualified_car.toString()
            : null,
        zoom_value: formTarget.value?.zoom_value != null
            ? formTarget.value.zoom_value.toString()
            : null,
    })
    const uploadedFiles = ref(formTarget.value?.files ?? [])
    const processing = ref(false)
    const calendarRemark = ref(null)
    const department_id = ref(formTarget.value?.department_id ?? props.preSelectedDepartment?.id ?? '')
    const { departmentsList } = useCalendar()
    const endTouched = ref(false)
    onMounted(() => {
        if(formTarget.value && formTarget.value.repetition_type == 1 && formTarget.value.repeat_week){
            const repeats = formTarget.value.repeat_week.split(',').map(Number);
            let pre = [false, false, false, false, false, false, false]
            repeats.forEach((val: number) => {                
                pre[val] = true
            });
            repeat_span.value.weekly.selected_days = pre
        }
        if (!formTarget.value) {
            const storedPermission = localStorage.getItem(permissionStorageKey)
            if (isPermissionLevel(storedPermission)) {
                permissionLevel.value = storedPermission
            } else if (localStorage.getItem('editAllDefault') === '1') {
                permissionLevel.value = 'all'
            }
        }
        const calendarDepartment = localStorage.getItem('calendarDepartment')
        if (!formTarget.value && !props.preSelectedDepartment && calendarDepartment) {
            department_id.value = Number(calendarDepartment)
        }
    })
    watch(permissionLevel, (value) => {
        localStorage.setItem(permissionStorageKey, value)
        localStorage.setItem('editAllDefault', value === 'all' ? '1' : '0')
    })
    watch(visibilityLevel, (value) => {
        if (value === 'private' && permissionLevel.value === 'all') {
            permissionLevel.value = 'members'
        }
    })
    const setAllDay = (event: Event) => {
        const target = event.target as HTMLInputElement
        if(target.checked){
            time_start.value = '00:00'
            time_end.value = '23:59'
        }
    }
    const closeModal = (val: boolean) => {
        const shareData = {
            active: false,
            title: '',
            text: '',
            files: [],
            from: '',
            to: '',
            drag: false,
            instruction: ''
        }
        sharingData.setSharingData(shareData)
        emit('close', val)
    }

    const calendarUsers = useTemplateRef<InstanceType<typeof MemberSelector>>('calendarUsers')
    const calendarviewUsers = useTemplateRef<InstanceType<typeof MemberSelector>>('calendarviewUsers')
    const calendarTitle = useTemplateRef<InstanceType<typeof ShortInput>>('calendarTitle')
    const calendarNormalTimeStart = useTemplateRef<InstanceType<typeof ShortInput>>('calendarNormalTimeStart')
    const calendarNormalTimeEnd = useTemplateRef<InstanceType<typeof ShortInput>>('calendarNormalTimeEnd')
    const calendarNormalDate = useTemplateRef<InstanceType<typeof ShortInput>>('calendarNormalDate')
    const calendarRepeatSpanEnd = useTemplateRef<InstanceType<typeof ShortInput>>('calendarRepeatSpanEnd')
    const calendarRepeatSpanStart = useTemplateRef<InstanceType<typeof ShortInput>>('calendarRepeatSpanStart')
    const monthlyDaySelector = useTemplateRef<InstanceType<typeof OptionSelector>>('monthlyDaySelector')
    const yearSelectorSelectedDay = useTemplateRef<InstanceType<typeof OptionSelector>>('yearSelectorSelectedDay')
    const yearSelectorSelectedMonth = useTemplateRef<InstanceType<typeof OptionSelector>>('yearSelectorSelectedMonth')
    const yearSelectorEnd = useTemplateRef<InstanceType<typeof OptionSelector>>('yearSelectorEnd')
    const yearSelectorStart = useTemplateRef<InstanceType<typeof OptionSelector>>('yearSelectorStart')
    const { ping } = useDialog()
    const validateTargets = computed(() => {
        const targets = [
            calendarUsers.value,
            calendarTitle.value, 
            calendarNormalTimeStart.value, 
            calendarNormalTimeEnd.value, 
            calendarNormalDate.value, 
            calendarRepeatSpanEnd.value,
            calendarRepeatSpanStart.value,
            monthlyDaySelector.value, 
            yearSelectorSelectedDay.value,
            yearSelectorSelectedMonth.value, 
            yearSelectorEnd.value, 
            yearSelectorStart.value,
        ]
        if (permissionLevel.value === 'limited') {
            targets.push(calendarviewUsers.value)
        }
        return targets
    })  
    const second_validation = async () => {
        if(time_start.value == time_end.value){
            return {
                valid: false,
                error: '開始時間と終了時間は同じにすることが出来ません。'
            }
        }else {
            if(DateTime.fromFormat(time_end.value, 'HH:mm') < DateTime.fromFormat(time_start.value, 'HH:mm')){
                return {
                    valid: false,
                    error: '終了時間は開始時間より先にすることが出来ません。'
                }
            }            
        }
        return {
            valid: true,
            error: ''
        }
    }
    const checkConfirm = async () => {
        createSend()        
    }
    const createSend = async () => {
        
        
        const targets = validateTargets.value.filter(ob => ob !== null)
        let result = true
        for(const target of targets){
            
            const val = await target?.validate() || {valid: false}
            result = result && val.valid
        }
        if (!result) {
            ping('必須項目が未入力または不正な値が入力されています。')
            processing.value = false
            return
        }
        const second_validate = await second_validation()
        if(!second_validate.valid){
            ping(second_validate.error)
            processing.value = false
            return
        }
        processing.value = true
        
        const convertableFacilities = {} as ConvertedFacilityForm

        for (const key in facility.value) {
            const typedKey = key as keyof FacilityForm
            const val = facility.value[typedKey]

            convertableFacilities[typedKey] = val !== null ? parseInt(val, 10) : null
        }
        const params = {
            editId: props.editTarget ? props.editTarget.id : null,
            edit_repeat: props.edit_all_record,
            title: title.value,
            remarks: remarks.value,
            users: calendar_users.value.map((ob: CalendarGroupUser)  => ob.id),
            referrer: referrer.value,
            release_flag: visibilityLevel.value === 'private',
            edit_all: permissionLevel.value === 'all',
            repetition_type: repetition_type.value,
            zoom_ai_companion: zoom_ai_companion.value,
            time_start:  all_day.value ? '00:00' : time_start.value,
            time_end: all_day.value ? '23:59' : time_end.value,
            once_date: once_date.value,
            repeat_span: repeat_span.value,
            facility: convertableFacilities,
            file_ids: uploadedFiles.value.length ? uploadedFiles.value.map((ob: CommonFile) => ob.id) : [],
            department_id: department_id.value,
            view_users: permissionLevel.value === 'limited'
                ? calendar_view_users.value.map((ob: CalendarGroupUser) => ob.id)
                : [],
            members_only: false
        }
        
        try {
            const response = await api.post('/calendar_add_record', params, {
                toast: props.editTarget ? '編集しました。' : '作成しました。'
            })

            if (response === null) return

            sharingData.$reset()
            emit('close', true)
        } catch {
            // useApi displays the server error; keep the form open for correction and retry.
        } finally {
            processing.value = false
        }

    }

    const selectedDaysValid = computed(() => {
        if(repetition_type.value == 1){
            const selected = repeat_span.value.weekly.selected_days.filter(ob => ob == true)
            return selected.length
        }
        return true
    })
    const avialableDay = computed(() => {
        repeat_span.value
        if(repeat_span.value.yearly.selected_month){
            const month = repeat_span.value.yearly.selected_month
            if (month === 2) {
                return Array.from({ length: 28 }, (_, index) => index + 1);
            } else {
                const is31DaysMonth = DateTime.fromISO(`${DateTime.now().year}-${month}-31`).isValid;
                return Array.from({ length: is31DaysMonth ? 31 : 30 }, (_, index) => index + 1);
            }
        }else{
            const def = Array.from({ length: 31 }, (_, index) => index + 1);
            return def
        }        
    })
    const week = computed(() => {
        return [
            { num: 1, name: '月'},
            { num: 2, name: '火'},
            { num: 3, name: '水'},
            { num: 4, name: '木'},
            { num: 5, name: '金'},
            { num: 6, name: '土'},
            { num: 0, name: '日'}
        ]
    })
    const avialabeStartYear = computed(() => {
        const thisYear = DateTime.now().year
        const limit = thisYear + 10
        const list = Array.from({ length: limit - thisYear + 1 }, (_, i) => thisYear + i);
        return list
    })
    const avialabeEndYear = computed(() => {
        const thisYear = repeat_span.value.yearly.year_from
        const limit = thisYear + 10
        const list = Array.from({ length: limit - thisYear + 1 }, (_, i) => thisYear + i);
        return list
    })
    watch(() => department_id.value, (newVal, oldVal) => {
        if(newVal !== oldVal){
            localStorage.setItem('calendarDepartment', newVal)
        }
    })
    watch(time_start, (newVal) => {
        if (endTouched.value || !newVal) return

        const dt = DateTime.fromFormat(newVal, 'HH:mm')
        if (!dt.isValid) return

        time_end.value = dt.plus({ hours: 1 }).toFormat('HH:mm')
    })
    const onEndChange = () => {
        endTouched.value = true
    }
</script>

<style scoped>
.schedule-policy-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.schedule-policy-field {
    min-width: 0;
}

.schedule-policy-field__heading {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    min-height: 20px;
}

.schedule-policy-field__heading label {
    min-width: 0;
    overflow: hidden;
    color: var(--primary-color);
    font-size: 12px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.schedule-policy-field__icon {
    display: grid;
    place-items: center;
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
    color: var(--primary-color);
}

.schedule-policy-field__icon > svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.schedule-policy-field__control {
    position: relative;
    margin-top: 7px;
}

.schedule-policy-field__control select {
    width: 100%;
    height: 40px;
    padding: 0 34px 0 10px;
    box-sizing: border-box !important;
    overflow: hidden;
    color: var(--primary-color);
    font: inherit;
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
    border: 1px solid var(--primary-color);
    border-radius: 0;
    outline: none;
    appearance: none;
    background: var(--background-color);
    cursor: pointer;
}

.schedule-policy-field__control select:focus {
    outline: 1px solid var(--primary-color);
    outline-offset: 1px;
}

.schedule-policy-field__chevron {
    position: absolute;
    top: 50%;
    right: 12px;
    width: 12px;
    height: 12px;
    color: var(--primary-color);
    stroke: currentColor;
    stroke-width: 1.4;
    stroke-linecap: round;
    stroke-linejoin: round;
    pointer-events: none;
    transform: translateY(-50%);
}

.schedule-policy-field__hint {
    min-height: 18px;
    margin: 7px 0 0;
    color: var(--sub-color);
    font-size: 10px;
    line-height: 1.5;
}

@media (max-width: 620px) {
    .schedule-policy-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }
}

.repeat-settings__heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-top: 34px;
}

.repeat-settings__title {
    margin: 0;
    color: var(--primary-color);
    font-size: 15px;
    font-weight: 600;
}

.repeat-frequency-panel {
    margin-top: 12px;
    padding: 4px;
    background: var(--bg3);
}

.repeat-frequency {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 4px;
}

.repeat-frequency__button {
    min-width: 0;
    height: 40px;
    padding: 0 12px;
    box-sizing: border-box !important;
    color: var(--sub-color);
    font: inherit;
    font-size: 12px;
    border: 1px solid transparent;
    background: transparent;
    cursor: pointer;
    white-space: nowrap;
    transition: color 120ms ease, border-color 120ms ease, background 120ms ease;
}

.repeat-frequency__button:hover {
    color: var(--primary-color);
    background: color-mix(in srgb, var(--background-color) 72%, transparent);
}

.repeat-frequency__button.active {
    color: var(--primary-color);
    font-weight: 600;
    border-color: var(--primary-color);
    background: var(--background-color);
}

.repeat-frequency__button:focus-visible {
    outline: 1px solid var(--primary-color);
    outline-offset: 1px;
}

.repeat-settings__body {
    margin-top: 12px !important;
    padding: 16px;
    box-sizing: border-box !important;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
}

.repeat-schedule-row {
    display: grid;
    grid-template-columns: minmax(150px, 1fr) auto minmax(225px, 1.25fr);
    gap: 18px;
    align-items: end;
}

.repeat-pattern,
.repeat-time-field,
.repeat-all-day-wrap {
    min-width: 0;
}

.repeat-field-label {
    margin: 0 0 8px;
    color: var(--sub-color);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.04em;
}

.repeat-control-row,
.repeat-time-range,
.repeat-expiration__range {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.repeat-weekdays {
    position: relative;
    display: grid;
    grid-template-columns: repeat(7, minmax(34px, 1fr));
    width: 100%;
    max-width: 350px;
    box-sizing: border-box !important;
    border: 1px solid var(--formBorder);
}

.repeat-weekday {
    display: grid;
    place-items: center;
    min-width: 0;
    height: 40px;
    padding: 0;
    color: var(--sub-color);
    font: inherit;
    font-size: 12px;
    border: 0;
    border-left: 1px solid var(--calendarBorder);
    background: var(--background-color);
    cursor: pointer;
    transition: color 120ms ease, background 120ms ease;
}

.repeat-weekday:first-child {
    border-left: 0;
}

.repeat-weekday:hover {
    color: var(--primary-color);
    background: var(--bg3);
}

.repeat-weekday.saturday {
    color: color-mix(in srgb, var(--primary-color) 62%, #3b82f6);
}

.repeat-weekday.sunday {
    color: tomato;
}

.repeat-weekday.active {
    color: var(--background-color);
    background: var(--primary-color);
}

.repeat-weekday:focus-visible {
    position: relative;
    z-index: 1;
    outline: 1px solid var(--primary-color);
    outline-offset: -2px;
}

.repeat-field-error {
    grid-column: 1 / -1;
    width: 100%;
    padding: 5px 7px;
    box-sizing: border-box !important;
    color: tomato;
    font-size: 10px;
    background: var(--background-color);
}

.repeat-all-day {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 40px;
    padding: 0 11px;
    box-sizing: border-box !important;
    color: var(--primary-color);
    font-size: 12px;
    border: 1px solid var(--formBorder);
    cursor: pointer;
}

.repeat-all-day input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.repeat-all-day__box {
    position: relative;
    width: 16px;
    height: 16px;
    flex: 0 0 16px;
    box-sizing: border-box !important;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
}

.repeat-all-day input:checked + .repeat-all-day__box {
    border-color: var(--primary-color);
    background: var(--primary-color);
}

.repeat-all-day input:checked + .repeat-all-day__box::after {
    position: absolute;
    top: 2px;
    left: 5px;
    width: 4px;
    height: 8px;
    content: '';
    border-right: 2px solid var(--background-color);
    border-bottom: 2px solid var(--background-color);
    transform: rotate(45deg);
}

.repeat-all-day:focus-within {
    border-color: var(--primary-color);
}

.repeat-range-separator {
    flex: 0 0 auto;
    color: var(--sub-color);
    font-size: 12px;
}

.repeat-all-day__message {
    display: flex;
    align-items: center;
    min-height: 40px;
    margin: 0;
    color: var(--sub-color);
    font-size: 11px;
}

.repeat-expiration {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px dashed var(--calendarBorder);
}

@media (max-width: 760px) {
    .repeat-schedule-row {
        grid-template-columns: 1fr 1fr;
    }

    .repeat-time-field {
        grid-column: 1 / -1;
    }
}

@media (max-width: 620px) {
    .repeat-frequency-panel {
        padding: 3px;
    }

    .repeat-frequency {
        gap: 2px;
    }

    .repeat-frequency__button {
        height: 34px;
        padding: 0 2px;
        font-size: 10px;
    }
}

@media (max-width: 480px) {

    .repeat-schedule-row {
        grid-template-columns: 1fr;
    }

    .repeat-time-field {
        grid-column: auto;
    }

    .repeat-control-row,
    .repeat-time-range,
    .repeat-expiration__range {
        flex-wrap: wrap;
    }
}

.facility-selection-panel {
    position: relative;
    width: 100%;
    overflow: visible;
    box-sizing: border-box !important;
}

.facility-selection-panel__heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 20px;
}

.facility-selection-panel__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    align-items: start;
}

.web-meeting-settings {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    margin-top: 8px;
    padding: 10px;
    box-sizing: border-box !important;
    background: var(--bg3);
}

.calendar-fg-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    padding: 0;
    color: var(--primary-color);
    font: inherit;
    font-size: 11px;
    text-align: left;
    border: 0;
    background: transparent;
    cursor: pointer;
}

.calendar-fg-toggle__switch {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
    flex: 0 0 36px;
    border-radius: 10px;
    background: var(--formBorder);
    transition: background 120ms ease;
}

.calendar-fg-toggle__switch::after {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    content: '';
    border-radius: 50%;
    background: #fff;
    transition: left 120ms ease;
}

.calendar-fg-toggle__switch.on {
    background: var(--primary-color);
}

.calendar-fg-toggle__switch.on::after {
    left: 18px;
}

.calendar-fg-toggle:focus-visible {
    outline: 1px solid var(--primary-color);
    outline-offset: 3px;
}

/* 常時ONで切り替えできない項目（文字起こし）。押せないことが見た目で分かるようにする */
.calendar-fg-toggle--locked {
    opacity: 0.55;
    cursor: not-allowed;
}

.web-meeting-settings__warning {
    margin: 0;
    color: tomato;
    font-size: 10px;
    line-height: 1.5;
}

.web-meeting-settings__note {
    margin: 0;
    color: var(--third-color);
    font-size: 10px;
    line-height: 1.5;
}

@media (max-width: 620px) {
    .facility-selection-panel__heading {
        flex-wrap: wrap;
    }

    .facility-selection-panel__grid {
        grid-template-columns: 1fr;
    }
}

.permission-text--warning {
    color: color-mix(in srgb, var(--primary-color) 72%, #f59e0b);
}

.permission-text--danger {
    color: tomato;
}

.permission-badge--warning {
    color: color-mix(in srgb, var(--primary-color) 72%, #f59e0b);
    background: color-mix(in srgb, var(--background-color) 76%, #f59e0b);
}
</style>
