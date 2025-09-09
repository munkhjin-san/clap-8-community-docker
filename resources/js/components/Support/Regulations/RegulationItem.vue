<template>
    <div class="py-[40px] regulation-item">
        <div class="flex">
            <h3 class="mb-[30px]">{{ regulation.title }}</h3>
            <div class="ml-auto" v-if="isAuthorized">
                <ItemMenu :items="[
                    { title: '編集', action: () => emit('edit', regulation) },
                    { title: '削除', action: () => emit('delete', regulation) }
                ]" />
            </div>
        </div>
        <div>
            <div :style="{ height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease' }">
                <p ref="contentBody" style="line-height: 1.5;white-space: pre-line;"
                    v-html="urlCheck(regulation.content)"></p>
            </div>
            <div @click="toggleFull" class="jump-link" style="margin-top:10px"
                v-if="dynamicHeight !== 'auto'">{{ dynamicHeight == staticHeight + 'px' ? '続きを表示する' : '閉じる' }}
            </div>
        </div>
        <div v-if="regulation.regulation_files && regulation.regulation_files.length" style="margin-top: 20px;">
            <RegulationFiles :files="regulation.regulation_files" mode="view"/>
        </div>
    </div>
</template>
<script setup lang="ts">
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { Regulation } from '@/interface/regulationInterface';
import { urlCheck } from '@/utils/tools';
import { onMounted, ref, useTemplateRef } from 'vue';
import RegulationFiles from './RegulationFiles.vue';
import { useResponsive } from '@/store/responsive';

const props = defineProps<{
    regulation: Regulation
    isAuthorized: boolean
}>()

const emit = defineEmits<{
    edit: [regulation: Regulation];
    delete: [regulation: Regulation];
}>();
const dynamicHeight = ref('auto')
const contentBody = useTemplateRef('contentBody')
const mobile = useResponsive()
const staticHeight = ref(mobile.mobile ? 42 : 48)
onMounted(() => {
    if (contentBody.value) {
        if (contentBody.value?.clientHeight > staticHeight.value) {
            dynamicHeight.value = staticHeight.value + 'px'
        }
    }
})

const toggleFull = () => {
    dynamicHeight.value = dynamicHeight.value == staticHeight.value + 'px' ? `${contentBody.value?.clientHeight}px` : staticHeight.value + 'px'
}
</script>
<style scoped>
.regulation-item {
    border-bottom: 1px solid var(--formBorder);
}
.regulation-item:last-child {
    border-bottom: none;
}
</style>
