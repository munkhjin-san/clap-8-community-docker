<template>
    <div class="tts-player">
        <div v-if="!isPlaying || hasError" @click="handlePlay" :disabled="!text || isLoading"
            class="tts-btn tts-btn-play" title="読み上げる">
            <svg v-if="!isLoading && (isIdle)" :style="{fill: color}" class="m-auto" xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                viewBox="0 0 32.57 26.53">
                <path class="cls-1"
                    d="M12.49,1.31l-3.5.03h-.03c-.4,0-.78.2-1.01.56l-1.71,2.6c-.46.7-.92,1.39-1.37,2.09-1.12.02-2.69.06-3.56.07-.48.03-.79.28-.95.59-.11.13-.18.29-.2.5-.1,1.79-.14,3.59-.14,5.38.01,1.76.03,3.58.17,5.33,0,.01,0,.02,0,.03,0,.63.49,1.15,1.12,1.16,1.18.02,2.37.04,3.55.05.56.84,3.09,4.67,3.09,4.67.22.33.6.55,1.03.56.02,0,3.5.02,3.52.03.72,0,1.3-.58,1.29-1.29,0-1.76.03-3.51.03-5.27.01-4.9.02-10.95-.03-15.83,0-.71-.58-1.28-1.29-1.27ZM6.48,17.82c-.22-.31-.57-.51-.98-.5-.87,0-2.35-.05-3.09-.09-.25-.01-.44-.16-.45-.48-.05-1.31-.08-5.53-.02-7.13.02-.42.31-.64.7-.66l2.86-.03c.38,0,.76-.18.98-.52.86-1.22,2-2.86,2.96-4.24.13-.19.35-.3.58-.3.25,0,.51,0,.87,0,.16,0,.29.13.29.3,0,1.23-.02,2.46-.02,3.69-.01,4.36-.01,9.63.01,14.17,0,.19-.16.35-.35.35h-.78c-.25,0-.48-.11-.62-.32-1.02-1.46-2.94-4.23-2.95-4.25Z" />
                <path class="cls-1"
                    d="M30.96,5.82c-.65-1.41-1.51-2.73-2.5-3.93-.6-.71-1.23-1.41-2.08-1.83-.53-.27-1.11.31-.84.84.34.76.9,1.47,1.36,2.15.74,1.15,1.37,2.41,1.82,3.69,1.78,5.13,1.38,11.03-1.41,15.73-.57.95-1.25,1.82-1.93,2.72-.23.29-.24.71,0,1.01.28.36.8.43,1.16.15.98-.76,1.79-1.7,2.53-2.7,3.81-5.02,4.64-12.13,1.9-17.83Z" />
                <path class="cls-1"
                    d="M25.26,8.18c-.46-.95-1.07-1.82-1.76-2.61-.42-.48-.86-.94-1.49-1.14-.51-.18-1.02.34-.84.84.13.54.48,1.01.77,1.46l.26.46c.98,1.82,1.42,3.9,1.42,5.94-.02,2.03-.43,4.12-1.45,5.92-.35.62-.78,1.18-1.17,1.8-.48.75.42,1.64,1.17,1.14.69-.47,1.25-1.1,1.76-1.77.51-.67.96-1.38,1.34-2.15,1.54-3.05,1.52-6.85,0-9.9Z" />
                <path class="cls-1"
                    d="M17.66,8.79c-.41,0-.76.3-.82.71-.03.15-.04.34-.02.46.02.16.11.32.17.45.58,1.39.56,2.92.32,4.38-.1.68-.47,1.14-.69,1.78-.24.7.51,1.38,1.19,1.06.4-.19.7-.5.99-.83.28-.33.55-.69.77-1.08,1.17-1.96.79-4.63-.79-6.25-.33-.32-.59-.7-1.12-.69Z" />
            </svg>
            <div v-if="isLoading" id="loaderMini">
                <div class="spinner-nano" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
            </div>
        </div>

        <!-- Pause Button - shows when playing -->
        <div v-if="isPlaying" @click="handlePause" class="tts-btn tts-btn-pause" title="一時停止">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18"
                :style="{fill: color}">
                <rect height="40" width="10" y="5" x="10"></rect>
                <rect height="40" width="10" y="5" x="30"></rect>
            </svg>
        </div>

        <!-- Resume Button - shows when paused -->
        <div v-if="isPaused" @click="handleResume" class="tts-btn tts-btn-resume" title="再生再開">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18"
                :style="{fill: color}">
                <polygon points="10,5 40,25 10,45"></polygon>
            </svg>
        </div>

        <!-- Stop Button - shows when playing or paused -->
        <div v-if="isPlaying || isPaused" @click="handleStop" class="tts-btn h-[15px] w-[15px] min-w-[15px] bg-[tomato]" title="停止"></div>
        <!-- Error Message -->
        <div v-if="hasError && showError" class="tts-error">
            <span>{{ error?.message || 'Playback failed' }}</span>
            <button @click="showError = false" class="tts-error-close">&times;</button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import { useTTS } from '@/composables/useTTS'

interface Props {
    text: string
    apiUrl?: string
    autoPlay?: boolean
    showError?: boolean
    color?: string
}

const props = withDefaults(defineProps<Props>(), {
    apiUrl: '/tts_stream',
    autoPlay: false,
    showError: true,
    color: 'var(--primary-color)',
})

interface Emits {
    (e: 'play'): void
    (e: 'pause'): void
    (e: 'resume'): void
    (e: 'stop'): void
    (e: 'complete'): void
    (e: 'error', error: Error): void
}

const emit = defineEmits<Emits>()

const showError = ref(props.showError)
const showInteractionPrompt = ref(false)

const {
    status,
    error,
    progress,
    isIdle,
    isLoading,
    isPlaying,
    isPaused,
    hasError,
    start,
    pause,
    resume,
    stop,
} = useTTS({
    apiUrl: props.apiUrl,
    onComplete: () => {
        showInteractionPrompt.value = false
        emit('complete')
    },
    onError: (err) => {
        showInteractionPrompt.value = false
        showError.value = true
        emit('error', err)
    },
    onUserInteractionRequired: () => {
        showInteractionPrompt.value = true
    },
})

const handlePlay = async () => {
    if (!props.text) return
    showError.value = false
    await start(props.text)
    emit('play')
}

const handlePause = () => {
    pause()
    emit('pause')
}

const handleResume = () => {
    resume()
    emit('resume')
}

const handleStop = () => {
    stop()
    showInteractionPrompt.value = false
    emit('stop')
}

// Auto-play when text changes (if enabled)
watch(() => props.text, (newText, oldText) => {
    if (props.autoPlay && newText && newText !== oldText) {
        // Stop current playback first
        if (isPlaying.value || isPaused.value) {
            stop()
        }
        // Start new playback
        setTimeout(() => {
            handlePlay()
        }, 100)
    }
})

// Cleanup on unmount
onBeforeUnmount(() => {
    stop()
    showInteractionPrompt.value = false
    showError.value = false
})
</script>

<style scoped>
button{
    background: inherit !important;
}
.tts-player {
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    height: 100%;
}

.tts-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}



.tts-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}


.tts-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
}

.tts-progress-bar {
    height: 100%;
    background-color: #4CAF50;
    transition: width 0.3s ease;
    border-radius: 3px;
}

.tts-error {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 8px;
    padding: 8px 12px;
    background-color: #ffebee;
    border: 1px solid #f44336;
    border-radius: 6px;
    color: #c62828;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    white-space: nowrap;
    z-index: 10;
    min-width: 200px;
}

.tts-error-close {
    background: none;
    border: none;
    color: #c62828;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 3px;
}

.tts-interaction-prompt {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 8px;
    padding: 8px 12px;
    background-color: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 6px;
    color: #856404;
    font-size: 12px;
    text-align: center;
    z-index: 10;
    white-space: nowrap;
}


</style>