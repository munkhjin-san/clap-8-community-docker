<template>
    <div class="settings-panel">
        <div class="settings-card">
            <label
                v-for="opt in themeOptions"
                :key="opt.value"
                class="option-row"
                :class="{ 'option-row--active': dark == opt.value }"
            >
                <span class="option-row__icon" v-html="opt.icon"></span>
                <span class="option-row__label">{{ opt.label }}</span>
                <input class="option-radio" v-model="dark" type="radio" name="theme" :value="opt.value">
                <span class="option-check"></span>
            </label>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useTheme } from '@/store/theme'
import { icons } from '../icons'

    const theme = useTheme()
    const themeChange = ref(0)

    const themeOptions = [
        { value: 0, label: 'ブラウザと同じ', icon: icons.auto },
        { value: 1, label: 'ダーク', icon: icons.moon },
        { value: 2, label: 'ライト', icon: icons.sun },
    ]

    const dark = computed({
        get() {
            themeChange.value
            const customTheme = localStorage.getItem('dark')
            if (customTheme == 0 || customTheme == '0' || !customTheme) return 0
            return parseInt(customTheme)
        },
        set(value) {
            if (value == 0) {
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                theme.setDark(!!prefersDark)
            } else {
                theme.setDark(value == 1)
            }
            localStorage.setItem('dark', value)
            themeChange.value++
        },
    })
</script>
