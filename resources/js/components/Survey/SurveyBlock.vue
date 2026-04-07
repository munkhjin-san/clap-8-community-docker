<template>
    <div>
        <div v-if="block.type == 'header'" class="rich-wrapper" v-html="urlCheck(block.question)"></div>
        <div v-else>
            {{ block.question }} <span :class="['text-[gray] text-[12px] ml-[5px]', {'text-[tomato]' : hasError}]">{{ block.is_required ? '必須' : '' }}</span> 
        </div>
        <div v-if="(block.type == 'radio' || block.type == 'checkbox') && block.elements" class="flex flex-col gap-[15px] mt-[15px]">
            <div v-for="element in block.elements">
                <div>
                    <label class="flex items-center gap-[10px] cursor-pointer">
                        <input 
                            v-if="block.type == 'radio'"
                            ref="target" 
                            :class="[`custom-f-${block.type}`, {'invalid-box' : validateOn && block.is_required && !radioModel}]" 
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
                            <input :class="['custom-a-input' , {'invalid-input': validateOn && element.has_sub_text_required && !sub_texts[Number(element.id)]}]" v-model="sub_texts[Number(element.id)]" :placeholder="element.placeholder ? element.placeholder : '回答'" back type="text"/>
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
                <input :class="['custom-a-input', {'invalid-input': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" :placeholder="block.placeholder ? block.placeholder : '回答'" type="text"/>
            </div>
            <div v-if="block.type == 'date'">
                <input :class="['custom-a-input', {'invalid-box': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" placeholder="回答" type="date"/>
            </div>
            <div v-if="block.type == 'time'">
                <input :class="['custom-a-input', {'invalid-box': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" placeholder="回答" type="time"/>
            </div>
            <div v-if="block.type == 'multitext'">
                <textarea :class="['custom-a-input', {'invalid-input': hasError}, { 'date-color': theme.dark }]" v-model="blockData.text_answer" :placeholder="block.placeholder ? block.placeholder : '回答'" type="text"></textarea>
            </div>
            <div v-if="block.type == 'file'">
                <FileUploader 
                    v-model="blockData.files"
                    :path="filePath"
                    :customClass="['custom-a-input', {'invalid-file-input': hasError}]"
                    customStyle="width: 50%; border: 1px solid var(--formBorder);"
                />
            </div>
        </div>

    </div>
</template>
<script setup lang="ts">
import { CustomFormBlock, SurverBlockElementAnswer, SurveyBlockAnswer } from '@/interface/customFormInterface';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import 'styles/customForm.css'
import { useTheme } from '@/store/theme';
import FileUploader from '../Form/FileUploader.vue';
import { urlCheck } from '@/utils/tools';
const props = defineProps<{
    block: CustomFormBlock
    answer?: SurveyBlockAnswer | null
    filePath?: string
}>()
const emit = defineEmits<{
    selectionChange: [payload: { blockId: number; type: 'radio' | 'checkbox'; elementIds: number[] }]
}>()
const simpleTypes = ['multitext', 'singletext', 'date', 'time', 'select']
const blockData = reactive<SurveyBlockAnswer>({
    text_answer: '',
    element_answers: [],
    custom_form_block_id: props.block.id,
    files: []
})
const radioModel = ref<number | string | null>(null)
const checkboxModel = ref<(number | string)[]>([])
const validateOn = ref(false)
const sub_texts = reactive<Record<number, string>>({})
const theme = useTheme()
const filePath = computed(() => props.filePath || '/survey_files')
onMounted(() => {
    if(props.answer){
        Object.assign(blockData, props.answer)
        if(props.block.type == 'radio'){
            const selected = props.answer.element_answers.find( an => an.custom_form_block_element_id && an.checked )
            if(selected && selected.custom_form_block_element_id && props.block.elements.find(el => el.id == selected.custom_form_block_element_id)){
                radioModel.value = selected.custom_form_block_element_id
            }
        }
        if(props.block.type == 'checkbox'){
            const selected = props.answer.element_answers.filter( an => an.checked && props.block.elements.find(el => el.id == an.custom_form_block_element_id))
            if(selected.length){
                const ids = selected.map(el => el.custom_form_block_element_id).filter(el => el !== null && el !== undefined)
                ids.forEach(id => {
                    if(props.block.elements.find(el => el.id == id)){
                        checkboxModel.value.push(id)
                    }
                });
            }
        }
        if(props.answer.element_answers?.length){
            props.answer.element_answers.forEach((elementAnswer) => {
                if (!elementAnswer.custom_form_block_element_id) return
                sub_texts[elementAnswer.custom_form_block_element_id] = elementAnswer.sub_text ?? ''
            })
        }
    }

})
watch(
    radioModel,
    (value) => {
        if (props.block.type !== 'radio') return
        const elementIds = value ? [Number(value)] : []
        emit('selectionChange', { blockId: props.block.id, type: 'radio', elementIds })
    },
    { immediate: true }
)
watch(
    checkboxModel,
    (value) => {
        if (props.block.type !== 'checkbox') return
        const elementIds = value ? value.map(v => Number(v)) : []
        emit('selectionChange', { blockId: props.block.id, type: 'checkbox', elementIds })
    },
    { immediate: true, deep: true }
)
const hasCheckError = computed(() => {
    if (!validateOn.value) return false;
    return !simpleTypes.includes(props.block.type) && props.block.is_required ? !checkboxModel.value.length : false
});
const hasError = computed(() => {
    if (!validateOn.value) return false;
    if(props.block.type == 'file'){
        return !blockData.files.length && props.block.is_required
    }else if(props.block.type == 'radio'){
        return props.block.is_required && !radioModel.value
    }else if(props.block.type == 'checkbox'){
        return hasCheckError.value
    }
    else {
        return simpleTypes.includes(props.block.type) && props.block.is_required ? !blockData.text_answer : false
    }
     
});
const isValid = ():boolean => {

    validateOn.value = true
    if(simpleTypes.includes(props.block.type)){
        return !props.block.is_required ? true : blockData.text_answer ? true : false
    }
    if(props.block.type == 'file'){
        if(!props.block.is_required) return true
        const val = Array.isArray(blockData.files) && blockData.files.length > 0
        return val
    }
    if(props.block.type == 'radio'){
        if (props.block.is_required && !radioModel.value) {
            return false
        }
        if (!radioModel.value) {
            return true
        }
        const selectedElement = props.block.elements.find((element) => element.id == radioModel.value)
        if (!selectedElement?.has_sub_text_required) {
            return true
        }
        const id = Number(selectedElement.id)
        return Boolean(sub_texts[id])
    }
    else{         
        let elementsValid = true
        const checked = checkboxModel.value
        props.block.elements.forEach(element => {
            if(element.is_required && !checked.includes(element.id)){
                elementsValid = false
                return
            }
            const id = Number(element.id)
            if(element.has_sub_text_required && checked.includes(element.id) && !sub_texts[id]){
                elementsValid = false
            }
        });
        const atleast = !props.block.is_required ? true : checked.length ? true : false
        return atleast && elementsValid
    }
}

const extractedData = computed(() => {
    const answers:SurverBlockElementAnswer[] = []
    if(props.block.type == 'radio' && radioModel.value){
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
