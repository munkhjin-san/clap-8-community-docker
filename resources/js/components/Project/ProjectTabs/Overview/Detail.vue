<template>
    <div class="relative" ref="scrollContainer" @scroll="handleScroll"> 
        <div class="project-detail flex flex-col gap-[15px] !px-5" :class="{'!pb-[70px] md:!pb-5': hasPrivilage && (auth.isBoss || auth.isAdmin) && selectedProject?.status == 'pending_director'}">
            <div v-if="hasPrivilage" class="ml-auto sticky top-0 z-10">
                <div class="flex gap-4 items-center">
                    <!-- Desktop: inline approval buttons -->
                    <div v-if="(auth.isBoss || auth.isAdmin) && selectedProject?.status == 'pending_director'" class="hidden sm:flex gap-4">
                        <button @click="statusChange('director_approved')" class="bg-[var(--primary-button)] text-white p-1">
                            承認する
                        </button>
                        <button @click="statusChange('returned')" class="bg-[var(--primary-button)] text-white p-1">
                            差し戻し
                        </button>
                    </div>
                </div>
            </div>
            <!-- Mobile: sticky bottom bar for approval buttons only -->
            <Teleport to="body">
                <div v-if="hasPrivilage && (auth.isBoss || auth.isAdmin) && selectedProject?.status == 'pending_director'"
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50 flex border-t border-[var(--calendarBorder)] bg-[var(--bg1)]">
                    <button @click="statusChange('director_approved')" class="flex-1 py-3 text-sm font-medium bg-[var(--primary-button)] text-white">
                        承認する
                    </button>
                    <button @click="statusChange('returned')" class="flex-1 py-3 text-sm font-medium bg-[var(--bg2)] text-[var(--primary-color)]">
                        差し戻し
                    </button>
                </div>
            </Teleport>
            <!-- 基本項目：ラベル幅を固定した定義リスト。2列で並べ、行ごとに罫線で区切る。 -->
            <section class="pd-fields">
                <div class="pd-field">
                    <span class="pd-field__label">プロジェクト種別</span>
                    <span class="pd-field__value" :class="{ 'is-empty': !projectTypeLabel }">{{ projectTypeLabel || '未設定' }}</span>
                </div>
                <div class="pd-field">
                    <span class="pd-field__label">ステータス</span>
                    <span class="pd-field__value" :class="{ 'text-[tomato]': checkItemConfirmBadge }">{{ PROJECT_STATUS_LABEL[selectedProject?.status ?? ''] ?? '不明' }}</span>
                </div>
                <div class="pd-field">
                    <span class="pd-field__label">プロジェクト期間</span>
                    <span class="pd-field__value" :class="{ 'is-empty': !periodText }">{{ periodText || '未設定' }}</span>
                </div>
                <div class="pd-field">
                    <span class="pd-field__label">契約開始日</span>
                    <span class="pd-field__value" :class="{ 'is-empty': !contractStartText }">{{ contractStartText || '未設定' }}</span>
                </div>
                <div class="pd-field">
                    <span class="pd-field__label">サービスカテゴリー</span>
                    <div class="pd-field__value">
                        <template v-if="selectedProject?.category?.length">
                            <div v-for="cat in selectedProject.category" :key="cat" class="pd-field__stack">
                                <div>{{ ProjectServiceCategories.find(c => c.value == cat)?.title }}</div>
                                <div class="pd-field__sub">{{ ProjectServiceCategories.find(c => c.value == cat)?.subtitle }}</div>
                            </div>
                        </template>
                        <span v-else class="is-empty">未設定</span>
                    </div>
                </div>
                <div class="pd-field">
                    <span class="pd-field__label">取引先</span>
                    <div class="pd-field__value">
                        <template v-if="selectedProject?.partner_records?.length">
                            <template v-for="(partner, index) in selectedProject.partner_records" :key="partner.id">
                                <span v-if="index" class="mr-[2px]">、</span>
                                <button type="button" class="partner-link" @click="openPartner(partner.id)">{{ partner.name }}</button>
                            </template>
                        </template>
                        <span v-else class="is-empty">未設定</span>
                    </div>
                </div>
                <div class="pd-field !border-b-0">
                    <span class="pd-field__label">顧客企業</span>
                    <span class="pd-field__value" :class="{ 'is-empty': !selectedProject?.customers?.length }">
                        {{ selectedProject?.customers?.length ? selectedProject.customers.join('、') : '未設定' }}
                    </span>
                </div>
                <div class="pd-field !border-b-0">
                    <span class="pd-field__label">業種区分</span>
                    <span class="pd-field__value" :class="{ 'is-empty': !selectedProject?.industry_type?.length }">
                        {{ selectedProject?.industry_type?.length ? selectedProject.industry_type.join('、') : '未設定' }}
                    </span>
                </div>
            </section>

            <!-- 本文ブロック：枠付きカードに見出しの角マーカーを添える。 -->
            <section class="pd-cards">
                <article class="pd-card pd-card--wide">
                    <div class="pd-card__head"><span class="pd-card__title">概要</span></div>
                    <ExpandableHtml :html="sanitized(selectedProject?.description ?? '')" :lines="5" />
                </article>

                <article v-if="hasPrivilage" class="pd-card pd-card--wide">
                    <div class="pd-card__head"><span class="pd-card__title">PM用非公開メモ</span></div>
                    <ExpandableHtml :html="sanitized(selectedProject?.private_memo ?? '')" :lines="5" />
                </article>

                <article class="pd-card">
                    <div class="pd-card__head"><span class="pd-card__title">ミッション</span></div>
                    <ExpandableHtml :html="sanitized(selectedProject?.mission ?? '')" :lines="5" />
                </article>
                <article class="pd-card">
                    <div class="pd-card__head"><span class="pd-card__title">イノベーション</span></div>
                    <ExpandableHtml :html="sanitized(selectedProject?.innovation ?? '')" :lines="5" />
                </article>
                <article class="pd-card">
                    <div class="pd-card__head"><span class="pd-card__title">ストラテジー</span></div>
                    <ExpandableHtml :html="sanitized(selectedProject?.strategy_miso ?? '')" :lines="5" />
                </article>
                <article class="pd-card">
                    <div class="pd-card__head"><span class="pd-card__title">オペレーション</span></div>
                    <ExpandableHtml :html="sanitized(selectedProject?.operation ?? '')" :lines="5" />
                </article>
            </section>


            <div v-if="hasPrivilage || (auth.isBoss || auth.isAdmin)">
                <MessageArea 
                    type="詳細"
                    :passing-data="passingData"
                    :item="{
                        ...selectedProject,
                        reports: selectedProject?.reports?.filter(report => report.type === '詳細') ?? []
                    }"
                    @refresh="updateProject([
                        { name: 'reports', include: ['user', 'files'] },
                    ])"
                />
            </div>
            
                                  
        </div>
    </div>

    <Teleport to="body">
        <PartnerModal
            v-if="partnerDetail"
            :partner="partnerDetail"
            readonly
            @close="partnerDetail = null"
        />
    </Teleport>
</template>
<script setup lang="ts">
import { computed, inject, onMounted, ref, useTemplateRef, watch } from 'vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { DateTime } from 'luxon';
import { useProject } from '@/composables/project';
import ProjectServiceCategories from 'assets/ProjectServiceCategories.json'
import { Project } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { useRoute } from 'vue-router';
import { EXPENSE_ITEMS, parseNumber, PROJECT_STATUS_LABEL, yenFmt } from '@/utils/tools';
import { useBadgeStore } from '@/store/badge';
import { useDashboardStore } from '@/store/dashboard';
import MessageArea from '../../MessageArea.vue';
import PartnerModal from '@/components/AccountControl/ProjectControl/PartnerModal.vue';
import ExpandableHtml from '@/components/Global/ExpandableHtml.vue';
import type { PartnerRecord } from '@/interface/partnerInterface';
    const props = defineProps(['hasPrivilage'])

    const asDate = (value?: string | null) => {
        if (!value) return null
        const parsed = DateTime.fromISO(value)

        return parsed.isValid ? parsed : null
    }
    const projectTypeLabel = computed(() =>
        selectedProject?.value?.projectType?.label ?? selectedProject?.value?.project_type?.label ?? '')
    const periodText = computed(() => {
        const start = asDate(selectedProject?.value?.date_start)
        const end = asDate(selectedProject?.value?.date_end)
        if (!start || !end) return ''

        return `${start.toLocaleString(DateTime.DATE_SHORT)}  ~  ${end.toLocaleString(DateTime.DATE_SHORT)}`
    })
    const contractStartText = computed(() =>
        asDate(selectedProject?.value?.contract_started_at)?.toLocaleString(DateTime.DATE_SHORT) ?? '')
    // 取引先の参照。編集・削除・freee連携は管理画面側にしか置かないので readonly で開く。
    const partnerDetail = ref<PartnerRecord | null>(null)
    const partnerLoading = ref(false)
    const openPartner = async (partnerId: number) => {
        if (partnerLoading.value) return
        partnerLoading.value = true
        try {
            const response = await api.get(`/partner_record/${partnerId}`)
            partnerDetail.value = response?.partner ?? null
        } catch {
            // メッセージは useApi が表示済み
        } finally {
            partnerLoading.value = false
        }
    }
    const editProjects = inject('editProjects') as (project: any) => void
    const deleteProject = inject('deleteProject') as (project: Project | null) => void
    const { selectedProject, updateProject, checkItemConfirmBadge, readProjectMessage } = useProject()
    const auth = useAuthUserStore()
    const checkTab = ref(false)
    const passingData = {
        path: '/project_checkitem_comment_add',
        title: 'メッセージ',
        file_path: 'project_checkitem_report_files'
    }
    const sanitized = (text: string) => {
        const clean = text ?? ''
        if(!clean) return '未設定'
        const markedText = marked.parse(clean) as string
        const saveText = DOMPurify.sanitize(markedText)
        return saveText
    }
    const { getBatchDashboardData } = useDashboardStore()
    const route = useRoute()
    const scrollRef = useTemplateRef('scrollContainer')
    onMounted(() => {
        // if(memoBody.value && memoBody.value?.clientHeight > 42){
        //     dynamicHeight.value = '42px'
        // }
        if (route.query.check) {
            checkTab.value = String(route.query.check) === 'true'
            const container = scrollRef.value
            if (container) {
                setTimeout(() => {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    }); 
                }, 100);
                
            }
        }   
    })
    const handleScroll = (event: Event) => {
        const el = event.target as HTMLElement

        const isBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10; // 10px threshold
        if (isBottom) {
            readProjectMessage('詳細')
        }
    }
    const planData = computed(() => {
        const raw = selectedProject.value?.specs?.plan_data
        if (!raw) return
        let parsed: any = raw
        if (typeof raw === 'string') {
            try {
                parsed = JSON.parse(raw)
            } catch {
                return
            }
        }
        if (!parsed || typeof parsed !== 'object') return
        return {
            ...parsed,
            lease: parsed.lease ?? parsed.leasing
        }
    })
    const totalExpenses = computed(() => EXPENSE_ITEMS.reduce((s, i) => s + parseNumber(planData.value?.[i.key]), 0) )
    const totalRevenue = computed(() => parseNumber(planData.value?.revenue))
    const profit = computed(() => {
        return totalRevenue.value - totalExpenses.value
    })

    const api = useApi()
    type ProjectStatus = 'director_approved' | 'returned'
    const badge = useBadgeStore()
    const statusChange = async(status: ProjectStatus) => {
        const question = status === 'director_approved' ? '承認しますか？' : '差し戻しますか？'
        const inform =  status === 'director_approved' ? '承認しました。' : '差し戻ししました。'
        await api.patch('/project_change_status', {
            status: status,
            id: selectedProject.value?.id
        }, {
            toast: inform,
            ask: question
        })
        updateProject([{name: 'status'}])
        badge.clearProjectConfirmBadge()
        getBatchDashboardData(['projects'])
    }
</script>
<style scoped>
    /* 取引先は押せることが分かるようにする（詳細モーダルを開く）。 */
    .partner-link{
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--primary-color);
        font-size: inherit;
        text-decoration: underline;
        text-underline-offset: 2px;
        cursor: pointer;
    }
    .partner-link:hover{
        opacity: 0.7;
    }

    @media screen and (max-width: 959px) {
        .project-cell-row:last-child .project-cell {
            border-bottom: 1px solid var(--calendarBorder) !important;
        }
        .project-table-container{
            border: none !important;
        }
        .project-cell:last-child{
            border-bottom: none !important;
        }
        .project-cell-row{
            margin-bottom: 20px !important;
            box-shadow: none !important;
        }
    }

/*
 * 概要タブのレイアウト。デザイン（Project page redesign）に合わせ、
 * 色はテーマ変数へ置き換えている（ダークモードで反転させるため）。
 */
.pd-fields{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 56px;
}
.pd-field{
    display: flex;
    gap: 20px;
    padding: 14px 2px;
}
.pd-field__label{
    flex: 0 0 118px;
    width: 118px;
    padding-top: 1px;
    color: var(--third-color);
    font-size: 12px;
    line-height: 1.6;
}
.pd-field__value{
    min-width: 0;
    font-size: 14px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}
/* 未入力はグレーで、埋まっている項目と読み分けられるようにする。 */
.pd-field__value.is-empty,
.pd-field__value .is-empty{
    color: var(--third-color);
}
.pd-field__stack + .pd-field__stack{
    margin-top: 10px;
}
.pd-field__sub{
    margin-top: 4px;
    color: var(--third-color);
    font-size: 12px;
    line-height: 1.6;
}

.pd-cards{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 8px;
}
.pd-card{
    padding: 20px 22px 22px;
    border: 1px solid var(--calendarBorder);
}
.pd-card--wide{
    grid-column: 1 / -1;
}
.pd-card__head{
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
/* 見出しの角マーカー。塗りと白抜きで隣り合うカードを区別する。 */
.pd-card__mark{
    width: 8px;
    height: 8px;
    background: var(--primary-color);
}
.pd-card__mark--hollow{
    background: transparent;
    border: 2px solid var(--primary-color);
}
.pd-card__title{
    font-size: 15px;
}

@media screen and (max-width: 959px){
    .pd-fields,
    .pd-cards{
        grid-template-columns: minmax(0, 1fr);
    }
    .pd-fields{
        gap: 0;
    }
    .pd-field{
        flex-direction: column;
        gap: 4px;
    }
    .pd-field__label{
        flex: none;
        width: auto;
    }
}
</style>
