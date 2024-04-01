<template>
    <div style="position: relative;background:inherit">
        <div style="background:inherit">
            <span v-if="placeHolder" class="form-plc smallPlc">{{placeHolder}}</span> 
            <input 
                @input="validate(true)"
                v-model="value" 
                :name="name" 
                :type="type" 
                :class="['g-text', customClass, {'date-color' : theme.dark }]"   
                :style="customStyle"              
            />
            <p v-if="error" class="i-error">{{ error }}</p>
        </div>
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
        modelValue: String,
        type: String,
        customClass: String,
        customStyle: String,
        initialValue: String
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
    padding: 20px 10px 10px 15px;
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

@media screen and (max-width: 959px) {
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