<template>
    <div class="flex flex-col gap-5">
      <label :for="String(questionId)" class="mb-4" style="font-size: 16px; font-weight: 600;">{{ question }}</label>
      
      <div v-for="(answer, index) in answers" :key="index" class="flex gap-2.5 align-center">
        <input 
          class="new-radio" 
          :id="`${questionId}-${index + 1}`" 
          type="radio" :value="index" 
          v-model="selectedAnswer" 
          @change="handleChange(index)"
          >
        <label :for="`${questionId}-${index + 1}`">{{ answer }}</label>
      </div>
      <span v-if="selectedAnswer === null && showError" class="form-error" style="font-size: 11px; color: tomato;">
        {{ radioError }}
      </span>

    </div>
</template>
  
  <script setup lang="ts">
    import { ref, watch } from 'vue';
    const props = defineProps<{
      questionId: string | number
      question?: string | null
      answers: string[]
      errorMessage?: string
      answer?: number | null
      showError?: boolean
    }>()
    const emit = defineEmits<{
      setValue: [value: number]
      validationError: [value: boolean]
    }>()
    const selectedAnswer = ref<number | null>(props.answer ?? null)
    
    const radioError = ref(props.errorMessage ? props.errorMessage : '必須です。')

    const handleChange = (value: number) => {
      emit('setValue', value)
      emit('validationError', false)
    }

    watch(() => props.showError, (newVal) => {
      if (newVal && selectedAnswer.value === null) {
        emit('validationError', true)
      }
    })
  </script>

  
