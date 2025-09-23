<template>
    <div class="workMemberSelector p-3">
        <div id="checkUserSelecter" style=" max-height: 50vh; overflow: hidden auto;">
            <div class="flex gap-3">
                <input :class="['custom-a-input', { 'date-color': theme.dark }]" v-model="date_start" placeholder="回答" type="date"/>
                <input :class="['custom-a-input', { 'date-color': theme.dark }]" v-model="date_end" placeholder="回答" type="date"/>
            </div>
            <div class="mt-3 flex gap-3">
                <CommandButton :buttons="[{title: 'フィルター', action: () => {emit('filter')}}]"/>
                <CommandButton :buttons="[{title: 'リセット', action: () => {emit('reset'), date_start = '', date_end = ''}}]"/>
            </div>  
        </div>
    </div>
</template>
<script setup lang="ts">
import { useTheme } from '@/store/theme';
import { computed, ref } from 'vue';
import CommandButton from '../Global/CommandButton.vue';
import { DateTime } from 'luxon';
import { useMenuStore } from '@/store/menu';
const theme = useTheme()
const menu = useMenuStore()
const emit = defineEmits<{
    (e: 'filter'): void,
    (e: 'reset'): void,
}>()
const date_start = defineModel('date_start')
const date_end = defineModel('date_end')
</script>
<style scoped lang="scss">
    .sub-tab-item{
        padding: 10px 15px;
        font-size: 14px;
        border-bottom: solid thin transparent;
        box-sizing: border-box;
        cursor: pointer;
    }
    .selected-sub-tab{
        border-bottom: solid thin var(--primary-color);
    }
    .sub-tab-container{
        display: flex;
    }
</style>