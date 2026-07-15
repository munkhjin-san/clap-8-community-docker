<template>
    <div class="learning-pdf-viewer">
        <pdfjs-viewer-element
            ref="viewerElement"
            class="learning-pdf-viewer__element"
            viewer-path="/pdf-reader"
            locale="ja"
            :page="1"
            zoom="page-fit"
        ></pdfjs-viewer-element>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import 'pdfjs-viewer-element'

const props = defineProps<{
    src: string
}>()

const viewerElement = ref<any>(null)
let instance: any = null

const openPdf = async() => {
    if (!viewerElement.value) return

    if (!instance) {
        instance = await viewerElement.value.initialize()
    }

    await instance?.open({ url: props.src })
}

onMounted(() => {
    void openPdf()
})

watch(() => props.src, () => {
    void openPdf()
})

onUnmounted(() => {
    instance?.pdfDocument?.destroy()
    instance = null
})
</script>

<style scoped>
.learning-pdf-viewer {
    height: calc(100vh - 120px);
    margin: 14px 0;
    min-height: 620px;
    overflow: hidden;
    width: 100%;
}

.learning-pdf-viewer__element {
    display: block;
    height: 100%;
    width: 100%;
}

@media screen and (max-width: 720px) {
    .learning-pdf-viewer {
        height: 75vh;
        min-height: 420px;
    }
}
</style>
