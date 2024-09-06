<template>
    <div class="work-modal" @mousedown="emit('closeModal')">
        <div class="work-modal-inner" @mousedown.stop>
            <div class="recordFormTitle">
                <p style="font-size: 18px;">{{formatedDay}}の日報を作成する</p>
                
                <div @click="emit('closeModal')" class="cursor-pointer" style="display:flex;align-items: center;margin: auto 0 auto auto;">
                    <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
            <div class="report-wrapper" style="background:inherit;">
                <div class="report-field">
                    <p class="report-header">部門を選択</p>
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
                    v-model="customValues[field.id]"
                />               
                <div class="si-box" style="display: flex; justify-content: center; gap: 20px;">
                    <LoaderButton style="margin: 0" :loading="loading[0]" content="一時保存" @triggered="saveTimeCard(0)" />
                    <LoaderButton style="margin: 0" :loading="loading[1]" content="申請する" @triggered="saveTimeCard(1)" />
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
    import { computed, inject, onMounted, ref, reactive, watch } from 'vue';
    import LoaderButton from '../Global/LoaderButton.vue';
    import { useTheme } from '@/store/theme';
    import moment from 'moment';
    import CustomField from './CustomField.vue'
    import CostField from './CostField.vue';
    import IncentiveField from './IncentiveField.vue'
    import { useAuthUserStore } from '../../store/auth';
    const auth = useAuthUserStore()
    const fields = inject('customInfo')
    const emit = defineEmits(['reload'])
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
        const start = moment(shiftStartTime, 'HH:mm:ss')
        const end = moment(shiftEndTime, 'HH:mm:ss')
        return end.diff(start, 'minutes')
    })
    const workedTime = computed(() => {
        const start = moment(editStartTime.value, 'HH:mm:ss')
        const end = moment(editEndTime.value, 'HH:mm:ss')
        return end.diff(start, 'minutes')
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
        const date = new Date(props.item?.day_full)
        return `${date.getMonth() + 1}月${date.getDate()}日`
    })
    
    const showToastIfEmpty = async() => {
        return new Promise ((resolve) => {
            const targets = [39,40,41]
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
            })
            resolve(true)
        })
    }
    const formatTime = (time) => { 
        const timeFormat = /^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/;
        if (timeFormat.test(time)) {
            const parsedTime = moment(time, 'HH:mm:ss');
            return parsedTime.format('HH:mm');
        } else {
            return time;
        }
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
            editEndTime.value = `${adjustedEndHours.toString().padStart(2, "0")}:${String(endnearestMinute).padStart(2, "0")}`;
            
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
        const startInstance = moment(`${today} ${editStartTime.value}`)
        const endInstance = moment(`${today} ${editEndTime.value}`)
        if(endInstance.isBefore(startInstance)){
            endInstance.add(1, 'day')
        }
        const differenceInMinutes = endInstance.diff(startInstance, 'minutes')

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
                shiftType: props.item?.shift?.shift_type?.id ?? null
            }
            resolve(a)
        })
    }
    const saveTimeCard = async(status_flag) => {
        const validate = await showToastIfEmpty()
        if(!validate) return
        if(shift.value?.overtime_request){
            const confirm = await confirmOvertime()
            if(!confirm) return            
        } else if(status_flag === 1){
            await fifteenMinuteCalc()
            const answer = await confirm('日報を申請します。申請後は修正できません。よろしいですか。')
            if(!answer) return
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