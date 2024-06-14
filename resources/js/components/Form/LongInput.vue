<template>
    <div style="position: relative;background:inherit">
        <div style="background:inherit" class="grow-wrap" ref="growRef">
            <span v-if="placeHolder" class="form-plc smallPlc">{{placeHolder}}</span> 
            <textarea 
                :style="{width: `${width - 30}px`, maxWidth:`${width - 30}px`}"
                @input="validate(true, $event)"
                v-model="value" 
                :name="name" 
                :class="['g-text-long', customClass, {'date-color' : theme.dark }]"                 
            ></textarea>
        </div>
        <p v-if="error" class="i-error">{{ error }}</p>
        {{ size }}
    </div> 
</template>
  
<script setup>
    import { validator } from '@/validation/validator'
    import { onMounted, ref } from 'vue';
    import { useTheme } from '@/store/theme';
    import { useElementSize } from '@vueuse/core'
    const growRef = ref(null)
    const {width} = useElementSize(growRef)
    const theme = useTheme()
    const error = ref('')
    const trigger = ref(false)
    
    const props = defineProps({
        name: String,
        placeHolder: String, 
        rules: String,
        customClass: String,
        modelValue: String,
        initialValue: String
    })
    const value = defineModel()
    onMounted(() => {
        updateTarget()
    })
    const updateTarget = () => {
        if(props.initialValue){
            value.value = props.initialValue
        }        
        growRef.value.dataset.replicatedValue = value.value || props.initialValue
    }
    const validate = async(passive, event) => {
        if(event){
            event.target.parentNode.dataset.replicatedValue = event.target.value
        }

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
    color: inherit;
    font-size: 16px;
    min-height: 150px;
    display: inline-block;
}
.grow-wrap {
  display: grid;
  line-height: 1.6;
  border: 1px solid var(--primary-color);
  padding: 20px 0;
  transition: border 0.3s ease;
  max-width: 100%;
}
.grow-wrap::after {
  content: attr(data-replicated-value) " ";
  white-space: pre-wrap;
  visibility: hidden;
}
.grow-wrap > textarea {
  resize: none;
  overflow: hidden;
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