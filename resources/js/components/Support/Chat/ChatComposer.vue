<template>
    <footer class="chat-composer">
        <div v-if="error" class="chat-composer__error" role="alert">{{ error }}</div>
        <form class="chat-composer__form" @submit.prevent="$emit('submit')">
            <div class="chat-composer__editor-shell">
                <div
                    ref="editor"
                    class="chat-composer__editor"
                    :contenteditable="sending ? 'false' : 'plaintext-only'"
                    role="textbox"
                    aria-label="メッセージを入力"
                    aria-multiline="true"
                    :aria-disabled="sending"
                    data-placeholder="メッセージを入力..."
                    @input="updateValue"
                    @keydown="handleKeydown"
                />
            </div>
            <button
                type="submit"
                class="chat-composer__send"
                aria-label="送信"
                title="Alt + Enterで送信"
                :disabled="sending || !modelValue.trim()"
            >
                <svg viewBox="0 0 43 32" aria-hidden="true">
                    <path d="M40.638.087c-1.842.361-6.097 1.292-9.435 2.047L1.157 9.025c-.419.096-.793.374-1.003.793-.364.728-.058 1.585.663 2.007 2.578 1.521 10.077 5.56 10.077 5.56.287.157.487.439.542.762 0 0 .711 4.473.921 5.891.21 1.417.714 4.465 1.184 6.482.168.726.631 1.335 1.215 1.512.495.152 1.03.037 1.43-.285 1.394-1.128 5.787-5.445 7.388-7.272.133-.152.355-.19.531-.085l6.184 3.646s.439.294.919.519c1.283.601 2.479.625 3.062-.829.325-.813 4.316-12.627 4.316-12.627l4.466-13.209c.053-.152.082-.321.082-.492 0-.844-.654-1.675-2.496-1.312zM20.045 24.741c-.475.477-1.473 1.473-2.284 2.197-.155.137-.385-.002-.313-.195l1.796-4.842c.051-.157.236-.226.378-.142l1.796 1.054c.157.091.161.294.041.432-.401.458-.975 1.058-1.413 1.495zM32.151 25.117c-.106.325-.482.47-.777.301l-1.447-.824-3.554-2.014-7.121-4.024c-.067-.037-.138-.068-.214-.094-.677-.232-1.411.13-1.64.808l-1.944 7.086c-.053.166-.229.143-.251-.046-.13-1.23-.328-3.178-.467-4.759-.13-1.459-.366-3.357-.494-4.434-.111-.931-.427-1.423-1.131-1.837-.704-.415-6.489-3.354-7.668-4.049-.241-.142-.166-.415.065-.463 0 0 13.334-2.689 16.022-3.304 2.689-.617 10.513-2.447 10.513-2.447.103-.025.152.118.056.161l-5.127 2.281-2.961 1.459c-.987.487-7.32 3.516-9.259 4.562-.477.258-.665.871-.373 1.36.255.429.808.574 1.265.374 2.004-.882 16.208-7.766 17.651-8.441.345-.162.376-.012.287.049-.89.615-9.43 6.896-10.25 7.528l-2.448 1.905c-.432.342-.519.976-.173 1.42.335.432.965.497 1.413.183 0 0 3.766-2.665 4.603-3.274l5.008-3.66s5.775-4.365 6.187-4.682c.166-.128.397.033.331.234l-2.517 7.675-3.585 10.965z" />
                </svg>
            </button>
        </form>
        <div class="chat-composer__footnote">
            <span>AIの回答は参照元の社内規定・FAQとあわせて確認してください。</span>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'

const props = defineProps<{
    modelValue: string
    sending: boolean
    error: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
    submit: []
}>()

const editor = ref<HTMLElement | null>(null)

const editorText = () => editor.value?.innerText.replace(/\r\n?/g, '\n') ?? ''

const updateValue = () => {
    if (!editor.value) return

    let value = editorText()

    if (value.length > 4000) {
        value = value.slice(0, 4000)
        editor.value.textContent = value
        placeCaretAtEnd(editor.value)
    }

    emit('update:modelValue', value)
}

const handleKeydown = (event: KeyboardEvent) => {
    if (
        event.key === 'Enter'
        && event.altKey
        && !event.isComposing
        && props.modelValue.trim()
        && !props.sending
    ) {
        event.preventDefault()
        emit('submit')
    }
}

const placeCaretAtEnd = (element: HTMLElement) => {
    const selection = window.getSelection()
    const range = document.createRange()
    range.selectNodeContents(element)
    range.collapse(false)
    selection?.removeAllRanges()
    selection?.addRange(range)
}

watch(
    () => props.modelValue,
    async (value) => {
        await nextTick()
        if (editor.value && editorText() !== value) {
            editor.value.textContent = value
        }
    },
)
</script>

<style scoped lang="scss">
.chat-composer {
    box-sizing: border-box;
    flex-shrink: 0;
    padding: 10px clamp(14px, 3vw, 34px) 8px;
    background: var(--background-color);
    border-top: 1px solid color-mix(in srgb, var(--primary-color) 10%, transparent);
}

.chat-composer * {
    box-sizing: border-box;
}

.chat-composer__form {
    display: flex;
    width: min(100%, 820px);
    margin: 0 auto;
    align-items: flex-end;
    gap: 2px;
}

.chat-composer__editor-shell {
    position: relative;
    min-width: 0;
    flex: 1;
    padding: 5px 0 5px 10px;
    background: var(--background-color);
    border: 1px solid var(--third-color);
    border-radius: 5px;

    &:focus-within {
        border-color: var(--primary-color);
    }
}

.chat-composer__editor {
    width: calc(100% - 10px);
    min-height: 42px;
    max-height: 120px;
    padding: 6px 8px 6px 2px;
    color: var(--primary-color);
    outline: none;
    overflow-y: auto;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
    font-size: 13px;
    line-height: 1.65;

    &:empty::before {
        content: attr(data-placeholder);
        color: inherit;
        pointer-events: none;
        opacity: 0.38;
    }

    &[contenteditable='false'] {
        cursor: wait;
        opacity: 0.58;
    }
}

.chat-composer__send {
    display: grid;
    width: 36px;
    height: 36px;
    align-self: flex-end;
    flex-shrink: 0;
    place-items: center;
    color: var(--primary-color);
    background: transparent;
    border-radius: 5px;
    transition: opacity 0.15s;

    &:disabled {
        opacity: 0.2;
    }

    svg {
        width: 24px;
        fill: currentColor;
    }
}

.chat-composer__footnote {
    display: flex;
    width: min(100%, 820px);
    margin: 6px auto 0;
    justify-content: center;
    gap: 12px;
    font-size: 9px;
    opacity: 0.4;
}

.chat-composer__error {
    width: min(100%, 820px);
    margin: 0 auto 8px;
    padding: 9px 11px;
    background: var(--bg3);
    border-left: 2px solid var(--primary-color);
    font-size: 11px;
    line-height: 1.5;
}

</style>
