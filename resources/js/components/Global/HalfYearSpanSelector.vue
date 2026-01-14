<template>
    <div class="flex items-center gap-2 relative">
        <div @click="shiftSpan(-1)" class="w-[30px] min-w-[30px] cursor-pointer flex items-center justify-center">
            <Back />
        </div>
        <!-- <select :key="spanKey" name="spanSelector" v-model="selectedSpan" @change="emit('changed')" class="text-[var(--primary-color)] border border-solid border-gray-300 px-2 py-1">
            <option v-for="option in halfYearOptions" :key="`${option.year}-${option.half}`" :value="`${option.year}-${option.half}`">{{ option.label }}</option>
        </select> -->
          <v-select
            v-model="selectedSpan"
            :hint="hint"
            :items="halfYearOptions"
            item-title="label"
            item-value="value"
            label="Select"
            density="compact"
            persistent-hint
            single-line
            class="half-span-picker"
            flat
            tile
            @update:modelValue="(val) => { selectedSpan = val; emit('changed') }"
        >
        <template #item="{ item, props }">
            <v-list-item v-bind="props"  rounded="0" density="compact" :ripple="false" variant="flat">
                <template #default>
                    <div class="flex flex-col">
                        <div class="text-[gray] text-[11px]">{{ item.raw.span }}</div>
                    </div>
                </template>
            </v-list-item> 
        </template>
        </v-select>
            <div @click="shiftSpan(1)" class="w-[30px] min-w-[30px] cursor-pointer flex items-center justify-center" >
            <Back class="rotate-180"/>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import Back from '../Icons/Back.vue';
import { DateTime } from 'luxon';

const emit = defineEmits<{
    changed: []
}>()

const halfYearOptions = ref<{
    year: number;
    half: 'first' | 'second';
    value: string;
    label: string;
    span: string
}[]>([])

const selectedSpan = defineModel<string>();

const spanKey = ref(0);


onMounted(() => {
    buildSpan();
})
const hint = computed(() => {
    if (!selectedSpan.value) return '';
    const [yearStr, half] = selectedSpan.value.split('-');
    const year = Number(yearStr);
    return half === 'first' ? `${year}/4/1 ~ ${year}/9/30` : `${year}/10/1 ~ ${year + 1}/3/31`;
})
const buildSpan = async() => {
    const currentYear = new Date().getFullYear();
    for(let year = currentYear - 1; year <= currentYear + 1; year++){
        halfYearOptions.value.push({ year, half: 'first', label: `${year}年上期`, value: `${year}-first`, span: `${year}/4/1 ~ ${year}/9/30` });
        halfYearOptions.value.push({ year, half: 'second', label: `${year}年下期`, value: `${year}-second`, span: `${year}/10/1 ~ ${year + 1}/3/31` });
    }
    if (!selectedSpan.value) {
        const today = DateTime.now();
        selectedSpan.value = today.month >= 4 && today.month <= 9
        ? `${today.year}-first`
        : `${today.month >= 10 ? today.year : today.year - 1}-second`;
    }
}
const shiftSpan = (direction: number) => {
    if (!selectedSpan.value) return;
    const [yearStr, half] = selectedSpan.value.split('-');
    let year = Number(yearStr);
    const currentSelectionIndex = halfYearOptions.value.findIndex(option => option.year === year && option.half === half);
    if (currentSelectionIndex === -1) return;
    let newIndex = currentSelectionIndex + direction;
    if (newIndex < 0) {
        newIndex = 0;
    } else if (newIndex >= halfYearOptions.value.length) {
        newIndex = halfYearOptions.value.length - 1;
    }
    if(newIndex === currentSelectionIndex) return; // no change
    const newSelection = halfYearOptions.value[newIndex];
    selectedSpan.value = `${newSelection.year}-${newSelection.half}`;
    spanKey.value += 1; 
    emit('changed');
}
</script>
<style>
.half-span-picker .v-field__input {
    padding: 0px !important;
    font-size: 14px !important;
}
.half-span-picker .v-field__append-inner{
    display: none;
}
.half-span-picker .v-field--appended {
    padding-inline-end: 0px !important;
}
.half-span-picker .v-input__details {
    position: absolute;
    white-space: nowrap;
    bottom: -7px;
    padding: 0;
    padding-inline: 0;
    font-size: 10px;
    left: 0;
    right: 0;
    width: fit-content;
    margin: auto;
    min-height: auto;
}
</style>
