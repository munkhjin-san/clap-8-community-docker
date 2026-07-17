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
                <div class="gas-rate-display">
                    <span class="gas-rate-label">ガソリン単価</span>
                    <span class="gas-rate-value">{{ gasoline_rate ? `${formatRate(gasoline_rate.rate)}円/L` : '未設定' }}</span>
                    <button class="gas-rate-edit" title="ガソリン単価を編集" @click="openGasModal">
                        <Edit :size="13" color="var(--primary-color)" />
                    </button>
                </div>
                <div class="admin-button" @click="departmentCSV">部門CSV出力</div>
                
                <div class="admin-button" @click="exportCSV">勤怠CSV出力</div>
                <div class="admin-button" @click="expenseCSV">経費CSV出力</div>
                <div class="admin-button" @click="vehicleCSV">車両CSV出力</div>
                <div class="admin-button" @click="oneShotConfirmation">一発承認</div>
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
                        <td class="admin-table-data" colspan="2">申請中</td>
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
                        <td class="admin-table-data !border-l-0">確定</td>
                        <td class="admin-table-data">予定</td>
                        <td class="admin-table-data">予定</td>
                        <td class="admin-table-data">日報</td>
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
                        <td>{{ item.shift_records.filter(shift => shift.status_flag === 2).length }}</td>
                        <td>{{ item.time_card_records.filter(record => record.status_flag === 1).length }}</td>
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
                        <td style="white-space: nowrap;">{{ monthly_result[item.id]?.find(ob => ob.unit_id ==='COUNT')?.total_amount || '' }}</td>
                        <td style="white-space: nowrap;">{{ item?.monthly_mileage ? `${item.monthly_mileage}km` : '' }}</td>
                        <td v-html="conversionTime(month_work_time[item.id])"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <Modal v-if="showGasModal" size="medium" @close="showGasModal = false">
        <template #title>
            <h2 class="gas-modal-title">ガソリン単価（全社共通）</h2>
        </template>
        <template #content>
            <div class="gas-modal-body">
                <p class="gas-modal-hint">
                    新しい単価を追加すると、適用開始日以降のマイカー走行距離の計算に使用されます。<br>
                    マイカーガソリン代 =（走行距離 ÷ 実燃費）× ガソリン単価（円/L）。
                </p>
                <div class="gas-add-form">
                    <label class="gas-field">
                        <span>金額（円/L）</span>
                        <input type="number" min="0" step="0.01" v-model="newRate" placeholder="例: 175">
                    </label>
                    <label class="gas-field">
                        <span>適用開始日</span>
                        <input type="date" v-model="newEffectiveFrom">
                    </label>
                    <button class="admin-button gas-add-btn" :disabled="gasSaving" @click="addGasolineRate">追加</button>
                </div>
                <div class="gas-history-wrapper">
                    <table class="gas-history-table">
                        <thead>
                            <tr>
                                <td>適用開始日</td>
                                <td>金額</td>
                                <td>変更者</td>
                                <td>登録日時</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!gasoline_history.length">
                                <td colspan="4" class="gas-empty">履歴はまだありません。</td>
                            </tr>
                            <tr
                                v-for="row in gasoline_history"
                                :key="row.id"
                            >
                                <td>
                                    {{ formatDate(row.effective_from) }}
                                    <span v-if="isCurrentRate(row)" class="gas-current-badge">現在</span>
                                </td>
                                <td>{{ formatRate(row.rate) }}円/L</td>
                                <td>{{ row.creator?.name ?? '—' }}</td>
                                <td>{{ formatDateTime(row.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </Modal>
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
import Modal from '@/components/Global/Modal.vue';
import Edit from '@/components/Icons/Edit.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
    const keywords = ref('')
    const selectedYear = ref(DateTime.now().year)
    const selectedMonth = ref(DateTime.now().month)
    const attendance_record_items = ref([])
    const paid_holiday_record = ref([])
    const month_work_time = ref([])
    const month_work_days = ref([])
    const users = ref([])
    const weather_average = ref([])
    const monthly_expenses = ref([])
    const monthly_incentive = ref([])
    const monthly_result = ref([])
    const timecard_costs = ref([])
    const departmentCount = ref([])
    const responsive = useResponsive()
    const { ping, ask } = useDialog()
    const api = useApi()
    const fetch = ref(0)
    const my_car_usage = ref([])
    const gasoline_rate = ref(null)
    const gasoline_history = ref([])
    const showGasModal = ref(false)
    const newRate = ref('')
    const newEffectiveFrom = ref(DateTime.now().toFormat('yyyy-MM-dd'))
    const gasSaving = ref(false)
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
        getGasolineRate()
    })
    const getGasolineRate = async () => {
        const data = await api.get('/gasoline_rate')
        if (!data) return
        gasoline_rate.value = data.current ?? null
        gasoline_history.value = data.history ?? []
    }
    const openGasModal = () => {
        newRate.value = ''
        newEffectiveFrom.value = DateTime.now().toFormat('yyyy-MM-dd')
        showGasModal.value = true
    }
    const addGasolineRate = async () => {
        if (newRate.value === '' || Number(newRate.value) < 0 || isNaN(Number(newRate.value))) {
            ping('金額を正しく入力してください。')
            return
        }
        if (!newEffectiveFrom.value) {
            ping('適用開始日を入力してください。')
            return
        }
        gasSaving.value = true
        const data = await api.post('/gasoline_rate', {
            rate: Number(newRate.value),
            effective_from: newEffectiveFrom.value,
        }, { toast: 'ガソリン単価を追加しました。' })
        gasSaving.value = false
        if (!data) return
        gasoline_rate.value = data.current ?? null
        gasoline_history.value = data.history ?? []
        newRate.value = ''
    }
    const isCurrentRate = (row) => {
        return gasoline_rate.value && row.id === gasoline_rate.value.id
    }
    const formatRate = (value) => {
        const num = Number(value)
        if (isNaN(num)) return value
        return Number.isInteger(num) ? String(num) : num.toString()
    }
    const formatDate = (value) => {
        if (!value) return ''
        return DateTime.fromISO(value).toFormat('yyyy/MM/dd')
    }
    const formatDateTime = (value) => {
        if (!value) return ''
        return DateTime.fromISO(value).toFormat('yyyy/MM/dd HH:mm')
    }
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
            if(record.project_segments && record.project_segments.filter(ob => ob.details?.includes('incident')).length){
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
            if(record.project_segments && record.project_segments.filter(ob => ob.details?.includes('vehicle')).length){
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
    const projectNameFromLinkedRecord = (record) => {
        return record?.project?.name
            ?? record?.project_segment?.project?.name
            ?? record?.projectSegment?.project?.name
            ?? record?.department
            ?? ''
    }

    const vehicleRowsForTimeCard = (timeCard) => {
        if (Array.isArray(timeCard?.vehicle_records) && timeCard.vehicle_records.length) {
            return timeCard.vehicle_records
        }
        return timeCard?.vehicle_data ? [timeCard.vehicle_data] : []
    }

    const vehicleCSV = () => {
        const date = DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value}).toFormat('yyyy-MM')
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `車両_${date}月`})
        const data = []
        users.value.forEach(user => {
            user.time_card_records.forEach(time_card => {
                vehicleRowsForTimeCard(time_card).forEach(vehicleData => {
                    const vehicle = vehicleAsOptions.find(ob => ob.value == vehicleData.vehicle)
                    const row = {
                        "氏名" : user.name,
                        "日付" : time_card.day,
                        "部門" : projectNameFromLinkedRecord(vehicleData) || time_card.department?.name || '',
                        "使用車両" : vehicle?.label ?? '',
                        "アルコールチェックした時間使用前" : vehicleData.alcohol_before_time,
                        "アルコールチェックした時間使用後" : vehicleData.alcohol_after_time,
                        "アルコールチェックした値使用前" : vehicleData.alcohol_before_value,
                        "アルコールチェックした値使用後" : vehicleData.alcohol_after_value,
                        "アルコールチェックした確認者使用前" : vehicleData.before_user?.name ?? '',
                        "アルコールチェックした確認者使用後" : vehicleData.after_user?.name ?? '',
                    }
                    data.push(row)
                })
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
                '月間労働時間(分)' : department.work_time,
                '月間労働時間' : department.work_time / 60,
                '職務手当に含まれる時間外' : department.job_allowance_over_time,
                '所定労働時間' : department.should_work_time / 60,
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
                "部門" : projectNameFromLinkedRecord(cost),
                "勘定科目" : costOptions.find(ob => ob.value == cost.type)?.label ?? '経費',
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
            const salaryUnit = users.value.find(user => user.id == item.user_id)?.salary_unit ?? ''
            const row = {
                "従業員番号" : item.user_code,
                "姓名" : item.name,
                "所定労働日数(当月)": month_work_days.value[item.user_id] ? month_work_days.value[item.user_id] : '',
                "所定労働時間(当月)" :item?.user?.position_id == 15 ? '0' : item.prescribed_working_hours,
                "就業形態" : item.work_type,
                "職階" : shokkai,
                "給与区分": salaryUnit,
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
                "研修時間（分）": item.training_time,
                "法定休日時間（分）": legalHolidayOvertime(item),
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
        monthly_result.value = data.monthly_result
        timecard_costs.value = data.timecard_costs
        departmentCount.value = data.departments
        my_car_usage.value = data.my_car_usage
        month_work_days.value = data.month_work_days
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
        const legalHolidays = shifts.filter(shift => shift.shift_type.category === 'legal_holiday')
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
    const oneShotConfirmation = async () => {
        const notConfirmed = users.value
        .filter(user =>
            user.attendance_records.length === 0 &&
            user.position_id > 5 &&
            !user.shift_records.some(shift => shift.status_flag === 2) &&
            !user.time_card_records.some(record => record.status_flag === 1) &&
            !user.time_card_records.some(record => record.status_flag === 0)
        )
        .map(user => user.id)
        const payload = {
            user_ids: notConfirmed,
            month: selectedDate.value
        }
        const question = await ask(`${selectedDate.value}分の${notConfirmed.length}人のユーザーを一発承認しますか？`)
        if (!question) return;
        const data = await api.post('/one_shot_confirmation', payload)
        if (data) {
            ping(`${data}人のユーザーを一発承認しました。`)
            getData()
        }
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

    .admin-work-table td:first-child {
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

    .gas-rate-display {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 12px;
        font-size: 13px;
        color: var(--primary-color);
        white-space: nowrap;
    }
    .gas-rate-label {
        opacity: 0.75;
    }
    .gas-rate-value {
        font-weight: 700;
    }
    .gas-rate-edit {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 6px;
        cursor: pointer;
        background: transparent;
    }
    .gas-rate-edit:hover {
        background: var(--bg2);
    }

    .gas-modal-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-color);
    }
    .gas-modal-body {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .gas-modal-hint {
        font-size: 12px;
        opacity: 0.7;
    }
    .gas-add-form {
        display: flex;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
    }
    .gas-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
        color: var(--primary-color);
    }
    .gas-field input {
        height: 38px;
        padding: 0 10px;
        border: 1px solid var(--primary-color);
        border-radius: 6px;
        color: var(--primary-color);
        background: transparent;
        min-width: 150px;
    }
    .gas-add-btn {
        height: 38px;
        margin: inherit;
    }
    .gas-add-btn:disabled {
        opacity: 0.5;
        cursor: default;
    }
    .gas-history-wrapper {
        max-height: 45vh;
        overflow: auto;
    }
    .gas-history-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }
    .gas-history-table thead td {
        position: sticky;
        top: 0;
        background: #363636;
        color: #fff;
        padding: 8px 10px;
        text-align: left;
        white-space: nowrap;
    }
    .gas-history-table tbody td {
        padding: 8px 10px;
        border-bottom: 1px solid var(--bg2);
    }
    .gas-current-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 11px;
        background: var(--primary-color);
        color: var(--background-color);
    }
    .gas-empty {
        text-align: center;
        opacity: 0.6;
    }

</style>