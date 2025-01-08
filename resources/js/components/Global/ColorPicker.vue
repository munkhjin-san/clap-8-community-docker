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
            type="HEX"
        />

    </div>
  </template>
  
  <script setup>
  import { Vue3ColorPicker } from '@cyhnkckali/vue3-color-picker';
  import { ref, onMounted, nextTick } from 'vue';
  
  const color = defineModel(); // Default color
  
  
  </script>
  
  <style scoped>
    .ck-cp-container {
        border-radius: 0 !important;
        box-shadow: none !important;
    }
  </style>