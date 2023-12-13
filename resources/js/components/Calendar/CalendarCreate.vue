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
            <!-- <div class="si-box" v-if="!editTarget">
                <BoardSelector @setMembers="setMembers" :update="boardSelectorKey"/>
            </div> -->
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
<script>
import FormShortText from '../Global/FormShortText.vue';
import FormLongText from '../Global/FormLongText.vue'
import UserSelector from '../Global/UserSelector.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import DatePicker from '../Global/DatePicker.vue'
import FormFileUploader from '../Global/FormFileUploader.vue'
import TimePicker from '../Global/TimePicker.vue'
import ItemSelector from '../Global/ItemSelector.vue';
import FormOptionSelector from '../Global/FormOptionSelector.vue';
import BoardSelector from '../Global/BoardSelector.vue'
import moment from 'moment';
export default{
    props:['editTarget', 'facilitiesList', 'preSelected', 'edit_all_record', 'preSelectedMembers'],
    emits: ['close'],
    data(){
        return{
            title: this.editTarget && this.editTarget.title ? this.editTarget.title : "",
            remarks: this.editTarget && this.editTarget.remarks ? this.editTarget.remarks : "",
            calendar_users: this.editTarget && this.editTarget.calendar_users ? this.editTarget.calendar_users : this.preSelectedMembers,
            referrer: this.editTarget && this.editTarget.referrer ? this.editTarget.referrer : "",
            release_flag: this.editTarget && this.editTarget.release_flag ? true : false,
            edit_all: this.editTarget && this.editTarget.edit_all ? true : false,
            zoom_waiting_room: this.editTarget && this.editTarget.zoom_waiting_room ? this.editTarget.zoom_waiting_room : 0,
            repetition_type: this.editTarget && this.editTarget.repetition_type && this.edit_all_record ? this.editTarget.repetition_type : 0,            
            all_day: this.editTarget && moment(this.editTarget.date_start).format('HH:mm') == '00:00' && moment(this.editTarget.date_end).format('HH:mm') == '23:59' ? 1 : 0,
            
            
            time_start: this.editTarget && this.editTarget.date_start ? moment(this.editTarget.date_start).format('HH:mm') : this.preSelected ? moment(this.preSelected).format('HH:mm') : moment().add(1, 'hour').startOf('hour').format('HH:mm'),
            time_end: this.editTarget && this.editTarget.date_end ? moment(this.editTarget.date_end).format('HH:mm') : this.preSelected ? moment(this.preSelected).add(1, 'hour').format('HH:mm') : moment().add(2, 'hour').startOf('hour').format('HH:mm'),
            once_date: this.editTarget && this.editTarget.date_end ? moment(this.editTarget.date_start).format('YYYY-MM-DD') : this.preSelected ? moment(this.preSelected).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'),
            referrer: this.editTarget && this.editTarget.referrer ? this.editTarget.referrer : "",
            repeat_span: {
                weekly: {
                    selected_days: [false, true, false, false, false, false, false],
                    repeat_date_from: this.editTarget && this.editTarget.repetition_type > 0 ? moment(this.editTarget.expiration_start).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'),
                    repeat_date_to: this.editTarget && this.editTarget.repetition_type > 0 ? moment(this.editTarget.expiration_end).format('YYYY-MM-DD') : moment().add(1, 'week').format('YYYY-MM-DD'),
                },
                monthly: {
                    selected_day: this.editTarget && this.editTarget.repeat_days !== null ? parseInt(this.editTarget.repeat_days) : moment().date(), 
                    repeat_date_from: this.editTarget && this.editTarget.repetition_type > 0 ? moment(this.editTarget.expiration_start).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'),
                    repeat_date_to: this.editTarget && this.editTarget.repetition_type > 0 ? moment(this.editTarget.expiration_end).format('YYYY-MM-DD') : moment().add(1, 'month').format('YYYY-MM-DD'),
                },
                yearly: {
                    selected_month: moment().month() + 1,
                    selected_day: this.editTarget && this.editTarget.repeat_days !== null ? parseInt(this.editTarget.repeat_days) : moment().date(),
                    year_from: this.editTarget && this.editTarget.repetition_type > 0 ? moment(this.editTarget.expiration_start).year() : moment().year(),
                    year_to: this.editTarget && this.editTarget.repetition_type > 0 ? moment(this.editTarget.expiration_start).year() : moment().add(1, 'year').year()
                }
            },
            facility: {
                qualified_institution: this.editTarget && this.editTarget.qualified_institution !== null ? this.editTarget.qualified_institution : null,
                qualified_car: this.editTarget && this.editTarget.qualified_car !== null ? this.editTarget.qualified_car : null,
                zoom_value: this.editTarget && this.editTarget.zoom_value !== null ? this.editTarget.zoom_value : null,
            },
            uploadedFiles: this.editTarget && this.editTarget.files ? this.editTarget.files : [],
            processing: false,
            userSelectorKey: 0,
            boardSelectorKey: 0
            
        }
    },    
    mounted(){
        if(this.editTarget && this.editTarget.repetition_type == 1 && this.editTarget.repeat_week){
            const repeats = this.editTarget.repeat_week.split(',').map(Number);
            console.log(repeats)
            let pre = [false, false, false, false, false, false, false]
            repeats.forEach(val => {
                // console.log('val',val)
                pre[val] = true
            });
            this.repeat_span.weekly.selected_days = pre
            // for(const i in this.repeat_span.weekly.selected_days){
            //     console.log('i', i)
            //     if(repeats.includes(i)){
            //         console.log('hihi', i)
            //         this.repeat_span.weekly.selected_days[i] = true
            //     }
            // }
        }
        if(!this.editTarget){
            const editAll = localStorage.getItem('editAllDefault')
            if(editAll && editAll == 1){
                this.edit_all = true
            }
        }
    },
    components:{
        FormShortText, 
        FormLongText, 
        UserSelector, 
        LoaderButton, 
        DatePicker, 
        FormFileUploader,
        TimePicker,
        ItemSelector,
        FormOptionSelector,
        BoardSelector
    },
    methods:{
        setEditAllDefault(event){
            const val = event.target.checked ? 1 : 0
            localStorage.setItem('editAllDefault', val)            
        },
        setMembers(values){
            console.log(values)
            this.calendar_users = values
            this.userSelectorKey ++
        },
        setEditAll(){
            this.repetition_type = event.target.checked == false ? 0 : this.editTarget.repetition_type           
        },
        setAllDay(){
            if(event.target.checked){
                this.time_start = '00:00'
                this.time_end = '23:59'
            }
        },
        closeModal(val){
            this.$store.commit('setSharingData', null)
            this.$emit('close', val)
        },
        async validation(){               
                
            try {                    
                let result = true
                let checkRef = ['calendarUsers', 'calendarTitle']
                if(!this.all_day){
                    checkRef.push('calendarNormalTimeStart', 'calendarNormalTimeEnd')
                }   
                if(this.repetition_type == 0){
                    checkRef.push('calendarNormalDate')
                }else if(this.repetition_type == 1){
                    checkRef.push('calendarRepeatSpanEnd', 'calendarRepeatSpanStart')
                }
                else if(this.repetition_type == 2){
                    checkRef.push('monthlyDaySelector', 'calendarRepeatSpanEnd', 'calendarRepeatSpanStart')
                }
                else if(this.repetition_type == 3){
                    checkRef.push('yearSelectorSelectedDay','yearSelectorSelectedMonth', 'yearSelectorEnd', 'yearSelectorStart')
                }
                for(const check of checkRef){
                    const exec = await this.$refs[check].$refs[check].validate()
                    result = result * exec.valid
                }        
                if(this.repetition_type == 1){
                    result = result * this.selectedDaysValid
                }             
                
                return result
            } catch (error) {
                console.error('Error fetching data:', error);
                throw error; // Re-throw the error to handle it further if needed
            }               
            
        },
        async second_validation(){
            if(this.time_start == this.time_end){
                return {
                    valid: false,
                    error: '開始時間と終了時間は同じにすることが出来ません。'
                }
            }else {
                const model = moment().format('YYYY-MM-DD')
                const a = `${model} ${this.time_end}:00`
                const b = `${model} ${this.time_start}:00`
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
        },
        checkConfirm(){
            // if(this.editTarget && this.editTarget.repetition_type > 0){
            //     const uniqueChannell = Math.random().toString(36).substring(5);
            //     const content = this.edit_all_record ? '繰り返しスケジュールのすべてのレコードを編集します。<br>よろしいですか。' : 'このスケジュールのみを編集します。<br>よろしいですか。'
            //     emitter.emit('setToast', {
            //         active: true,   
            //         type: 'info', 
            //         content: content,
            //         closeButton: false, 
            //         autoClose: false,
            //         answers: ['OK', 'キャンセル'],
            //         channel: uniqueChannell

            //     })
            //     let ans = false
            //     emitter.on(uniqueChannell, (data) => {                                        
            //         data.answer == 'OK' ? this.createSend()  : false                                  
            //     });  
            // }else{
                this.createSend()
            // }
        },
        async createSend(){
            this.processing = true
            const valid = await this.validation()
            if(!valid){
                this.processing = false
                return
            }
            const second_validate = await this.second_validation()
            console.log(second_validate)
            if(!second_validate.valid){
                this.errorToast(second_validate.error)
                this.processing = false
                return
            }
            
            const params = {
                editId: this.editTarget ? this.editTarget.id : null,
                edit_repeat: this.edit_all_record,
                title: this.title,
                remarks: this.remarks,
                users: this.calendar_users.map(ob => ob.id),
                referrer: this.referrer,
                release_flag: this.release_flag,
                edit_all: !this.release_flag ? this.edit_all : false,
                repetition_type: this.repetition_type,
                zoom_waiting_room: this.zoom_waiting_room,
                time_start:  this.all_day ? '00:00' : this.time_start,
                time_end: this.all_day ? '23:59' : this.time_end,
                once_date: this.once_date,
                repeat_span: this.repeat_span,
                facility: this.facility,
                file_ids: this.uploadedFiles.length ? this.uploadedFiles.map(ob => ob.id) : []
            }
            axios.post('/calendar_add_record',params)
            .then(response =>  {
                // this.closeModal(true)
                const data = {
                    text: this.editTarget ? '編集しました。' : '作成しました。',
                    channel: Math.random().toString(36).substring(5),
                    icon: 0,
                    view: true
                }
                emitter.emit('setInfo', data)
                this.processing = false
                this.$store.commit('setSharingData', null)
                this.$emit('close', true)     
            })
            .catch(function (error) {
                if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) this.errorToast('エラーが発生しました。')
                else this.errorToast('エラーが発生しました。 ' + error.message)      
                this.processing = false     
                          
            }.bind(this));
        },
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })  
            this.processing = false
            
        }, 
    },
    computed:{
        selectedDaysValid(){
            if(this.repetition_type == 1){
                const selected = this.repeat_span.weekly.selected_days.filter(ob => ob == true)
                return selected.length
            }
            return true
        },
        avialableDay(){
            this.repeat_span
            if(this.repeat_span.yearly.selected_month){
                const month = this.repeat_span.yearly.selected_month
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
            
        },
        week(){
            return [
                { num: 1, name: '月'},
                { num: 2, name: '火'},
                { num: 3, name: '水'},
                { num: 4, name: '木'},
                { num: 5, name: '金'},
                { num: 6, name: '土'},
                { num: 0, name: '日'}
            ]
        },
        avialabeStartYear(){
            const thisYear = moment().year()
            const limit = thisYear + 10
            const list = Array.from({ length: limit - thisYear + 1 }, (_, i) => thisYear + i);
            return list
        },
        avialabeEndYear(){
            const index = this.repetition_type == 2 ? 'monthly' : 'yearly'
            const thisYear = this.repeat_span[index].year_from
            const limit = thisYear + 10
            const list = Array.from({ length: limit - thisYear + 1 }, (_, i) => thisYear + i);
            return list
        }
    }
}
</script>