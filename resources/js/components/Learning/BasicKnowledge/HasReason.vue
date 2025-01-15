<template>
    <div class="overlay">
        <div class="chatCreate scrollable">
            <div class="recordFormTitle" style="display:flex">
                <p>個別フォローアップ申請</p>
            </div>
            <div>
                <p>理解出来なかった内容について、法務から個別フォローアップのため後日ご連絡致します。</p>
            </div>
            <div class="si-box">
                <p class="mb-[10px]">効果的なフォローアップを実施するため、理解が難しかった内容や具体的な質問点を以下にご記入ください。</p>
                <LongInput 
                    place-holder="内容"
                    v-model="reason_dnt_und"
                    rules="required"
                    ref="dntUndRef"
                />
            </div>
            <div class="si-box flex justify-center gap-[30px]">
                <LoaderButton style="margin: 0" content="戻る" @click="emit('close')"/>
                <LoaderButton style="margin: 0" content="申請" @click="validate"/>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { ref, useTemplateRef } from 'vue';
import { ComponentExposed } from 'vue-component-type-helpers';
const emit = defineEmits(['close', 'update'])
const reason_dnt_und = ref('')
const dntUndRef = useTemplateRef<ComponentExposed<typeof LongInput>>('dntUndRef')
const validate = async() =>{
    const result = await dntUndRef.value?.validate();
    if(!result?.valid) return

    emit('update', -1, reason_dnt_und.value)
}
</script>