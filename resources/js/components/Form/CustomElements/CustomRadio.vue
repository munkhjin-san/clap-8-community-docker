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
                        <div class="flex items-start gap-[10px]">
                            <label class="flex items-center gap-[10px] cursor-pointer pt-[3px]">
                                <input :disabled="true" class="custom-f-radio" :name="`radio_values_${block.id}`" type="radio" :value="answer.value">
                            </label>
                            <div class="flex flex-1 flex-wrap items-start min-w-0">
                                <textarea
                                    placeholder="選択肢"
                                    class="custom-o-input custom-o-textarea"
                                    :id="`c-check-${answer.id}`"
                                    ref="radios"
                                    v-model="answer.value"
                                    rows="2"
                                    @keydown.ctrl.enter.prevent="addItem(index, true)"
                                    @keydown.meta.enter.prevent="addItem(index, true)"
                                ></textarea>
                                <div class="flex items-center ml-[15px] mt-[5px]">
                                    <div class="flex items-center gap-[10px]">
                                        <label class="flex items-center gap-[5px] text-[12px] cursor-pointer">
                                            <input type="checkbox" v-model="answer.has_sub_text"/>
                                            サブテキスト欄
                                        </label>
                                        <label class="flex items-center gap-[5px] text-[12px] cursor-pointer">
                                            <input type="checkbox" v-model="answer.has_file_attachment" @change="ensureFiles(answer)"/>
                                            ファイル添付
                                        </label>
                                        <input v-if="answer.has_sub_text" placeholder="プレースホルダー" class="custom-o-input" back type="text" v-model="answer.placeholder"/>
                                    </div>                                    
                                </div>
                                <div v-if="answer.has_file_attachment" class="w-full mt-[10px]">
                                    <FileUploader
                                        v-model="answer.files"
                                        path="/survey_files"
                                        :customClass="['custom-a-input']"
                                        customStyle="width: min(420px, 100%); border: 1px solid var(--formBorder);"
                                    />
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
import { CustomFormBlock } from '@/interface/customFormInterface';
import { onMounted, reactive } from 'vue';
import 'vuetify/lib/components/VCheckbox/VCheckbox.css';
import CloseIcon from '../CloseIcon.vue';
import { useTemplateRef } from 'vue';
import 'styles/customForm.css'
import AddIcon from '../AddIcon.vue';
import FileUploader from '../FileUploader.vue';
    const props = defineProps<{
        block: CustomFormBlock
    }>()
    const elements = reactive(props.block.elements)
    const radios = useTemplateRef<HTMLTextAreaElement[]>('radios')
    const ensureFiles = (answer: CustomFormBlock['elements'][number]) => {
        if (!answer.files) {
            answer.files = []
        }
    }
    elements.forEach(ensureFiles)
    onMounted(() => {
        if(!props.block.elements.length){
            addItem(0)
        } 
    })
    const addItem = (index: number, jump?: boolean) => {
        const id = -(Math.floor(100000 + Math.random() * 900000))
        const item = {
            id: id,
            value: '',
            has_sub_text: false,
            has_sub_text_required: false,
            has_file_attachment: false,
            is_required: false,
            files: [],
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
