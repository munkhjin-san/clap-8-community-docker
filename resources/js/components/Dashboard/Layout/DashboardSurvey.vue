<template>
    <BaseLayout
        :title="data.title" 
        :count="data.data.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <div v-if="!fullscreen" class="mx-3 mb-3">
            <v-expansion-panels>
                <v-expansion-panel selected-class="selected-panel-item" hide-actions static :tile="true" class="rm-p" v-for="(form, index) in data.data" :key="index">
                    <v-expansion-panel-title>
                        <template v-slot:default="{ expanded }">
                            <div class="px-3 text-[13px] overflow-hidden whitespace-nowrap text-ellipsis">{{ form.title }}</div>
                        </template>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                        <div class="mt-2">
                            <CommandButton 
                                :buttons="[
                                    {title: '回答', action: () => router.push(`/survey/${form.id}`)},
                                ]"
                            />
                        </div>
                    </v-expansion-panel-text>
                </v-expansion-panel>
            </v-expansion-panels>
        </div>
    </BaseLayout>
            
</template>
<script setup lang="ts">
import { CustomForm } from '@/interface/customFormInterface';
import { computed, ref, useTemplateRef } from 'vue';
import BaseLayout from './BaseLayout.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';

const props = defineProps<{
    data: {
        title: string,
        data: CustomForm[],
        order?: number,
        type: string
    }
}>()
const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()
const router = useRouter()
const parent = useTemplateRef('parent')
const fullscreen = ref(false)
const auth = useAuthUserStore()


defineExpose({
    cardType: props.data.type,
})
</script>