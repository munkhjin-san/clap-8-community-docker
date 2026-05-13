<template>
    <div class="inbox-page">

        <!-- Table (desktop) / Cards (mobile) -->
        <div class="inbox-table-wrap">
            <table class="inbox-table">
                <thead>
                    <tr>
                        <th>氏名</th>
                        <th>問合せ日時</th>
                        <th>相談種別</th>
                        <th>希望連絡先</th>
                        <th>相談内容</th>
                        <th>ステータス</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in list" :key="item.id" @click="select(item)" class="inbox-row">
                        <td data-label="氏名">{{ item.user ? item.user.name : '' }}</td>
                        <td data-label="問合せ日時">{{ createdDate(item.created_at) }}</td>
                        <td data-label="相談種別">{{ type(item.kind_value) }}</td>
                        <td data-label="希望連絡先">{{ item.contact_address }}</td>
                        <td data-label="相談内容">
                            <p class="inbox-content-preview">
                                {{ item.consultation_content && item.consultation_content.length > 20
                                    ? `${item.consultation_content.slice(0, 20)}...`
                                    : item.consultation_content }}
                            </p>
                            <div v-if="item.support_mail_responding_logs && item.support_mail_responding_logs.length" class="inbox-reply-badge">
                                <svg fill="currentColor" width="14" height="14" viewBox="0 0 40 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path>
                                    <path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169z"></path>
                                </svg>
                                <span>{{ item.support_mail_responding_logs.length }}</span>
                            </div>
                        </td>
                        <td data-label="ステータス">
                            <span :class="['inbox-status-badge', `inbox-status-badge--${item.status_flag}`]">
                                {{ status(item.status_flag) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Detail modal -->
        <Transition name="modalFade">
            <Modal v-if="selectedItem" size="medium" @close="reset">
                <template #title>
                    <span class="inbox-modal-title">相談詳細</span>
                </template>
                <template #content>
                    <div class="inbox-detail">
                        <div class="inbox-detail-row">
                            <span class="inbox-detail-label">氏名</span>
                            <span class="inbox-detail-value">{{ selectedItem.user ? selectedItem.user.name : '' }}</span>
                        </div>
                        <div class="inbox-detail-row">
                            <span class="inbox-detail-label">問合せ日時</span>
                            <span class="inbox-detail-value">{{ createdDate(selectedItem.created_at) }}</span>
                        </div>
                        <div class="inbox-detail-row">
                            <span class="inbox-detail-label">相談種別</span>
                            <span class="inbox-detail-value">{{ type(selectedItem.kind_value) }}</span>
                        </div>
                        <div class="inbox-detail-row">
                            <span class="inbox-detail-label">希望連絡先</span>
                            <span class="inbox-detail-value">{{ selectedItem.contact_address }}</span>
                        </div>
                        <div class="inbox-detail-row inbox-detail-row--block">
                            <span class="inbox-detail-label">相談内容</span>
                            <p class="inbox-detail-value inbox-detail-body">{{ selectedItem.consultation_content }}</p>
                        </div>

                        <!-- Memo block -->
                        <div class="inbox-memo-block">
                            <div class="inbox-memo-header">
                                <span class="inbox-detail-label">メモ</span>
                                <button class="inbox-memo-add-btn" @click="viewNewMemo">
                                    {{ addMemoWindow ? '閉じる' : 'メモ追加' }}
                                </button>
                            </div>
                            <div v-if="addMemoWindow" class="inbox-memo-form">
                                <LongInput
                                    :initialValue="newMemo"
                                    ref="consultMemo"
                                    placeHolder="新しいメモ"
                                    uId="consultMemo"
                                    name="consultMemo"
                                    rules="max:2000"
                                    label="タイトル"
                                    v-model="newMemo"
                                />
                                <div style="margin-top: 15px;">
                                    <LoaderButton content="保存する" :loading="sending" @triggered="sendMemo" />
                                </div>
                            </div>
                            <div v-for="memo in selectedItem.support_mail_responding_logs" :key="memo.id" class="inbox-memo-item">
                                <span class="inbox-memo-meta">{{ createdDate(memo.created_at) }} {{ memo.user ? memo.user.name : '' }}</span>
                                <span class="inbox-memo-text">{{ memo.text }}</span>
                            </div>
                            <p v-if="!selectedItem.support_mail_responding_logs?.length" class="inbox-memo-empty">メモはありません</p>
                        </div>

                        <!-- Status -->
                        <div class="inbox-detail-row inbox-detail-row--block">
                            <span class="inbox-detail-label">ステータス</span>
                            <div class="inbox-status-select-wrap">
                                <select @change="setStatus" v-model="newStatus" class="inbox-status-select dropDownSelector cursor-pointer">
                                    <option :value="0">{{ status(0) }}</option>
                                    <option :value="1">{{ status(1) }}</option>
                                    <option :value="2">{{ status(2) }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

    </div>
</template>
<script setup>
import Modal from '../Global/Modal.vue';
import LongInput from '../Form/LongInput.vue';
import LoaderButton from '../Global/LoaderButton.vue'
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
    const router = useRouter()
    const auth = useAuthUserStore()
    if (!auth.isAdmin) {
        router.replace({ name: 'dashboard-support' })
    }
    const list = ref([])
    const selectedItem = ref(null)
    const newStatus = ref(0)
    const addMemoWindow = ref(false)
    const newMemo = ref('')
    const sending = ref(false)
    const api = useApi()
    const viewNewMemo = () => {
        addMemoWindow.value = !addMemoWindow.value
        if(addMemoWindow.value){
            setTimeout(() => {
                const el = document.getElementById('consultMemo')
                if(el){
                    el.scrollIntoView({behavior: 'smooth', block: 'center'})
                    el.focus()
                }
            }, 0);
            
        }
    }
    const setStatus = async(event) => {
        const value = event.target.value

        await api.post('/update_consult_status', {
            record_id: selectedItem.value.id,
            value: value,                
        }, { toast: '更新しました。' })
        newMemo.value = '';
        addMemoWindow.value = false
        getRecievedConsults(selectedItem.value.id)

    }
    const sendMemo = async() => { 

        await api.post('/add_memo_to_consult', {
            record_id: selectedItem.value.id,
            text: newMemo.value,                
        }, { toast: '保存しました。', loadingRef: sending })
        
        newMemo.value = '';
        addMemoWindow.value = false
        getRecievedConsults(selectedItem.value.id)

    }
    const select = (item) => {
        selectedItem.value = item
        newStatus.value = item.status_flag
    }
    const reset = () => {
        selectedItem.value = null
    }
    const getRecievedConsults = async(id) => {
   
        const response = await api.get('/get_recieved_consults' )
        list.value = response
        if(id){
            const replaceData = list.value.find(ob => ob.id == id)
            if(replaceData){
                selectedItem.value = replaceData
            }
        }

    }
    const createdDate = (date) => {
        return DateTime.fromISO(date).toLocaleString(DateTime.DATETIME_MED)
    }
    const type = (value) => {
        const items = [ '法務','総務','会計','人事','労務','広報','事業','システム開発','その他','よくある質問未解決' ]
        const index = value == 99 ? 9 : value
        return items[index]
                
    }
    const status = (val) => {
        const list = ['未対応','対応中','対応済']
        return list[val]
    }
    onMounted(getRecievedConsults)
 
</script>
<style scoped lang="scss">

/* ─── Page layout ──────────────────────────────────────── */
.inbox-page {
    padding: 24px;
    min-height: 100%;
    box-sizing: border-box;
}

/* ─── Header ───────────────────────────────────────────── */
.inbox-page-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}

.inbox-page-header-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--primary-color);
    color: var(--background-color);
    flex-shrink: 0;
}

.inbox-page-title {
    font-size: 17px;
    font-weight: 600;
    margin: 0;
    line-height: 1.3;
}

.inbox-count {
    margin-left: auto;
    font-size: 12px;
    color: #888;
    background: var(--bg3);
    border-radius: 20px;
    padding: 3px 10px;
    flex-shrink: 0;
}

/* ─── Table wrapper ─────────────────────────────────────── */
.inbox-table-wrap {
    border: 1px solid var(--bg3);
    border-radius: 12px;
    overflow: hidden;
}

/* ─── Desktop table ─────────────────────────────────────── */
.inbox-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;

    thead tr {
        background: var(--bg3);
    }

    th {
        padding: 11px 14px;
        font-weight: 600;
        font-size: 12px;
        text-align: left;
        white-space: nowrap;
        border-bottom: 1px solid color-mix(in srgb, var(--primary-color) 12%, transparent);
    }

    td {
        padding: 12px 14px;
        line-height: 1.5;
        border-bottom: 1px solid var(--bg3);
        vertical-align: middle;
    }

    tbody tr:last-child td {
        border-bottom: none;
    }

    tbody tr {
        cursor: pointer;
        transition: background 0.15s ease;

        &:hover {
            background: var(--bg3);
        }
    }
}

/* ─── Content preview in table cell ─────────────────────── */
.inbox-content-preview {
    margin: 0;
    max-width: 180px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

/* ─── Reply count badge (chat icon + count) ─────────────── */
.inbox-reply-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    font-size: 12px;
    color: var(--primary-color);
    background: color-mix(in srgb, var(--primary-color) 10%, transparent);
    border-radius: 20px;
    padding: 2px 8px 2px 6px;
}

/* ─── Status badge ──────────────────────────────────────── */
.inbox-status-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    white-space: nowrap;

    /* 0 = 未対応 */
    &--0 {
        background: color-mix(in srgb, #aaa 15%, transparent);
        color: #777;
    }
    /* 1 = 対応中 */
    &--1 {
        background: color-mix(in srgb, #f5a623 15%, transparent);
        color: #c47b00;
    }
    /* 2 = 対応済 */
    &--2 {
        background: color-mix(in srgb, var(--primary-color) 15%, transparent);
        color: var(--primary-color);
    }
}

/* ─── Detail modal content ──────────────────────────────── */
.inbox-modal-title {
    font-size: 15px;
    font-weight: 600;
}

.inbox-detail {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.inbox-detail-row {
    display: flex;
    align-items: baseline;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--bg3);

    &:last-child {
        border-bottom: none;
    }

    &--block {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
}

.inbox-detail-label {
    font-size: 11px;
    font-weight: 600;
    color: #888;
    white-space: nowrap;
    flex-shrink: 0;
    min-width: 70px;
}

.inbox-detail-value {
    font-size: 14px;
    line-height: 1.6;
}

.inbox-detail-body {
    white-space: pre-wrap;
    margin: 0;
    padding: 12px;
    background: var(--bg3);
    border-radius: 8px;
    font-size: 13px;
}

/* ─── Memo section ──────────────────────────────────────── */
.inbox-memo-block {
    padding: 12px;
    background: var(--bg3);
    border-radius: 10px;
    margin: 4px 0 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.inbox-memo-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.inbox-memo-add-btn {
    font-size: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid var(--primary-color);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    transition: background 0.15s, color 0.15s;

    &:hover {
        background: var(--primary-color);
        color: var(--background-color);
    }
}

.inbox-memo-form {
    padding: 10px;
    background: var(--message-background);
    border-radius: 8px;
}

.inbox-memo-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 0;
    border-top: 1px solid color-mix(in srgb, var(--primary-color) 12%, transparent);
}

.inbox-memo-meta {
    font-size: 11px;
    color: #888;
}

.inbox-memo-text {
    font-size: 13px;
    line-height: 1.5;
}

.inbox-memo-empty {
    font-size: 12px;
    color: #aaa;
    margin: 0;
}

/* ─── Status select ─────────────────────────────────────── */
.inbox-status-select-wrap {
    display: flex;
}

.inbox-status-select {
    font-size: 13px;
    padding: 7px 12px;
    border-radius: 8px;
    border: 1px solid var(--bg3);
    background: var(--message-background);
    color: inherit;
    min-width: 140px;
    outline: none;

    &:focus {
        border-color: var(--primary-color);
    }
}

/* ─── Transition ────────────────────────────────────────── */
.modalFade-enter-active,
.modalFade-leave-active {
    transition: opacity 0.2s ease;
}
.modalFade-enter-from,
.modalFade-leave-to {
    opacity: 0;
}

/* ─── Mobile ────────────────────────────────────────────── */
@media (max-width: 959px) {
    .inbox-page {
        padding: 16px;
    }

    .inbox-page-header {
        margin-bottom: 16px;
    }

    .inbox-page-title {
        font-size: 15px;
    }

    /* Hide table header on mobile */
    .inbox-table-wrap {
        border-radius: 10px;
    }

    .inbox-table {
        thead {
            display: none;
        }

        /* Reflow rows as cards */
        tbody,
        tr,
        td {
            display: block;
        }

        tbody tr {
            border-bottom: 1px solid var(--bg3);
            padding: 14px 16px;

            &:last-child {
                border-bottom: none;
            }

            &:hover {
                background: var(--bg3);
            }
        }

        td {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 5px 0;
            border-bottom: none;
            font-size: 13px;

            &::before {
                content: attr(data-label);
                font-size: 10px;
                font-weight: 700;
                color: #888;
                min-width: 68px;
                flex-shrink: 0;
                padding-top: 2px;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }
        }

        /* Hide "相談内容" cell on list — show only relevant columns */
        td[data-label="相談内容"] {
            display: none;
        }
    }

    /* Detail modal rows stack on mobile */
    .inbox-detail-row {
        flex-direction: column;
        gap: 4px;
    }

    .inbox-detail-label {
        min-width: unset;
    }
}
</style>