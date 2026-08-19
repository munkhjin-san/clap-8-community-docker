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
    margin: 0 0 14px;
    min-height: 620px;
    overflow: hidden;
    width: 100%;
}

/* The element's shadow root holds formatting whitespace around its <iframe>.
   .section-wrapper's `white-space: break-spaces` and `line-height: 1.8` inherit
   through the shadow boundary, so those newlines render as blank lines and push
   the inline iframe down (~86px) — pushing an equal amount of the PDF past the
   wrapper, where overflow:hidden clips it. Neutralise the inherited text
   formatting so the iframe sits flush at the top. */
.learning-pdf-viewer__element {
    display: block;
    height: 100%;
    width: 100%;
    white-space: normal;
    line-height: 0;
    font-size: 0;
}

@media screen and (max-width: 720px) {
    .learning-pdf-viewer {
        height: 75vh;
        min-height: 420px;
    }
}
</style>
