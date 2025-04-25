<template>
    <div 
        v-if="show" 
        class="text-[11px] whitespace-nowrap ml-[5px]" 
        :style="{ color: color}"
    >
        {{ `${show > 0 ? ' ↑ ' : ' ↓ '}${type === 'profit_rate' ? `${show.toFixed(2)}%` : amountOfMoneyParser( show )}` }}
    </div>
</template>
<script lang="ts" setup>
import { amountOfMoneyParser } from '@/utils/tools';
import { computed } from 'vue';

const props = defineProps<{
    type: "sales" | "expense" | "profit" | "profit_rate"
    planned: any
    actual: any
}>()

const show = computed(() => {
    const plan = Number(props.planned)
    const actual = Number(props.actual)
    if(!plan || !actual) return false
    if(plan === actual) return false
    return actual - plan

})

const color = computed(() => {
    if(!show.value) return ''
    if(props.type === 'expense') {
        return show.value > 0 ? 'tomato' : 'green'
    } else {
        return show.value > 0 ? 'green' : 'tomato'
    }
})
</script>