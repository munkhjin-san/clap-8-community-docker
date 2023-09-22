<template>
    <div>
        <div v-for="(custom_field_record_item , index ) in info">
            <div v-for="(custom_field_type_records_item , index) in custom_field_record_item.custom_field_type_records">
                <div class="customfieldTextAreaLayer-01" v-if="custom_field_type_records_item.form_type == 'textarea'">
                    <div class="customfieldTextAreaLayer-02">
                        <textarea class="recordTextArea" v-on:change="customFieldChoose(comment, custom_field_type_records_item.id)" v-model="comment" name="comment" :placeholder="custom_field_type_records_item.title + 'を入力'"></textarea>
                    </div>
                </div>
                <div v-if="custom_field_type_records_item.form_type == 'radio'">
                    <div class="report-field">
                        <p class="report-header">{{ custom_field_type_records_item.title }}を選択</p>
                        <div class="report-input">
                                <template v-if="custom_field_type_records_item.id == 40">
                                    <div class="report-input-wrapper" v-for="(custom_field_parts_records_item , index) in custom_field_type_records_item.custom_field_parts_records">
                                        <input style="margin-right: 10px !important;" type="radio" name="incident" v-on:change="customFieldChoose(incident, custom_field_type_records_item.id)" v-model="incident" :value="custom_field_parts_records_item.parts_value">
                                        <label>{{ custom_field_parts_records_item.parts_lavel }}</label>
                                    </div>
                                </template>
                                <template v-if="custom_field_type_records_item.id == 41">
                                    <div class="report-input-wrapper" v-for="(custom_field_parts_records_item , index) in custom_field_type_records_item.custom_field_parts_records">
                                        <input style="margin-right: 10px !important;" type="radio" name="achievement" v-on:change="customFieldChoose(achievement, custom_field_type_records_item.id)" v-model="achievement" :value="custom_field_parts_records_item.parts_value">
                                        <label>{{ custom_field_parts_records_item.parts_lavel }}</label>
                                    </div>
                                </template>
                        </div>
                    </div>
                </div>

                <div v-if="custom_field_type_records_item.form_type == 'checkbox'">
                    <div class="report-field">
                        <p class="report-header">{{ custom_field_type_records_item.title }}を選択</p>
                        <div class="report-input">
                            <div class="report-input-wrapper" v-for="(custom_field_parts_records_item , index) in custom_field_type_records_item.custom_field_parts_records">
                                <input style="margin-right: 10px !important;" type="checkbox" name="allowance" v-on:change="customFieldChoose(allowance, custom_field_type_records_item.id)" v-model="allowance" :value="custom_field_parts_records_item.parts_value">
                                <label>{{ custom_field_parts_records_item.parts_lavel }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        props: ['customFieldData', 'info'],
        data() {
            return {
                showTooltip_07: false,
                showTooltip_08: false,
                showTooltip_09: false,
                allowance: [],
                incident: '',
                achievement: '',
                comment: '',
                customFields: []
            }
        },
        methods: {
            setTooltip(visibility,flag) {
                if(flag == 7){
                    this.showTooltip_07 = visibility;
                }
                if(flag == 8){
                    this.showTooltip_08 = visibility;
                }
                if(flag == 9){
                    this.showTooltip_09 = visibility;
                }
            },
            customFieldChoose(value, type_id){
                this.$emit('updateData', {
                    value: value,
                    field_type_id: type_id
                }); 
            }
        },
    
        mounted() {
            this.$nextTick(() => {
                if(this.customFieldData && this.customFieldData.length){
                    for(let field of this.customFieldData){
                        if(field.type_id == 39){
                            this.comment = field.value_text
                        }else if(field.type_id == 40){
                            this.incident = field.value_int
                        }else if(field.type_id == 41){
                            this.achievement = field.value_int
                        }else if(field.length > 0){
                            for(let item of field){
                                this.allowance.push(item.value_int)
                            }
                        }
                    }
                }
            })
        },
    }
</script>