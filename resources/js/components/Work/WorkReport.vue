<template>
    <Modal @close="emit('closeModal')">
        <template #title>
            <p style="font-size: 18px;">{{formatedDay}}の日報を作成する</p>
        </template>
        <template #content>
            <div class="report-wrapper" style="background:inherit;">
                <div class="report-field">
                    <p class="report-header">プロジェクト</p>
                    <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="todayWorkGroup">
                        <option v-for="group in workGroupAsOptions" :value="group.id">{{ group.name }}</option>
                    </select>
                </div>
                <div class="report-field">
                    <p class="report-header">就業時間</p>
                    
                    <div class="report-input-time">
                        <div>
                            <input class="taskDateTimePicker" :class="{'clock-color' : theme.dark == true }" type="time" v-model="editStartTime" step="900">
                        </div>
                        <div class="between-line">～</div>
                        <div>
                            <input class="taskDateTimePicker" :class="{'clock-color' : theme.dark == true }" type="time" v-model="editEndTime" step="900">
                        </div>
                    </div>
                    <div v-if="shift?.overtime_request" style="font-size: 12px;line-height:1.5">
                        <div>※申請した残業時間は<strong>{{shift?.overtime_request.minutes}}分</strong>です。退勤は1分単位で入力してください。</div>
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
                <CustomField 
                    v-for="field in filterCustomValues" 
                    :shift_type="shift?.shift_type" 
                    :data="field"
                    v-model:fieldValue="customValues[field.id]"
                    v-model:vehicle="vehicleData"
                />              
                <div class="si-box" style="display: flex; justify-content: center; gap: 20px;">
                    <LoaderButton style="margin: 0" :loading="loading[0]" content="一時保存" @triggered="saveTimeCard(0)" />
                    <LoaderButton style="margin: 0" :loading="loading[1]" content="申請する" @triggered="saveTimeCard(1)" />
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup>
import { computed, inject, onMounted, ref, reactive, watch } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useTheme } from '@/store/theme';
import CustomField from './CustomField.vue'
import CostField from './CostField.vue';
import IncentiveField from './IncentiveField.vue'
import { useAuthUserStore } from '../../store/auth';
import Modal from '../Global/Modal.vue';
import { DateTime } from 'luxon';
import { customParser } from '@/utils/tools';
    const auth = useAuthUserStore()
    const fields = inject('customInfo')
    const emit = defineEmits(['reload', 'closeModal'])
    const theme = useTheme()
    const workGroups = inject('workGroups')
    
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
    const breakTimeOptions = ref([{label : 'なし' , value : 0 },
                        {label : '30分' , value : 30 },
                        {label : '45分' , value : 45 },
                        {label : '60分' , value : 60 },
                        {label : '90分' , value : 90 }])
    const breakTimeSelect = ref(timeCard.value?.break_time ? timeCard.value.break_time : 0)
    const { confirm, notify, info } = inject('dialog')
    const customValues = ref({})
    const todayWorkGroup = ref(timeCard.value?.work_group_id ? timeCard.value.work_group_id : workGroupAsOptions.value[0]?.id ?? '')
    const costDepartment = computed(() => {
        return workGroupAsOptions.value.find(group => group.id === todayWorkGroup.value)?.name
    })
    watch(todayWorkGroup, (newWorkGroup) => {
        costs.forEach(cost => {
            cost.department = workGroupAsOptions.value.find(group => group.id === newWorkGroup)?.name
        })
    })
    const addCostField = () => {
        if(costs.length >= 10){
            notify('上限は10個です。')
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

    onMounted(() => {
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
        costsFill()
        customFieldFill()
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
        const shiftStartTime = shift.value ? shift.value?.start_time : '09:00:00'
        const shiftEndTime = shift.value ? shift.value?.end_time : '18:00:00'
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
                    notify(message)
                    resolve(false)
                }
                if(index == 44 && v == 1){
                    if(!vehicleConfirm()){
                        resolve(false)
                    }
                }
            })
            resolve(true)
        })
    }
    const vehicleConfirm = () => {
        if (!vehicleData.value) {
            notify('車両の使用に関する情報はありません。');
            return false;
        } else if (vehicleData.value['vehicle'] === null) {
            notify('車両が選択されていません。');
            return false;
        } else if (!vehicleData.value['alcohol_before_time'] || !vehicleData.value['alcohol_after_time']) {
            notify('前後の時間が選択されていません。');
            return false;
        } else if (vehicleData.value['alcohol_before_value'] == null || !vehicleData.value['alcohol_after_value'] == null) {
            notify('前後の値が選択されていません。');
            return false;
        } else if (!vehicleData.value['confirm_before_user'] || !vehicleData.value['confirm_after_user']) {
            notify('前後の確認者が選択されていません。');
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
                resolve(await confirm(`申請した残業時間を超過しています。<strong>${diffInMinutes.value - props.item?.work_time_day}分</strong>で申請しますか`))
                
            }else if(diffInMinutes.value < overtime){
                const workedOverTime = shift.value?.overtime_request.minutes - (overtime - diffInMinutes.value)
                resolve(await confirm(`時間外は<strong>${workedOverTime < 0 ? 0 : workedOverTime}分</strong>になります。よろしいですか。`))               
            } else {
                resolve(await confirm('日報を申請します。申請後は修正できません。よろしいですか。'))
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
                day: props.item?.day_full,
                status_flag: status_flag,
                userId: props.item?.user_id,
                overTimeMinute: shift.value?.overtime_request?.minutes,
                costsValues: costs,
                incentiveValues: incentives.value,
                department: todayWorkGroup.value,
                shiftType: props.item?.shift?.shift_type?.id ?? null,
                vehicleData: vehicleData.value
            }
            resolve(a)
        })
    }
    const saveTimeCard = async(status_flag) => {
        const validate = await showToastIfEmpty()
        if(!validate) return
        if(shift.value?.overtime_request){
            const confirm = await confirmOvertime()
            if(!confirm.value) return            
        } else if(status_flag === 1){
            await fifteenMinuteCalc()
            const answer = await confirm('日報を申請します。申請後は修正できません。よろしいですか。')
            if(!answer.value) return
        }
        loading.value[status_flag] = true
        const params = await buildParams(status_flag)
        try{
            await axios.post('/save_time_card', params)
            info('申請しました。')
            emit('reload')
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')    
        }finally{
            loading.value[status_flag] = false
        }
        
        
    }

</script>