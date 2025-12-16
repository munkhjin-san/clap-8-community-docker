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
                    :initialValue="editTarget ? editTarget.title : ''"
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
            <div class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">編集許可</p>
                </div>
                <div class="selectSwitchArea" style="width: fit-content;">    
                    <input @change="setEditAllDefault" type="checkbox" id="edit_all" v-model="edit_all" :disabled="release_flag || members_only ? true : false">
                    <label for="edit_all" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer', {'disabled-toggle' : release_flag || members_only}]"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    <span v-if="release_flag || members_only" style="font-size: 11px;color:gray;position: absolute;white-space: nowrap;left: 0;bottom: -27px;">非公開設定またはメンバー限定ONのため設定できません</span>
                </div>  
            </div> 
            <div class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">非公開設定</p>
                </div>
                <div class="selectSwitchArea" style="width: fit-content;">    
                    <input type="checkbox" id="release_flag" v-model="release_flag" :disabled="edit_all || members_only ? true : false">
                    <label for="release_flag" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer', {'disabled-toggle' : edit_all || members_only}]"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    <span v-if="edit_all || members_only" style="font-size: 11px;color:gray;position: absolute;white-space: nowrap;left: 0;bottom: -27px;">非公開設定または編集許可ONのため設定できません</span>
                </div>  
            </div>  
            <div class="si-box" style="position: relative">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">メンバー限定</p>
                </div>
                <div class="selectSwitchArea" style="width: fit-content;">    
                    <input type="checkbox" id="members_only" v-model="members_only" :disabled="release_flag || edit_all ? true : false">
                    <label for="members_only" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer', {'disabled-toggle' : release_flag || edit_all}]"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    <span v-if="release_flag || edit_all" style="font-size: 11px;color:gray;position: absolute;white-space: nowrap;left: 0;bottom: -27px;">編集許可または非公開設定ONのため設定できません</span>
                </div>
            </div>
            <div v-if="members_only" class="my-[50px]">
                <p>限定メンバー選択</p>
                <div  class="mt-[20px]">
                    <GroupSelector place-holder="グループ・プロジェクトから選択" v-model="calendar_view_users"/>
                </div>
                <div class="mt-[20px]">
                    <MemberSelector 
                        placeHolder="限定メンバー選択"
                        rules="required"
                        name="calendarUsers"
                        ref="calendarviewUsers"
                        path="calendar_more_users"
                        :multiple="true"
                        :closeOnSelect="false"
                        v-model="calendar_view_users"
                    />
                </div>
            </div>

             
            <div style="margin: 30px 0 -10px 0;">
                <p :class="['form-title-small', 'form-title-active']">繰り返し設定</p>
            </div>
            <div class="si-box">                
                <div v-if="!editTarget || edit_all_record" style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap;">
                    <div @click="repetition_type = 0" :class="['ch-selector', { chSelected: repetition_type == 0}]">1回のみ</div>
                    <div @click="repetition_type = 1" :class="['ch-selector', { chSelected: repetition_type == 1}]">毎週</div>
                    <div @click="repetition_type = 2" :class="['ch-selector', { chSelected: repetition_type == 2}]">毎月</div>
                    <div @click="repetition_type = 3" :class="['ch-selector', { chSelected: repetition_type == 3}]">毎年</div>

                </div>
            </div>
            <div class="si-box">
                <div style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap;min-height: 40px;align-items: center;">
                    <div v-if="repetition_type == 0">
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
                    <div v-if="repetition_type == 3">
                        <div style="display: flex;gap: 10px;margin-right: 15px;">
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
                    <div v-if="repetition_type == 1">
                        <div style="display: flex;gap: 10px;margin-right: 15px;position:relative">
                            <div 
                                v-for="day in week"
                                @click="repeat_span.weekly.selected_days[day.num] = !repeat_span.weekly.selected_days[day.num]" 
                                :class="['weekDayPicker', {weekDayPickerSelected : repeat_span.weekly.selected_days[day.num]}]" 
                            >
                                {{ day.name }}
                            </div>
                            <span v-if="!selectedDaysValid" class="form-error" style="font-size: 11px;color:tomato;position: absolute;left: 0;bottom: -15px;">必須です</span>
                        </div>
                    </div>
                    <div v-if="repetition_type == 2">
                        
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
                    <div style="display: flex;">
                        <label for="all_day_on" class="check-container privacy-check" style="align-self: center;white-space: nowrap;">
                            <input @change="setAllDay" v-model="all_day" id="all_day_on" name="all_day_on" type="checkbox">
                            <span :class="['checkmark-mini', {'checkmark-mini-checked' : all_day}]"></span>
                            終日
                        </label>  
                    </div>
                    <div style="display: flex;gap: 10px;">                        
                        
                        <ShortInput 
                            v-if="!all_day"
                            name="calendarNormalTimeStart" 
                            :rules="'required'"
                            :initialValue="time_start"
                            customClass="date"
                            ref="calendarNormalTimeStart"
                            type="time"
                            v-model="time_start"
                        />
                        <ShortInput 
                            v-if="!all_day"
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
                </div>
                <div class="si-box" style="margin-top: 20px;">
                    <div v-if="repetition_type == 1 || repetition_type == 2">
                        <div>
                            <p :class="['form-title-small']">有効期限設定</p>
                        </div>
                        <div style="display: flex;gap: 10px;margin-top: 20px;">
                            <ShortInput 
                                name="calendarRepeatSpanStart" 
                                :rules="'required'"
                                :initialValue="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_from ?? ''"
                                customClass="date"
                                ref="calendarRepeatSpanStart"
                                type="date"
                                v-model="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_from"
                            />
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
                        <div>
                            <p :class="['form-title-small']">有効期限設定</p>
                        </div>
                        <div style="display: flex;gap: 10px;margin-top: 20px;">
                            <OptionSelector 
                                :initialValue="repeat_span.yearly.year_from"
                                :options="avialabeStartYear"
                                unit="年"
                                ref="yearSelectorStart"
                                name="yearSelectorStart"
                                rules="required"
                                v-model="repeat_span.yearly.year_from"
                            />
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
                <div class="si-box">
                    <FacilitySelector 
                        v-model="facility.qualified_institution"
                        :repeatSpan="repeat_span"
                        :repetitionFlag="repetition_type"
                        :time_end="time_end"
                        :time_start="time_start"
                        :once_date="once_date"
                        :facility="facilitiesList"
                        :editId="editTarget ? editTarget.id : null"
                        :edit_all_record="edit_all_record"
                        target="qualified_institution"
                        placeHolder="施設選択"
                        ref="calendarFacility"
                    />
                </div>
                <div class="si-box">
                    <FacilitySelector 
                        v-model="facility.zoom_value"
                        :repeatSpan="repeat_span"
                        :repetitionFlag="repetition_type"
                        :time_end="time_end"
                        :time_start="time_start"
                        :once_date="once_date"
                        :facility="facilitiesList"
                        :editId="editTarget ? editTarget.id : null"
                        :edit_all_record="edit_all_record"
                        target="zoom_value"
                        placeHolder="WEB会議選択"   
                        ref="calendarZoom"
                    />
                </div>
                <div v-if="facility.zoom_value" class="si-box" style="position:relative;">
                    <div>
                        <p :class="['form-title-small', {'form-title-active' : release_flag}]">WEB会議待機室</p>
                    </div>
                    <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                        <input type="checkbox" id="zoom_waiting_room" v-model="zoom_waiting_room">
                        <label for="zoom_waiting_room" style="min-width: 80px;" class="cursor-pointer"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                    </div>  

                    <div class="mt-[20px]">
                        <p :class="['form-title-small']">AIコンパニオン機能</p>
                    </div>
                    <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                        <input type="checkbox" id="zoom_ai_companion" v-model="zoom_ai_companion">
                        <label for="zoom_ai_companion" style="min-width: 80px;" class="cursor-pointer"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                    </div>  
                    <span v-if="zoom_ai_companion" class="text-[12px] text-[tomato]">※AIコンパニオン機能は、ミーティングのホストが参加するまで開始されません。<br>ホストがミーティングを開始した後、AIコンパニオン機能がオンになっていることを確認してください。</span>
                </div>  
                <div class="si-box">
                    <FacilitySelector 
                        v-model="facility.qualified_car"
                        :repeatSpan="repeat_span"
                        :repetitionFlag="repetition_type"
                        :time_end="time_end"
                        :time_start="time_start"
                        :once_date="once_date"
                        :facility="facilitiesList"
                        :editId="editTarget ? editTarget.id : null"
                        :edit_all_record="edit_all_record"
                        target="qualified_car"
                        placeHolder="車両選択"
                        ref="calendarCars"
                    />
                </div>

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
                        :initialValue="editTarget ? editTarget.referrer : ''"
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
import { RepeatDataType } from '@/interface/calendarInterface';
import { useCalendar } from '@/composables/calendar';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import Modal from '../Global/Modal.vue';
    const api = useApi()
    const sharingData = useSharingDataStore()

    const props = defineProps([
        'editTarget', 
        'preSelected', 
        'edit_all_record', 
        'preSelectedMembers', 
        'preSelectedDepartment'
    ])
    const emit = defineEmits(['close'])

    const title = ref(props.editTarget && props.editTarget.title ? props.editTarget.title : "")
    const remarks = ref(props.editTarget && props.editTarget.remarks ? props.editTarget.remarks : sharingData.active ? sharingData.text : '')
    const calendar_users = ref(props.editTarget && props.editTarget.calendar_users ? props.editTarget.calendar_users : props.preSelectedMembers)
    const calendar_view_users = ref(props.editTarget && props.editTarget.calendar_view_users ? props.editTarget.calendar_view_users : [])
    const referrer = ref(props.editTarget && props.editTarget.referrer ? props.editTarget.referrer : "")
    const release_flag = ref(props.editTarget && props.editTarget.release_flag ? true : false)
    const edit_all = ref(props.editTarget && props.editTarget.edit_all ? true : false)
    const zoom_waiting_room = ref(props.editTarget && props.editTarget.zoom_waiting_room ? true : false)
    const zoom_ai_companion = ref(props.editTarget && props.editTarget.zoom_ai_companion ? true : false)
    const repetition_type = ref(props.editTarget && props.editTarget.repetition_type && props.edit_all_record ? props.editTarget.repetition_type : 0)            
    const all_day = ref(props.editTarget &&  Math.abs(DateTime.fromSQL(props.editTarget.date_start).diff(DateTime.fromSQL(props.editTarget.date_end), 'hours').as('hour')) >= 23 ? true : false)   
    
    const time_start = ref(props.editTarget && props.editTarget.date_start ? DateTime.fromSQL(props.editTarget.date_start).toFormat('HH:mm'): props.preSelected  ? DateTime.fromSQL(props.preSelected).toFormat('HH:mm'): DateTime.now().plus({hour: 1}).startOf('hour').toFormat('HH:mm'))
    const time_end = ref(props.editTarget && props.editTarget.date_end ? DateTime.fromSQL(props.editTarget.date_end).toFormat('HH:mm'): props.preSelected  ? DateTime.fromSQL(props.preSelected ).plus({hour: 1}).toFormat('HH:mm'): DateTime.now().plus({hour: 2}).startOf('hour').toFormat('HH:mm'))
    const once_date = ref<string>(props.editTarget && props.editTarget.date_end ? DateTime.fromSQL(props.editTarget.date_start).toISODate() as string : props.preSelected  ? DateTime.fromSQL(props.preSelected).toISODate() as string : DateTime.now().toISODate())
    const repeat_span = ref<RepeatDataType>({
        weekly: {
            selected_days: [false, true, false, false, false, false, false],
            repeat_date_from: props.editTarget && props.editTarget.repetition_type > 0 ? DateTime.fromFormat(props.editTarget.expiration_start, "yyyy-MM-dd HH:mm:ss").toISODate() as string : DateTime.now().toISODate(),
            repeat_date_to: props.editTarget && props.editTarget.repetition_type > 0 ? DateTime.fromFormat(props.editTarget.expiration_end, "yyyy-MM-dd HH:mm:ss").toISODate() as string : DateTime.now().plus({week: 1}).toISODate(),
        },
        monthly: {
            selected_day: props.editTarget && props.editTarget.repeat_days !== null ? parseInt(props.editTarget.repeat_days) : DateTime.now().day, 
            repeat_date_from: props.editTarget && props.editTarget.repetition_type > 0 ? DateTime.fromISO(props.editTarget.expiration_start).toISODate() as string : DateTime.now().toISODate(),
            repeat_date_to: props.editTarget && props.editTarget.repetition_type > 0 ? DateTime.fromISO(props.editTarget.expiration_end).toISODate() as string : DateTime.now().plus({month: 1}).toISODate(),
        },
        yearly: {
            selected_month: DateTime.now().plus({month: 1}).month,
            selected_day: props.editTarget && props.editTarget.repeat_days !== null ? parseInt(props.editTarget.repeat_days) : DateTime.now().day,
            year_from: props.editTarget && props.editTarget.repetition_type > 0 ? DateTime.fromISO(props.editTarget.expiration_start).year : DateTime.now().year,
            year_to: props.editTarget && props.editTarget.repetition_type > 0 ? DateTime.fromISO(props.editTarget.expiration_start).year : DateTime.now().plus({year: 1}).year
        }
    })
    const facility = ref({
        qualified_institution:  ref( props.editTarget && props.editTarget.qualified_institution !== null ? props.editTarget.qualified_institution.toString() : null),
        qualified_car: ref(props.editTarget && props.editTarget.qualified_car !== null ? props.editTarget.qualified_car.toString() : null),
        zoom_value: ref(props.editTarget && props.editTarget.zoom_value !== null ? props.editTarget.zoom_value.toString() : null)
    })
    const uploadedFiles = ref(props.editTarget && props.editTarget.files ? props.editTarget.files : [])
    const processing = ref(false)
    const calendarRemark = ref(null)
    const department_id = ref(props.editTarget?.department_id ?? props.preSelectedDepartment?.id ?? '')
    const members_only = ref(props.editTarget?.members_only ? true : false)
    const {  facilitiesList, departmentsList } = useCalendar()
    const endTouched = ref(false)
    onMounted(() => {
        if(props.editTarget && props.editTarget.repetition_type == 1 && props.editTarget.repeat_week){
            const repeats = props.editTarget.repeat_week.split(',').map(Number);
            let pre = [false, false, false, false, false, false, false]
            repeats.forEach(val => {                
                pre[val] = true
            });
            repeat_span.value.weekly.selected_days = pre
        }
        if(!props.editTarget){
            const editAll = localStorage.getItem('editAllDefault')
            if(editAll && Number(editAll) == 1){
                edit_all.value = true
            }
        }
        const calendarDepartment = localStorage.getItem('calendarDepartment')
        if (calendarDepartment) {
            department_id.value = Number(calendarDepartment)
        }
    })
    const setEditAllDefault = (event) => {
        const val = event.target.checked ? 1 : 0
        localStorage.setItem('editAllDefault', val.toString())            
    }
    
    const setAllDay = (event: Event) => {
        const target = event.target as HTMLInputElement
        if(target.checked){
            time_start.value = '00:00'
            time_end.value = '23:59'
        }
    }
    const closeModal = (val) => {
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
        return [
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
        
        let convertableFacilities = {};
        for (let key in facility.value) {
            convertableFacilities[key] =  facility.value[key] !== null ? parseInt(facility.value[key]) : null            
        }
        const params = {
            editId: props.editTarget ? props.editTarget.id : null,
            edit_repeat: props.edit_all_record,
            title: title.value,
            remarks: remarks.value,
            users: calendar_users.value.map(ob => ob.id),
            referrer: referrer.value,
            release_flag: release_flag.value,
            edit_all: !release_flag.value ? edit_all.value : false,
            repetition_type: repetition_type.value,
            zoom_waiting_room: zoom_waiting_room.value,
            zoom_ai_companion: zoom_ai_companion.value,
            time_start:  all_day.value ? '00:00' : time_start.value,
            time_end: all_day.value ? '23:59' : time_end.value,
            once_date: once_date.value,
            repeat_span: repeat_span.value,
            facility: convertableFacilities,
            file_ids: uploadedFiles.value.length ? uploadedFiles.value.map(ob => ob.id) : [],
            department_id: department_id.value,
            view_users: calendar_view_users.value.map(ob => ob.id),
            members_only: members_only.value
        }
        
        await api.post('/calendar_add_record', params, {
            toast: props.editTarget ? '編集しました。' : '作成しました。'
        })

        processing.value = false
        sharingData.$reset()
        emit('close', true)     

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