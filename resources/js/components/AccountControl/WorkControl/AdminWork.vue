<template>
    <div style="overflow:hidden; height: 100%; width: 100%;position: relative">
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <div class="admin-sub-c-bar">
            <UserSearchBar v-model="keywords"/>   
            <div class="admin-work-header">
                <div class="admin-button" v-on:click="downloadCSV">CSV出力</div>
                <div class="admin-month-wrapper">
                    <MonthPicker 
                        :selectedYear="selectedYear"
                        :selectedMonth="selectedMonth"
                        :right="responsive.mobile ? 'auto' : '0'"
                        @setDate="setDate"
                    />
                </div>
            </div>
        </div>        
        <div style="height:calc(100% - 70px);overflow: hidden auto;">
            
            <table class="admin-work-table">
                <thead style="background:#363636;color:#fff;position:sticky; top:0px;">
                    <tr style="border:1px solid rgb(102, 102, 102);">
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;" rowspan="2">社員名</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "rowspan="2">勤怠確定</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "rowspan="2">インシデント</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "rowspan="2">職階</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "rowspan="2">天気（3日連続）</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "rowspan="2">勤怠予定入力</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "rowspan="2">計画有給</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "colspan="9">年休</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "colspan="2">休暇</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle; "rowspan="2">労働時間</td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;border-left: none;">1日</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">半日</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">1時間</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">2時間</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">3時間</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">4時間</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">5時間</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">6時間</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">7時間</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">慶弔</td>
                        <td style="font-size: 13px;text-align: center;vertical-align: middle;">転勤</td>
                    </tr>
                </thead>
                <tbody>
                    <tr :style="{backgroundColor : item.attendance_records.length ? 'var(--complete)' : 'unset'}" v-for="(item, index) in filteredUsers" :key="index">
                        <td>{{ item.name }}</td>
                        <td>{{ item.attendance_records.length ? item.attendance_records[0].month_petition : ''}}</td>
                        <td style="white-space: nowrap;" v-html="hasIncident(item)"></td>
                        <td style="white-space: nowrap;" v-html="hasShokkai(item)"></td>
                        <td>
                            <img v-if="weather_average[item.id]" :src="'/images/icon_' + weather_average[item.id].current_value + '.svg'" alt="Weather Icon" width="30" height="17" />
                        </td>                        
                        <td v-html="item.shift_records.length ? '済' : ''"></td>
                        <td style="white-space: nowrap;" v-for="number in [3,5,6,7,8,9,10,11,12,13,14,15]" v-html="computedHoliday(item.id, number)"></td>
                        <td v-html="conversionTime(month_work_time[item.id])"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
    import moment from 'moment';
    import MonthPicker from '../../Global/MonthPicker.vue'
    import { computed, inject, onMounted, ref } from 'vue';
    import { useResponsive } from '@/store/responsive';
    import UserSearchBar from '../UserSearchBar.vue';
    const keywords = ref('')
    const selectedYear = ref(moment().year())
    const selectedMonth = ref(moment().month())
    const attendance_record_items = ref([])
    const paid_holiday_record = ref([])
    const month_work_time = ref([])
    const users = ref([])
    const weather_average = ref([])
    const kintone_data = ref([])
    const responsive = useResponsive()
    const { notify } = inject('dialog')
    const fetch = ref(0)
    onMounted(async() => {
        await getData()
        fetch.value ++
    })
    const filteredUsers = computed(() => {
        let result = users.value.filter(user1 => {
            return Object.values(user1).some(val => 
                String(val).toLowerCase().includes(keywords.value)
            )
        })
        return result
    })
    const dayFormat = (date) => {
        return moment(date).locale('ja').format('Do')
    }
    const hasIncident = (user) => {
        let days = ''
        user.time_card_records.forEach(record => {
            if(record.custom_field_data_records && record.custom_field_data_records.length){
                if(days == ''){
                    days = ''
                }
                days = days + `<div style="color:tomato">${dayFormat(record.day)}</div>`
            }
        });
        return days
    }
    const downloadCSV = () => {
        var csv = '\ufeff' + '社員コード,社員名,所定労働時間,就業形態,職階,勤怠月,給与支払日,確定フラグ,予定稼働日,通常出勤日数,休日出勤日数,欠勤日数,年休時間,計画年休,1日年休,半日年休,1時間年休,2時間年休,3時間年休,4時間年休,5時間年休,6時間年休,7時間年休,慶弔休暇,特別休暇,休業,労働時間,欠勤時間,残業時間,深夜勤務,遠方手当,宿泊日当\n';
        attendance_record_items.value.forEach(el => {
            const data = kintone_data.value.filter(ob => ob.user_code == el['user_code'])
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
            + el['planned_paid_holiday'] + ','
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
        const date = moment([selectedYear.value, selectedMonth.value]).format('YYYY-MM')
        link.href = window.URL.createObjectURL(blob);
        link.download = 'work_' + date + '.csv';
        link.click();

    }

    const getData = async() => {
        try{
            const date = moment([selectedYear.value, selectedMonth.value]).format('YYYY-MM')
            const params = {
                month : date
            }
            const response = await axios.post('/get_admin_work', params )
            attendance_record_items.value = response.data.attendance_record,
            paid_holiday_record.value = response.data.paid_holiday_record,
            month_work_time.value = response.data.month_work_time,
            users.value = response.data.users,
            weather_average.value = response.data.weather_average,
            kintone_data.value = response.data.kintone_data
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
        }
    }
    const conversionTime = (value) => {
        if(value == '' || value == null){
            var value = '';
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
    }
    const setDate = (date) => {
        selectedYear.value = date.year
        selectedMonth.value = date.month - 1
        getData()
    }
    const computedHoliday = (userId, type) => {
        let days = ''
        if(paid_holiday_record.value.hasOwnProperty(userId)){
            const matchedDays = paid_holiday_record.value[userId].filter(ob => ob.type == type)
            matchedDays.forEach(matched => {
                if(days == '') {
                    days = ''
                }
                days = days + `<div>${dayFormat(matched.day)}</div>`
            });
        }
        return days
    }
    const hasShokkai = (user) => {
        let shokkai = ''
        if(user.user_code){
            const matched = kintone_data.value.find(ob => parseInt(ob.user_code) == user.user_code)
            if(matched && matched.general_position){
                shokkai =  matched.general_position
            }
        }
        return shokkai
    }
</script>
<style lang="scss" scoped>

    .admin-work-header{

        display: flex;
        gap:20px;
    }
    .admin-month-wrapper{
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .admin-work-table{
        background-color: var(--background-color);
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
    }
    .admin-work-table td{
        padding: 10px;
        font-size: 14px;
        border-bottom: 1px solid rgb(102, 102, 102);
        border-right: 1px solid rgb(102, 102, 102);
    }
    table td:first-child {
        border-left: 1px solid rgb(102, 102, 102);
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
    
</style>