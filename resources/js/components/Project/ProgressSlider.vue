<template>
    <div class="relative">
        <button :disabled="disabled" @click.stop="viewMenu" class="flex items-center gap-[5px] px-[10px] py-[5px] w-[50px]" :style="{ background: progress == 100 ? '#64bc44' : 'var(--bg3)', color: progress == 100 ? 'white' : 'var(--primary-color)' }">
            <span class="text-[12px]">{{ progress }}%</span>
            <Back v-if="!disabled" size="10" :style="{fill: progress == 100 ? '#fff' : 'var(--primary-color)'}" class="-rotate-90 ml-auto"/>

        </button>
        <Transition name="slidePop">
            <div class="absolute top-[30px] right-0 shadow-me z-[7] bg-[var(--background-color)] px-[10px] py-[10px]" :id="`progress-slider-${stepId ? stepId : goalId}`" v-if="menu.parent == `progress-slider-${stepId ? stepId : goalId}`">
                <div class="my-[10px]">達成率</div>
                <div class="flex items-center gap-[10px] mb-[10px]">
                    <input
                        type="range"
                        id="value-slider"
                        v-model="progressData"
                        :min="0"
                        :max="100"
                        :step="10"
                        class="range-input !w-auto"
                    />
                    <span>{{ progressData }}%</span>                    
                </div>
                <LoaderButton class="!m-0" content="保存" @triggered="save" :loading="saving"/>
            </div>
        </Transition>
    </div>

</template>
<script setup lang="ts">
import { useMenuStore } from '@/store/menu';
import Back from '@/components/Icons/Back.vue';
import { inject, ref, toRef } from 'vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useApi } from '@/composables/api';

const props = defineProps<{
    goalId: number
    stepId?: number
    disabled: boolean
    progress: number
    type: string
}>()
const menu = useMenuStore()

const progressData = ref(toRef(props.progress))

const saving = ref(false)

const refresh = inject('refresh') as Function

const api = useApi()

const viewMenu = () => {
    if (props.disabled) return
    menu.setMenu({parent: `progress-slider-${props.stepId ? props.stepId : props.goalId}`})
}

const save = async() => {

    await api.post('/save_project_progress', {
        goal_id: props.goalId,
        progress: progressData.value,
        step_id: props.stepId,
        type: props.type
    }, {
        toast: '保存しました。',
        loadingRef: saving
    })
    menu.close()
    if(typeof refresh === 'function') {
        refresh()
    }
}
</script>