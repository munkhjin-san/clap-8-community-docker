<template>
    <div>        
        <div v-if="data.form_type == 'textarea'" style="background: var(--background-color);">
            <LongInput
                ref="commentRef"
                :placeHolder="data.title + 'を入力'"
                name="commentRef"
                v-model="value"
            />               
        </div>
        <div v-if="data.form_type == 'radio'">
            <div class="report-field">
                <p class="report-header">{{ data.title }}を選択</p>
                <div class="report-input">
                        <template v-if="data.id == 40">
                            <div class="report-input-wrapper" v-for="(customPart , index) in data.custom_field_parts_records">
                                <input :id="'workIncident' + index" type="radio" name="incident" v-model="value" :value="customPart.parts_value">
                                <label :for="'workIncident' + index">{{ customPart.parts_lavel }}</label>
                            </div>
                        </template>
                        <template v-if="data.id == 41">
                            <div class="report-input-wrapper" v-for="(customPart , index) in data.custom_field_parts_records">
                                <input :id="'workAchievement' + index" type="radio" name="achievement" v-model="value" :value="customPart.parts_value">
                                <label :for="'workAchievement' + index">{{ customPart.parts_lavel }}</label>
                            </div>
                        </template>
                </div>
            </div>
        </div>

        <div v-if="data.form_type == 'checkbox'">
            <div class="report-field">
                <p class="report-header">{{ data.title }}を選択</p>
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
    const props = defineProps(['data', 'shift_type'])
    const value = defineModel() 
</script>