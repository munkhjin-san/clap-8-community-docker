<template>
    <Modal size="medium" :loader="busy" @close="emit('close')">
        <template #title>
            <span>{{ title }}</span>
        </template>
        <template #content>
            <div class="partner-diff">
                <p class="partner-diff__message">{{ message }}</p>

                <div class="partner-diff__table">
                    <div class="partner-diff__row partner-diff__row--head">
                        <span>項目</span>
                        <span>当システム</span>
                        <span>freee</span>
                    </div>
                    <div v-for="diff in differences" :key="diff.field" class="partner-diff__row">
                        <span class="partner-diff__label">{{ diff.label }}</span>
                        <span>{{ displayValue(diff.local) }}</span>
                        <span>{{ displayValue(diff.freee) }}</span>
                    </div>
                </div>

                <div v-if="resolvable" class="partner-diff__buttons">
                    <button type="button" class="partner-diff__button" :disabled="busy" @click="emit('keep-local')">
                        こちらの内容でfreeeを上書き
                    </button>
                    <button type="button" class="partner-diff__button" :disabled="busy" @click="emit('keep-freee')">
                        freeeの内容を取り込む
                    </button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue'
import type { PartnerFieldDifference } from '@/interface/partnerInterface'

defineProps<{
    title: string
    message: string
    differences: PartnerFieldDifference[]
    resolvable: boolean
    busy: boolean
}>()

const emit = defineEmits(['close', 'keep-local', 'keep-freee'])

const displayValue = (value: unknown) => {
    if (value === null || value === undefined || value === '') return '（空）'
    if (typeof value === 'boolean') return value ? '使用中' : '使用不可'
    return String(value)
}
</script>

<style scoped>
.partner-diff {
    display: flex;
    flex-direction: column;
    gap: 16px;
    font-size: 13px;
}

.partner-diff__message {
    line-height: 1.6;
}

.partner-diff__table {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--calendarBorder);
    max-height: 50vh;
    overflow: auto;
}

.partner-diff__row {
    display: grid;
    grid-template-columns: 140px 1fr 1fr;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid var(--calendarBorder);
    line-height: 1.5;
    word-break: break-all;
}

.partner-diff__row:last-child {
    border-bottom: 0;
}

.partner-diff__row--head {
    position: sticky;
    top: 0;
    background: var(--bg3);
    color: var(--third-color);
    font-size: 12px;
}

.partner-diff__label {
    color: var(--third-color);
}

.partner-diff__buttons {
    display: flex;
    gap: 10px;
}

.partner-diff__button {
    padding: 8px 14px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
}

.partner-diff__button:disabled {
    color: var(--third-color);
    cursor: not-allowed;
    opacity: 0.6;
}
</style>
