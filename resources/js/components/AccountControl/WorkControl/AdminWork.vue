<template>
    <div style="overflow:hidden; height: 100%; width: 100%;position: relative">
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <div class="admin-sub-c-bar">
            <PostSearchBar 
                className="newChatMemberSearch" 
                style="width:auto;min-width: 300px;" 
                @search-start="(word) => {keywords = word}"
            />   
            <div class="admin-work-header">
                <div class="admin-button" @click="departmentCSV">部門CSV出力</div>
                <div class="admin-button" @click="exportCSV">勤怠CSV出力</div>
                <div class="admin-button" @click="expenseCSV">経費CSV出力</div>
                <div class="admin-button" @click="vehicleCSV">車両CSV出力</div>
                <div class="admin-month-wrapper">
                    <MonthPickerNew
                        v-model:month="selectedMonth"
                        v-model:year="selectedYear"
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
                        <td class="admin-table-data" rowspan="2">社員名</td>
                        <td class="admin-table-data" colspan="2">勤怠</td>
                        <td class="admin-table-data" rowspan="2">インシデント</td>
                        <td class="admin-table-data" rowspan="2">車両</td>
                        <td class="admin-table-data" rowspan="2">職階</td>
                        <td class="admin-table-data" rowspan="2">天気<br><span style="font-size: 11px;">（3日連続）</span></td>
                        <td class="admin-table-data" rowspan="2">計画有給</td>
                        <td class="admin-table-data" colspan="9">年休</td>
                        <td class="admin-table-data" colspan="4">休暇</td>
                        <td class="admin-table-data" rowspan="2">経費</td>
                        <td class="admin-table-data" rowspan="2">インセ</td>
                        <td class="admin-table-data" rowspan="2">労働時間</td>
                    </tr>
                    <tr>
                        <td class="admin-table-data" style="border-left: none;">確定</td>
                        <td class="admin-table-data">予定</td>
                        <td class="admin-table-data">1日</td>
                        <td class="admin-table-data">半日</td>
                        <td class="admin-table-data">1時間</td>
                        <td class="admin-table-data">2時間</td>
                        <td class="admin-table-data">3時間</td>
                        <td class="admin-table-data">4時間</td>
                        <td class="admin-table-data">5時間</td>
                        <td class="admin-table-data">6時間</td>
                        <td class="admin-table-data">7時間</td>
                        <td class="admin-table-data">慶弔</td>
                        <td class="admin-table-data">転勤</td>
                        <td class="admin-table-data">ODA</td>
                        <td class="admin-table-data">代休</td>
                    </tr>
                </thead>
                <tbody>
                    <tr :style="{backgroundColor : item.attendance_records.length ? 'var(--complete)' : 'unset'}" v-for="(item, index) in filteredUsers" :key="index">
                        <td>{{ item.name }}</td>
                        <td>{{ item.attendance_records.length ? item.attendance_records[0].month_petition : ''}}</td>
                        <td v-html="item.shift_records.length ? '済' : ''"></td>
                        <td style="white-space: nowrap;" v-html="hasIncident(item)"></td>
                        <td style="white-space: nowrap;" v-html="hasVehicle(item)"></td>
                        <td style="white-space: nowrap;">{{ item.general_position }}</td>
                        <td>
                            <WeatherIcon v-if="weather_average[item.id]" :which="weather_average[item.id].current_value" :size="15"/>
                        </td>                        
                        <td style="white-space: nowrap;" v-for="number in [3,5,6,7,8,9,10,11,12,13,14,15,16,17]" v-html="computedHoliday(item.id, number)"></td>
                        <td style="white-space: nowrap;">{{ monthly_expenses[item.id] ? `${monthly_expenses[item.id]}円` : '' }}</td>
                        <td style="white-space: nowrap;">{{ monthly_incentive[item.id] }}</td>
                        <td v-html="conversionTime(month_work_time[item.id])"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { useResponsive } from '@/store/responsive';
import { mkConfig, generateCsv, download } from "export-to-csv";
import PostSearchBar from '../../Post/PostSearchBar.vue';
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
import { vehicleAsOptions } from '@/utils/workApi';
import { DateTime } from 'luxon';
import MonthPickerNew from '@/components/Global/MonthPickerNew.vue';
    const keywords = ref('')
    const selectedYear = ref(DateTime.now().year)
    const selectedMonth = ref(DateTime.now().month)
    const attendance_record_items = ref([])
    const paid_holiday_record = ref([])
    const month_work_time = ref([])
    const users = ref([])
    const weather_average = ref([])
    const monthly_expenses = ref([])
    const monthly_incentive = ref([])
    const timecard_costs = ref([])
    const departmentCount = ref([])
    const responsive = useResponsive()
    const { notify } = inject('dialog')
    const fetch = ref(0)
    const costOptions = [
        {label: '交通費', value: 1},
        {label:'通信費', value: 2},
        {label:'宿泊費', value: 3},
        {label: '旅費交通費', value: 4},
        {label:'消耗品費', value: 5},
        {label:'交際費', value: 6},
        {label:'支払手数料', value: 7},
        {label:'福利厚生費', value: 8}
    ]
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
        return DateTime.fromISO(date).toFormat('d日')
    }
    const hasIncident = (user) => {
        let days = ''
        user.time_card_records.forEach(record => {
            if(record.custom_field_data_records && record.custom_field_data_records.filter(ob => ob.type_id === 40).length){
                if(days == ''){
                    days = ''
                }
                days = days + `<div style="color:tomato">${dayFormat(record.day)}</div>`
            }
        });
        return days
    }
    const hasVehicle = (user) => {
        let days = ''
        user.time_card_records.forEach(record => {
            if(record.custom_field_data_records && record.custom_field_data_records.filter(ob => ob.type_id === 44).length){
                if(days == ''){
                    days = ''
                }
                days = days + `<div>${dayFormat(record.day)}</div>`
            }
        });
        return days
    }
    const vehicleCSV = () => {
        const date = DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value}).toFormat('yyyy-MM')
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `車両_${date}月`})
        const data = []
        users.value.forEach(user => {
            user.time_card_records.forEach(time_card => {
                const vehicleData = time_card.vehicle_data
                if (vehicleData) {
                    const vehicle = vehicleAsOptions.find(ob => ob.value == vehicleData.vehicle)
                    const row = {
                        "氏名" : user.name,
                        "日付" : time_card.day,
                        "使用車両" : vehicle.label,
                        "アルコールチェックした時間使用前" : vehicleData.alcohol_before_time,
                        "アルコールチェックした時間使用後" : vehicleData.alcohol_after_time,
                        "アルコールチェックした値使用前" : vehicleData.alcohol_before_value,
                        "アルコールチェックした値使用後" : vehicleData.alcohol_after_value,
                        "アルコールチェックした確認者使用前" : vehicleData.before_user.name,
                        "アルコールチェックした確認者使用後" : vehicleData.after_user.name,
                    }
                    data.push(row)
                }
                return []
            })
        })
        if(data && data.length){
            const csv = generateCsv(csvConfig)(data)
            download(csvConfig)(csv);
        } else {
            notify('出力するデータはありません。')
            return
        }

    }
    const departmentCSV = () => {
        const date = selectedDate.value
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `部門_${date}月`});
        const data = []
        departmentCount.value.forEach(department => {
            const row = {
                "計上月" : department.month,
                "部門" : department.department,
                "氏名" : department.username,
                "稼働日数" : department.count,
            }
            data.push(row)
        })
        if(data && data.length){
            const csv = generateCsv(csvConfig)(data)
            download(csvConfig)(csv);
        } else {
            notify('出力するデータはありません。')
            return
        }
    }
    const expenseCSV = () => {
        const date = selectedDate.value
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `経費_${date}月`});
        const data = []
        timecard_costs.value.forEach(cost => {
            const row = {
                "氏名" : cost.user.name,
                "日付" : cost.timecard?.day,
                "部門" : cost.department ? cost.department : '',
                "勘定科目" : costOptions.find(ob => ob.value == cost.type).label,
                "金額" : cost.expenses ? cost.expenses : 0,
            }
            data.push(row)
        })
        if(data && data.length){
            const csv = generateCsv(csvConfig)(data)
            download(csvConfig)(csv);
        } else {
            notify('出力するデータはありません。')
            return
        }
    }
    const exportCSV = () => {
        const date = selectedDate.value
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `勤怠_${date}月`});
        const data = []
        attendance_record_items.value.forEach(item => {     
            const shokkai = users.value.find(user => user.id == item.user_id)?.general_position ?? ''
            const row = {
                "社員コード" : item.user_code,
                "社員名" : item.name,
                "所定労働時間" : item.prescribed_working_hours,
                "就業形態" : item.work_type,
                "職階" : shokkai,
                "勤怠月" : item.date_year_month,
                "給与支払日" : item.pay_day,
                "確定フラグ" : item.month_petition,
                "予定稼働日": item.working_days_shift,
                "通常出勤日数": item.normal_working_days,
                "休日出勤日数" : item.holiday_working_days,
                "欠勤日数": item.absence_days,
                "年休時間": item.paid_holiday_hours,
                "計画年休": item.planned_paid_holiday,
                "1日年休": item.petitionType8_count,
                "半日年休": item.half_day_holiday,
                "1時間年休": item.petitionType1_count,
                "2時間年休": item.petitionType2_count,
                "3時間年休": item.petitionType3_count,
                "4時間年休": item.petitionType4_count,
                "5時間年休": item.petitionType5_count,
                "6時間年休": item.petitionType6_count,
                "7時間年休": item.petitionType7_count,
                "慶弔休暇": item.condolence_holiday,
                "特別休暇": item.special_holiday,
                "ODA休暇": item.oda_holiday,
                "代休" : item.comp_holiday,
                "休業": item.closed_day,
                "労働時間": item.working_hours_no_over,
                "欠勤時間": item.absence_hour,
                "残業時間": item.over_time,
                "深夜勤務": item.night_work_time,
                "インセンティブ件" : item.incentive,
                "遠方手当": item.stay_pay,
                "宿泊日当": item.move_pay,
                "待機手当": item.waiting_pay,
                "在宅手当": item.remote_pay
            }
            data.push(row)
        });
        if(data && data.length){
            const csv = generateCsv(csvConfig)(data)
            download(csvConfig)(csv);
        } else {
            notify('出力するデータはありません。')
            return
        }
        
    }
    

    const getData = async() => {
        try{
            const date = selectedDate.value
            const params = {
                month : date
            }
            const data = await axios.post('/get_admin_work', params ).then(res => res.data)
            attendance_record_items.value = data.attendance_record,
            paid_holiday_record.value = data.paid_holiday_record,
            month_work_time.value = data.month_work_time,
            users.value = data.users,
            weather_average.value = data.weather_average,
            monthly_expenses.value = data.monthly_expenses
            monthly_incentive.value = data.monthly_incentive
            timecard_costs.value = data.timecard_costs
            departmentCount.value = data.departments
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
        selectedMonth.value = date.month
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
    const selectedDate = computed(() => {
        return DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value}).toFormat('yyyy-MM')
    })
</script>
<style lang="scss" scoped>
    .admin-table-data{
        font-size: 13px;
        text-align: center;
        vertical-align: middle;
    }
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
        font-size: 13px;
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