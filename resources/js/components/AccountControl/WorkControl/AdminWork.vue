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
                <!-- <div class="admin-button" @click="myCarCsv">マイカーCSV出力</div> -->
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
        <div style="height:calc(100% - 70px);overflow: auto;" id="admin-table-container">
            
            <table class="admin-work-table">
                <thead class="bg-[#363636] text-white sticky top-0 z-[4]">
                    <tr class="border border-[#666666]">
                        <td class="admin-table-data left-item !bg-[#363636] sticky top-0 z-[4]" rowspan="2">社員名</td>
                        <td class="admin-table-data" colspan="2">勤怠</td>
                        <td class="admin-table-data" rowspan="2">インシデント</td>
                        <td class="admin-table-data" rowspan="2">車両</td>
                        <td class="admin-table-data" rowspan="2">職階</td>
                        <td class="admin-table-data" rowspan="2">天気<br><span class="text-xs">（3日連続）</span></td>
                        <td class="admin-table-data" rowspan="2">計画有給</td>
                        <td class="admin-table-data" colspan="9">年休</td>
                        <td class="admin-table-data" colspan="4">休暇</td>
                        <td class="admin-table-data" colspan="3">休日</td>
                        <td class="admin-table-data" rowspan="2">経費</td>
                        <td class="admin-table-data" rowspan="2">インセンティブ</td>
                        <td class="admin-table-data" rowspan="2">マイカー走行距離</td>
                        <td class="admin-table-data" rowspan="2">労働時間</td>
                    </tr>
                    <tr>
                        <td class="admin-table-data border-l-0">確定</td>
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
                        <td class="admin-table-data">法定</td>
                        <td class="admin-table-data">法定外時間</td>
                        <td class="admin-table-data">年間</td>
                    </tr>
                </thead>
                <tbody>
                    <tr class="admin-window-row" :style="{backgroundColor : item.attendance_records.length ? 'var(--complete)' : 'unset'}" v-for="(item, index) in filteredUsers" :key="index">
                        <td class="left-item" :style="{backgroundColor : item.attendance_records.length ? 'var(--complete)' : 'var(--bg3)'}">{{ item.name }}</td>
                        <td>{{ item.attendance_records.length ? item.attendance_records[0].month_petition : ''}}</td>
                        <td v-html="item.shift_records.length ? '済' : ''"></td>
                        <td style="white-space: nowrap;" v-html="hasIncident(item)"></td>
                        <td style="white-space: nowrap;" v-html="hasVehicle(item)"></td>
                        <td style="white-space: nowrap;">{{ item.general_position }}</td>
                        <td>
                            <WeatherIcon v-if="weather_average[item.id]" :which="weather_average[item.id].current_value" :size="15"/>
                        </td>                        
                        <td style="white-space: nowrap;" v-for="number in [3,5,6,7,8,9,10,11,12,13,14,15,16,17]" v-html="computedHoliday(item.id, number)"></td>
                        <td v-html="legalHoliday(item?.shift_records)"></td>
                        <td v-html="legalHolidayOvertime(item, 'display')"></td>
                        <td v-html="item?.yearly_holiday_minutes && yearlyHolidayTime(item.yearly_holiday_minutes, item.work_minutes_per_day) || ''"></td>
                        <td style="white-space: nowrap;">{{ monthly_expenses[item.id] ? `${monthly_expenses[item.id]}円` : '' }}</td>
                        <td style="white-space: nowrap;">{{ monthly_incentive[item.id] }}</td>
                        <td style="white-space: nowrap;">{{ item?.monthly_mileage ? `${item.monthly_mileage}km` : '' }}</td>
                        <td v-html="conversionTime(month_work_time[item.id])"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue';
import { useResponsive } from '@/store/responsive';
import { mkConfig, generateCsv, download } from "export-to-csv";
import PostSearchBar from '../../Post/PostSearchBar.vue';
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
import { vehicleAsOptions } from '@/utils/workApi';
import { DateTime } from 'luxon';
import MonthPickerNew from '@/components/Global/MonthPickerNew.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
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
    const { ping } = useDialog()
    const api = useApi()
    const fetch = ref(0)
    const my_car_usage = ref([])
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
    const myCarCsv = () => {
        const date = selectedDate.value
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `マイカー_${date}月`});
        const data = []
        my_car_usage.value.forEach(car => {
            const row = {
                '氏名' : car.user_name,
                '日付' : car.date,
                '部門' : car.project,
                'マイカー走行距離' : car.mileage,
                'ガソリン代/日額' : car.gas_full_price
            }
            data.push(row)
        })
        if(data && data.length){
            const csv = generateCsv(csvConfig)(data)
            download(csvConfig)(csv);
        } else {
            ping('出力するデータはありません。')
            return
        }
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
            ping('出力するデータはありません。')
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
            ping('出力するデータはありません。')
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
                "部門" : cost.department ?? "",
                "勘定科目" : costOptions.find(ob => ob.value == cost.type).label,
                "金額" : cost.expenses ?? 0,
            }
            data.push(row)
        })
        my_car_usage.value.forEach(car => {
            const row = {
                '氏名' : car.user_name,
                '日付' : car.date,
                '部門' : car.project,
                '勘定科目' : '旅費交通費',
                '金額' : car.gas_full_price
            }
            data.push(row)
        })
        if(data && data.length){
            const csv = generateCsv(csvConfig)(data)
            download(csvConfig)(csv);
        } else {
            ping('出力するデータはありません。')
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
                "従業員番号" : item.user_code,
                "姓名" : item.name,
                "所定労働時間(当月)" :item?.user?.position_id == 15 ? '0' : item.prescribed_working_hours,
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
                "労働時間（分）": item.working_hours_no_over,
                "欠勤時間（分）": item.absence_hour,
                "残業時間（分）": item.over_time,
                "深夜勤務（分）": item.night_work_time,
                "インセンティブ件" : item.incentive,
                "遠方": item.stay_pay,
                "宿泊数": item.move_pay,
                "待機日数": item.waiting_pay,
                "マイカー日数": item.vehicle_pay,
                "特別通勤日数": item.special_commute_pay,
                "在宅日数（個人都合）": item.remote_personal_pay,
                "在宅日数（会社都合）": item.remote_company_pay,
                "経費合計額": monthly_expenses.value[item.user_id] ? monthly_expenses.value[item.user_id] : '',
                "マイカー走行距離" : item.mileage ? item.mileage : '',
            }
            data.push(row)
        });
        if(data && data.length){
            const csv = generateCsv(csvConfig)(data)
            download(csvConfig)(csv);
        } else {
            ping('出力するデータはありません。')
            return
        }
        
    }
    

    const getData = async() => {
 
        const date = selectedDate.value
        const params = {
            month : date
        }
        const data = await api.post('/get_admin_work', params )
        attendance_record_items.value = data.attendance_record,
        paid_holiday_record.value = data.paid_holiday_record,
        month_work_time.value = data.month_work_time,
        users.value = data.users,
        weather_average.value = data.weather_average,
        monthly_expenses.value = data.monthly_expenses
        monthly_incentive.value = data.monthly_incentive
        timecard_costs.value = data.timecard_costs
        departmentCount.value = data.departments
        my_car_usage.value = data.my_car_usage

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

    const legalHoliday = (shifts) => {
        if(!shifts || !shifts.length) {
            return ''
        }
        const legalHolidays = shifts.filter(shift => shift.shift_type.id == 18)
        if(!legalHolidays.length) {
            return ''
        }
        let days = ''
        legalHolidays.forEach(holiday => {
            days = days + `<div>${dayFormat(holiday.shift_day)}</div>`
        });
        return days
    }
    const yearlyHolidayTime = (value, userWorkMinutesPerDay) => {
        const minutesPerDay = userWorkMinutesPerDay || 480; // Default to 480 minutes (8 hours) if not provided

        if (!value || !minutesPerDay) return '0日';

        const totalDays = Math.floor(value / minutesPerDay);
        const color = totalDays > 111 ? 'tomato' : 'inherit';
        const remainingMinutes = value % minutesPerDay;
        const remainingHours = Math.floor(remainingMinutes / 60);

        let result = `${totalDays}日`;
        if (remainingHours > 0) result += `${remainingHours}時間`;
        result = `<span style="color:${color}">${result}</span>`;

        return result;
    }
    const legalHolidayOvertime = (item, mode) => {
        const minutes = item?.legal_holiday_worked_time_in_minutes || 0;
        if (mode === 'export'){
            return minutes;
        }
        if (minutes === 0) return '';
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;
        let result = `${hours}時間`;
        if (remainingMinutes > 0) {
            result += `${remainingMinutes}分`;
        }
        return result;
    }
</script>
<style lang="scss" scoped>
    #admin-table-container {
        scrollbar-width: auto;      /* Firefox */
        scrollbar-color: auto;      /* Firefox */
    }

    #admin-table-container::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .admin-window-row:hover{
        background-color: var(--bg2);

    }
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
        width: max-content;
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
    .left-item {
        background: var(--bg3);
        z-index: 3;
        position: sticky;
        left: 0;
    }
    
</style>