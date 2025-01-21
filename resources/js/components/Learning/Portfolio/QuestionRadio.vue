<template>
    <div class="flex flex-col gap-5">
      <label :for="questionId" class="mb-4" style="font-size: 16px; font-weight: 600;">{{ question }}</label>
      
      <div v-for="(answer, index) in answers" :key="index" class="flex gap-2.5 align-center">
        <input 
          class="fish-eye" 
          :id="`${questionId}-${index + 1}`" 
          type="radio" :value="index" 
          v-model="selectedAnswer" 
          @change="handleChange(index)"
          >
        <label :for="`${questionId}-${index + 1}`">{{ answer }}</label>
      </div>
      <span v-if="!selectedAnswer && showError" class="form-error" style="font-size: 11px; color: tomato;">
        {{ radioError }}
      </span>

    </div>
</template>
  
  <script setup>
    import { ref, watch } from 'vue';
    const props = defineProps(['questionId', 'question', 'answers', 'errorMessage', 'answer', 'showError'])
    const emit = defineEmits(['setValue', 'validationError'])
    const selectedAnswer = ref(props.answer ?? null)
    
    const radioError = ref(props.errorMessage ? props.errorMessage : '必須です。')

    const handleChange = (value) => {
      emit('setValue', value)
      emit('validationError', false)
    }

    watch(() => props.showError, (newVal) => {
      if (newVal && !selectedAnswer.value) {
        emit('validationError', true)
      }
    })
  </script>

  