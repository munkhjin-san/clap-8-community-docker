<template>
    <div>
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
</template>
<script setup lang="ts">
import { onMounted } from 'vue';

const props = defineProps<{
    subParts: any[],
}>()

const value = defineModel<any>()

onMounted(() => {
// simulate event and pass to setsubpart of first elemenet
    if(props.subParts.length > 0){
        const event = {
            target: {
                value: props.subParts[0].parts_value,
                checked: true
            }
        }
        if(!value.value.includes(props.subParts[0].parts_value)){
            value.value.push(props.subParts[0].parts_value);
        }
        setSubPart(event);
    }
})
const setSubPart = (event) => {
    console.log('setSubPart', event);
    const targetValue = event.target.value;
    if(event.target.checked){
        const otherParts = props.subParts.filter(part => part.parts_value !== targetValue);
        otherParts.forEach(part => {
            value.value = value.value.filter(v => v !== part.parts_value);
        })
    }
}
</script>