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
                <FormShortText
                    :initialValue="title"
                    ref="calendarTitle"
                    placeHolder="タイトルを入力（必須）"
                    uId="calendarTitle"
                    name="calendarTitle"
                    rules="required|max:48"
                    label="タイトル"
                    @setValue="val => title = val"
                />
            </div>
            <div class="si-box">
                <UserSelector 
                    :hasBoardSelect="!editTarget"
                    :key="userSelectorKey"
                    :selfInclude="true" 
                    :initialSelected="calendar_users"
                    placeHolder="メンバー選択"
                    rules="required"
                    @setUser="val => calendar_users = val"
                    uId="calendarUsers"
                    name="calendarUsers"
                    ref="calendarUsers"
                    path="calendar_more_users"
                />
            </div>
            <div v-if="!release_flag" class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">編集許可</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input @change="setEditAllDefault" type="checkbox" id="edit_all" v-model="edit_all">
                    <label for="edit_all" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                    <div class="switch-toggle"></div>
                </div>  
            </div> 
            <div v-if="!edit_all" class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">非公開設定</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input type="checkbox" id="release_flag" v-model="release_flag">
                    <label for="release_flag" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                    <div class="switch-toggle"></div>
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
                        <DatePicker
                            :initialValue="once_date"
                            ref="calendarNormalDate"
                            uId="calendarNormalDate"
                            name="calendarNormalDate"
                            :rules="repetition_type == 0 ? 'required' : ''"
                            @setValue="val => once_date = val"
                        />
                    </div>
                    <div v-if="repetition_type == 3">
                        <div style="display: flex;gap: 10px;margin-right: 15px;">
                            <FormOptionSelector
                                :initialValue="repeat_span.yearly.selected_month"
                                :options="12"
                                unit="月"
                                ref="yearSelectorSelectedMonth"
                                uId="yearSelectorSelectedMonth"
                                name="yearSelectorSelectedMonth"
                                rules="required"
                                @setValue="val => repeat_span.yearly.selected_month = parseInt(val)"
                            />
                            <FormOptionSelector
                                :initialValue="repeat_span.yearly.selected_day"
                                :options="avialableDay"
                                unit="日"
                                ref="yearSelectorSelectedDay"
                                uId="yearSelectorSelectedDay"
                                name="yearSelectorSelectedDay"
                                rules="required"
                                @setValue="val => repeat_span.yearly.selected_day = parseInt(val)"
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
                        <FormOptionSelector
                            :initialValue="repeat_span.monthly.selected_day"
                            :options="31"
                            unit="日"
                            ref="monthlyDaySelector"
                            uId="monthlyDaySelector"
                            name="monthlyDaySelector"
                            rules="required"
                            @setValue="val => repeat_span.monthly.selected_day = parseInt(val)"
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
                        <TimePicker
                            v-if="!all_day"
                            :initialValue="time_start"
                            ref="calendarNormalTimeStart"
                            uId="calendarNormalTimeStart"
                            name="calendarNormalTimeStart"
                            rules="required"
                            @setValue="val => time_start = val"
                        />
                        <TimePicker
                            v-if="!all_day"
                            :initialValue="time_end"
                            ref="calendarNormalTimeEnd"
                            uId="calendarNormalTimeEnd"
                            name="calendarNormalTimeEnd"
                            rules="required"
                            @setValue="val => time_end = val"
                        />
                    </div>
                </div>
                <div class="si-box" style="margin-top: 20px;">
                    <div v-if="repetition_type == 1 || repetition_type == 2">
                        <div>
                            <p :class="['form-title-small']">有効期限設定</p>
                        </div>
                        <div style="display: flex;gap: 10px;margin-top: 20px;">
                            <DatePicker
                                :initialValue="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_from"
                                ref="calendarRepeatSpanStart"
                                uId="calendarRepeatSpanStart"
                                name="calendarRepeatSpanStart"
                                :rules="'required'"
                                @setValue="val => repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_from = val"
                            />
                            <DatePicker
                                :initialValue="repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_to"
                                ref="calendarRepeatSpanEnd"
                                uId="calendarRepeatSpanEnd"
                                name="calendarRepeatSpanEnd"
                                :rules="'required'"
                                @setValue="val => repeat_span[repetition_type == 2 ? 'monthly' : 'weekly'].repeat_date_to = val"
                            />
                        </div>
                    </div>
                    <div v-if="repetition_type == 3">
                        <div>
                            <p :class="['form-title-small']">有効期限設定</p>
                        </div>
                        <div style="display: flex;gap: 10px;margin-top: 20px;">
                            <FormOptionSelector
                                :initialValue="repeat_span.yearly.year_from"
                                :options="avialabeStartYear"
                                unit="年"
                                ref="yearSelectorStart"
                                uId="yearSelectorStart"
                                name="yearSelectorStart"
                                rules="required"
                                @setValue="val => repeat_span.yearly.year_from = parseInt(val)"
                            />
                            <FormOptionSelector
                                :initialValue="repeat_span.yearly.year_to"
                                :options="avialabeEndYear"
                                unit="年"
                                ref="yearSelectorEnd"
                                uId="yearSelectorEnd"
                                name="yearSelectorEnd"
                                rules="required"
                                @setValue="val => repeat_span.yearly.year_to = parseInt(val)"
                            />
                        </div>
                    </div>

                </div>
                <div class="si-box">
                    <ItemSelector 
                        :initialSelected="facility.qualified_institution"
                        :repeatSpan="repeat_span"
                        :repetitionFlag="repetition_type"
                        :time_end="time_end"
                        :time_start="time_start"
                        :once_date="once_date"
                        :facility="facilitiesList"
                        target="qualified_institution"
                        placeHolder="施設選択"
                        rules=""
                        @setItems="val => facility.qualified_institution = val ? val.value : null"
                        uId="calendarFacility"
                        name="calendarFacility"
                        ref="calendarFacility"
                    />
                </div>
                <div class="si-box">
                    <ItemSelector 
                        :initialSelected="facility.zoom_value"
                        :repeatSpan="repeat_span"
                        :repetitionFlag="repetition_type"
                        :time_end="time_end"
                        :time_start="time_start"
                        :once_date="once_date"
                        :facility="facilitiesList"
                        target="zoom_value"
                        placeHolder="WEB会議選択"
                        rules=""
                        @setItems="val => facility.zoom_value = val ? val.value : null"
                        uId="calendarZoom"
                        name="calendarZoom"
                        ref="calendarZoom"
                    />
                </div>
                <div v-if="facility.zoom_value" class="si-box" style="position:relative;">
                    <div>
                        <p :class="['form-title-small', {'form-title-active' : release_flag}]">WEB会議待機室</p>
                    </div>
                    <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                        <input type="checkbox" id="zoom_waiting_room" v-model="zoom_waiting_room">
                        <label for="zoom_waiting_room" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                        <div class="switch-toggle"></div>
                    </div>  
                </div>  
                <div class="si-box">
                    <ItemSelector 
                        :initialSelected="facility.qualified_car"
                        :repeatSpan="repeat_span"
                        :repetitionFlag="repetition_type"
                        :time_end="time_end"
                        :time_start="time_start"
                        :once_date="once_date"
                        :facility="facilitiesList"
                        target="qualified_car"
                        placeHolder="車両選択"
                        rules=""
                        @setItems="val => facility.qualified_car = val ?  val.value : null"
                        uId="calendarCars"
                        name="calendarCars"
                        ref="calendarCars"
                    />
                </div>

                <div class="si-box">                   
                    <FormLongText
                        :initialValue="remarks"  
                        ref="calendarRemark"
                        :placeHolder="`メモ`"
                        uId="calendarRemark"
                        name="calendarRemark"
                        rules="max:2000"
                        label="メモ"
                        @setValue="val => remarks = val"
                    />                    
                </div>
                <div class="si-box">
                    <FormShortText
                        :initialValue="referrer"  
                        placeHolder="参照元URLを入力"
                        uId="calendarUrl"
                        name="calendarUrl"
                        rules=""
                        label="タイトル"
                        @setValue="(val) => referrer = val"
                    />
                </div>  

                <div class="si-box">
                    <FormFileUploader
                        :initialValue="uploadedFiles"
                        @updated="val => uploadedFiles = val"
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
import FormShortText from '../Global/FormShortText.vue';
import FormLongText from '../Global/FormLongText.vue'
import UserSelector from '../Global/UserSelector.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import DatePicker from '../Global/DatePicker.vue'
import FormFileUploader from '../Global/FormFileUploader.vue'
import TimePicker from '../Global/TimePicker.vue'
import ItemSelector from '../Global/ItemSelector.vue';
import FormOptionSelector from '../Global/FormOptionSelector.vue';
import moment from 'moment';
import { computed, onMounted, ref } from 'vue';
import { useStore } from 'vuex';
    const store = useStore()

    const props = defineProps(['editTarget', 'facilitiesList', 'preSelected', 'edit_all_record', 'preSelectedMembers'])
    const emit = defineEmits(['close'])

    const title = ref(props.editTarget && props.editTarget.title ? props.editTarget.title : "")
    const remarks = ref(props.editTarget && props.editTarget.remarks ? props.editTarget.remarks : "")
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
        qualified_institution:  ref( props.editTarget && props.editTarget.qualified_institution !== null ? props.editTarget.qualified_institution : null),
        qualified_car: ref(props.editTarget && props.editTarget.qualified_car !== null ? props.editTarget.qualified_car : null),
        zoom_value: ref(props.editTarget && props.editTarget.zoom_value !== null ? props.editTarget.zoom_value : null)
    })
    const uploadedFiles = ref(props.editTarget && props.editTarget.files ? props.editTarget.files : [])
    const processing = ref(false)
    const userSelectorKey = ref(0)
 
    onMounted(() => {
        if(props.editTarget && props.editTarget.repetition_type == 1 && props.editTarget.repeat_week){
            const repeats = props.editTarget.repeat_week.split(',').map(Number);
            console.log(repeats)
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
        store.commit('setSharingData', null)
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
    
    const validation = async () => {               
        
        try {       
            const checkRef = []            
            let result = true
            checkRef.push(calendarUsers, calendarTitle)

            
            if(!all_day.value){
                checkRef.push(calendarNormalTimeStart, calendarNormalTimeEnd)
            }   
            if(repetition_type.value == 0){
                checkRef.push(calendarNormalDate)
            }else if(repetition_type.value == 1){
                checkRef.push(calendarRepeatSpanEnd, calendarRepeatSpanStart)
            }
            else if(repetition_type.value == 2){
                checkRef.push(monthlyDaySelector, calendarRepeatSpanEnd, calendarRepeatSpanStart)
            }
            else if(repetition_type.value == 3){
                checkRef.push(yearSelectorSelectedDay,yearSelectorSelectedMonth, yearSelectorEnd, yearSelectorStart)
            }
            
            for(const check of checkRef){
                const exec = await check.value.$refs[check.value['uId']].validate()
                result = result * exec.valid
            }        
            if(repetition_type.value == 1){
                result = result * selectedDaysValid.value
            }             
            
            return result
        } catch (error) {
            console.error('Error fetching data:', error);
            throw error; // Re-throw the error to handle it further if needed
        }               
        
    }
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
    const checkConfirm = () => {
        createSend()        
    }
    const createSend = async () => {
        processing.value = true
        const valid = await validation()
        if(!valid){
            processing.value = false
            return
        }
        const second_validate = await second_validation()
        console.log(second_validate)
        if(!second_validate.valid){
            errorToast(second_validate.error)
            processing.value = false
            return
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
            facility: facility.value,
            file_ids: uploadedFiles.value.length ? uploadedFiles.value.map(ob => ob.id) : []
        }
        axios.post('/calendar_add_record',params)
        .then(response =>  {
            const data = {
                text: props.editTarget ? '編集しました。' : '作成しました。',
                channel: Math.random().toString(36).substring(5),
                icon: 0,
                view: true
            }
            emitter.emit('setInfo', data)
            processing.value = false
            store.commit('setSharingData', null)
            emit('close', true)     
        })
        .catch(function (error) {
            if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。 ' + error.message)      
            processing.value = false     
                        
        });
    }
    const errorToast = (message) => {
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: message,
            closeButton: false, 
            autoClose: false,
            answers: ['OK']

        })  
        processing.value = false        
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
                console.log(is31DaysMonth)
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