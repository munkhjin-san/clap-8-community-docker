<template>
    <div class="work-modal">
        <div class="work-modal-inner">
            <div style="display:flex; justify-content: space-between; margin-bottom: 20px; align-items: center;">
                <h1 style="font-size: 17px;">{{formatedDay}}の日報を作成する</h1>
                <button v-if="!createReport" style="width: auto; height: 30px; line-height: 30px; background: rgb(0, 0, 0); color: rgb(255, 255, 255); cursor: pointer; font-size: 12px; padding: 0px 8px;">削除する</button>
                <div class="modalCloseButton cursor-pointer" @click="$emit('closeModal')" style="position:fixed;right:40px !important;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="#fff" style="margin: auto;">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
            <div class="report-wrapper">
                <div class="report-field">
                    <p class="report-header">本日の就業時間</p>
                    <div class="report-input">
                        <ul class="report-time">
                            <li>
                                {{editStartTime}}
                            </li>
                            <li class="between-line">
                                ～
                            </li>
                            <li>
                                {{editEndTime}}
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="report-field">
                    <p class="report-header">就業時間の変更</p>
                    <div class="report-input">
                        <div class="report-input-wrapper">
                            <input type="radio" name="reportTimeEdit" v-model="reportTimeEdit" value="0" v-on:change="showTime">
                            <label for="reportTimeEdit">変更なし</label>
                        </div>
                        <div class="report-input-wrapper">
                            <input type="radio" name="reportTimeEdit" v-model="reportTimeEdit" value="1" v-on:change="showTime">
                            <label for="reportTimeEdit">変更あり</label>
                        </div>
                    </div>
                </div>
                <div class="report-field" v-if="editTime">
                    <p class="report-header">就業時間の入力</p>
                    <div class="report-input-time">
                        <div class="report-input-wrapper">
                            <input type="time" v-model="editStartTime">
                        </div>
                        <div class="between-line">～</div>
                        <div class="report-input-wrapper">
                            <input type="time" v-model="editEndTime">
                        </div>
                    </div>
                </div>
                <div class="report-field">
                    <p class="report-header">本日の休憩時間</p>
                    <div class="report-input">
                        <ul>
                            <li>{{breakTimeSelect}}分</li>
                        </ul>
                    </div>
                </div>
                <div class="report-field">
                    <p class="report-header">休憩時間の変更</p>
                    <div class="report-input">
                        <div class="report-input-wrapper"><input type="radio" name="breakTimeEdit" v-model="breakTimeEdit" value="0" v-on:change="showBreakTime">
                            <label for="breakTimeEdit">変更なし</label>
                        </div>
                        <div class="report-input-wrapper"><input type="radio" name="breakTimeEdit" v-model="breakTimeEdit" value="1" v-on:change="showBreakTime">
                            <label for="breakTimeEdit">変更あり</label>
                        </div>
                    </div>
                </div>
                <div class="report-field" v-if="breakTime">
                    <p class="report-header">休憩時間の選択</p>
                    <div class="report-input">
                        <select class="report-breakTime dropDownSelector" v-model="breakTimeSelect" name="breakTimeSelect">
                            <option :key="index" v-for="(item , index) in breakTimeOptions" :value="item.value">{{ item.label }}</option>
                        </select>
                    </div>
                </div>
                <WorkCustomField
                    :customFieldData="customFieldData" 
                    :info="info"
                    @updateData="updateData" 
                />
                <div class="report-button-wrapper">
                    <div class="r-button cursor-pointer" style="margin-top:30px;" @click="saveTimeCard(0)">
                        <span v-if="!loading">保存する</span>
                        <div v-else id="loaderMini">
                            <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                        </div> 
                    </div>
                    <div class="r-button cursor-pointer" style="margin-top:30px;" @click="saveTimeCard(1)">
                        <span v-if="!loading">申請する</span>
                        <div v-else id="loaderMini">
                            <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import WorkCustomField from './WorkCustomField.vue'
    export default{
        props: [
            'choosenDate', 
            'todayStartTime', 
            'todayEndTime', 
            'todayBreakTime', 
            'customFieldData', 
            'info',
            'createReport',
            'chosenUserId',
            'shiftStartTime',
            'shiftEndTime'
        ],
        data(){
            return{
                reportTimeEdit: 0,
                breakTimeEdit: 0,
                loading: false,
                editTime: false,
                breakTime: false,
                editStartTime: this.todayStartTime,
                editEndTime: this.todayEndTime,
                breakTimeOptions: [{label : 'なし' , value : 0 },
                        {label : '30分' , value : 30 },
                        {label : '45分' , value : 45 },
                        {label : '60分' , value : 60 },
                        {label : '90分' , value : 90 }],
                breakTimeSelect: this.todayBreakTime,
                reportComment: '',
                reportIncident: '',
                reportAchievement: '',
                reportAllowance: [],
            }
        },
        mounted(){
            if(this.todayStartTime && this.todayEndTime && this.todayBreakTime == 0){
                const startTimeParts = this.todayStartTime.split(":");
                const endTimeParts = this.todayEndTime.split(":");
                const startHour = parseInt(startTimeParts[0]);
                const startMinute = parseInt(startTimeParts[1]);
                const endHour = parseInt(endTimeParts[0]);
                const endMinute = parseInt(endTimeParts[1]);

                const workTimeMinutes = (endHour * 60 + endMinute) - (startHour * 60 + startMinute);

                if (workTimeMinutes >= 360) {
                    this.breakTimeSelect = 60;
                } else if (workTimeMinutes >= 180 && workTimeMinutes < 360) {
                    this.breakTimeSelect = 30;
                } else if (workTimeMinutes < 180) {
                    this.breakTimeSelect = 0;
                }
            }
            console.log(this.todayBreakTime)
        },
        computed: {
            formatedDay(){
                const date = new Date(this.choosenDate)
                return `${date.getMonth() + 1}月${date.getDate()}日`
            }
        },
        methods: {
            updateData(data){
                if(data.field_type_id == 39){
                    this.reportComment = data
                }else if(data.field_type_id == 40){
                    this.reportIncident = data
                }else if(data.field_type_id == 41){
                    this.reportAchievement = data
                }else{
                    this.reportAllowance = data
                }
            },
            showTime(){
                if(this.reportTimeEdit == 1){
                    this.editTime = true
                }else{
                    this.editTime = false
                }
            },
            showBreakTime(){
                if(this.breakTimeEdit == 1){
                    this.breakTime = true
                }else{
                    this.breakTime = false
                }
            },
            saveTimeCard(status_flag){
                if(!this.reportIncident){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: 'インシデント・アクシデントは必須項目です。必ず選択してください。',
                        closeButton: true, 
                        autoClose: true,
                    }) 
                    return
                }
                if(!this.reportAchievement){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: '本日の目標達成率は必須項目です。必ず選択してください。',
                        closeButton: true, 
                        autoClose: true,
                    }) 
                    return
                }
                if(!this.reportComment) {
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: '仕事満足または不満の要因は必須です。必ず入力してください。',
                        closeButton: true, 
                        autoClose: true,
                    }) 
                    return
                }
                
                if(this.loading) return

                this.loading = true
                const params = {
                    comment: this.reportComment,
                    incident: this.reportIncident,
                    achievement: this.reportAchievement,
                    allowance: this.reportAllowance,
                    breakTime: this.breakTimeSelect,
                    start_time: this.editStartTime,
                    end_time: this.editEndTime,
                    day: this.choosenDate,
                    status_flag: status_flag,
                    userId: this.chosenUserId,
                    shift_start_time: this.shiftStartTime,
                    shift_end_time: this.shiftEndTime
                }
                if(status_flag == 1){
                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: '日報を申請します。申請後は修正できません。よろしいですか。',
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                        channel: uniqueChannell
                    })            
                    emitter.on(uniqueChannell, (data) => { 
                        if(data.answer === this.$t('confirmToAction')){
                            axios.post('/save_time_card', params).then(
                                response => {
                                    this.$emit('reload')
                                    this.loading = false
                                }
                            )
                        } 
                    });
                    
                }else{
                    axios.post('/save_time_card', params).then(
                        response => {
                            this.$emit('reload')
                            this.loading = false
                        }
                    )
                }
                
            },
        },
        components: {
            WorkCustomField
        }
    }
</script>