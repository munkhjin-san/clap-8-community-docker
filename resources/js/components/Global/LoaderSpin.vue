<template>
    <Transition name="modalFade">
        <div class="cal-month-loader" :style="{ top: top, height: height }" v-if="initialLoader">
            <div id="loaderMini">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </div>
    </Transition>
</template>
<script setup lang="ts">
const props = defineProps<{
    initialLoader: boolean
    top: string
    height: string
}>()
</script>