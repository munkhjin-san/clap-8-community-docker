<template>
    <div class="theme-ai-settings">
        <div class="theme-ai-settings__grid">
            <ItemSelector
                v-model="model"
                place-holder="モデル"
                :options="modelOptions"
                :multiple="false"
                :clearable="true"
                :close-on-select="true"
                :disabled="disabled"
            />
        </div>
        <div class="si-box">
            <LongInput
                v-model="instructions"
                :disabled="disabled"
                place-holder="指示文"
            />
        </div>
        <div class="si-box">
            <LongInput
                v-model="settingsJson"
                :disabled="disabled"
                place-holder='追加設定（{"temperature":0.3}）'
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import ItemSelector from '@/components/Form/ItemSelector.vue'
import LongInput from '@/components/Form/LongInput.vue';

defineProps<{
    disabled?: boolean
    settingsError?: string
    modelOptions: string[]
}>()

const model = defineModel<string | null>('model', { default: null })
const instructions = defineModel<string | null>('instructions', { default: null })
const settingsJson = defineModel<string>('settingsJson', { default: '' })
</script>

<style scoped>
.theme-ai-settings{
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.theme-ai-settings__header{
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.theme-ai-settings__header p{
    font-size: 15px;
    font-weight: 700;
    margin: 0;
}

.theme-ai-settings__header span,
.theme-ai-settings__field span{
    color: var(--light-color);
    font-size: 12px;
    line-height: 1.5;
}

.theme-ai-settings__grid{
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr);
}

.theme-ai-settings__field{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.theme-ai-settings__textarea{
    background: var(--background-color);
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
    font: inherit;
    line-height: 1.7;
    min-height: 220px;
    padding: 12px;
    resize: vertical;
    width: 100%;
}

.theme-ai-settings__textarea--small{
    min-height: 120px;
}

.theme-ai-settings__textarea--error{
    border-color: tomato;
}

.theme-ai-settings__error{
    color: tomato;
    font-size: 12px;
    margin: 0;
}
</style>
