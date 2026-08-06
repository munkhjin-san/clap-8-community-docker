<template>
    <div class="admin-window">
        <Transition name="modalFade">
            <div v-if="fetch === 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div v-if="fetch > 0" class="partners">
            <div class="partners__bar">
                <p class="partners__range">
                    <template v-if="meta.total_count">
                        全{{ meta.total_count }}件中 {{ meta.from }}〜{{ meta.to }}件目
                    </template>
                    <template v-else>該当する取引先はありません</template>
                </p>
                <div class="partners__pager">
                    <button
                        type="button"
                        class="partners__button"
                        :disabled="loading"
                        title="freeeから取り直す"
                        @click="reload(true)"
                    >
                        再取得
                    </button>
                    <button
                        type="button"
                        class="partners__button"
                        :disabled="meta.page <= 1 || loading"
                        @click="goToPage(meta.page - 1)"
                    >
                        前へ
                    </button>
                    <span class="partners__pager-page">{{ meta.page }} / {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="partners__button"
                        :disabled="!meta.has_more || loading"
                        @click="goToPage(meta.page + 1)"
                    >
                        次へ
                    </button>
                </div>
            </div>

            <div class="partners__table-wrap">
                <table class="partners__table">
                    <thead>
                        <tr>
                            <th class="is-code is-sortable" @click="toggleSort('code')">
                                コード<span class="partners__sort">{{ sortMark('code') }}</span>
                            </th>
                            <th class="is-sortable" @click="toggleSort('name')">
                                取引先名<span class="partners__sort">{{ sortMark('name') }}</span>
                            </th>
                            <th class="is-sortable" @click="toggleSort('name_kana')">
                                カナ<span class="partners__sort">{{ sortMark('name_kana') }}</span>
                            </th>
                            <th>担当者</th>
                            <th>電話番号</th>
                            <th>メール</th>
                            <th>登録番号</th>
                            <th class="is-sortable" @click="toggleSort('update_date')">
                                更新日<span class="partners__sort">{{ sortMark('update_date') }}</span>
                            </th>
                            <th class="is-sortable is-id" @click="toggleSort('id')">
                                登録順<span class="partners__sort">{{ sortMark('id') }}</span>
                            </th>
                            <th class="is-state">状態</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="partner in partners" :key="partner.id ?? partner.code ?? ''">
                            <td class="is-code">{{ partner.code || '—' }}</td>
                            <td>
                                {{ partner.name || '—' }}
                                <span v-if="partner.long_name && partner.long_name !== partner.name" class="partners__sub">
                                    {{ partner.long_name }}
                                </span>
                            </td>
                            <td>{{ partner.name_kana || '—' }}</td>
                            <td>{{ partner.contact_name || '—' }}</td>
                            <td>{{ partner.phone || '—' }}</td>
                            <td class="is-break">{{ partner.email || '—' }}</td>
                            <td>{{ partner.invoice_registration_number || '—' }}</td>
                            <td>{{ partner.update_date || '—' }}</td>
                            <td class="is-id">{{ partner.id ?? '—' }}</td>
                            <td class="is-state">
                                <span :class="['partners__chip', { configured: partner.available !== false }]">
                                    {{ partner.available === false ? '使用不可' : '使用中' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!partners.length">
                            <td colspan="10" class="partners__empty">現在データはありません</td>
                        </tr>
                    </tbody>
                </table>

                <Transition name="modalFade">
                    <div v-if="loading" class="partners__overlay">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useApi } from '@/composables/api'
import type {
    FreeePartner,
    FreeePartnerSort,
    FreeePartnersResponse,
} from '@/interface/freeeInterface'

const api = useApi()
const fetch = ref(0)
const loading = ref(false)
const partners = ref<FreeePartner[]>([])

// freeeは常にid昇順（古い順）で返し、並び替えパラメータを持たない。
// 並び替えはサーバー側が全件を保持して行うため、ここでは条件を送るだけ。
const meta = reactive({
    page: 1,
    per_page: 50,
    total_count: 0,
    last_page: 1,
    has_more: false,
    from: 0,
    to: 0,
    sort: 'id' as FreeePartnerSort,
    direction: 'desc' as 'asc' | 'desc',
})

const getPartners = async (page: number, fresh = false) => {
    loading.value = true
    try {
        const response = await api.get('/admin/freee/partners', {
            page,
            sort: meta.sort,
            direction: meta.direction,
            ...(fresh ? { fresh: 1 } : {}),
        }) as FreeePartnersResponse | null
        if (!response) return

        partners.value = response.partners ?? []
        Object.assign(meta, response.meta)
    } finally {
        loading.value = false
        fetch.value++
    }
}

const goToPage = (page: number) => {
    if (page < 1 || loading.value) return
    getPartners(page)
}

const reload = (fresh = false) => {
    if (loading.value) return
    getPartners(meta.page, fresh)
}

// 同じ列を押したら昇降を反転、別の列なら新しい順（更新日・登録順）か昇順（文字列）で始める。
const toggleSort = (column: FreeePartnerSort) => {
    if (loading.value) return

    if (meta.sort === column) {
        meta.direction = meta.direction === 'asc' ? 'desc' : 'asc'
    } else {
        meta.sort = column
        meta.direction = column === 'id' || column === 'update_date' ? 'desc' : 'asc'
    }

    getPartners(1)
}

const sortMark = (column: FreeePartnerSort) => {
    if (meta.sort !== column) return ''
    return meta.direction === 'asc' ? '▲' : '▼'
}

onMounted(() => getPartners(1))
</script>

<style scoped>
.partners {
    height: 100%;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    overflow: hidden;
}

.partners__bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.partners__range {
    color: gray;
    font-size: 12px;
}

.partners__pager {
    display: flex;
    align-items: center;
    gap: 8px;
}

.partners__button {
    min-width: 64px;
    padding: 6px 12px;
    border: 1px solid var(--bg3);
    background: var(--background-color);
    color: var(--text-color);
    font-size: 12px;
    cursor: pointer;
}

.partners__button:disabled {
    color: #a1a1aa;
    cursor: not-allowed;
    opacity: 0.6;
}

.partners__pager-page {
    min-width: 60px;
    color: gray;
    font-size: 12px;
    text-align: center;
}

.partners__table-wrap {
    position: relative;
    flex: 1;
    min-height: 0;
    overflow: auto;
    background: var(--background-color);
}

.partners__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.partners__table th,
.partners__table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--bg3);
    text-align: left;
    line-height: 1.5;
    white-space: nowrap;
}

.partners__table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: var(--bg3);
    color: gray;
    font-size: 12px;
    font-weight: normal;
}

.partners__table th.is-sortable {
    cursor: pointer;
    user-select: none;
}

.partners__table th.is-sortable:hover {
    color: var(--text-color);
}

.partners__sort {
    display: inline-block;
    width: 14px;
    font-size: 9px;
}

.partners__table td.is-break {
    white-space: normal;
    overflow-wrap: anywhere;
}

.partners__table th.is-code,
.partners__table td.is-code {
    width: 110px;
}

.partners__table th.is-id,
.partners__table td.is-id {
    width: 110px;
    color: gray;
    font-size: 11px;
}

.partners__table th.is-state,
.partners__table td.is-state {
    width: 90px;
}

.partners__sub {
    display: block;
    color: gray;
    font-size: 11px;
}

.partners__chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 8px;
    background: var(--bg3);
    color: gray;
    font-size: 11px;
}

.partners__chip::before {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #a1a1aa;
    content: '';
}

.partners__chip.configured {
    background: #edf8f0;
    color: #166534;
}

.partners__chip.configured::before {
    background: #22a447;
}

.partners__empty {
    padding: 40px 12px;
    color: gray;
    text-align: center;
}

.partners__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--background-color) 65%, transparent);
}
</style>
