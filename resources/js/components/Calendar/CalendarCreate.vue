<template>    
    <div class="overlay" @mousedown="closeModal(false)">                         
        <div class="chatCreate scrollable" @mousedown.stop>     
            <div class="recordFormTitle" style="display:flex">
                <p>{{ editTarget ? `スケジュールを編集する` : `新しいスケジュールを作成する`}}</p>
                <div class="m-close-button" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
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
            <GroupSelector v-model="calendar_users"/>
            <div class="si-box">
                <MemberSelector 
                    placeHolder="メンバー選択"
                    rules="required"
                    name="calendarUsers"
                    ref="calendarUsers"
                    path="calendar_more_users"
                    :closeOnSelect="false"
                    v-model="calendar_users"
                />
            </div>
            <div class="si-box">
                <ItemSelector 
                    placeHolder="部門選択"
                    :multiple="false"
                    :clearable="false"
                    :options="departmentsList"
                    v-model="department_id" 
                />
            </div>
            <div class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">編集許可</p>
                </div>
                <div class="selectSwitchArea" style="width: fit-content;">    
                    <input @change="setEditAllDefault" type="checkbox" id="edit_all" v-model="edit_all" :disabled="release_flag ? true : false">
                    <label for="edit_all" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer', {'disabled-toggle' : release_flag}]"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    <span v-if="release_flag" style="font-size: 11px;color:gray;position: absolute;white-space: nowrap;left: 0;bottom: -27px;">非公開設定ONのため設定できません</span>
                </div>  
            </div> 
            <div class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">非公開設定</p>
                </div>
                <div class="selectSwitchArea" style="width: fit-content;">    
                    <input type="checkbox" id="release_flag" v-model="release_flag" :disabled="edit_all ? true : false">
                    <label for="release_flag" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer', {'disabled-toggle' : edit_all}]"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    <span v-if="edit_all" style="font-size: 11px;color:gray;position: absolute;white-space: nowrap;left: 0;bottom: -27px;">編集許可ONのため設定できません</span>
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
                            :initialValue="once_date"
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
                    <div style="display: flex;gap: 10px;">                        
                        <div style="display: flex;">
                            <label for="all_day_on" class="check-container privacy-check" style="align-self: center;white-space: nowrap;">
                                <input @change="setAllDay" v-model="all_day" id="all_day_on" name="all_day_on" type="checkbox">
                                <span :class="['checkmark-mini', {'checkmark-mini-checked' : all_day}]"></span>
                                終日
                            </label>  
                        </div>
                        <ShortInput 
                            v-if="!all_day"
                            name="calendarNormalTimeStart" 
                            :rules="'required'"
                            :initialValue="set_start_time"
                            customClass="date"
                            ref="calendarNormalTimeStart"
                            type="time"
                            v-model="set_start_time"
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
                                :initialValue="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_from"
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
        </div>
    </div>
    
</template>
<script setup>
import LoaderButton from '../Global/LoaderButton.vue'
import FacilitySelector from '../Form/FacilitySelector.vue';
import moment from 'moment';
import { computed, onMounted, ref, inject } from 'vue';
import ShortInput from '../Form/ShortInput.vue';
import OptionSelector from '../Form/OptionSelector.vue';
import MemberSelector from '../Form/MemberSelector.vue';
import GroupSelector from '../Form/GroupSelector.vue';
import LongInput from '../Form/LongInput.vue';
import FileUploader from '../Form/FileUploader.vue';
import { useSharingDataStore } from '@/store/sharingData'
import ItemSelector from '../Form/ItemSelector.vue';
    const sharingData = useSharingDataStore()

    const props = defineProps([
        'editTarget', 
        'facilitiesList', 
        'preSelected', 
        'edit_all_record', 
        'preSelectedMembers', 
        'departmentsList',
        'preSelectedDepartment'
    ])
    const emit = defineEmits(['close'])

    const title = ref(props.editTarget && props.editTarget.title ? props.editTarget.title : "")
    const remarks = ref(props.editTarget && props.editTarget.remarks ? props.editTarget.remarks : sharingData.active ? sharingData.text : '')
    const calendar_users = ref(props.editTarget && props.editTarget.calendar_users ? props.editTarget.calendar_users : props.preSelectedMembers)
    const referrer = ref(props.editTarget && props.editTarget.referrer ? props.editTarget.referrer : "")
    const release_flag = ref(props.editTarget && props.editTarget.release_flag ? true : false)
    const edit_all = ref(props.editTarget && props.editTarget.edit_all ? true : false)
    const zoom_waiting_room = ref(props.editTarget && props.editTarget.zoom_waiting_room ? props.editTarget.zoom_waiting_room : 0)
    const repetition_type = ref(props.editTarget && props.editTarget.repetition_type && props.edit_all_record ? props.editTarget.repetition_type : 0)            
    const all_day = ref(props.editTarget &&  Math.abs(moment(props.editTarget.date_start).diff(moment(props.editTarget.date_end), 'hours')) >= 23 ? true : false)   
    
    const time_start = ref(props.editTarget && props.editTarget.date_start ? moment(props.editTarget.date_start).format('HH:mm') : props.preSelected  ? moment(props.preSelected ).format('HH:mm') : moment().add(1, 'hour').startOf('hour').format('HH:mm'))
    const time_end = ref(props.editTarget && props.editTarget.date_end ? moment(props.editTarget.date_end).format('HH:mm') : props.preSelected  ? moment(props.preSelected ).add(1, 'hour').format('HH:mm') : moment().add(2, 'hour').startOf('hour').format('HH:mm'))
    const once_date = ref(props.editTarget && props.editTarget.date_end ? moment(props.editTarget.date_start).format('YYYY-MM-DD') : props.preSelected  ? moment(props.preSelected ).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'))
    const repeat_span = ref({
        weekly: {
            selected_days: [false, true, false, false, false, false, false],
            repeat_date_from: props.editTarget && props.editTarget.repetition_type > 0 ? moment(props.editTarget.expiration_start).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'),
            repeat_date_to: props.editTarget && props.editTarget.repetition_type > 0 ? moment(props.editTarget.expiration_end).format('YYYY-MM-DD') : moment().add(1, 'week').format('YYYY-MM-DD'),
        },
        monthly: {
            selected_day: props.editTarget && props.editTarget.repeat_days !== null ? parseInt(props.editTarget.repeat_days) : moment().date(), 
            repeat_date_from: props.editTarget && props.editTarget.repetition_type > 0 ? moment(props.editTarget.expiration_start).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'),
            repeat_date_to: props.editTarget && props.editTarget.repetition_type > 0 ? moment(props.editTarget.expiration_end).format('YYYY-MM-DD') : moment().add(1, 'month').format('YYYY-MM-DD'),
        },
        yearly: {
            selected_month: moment().month() + 1,
            selected_day: props.editTarget && props.editTarget.repeat_days !== null ? parseInt(props.editTarget.repeat_days) : moment().date(),
            year_from: props.editTarget && props.editTarget.repetition_type > 0 ? moment(props.editTarget.expiration_start).year() : moment().year(),
            year_to: props.editTarget && props.editTarget.repetition_type > 0 ? moment(props.editTarget.expiration_start).year() : moment().add(1, 'year').year()
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
    const department_id = ref(props.preSelectedDepartment?.id ?? '')
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
            if(editAll && editAll == 1){
                edit_all.value = true
            }
        }
    })
    const set_start_time = computed({
        get(){
            return time_start.value
        },
        set(value){
            time_start.value = value
            time_end.value = moment(time_start.value, 'HH:mm').add(1, 'hour').startOf('hour').format('HH:mm')
        }
    })
    const setEditAllDefault = (event) => {
        const val = event.target.checked ? 1 : 0
        localStorage.setItem('editAllDefault', val)            
    }
    
    const setAllDay = () => {
        if(event.target.checked){
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
    const calendarUsers = ref(null)
    const calendarTitle = ref(null)
    const calendarNormalTimeStart = ref(null)
    const calendarNormalTimeEnd = ref(null)
    const calendarNormalDate = ref(null)
    const calendarRepeatSpanEnd = ref(null)
    const calendarRepeatSpanStart = ref(null)
    const monthlyDaySelector = ref(null)
    const yearSelectorSelectedDay = ref(null)
    const yearSelectorSelectedMonth = ref(null)
    const yearSelectorEnd = ref(null)
    const yearSelectorStart = ref(null)
    const { notify, info } = inject('dialog')
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
            const model = moment().format('YYYY-MM-DD')
            const a = `${model} ${time_end.value}:00`
            const b = `${model} ${time_start.value}:00`
            if(moment(a).isBefore(moment(b))){
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
            result = result * val.valid
        }
        if (!result) return
        const second_validate = await second_validation()
        if(!second_validate.valid){
            notify(second_validate.error)
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
            time_start:  all_day.value ? '00:00' : time_start.value,
            time_end: all_day.value ? '23:59' : time_end.value,
            once_date: once_date.value,
            repeat_span: repeat_span.value,
            facility: convertableFacilities,
            file_ids: uploadedFiles.value.length ? uploadedFiles.value.map(ob => ob.id) : [],
            department_id: department_id.value
        }
        
        axios.post('/calendar_add_record',params)
        .then(response =>  {
            info(props.editTarget ? '編集しました。' : '作成しました。')
            processing.value = false
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
            emit('close', true)     
        })
        .catch(function (error) {
            if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。 ' + error.message)      
            processing.value = false     
                        
        });
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
                const is31DaysMonth = moment(`${moment().year()}-${month}-31`, 'YYYY-MM-DD').isValid();
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
        const thisYear = moment().year()
        const limit = thisYear + 10
        const list = Array.from({ length: limit - thisYear + 1 }, (_, i) => thisYear + i);
        return list
    })
    const avialabeEndYear = computed(() => {
        const index = repetition_type.value == 2 ? 'monthly' : 'yearly'
        const thisYear = repeat_span.value[index].year_from
        const limit = thisYear + 10
        const list = Array.from({ length: limit - thisYear + 1 }, (_, i) => thisYear + i);
        return list
    })
</script>