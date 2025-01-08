<template>
    <div>
        <Vue3ColorPicker 
            v-model="color" 
            mode="solid" 
            :showColorList="false" 
            :showEyeDrop="true"
            :showInputMenu="false" 
            :show-alpha="false"
            :show-picker-mode="false"
            :theme="darkLight"
            type="HEX"
        />

    </div>
  </template>
  
  <script setup>
  import { useTheme } from '@/store/theme';
import { Vue3ColorPicker } from '@cyhnkckali/vue3-color-picker';
import { computed } from 'vue';
  
  const color = defineModel(); 
  const theme = useTheme()
  const darkLight = computed(() => {
    return theme.dark ? 'dark' : 'light'
  })
  </script>
  
  <style scoped>
    .ck-cp-container {
        border-radius: 0 !important;
        box-shadow: none !important;
    }
  </style>