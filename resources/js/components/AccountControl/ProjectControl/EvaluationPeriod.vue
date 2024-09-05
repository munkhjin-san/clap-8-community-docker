<template>
    <div>
        <div>

            <FloatButton @action="createWindow = true" type="plus"/>
        </div>
        <Transition name="modalFade">
            <CreatePeriod
                v-if="createWindow"
                @close="createWindow = false"
            />
        </Transition>
        
    </div>
</template>
<script lang="ts" setup>
import { ref } from 'vue';
import CreatePeriod from './CreatePeriod.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
const createWindow = ref(false)
</script>