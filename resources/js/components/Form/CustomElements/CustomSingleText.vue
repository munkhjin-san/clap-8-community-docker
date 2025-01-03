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
        </div>
    </div>
</template>
<script setup lang="ts">
import { CustomFormBlock } from '@/interface/customFormInterface';
import 'vuetify/lib/components/VCheckbox/VCheckbox.css';
import 'styles/customForm.css'

    const props = defineProps<{
        block: CustomFormBlock
    }>()

    const question = defineModel<string>('question')
</script>
