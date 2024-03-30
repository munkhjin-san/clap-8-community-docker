<template>
    <div>
        <div v-for="(customType , index) in customInfo" :key="index">
            <div v-if="customType.form_type == 'textarea'" style="background: var(--background-color);">
         
                    <!-- <textarea 
                        class="recordTextArea" 
                        @change="customFieldChoose(comment, customType.id)" 
                        v-model="comment" 
                        name="comment" 
                        :placeholder="customType.title + 'を入力'"
                    >
                    </textarea> -->

                    <LongInput
                        ref="commentRef"
                        :placeHolder="customType.title + 'を入力'"
                        name="commentRef"
                        v-model="comment"
                    />   
               
            </div>
            <div v-if="customType.form_type == 'radio'">
                <div class="report-field">
                    <p class="report-header">{{ customType.title }}を選択</p>
                    <div class="report-input">
                            <template v-if="customType.id == 40">
                                <div class="report-input-wrapper" v-for="(customPart , index) in customType.custom_field_parts_records">
                                    <input :id="'workIncident' + index" type="radio" name="incident" @change="customFieldChoose(incident, customType.id)" v-model="incident" :value="customPart.parts_value">
                                    <label :for="'workIncident' + index">{{ customPart.parts_lavel }}</label>
                                </div>
                            </template>
                            <template v-if="customType.id == 41">
                                <div class="report-input-wrapper" v-for="(customPart , index) in customType.custom_field_parts_records">
                                    <input :id="'workAchievement' + index" type="radio" name="achievement" @change="customFieldChoose(achievement, customType.id)" v-model="achievement" :value="customPart.parts_value">
                                    <label :for="'workAchievement' + index">{{ customPart.parts_lavel }}</label>
                                </div>
                            </template>
                    </div>
                </div>
            </div>

            <div v-if="customType.form_type == 'checkbox'">
                <div class="report-field">
                    <p class="report-header">{{ customType.title }}を選択</p>
                    <div class="report-input">
                        <div class="report-input-wrapper" v-for="(customPart , index) in customType.custom_field_parts_records">
                            <div v-if="customPart.parts_value != 2 || chosenDateShiftType?.id == 0">
                                <input :id="'workAllowance' + index" type="checkbox" name="allowance" @change="customFieldChoose(allowance, customType.id)" v-model="allowance" :value="customPart.parts_value">
                                <label :for="'workAllowance' + index">{{ customPart.parts_lavel }}</label> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">

    import { inject, nextTick, onMounted, ref } from 'vue';
    import { CustomFieldData, CustomInfoType, ChosenDateShiftType } from '../../interface/workInterface';
    import LongInput from '../Form/LongInput.vue';
    const emit = defineEmits(['updateData'])
    interface Props {
        customFieldData: CustomFieldData[]
        chosenDateShiftType: ChosenDateShiftType
    }
    
    const props = defineProps<Props>()
    const customInfo = inject<CustomInfoType>('customInfo')
    const allowance = ref([] as number[]);   
    const incident = ref<number>()
    const achievement = ref<number>()
    const comment = ref('')
      
    const customFieldChoose = (value: string | number | number[] | undefined, type_id: number) => {
        emit('updateData', {
            value: value,
            field_type_id: type_id
        }); 
    }
    const handleCustomFieldData = (props: Props) => {
            for (const field of props.customFieldData) {
                switch (field.type_id) {    
                    case 39:
                        comment.value = field.value_text;
                        break;
                    case 40:
                        if (typeof field.value_int === 'number') {
                            incident.value = field.value_int;
                        }
                        break;
                    case 41:
                        if (typeof field.value_int === 'number') {
                            achievement.value = field.value_int;
                        }
                        break;
                    case 37:
                        // if (Array.isArray(field)) {
                            // console.log
                            // for (const item of field) {
                            //     if (typeof item.value_int === 'number') {
                                    console.log('allowabce')
                                    allowance.value.push(field.value_int);
                                    break;
                            //     }
                            // }
                        // }
                    default: break;
                }
                   
            }
        
    }
    onMounted(() => {
        nextTick(() => {
            handleCustomFieldData(props)
        })
    })
</script>