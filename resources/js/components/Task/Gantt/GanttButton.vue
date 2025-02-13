<template>
    <div @click="run" class="c-button !h-[20px]" :style="{background: backgrounds[status], cursor: viewType == 'status' ? 'not-allowed' : 'pointer'}">
        <div class="primary-selection !px-[5px]">{{ statuses[status] }}</div>
    </div>
</template>
<script setup lang="ts">
import { onMounted } from 'vue';


const props = defineProps<{
    status: number
    loading: boolean
    viewType: string
}>()
const emit = defineEmits<{
    action: [flag: number]
}>()
const statuses = [
    "未対応",
    "対応中",
    "完了"
]
const flags = [1, 2, 0]

const backgrounds = [
    "black",
    "#eb7a00",
    "green"
]

const run = () => {
    if(props.loading) return
    emit('action', flags[props.status])
}
</script>
<style scoped>
    .c-button{
        color: #fff;
        background-color: #000;
        font-size: 12px;
        line-height: 1.5;
        white-space: nowrap;
        height: 25px;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: center;
        width: fit-content;
        position: relative;
        user-select: none;
    }


    .primary-selection{
        padding: 0 7px;
    }
    @media (max-width: 959px) {
        .c-button{
            height: 30px;
        }
        .primary-selection{
            padding: 0 15px;
        }
    }
</style>