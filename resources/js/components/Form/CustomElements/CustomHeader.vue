<template>
    <div>
        <div>
            <div class="mb-[15px] flex items-center gap-[10px]">
                <RichEditor 
                    style="width: 100%; background-color: var(--background-color);" 
                    ref="richEdit" 
                    :initila-value="question ? question : ''"
                    @content-updated="updateQuestion"
                />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { CustomFormBlock } from '@/interface/customFormInterface';
import 'vuetify/lib/components/VCheckbox/VCheckbox.css';
import RichEditor from '@/components/Global/RichEditor.vue';
import 'styles/customForm.css'

    const props = defineProps<{
        block: CustomFormBlock
    }>()

    const question = defineModel<string>('question')
    const updateQuestion = (content: string) => {
        question.value = content
    }
</script>
