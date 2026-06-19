<template>
    <div>
        <div class="report-field">
            <div class="report-input">
                <div class="report-input-wrapper" v-for="(subPart , index) in subParts">
                    <div>
                        <input ref="subPartsRef" :id="subInputId(index)" type="checkbox" :name="subInputName" v-model="value" @change="setSubPart" :value="subPart.parts_value" :disabled="props.locked">
                        <label :for="subInputId(index)">{{ subPart.parts_lavel }}</label>
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
    idPrefix?: string,
    locked?: boolean,
}>()

const value = defineModel<any>()

onMounted(() => {
    if (!Array.isArray(value.value)) {
        value.value = [];
    }

    if(props.subParts.length > 0){
        let includes = false;
        props.subParts.forEach(part => {
            if(Array.isArray(value.value) && value.value.map(Number).includes(Number(part.parts_value))){
                includes = true;
            }
        })
        if(!includes){
            value.value.push(props.subParts[0].parts_value);
        }
    }
})
const subInputName = `sub_allowance_${props.idPrefix ?? 'default'}`
const subInputId = (index: number) => `${subInputName}_${index}`
const setSubPart = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const targetValue = target.value;
    if (!Array.isArray(value.value)) return;

    if(target.checked){
        const otherParts = props.subParts.filter(part => Number(part.parts_value) !== Number(targetValue));
        otherParts.forEach(part => {
            value.value = value.value.filter((v: string | number) => Number(v) !== Number(part.parts_value));
        })
    }
}
</script>
