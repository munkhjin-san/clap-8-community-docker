<template>
    <div>
        <div>
            <div class="mb-[15px] flex items-center gap-[10px]">
                <input class="custom-q-input" placeholder="質問" type="text" v-model="question"/>
                <div class="flex ml-auto">
                    <div class="flex items-center gap-[10px]">
                        <label class="flex items-center gap-[5px] text-[12px] cursor-pointer whitespace-nowrap">
                            <input type="checkbox" v-model="block.is_required"/>
                            必須
                        </label>                                        
                    </div>                                
                </div> 
            </div>
            <div class="flex flex-col gap-[5px]">
                <TransitionGroup name="customInputGroup">
                    <div :key="answer.id" v-for="(answer, index) in elements">     
                        <div class="flex items-center gap-[10px]">
                            <label class="flex items-center gap-[10px] cursor-pointer">
                                <input :disabled="true" class="custom-f-radio" :name="`radio_values_${block.id}`" type="radio" :value="answer.value">
                            </label>
                            <div class="flex items-center">
                                <input placeholder="選択肢" class="custom-o-input" type="text" :id="`c-check-${answer.id}`" ref="radios" v-model="answer.value" @keyup.enter="addItem(index, true)"/>
                                <div class="flex items-center ml-[15px]">
                                    <div class="flex items-center gap-[10px]">
                                        <label class="flex items-center gap-[5px] text-[12px] cursor-pointer">
                                            <input type="checkbox" v-model="answer.has_sub_text"/>
                                            サブテキスト欄
                                        </label>
                                        <input v-if="answer.has_sub_text" placeholder="プレースホルダー" class="custom-o-input" back type="text" ref="checkboxes" v-model="answer.placeholder"/>                                                                             
                                    </div>                                    
                                </div>  
                            </div>
                            <div class="flex ml-auto">
                                <div title="選択肢追加" @click="addItem(index)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                    <AddIcon size="10"/>
                                </div>
                                <div title="選択肢削除" @click="removeItem(index)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                    <CloseIcon size="8"/>
                                </div>                                
                            </div> 
                        </div>             
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { CustomFormBlockState, CustomFormBlockElement, CustomFormBlock } from '@/interface/customFormInterface';
import { onMounted, reactive } from 'vue';
import 'vuetify/lib/components/VCheckbox/VCheckbox.css';
import CloseIcon from '../CloseIcon.vue';
import { useTemplateRef } from 'vue';
import 'styles/customForm.css'
import AddIcon from '../AddIcon.vue';
    const props = defineProps<{
        block: CustomFormBlock
    }>()
    const elements = reactive(props.block.elements)
    onMounted(() => {
        if(!props.block.elements.length){
            addItem(0)
        } 
    })
    const radios = useTemplateRef<HTMLInputElement[]>('radios')
    const addItem = (index: number, jump?: boolean) => {
        const id = -(Math.floor(100000 + Math.random() * 900000))
        const item = {
            id: id,
            value: '',
            has_sub_text: false,
            has_sub_text_required: false,
            is_required: false
        }
        elements.splice(index + 1, 0, item)
        
        setTimeout(() => {
            if(jump && radios.value && radios.value.length){
                const el = radios.value.find( c => c.id == `c-check-${id}`)
                console.log(el)
                el?.focus()
            }
        });
      

    }
    const removeItem = (index: number) => {
        if(elements.length && elements.length > 1){
            elements.splice(index, 1)
        }

    }
    const question = defineModel<string>('question')
</script>
