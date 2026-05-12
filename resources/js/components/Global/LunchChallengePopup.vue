<template>
    <Transition name="modalFade">
        <div v-if="visible" class="lunch-challenge-overlay">
            <div class="p-10 flex flex-col gap-5">
                <div class="challenge-top">
                    <div class="avatar-and-bubble">
                        <div class="image-wrap">
                            <img
                                class="oikawa-normal"
                                :class="{ 'is-visible': imageVisible }"
                                src="/images/minisuke.webp"
                                alt=""
                            />
                        </div>

                        <div class="bubble">
                            <p class="text-sm">{{ message }}</p>
                        </div>
                    </div>

                    <div class="close-wrap" @click="emit('close')">
                        <CloseIcon size="10"/>
                    </div>
                </div>
                
                <div class="lunch-challenge-modal-wrapper">
                    <div @click="createChallenge(challenge)" class="lunch-challenge-modal" v-for="challenge in challenge.generated_challenges" @click.stop>
                        <!-- <div class="lunch-challenge-header">
                            <div>
                                <p class="text-[18px]">チャレンジしてみませんか？</p>
                            </div>
                            <div @click="emit('close')" class="cursor-pointer">
                                <CloseIcon size="12"/>
                            </div>
                            
                        </div> -->
                        <div  class="lunch-challenge-intro">
                            <!-- <div class="challenge-icon">
                                <Easy v-if="challenge.challenge_difficult == 'easy'" size="32"/>
                                <Normal v-if="challenge.challenge_difficult == 'normal'" size="32"/>
                                <Hard v-if="challenge.challenge_difficult == 'hard'" size="32"/>
                            </div> -->
                            <p>{{ challenge.title }}</p>
                        </div>
                        
                    
                        
                        
                    </div>
                    <PostCreate 
                            v-if="createWindow"
                            app-name="post"
                            app-name-jp="ポスト"
                            :edit-target="editTarget"
                            :popup="true"
                            :get-query="{
                                id: null,
                                search_tags: null,
                                member: null,
                                app_type: null,
                            }"
                            @post-finish="finishPopup"
                        />
                </div>
                
                
            </div>
        </div>
        
    </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import PostCreate from '../Post/PostCreate.vue'
import { DateTime } from 'luxon'
import { useAuthUserStore } from '@/store/auth'
import { nextTick } from 'vue'
import CloseIcon from '../Form/CloseIcon.vue'

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    challenge: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
})
const challengeMessages = [
    "本日のミニチャレンジ",
    "今日のミニチャレンジ",
    "今日の一題（ミニ）",
    "今日のミニ、やる？",
    "ミニチャレンジ",
    "今日の一歩（ミニ）",
    "今日のミニ",
    "まずはミニ",
    "ミニをチェック",
    "準備OK？ミニ"
];

const message = ref(
  challengeMessages[Math.floor(Math.random() * challengeMessages.length)]
);
const emit = defineEmits(['close'])

const createWindow = ref(false)

const imageVisible = ref(false)
const auth = useAuthUserStore()

const editTarget = ref({
    date_start: '',
    date_end: '',
    app_type: 2,
    to_users: [auth.user]
})
let imageTimer = null
watch(
    () => props.visible,
    async (visible) => {
        if (imageTimer) clearTimeout(imageTimer)

        if (!visible) {
            imageVisible.value = false
            return
        }

        imageVisible.value = false

        await nextTick()

        imageTimer = setTimeout(() => {
            imageVisible.value = true
        }, 1200)
    }
)

const createChallenge = (challenge) => {
    editTarget.value = {
        title: challenge.title,
        content_rule: challenge.content_rule,
        content_goal: challenge.achievement_condition,
        challenge_main_category: challenge.main_category,
        date_start: DateTime.now().toISODate(),
        date_end: DateTime.now().plus({days : 7}).toISODate(),
        app_type: 2,
        to_users: [auth.user],
        mini: true
    }
    createWindow.value = true
    
}
const finishPopup = (flag, id) => {
    createWindow.value = flag
    if (id) {
        emit('close')
    }
}

</script>

<style scoped>
.challenge-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    min-width: 320px;
}

.avatar-and-bubble {
    display: flex;
    align-items: center;
    gap: 20px;
    min-width: 0;
    flex: 1;
}

.bubble {
    position: relative;
    max-width: 280px;
    padding: 12px 16px;
    background: var(--background-color);
    border: 1px solid var(--calendarBorder);
    border-radius: 12px;
    color: var(--primary-color);
    font-size: 14px;
    line-height: 1.5;
}

.close-wrap {
    flex-shrink: 0;
    cursor: pointer;
}

.bubble::before,
.bubble::after {
    content: "";
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-style: solid;
}

.bubble::before {
    left: -9px;
    border-width: 8px 9px 8px 0;
    border-color: transparent var(--calendarBorder) transparent transparent;
}

.bubble::after {
    left: -8px;
    border-width: 7px 8px 7px 0;
    border-color: transparent var(--background-color) transparent transparent;
}
.image-wrap {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--background-color);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    /* box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28); */
    border: 1px solid var(--calendarBorder);
}
.oikawa-normal {
    width: 40px;
    object-fit: contain;
    opacity: 0;
    transform: translateY(55px);
}

.oikawa-normal.is-visible {
    animation: oikawa-rise 1.2s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes oikawa-rise {
    0% {
        transform: translateY(55px) scale(0.96);
        opacity: 0;
    }
    60% {
        transform: translateY(0px) scale(1.02);
        opacity: 1;
    }
    100% {
        transform: translateY(0px) scale(1);
        opacity: 1;
    }
}
.lunch-challenge-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    z-index: 50;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28);
    color:var(--primary-color);
    background-color: var(--background-color);
    border-radius: 12px;
}
.lunch-challenge-modal-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
    
    align-items: center;
}
.lunch-challenge-modal {
    
    background: var(--background-color);
    border: 1px solid var(--formBorder);
    /* border-radius: 22px; */
    /* box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28); */
    padding: 10px 20px;
    max-width: 250px;
    min-width: 250px;
}
.lunch-challenge-modal:hover{
    background-color: var(--primary-color);
    cursor: pointer;
    color: var(--background-color);
}
.lunch-challenge-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    height: fit-content;
    padding: 10px;
    /* box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28); */
    background-color: var(--background-color);
}

.lunch-challenge-header h2 {
    margin: 6px 0 0;
    font-size: 28px;
    line-height: 1.15;
}

.lunch-challenge-kicker {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.lunch-challenge-close,
.lunch-challenge-generate {
    border: none;
    /* border-radius: 999px; */
    cursor: pointer;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.lunch-challenge-close {
    background: var(--bg2);
    padding: 10px 16px;
}

.lunch-challenge-generate {
    background: var(--primary-button);
    color: white;
    padding: 10px 14px;
    font-weight: 700;
}

.lunch-challenge-close:hover,
.lunch-challenge-generate:hover {
    opacity: 0.92;
    transform: translateY(-1px);
}

.lunch-challenge-generate:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
}

.lunch-challenge-intro {
    font-size: 12px;
    line-height: normal;
    display: flex;
    align-items: center;
    gap: 20px;
    
}
.challenge-icon {
    width: 40px;
    min-width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.lunch-challenge-intro p {
    margin: 0;
    flex: 1;
    line-height: 1.5;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}
.lunch-challenge-grid {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.lunch-challenge-card {
    border: 1px solid var(--formBorder);
    /* border-radius: 18px; */
    padding: 16px;
}

.lunch-challenge-card p {
    margin: 10px 0 0;
    white-space: pre-wrap;
    line-height: 1.6;
}

.lunch-challenge-label {
    color: var(--font2);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.lunch-challenge-idea {
    margin-top: 20px;
}

.lunch-challenge-textarea {
    width: 100%;
    margin-top: 10px;
    border: 1px solid var(--formBorder);
    /* border-radius: 16px; */
    padding: 14px 16px;
    resize: vertical;
    box-sizing: border-box !important;
}

.lunch-challenge-actions {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    gap: 20px;
}

@media (max-width: 640px) {
    .lunch-challenge-overlay {
        padding: 0;
    }

    .lunch-challenge-modal {
        width: 100%;
        max-height: 88vh;
        padding: 20px;
    }

    .lunch-challenge-generate,
    .lunch-challenge-close {
        width: 100%;
    }
}
</style>
