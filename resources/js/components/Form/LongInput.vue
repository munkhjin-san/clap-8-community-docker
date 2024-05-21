<template>
    <div style="position: relative;background:inherit">
        <div style="background:inherit" class="grow-wrap" ref="growRef">
            <span v-if="placeHolder" class="form-plc smallPlc">{{placeHolder}}</span> 
            <textarea 
                @input="validate(true, $event)"
                v-model="value" 
                :name="name" 
                :class="['g-text-long', customClass, {'date-color' : theme.dark }]"                 
            ></textarea>
        </div>
        <p v-if="error" class="i-error">{{ error }}</p>
    </div> 
</template>
  
<script setup>
    import { validator } from '@/validation/validator'
    import { onMounted, ref } from 'vue';
    import { useTheme } from '@/store/theme';
    const theme = useTheme()
    const error = ref('')
    const trigger = ref(false)
    const growRef = ref(null)
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
    width: -webkit-fill-available;
    color: inherit;
    width: -moz-available;
    font-size: 16px;
    min-height: 150px;
    display: inline-block;
}
.grow-wrap {
  /* easy way to plop the elements on top of each other and have them both sized based on the tallest one's height */
  display: grid;
  line-height: 1.6;
  border: 1px solid var(--primary-color);
  padding: 20px 0;
  transition: border 0.3s ease;
  max-width: 100%;
}
.grow-wrap::after {
  /* Note the weird space! Needed to preventy jumpy behavior */
  content: attr(data-replicated-value) " ";

  /* This is how textarea text behaves */
  white-space: pre-wrap;

  /* Hidden from view, clicks, and screen readers */
  visibility: hidden;
}
.grow-wrap > textarea {
  /* You could leave this, but after a user resizes, then it ruins the auto sizing */
  resize: none;
  
  /* Firefox shows scrollbar on growth, you can hide like this. */
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