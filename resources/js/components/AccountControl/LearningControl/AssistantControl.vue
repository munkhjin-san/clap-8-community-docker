<template>
    <div class="admin-window" style="overflow: auto;">
        <div class="w-[50%] p-[20px]">
            <ShortInput 
                placeHolder="プロンプトID"
                v-model="promptId"
                type="text"
            />
        </div>
    </div>
</template>
<script setup>
import { onMounted, ref }from 'vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import ShortInput from '@/components/Form/ShortInput.vue';
const props = defineProps(['theme'])


const promptId = ref(props.theme.prompt_id ? props.theme.prompt_id : null)
const { ask, ping, toast } = useDialog()
onMounted(async() => {

})

</script>