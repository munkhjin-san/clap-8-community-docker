<template>
    <Modal @close="emit('closeModal')" persist :loader="spinner < 1">        
        <template #title>
            <p style="font-size: 18px;">{{formatedDay}}の日報を作成する</p>
        </template>
        <template #content>
            <div class="report-wrapper" style="background:inherit;">
                <div class="report-field" id="timesheetProjectSelect">
                    <p class="report-header">プロジェクト</p>
                    <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="todayWorkGroup">
                        <option v-for="group in workGroupAsOptions" :value="group.id">{{ group.name }}</option>
                    </select>
                </div>
                <div class="report-field">
                    <p class="report-header">就業時間</p>
                    
                    <div class="report-input-time">
                        <div>
                            <input name="workStartTime" class="taskDateTimePicker" :class="{'clock-color' : theme.dark == true }" type="time" v-model="editStartTime" step="900">
                        </div>
                        <div class="between-line">～</div>
                        <div>
                            <input name="workEndTime" class="taskDateTimePicker" :class="{'clock-color' : theme.dark == true }" type="time" v-model="editEndTime" step="900">
                        </div>
                    </div>
                    <div v-if="shift?.overtime_request" style="font-size: 12px;line-height:1.5">
                        <div>※申請した残業時間は<strong>{{shift?.overtime_request.minutes}}分</strong>です。退勤は1分単位で入力してください。</div>
                    </div>
                </div>
                <div class="report-field">
                    <p class="report-header">研修時間</p>
                    <div class="report-input">
                        <div class="report-input-wrapper">
                            <input id="hasTraining" name="trainingPre" type="radio" v-model="hasTraining" :value="1">
                            <label for="hasTraining">あり</label>
                        </div>
                        <div class="report-input-wrapper">
                            <input id="noTraining" name="trainingPre" type="radio" v-model="hasTraining" :value="0">
                            <label for="noTraining">なし</label>
                        </div>
                    </div>

                    <div v-if="hasTraining" class="report-input-time">
                        <div>
                            <input name="trainingStartTime" class="taskDateTimePicker" :class="{'clock-color' : theme.dark == true }" type="time" v-model="trainingStartTime" step="900">
                        </div>
                        <div class="between-line">～</div>
                        <div>
                            <input name="trainingEndTime" class="taskDateTimePicker" :class="{'clock-color' : theme.dark == true }" type="time" v-model="trainingEndTime" step="900">
                        </div>
                    </div>
                </div>
                <div class="report-field">
                    <p class="report-header">休憩時間</p>
                    <div class="report-input">
                        <select class="dropDownSelector taskDateTimePicker" v-model="breakTimeSelect" name="breakTimeSelect">
                            <option :key="index" v-for="(item , index) in breakTimeOptions" :value="item.value">{{ item.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="report-field" style="background:inherit;">
                    <p class="report-header" style="margin-bottom: 20px;">経費</p>
                    <CostField 
                        v-for="cost, index in costs"
                        :key="index"
                        v-model:department="cost.department"
                        v-model:content="cost.content"
                        v-model:type="cost.type"
                        v-model:expenses="cost.expenses"
                        v-model:file_path="cost.file_path"
                        :workGroupAsOptions="workGroupAsOptions.map(ob => ob.name)"
                        :fieldIndex="index"
                        :isRegistered="item.position_id === 15"
                        @addCostField="addCostField"
                        @removeCostField="removeCostField"
                        @removeFile="removeFile"
                    />
                </div>
                <IncentiveField v-if="item.position_id === 15" v-model="incentives"/>
                <div class="report-field !mb-[35px]">
                    <p class="report-header !mb-4">マイカーの走行距離（往復）</p>
                    <div class="flex gap-4 items-center flex-wrap">
                        <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="car_used_project">
                            <option v-for="group in workGroupAsOptions" :value="group.id">{{ group.name }}</option>
                        </select>
                        <div class="relative w-fit">
                            <input type="number" style="padding: 0px 40px 0 10px;height: 38px;border: 1px solid var(--primary-color);color: var(--primary-color);max-width: 100px;" name="work-mileage" v-model="car_mileage" min="0">
                            <span data-v-73d35938="" style="position: absolute; height: 100%; top: 0px; right: 5px; line-height: 38px;">km</span>
                        </div>
                    </div>
                </div>
                <div id="performanceReport" class="report-field" v-if="selectedProject && selectedProject.has_actual_func">
                    <p class="report-header">実績報告</p>

                    <div class="space-y-2">
                        <div class="flex items-center gap-4"
                            v-for="(row, index) in actualRows"
                            :key="row.status ?? index">
                            <div v-if="row.status" class="min-w-[120px] text-sm">
                                {{ row.status }}
                            </div>

                            <input
                                name="actual_val"
                                :placeholder="unitLabel ? `実績値（${unitLabel}）` : '実績値'"
                                type="number"
                                style="padding: 0px 10px; height:38px; width: 100px; border:1px solid var(--primary-color); color:var(--primary-color);"
                                v-model.number="row.value"
                            />
                        </div>


                        
                    </div>
                </div>

                <div class="report-field" v-if="car_mileage && car_data?.status == 'success'">
                    <table>
                        <thead>
                            <tr>
                                <td>実燃費</td>
                                <td>ガソリン単価</td>
                                <td>ガソリン代</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ car_data.gas_consumption }}km/L</td>
                                <td>{{ car_data.gas_unit_price }}円</td>
                                <td>{{ car_data.gas_full_price }}円</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
                <CustomField 
                    v-for="field in filterCustomValues" 
                    :shift_type="shift?.shift_type" 
                    :data="field"
                    v-model:fieldValue="customValues[field.id]"
                    v-model:vehicle="vehicleData"
                    ref="customFieldRef"
                />              
                <div id="saveButton" class="si-box" style="display: flex; justify-content: center; gap: 20px;">
                    <LoaderButton style="margin: 0" :loading="loading[0]" content="一時保存" @triggered="saveTimeCard(0)" />
                    <LoaderButton style="margin: 0" :loading="loading[1]" content="申請する" @triggered="saveTimeCard(1)" />
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup>
import { computed, onMounted, ref, reactive, watch, useTemplateRef } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useTheme } from '@/store/theme';
import CustomField from './CustomField.vue'
import CostField from './CostField.vue';
import IncentiveField from './IncentiveField.vue'
import { useAuthUserStore } from '../../store/auth';
import Modal from '../Global/Modal.vue';
import { DateTime } from 'luxon';
import { customParser, useDebouncedRef } from '@/utils/tools';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { getCustomFields, getWorkGroup } from '../../utils/workApi';
import { useTutorialStore } from '@/store/tutorial';
import { useTour } from '@/composables/useTour';
    const auth = useAuthUserStore()
    const emit = defineEmits(['reload', 'closeModal'])
    const theme = useTheme()
    
    const props = defineProps([
        'chosenDate', 
        'todayStartTime', 
        'todayEndTime', 
        'todayBreakTime', 
        'customFieldData', 
        'createReport',
        'chosenUserId',
        'notSubmitted',
        'chosenDateShift',
        'item'
    ])
    const customFieldRef = useTemplateRef('customFieldRef')
    const workGroups = ref([])
    const fields = ref([])
    const spinner = ref(0)
    const shift = computed(() => {
        return props.item?.shift
    })
    const timeCard = computed(() => {
        return props.item?.time_card
    })
    const vehicleData = ref(timeCard.value?.vehicle_data ? timeCard.value.vehicle_data : {
        vehicle: null,
        alcohol_before_time: null,
        alcohol_after_time: null,
        alcohol_before_value: null,
        alcohol_after_value: null,
        confirm_before_user: null,
        confirm_before_user: null
    });
    const workGroupAsOptions = computed(() => {
        let filteredgroups
        let mappedgroups
        if(auth.activeUser.id == 608 || auth.activeUser.id == 610){
            filteredgroups = workGroups.value
            .filter(group => 
                group.members.some(member => member.id === props.item.user_id) || 
                group.manager?.some(manager => manager.id === props.item.user_id)
            )            
            mappedgroups = filteredgroups.map(group => group.name);

        } else {
            filteredgroups = workGroups.value
            mappedgroups = workGroups.value.map(group => group.name)
        }
        return filteredgroups
    })
    const costs = reactive([])
    const incentives = ref(timeCard.value?.timecard_incentives?.length ? timeCard.value.timecard_incentives : [
        {
            count: null,
            file: null,
        }
    ])
    const loading = ref([false, false])
    const editStartTime = ref(timeCard.value?.start_time ? timeCard.value.start_time : shift.value?.start_time ? shift.value.start_time : '09:00:00')
    const editEndTime = ref(timeCard.value?.end_time ? timeCard.value.end_time : shift.value?.end_time ? shift.value.end_time : '18:00:00')
    const trainingStartTime = ref(timeCard.value?.training_start_time ? timeCard.value.training_start_time : '')
    const trainingEndTime = ref(timeCard.value?.training_end_time ? timeCard.value.training_end_time : '')
    const breakTimeOptions = ref([{label : 'なし' , value : 0 },
                        {label : '30分' , value : 30 },
                        {label : '45分' , value : 45 },
                        {label : '60分' , value : 60 },
                        {label : '90分' , value : 90 }])
    const breakTimeSelect = ref(timeCard.value?.break_time ? timeCard.value.break_time : 0)
    const customValues = ref({})
    const todayWorkGroup = ref(timeCard.value?.work_group_id ?? '')
    const selectedProject = ref(timeCard.value?.department ?? {})
    const car_used_project = ref(timeCard.value?.car_used_project ?? '')
    const car_mileage = useDebouncedRef('')
    const car_data = ref({})
    const actualRows = ref([
        { status: null, value: null },
    ])
    const costDepartment = computed(() => {
        return workGroupAsOptions.value.find(group => group.id === todayWorkGroup.value)?.name
    })
    const hasTraining = ref(timeCard.value ? (timeCard.value.training_start_time ? 1 : 0) : undefined)
    const api = useApi()
    const { ask, ping, toast } = useDialog()
    watch(car_mileage, (after) => {
        if (after) {
            getMyCarData()
        }
    })
    watch(todayWorkGroup, (newWorkGroup) => {
        costs.forEach(cost => {
            cost.department = workGroupAsOptions.value.find(group => group.id === newWorkGroup)?.name
        })
        selectedProject.value = workGroupAsOptions.value.find(group => group.id === newWorkGroup)
    })
    // watch(selectedProject, (newVal) => {
    //     actualRows.value = [{ status: null, value: null }]
    // })
    const buildRowsFromStatuses = () => {
        const statuses = selectedProject.value?.actual_statuses ?? []

        if (statuses.length) {
            actualRows.value = statuses.map((s) => ({
            status: s.label ?? s.custom_label ?? null,
            value: null,
            }))
        } else {
            actualRows.value = [{ status: null, value: null }]
        }
    }
    watch(
        () => selectedProject.value?.actual_statuses,
        () => buildRowsFromStatuses(),
        { immediate: true, deep: true }
    )
    watch(
        () => timeCard.value?.project_case,
        (cases) => {
            const list = cases || [];

            if (!list.length) {
                actualRows.value = [{ status: null, value: null }];
                return;
            }

            actualRows.value = list.map(c => ({
                status: c.status ?? null,
                value: c.amount ?? null,
            }));
        },
        { immediate: true }
    );
    const unitCode = computed(() => selectedProject.value?.unit_id ?? 'JPY');
    const unitLabel = computed(() => {
        if (unitCode.value === 'COUNT') return '件';
        if (unitCode.value === 'HOUR') return '時間';
        if (unitCode.value === 'CUSTOM') return selectedProject.value?.custom_unit_label || '単位';
        return '円';
    });
    const addCostField = () => {
        if(costs.length >= 10){
            ping('上限は10個です。')
            return
        }
        costs.push({
            department: costDepartment.value ?? '',
            content: '',
            type: props.item.position_id == 15 ? 1 : 4,
            expenses: null,
            file_path: null,
        })
    }
    const removeCostField = async(index) => {
        costs.splice(index, 1)
        if(costs.length == 0){
            addCostField()
        }
    }
    const removeFile = async(index) => {
        costs[index].file_path = null
    }
    const getMyCarData = async() => {
        if (car_mileage.value < 2) return
        
        const data = await api.get('/get_my_car_data', { user_code: props.item.user_code, mileage: car_mileage.value})
        if (!data) return
        car_data.value = data
        
    }
    const tutorialStore = useTutorialStore()
    const { startTour } = useTour()
    onMounted(async() => {
        fields.value = await getCustomFields()
        workGroups.value = await getWorkGroup()
        setTimeout(() => {
            if(!timeCard.value || !timeCard.value?.work_group_id){
                todayWorkGroup.value = workGroupAsOptions.value[0]?.id ?? ''
                car_used_project.value = todayWorkGroup.value
                spinner.value++
            } else {
                spinner.value++
            }
        }, 100);

        
        if(props.item?.total_break_time){
            const newItem = {
                label: props.item?.total_break_time + '分',
                value: props.item?.total_break_time
            }
            breakTimeOptions.value.push(newItem)
            breakTimeSelect.value = props.item?.total_break_time
        } else {
            breakTimeCalc()
        }
        if (timeCard.value?.car_mileage) {
            car_mileage.value = timeCard.value?.car_mileage
        }
        costsFill()
        customFieldFill()
        if (tutorialStore.state.active && tutorialStore.state.name.includes('timesheet.dailyreport')) {
            console.log(todayWorkGroup.value)
            setTimeout(() => {
                todayWorkGroup.value = workGroupAsOptions.value.find(group => group.has_actual_func === true)?.id
                startTour('timesheet.dailyreport.create.details')
            }, 500)
            tutorialStore.setTutorial({ active: true, name: [] })
        }
    })
    const costsFill = () => {
        if(timeCard.value?.timecard_costs?.length){
            timeCard.value.timecard_costs.forEach(cost => {
                const boil = { ...cost}
                costs.push(boil)
            });
        }
        if(!costs.length){
            addCostField()
        }
    }
    const customFieldFill = () => {
        if(fields.value){
            fields.value.forEach(element => {
                const index = element.id == 39 || element.id == 42 ? 'value_text' : 'value_int'
                const pre = timeCard.value?.custom_field_data_records.filter(ob => ob.type_id == element.id && ob.user_id == timeCard.value.user_id)
                if(element.id == 37){
                    const allowance = pre && pre.length ? pre.map(ob => ob.value_int) : []
                    customValues.value[element.id] = allowance
                    
                }else{
                    customValues.value[element.id] = pre && pre.length ? pre[0][index] !== null ? pre[0][index].toString() : '' : ''
                }               
            });
        }
    }
    const shiftWorkTime = computed(() => {
        const shiftStartTime = shift.value && shift.value?.start_time ? shift.value?.start_time : '09:00:00'
        const shiftEndTime = shift.value && shift.value?.end_time ? shift.value?.end_time : '18:00:00'
        const start = DateTime.fromFormat(shiftStartTime, 'HH:mm:ss')
        const end = DateTime.fromFormat(shiftEndTime, 'HH:mm:ss')
        return end.diff(start, 'minutes').as('minutes')
    })
    const workedTime = computed(() => {
        const [fixedStartHour, fixedStartMinute] = editStartTime.value.split(':')
        const [fixedEndHour, fixedEndMinute] = editEndTime.value.split(':')
        const start = DateTime.fromFormat(`${fixedStartHour}:${fixedStartMinute}`, 'HH:mm')
        const end = DateTime.fromFormat(`${fixedEndHour}:${fixedEndMinute}`, 'HH:mm')
        return end.diff(start, 'minutes').as('minutes')
    })
    const filterCustomValues = computed(() => {
        if(workedTime.value > shiftWorkTime.value && props.item?.work_type == 1 && !shift.value?.overtime_request){
            return fields.value
        }else{
            return fields.value.filter(ob => ob.id !== 42)
        }
    })
    const breakTimeCalc = () => {
        if(editStartTime.value && editEndTime.value && breakTimeSelect.value == 0){
            const startTimeParts = editStartTime.value.split(":");
            const endTimeParts = editEndTime.value.split(":");
            const startHour = parseInt(startTimeParts[0]);
            const startMinute = parseInt(startTimeParts[1]);
            const endHour = parseInt(endTimeParts[0]);
            const endMinute = parseInt(endTimeParts[1]);

            const workTimeMinutes = (endHour * 60 + endMinute) - (startHour * 60 + startMinute);

            if (workTimeMinutes >= 360) {
                breakTimeSelect.value = 60;
            } else if (workTimeMinutes >= 180 && workTimeMinutes < 360) {
                breakTimeSelect.value = 30;
            } else if (workTimeMinutes < 180) {
                breakTimeSelect.value = 0;
            }
        }
    }
    
    const formatedDay = computed(() => {
        return DateTime.fromISO(props.item?.day_full).toFormat('M月d日')
    })
    
    const showToastIfEmpty = async() => {
        return new Promise ((resolve) => {
            const targets = [39,40,41,44]
            if (workedTime.value > shiftWorkTime.value && props.item?.work_type == 1 && !shift.value?.overtime_request) {
                targets.push(42)
            }
            targets.forEach(index => {
                const v = customValues.value[index]
                if(!v){
                    const fieldName = fields.value.find(ob => ob.id == index)?.title
                    const message = `${fieldName}は必須項目です。必ず選択してください。`
                    ping(message)
                    resolve(false)
                }
                if(index == 44 && v == 1){
                    if(!vehicleConfirm()){
                        resolve(false)
                    }
                }
            })
            const invalidCustomFields = customFieldRef.value?.filter(field => field.subPartsChecked === false)
            if(invalidCustomFields && invalidCustomFields.length > 0){
                ping('在宅手当の種類を選択してください。')
                resolve(false)
            }
            resolve(true)
        })
    }
    const vehicleConfirm = () => {
        if (!vehicleData.value) {
            ping('車両の使用に関する情報はありません。');
            return false;
        } else if (vehicleData.value['vehicle'] === null) {
            ping('車両が選択されていません。');
            return false;
        } else if (!vehicleData.value['alcohol_before_time'] || !vehicleData.value['alcohol_after_time']) {
            ping('前後の時間が選択されていません。');
            return false;
        } else if (vehicleData.value['alcohol_before_value'] == null || !vehicleData.value['alcohol_after_value'] == null) {
            ping('前後の値が選択されていません。');
            return false;
        } else if (!vehicleData.value['confirm_before_user'] || !vehicleData.value['confirm_after_user']) {
            ping('前後の確認者が選択されていません。');
            return false;
        }
        return true;
    };
    const formatTime = (time) => { 
        const [hours, minutes] = time.split(':')
        return `${hours}:${minutes}`
    }
    
    const fifteenMinuteCalc = async() => {
        return new Promise((resolve) => {
            const [endhours, endminutes] = editEndTime.value.split(":");
            let endnearestMinute = Math.floor(endminutes / 15) * 15;
            let endhoursAdjustment = 0;
            if (endnearestMinute === 60) {
                endnearestMinute = 0;
                endhoursAdjustment = 1;
            }
            const adjustedEndHours = parseInt(endhours) + endhoursAdjustment;
            if (auth.activeUser.id !== 610) {
                editEndTime.value = `${adjustedEndHours.toString().padStart(2, "0")}:${String(endnearestMinute).padStart(2, "0")}`;
            }
            const [hours, minutes] = editStartTime.value.split(":");
            let nearestMinute = Math.ceil(minutes / 15) * 15;
            let hoursAdjustment = 0;
            if (nearestMinute === 60) {
                nearestMinute = 0;
                hoursAdjustment = 1;
            }
            const adjustedHours = parseInt(hours) + hoursAdjustment;
            editStartTime.value = `${adjustedHours.toString().padStart(2, "0")}:${String(nearestMinute).padStart(2, "0")}`;
            resolve(true)
        })
    }

    const diffInMinutes = computed(() => {
        const today = props.item?.day_full
        const [starthours, startminutes] = editStartTime.value.split(":");
        const [endhours, endminutes] = editEndTime.value.split(":");
        const stringStartTime = `${today} ${starthours}:${startminutes}:00`
        const stringEndTime = `${today} ${endhours}:${endminutes}:00`
        const startInstance = customParser(stringStartTime)
        const endInstance = customParser(stringEndTime)
        if(endInstance < startInstance){
            endInstance.plus({days: 1})
        }
        const differenceInMinutes = endInstance.diff(startInstance, 'minutes').as('minutes')

        return differenceInMinutes - breakTimeSelect.value;
    })
    const confirmOvertime = async() => {
        return new Promise(async(resolve) => {
            const overtime = shift.value.overtime_request.minutes + props.item?.work_time_day
            if(diffInMinutes.value > overtime){
                resolve(await ask(`申請した残業時間を超過しています。<strong>${diffInMinutes.value - props.item?.work_time_day}分</strong>で申請しますか`))
                
            }else if(diffInMinutes.value < overtime){
                const workedOverTime = shift.value?.overtime_request.minutes - (overtime - diffInMinutes.value)
                resolve(await ask(`時間外は<strong>${workedOverTime < 0 ? 0 : workedOverTime}分</strong>になります。よろしいですか。`))               
            } else {
                resolve(await ask('日報を申請します。申請後は修正できません。よろしいですか。'))
            }
        })
    }
    const buildParams = async(status_flag) => {
        return new Promise((resolve) => {
            const a = {
                customValues: customValues.value,
                breakTime: breakTimeSelect.value,
                start_time: formatTime(editStartTime.value),
                end_time: formatTime(editEndTime.value),
                training_start_time: hasTraining.value == 1 ? formatTime(trainingStartTime.value) : null,
                training_end_time: hasTraining.value == 1 ? formatTime(trainingEndTime.value) : null,
                day: props.item?.day_full,
                status_flag: status_flag,
                userId: props.item?.user_id,
                overTimeMinute: shift.value?.overtime_request?.minutes,
                costsValues: costs,
                incentiveValues: incentives.value,
                department: todayWorkGroup.value,
                shiftType: props.item?.shift?.shift_type?.id ?? null,
                vehicleData: vehicleData.value,
                car_mileage: car_mileage.value,
                car_used_project: car_used_project.value,
                gas_full_price: car_data.value?.gas_full_price ?? 0,
                actual_results: actualRows.value,
            }
            resolve(a)
        })
    }
    const saveTimeCard = async(status_flag) => {
        const validate = await showToastIfEmpty()
        if(!validate) return
        if (isInvalidTime(formatTime(editStartTime.value)) || isInvalidTime(formatTime(editEndTime.value))) {
            ping('就業時間は必須項目です。入力してください。')
            return
        }
        if(shift.value?.overtime_request){
            const confirm = await confirmOvertime()
            if(!confirm.value) return            
        } else if(status_flag === 1){
            await fifteenMinuteCalc()
            const answer = await ask('日報を申請します。申請後は修正できません。よろしいですか。')
            if(!answer.value) return
        }
        loading.value[status_flag] = true
        const params = await buildParams(status_flag)
        await api.post('/save_time_card', params, {
            toast: '申請しました。'
        })
        emit('reload')
    }
    const isInvalidTime = (t) => {
        if (!t) return true             // null, undefined, empty
        if (typeof t !== 'string') return true
        if (!/^\d{2}:\d{2}$/.test(t)) return true // format sanity
        const [h, m] = t.split(':').map(Number)
        if (Number.isNaN(h) || Number.isNaN(m)) return true
        if (h > 23 || m > 59) return true
        return false
    }
</script>
<style scoped>
    table{
        background-color: var(--background-color);
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
        color: var(--primary-color);
        border-top: 1px solid var(--primary-color);
    }
    table td{
        padding: 10px;
        font-size: 13px;
        border-bottom: 1px solid var(--primary-color);
        border-right: 1px solid var(--primary-color);
    }
    table td:first-child {
        border-left: 1px solid var(--primary-color);
    }
    thead td:first-child{
        border-left: 1px solid var(--primary-color);
    }
</style>