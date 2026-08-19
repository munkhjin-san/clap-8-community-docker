<template>
    <div class="partner-list">
        <!-- 絞り込みと件数は検索欄の行へ送る。一覧の上に操作バーを重ねない。 -->
        <Teleport v-if="toolsMounted" to="#adminListTools">
            <div class="partner-list__tools">
                <div class="partner-list__filter">
                    <button
                        type="button"
                        class="partner-list__filter-button"
                        :class="{ 'is-on': activeFilterCount > 0 }"
                        title="絞り込み"
                        @click.stop="menu.setMenu({ parent: 'partnerFilter' })"
                    >
                        <Filter :filtered="activeFilterCount > 0" size="14" />
                        <span v-if="activeFilterCount" class="partner-list__filter-count">{{ activeFilterCount }}</span>
                    </button>

                    <Transition name="slidePop">
                        <div v-if="menu.parent === 'partnerFilter'" id="partnerFilter" class="partner-list__filter-pop" @click.stop>
                            <div class="partner-list__filter-head">
                                <span>絞り込み</span>
                                <button type="button" class="partner-list__filter-reset" :disabled="!activeFilterCount" @click="resetFilters">リセット</button>
                            </div>

                            <div class="partner-list__filter-group">
                                <p class="partner-list__filter-label">freee連携</p>
                                <label v-for="option in linkFilters" :key="option.value" class="partner-list__filter-option">
                                    <input v-model="filters.linked" type="radio" class="custom-f-radio" name="partner-linked" :value="option.value" />
                                    <span>{{ option.label }}</span>
                                </label>
                            </div>

                            <div class="partner-list__filter-group">
                                <p class="partner-list__filter-label">状態</p>
                                <label v-for="option in availableFilters" :key="option.value" class="partner-list__filter-option">
                                    <input v-model="filters.available" type="radio" class="custom-f-radio" name="partner-available" :value="option.value" />
                                    <span>{{ option.label }}</span>
                                </label>
                            </div>

                            <div class="partner-list__filter-group">
                                <p class="partner-list__filter-label">区分</p>
                                <label v-for="option in ENTITY_TYPES" :key="option.value" class="partner-list__filter-option">
                                    <input v-model="filters.entityTypes" type="checkbox" class="custom-f-checkbox" :value="option.value" />
                                    <span>{{ option.label }}</span>
                                </label>
                            </div>

                            <div class="partner-list__filter-group">
                                <p class="partner-list__filter-label">取引区分</p>
                                <label v-for="option in TRANSACTION_CATEGORIES" :key="option.value" class="partner-list__filter-option">
                                    <input v-model="filters.categories" type="checkbox" class="custom-f-checkbox" :value="option.value" />
                                    <span>{{ option.label }}</span>
                                </label>
                            </div>
                        </div>
                    </Transition>
                </div>

                <span class="partner-list__range">
                    <template v-if="meta.total_count">全{{ meta.total_count }}件中 {{ meta.from }}〜{{ meta.to }}件目</template>
                    <template v-else>該当する取引先はありません</template>
                </span>
            </div>
        </Teleport>

        <div class="partner-list__table-wrap">
            <table class="partner-list__table">
                <thead>
                    <tr>
                        <th class="is-sortable is-name" @click="toggleSort('name')">取引先名<span class="partner-list__sort">{{ sortMark('name') }}</span></th>
                        <th class="is-entity">区分</th>
                        <th class="is-category">取引区分</th>
                        <th class="is-contact">担当者</th>
                        <th class="is-projects">プロジェクト</th>
                        <th class="is-freee">freee連携</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 行そのものが編集画面への入口。操作列は置かず、詳細・freee連携・削除はモーダル内にまとめる。 -->
                    <tr v-for="partner in partners" :key="partner.id" class="is-clickable" @click="openEdit(partner)">
                        <td class="is-name">
                            <div class="partner-list__name">
                                <span class="partner-list__name-main" :title="partner.name">{{ partner.name }}</span>
                                <span v-if="!partner.available" class="partner-list__flag">使用不可</span>
                            </div>
                            <span
                                v-if="partner.long_name && partner.long_name !== partner.name"
                                class="partner-list__sub"
                                :title="partner.long_name"
                            >{{ partner.long_name }}</span>
                        </td>
                        <td class="is-entity">{{ entityLabel(partner.entity_type) }}</td>
                        <td class="is-category">{{ categoryLabel(partner.transaction_category) }}</td>
                        <td class="is-contact">{{ partner.contact_name || '—' }}</td>
                        <td class="is-projects">
                            <template v-if="partner.projects?.length">
                                {{ partner.projects.length }}件
                                <span class="partner-list__sub" :title="partner.projects.map(p => p.name).join('、')">
                                    {{ partner.projects.map(p => p.name).join('、') }}
                                </span>
                            </template>
                            <template v-else>—</template>
                        </td>
                        <td class="is-freee">
                            <span :class="['partner-list__chip', { 'is-linked': partner.freee_linked }]">
                                {{ partner.freee_linked ? `#${partner.freee_partner_id}` : '未連携' }}
                            </span>
                            <span v-if="partner.has_unsynced_changes" class="partner-list__sub">未反映の変更あり</span>
                        </td>
                    </tr>
                    <tr v-if="!partners.length && !loading">
                        <td colspan="6" class="partner-list__empty">現在データはありません</td>
                    </tr>
                </tbody>
            </table>

            <Transition name="modalFade">
                <div v-if="loading" class="partner-list__overlay">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </Transition>
        </div>

        <div v-if="meta.last_page > 1" class="partner-list__pager">
            <PostSearchPager
                :possible-page="meta.last_page"
                :active-path="meta.page"
                @set-navi="shiftPage"
                @set-active-page="goToPage"
            />
        </div>

        <FloatButton @action="openCreate">
            <template #icon>
                <AddIcon size="15" fill="black" />
            </template>
        </FloatButton>

        <Teleport to="body">
            <PartnerModal
                v-if="formWindow"
                :partner="editData"
                @close="formWindow = false; editData = null"
                @saved="onSaved"
                @refresh="getPartners()"
                @manage-projects="openProjects"
            />
        </Teleport>

        <Teleport to="body">
            <PartnerProjects
                v-if="projectWindow && projectTarget"
                :partner="projectTarget"
                @close="projectWindow = false; projectTarget = null"
                @saved="onProjectsSaved"
            />
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useApi } from '@/composables/api'
import { useMenuStore } from '@/store/menu'
import Filter from '@/components/Icons/Filter.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import FloatButton from '@/components/Global/FloatButton.vue'
import PostSearchPager from '@/components/Post/PostSearchPager.vue'
import PartnerModal from './PartnerModal.vue'
import PartnerProjects from './PartnerProjects.vue'
import {
    ENTITY_TYPES,
    TRANSACTION_CATEGORIES,
    type PartnerListResponse,
    type PartnerRecord,
    type PartnerSort,
} from '@/interface/partnerInterface'

const props = defineProps<{ keywords?: string }>()

const api = useApi()
const menu = useMenuStore()

const partners = ref<PartnerRecord[]>([])
const loading = ref(false)
// Teleport先は親（ProjectControl）のDOM。マウント後に描画する。
const toolsMounted = ref(false)

const filters = reactive({
    linked: '' as '' | 'linked' | 'unlinked',
    available: '' as '' | 'available' | 'unavailable',
    entityTypes: [] as string[],
    categories: [] as string[],
})

const formWindow = ref(false)
const editData = ref<PartnerRecord | null>(null)
const projectWindow = ref(false)
const projectTarget = ref<PartnerRecord | null>(null)

const linkFilters = [
    { value: '', label: 'すべて' },
    { value: 'linked', label: '連携済み' },
    { value: 'unlinked', label: '未連携' },
]

const availableFilters = [
    { value: '', label: 'すべて' },
    { value: 'available', label: '使用中' },
    { value: 'unavailable', label: '使用不可' },
]

const activeFilterCount = computed(() =>
    (filters.linked ? 1 : 0)
    + (filters.available ? 1 : 0)
    + (filters.entityTypes.length ? 1 : 0)
    + (filters.categories.length ? 1 : 0))

const resetFilters = () => {
    filters.linked = ''
    filters.available = ''
    filters.entityTypes = []
    filters.categories = []
}

const meta = reactive({
    page: 1,
    per_page: 50,
    total_count: 0,
    last_page: 1,
    has_more: false,
    from: 0,
    to: 0,
    sort: 'created_at' as PartnerSort,
    direction: 'desc' as 'asc' | 'desc',
})

const getPartners = async (page = meta.page) => {
    loading.value = true
    try {
        // cancel: 一覧は条件を変えるたびに叩き直すため、前の要求を必ず打ち切る。
        // 打ち切らないと、遅れて届いた古い応答が件数だけを上書きして表示と食い違う。
        const response = await api.get('/admin/partners', {
            page,
            keyword: props.keywords || undefined,
            sort: meta.sort,
            direction: meta.direction,
            linked: filters.linked || undefined,
            available: filters.available || undefined,
            entity_type: filters.entityTypes.length ? filters.entityTypes : undefined,
            transaction_category: filters.categories.length ? filters.categories : undefined,
        }, { cancel: true }) as PartnerListResponse | null
        if (!response) return

        partners.value = response.partners ?? []
        Object.assign(meta, response.meta)
    } catch {
        // useApi がメッセージを出すので、ここでは一覧を空にしない（直前の表示を残す）
    } finally {
        loading.value = false
    }
}

const goToPage = (page: number) => {
    if (page < 1 || page > meta.last_page || loading.value) return
    getPartners(page)
}

/** PostSearchPager の「前へ／次へ」。-1 / +1 が飛んでくる。 */
const shiftPage = (direction: number) => goToPage(meta.page + direction)

const toggleSort = (column: PartnerSort) => {
    if (loading.value) return

    if (meta.sort === column) {
        meta.direction = meta.direction === 'asc' ? 'desc' : 'asc'
    } else {
        meta.sort = column
        meta.direction = column === 'updated_at' || column === 'created_at' ? 'desc' : 'asc'
    }

    getPartners(1)
}

// 保存値はキーなので、一覧では表示名に戻す。
const entityLabel = (value: string | null) =>
    ENTITY_TYPES.find(o => o.value === value)?.label ?? '—'

const categoryLabel = (value: string | null) =>
    TRANSACTION_CATEGORIES.find(o => o.value === value)?.label ?? '—'

const sortMark = (column: PartnerSort) => {
    if (meta.sort !== column) return ''
    return meta.direction === 'asc' ? '▲' : '▼'
}

const openCreate = () => {
    editData.value = null
    formWindow.value = true
}

const openEdit = (partner: PartnerRecord) => {
    editData.value = partner
    formWindow.value = true
}

/** 編集モーダルから「紐付けを編集」で呼ばれる。編集モーダルは開いたままにする。 */
const openProjects = (partner: PartnerRecord) => {
    projectTarget.value = partner
    projectWindow.value = true
}

/**
 * 保存・削除後の受け口。既存レコードの保存はモーダルを閉じずに閲覧へ戻すので、
 * 手元のデータだけ差し替える。新規登録と削除（partnerが返らない）は閉じる。
 */
const onSaved = (partner?: PartnerRecord) => {
    if (editData.value && partner) {
        editData.value = partner
    } else {
        formWindow.value = false
        editData.value = null
    }
    getPartners()
}

const onProjectsSaved = (partner: PartnerRecord) => {
    projectWindow.value = false
    projectTarget.value = null
    // 編集モーダルが開いたままなら、紐付け結果を反映させる。
    if (editData.value && partner?.id === editData.value.id) {
        editData.value = partner
    }
    getPartners()
}

// 絞り込みは変更のたびに1ページ目から取り直す。
watch(filters, () => getPartners(1), { deep: true })

// 検索語は親（ProjectControl）から降りてくる。打つたびに叩かないよう少し待つ。
let searchTimer: number | undefined
watch(() => props.keywords, () => {
    window.clearTimeout(searchTimer)
    searchTimer = window.setTimeout(() => getPartners(1), 300)
})

onMounted(() => {
    toolsMounted.value = true
    getPartners(1)
})
</script>

<style scoped>
.partner-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    height: calc(100% - 120px);
    margin: 0 20px;
    overflow: hidden;
}

/* 検索欄の行に差し込む道具立て。件数は右端へ寄せる。 */
.partner-list__tools {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 12px;
}

.partner-list__range {
    margin-left: auto;
    color: var(--third-color);
    font-size: 12px;
    white-space: nowrap;
}

.partner-list__filter {
    position: relative;
}

.partner-list__filter-button {
    display: flex;
    align-items: center;
    gap: 6px;
    /* 検索欄と同じ高さ・角丸に揃える（隣り合うため）。 */
    height: 31px;
    padding: 0 10px;
    border-radius: 5px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    fill: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
}

/* 適用中は枠線の色だけで示す（太さは変えない）。 */
.partner-list__filter-button.is-on {
    border-color: var(--primary-color);
}

.partner-list__filter-count {
    color: var(--third-color);
}

.partner-list__filter-pop {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-width: 210px;
    max-height: 70vh;
    overflow: auto;
    padding: 14px;
    background: var(--background-color);
    border: 1px solid var(--calendarBorder);
    box-shadow: #3c40434d 0 1px 2px, #3c404326 0 2px 6px 2px;
}

.partner-list__filter-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--third-color);
    font-size: 12px;
}

.partner-list__filter-reset {
    padding: 4px 10px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 11px;
    cursor: pointer;
}

.partner-list__filter-reset:disabled {
    color: var(--third-color);
    cursor: not-allowed;
    opacity: 0.6;
}

.partner-list__filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.partner-list__filter-label {
    color: var(--third-color);
    font-size: 11px;
}

.partner-list__filter-option {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
}

.partner-list__table-wrap {
    position: relative;
    flex: 1;
    min-height: 0;
    overflow: auto;
    background: var(--background-color);
}

/*
 * table-layout: fixed で列幅を固定する。auto のままだと一番長い社名に引っ張られて
 * 表が枠からはみ出し、右側の列（プロジェクト・freee連携）が横スクロールの裏に隠れる。
 * 幅を決めておけば、あふれた文字は列の中で省略される。
 */
.partner-list__table {
    width: 100%;
    /* 管理画面のペインは狭くなることがある。これを下回ったら列を潰さず横スクロールさせる
       （下回ったときに幅指定の合計がペインを超え、取引先名の列が0pxまで潰れていた）。 */
    min-width: 880px;
    table-layout: fixed;
    border-collapse: collapse;
    font-size: 13px;
}

.partner-list__table th,
.partner-list__table td {
    /* 指定した列幅にpaddingを含める。content-boxのままだと1列あたり28px余分に広がり、
       合計がペイン幅を超えて取引先名の列が消える。 */
    box-sizing: border-box;
    padding: 0 14px;
    text-align: left;
    vertical-align: middle;
    /* はみ出しは折り返さず省略。行の高さを揃えるため。 */
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

/* 見出しは塗りつぶさず、細い罫線と字色だけで区切る。 */
.partner-list__table th {
    position: sticky;
    top: 0;
    z-index: 1;
    height: 38px;
    background: var(--background-color);
    border-bottom: 1px solid var(--primary-color);
    color: var(--third-color);
    font-size: 11px;
    font-weight: normal;
}

.partner-list__table td {
    height: 52px;
    border-bottom: 1px solid var(--calendarBorder);
    line-height: 1.5;
}

.partner-list__table th.is-sortable {
    cursor: pointer;
    user-select: none;
}

.partner-list__table th.is-sortable:hover {
    color: var(--primary-color);
}

.partner-list__sort {
    display: inline-block;
    width: 14px;
    font-size: 9px;
}

/* 列幅。取引先名だけ幅を指定せず、残りをすべて受け取る。 */
.partner-list__table th.is-entity,
.partner-list__table td.is-entity {
    width: 72px;
}

.partner-list__table th.is-category,
.partner-list__table td.is-category {
    width: 130px;
}

.partner-list__table th.is-contact,
.partner-list__table td.is-contact {
    width: 150px;
}

.partner-list__table th.is-projects,
.partner-list__table td.is-projects {
    width: 170px;
}

.partner-list__table th.is-freee,
.partner-list__table td.is-freee {
    width: 130px;
}

.partner-list__table td.is-name {
    padding-right: 20px;
}

.partner-list__name {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.partner-list__name-main {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

/* 使用不可は行の意味を変える情報なので、名前の真横に置いて見落とさせない。 */
.partner-list__flag {
    flex-shrink: 0;
    padding: 1px 6px;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--third-color);
    font-size: 10px;
}

.partner-list__table tbody tr.is-clickable {
    cursor: pointer;
}

.partner-list__table tbody tr.is-clickable:hover {
    background: var(--selected-background);
}

.partner-list__sub {
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    color: var(--third-color);
    font-size: 11px;
    line-height: 1.4;
}

.partner-list__chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 8px;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--third-color);
    font-size: 11px;
}

.partner-list__chip::before {
    flex-shrink: 0;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--check-inactive, #a1a1aa);
    content: '';
}

.partner-list__chip.is-linked::before {
    background: #22a447;
}

.partner-list__empty {
    padding: 40px 12px;
    color: var(--third-color);
    text-align: center;
}

/* ページ送りは表の下・中央。下端に貼り付かないよう余白を持たせる。 */
.partner-list__pager {
    display: flex;
    justify-content: center;
    flex-shrink: 0;
    padding: 10px 0 20px;
}

.partner-list__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--background-color) 65%, transparent);
}
</style>
