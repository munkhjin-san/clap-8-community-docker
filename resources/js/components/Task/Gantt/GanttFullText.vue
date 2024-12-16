<template>
    <div class="overlay" @click="emit('close')">
        
        <div :class="['g-full-text', {'editingRemarks' : editing}]" @click.stop>
            <div class="f-full-inner">
                <p class="editable-content" ref="textValue" :contenteditable="editing" v-html="urlCheck(data.text)"></p>
                
            </div>
            <div class="flex items-center gap-[20px] mt-[20px] justify-center">
                <LoaderButton v-if="data.editable && !editing" :loading="false" @triggered="editStart" content="編集" style="margin: 0;"/>
                <LoaderButton v-if="editing" :loading="sending" @triggered="send" content="保存" style="margin: 0;"/>
                <LoaderButton v-if="editing" :loading="false" @triggered="editing = false" content="キャンセル" style="margin: 0;"/>
            </div>
            
        </div>
    </div>
</template>
<script setup lang="ts">
import { urlCheck } from '@/utils/tools';
import { inject, ref, useTemplateRef } from 'vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { QuickEditText } from '@/interface/projectInterface';
import axios from 'axios';
import { Dialog } from '@/interface/globalInterface';
import { GanttProjectMethods, GanttProjectMethodsKey } from '@/interface/keys';
import { useRoute } from 'vue-router';
const props = defineProps<{
  data: QuickEditText
}>()
const { notify, info } = inject<Dialog>('dialog')!;
const {refreshProject} = inject(GanttProjectMethodsKey) as GanttProjectMethods
const route = useRoute()
const sending = ref(false)
const editing = ref(false)
const textValue = useTemplateRef('textValue')
const send = async () => {
    if(!textValue.value?.textContent) return
    console.log(textValue.value?.textContent)
    try {
        await axios.patch(`/quick_edit_task`, {
            id: props.data.id,
            column: 'remarks',
            value: textValue.value?.textContent
        })
        await refreshProject({})
        emit('close')
        info('更新しました。')
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
    }
}
const editStart = () => {
    editing.value = true
    setTimeout(() => {
        textValue.value?.focus()   
    });
}
const emit = defineEmits(['close'])
</script>
<style>
.g-full-text{
    min-width: 20%;
    max-width: 70%;
    max-height: 70%;
    font-size: 14px;
    line-height: 1.5;
    padding: 20px;
    background: var(--background-color);
    color: var(--primary-color);
    white-space: break-spaces;
    overflow-wrap: break-word;

}
.f-full-inner{
    max-height: calc(70vh - 55px);
    overflow: hidden auto;
    display: inline-block;
}
.editable-content{
    outline: none;
    display: inline-block;
}
.editingRemarks{
    border: solid 2px var(--hoverBorder)
}

</style>