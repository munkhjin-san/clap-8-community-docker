<template>
    <div id="cMonthPicker" class="monthPicker gantt-month-picker" :class="{'has-navigation': showNavigation}">
        <button
            v-if="showNavigation"
            type="button"
            class="gantt-picker-nav gantt-picker-nav-prev"
            :class="{ 'has-unread': previousBadge > 0 }"
            :title="previousTitle || '前の期間'"
            @click.stop="shiftPeriod(-1)"
        >
            <Back size="12"/>
            <span v-if="previousBadge > 0" class="gantt-picker-nav-badge">{{ badgeLabel(previousBadge) }}</span>
        </button>
        <div class="gantt-picker-display">
            <div @click.stop="openMonthPicker" id="activateButton" class="g-y-pick">{{ formatDate }}</div>
        </div>
        <button
            v-if="showNavigation"
            type="button"
            class="gantt-picker-nav gantt-picker-nav-next"
            :class="{ 'has-unread': nextBadge > 0 }"
            :title="nextTitle || '次の期間'"
            @click.stop="shiftPeriod(1)"
        >
            <Back size="12" class="rotate-180"/>
            <span v-if="nextBadge > 0" class="gantt-picker-nav-badge">{{ badgeLabel(nextBadge) }}</span>
        </button>
        <div id="taskYearPicker" class="month-grid" v-if="menu.id == uniqueId && menu.name == 'taskYearPicker'" :style="{right : right ? right : 'auto'}">
            <div class="grid-container">
                <div @click.stop="decreaseYear" class="grid-item grid-picker">
                    <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                <div @click.stop="pickerIs = 'year'" class="grid-item grid-picker">{{ year }}年</div>
                <div @click.stop="increaseYear" class="grid-item grid-picker">
                    <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
            </div>
            <div v-if="pickerIs == 'month'" class="grid-container ">
                <div @click.stop="setMonth(month as MonthNumbers)" :id="`m_${month}`" v-for="month in 12" class="grid-item">{{ month }}月</div>
            </div>
            <div v-if="pickerIs == 'year'" class="grid-container year-picker">
                <div @click.stop="setYear(y)" :id="`y_${y}`" :class="{thisYear : y == year}" v-for="y in yearList" class="grid-item">{{ y }}年</div>
            </div>
        </div>
       
    </div>
</template>

<script setup lang="ts">
import { computed, ref, } from 'vue'   
import { useMenuStore } from "@/store/menu";
import { DateTime, MonthNumbers } from 'luxon';
import Back from '@/components/Icons/Back.vue';
    const props = withDefaults(defineProps<{
        right: string;
        displayMode?: 'month' | 'year';
        showNavigation?: boolean;
        previousBadge?: number;
        nextBadge?: number;
        previousTitle?: string;
        nextTitle?: string;
    }>(), {
        displayMode: 'month',
        showNavigation: false,
        previousBadge: 0,
        nextBadge: 0,
        previousTitle: '',
        nextTitle: '',
    })
    
    const emit = defineEmits<{
        (e: 'setDate'): void
    }>()

    const month = defineModel<MonthNumbers>('month')
    const year = defineModel<number>('year')
    const pickerIs = ref('')
    const uniqueId = ref(Math.floor(100000 + Math.random() * 900000))

    const menu = useMenuStore()

    const yearList = computed(() => {
        const centerYear = year.value ?? DateTime.now().year
        return Array.from({ length: 12 }, (_, i) => centerYear - 5 + i);
    })
    const formatDate = computed(() => {
        if (props.displayMode == 'year') {
            return `${year.value}年`
        }
        let formattedMonth = `${month.value}`.padStart(2, '0');
        const date = `${year.value}-${formattedMonth}`
        return DateTime.fromISO(date).toFormat('yyyy年M月')
    })
    const decreaseYear = () => {
        if(year.value){
            year.value --
            if (props.displayMode == 'year') {
                emit('setDate')
            }
        }
    }
    const increaseYear = () => {
        if(year.value){
            year.value ++
            if (props.displayMode == 'year') {
                emit('setDate')
            }
        }
    }
    const openMonthPicker = () => {
        pickerIs.value = props.displayMode == 'year' ? 'year' : 'month';
        if(menu.name == 'taskYearPicker'){
            menu.close()
            return
        }
        menu.setMenu( { name: 'taskYearPicker', id: uniqueId.value})
    }
    const setYear = (y: number) => {
        year.value = y
        pickerIs.value = props.displayMode == 'year' ? '' : 'month'
        if (props.displayMode == 'year') {
            menu.close()
            emit('setDate')
        }
    }
    const setMonth = (m: MonthNumbers) => {
        month.value = m
        pickerIs.value = ''
        menu.close()
        emit('setDate')

    }
    const shiftPeriod = (direction: number) => {
        if (!year.value) {
            year.value = DateTime.now().year
        }
        if (props.displayMode == 'month') {
            const currentMonth = month.value ?? DateTime.now().month as MonthNumbers
            const shifted = DateTime.fromObject({ year: year.value, month: currentMonth }).plus({ months: direction })
            year.value = shifted.year
            month.value = shifted.month as MonthNumbers
        } else {
            year.value += direction
        }
        emit('setDate')
    }
    const badgeLabel = (count: number) => count > 9 ? '9+' : count.toString()
</script>

<style scoped>
.gantt-month-picker {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    color: var(--primary-color);
}

.gantt-picker-display {
    display: flex;
    align-items: center;
    min-width: 0;
}

.g-y-pick {
    min-width: 96px;
    height: 28px;
    padding: 0 10px;
    border: 1px solid transparent;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    line-height: 28px;
    text-align: center;
    white-space: nowrap;
    user-select: none;
}

.g-y-pick:hover {
    border-color: var(--hoverBorder);
    background: var(--bg3);
}

.gantt-picker-nav {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
}

.gantt-picker-nav-badge {
    position: absolute;
    top: 50%;
    min-width: 13px;
    height: 13px;
    padding: 0 3px;
    border-radius: 999px;
    background: #f28c28;
    box-shadow: 0 0 0 1px var(--background-color);
    color: #fff;
    font-size: 8px;
    font-weight: 700;
    line-height: 13px;
    text-align: center;
    transform: translateY(-50%);
    pointer-events: none;
}

.gantt-picker-nav-prev .gantt-picker-nav-badge {
    right: calc(100% - 2px);
}

.gantt-picker-nav-next .gantt-picker-nav-badge {
    left: calc(100% - 2px);
}

.gantt-picker-nav:hover {
    background: var(--bg3);
}

.gantt-picker-nav :deep(svg) {
    fill: currentColor;
}

.month-grid {
    position: absolute;
    top: 34px;
    z-index: 220;
    width: max-content;
    max-height: 240px;
    overflow-y: auto;
    padding: 8px;
    border: 1px solid var(--normalBorder);
    background: var(--background-color);
    box-shadow: 0 8px 18px rgb(0 0 0 / 14%);
}

.grid-container {
    display: grid;
    grid-template-columns: repeat(3, minmax(48px, 1fr));
    gap: 4px;
}

.grid-container + .grid-container {
    margin-top: 6px;
}

.grid-item {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 48px;
    min-height: 32px;
    padding: 0 8px;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
    white-space: nowrap;
}

.grid-item:hover,
.thisYear {
    background: var(--bg3);
    font-weight: 700;
}

.grid-picker {
    min-height: 28px;
}

.grid-picker svg {
    fill: currentColor;
}

.year-picker {
    grid-template-columns: repeat(3, minmax(60px, 1fr));
}
</style>
