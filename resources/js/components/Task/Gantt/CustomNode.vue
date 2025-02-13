<script lang="ts" setup>
import { Task } from '@/interface/globalInterface';
import { Handle, Position } from '@vue-flow/core'
import { onMounted, ref } from 'vue'
import GanttTaskBody from './GanttTaskBody.vue';
const props = defineProps<{
  data: {
    task: Task
  }
}>()

onMounted(() => {
  console.log('CustomNode mounted', props.data.task)
})
</script>

<template>
  <div class="custom-node">
    <Handle type="target" :position="Position.Top" />
    <!-- <div>{{props.data.task.remarks}}</div> -->

  </div>
</template>

<style>
.custom-node {
  min-width: 100px;
  gap: 4px;
  padding: 8px;
  background: white;
  border: 1px solid black;
  border-radius: 4px;
}

.increment {
  border-radius: 4px;
  background: #42b983;
  font-size: 10px;
  color: #fff;
  cursor: pointer;
  border: none;
}

.increment:hover {
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

.counter {
  margin-top: 8px;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 4px;
}

.count {
  font-size: 6px;
  color: #ff0072;
  border: 1px solid rgba(0, 0, 0, 0.3);
  border-radius: 8px;
}
</style>
