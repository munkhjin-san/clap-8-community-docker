<template>
    <Modal
        size="large"
        disable-scroll
        custom-class=""
        body-style="width: 100%; height: 100%; padding: 0;"
        @close="emit('close')"
    >
        <template #title>
            <strong class="learning-presentation__title">{{ presentation.title }}</strong>
        </template>
        <template #content>
            <div class="learning-presentation__content">
                <iframe
                    class="learning-presentation__frame"
                    :srcdoc="safePresentationHtml"
                    :title="presentation.title"
                    sandbox=""
                    referrerpolicy="no-referrer"
                ></iframe>
                <footer
                    v-if="discussionThemes.length === 3"
                    class="learning-presentation__discussion-selector"
                >
                    <p class="learning-presentation__discussion-prompt">
                        <strong>ディスカッション</strong>テーマを選択してください
                    </p>
                    <div class="learning-presentation__discussion-actions">
                        <button
                            v-for="theme in discussionThemes"
                            :key="theme.number"
                            type="button"
                            class="learning-presentation__discussion-button"
                            @click="selectDiscussionTheme(theme.text)"
                        >
                            テーマ{{ theme.number }}
                        </button>
                    </div>
                </footer>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import DOMPurify from 'dompurify'
import Modal from '@/components/Global/Modal.vue'
import type { LearningHtmlPresentationSpec } from '@/types/learning'

const props = defineProps<{
    presentation: LearningHtmlPresentationSpec
    accentColor?: string | null
}>()

const emit = defineEmits<{
    close: []
    selectDiscussionTheme: [content: string]
}>()

const sanitizedPresentationHtml = computed(() => {
    return DOMPurify.sanitize(props.presentation.html, {
        WHOLE_DOCUMENT: true,
        ADD_TAGS: ['style'],
        FORBID_TAGS: [
            'script',
            'iframe',
            'object',
            'embed',
            'link',
            'meta',
            'base',
            'form',
            'input',
            'button',
            'textarea',
            'select',
            'img',
            'video',
            'audio',
            'canvas',
            'math',
            'foreignObject',
        ],
        FORBID_ATTR: [
            'src',
            'srcset',
            'href',
            'action',
            'formaction',
            'poster',
        ],
    })
})

const discussionThemes = computed(() => {
    const document = new DOMParser().parseFromString(
        sanitizedPresentationHtml.value,
        'text/html',
    )

    return [1, 2, 3].flatMap((number) => {
        const theme = document.querySelector(
            `#group-discussion .discussion-theme[data-theme-number="${number}"]`,
        )
        if (!theme) return []

        const textContainer = theme.cloneNode(true) as HTMLElement
        textContainer.querySelectorAll('br').forEach(element => element.replaceWith('\n'))
        textContainer
            .querySelectorAll('h1, h2, h3, h4, p, li, blockquote')
            .forEach(element => element.append('\n'))

        const text = (textContainer.textContent ?? '')
            .replace(/\u00a0/g, ' ')
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n[ \t]+/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim()

        return text ? [{ number, text }] : []
    })
})

const selectDiscussionTheme = (content: string) => {
    emit('selectDiscussionTheme', content)
}

const safePresentationHtml = computed(() => {
    const requestedAccentColor = props.accentColor
    const accentColor = typeof requestedAccentColor === 'string'
        && /^#[0-9a-f]{6}$/i.test(requestedAccentColor)
        ? requestedAccentColor
        : '#dedede'
    const securityPolicy = `
        <meta
            http-equiv="Content-Security-Policy"
            content="default-src 'none'; style-src 'unsafe-inline'; img-src data:; font-src data:;"
        >
    `
    const presentationGuardrails = `
        <style>
            :root { --accent: ${accentColor} !important; }
            html {
                background: #e7e7e7 !important;
                min-height: 100% !important;
                overflow-x: hidden !important;
            }
            body {
                margin: 0 !important;
                background: #e7e7e7 !important;
                width: 100% !important;
                min-height: 100% !important;
                overflow-x: hidden !important;
                overflow-y: auto !important;
            }
            main.story {
                display: block !important;
                box-sizing: border-box !important;
                width: 100% !important;
                min-height: 100% !important;
                margin: 0 !important;
                overflow: visible !important;
            }
            main.story > section.scene {
                box-sizing: border-box !important;
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                max-height: none !important;
                overflow-x: hidden !important;
                overflow-y: visible !important;
            }
        </style>
    `
    const htmlWithSecurityPolicy = sanitizedPresentationHtml.value.replace(
        /<head(\s[^>]*)?>/i,
        match => `${match}${securityPolicy}`,
    )

    return htmlWithSecurityPolicy.replace(
        /<\/head>/i,
        `${presentationGuardrails}</head>`,
    )
})

</script>

<style scoped>
:deep(.learning-presentation-modal) {
    width: min(1400px, 94vw) !important;
    height: 92vh !important;
    color: #151515;
    background: #f1f1f1 !important;
    box-shadow: 0 28px 90px rgb(0 0 0 / 35%);
}

.learning-presentation__title {
    min-width: 0;
    overflow: hidden;
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.learning-presentation__content {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: var(--background-color);
}

.learning-presentation__frame {
    display: block;
    flex: 1;
    min-height: 0;
    width: 100%;
    border: 0;
    background: var(--background-color);
}

.learning-presentation__discussion-selector {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 14px 24px;
    border-top: 1px solid #b8b8b8;
    background: var(--background-color);
    color: var(--primary-color);
}

.learning-presentation__discussion-prompt {
    margin: 0;
    color: var(--primary-color);
    font-size: 13px;
    line-height: 1.5;
}

.learning-presentation__discussion-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.learning-presentation__discussion-button {
    min-width: 88px;
    padding: 9px 14px;
    border: 1px solid var(--primary-color);
    background: transparent;
    color: var(--primary-color);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 150ms ease, color 150ms ease;
}

.learning-presentation__discussion-button:hover,
.learning-presentation__discussion-button:focus-visible {
    background: var(--primary-color);
    color: var(--background-color);
}

@media (max-width: 760px) {
    :deep(.learning-presentation-modal) {
        width: 100%;
        height: 100%;
    }

    .learning-presentation__discussion-selector {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
        padding: 12px 16px;
    }

    .learning-presentation__discussion-actions {
        width: 100%;
    }

    .learning-presentation__discussion-button {
        flex: 1;
        min-width: 0;
    }
}
</style>
