<template>
    <div class="flex flex-col gap-2.5">
      <label :for="questionId" style="font-size: 16px; font-weight: 600;">{{ question }}</label>
      
      <div v-for="(answer, index) in answers" :key="index" class="flex gap-2.5 align-center">
        <input class="fish-eye" :id="`${questionId}-${index + 1}`" type="radio" :value="index" v-model="selectedAnswer" @change="emit('setValue', index)">
        <label :for="`${questionId}-${index + 1}`">{{ answer }}</label>
      </div>
      <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedAnswer ? "" : radioError }}</span>

    </div>
</template>
  
  <script setup>
    import { ref, watch } from 'vue';
    const props = defineProps(['questionId', 'question', 'answers', 'errorMessage', 'answer'])
    const emit = defineEmits(['setValue'])
    const selectedAnswer = ref(props.answer ?? null)
    
    const radioError = ref(props.errorMessage ? props.errorMessage : '')
  </script>

  