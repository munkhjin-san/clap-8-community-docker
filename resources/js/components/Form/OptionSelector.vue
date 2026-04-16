<template>
    <div class="locale-selector" style="width: fit-content;position:relative;height:40px;">

        <select 
            class="optionPicker"
            :class="[{'date-color' : theme.dark }, { 'cursor-not-allowed opacity-60': disabled, 'cursor-pointer': !disabled }]" 
            :name="name" 
            :rules="rules" 
            v-model="value"
            :disabled="disabled"
            style="height: 40px; font-size: 14px; border: solid 1px var(--primary-color);"
        >
        <option :value="option" v-for="option in options" v-html="`${option}${unit}`"></option>
        </select>
        <p v-if="error" class="i-error" style="margin: 0;bottom: -20px;">{{ error }}</p>       
    </div>   
</template>
<script setup>
import { validator } from '@/validation/validator'
import { useTheme } from '@/store/theme';
import { ref } from 'vue';
    const theme = useTheme()
    const props = defineProps({
        name: String,
        rules: String,
        options: null,
        unit: String,
        disabled: Boolean,
    })
    const value = defineModel()
    const error = ref('')
    const trigger = ref(false)
    const validate = async(passive) => {
        if(passive && !trigger.value) return

        const { isValid, errorMessage }= await validator(props.rules, value.value)
        error.value = errorMessage
        trigger.value = true
        return {valid: isValid}
    }   
    defineExpose({validate})  
    
</script>
