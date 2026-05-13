<template>
<div class="faq-page">
    <div class="w-[300px] under960:w-full mb-5" style="margin-left: 0;">
        <PostSearchBar 
            className="newChatMemberSearch" 
            :customPlaceHolder="'よくある質問から検索'"
            @searchStart="(key) => emit('setKeyWord', key)"
        />                
    </div> 
    <!-- Tag filter section -->
    <section class="faq-section">
        <div class="faq-section-label">カテゴリーから絞り込む</div>
        <div class="faq-tag-list">
            <template v-for="item in tagList" :key="item.id">
                <!-- Tag in edit mode (admin only) -->
                <div v-if="auth.isAdmin && editingTagId === item.id" class="faq-tag faq-tag--editing">
                    <input
                        v-model="editingTagText"
                        @keydown.enter="saveTag"
                        @keydown.esc="cancelEditTag"
                        class="faq-tag-input"
                        autofocus
                    />
                    <span @click="saveTag" class="faq-tag-confirm">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32" fill="var(--primary-color)">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </span>
                    <span @click="cancelEditTag" class="faq-tag-cancel">
                        <CloseIcon size="10"/>
                    </span>
                </div>
                <!-- Normal tag -->
                <div v-else class="faq-tag-wrap">
                    <div
                        @click="setText(item)"
                        :class="['faq-tag relative', { 'faq-tag--active': selectedTag === item.id }]"
                    >
                        <div :class="{'mr-3' : auth.isAdmin && item.id !== 0}">{{ item.text }}</div>
                        <div class="absolute right-0">
                            <ItemMenu
                                v-if="auth.isAdmin && item.id !== 0"
                                :items="[
                                    { title: '編集する', action: () => startEditTag(item) },
                                    { title: '削除する', action: () => deleteTag(item) }
                                ]"
                            />
                        </div>                                        
                    </div>
                </div>
            </template>

            <!-- Admin: add new tag -->
            <template v-if="auth.isAdmin">
                <div v-if="addingTag" class="faq-tag faq-tag--editing">
                    <input
                        v-model="newTagText"
                        @keydown.enter="createTag"
                        @keydown.esc="addingTag = false; newTagText = ''"
                        placeholder="タグ名"
                        class="faq-tag-input"
                        autofocus
                    />
                    <span @click="createTag" class="faq-tag-confirm">✓</span>
                    <span @click="addingTag = false; newTagText = ''" class="faq-tag-cancel">✗</span>
                </div>
                <button v-else class="faq-tag faq-tag--add" @click="addingTag = true">
                    + タグ追加
                </button>
            </template>
        </div>
    </section>

    <!-- FAQ list -->
    <section class="faq-section faq-list-section">
        <div class="faq-section-label">{{ qaList.length }} 件の質問</div>
        <div class="faq-list">
            <!-- <TransitionGroup name="faq-item" tag="div"> -->
            <ExpansionGrid :col="1" v-model="expandedFaqId">
            <FaqItem
                v-for="item in qaList"
                :key="item.id"
                :item="item"
                :expandedId="expandedFaqId"
                :isAdmin="auth.isAdmin"
                @edit="openEdit"
                @delete="deleteFaq"
                @close="expandedFaqId = null"
            />
            </ExpansionGrid>
            <!-- </TransitionGroup> -->

            <div v-if="!qaList.length" class="faq-empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.25;margin-bottom:10px">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>該当する質問が見つかりません</span>
            </div>
        </div>
    </section>


    <!-- FAQ Create / Edit Modal -->
    <Transition name="modalFade">
        <FaqCreate
            v-if="showFaqCreate"
            :editTarget="editTarget"
            :tagList="tagList"
            @close="onFaqCreateClose"
        />
    </Transition>

    <!-- Float button for create (admin only) -->
    <FloatButton
        v-if="auth.isAdmin"
        class="fixed"
        @action="openCreate"
        title="FAQを作成する"
    >
        <template #icon>
            <AddIcon />
        </template>
    </FloatButton>
</div>
</template>
<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import FaqCreate from './FaqCreate.vue';
import FaqItem from './FaqItem.vue';
import FloatButton from '../Global/FloatButton.vue';
import AddIcon from '../Form/AddIcon.vue';
import ExpansionGrid from '../Dashboard/ExpansionGrid.vue';
import ItemMenu from '../Global/ItemMenu.vue';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import PostSearchBar from '../Post/PostSearchBar.vue';
import CloseIcon from '../Form/CloseIcon.vue';

    const props = defineProps(['qaList', 'tagList'])
    const emit = defineEmits(['setKeyWord', 'refresh'])
    const auth = useAuthUserStore()
    const api = useApi()
    const route = useRoute()
    const router = useRouter()

    // FAQ list state
    const selectedTag = ref(0)

    // Sync expansion with route param
    const expandedFaqId = computed({
        get() {
            return route.params.faqId ? Number(route.params.faqId) : null
        },
        set(value) {
            if (value) {
                router.push({ name: 'faq_detail', params: { faqId: value } })
            } else {
                router.push({ name: 'faq' })
            }
        }
    })

    // FAQ create/edit state
    const showFaqCreate = ref(false)
    const editTarget = ref(null)

    // Tag management state
    const editingTagId = ref(null)
    const editingTagText = ref('')
    const addingTag = ref(false)
    const newTagText = ref('')

    // --- FAQ list handlers ---
    const setText = (item) => {
        selectedTag.value = item.id
        const text = item.id == 0 ? '' : item.text
        emit('setKeyWord', text)
    }
    // --- FAQ create/edit ---
    const openCreate = () => {
        editTarget.value = null
        showFaqCreate.value = true
    }
    const openEdit = (item) => {
        editTarget.value = item
        showFaqCreate.value = true
    }
    const onFaqCreateClose = (refreshNeeded) => {
        showFaqCreate.value = false
        editTarget.value = null
        if (refreshNeeded) {
            emit('refresh')
        }
    }
    const deleteFaq = async (item) => {
        await api.post('/faq_delete_record', { id: item.id }, {
            toast: '削除しました。',
            ask: '本当に削除しますか？'
        })
        emit('refresh')
    }

    // --- Tag management ---
    const startEditTag = (item) => {
        editingTagId.value = item.id
        editingTagText.value = item.text
    }
    const cancelEditTag = () => {
        editingTagId.value = null
        editingTagText.value = ''
    }
    const saveTag = async () => {
        const text = editingTagText.value.trim()
        if (!text) return
        await api.post('/faq_tag_save', { id: editingTagId.value, text }, {
            toast: '更新しました。'
        })
        cancelEditTag()
        emit('refresh')
    }
    const createTag = async () => {
        const text = newTagText.value.trim()
        if (!text) return
        await api.post('/faq_tag_save', { text }, {
            toast: 'タグを追加しました。'
        })
        addingTag.value = false
        newTagText.value = ''
        emit('refresh')
    }
    const deleteTag = async (item) => {
        await api.post('/faq_tag_delete', { id: item.id }, {
            toast: '削除しました。',
            ask: '本当に削除しますか？'
        })
        emit('refresh')
    }
</script>

<style scoped lang="scss">
/* ── Page layout ─────────────────────────────────────── */
.faq-page {
    min-height: 100%;
    padding: 28px 28px 80px;
    color: var(--primary-color);
}

/* ── Page header ─────────────────────────────────────── */
.faq-page-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
}
.faq-page-header-icon {
    display: flex;
    align-items: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: var(--bg3);
    justify-content: center;
    flex-shrink: 0;
    opacity: 0.8;
}
.faq-page-title {
    font-size: 17px;
    font-weight: 700;
    margin: 0;
    letter-spacing: 0.01em;
}

/* ── Sections ────────────────────────────────────────── */
.faq-section {
    margin-bottom: 28px;
}
.faq-section-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    opacity: 0.35;
    margin-bottom: 12px;
}

/* ── Tags / chips ────────────────────────────────────── */
.faq-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.faq-tag-wrap {
    display: flex;
    align-items: center;
    gap: 2px;
}
.faq-tag {
    display: inline-flex;
    align-items: center;
    padding: 5px 14px;
    border-radius: 5px;
    border: 1px solid rgba(128, 128, 128, 0.2);
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
    line-height: 1.4;
    white-space: nowrap;
}
.faq-tag:hover { border-color: var(--primary-color); }
.faq-tag--active {
    background: var(--background-color);
    color: var(--primary-color);
    border-color: var(--primary-color);
}
.faq-tag--add { opacity: 0.55; }
.faq-tag--add:hover { opacity: 1; }

.faq-tag--editing {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 5px;
    border: 1px solid var(--primary-color);
    background: var(--bg3);
}
.faq-tag-input {
    width: 80px;
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--primary-color);
    outline: none;
    color: inherit;
    font-size: inherit;
}
.faq-tag-confirm { cursor: pointer; font-size: 12px; color: var(--primary-color); }
.faq-tag-cancel   { cursor: pointer; font-size: 12px; opacity: 0.45; }

/* ── FAQ list ────────────────────────────────────────── */
.faq-list-section { flex: 1; }

.faq-list {
    position: relative;
    border-radius: 5px;
    overflow: hidden;
}

/* ── Empty state ─────────────────────────────────────── */
.faq-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    color: var(--primary-color);
    opacity: 0.4;
    font-size: 13px;
}

/* ── Mobile ──────────────────────────────────────────── */
@media screen and (max-width: 959px) {
    .faq-page { padding: 20px 16px 80px; }
    .faq-page-header { margin-bottom: 20px; }
}
</style>
