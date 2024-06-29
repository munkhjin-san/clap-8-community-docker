<template>
    <Transition name="modalFade">
        <div class="overlay" @mousedown="emit('close', false)">
            <div class="chatCreate scrollable" @mousedown.stop>     
                <div class="recordFormTitle" style="display:flex">
                    <p><strong>{{day}}</strong>残業申請</p>
                    
                    <div class="m-close-button" style="position:unset; margin:auto 0 auto auto;width:auto;gap:30px">
                        <button v-if="target" @click="deleteRequest" class="workRecords-button">削除</button>
                        <svg @click="emit('close', false)"  version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>                        
                    </div> 
                </div>

                <div v-if="target" style="padding-bottom: 20px;">
                    ステータス：<strong>{{ statuses[target.status] }}</strong>
                </div>
                <div class="si-box" style="background: var(--bg3);padding: 15px;margin-top: 0;">
                    <p class="report-header">申請前の就業時間</p>
                    <div class="report-time">
                        <div class="timePreview">{{ timeParser(data?.shift?.start_time) }}</div>
                        <div class="between-line"> ～ </div>
                        <div class="timePreview">{{ timeParser(data?.shift?.end_time) }}</div>
                    </div>
                </div>
                <div class="si-box" style="width: fit-content;">
                    <p class="report-header">残業時間</p>
                    <div style="display: flex;gap: 10px;margin-top: 20px;">
                        <OptionSelector 
                            :options="avialAbleHours"
                            unit="時間"
                            ref="overTimeHour"
                            name="overTimeHour"
                            rules="required"
                            v-model="overtime.hours"
                        />
                        <OptionSelector
                            :options="avialAbleMinutes"
                            unit="分"
                            ref="overTimeMinute"
                            name="overTimeMinute"
                            rules="required"
                            v-model="overtime.minutes"
                        />
                    </div>
                </div>
                <div class="si-box">
                    <LongInput
                        v-model="remarks"  
                        ref="overtimeContent"
                        :placeHolder="`作業内容`"
                        name="overtimeContent"
                        rules="required|max:2000"
                    />  
                </div>
                <div class="si-box">
                    <LoaderButton @triggered="send" :loading="loading" content="申請する"/>
                </div>  
            </div>
        </div>
    </Transition>
</template>
<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import moment from 'moment';
import OptionSelector from '../Form/OptionSelector.vue';
import LongInput from '../Form/LongInput.vue';
import LoaderButton from '../Global/LoaderButton.vue'
import { useAuthUserStore } from '@/store/auth';
const statuses = ['差戻中', '申請中', '承認済']
const auth = useAuthUserStore()
const props = defineProps(['data'])
const emit = defineEmits(['close'])
const remarks = ref('')
const loading = ref(false)
const overTimeHour = ref(null)
const overTimeMinute = ref(null)
const overtimeContent = ref(null)
const { info, notify, confirm } = inject('dialog')
const overtime = ref({
    hours: 0,
    minutes: 0
})

const fetchShiftDataTable = inject('fetchShiftDataTable')
onMounted(() => {
    if(target.value){
        remarks.value = target.value.content
        overtime.value.hours = Math.floor(target.value.minutes / 60);
        overtime.value.minutes = target.value.minutes % 60;
    }
})

const target = computed(() => {
    return props.data?.shift?.overtime_request
})
const validateTargets = computed(() => {
    return [overTimeHour.value, overTimeMinute.value, overtimeContent.value]
})
const avialAbleHours =  Array.from({ length: 11 }, (_, index) => index)
const avialAbleMinutes = [0, 15, 30, 45]

const day = computed(() => {
    return props.data?.shift ? moment(props.data.shift.shift_day).format('M月D日') : ''
})
const timeParser = (time) => {
    if(!time) return 
    const shift = props.data?.shift
    const combined = moment(`${shift.shift_day} ${time}`)
    return combined.format('M月D日 HH:mm')
}


const send = async() => {
    const targets = validateTargets.value.filter(ob => ob !== null)
    let result = true
    for(const target of targets){        
        const val = await target?.validate() || false
        result = result * val.valid
    }
    if (!result) return
    const confirmed = target.value && target.value.status == 2 ? await confirm('「承認済み」の残業時間を編集すると、ステータスが「申請中」に戻ります。よろしいでしょうか。') : true
    if (!confirmed) return
    const minutes = overtime.value.hours * 60 + overtime.value.minutes
    if(!minutes){
        notify('残業時間は必須です')
        return
    }
    const params = {
        record_id: props.data.shift.id,
        content: remarks.value,
        minutes: minutes,        
        created_by: auth.activeUser.id,
        status: 1,
        overtime_day: props.data.shift.shift_day
    }
    loading.value = true
    try{
        await axios.post('/request_overtime', params).then(res => res.data)
        await fetchShiftDataTable()
        info('申請しました。')
        emit('close', true)
    } catch (e) { 
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        loading.value = false
    }
    
}
const deleteRequest = async() => {
    const answer = await confirm('残業申請を削除しますか。')
    if(!answer) return
    try{
        await axios.delete(`/request_overtime?id=${target.value.id}`).then(res => res.data)
        await fetchShiftDataTable()
        info('削除しました。')
        emit('close', true)
    } catch (e) { 
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        loading.value = false
    }
}
</script>