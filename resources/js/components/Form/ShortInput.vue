<template>
    <div>
        <div class="form-wrapper" :class="{focused: (modelValue !== '' && modelValue !== null && modelValue !== undefined) || type === 'date'}"> 
             
            <input 
                @input="validate(true)"
                v-model="value" 
                :disabled="disabled ? true : false"
                :name="name" 
                :type="type" 
                :max="max ? max : ''"
                :min="min ? min : ''"
                :step="step ? step : ''"
                :class="['g-text', customClass, {'date-color' : theme.dark }, {'!bg-[var(--bg3)] opacity-80 !cursor-not-allowed' : disabled}]"   
                :style="customStyle"              
            />
            <label v-if="placeHolder" class="form-plc">{{placeHolder}}</label>
            
        </div>
        <p v-if="error" class="i-error">{{ error }}</p>
    </div> 
</template>
  
<script setup>
    import { validator } from '@/validation/validator'
    import { onMounted, ref } from 'vue';
    import { useTheme } from '@/store/theme';
    const error = ref('')
    const trigger = ref(false)
    const props = defineProps({
        name: String,
        placeHolder: String, 
        rules: String,
        type: String,
        customClass: String,
        customStyle: Object,
        initialValue: String,
        max: String,
        min: String,
        step: String,
        disabled: Boolean,
    })
    onMounted(() => {
        if(props.initialValue){
            value.value = props.initialValue
        }
    })
    const theme = useTheme()
    const value = defineModel()
    const validate = async(passive) => {
        if(passive && !trigger.value) return
        const { isValid, errorMessage }= await validator(props.rules, value.value)
        error.value = errorMessage
        trigger.value = true
        return {valid: isValid}
    }    
    defineExpose({validate})
</script>
<style scoped>

input:focus + label{
    font-size: 11px;
    top: 15px;
    left: 15px;
    color: var(--primary-color);
    transform: translateY(-50%);
}
.g-text{
    width: -webkit-fill-available;
    margin: 0 auto;
    padding: 0px;
    border: 1px solid var(--primary-color);
    padding: 10px;
    color: inherit;
    width: -moz-available;
    font-size: 16px;
    line-height: 1.6;
    transition: border 0.3s ease;
    padding: 25px 10px 10px 15px;
}
.full{
    width: -webkit-fill-available;
}
.fit{
    width: fit-content;
}
.date{
    padding: 0 15px;
    min-height: 40px;
    text-align: center;
    font-size: 14px;
}
.minimal{
    padding: 10px 15px;
}

@media screen and (max-width: 959px) {
    .date{
        padding: 0 5px;
    }
    .g-text {
        font-size: 14px;
    }
    input[type="date"]{
        min-width: 100px;
    }
    input[type="time"]{
        min-width: 100px;
    }
}
</style>