<template>

    <div v-if="info" class="mini-info">    
        <span>{{ info }}</span>
    </div>  
    <div v-else class="cu-toast-mask">
        <div ref="cuToastCont" class="cu-toast-container">
            <div class="cu-toast-inner">
                <div v-if="confirm || notify">
                    <div v-html="confirm || notify"></div>
                </div>
                <div v-if="input" class="mt-3">
                    <!-- <label class="text-xs">{{ input.label || 'ダ名' }}</label> -->
                    <textarea
                        v-if="input.multiline"
                        ref="inputEl"
                        v-model="inputValue"
                        class="cu-text-input cu-textarea mt-1"
                        :placeholder="input.placeholder || ''"
                        rows="4"
                        @keydown.stop
                        @keydown.esc.prevent="emit('close')"
                    ></textarea>
                    <input
                        v-else
                        ref="inputEl"
                        v-model="inputValue"
                        type="text"
                        class="cu-text-input mt-1"
                        :placeholder="input.placeholder || ''"
                        @keydown.stop
                        @keydown.enter.prevent="options?.answers?.length ? sendAnswer(options.answers[0], 0) : submitInput()"
                        @keydown.esc.prevent="emit('close')"
                    />
                    <p v-if="showError" class="i-error">{{ inputError }}</p>
                </div>
                <div style="display:flex;gap:20px;justify-content: space-evenly;margin: 20px 0 0 0;">
                    <template v-if="options?.answers?.length">
                        <div
                            @click="sendAnswer(answer, index)"  
                            :key="index" 
                            v-for="(answer, index) in options?.answers"
                            :style="{transform: `scale(${selected === index ? '1.3': '1'})`}" 
                            class="cu-answer-button"
                        >
                            {{ answer.label }}
                        </div>
                    </template>
                    <template v-else-if="input">
                        <div class="cu-answer-button"
                            :class="{'opacity-60 pointer-events-none': !!inputError}"
                            @click="submitInput()">
                        {{ input.submitText || 'OK' }}
                        </div>
                        <div class="cu-answer-button" @click="emit('close')">Cancel</div>
                    </template>
                </div>               
            </div>
        </div>
    </div>

</template>


<script setup lang="ts">
import {  ref, computed, watch, useTemplateRef } from 'vue';
import { Answer, ConfirmOptions } from '@/interface/globalInterface'
type InputOptions = {
    value?: string;
    placeholder?: string;
    label?: string;
    submitText?: string;
    required?: boolean;
    selectBaseName?: boolean;
    multiline?: boolean;
    maxLength?: number;
    validate?: (v:string) => string | null;
}
interface Props {
    confirm: string | null;
    notify: string | null;
    info: string | null;
    options: ConfirmOptions | null;
    input?: InputOptions | null;
}
const props = defineProps<Props>()
const cuToastCont = ref(null)
const selected = ref()
const emit = defineEmits<{
    (e: 'close'): void
    (e: 'handle', answer: Answer): void
    (e: 'submit', payload: { input: string, answer?: Answer }): void
}>()

const sendAnswer = (answer: Answer, index: number) => {
  selected.value = index;
  attempted.value = true;

  if (props.input && inputError.value && !isCancel(answer)) {
    setTimeout(() => {
      selected.value = null
    }, 150)
    return
  };
  emit('handle', answer); // keep legacy event
  emit('submit', { input: (inputValue.value ?? '').trim(), answer });
  setTimeout(() => emit('close'), 50);
}

const inputEl = useTemplateRef('inputEl')
const inputValue = ref<string>(props.input?.value ?? '');
const dirty = ref(false);
const attempted = ref(false);

const isCancel = (answer: Answer | undefined) => {
  if (typeof answer?.value === 'boolean' && answer.value === false) return true;
  const label = String(answer?.label || '').toLowerCase();
  return label === 'cancel' || label === 'キャンセル';
}
const inputError = computed(() => {
  if (!props.input) return null;
  const v = (inputValue.value ?? '').trim();
  if (props.input.required && !v) return '入力してください。';
  // Multiline free-text (e.g. reasons): skip the filename-specific rules below.
  if (props.input.multiline) {
    const max = props.input.maxLength ?? 2000;
    if (v.length > max) return `${max}文字以内で入力してください。`;
    return props.input.validate ? props.input.validate(v) : null;
  }
  if (v.length > 255) return '名前が長すぎます。';
  if (/[\\/:*?"<>|]/.test(v)) return '使用できない文字が含まれています（\\ / : * ? " < > |）。';
  if (v === '.' || v === '..') return '「.」や「..」は使用できません。';
  return props.input.validate ? props.input.validate(v) : null;
});
const showError = computed(() => !!inputError.value && (dirty.value || attempted.value));
const submitInput = (answer?: Answer) => {
  attempted.value = true;

  if (props.input && inputError.value && !isCancel(answer)) return;
  emit('submit', { input: (inputValue.value ?? '').trim(), answer });
  emit('close');
}
watch(inputValue, () => { dirty.value = true; });
watch(() => props.input, async(inp) => {
  if (!inp) return;
  inputValue.value = inp.value ?? '';
  queueMicrotask(() => {
    const el = inputEl.value as HTMLInputElement | HTMLTextAreaElement | null;
    el?.focus();
    if (!el) return;
    if (inp.selectBaseName && el instanceof HTMLInputElement) {
      const v = inputValue.value ?? '';
      const dot = v.lastIndexOf('.');
      const end = dot > 0 ? dot : v.length;
      el.setSelectionRange(0, end);
    } else {
      el.select();
    }
  });
}, { immediate: true, flush: 'post' });
</script>
<style lang="scss" scoped>
$primary: #626262;
$secondary: #fff;
.cu-text-input {
  border: 1px solid var(--primary-color);
  color: var(--primary-color);
  padding: 8px 12px;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
  box-sizing: border-box;
}
.cu-text-input::placeholder { color: #999; }

.cu-textarea {
  width: 320px;
  max-width: 100%;
  min-height: 110px;
  resize: vertical;
  font-family: inherit;
  line-height: 1.5;
}


.cu-toast-container {
font-size: 14px;
background: var(--background-color);
max-width: 40%;
line-height: 1.5;
white-space: break-spaces;
color:var(--primary-color);
fill:var(--primary-color);
}

.cu-toast-inner {
position: relative;
padding: 20px;
}

.toast-close-button {
position: absolute;
right: 5px;
top: 5px;
border-radius: 50px;
width: 20px;
height: 20px;
display: flex;
background-color: inherit;
cursor: pointer;
color: $secondary;
transition: background-color 0.2s, color 0.2s;
}

.toast-close-button:hover {
background-color: var(--primary-color);
fill: var(--background-color);
}

.toast-close-button>svg {
width: 10px;
height: 10px;
transition: fill 0.2s;
margin: auto;
}

.cu-answer-button {
background-color: var(--primary-button);
color: #ffffff;
padding: 10px 20px;
cursor: pointer;
font-size: 13px;
transition: transform 0.1s;
}

.cu-toast-mask {
position: fixed;
width: 100%;
height: 100%;
background: var(--overlay);
display: flex;
align-items: center;
justify-content: center;
flex-direction: column;
z-index: 54;
left: 0;
top: 0;
}
.mini-info {
position: fixed;
right: 0;
top: 10px;
background-color: green;
display: flex;
gap: 5px;
padding: 10px;
align-items: center;
z-index: 105;
color: #fff;
font-size: 13px;
left: 0;
width: -moz-fit-content;
width: fit-content;
margin: 0 auto;
white-space: nowrap;
}
@media screen and (max-width: 959px) {
.cu-toast-container {
    max-width: 90%;

}
}
</style>
