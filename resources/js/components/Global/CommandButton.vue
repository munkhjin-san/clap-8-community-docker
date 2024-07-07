<template>
    <div class="c-button" :style="customStyle" v-for="button in buttons">
        <div @click="button.action()" class="primary-selection" :class="customClass">{{ button.title }}</div>
    </div>
</template>
<script setup lang="ts">
import { ref } from 'vue';
import { useMenuStore } from "@/store/menu";
import { CommandButtonInterface } from '@/interface/globalInterface';

    const menu = useMenuStore()
    defineProps<{
        buttons: CommandButtonInterface[];
        customStyle?: string;
        customClass?: string;
    }>()
    const emit = defineEmits<{
        (e: 'select', button: CommandButtonInterface): void
    }>()
    const uniqueId = ref(Math.floor(100000 + Math.random() * 900000))
    const select = (button: CommandButtonInterface) => {
        emit('select', button)
        menu.close()
    }
</script>
<style scoped>
    .c-button{
        color: var(--background-color);
        background-color: var(--primary-color);
        font-size: 12px;
        line-height: 1.5;
        white-space: nowrap;
        height: 25px;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: center;
        width: fit-content;
        position: relative
    }
    .divider{
        width: 1px;
        max-width: 1px;
        height: 18px;
        background: gray;
    }
    .rt-icon{
        transition: transform 0.2s ease;
    }
    .rt-cont{
        width: 25px;
        min-width: 25px;
        min-height: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .d-popup{
        position: absolute;
        top: 25px;
        background-color: var(--background-color);
        box-shadow: 0 1px 2px 0 rgba(60,64,67,.3), 0 2px 6px 2px rgba(60,64,67,.15);
        right: 0;
        color: var(--primary-color);
        overflow: hidden;
        z-index: 12;
    }
    .d-item{
        padding: 5px 10px;
        cursor: pointer;
    }
    .d-item:hover{
        background: var(--primary-color);
        color: var(--background-color);
    }
    .d-transtion-enter-active,
    .d-transtion-leave-active {
        max-height: 100px;
        transition: max-height 0.2s ease;
    }

    .d-transtion-enter-from,
    .d-transtion-leave-to {
        max-height: 0;
    }
    .primary-selection{
        padding: 0 7px;
        cursor:pointer
    }
    @media (max-width: 959px) {
        .c-button{
            height: 30px;
        }
        .divider{
            height: 20px;
        }
        .rt-cont{
            width: 30px;
            min-width: 30px;
            min-height: 30px;
            height: 30px;
        }
        .d-popup{
            top: 30px;
        }
        .d-item{
            padding: 10px 15px;
        }
        .primary-selection{
            padding: 0 15px;
        }
        .custom-padding {
            padding: 0 7px;
        }
    }
</style>