<script lang="ts" setup>
import { computed, onMounted } from 'vue'
import type { EdgeProps } from '@vue-flow/core'
import { BaseEdge, EdgeLabelRenderer, getBezierPath, useVueFlow } from '@vue-flow/core'

const props = defineProps<EdgeProps>()

const path = computed(() => {
    let [path, labelX, labelY, c1x, c1y] = getBezierPath(props)
    return path;
});
</script>

<script lang="ts">
export default {
  inheritAttrs: false,
}
</script>

<template>
  <BaseEdge :path="path"/>
</template>

<style>
.edgebutton {
  border-radius: 999px;
  cursor: pointer;
}

.edgebutton:hover {
  box-shadow: 0 0 0 2px pink, 0 0 0 4px #f05f75;
}
</style>
