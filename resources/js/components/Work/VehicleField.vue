<template>
    <div>
        <div>
            <div class="report-field">
                <p class="report-header">使用車両</p>
                <div class="report-input">
                    <div class="report-input-wrapper">
                        <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" @change="vehicleChange('vehicle', vehicleType)" v-model="vehicleType">
                            <option v-for="vehicle in vehicleAsOptions" :value="vehicle.value">{{ vehicle.label }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="report-field">
                <p class="report-header">アルコールチェックした時間</p>
                <div>
                    <div class="report-input-time">
                        <div>
                            <label style="margin-right:10px;font-size: 14px;">車両使用前</label>
                            <input class="taskDateTimePicker" @change="vehicleChange('alcohol_before_time', alchoholTestBefore)" :class="{'clock-color' : theme.dark == true }" type="time" v-model="alchoholTestBefore" step="900">
                        </div>
                        <div>
                            <label style="margin-right:10px;font-size: 14px;">車両使用後</label>
                            <input class="taskDateTimePicker" @change="vehicleChange('alcohol_after_time', alchoholTestAfter)" :class="{'clock-color' : theme.dark == true }" type="time" v-model="alchoholTestAfter" step="900">
                        </div>
                    </div>
                </div>
            </div>
            <div class="report-field">
                <p class="report-header">アルコールチェックした値</p>
                <div class="report-input-time">
                    <div>
                        <label style="margin-right:10px;font-size: 14px;">車両使用前</label>
                        <input class="value-input" @change="vehicleChange('alcohol_before_value', alchoholTestValueBefore)" type="number" v-model="alchoholTestValueBefore">
                    </div>
                    <div>
                        <label style="margin-right:10px;font-size: 14px;">車両使用後</label>
                        <input class="value-input" @change="vehicleChange('alcohol_after_value', alchoholTestValueAfter)" type="number" v-model="alchoholTestValueAfter">
                    </div>
                </div>
            </div>
            <div class="report-field">
                <p class="report-header">アルコールチェックした確認者</p>
                <div style="display: flex;gap: 15px; padding: 15px 0;">
                    <div style="min-width: 300px;">
                        <MemberSelector 
                            placeHolder="車両使用前"
                            rules="required"
                            v-model="alcoholTestConfirmMember" 
                            :multiple="false"
                            :closeOnSelect="true"
                            path="board_possible_users"
                        />
                    </div>
                    <div style="min-width: 300px;">
                        <MemberSelector
                            placeHolder="車両使用後"
                            rules="required"
                            v-model="alcoholTestConfirmMember2" 
                            :multiple="false"
                            :closeOnSelect="true"
                            path="board_possible_users"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { useTheme } from '@/store/theme';
import { onMounted, ref, watch } from 'vue';
import MemberSelector from '../Form/MemberSelector.vue';
import { User } from '@/interface/globalInterface';
import { vehicleAsOptions } from '@/utils/workApi';
const vehicle = defineModel('vehicle')
const vehicleType = ref(null)
const theme = useTheme()
const alchoholTestBefore = ref(null)
const alchoholTestAfter = ref(null)
const alchoholTestValueBefore = ref(null)
const alchoholTestValueAfter = ref(null)
const alcoholTestConfirmMember = ref<User | null>(null)
const alcoholTestConfirmMember2 = ref<User | null>(null)
onMounted(() => {
    if (vehicle.value && typeof vehicle.value === 'object') {
        vehicleType.value = vehicle.value['vehicle']
        alchoholTestBefore.value = vehicle.value['alcohol_before_time']
        alchoholTestAfter.value = vehicle.value['alcohol_after_time']
        alchoholTestValueBefore.value = vehicle.value['alcohol_before_value']
        alchoholTestValueAfter.value = vehicle.value['alcohol_after_value']
        alcoholTestConfirmMember.value = vehicle.value['before_user']
        alcoholTestConfirmMember2.value = vehicle.value['after_user']
    }
})
const vehicleChange = (type: string, value: any) => {
    if (vehicle.value && typeof vehicle.value === 'object') {
        vehicle.value[type] = value
    }
}
watch([alcoholTestConfirmMember, alcoholTestConfirmMember2], () => {
    vehicleChange('confirm_before_user', alcoholTestConfirmMember.value?.id)
    vehicleChange('confirm_after_user', alcoholTestConfirmMember2.value?.id)
})
</script>
<style scoped>
.report-input-time {
    gap: 15px;
}
.value-input {
    padding: 0px 25px 0 10px; 
    height:38px; 
    width: 50px;
    border:1px solid var(--primary-color);
    color:var(--primary-color);
    font-size: 14px;
}
</style>