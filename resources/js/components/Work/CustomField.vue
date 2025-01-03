<template>
    <div>        
        <div v-if="data.form_type == 'textarea'" style="background: var(--background-color);margin-bottom: 20px;">
            <LongInput
                ref="commentRef"
                :placeHolder="data.title"
                name="commentRef"
                v-model="value"
            />               
        </div>
        <div v-if="data.form_type == 'radio'">
            <div class="report-field">
                <p class="report-header">{{ data.title }}</p>
                <div class="report-input">
                    <div class="report-input-wrapper" v-for="(customPart , index) in data.custom_field_parts_records">
                        <input :id="'workRadio' + customPart.id" type="radio" :name="data.title" v-model="value" :value="customPart.parts_value">
                        <label :for="'workRadio' + customPart.id">{{ customPart.parts_lavel }}</label>
                    </div>
                </div>
            </div>
        </div>
        <VehicleField 
            v-if="data.id === 43 && value == 1"
            v-model:vehicle="vehicle"
        />
        <div v-if="data.form_type == 'checkbox'">
            <div class="report-field">
                <p class="report-header">{{ data.title }}</p>
                <div class="report-input">
                    <div class="report-input-wrapper" v-for="(customPart , index) in data.custom_field_parts_records">
                        <div v-if="customPart.parts_value != 2 || shift_type?.id == 0">
                            <input :id="'workAllowance' + index" type="checkbox" name="allowance" v-model="value" :value="customPart.parts_value">
                            <label :for="'workAllowance' + index">{{ customPart.parts_lavel }}</label> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</template>
<script setup>
import LongInput from '../Form/LongInput.vue';    
import VehicleField from './VehicleField.vue';
    const props = defineProps(['data', 'shift_type'])
    const value = defineModel('fieldValue') 
    const vehicle = defineModel('vehicle')
</script>