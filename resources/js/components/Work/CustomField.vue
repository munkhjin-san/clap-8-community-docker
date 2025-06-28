<template>
    <div>        
        <div class="relative" v-if="data.form_type == 'textarea'" style="background: var(--background-color);margin-bottom: 20px;">
            <LongInput
                ref="commentRef"
                :key="textAreaKey"
                :placeHolder="data.title"
                name="commentRef"
                v-model="value"
            />      
            <div class="absolute right-[3px] top-[3px]">
                <div class="relative">
                    <div title="下書き保存" @click.stop="menu.setMenu({parent: 'temp_comment'})" class="h-[30px] w-[30px] min-w-[30px] flex items-center justify-center rounded-full bg-[var(--background-color)] hover:bg-[var(--bg3)] cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 29.89 29.93" width="15" height="15" style="fill: var(--third-color);">
                            <path class="cls-1" d="M23.03,16.57s-.11,7.77-.14,10.34c0,.21-.18.38-.39.38-4.93-.09-10.68-.05-15.43-.06h-3.94c-.21.01-.39-.16-.39-.38.06-6.23-.01-13.1-.05-19.34,0-.21.17-.38.38-.39,3.27-.04,7.38-.11,10.32-.17,1.45-.08,1.45-2.14,0-2.23-1.97-.03-7.1-.13-9-.16-1,0-2-.03-3-.03-.71,0-1.3.58-1.3,1.29,0,0-.03,5.69-.04,5.7,0,5.29-.1,11.83,0,17.08.02.72.61,1.3,1.33,1.31,2.83.02,8.54.01,11.37.01,3.35-.03,8.13.03,11.43-.1.67-.02,1.21-.58,1.2-1.25l-.17-12.01c-.08-1.42-2.1-1.42-2.18,0Z"></path><path class="cls-1" d="M29.56,5.67L24.23.33c-.44-.44-1.16-.44-1.61,0-.03.03-.05.06-.08.09-2,1.78-4.63,4.51-6.56,6.37-1.64,1.62-4.92,4.91-6.55,6.54-.13.13-.24.31-.3.5l-2.34,7.79c-.07.23-.07.48,0,.72.2.65.89,1.01,1.54.81l7.78-2.39c.18-.05.34-.15.49-.29,3.16-3.1,6.68-6.66,9.81-9.83,1.08-1.1,2.15-2.21,3.17-3.37.07-.08.13-.17.17-.26.24-.43.18-.99-.19-1.36ZM10.43,17.93c.3-1.04.58-2.04.77-2.69.05-.18.27-.24.41-.11.71.71,2.54,2.54,3.25,3.25.13.13.07.36-.11.41-.55.15-1.54.43-2.71.76-.35.1-.72,0-.97-.25l-.39-.39c-.26-.26-.35-.63-.25-.98ZM21.47,12.28c-1.44,1.45-2.96,2.99-4.45,4.51-.15.15-.4.15-.55,0l-3.31-3.31c-.15-.15-.15-.39,0-.54,3.26-3.26,6.77-6.73,9.87-10.03.15-.16.4-.16.55,0l3.39,3.39c.15.15.15.4,0,.55-1.81,1.71-3.91,3.87-5.49,5.44Z"></path>
                        </svg>                        
                    </div>
                    <Transition nam="modalFade">
                        <div id="temp_comment" v-if="menu.parent == 'temp_comment'" class="absolute top-[25px] right-[0] z-[7] shadow-me whitespace-nowrap bg-[var(--background-color)]">
                           <div @click="saveComment" class="flex items-center gap-[5px] cursor-pointer p-[10px]">
                                <AddIcon size="12" />
                                <p class="text-[var(--primary-color)] text-[12px] ml-[5px]">下書保存</p>
                           </div>
                           <div v-if="tempComments.length > 0" class="flex flex-col gap-[5px] mt-[5px]">
                                <div v-for="(comment, index) in tempComments" :key="index" class="flex items-center gap-[5px] cursor-pointer justify-between leading-normal hover:bg-[var(--bg3)]  p-[10px]">                                    
                                    <p @click="setText(comment)" class="text-[var(--primary-color)] text-[12px] max-w-[100px] overflow-hidden text-ellipsis">{{ comment }}</p>
                                    <Trash size="12" @click.stop="deleteComment(index)" />
                                </div>

                           </div>
                        </div>
                    </Transition>
                </div>
            </div>         
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
            v-if="data.id === 44 && value == 1"
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
        <div v-if="subParts && subParts.length">
            <div class="report-field">
                <div class="report-input">
                    <div class="report-input-wrapper" v-for="(subPart , index) in subParts">
                        <div>
                            <input ref="subPartsRef" :id="'workSub' + index" type="checkbox" name="sub_allowance" v-model="value" @change="setSubPart" :value="subPart.parts_value">
                            <label :for="'workSub' + index">{{ subPart.parts_lavel }}</label> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
    </div>

</template>
<script setup>
import { useMenuStore } from '@/store/menu';
import LongInput from '../Form/LongInput.vue';    
import Trash from '../Icons/Trash.vue';
import VehicleField from './VehicleField.vue';
import AddIcon from '../Form/AddIcon.vue';
import { onMounted, ref, computed, useTemplateRef } from 'vue';
import { useDialog } from '@/composables/dialog';
    const props = defineProps(['data', 'shift_type'])
    const value = defineModel('fieldValue') 
    const vehicle = defineModel('vehicle')
    const menu = useMenuStore()
    const tempComments = ref([])
    const textAreaKey = ref(0)
    const subPartsRef = useTemplateRef('subPartsRef')
    const { ping } = useDialog()
    onMounted(() => {
        getTempComments()
    })
    const getTempComments = async () => {
        const data = localStorage.getItem('temp_comments')
        if(data){
            tempComments.value = JSON.parse(data)
        }
    }
    const deleteComment = (index) => {
        tempComments.value.splice(index, 1)
        localStorage.setItem('temp_comments', JSON.stringify(tempComments.value))
        getTempComments()
    }
    const saveComment = () => {
        if(value.value){
            tempComments.value.push(value.value)
            localStorage.setItem('temp_comments', JSON.stringify(tempComments.value))
            getTempComments()
        }else{
            ping('下書き保存する内容がありません')
        }
    }
    const setText = (text) => {
        value.value = text
        menu.close()
        textAreaKey.value += 1
    }
    const subParts = computed(() => {
        const type = props.data.form_type;
        const parts = props.data.custom_field_parts_records;
        if(type === 'radio'){
            const selectedPart = parts.find(part => part.parts_value === value.value);
            return selectedPart ? selectedPart.sub_parts : [];
        }else if(type === 'checkbox'){
            const sub_parts = parts.filter(part => Array.isArray(value.value) && value.value.includes(part.parts_value));
            return sub_parts.map(part => part.sub_parts).flat();
        }
    })
    const setSubPart = (event) => {
        const targetValue = event.target.value;
        if(event.target.checked){
            const otherParts = subParts.value.filter(part => part.parts_value !== targetValue);
            console.log(otherParts);
            otherParts.forEach(part => {
                value.value = value.value.filter(v => v !== part.parts_value);
            })
        }
    }
    const subPartsChecked = computed(() => {
        const parts = props.data.custom_field_parts_records;
        if(!parts || !Array.isArray(value.value)) return true;
        let result = true;
        parts.forEach(part => {
            const subParts = part.sub_parts || [];
            if(!subParts.length || !value.value.includes(part.parts_value)) {
                result = result && true;
            }else{
                const valuesList = subParts.map(subPart => subPart.parts_value);
                const includes = value.value.filter(v => valuesList.includes(v));
                const valid = includes.length ? true : false;
                result = result && valid;
            }         
        });
        return result;        
    })

    defineExpose({
        subPartsChecked
    })
</script>