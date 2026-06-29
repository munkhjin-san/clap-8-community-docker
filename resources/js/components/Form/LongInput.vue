<template>
    <div class="relative">
        <div class="grow-wrap" ref="growRef" :class="{focused: modelValue}">
            <textarea 
                :style="{width: `${width - 30}px`, maxWidth:`${width - 30}px`}"
                @input="validate(true, $event)"
                v-model="value" 
                :name="inputName" 
                :disabled="disabled"
                :class="['g-text-long', customClass, {'date-color' : theme.dark }, {'!bg-[var(--bg3)] opacity-80 !cursor-not-allowed' : disabled}]"                
            ></textarea>
            <label v-if="placeHolder" :class="['form-plc', {centerLabel: !modelValue}]">{{placeHolder}}</label> 

        </div>
        <p v-if="error" class="i-error">{{ error }}</p>
    </div> 
</template>
  
<script setup lang="ts">
    import { validator } from '@/validation/validator'
    import { computed, onMounted, ref, useTemplateRef } from 'vue';
    import { useTheme } from '@/store/theme';
    import {  useElementSize } from '@vueuse/core'
    const growRef = useTemplateRef('growRef')
    const { width } = useElementSize(growRef)
    const theme = useTheme()
    const error = ref('')
    const trigger = ref(false)
    const props = withDefaults(defineProps<{
        name?: string
        placeHolder?: string
        rules?: string
        customClass?: string
        initialValue?: string
        disabled?: boolean
    }>(), {
        name: '',
        placeHolder: '',
        rules: '',
        customClass: '',
        initialValue: '',
        disabled: false,
    })
    const inputName = computed(() => props.name ? props.name : `long-input-${Math.random().toString(36).substring(2, 15)}`)
    const value = defineModel<any>()
    onMounted(() => {
        updateTarget()
    })
    const updateTarget = () => {
        if(props.initialValue){
            value.value = props.initialValue
        }    
        if(!growRef.value) return   
        growRef.value.dataset.replicatedValue = value.value || props.initialValue
    }
    const validate = async(passive?: boolean, event?: Event) => {
        if(event && growRef.value){
            const target = event.target as HTMLTextAreaElement
            growRef.value.dataset.replicatedValue = target.value
        }
        if(passive && !trigger.value) {
            return {valid: true}
        }       
        if(!props.rules) {
            return {valid: true}
        }         
        const { isValid, errorMessage }= await validator(props.rules, value.value)
        if(errorMessage){
            error.value = errorMessage
        }else {
            error.value = ''
        }
        
        trigger.value = true
        return {valid: isValid}
    }    
    defineExpose({validate})
</script>
<style scoped>
.height-adjust {
    min-height: 40px !important;
}
.centerLabel{
    top: 50%;
    transform: translateY(-50%);
}
.g-text-long{
    color: inherit;
    font-size: 16px;
    min-height: 80px;
    display: inline-block;
    white-space: pre-wrap;
}
textarea:focus + label{
    font-size: 11px;
    top: 15px;
    left: 15px;
    color: var(--primary-color);
    transform: translateY(-50%);
}
.grow-wrap {
    display: grid;
    line-height: 1.6;
    border: 1px solid var(--primary-color);
    padding: 25px 0 20px;
    transition: border 0.3s ease;
    max-width: 100%;
    position: relative;

}
.grow-wrap::after {
    content: attr(data-replicated-value) " ";
    white-space: pre-wrap;
    visibility: hidden;
}
.grow-wrap > textarea {
    resize: none;
    overflow: hidden;
    background: inherit;
}
.grow-wrap > textarea,
.grow-wrap::after {
    font: inherit;
    grid-area: 1 / 1 / 2 / 2;
    max-width: calc(100% - 30px);
    word-break: break-word;
    margin-left: 15px;
}


@media screen and (max-width: 959px) {
    .g-text {
        font-size: 14px;
    }
}
</style>