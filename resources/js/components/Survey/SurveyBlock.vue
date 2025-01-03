<template>
    <div>
        <div>
            {{ block.question }} <span :class="['text-[gray] text-[12px] ml-[5px]', {'text-[tomato]' : hasError || hasCheckError}]">{{ block.is_required ? '必須' : '' }}</span> 
        </div>
        <div v-if="(block.type == 'radio' || block.type == 'checkbox') && block.elements" class="flex flex-col gap-[15px] mt-[15px]">
            <div v-for="element in block.elements">
                <div>
                    <label class="flex items-center gap-[10px] cursor-pointer">
                        <input 
                            v-if="block.type == 'radio'"
                            ref="target" 
                            :class="[`custom-f-${block.type}`, {'invalid-box' : validateOn && element.is_required && radioModel !== element.id}]" 
                            :name="`radio_values_${block.id}`" 
                            type="radio" 
                            :value="element.id"
                            v-model="radioModel"
                        >
                        <input 
                            v-if="block.type == 'checkbox'"
                            ref="target" 
                            :class="[`custom-f-${block.type}`, {'invalid-box' :  validateOn && element.is_required && !checkboxModel.includes(element.id)}]" 
                            :name="`radio_values_${block.id}`" 
                            type="checkbox" 
                            :value="element.id"
                            v-model="checkboxModel"
                        >
                        <div>{{element.value}}<span class="text-[gray] text-[12px] ml-[5px]">{{ element.is_required ? '(必須)' : '' }}</span> </div>
                    </label>                    
                    <Transition name="customInputGroup">
                        <div class="mt-[10px] ml-[25px]" v-if="element.has_sub_text && (radioModel == element.id || checkboxModel.includes(element.id))">
                            <input :class="['custom-a-input' , {'invalid-input': element.is_required && !sub_texts[element.id]}]" v-model="sub_texts[element.id]" placeholder="回答" back type="text"/>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
        <div class="mt-[15px]">
            <div class="mt-[15px]" v-if="block.type == 'select'">
                <select :class="['custom-a-input', {'invalid-box': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer">
                    <option value="" selected>選択してください</option>
                    <option v-for="element in block.elements" :value="element.value">{{ element.value }}</option>
                </select>
            </div>
            <div v-if="block.type == 'singletext'">
                <input :class="['custom-a-input', {'invalid-input': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" placeholder="回答" type="text"/>
            </div>
            <div v-if="block.type == 'date'">
                <input :class="['custom-a-input', {'invalid-box': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" placeholder="回答" type="date"/>
            </div>
            <div v-if="block.type == 'time'">
                <input :class="['custom-a-input', {'invalid-box': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" placeholder="回答" type="time"/>
            </div>
            <div v-if="block.type == 'multitext'">
                <textarea :class="['custom-a-input', {'invalid-input': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" placeholder="回答" type="text"></textarea>
            </div>
        </div>

    </div>
</template>
<script setup lang="ts">
import { CustomFormBlock, CustomFormBlockElement, SurverBlockElementAnswer, SurveyBlockAnswer } from '@/interface/customFormInterface';
import { computed, onMounted, reactive, ref } from 'vue';
import { ComponentExposed } from 'vue-component-type-helpers';
import 'styles/customForm.css'
import { useTheme } from '@/store/theme';
const props = defineProps<{
    block: CustomFormBlock
}>()
const simpleTypes = ['multitext', 'singletext', 'date', 'time', 'select']
const blockData = reactive<SurveyBlockAnswer>({
    text_answer: '',
    element_answers: [],
    custom_form_block_id: props.block.id,
})
const radioModel = ref<number | string | null>(null)
const checkboxModel = ref<(number | string)[]>([])
const validateOn = ref(false)
const sub_texts = reactive({})
const theme = useTheme()
onMounted(() => {
    const myAnswer = props.block.answers && props.block.answers.length ? props.block.answers[0] : null
    if(myAnswer){
        Object.assign(blockData, myAnswer)
        if(props.block.type == 'radio'){
            const selected = props.block.elements.find( el => el.answers && el.answers.length) ?? null
            if(selected){
                radioModel.value = selected.id
            }
        }
        if(props.block.type == 'checkbox'){
            const selected = props.block.elements.flatMap( el => el.answers).map( an => an?.custom_form_block_element_id)
            if(selected.length){
                checkboxModel.value = selected as number[]
            }
        }
        if(props.block.elements.length){
            props.block.elements.forEach(element => {
                if(element.answers && element.answers.length){
                    sub_texts[element.id] = element.answers[0].sub_text
                }
            });
        }
    }

})
const hasCheckError = computed(() => {
    if (!validateOn.value) return false;
    return !simpleTypes.includes(props.block.type) && props.block.is_required ? !checkboxModel.value.length : false
});
const hasError = computed(() => {
    if (!validateOn.value) return false;
    return simpleTypes.includes(props.block.type) && props.block.is_required ? !blockData.text_answer : false
});
const isValid = ():boolean => {

    validateOn.value = true
    if(simpleTypes.includes(props.block.type)){
        return !props.block.is_required ? true : blockData.text_answer ? true : false
    }
    else{         
        let elementsValid = true
        const checked = props.block.type == 'radio' ? radioModel.value ? [radioModel.value] : [] : checkboxModel.value
        props.block.elements.forEach(element => {
            if(element.is_required){
                let valid = checked.includes(element.id)
                if(element.has_sub_text){
                    let validText = sub_texts[element.id] ? true : false
                    valid = validText && valid
                }
                elementsValid = elementsValid && valid
            }
        });
        const atleast = !props.block.is_required ? true : checked.length ? true : false
        return atleast && elementsValid
    }
}

const extractedData = computed(() => {
    const answers:SurverBlockElementAnswer[] = []
    if(props.block.type == 'radio'){
        const answer:SurverBlockElementAnswer = {
            custom_form_block_element_id: Number(radioModel.value),
            sub_text: sub_texts[Number(radioModel.value)],
            checked: true
        }
        answers.push(answer)
    }
    if(props.block.type == 'checkbox'){
        checkboxModel.value.forEach( id => {
            const answer:SurverBlockElementAnswer = {
                custom_form_block_element_id: Number(id),
                sub_text: sub_texts[Number(id)],
                checked: true
            }
            answers.push(answer)
        });
    }
    const element_answers = {
        element_answers: answers
    }
    Object.assign(blockData, element_answers)
    return blockData
})

defineExpose({isValid, blockData, extractedData})
</script>
<style>
.custom-a-input{
    font-size: 13px;
    padding: 5px 10px;
    width: fit-content;
    border: solid thin var(--formBorder);
    transition: border 0.2s;
    resize: none;
    width: fit-content;
    color: var(--primary-color);
}
input[type='text'].custom-a-input{
    width: 50%;
}

textarea.custom-a-input{
    min-height: 150px;
    width: 50%;
}
.custom-a-input:focus{
    border: solid thin var(--primary-color);
}

@media screen and (max-width: 959px) {
    input[type='text'].custom-a-input{
        width: -webkit-fill-available;
    }
    textarea.custom-a-input{
        width: -webkit-fill-available;
    }
}
</style>