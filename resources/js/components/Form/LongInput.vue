<template>
    <div style="position: relative;background:inherit">
        <div style="background:inherit">
            <span v-if="placeHolder" class="form-plc smallPlc">{{placeHolder}}</span> 
            <textarea 
                @input="validate(true)"
                v-model="value" 
                :name="name" 
                :class="['g-text-long', customClass, {'date-color' : theme.dark }]"                 
            ></textarea>
            <p v-if="error" class="i-error">{{ error }}</p>
        </div>
    </div> 
</template>
  
<script setup>
    import { validator } from '@/validation/validator'
    import { ref } from 'vue';
    import { useTheme } from '@/store/theme';
    const theme = useTheme()
    const error = ref('')
    const trigger = ref(false)
    const props = defineProps({
        name: String,
        placeHolder: String, 
        rules: String,
        customClass: String,
        modelValue: String,
    })
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
.g-text-long{
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
    padding: 20px 10px 10px 15px;
    min-height: 150px;
    display: inline-block;
    overflow: auto;
    resize: vertical;
    border-radius: 0;
}

@media screen and (max-width: 959px) {
    .g-text {
        font-size: 14px;
    }
}
</style>