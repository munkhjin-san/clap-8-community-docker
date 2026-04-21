<template>
    <div ref="girdParent">
        <!-- <div ref="girdParent" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5"> -->
        <masonry-wall :items="masonryItems" :ssr-columns="1" :column-width="230" :gap="30">
            <template #default="{ item, index }">
            <div v-if="isAddTile(item)" class="daily-add-card">
                <button
                    v-if="!isComposerOpen"
                    type="button"
                    class="daily-add-trigger"
                    @click="openComposer"
                >
                    <template v-if="currentUserCommentText">
                        <span class="daily-add-label">あなたのひとこと</span>
                        <p class="daily-current-comment">{{ currentUserCommentText }}</p>
                        <span class="daily-edit-chip">Edit</span>
                    </template>
                    <template v-else>
                        <span class="daily-add-plus">+</span>
                        <span class="daily-add-label">ひとことを追加</span>
                    </template>
                </button>
                <div v-else class="daily-add-form">
                    <textarea
                        v-model="draftComment"
                        class="daily-add-input"
                        rows="4"
                        maxlength="200"
                        placeholder="今日のひとことを入力"
                    />
                    <div class="daily-add-actions">
                        <button type="button" class="daily-add-btn ghost" @click="cancelComposer" :disabled="isSubmitting">キャンセル</button>
                        <button type="button" class="daily-add-btn" @click="submitComment" :disabled="isSubmitting || !draftComment.trim()">{{ currentUserCommentText ? '保存' : '投稿' }}</button>
                    </div>
                </div>
            </div>
            <DailyMessageItem 
                v-else
                :left-edge="horizontalLimit.left" 
                :right-edge="horizontalLimit.right" 
                :user="item" 
                :key="item.id"
                @refresh="(data) => emit('refresh', data)"
            />        
            </template>
        </masonry-wall>    
        <!-- </div> -->
    </div>
</template>
<script setup lang="ts">
import { computed, ref, useTemplateRef } from 'vue';
import DailyMessageItem from './DailyMessageItem.vue';
import { DailyMessageUser } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
const emit = defineEmits<{
    refresh: [data: DailyMessageUser]
    create: [comment: string]
}>()

type AddTileItem = {
    id: string
    __addTile: true
}

type MasonryItem = DailyMessageUser | AddTileItem

const props = defineProps<{
    members: DailyMessageUser[];
    from?: string; 
}>()

const addTile: AddTileItem = {
    id: 'daily-add-tile',
    __addTile: true
}

const isComposerOpen = ref(false)
const isSubmitting = ref(false)
const draftComment = ref('')
const auth = useAuthUserStore()

const currentUserDailyMessage = computed(() => {
    if (!auth.user?.id) {
        return null
    }

    return props.members.find(member => member.id === auth.user?.id) ?? null
})

const currentUserCommentText = computed(() => {
    return currentUserDailyMessage.value?.custom_field_data_records?.[0]?.value_text ?? ''
})

const isAddTile = (item: MasonryItem): item is AddTileItem => {
    return '__addTile' in item
}

const openComposer = () => {
    isComposerOpen.value = true
}

const cancelComposer = () => {
    draftComment.value = ''
    isComposerOpen.value = false
}

const submitComment = async () => {
    const message = draftComment.value.trim()
    if (!message || isSubmitting.value) {
        return
    }

    isSubmitting.value = true
    emit('create', message)
    draftComment.value = ''
    isComposerOpen.value = false
    isSubmitting.value = false
}

const masonryItems = computed<MasonryItem[]>(() => {
    if (props.from === 'today-comments') {
        return props.members.some(member => member.id === auth.user?.id) ? props.members : [addTile, ...props.members]
    }
    return props.members
})

const gridParent = useTemplateRef('girdParent');

const horizontalLimit = computed(() => {
    if(!gridParent.value) {
        return {
            left: 0, right: 0
        }
    }
    const rect = gridParent.value.getBoundingClientRect();
    return {
        left: rect.left,
        right: rect.right
    }
})
</script>
<style scoped>
.daily-add-card {
    display: flex;
    align-items: center;
    justify-content: center;
}

.daily-add-trigger {
    width: 100%;
    min-height: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    color: var(--sub-color);
}

.daily-add-plus {
    font-size: 28px;
    line-height: 1;
}

.daily-add-label {
    font-size: 12px;
}

.daily-add-form {
    width: 100%;
}

.daily-add-input {
    width: 100%;
    resize: vertical;
    min-height: 88px;
    border: 1px solid var(--check-inactive);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 8px 10px;
    font-size: 13px;
    line-height: 1.5;
    box-sizing: border-box !important;
}

.daily-add-actions {
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.daily-add-btn {
    border: 1px solid var(--primary-color);
    background: var(--primary-color);
    color: var(--bg2);
    font-size: 12px;
    padding: 4px 10px;
}

.daily-add-btn.ghost {
    background: transparent;
    color: var(--primary-color);
}

.daily-add-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>