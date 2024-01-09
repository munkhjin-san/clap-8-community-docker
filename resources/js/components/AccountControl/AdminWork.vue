<template>
    <div style="overflow:hidden; height: 100%; width: 100%;">
        <div style="height: calc(100% - 60px); overflow: hidden auto;">
            <div class="admin-work-header">
                <div class="admin-button" v-on:click="downloadCSV">CSVダウンロード</div>
                <div class="admin-month-wrapper">
                    <!-- <div @click="prevMonth" class="work-prevmonth">
                        <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>
                    </div> -->
                    <MonthPicker 
                        :selectedYear="selectedYear"
                        :selectedMonth="selectedMonth"
                        :right="$store.state.mobile ? 'auto' : '0'"
                        @setDate="setDate"
                    />
                    <!-- <div @click="nextMonth" class="work-nextmonth">
                        <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>
                    </div> -->
                </div>
            </div>
            <table class="admin-work-table">
                <thead>
                    <tr style="background:#363636;color:#fff;position:sticky; top:-1px;">
                        <td>社員名</td>
                        <td>勤怠確定</td>
                        <td>インシデント</td>
                        <td>職階</td>                        
                        <td>連続</td>
                        <td>勤怠予定入力</td>
                        <td>計画有給</td>
                        <td>1日年休</td>
                        <td>半日年休</td>
                        <td>1時間年休</td>
                        <td>2時間年休</td>
                        <td>3時間年休</td>
                        <td>4時間年休</td>
                        <td>5時間年休</td>
                        <td>6時間年休</td>
                        <td>7時間年休</td>
                        <td>慶弔休暇</td>
                        <td>転勤休暇</td>
                        <td>労働時間</td>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in filteredUsers" :key="index">
                        <td style="border:1px solid #666;">
                            <p v-if="item.attendance_records.length != 0" style="background-color:#87ceeb;">
                                {{ item.name }}
                            </p>
                            <p v-else>
                                {{ item.name }}
                            </p>
                        </td>
                        <td style="border:1px solid #666;">
                            <p v-if="item.attendance_records.length != 0" style="background-color:#87ceeb;">
                                {{ item.attendance_records[0].month_petition }}
                            </p>
                            <p v-else>
                                --
                            </p>
                        </td>
                        <td style="border:1px solid #666;">
                                <div v-if="item.time_card_records.length != 0">
                                    <p v-for="(records, index) in item.time_card_records" :key="index">
                                        {{ incidentDay(records) }}
                                    </p>    
                                    
                                </div>
                                <p v-else>--</p>
                        </td>
                        <td style="border:1px solid #666;">
                            <div v-for="(data, index) in kintone_data">
                                <p v-if="item.user_code && data.user_code && item.user_code == data.user_code">{{data.general_position}}</p>
                            </div>
                        </td>
                        <td style="border:1px solid #666;">
                            <div>
                                <img v-if="weather_average[item.id] != null" :src="'/images/icon_' + weather_average[item.id].current_value + '.svg'" alt="Weather Icon" width="30" height="17" />
                                <p v-else>--</p>
                            </div>
                            
                        </td>
                        
                        <td style="border:1px solid #666;text-align:center;">
                            
                                <p v-if="item.shift_records.length != 0">〇</p>
                                <p v-else>
                                    --
                                </p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 3">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 5">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 6">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 7">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 8">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 9">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 10">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 11">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 12">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 13">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 14">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td v-if="item.shift_records.length != 0" style="border:1px solid #666;">
                            <div v-for="(paid_holiday_item, index) in paid_holiday_record[item.id]" :key="index">
                                <p v-if="paid_holiday_item.type == 15">{{ dayFormat(paid_holiday_item.day) }}</p>
                            </div>
                        </td>
                        <td v-else style="border:1px solid #666;">
                            <p>--</p>
                        </td>
                        <td style="border:1px solid #666;">
                            <div>
                                <p>{{ conversionTime(month_work_time[item.id]) }}</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script>
    import moment from 'moment';
    import MonthPicker from '../Global/MonthPicker.vue'
    export default {
        props: ['searchUser'],
        components : {
            MonthPicker
        },
        data(){
            return {
                selectedYear: moment().year(),
                selectedMonth: moment().month(),
                attendance_record_items: [],
                custom_filed_data: [],
                shift_record: [],
                paid_holiday_record: [],
                month_work_time: [],
                thisMonth: moment().local().format('YYYY-MM'),
                users: [],
                weather_average: [],
                kintone_data: []
            }
        },
        mounted(){
            this.loard();
        },
        computed: {
            filteredUsers(){
                let result = this.users.filter(user1 => {
                    return this.searchUser.some(user2 => {
                        return user1.id === user2.id
                    })
                })
                return result
            },
            calendarDate(){
                return moment(this.thisMonth).local().format('YYYY-M')
            }
        },
        methods:{
            dayFormat(date){
                return moment(date).locale('ja').format('Do')
            },
            incidentDay(records){
                for(let custom of records.custom_field_data_records){
                    
                    if(custom){
                        return moment(custom.date).locale('ja').format('Do')
                    }
                    
                }
            },
            downloadCSV(){
                var csv = '\ufeff' + '社員コード,社員名,所定労働時間,就業形態,職階,勤怠月,給与支払日,確定フラグ,予定稼働日,通常出勤日数,休日出勤日数,欠勤日数,年休時間,1日年休,半日年休,1時間年休,2時間年休,3時間年休,4時間年休,5時間年休,6時間年休,7時間年休,慶弔休暇,特別休暇,休業,労働時間,欠勤時間,残業時間,深夜勤務,遠方手当,宿泊日当\n';
                this.attendance_record_items.forEach(el => {
                    const data = this.kintone_data.filter(ob => ob.user_code == el['user_code'])
                    const shokkai = data && data.length ? data[0]['general_position'] : ''
                    var line = el['user_code'] + ',' 
                    + el['name'] + ','
                    + el['prescribed_working_hours'] + ','
                    + el['work_type'] + ',' 
                    + shokkai + ','
                    + el['date_year_month'] + ','
                    + el['pay_day'] + ','
                    + el['month_petition'] + ',' 
                    + el['working_days_shift'] + ',' 
                    + el['normal_working_days'] + ','
                    + el['holiday_working_days'] + ',' 
                    + el['absence_days'] + ',' 
                    + el['paid_holiday_hours'] + ','
                    + el['petitionType8_count'] + ','
                    + el['half_day_holiday'] + ','
                    + el['petitionType1_count'] + ','
                    + el['petitionType2_count'] + ','
                    + el['petitionType3_count'] + ','
                    + el['petitionType4_count'] + ','
                    + el['petitionType5_count'] + ','
                    + el['petitionType6_count'] + ','
                    + el['petitionType7_count'] + ','
                    + el['condolence_holiday'] + ','
                    + el['special_holiday'] + ','
                    + el['closed_day'] + ','
                    + el['working_hours_no_over'] + ','
                    + el['absence_hour'] + ','
                    + el['over_time'] + ','
                    + el['night_work_time'] + ','
                    + el['stay_pay'] + ','
                    + el['move_pay'] + '\n';
                    csv += line;
                });
                let blob = new Blob([csv], { type: 'text/csv' });
                let link = document.createElement('a');
                const date = moment([this.selectedYear, this.selectedMonth]).format('YYYY-MM')
                link.href = window.URL.createObjectURL(blob);
                link.download = 'work_' + date + '.csv';
                link.click();

            },

            loard(){
                const date = moment([this.selectedYear, this.selectedMonth]).format('YYYY-MM')
                const params = {
                    month : date
                };
                axios.post('/get_admin_work', params ).then(
                response => {
                        this.attendance_record_items = response.data.attendance_record,
                        this.paid_holiday_record = response.data.paid_holiday_record,
                        this.month_work_time = response.data.month_work_time,
                        this.users = response.data.users,
                        this.weather_average = response.data.weather_average,
                        this.kintone_data = response.data.kintone_data
                    }
                ).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))

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
            },
            conversionTime(value){
                if(value == '--' || value == null){
                    var value = '--';
                    return value;
                }else{
                    var hour = Math.floor(value / 60);
                    var min = value % 60;
                    if(min == 0){
                        return hour + '時間';
                    }else{
                        return hour + '時間' + min + '分';
                    }
                }
            },
            prevMonth(){
                this.selectedMonth = this.selectedMonth - 1
                if(this.selectedMonth < 0){
                    this.selectedYear = this.selectedYear - 1
                    this.selectedMonth = 11
                }
                this.loard()
            },
            nextMonth(){
                this.selectedMonth = this.selectedMonth + 1;
                if(this.selectedMonth > 11){
                    this.selectedYear = this.selectedYear + 1
                    this.selectedMonth = 0
                }
                this.loard()
            },
            setDate(date){
                this.selectedYear = date.year
                this.selectedMonth = date.month - 1
                this.loard()
            },
        },
    }
</script>
<style lang="scss" scoped>
    .admin-work-header{
        position: absolute;
        top: 0;
        right: 15px;
        display: flex;
        gap:20px;
        height: 60px;
    }
    .admin-month-wrapper{
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .admin-work-table{
        background-color: var(--background-color);
        width: 100%;
    }
    .admin-work-table td{
        padding: 10px;
        font-size: 14px;
    }
    thead td{
        border-right: 1px solid rgb(102, 102, 102);
        border-top: 1px solid rgb(102, 102, 102);
    }
    thead td:first-child{
        border-left: 1px solid rgb(102, 102, 102);
    }
    .work-prevmonth{
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        cursor: pointer;
        fill: var(--primary-color);
    }
    .work-nextmonth{
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        cursor: pointer;
        fill: var(--primary-color);
    }
    @media screen and (max-width: 959px) {
        .admin-work-header{
            position: static;
            margin: 0 15px 10px;
            height: auto;
        }
    }
</style>