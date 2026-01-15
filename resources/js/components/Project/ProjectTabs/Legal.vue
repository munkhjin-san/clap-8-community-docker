<template>
    <div class="legal-tab h-full bg-[var(--background-color)]">
        <div v-if="!hasPrivilage" class="legal-tab__empty">
            <p class="text-[gray]">権限がありません</p>
        </div>
        <div v-else class="legal-tab__body">
            <AiLoader v-if="aiLoading" message="徹底的な検査中です。<br>この処理には数分かかる場合があります。"/>
            
            <section v-if="contract" class="legal-summary">
                <div class="legal-summary__file">
                    <div class="legal-summary__icon">
                        <FileIcon :ext="fileMeta.extension" />
                    </div>
                    <div class="legal-summary__info">
                        <p class="legal-summary__title" :title="fileMeta.name">{{ fileMeta.name }}</p>
                        <p class="legal-summary__meta">
                            {{ statusLabel }}・{{ reviewTypeLabel }}レビュー
                        </p>
                        <p class="legal-summary__meta">
                            契約種別: {{ contractTypeLabel(contract.contract_type) }}
                        </p>
                        <p class="legal-summary__meta">
                            当事者区分: {{ contract.role }}
                        </p>
                        <p v-if="contract.updated_at" class="legal-summary__timestamp">
                            最終更新: {{ formatDate(contract.updated_at) }}
                        </p>
                    </div>
                </div>
                <div class="legal-summary__metrics">
                    <div class="legal-summary__metric">
                        <span class="legal-summary__metric-label">リスク</span>
                        <span :class="['legal-summary__badge', `legal-summary__badge--${summary.overallRisk}`]">
                            {{ severityLabel(summary.overallRisk) }}
                        </span>
                    </div>
                    <div class="legal-summary__metric">
                        <span class="legal-summary__metric-label">指摘件数</span>
                        <span class="legal-summary__metric-value">{{ summary.findings.length }}</span>
                    </div>
                </div>
                <div class="legal-summary__actions">
                    <LoaderButton 
                        class="legal-summary__button"
                        :content="renewalOpen ? '閉じる' : '契約書を更新'"
                        :loading="false"
                        @triggered="toggleRenewal"
                    />
                    <LoaderButton
                        v-if="downloadUrl"
                        class="legal-summary__button"
                        content="ダウンロード"
                        :loading="false"
                        @triggered="downloadContract"
                    />
                    <LoaderButton
                        class="legal-summary__button"
                        :content="detailOpen ? '閉じる' : '詳細を見る'"
                        :loading="false"
                        @triggered="toggleDetail"
                    />
                </div>
            </section>

            <!-- <section v-else-if="loading" class="legal-tab__empty">
                <p class="text-[gray]">レビュー情報を読み込んでいます…</p>
            </section> -->

            <!-- <section v-else class="legal-tab__empty">
                <p class="text-[gray]">レビュー済みの契約書がまだ登録されていません。</p>
                <p class="legal-tab__hint">
                    AIレビューを実行すると、ここに結果が表示されます。
                </p>
            </section> -->
            <transition v-if="renewalOpen || !contract" name="slide-fade">
                <section class="legal-upload-panel">
                    <div class="legal-upload-panel__head">
                        <div>
                            <p class="legal-upload-panel__title">契約書を更新</p>
                            <p class="legal-upload-panel__caption">新しいファイルをアップロードすると現在のレビュー結果が上書きされます。</p>
                        </div>
                        <p class="legal-upload-panel__badge">最新のみ保持</p>
                    </div>
                    <div class="legal-upload-panel__form">
                        <div class="legal-upload-panel__field">
                            <label class="legal-upload-panel__label">契約種別</label>
                            <select class="legal-upload-panel__select" v-model="uploadContractType">
                                <option
                                    v-for="type in contractTypeDefaults"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                            <p class="legal-upload-panel__hint" v-if="uploadFocus">{{ uploadFocus }}</p>
                        </div>
                        <div class="legal-upload-panel__field">
                            <label class="legal-upload-panel__label">当事者区分</label>
                            <div class="legal-upload-panel__chips">
                                <button
                                    v-for="role in contractRoleDefaults"
                                    :key="role.value"
                                    type="button"
                                    class="legal-upload-panel__chip"
                                    :class="{'legal-upload-panel__chip--active': uploadRole === role.value}"
                                    @click="uploadRole = role.value"
                                >
                                    {{ role.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        class="legal-upload"
                        :class="{'legal-upload--filled': !!uploadFileMeta}"
                        role="button"
                        tabindex="0"
                        @click="triggerUploadInput"
                        @keydown.enter.prevent="triggerUploadInput"
                        @keydown.space.prevent="triggerUploadInput"
                    >
                        <input
                            ref="uploadInput"
                            type="file"
                            class="legal-upload__input"
                            @change="handleUploadChange"
                        />
                        <template v-if="!uploadFileMeta">
                            <div class="legal-upload__placeholder">
                                <div class="legal-upload__icon">
                                    <FileIcon ext="file" />
                                </div>
                                <div class="legal-upload__text">
                                    <p class="legal-upload__title">契約書ファイルをアップロード</p>
                                    <p class="legal-upload__hint">PDF / Office ドキュメントなどを 1 件まで選択できます。</p>
                                    <p class="legal-upload__cta">クリックしてファイルを選択</p>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="legal-upload__content">
                                <div class="legal-upload__info">
                                    <div class="legal-upload__icon">
                                        <FileIcon :ext="uploadFileMeta.ext" />
                                    </div>
                                    <div class="legal-upload__details">
                                        <p class="legal-upload__filename" :title="uploadFileMeta.name">{{ uploadFileMeta.name }}</p>
                                        <p class="legal-upload__meta">{{ uploadFileMeta.sizeLabel }}</p>
                                    </div>
                                </div>
                                <div class="legal-upload__actions">
                                    <button type="button" class="legal-upload__btn legal-upload__btn--ghost" @click.stop="clearUploadFile">削除</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="legal-upload-panel__actions">
                        <LoaderButton
                            class="legal-summary__button"
                            content="AIレビュー & 更新"
                            :loading="uploadLoading"
                            @triggered="uploadContract"
                        />
                    </div>
                </section>
            </transition>
            
            <transition name="slide-fade">
                <section v-if="detailOpen && contract" class="legal-detail">
                    <div class="legal-detail__preview">
                        <div v-if="previewUrl" class="legal-detail__preview-frame">
                            <iframe
                                :src="previewUrl"
                                title="契約書プレビュー"
                                allowfullscreen
                            ></iframe>
                        </div>
                        <div v-else class="legal-detail__preview-empty">
                            <p>プレビューを表示できません。</p>
                            <p class="legal-tab__hint">
                                バックエンドで <code>GET /projects/{{ selectedProject?.id }}/contract/file</code> の実装が必要です。
                            </p>
                        </div>
                    </div>
                    <div class="legal-detail__findings">

                        <ContractFindings :contract="deepSummary ? deepSummary : summary" />
                        <LoaderButton v-if="deepSummary" :loading="saveLoading" class="mt-8" content="結果保存" @triggered="save_review(contract)" />
                        <LoaderButton v-else class="mt-8" :loading="aiLoading" content="徹底的な検査" @triggered="ai_review(contract)"/>
                    </div>
                </section>
            </transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { DateTime } from 'luxon'
import FileIcon from '@/components/Board/Mixed/FileIcon.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import ContractFindings from '@/components/Project/Legal/ContractFindings.vue'
import { useApi } from '@/composables/api'
import { Project, ProjectContractResponse, ContractFindingSeverity } from '@/interface/projectInterface'
import { filesize } from 'filesize'
import { contractTypeDefaults, contractRoleDefaults } from '@/utils/tools'
import { useDialog } from '@/composables/dialog'
import AiLoader from '@/components/Global/AiLoader.vue'

const props = defineProps<{
    selectedProject: Project | null
    hasPrivilage: boolean
}>()

const api = useApi()
const detailOpen = ref(false)
const loading = ref(false)
const contractState = ref<ProjectContractResponse | null>(null)
const fetchAttempted = ref(false)
const aiLoading = ref(false)
const saveLoading = ref(false)
const uploadLoading = ref(false)
const renewalOpen = ref(false)
const { ping } = useDialog()
const uploadInput = ref<HTMLInputElement | null>(null)
const uploadFile = ref<File | null>(null)
const uploadContractType = ref<string>(contractTypeDefaults[0]?.value ?? '')
const uploadRole = ref('乙')

const contract = computed<ProjectContractResponse | null>(() => {
    return contractState.value ?? props.selectedProject?.contract ?? null
})
const deepResult = ref()
const deepSummary = computed(() => {
    if (!deepResult.value) {
        return null
    }
    const raw = deepResult.value.json ?? {}
    const overall = (raw.overall_risk ?? 'unknown') as ContractFindingSeverity
    const findings = Array.isArray(raw.findings) ? raw.findings : []

    return {
        overallRisk: overall || 'unknown',
        findings: findings.map((item: any) => ({
            section: item.section,
            issue: item.issue ?? item.title ?? '',
            severity: (item.severity ?? 'unknown') as ContractFindingSeverity,
            rationale: item.rationale ?? item.reason ?? '',
            suggestion: item.suggestion ?? item.remedy ?? '',
            category: item.category,
            score: item.score,
            quote: item.quote,
            negotiation_tip: item.negotiation_tip
        })),
    }
})
const summary = computed(() => {
    if (!contract.value) {
        return {
            overallRisk: 'unknown' as ContractFindingSeverity,
            findings: [],
        }
    }
    const raw = contract.value.result_json ?? (contract.value as any).json ?? {}
    const overall = (raw.overall_risk ?? contract.value.overall_risk ?? 'unknown') as ContractFindingSeverity
    const findings = Array.isArray(raw.findings) ? raw.findings : []

    return {
        overallRisk: overall || 'unknown',
        findings: findings.map((item: any) => ({
            section: item.section,
            issue: item.issue ?? item.title ?? '',
            severity: (item.severity ?? 'unknown') as ContractFindingSeverity,
            rationale: item.rationale ?? item.reason ?? '',
            suggestion: item.suggestion ?? item.remedy ?? '',
            category: item.category ?? '',
            score: item.score ?? '',
            quote: item.quote ?? '',
            negotiation_tip: item.negotiation_tip ?? '',
        })),
    }
})

const fileMeta = computed(() => {
    if (!contract.value?.file_path) {
        return {
            name: 'レビュー結果',
            extension: 'file',
            sizeLabel: '',
        }
    }
    const segments = contract.value.file_path.split('/')
    const name = segments[segments.length - 1]
    const extension = name.includes('.') ? name.split('.').pop() : 'file'
    const size = contract.value.file_size ?? contract.value.size ?? null
    return {
        name,
        extension: extension?.toString().toLowerCase() || 'file',
        sizeLabel: size ? filesize(size, size > 1_000_000 ? { standard: 'jedec', round: 1 } : { standard: 'jedec', round: 0 }) : '',
    }
})

const reviewTypeLabel = computed(() => {
    if (!contract.value) return ''
    return contract.value.review_type === 'deep' ? 'ディープ' : 'クイック'
})
const statusLabel = computed(() => {
    if(!contract.value) return ''
    return contract.value.active === false ? '契約終了' : '稼動中'
})

const previewUrl = computed(() => {
    if (!contract.value) return null
    if (contract.value.file_url) return contract.value.file_url
    if (contract.value.file_path && props.selectedProject?.id) {
        return `/projects/${props.selectedProject.id}/contract/file`
    }
    return null
})

const downloadUrl = computed(() => {
    if (!contract.value?.file_path || !props.selectedProject?.id) return null
    return `/projects/${props.selectedProject.id}/contract/download`
})

const severityLabel = (severity: ContractFindingSeverity) => {
    switch (severity) {
        case 'high':
            return '高'
        case 'medium':
            return '中'
        case 'low':
            return '低'
        default:
            return '不明'
    }
}

const formatDate = (value: string) => {
    return DateTime.fromISO(value).toFormat('yyyy年MM月dd日 HH:mm')
}

const toggleDetail = () => {
    detailOpen.value = !detailOpen.value
}
const toggleRenewal = () => {
    renewalOpen.value = !renewalOpen.value
}
const downloadContract = () => {
    if (downloadUrl.value) {
        window.open(downloadUrl.value, '_blank', 'noopener')
    }
}

const fetchContract = async (force = false) => {
    if (!props.selectedProject?.id) return
    if (!force) {
        if (fetchAttempted.value) return
        fetchAttempted.value = true
    }
    try {
        const data = await api.get(`/projects/${props.selectedProject.id}/contract`, null, { loadingRef: loading, silent: true })
        contractState.value = data.exists ? data.contract : null
    } catch (error) {
        contractState.value = null
    }
}
const contractTypeLabel = (value: string) => {
    return contractTypeDefaults.find(r => r.value === value)?.label ?? '—'
}
const getContractBlob = async()=> {
  if (!previewUrl.value) throw new Error('No preview URL')
  const res = await fetch(previewUrl.value, { credentials: 'include' }) // send session cookie
  if (!res.ok) throw new Error('Failed to fetch contract')
  return await res.blob()
}
const ai_review = async(contract: ProjectContractResponse) => {
    if (!contract.file_path) { ping('契約ファイルがありません'); return }
    const blob = await getContractBlob()
    const filename = fileMeta.value.name
    if(!blob) return
    const file = new File([blob], filename, { type: blob.type || 'application/octet-stream' })
    const formData = new FormData();
    formData.append('file', file)
    formData.append('role', contract.role)
    formData.append('type', contract.contract_type)
    formData.append('review_type', 'deep')
    const data = await api.post('/review_document', formData, {loadingRef: aiLoading})
    if (data) {
        deepResult.value = data
    }
}
const save_review = async(contract: ProjectContractResponse) => {
    if (!deepSummary.value) { ping('保存するレビューはありません'); return }
    await api.post('/save_review', {id: contract.id, summary: deepSummary.value}, {toast: '保存しました。', loadingRef: saveLoading})
    fetchContract()
}
watch(
    () => props.selectedProject?.id,
    () => {
        contractState.value = null
        detailOpen.value = false
        fetchAttempted.value = false
        uploadFile.value = null
        if (uploadInput.value) {
            uploadInput.value.value = ''
        }
        if (!props.selectedProject?.contract) {
            fetchContract()
        }
    },
    { immediate: true }
)

watch(
    () => contract.value,
    (newContract) => {
        if (newContract?.role) {
            uploadRole.value = newContract.role as '甲' | '乙'
        } else {
            uploadRole.value = '乙'
        }
        if (newContract?.contract_type) {
            uploadContractType.value = newContract.contract_type
        } else if (!uploadContractType.value && contractTypeDefaults.length) {
            uploadContractType.value = contractTypeDefaults[0].value
        }
        deepResult.value = null
    },
    { immediate: true }
)

onMounted(() => {
    if (!props.selectedProject?.contract) {
        fetchContract()
    }
})

const uploadFileMeta = computed(() => {
    if (!uploadFile.value) return null
    const name = uploadFile.value.name
    const extension = name.includes('.') ? name.split('.').pop()?.toLowerCase() || 'file' : 'file'
    const sizeLabel = filesize(
        uploadFile.value.size,
        uploadFile.value.size > 1_000_000 ? { standard: 'jedec', round: 1 } : { standard: 'jedec', round: 0 }
    )
    return { name, ext: extension, sizeLabel }
})

const uploadFocus = computed(() => {
    return contractTypeDefaults.find(item => item.value === uploadContractType.value)?.focus ?? ''
})

const triggerUploadInput = () => {
    uploadInput.value?.click()
}

const handleUploadChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target && target.files ? target.files[0] : null
    if (file) {
        uploadFile.value = file
    }
}

const clearUploadFile = () => {
    uploadFile.value = null
    if (uploadInput.value) {
        uploadInput.value.value = ''
    }
}

const uploadContract = async () => {
    if (!props.selectedProject?.id) {
        ping('プロジェクトが見つかりません。')
        return
    }
    if (!uploadFile.value) {
        ping('契約書ファイルを選択してください。')
        return
    }
    const formData = new FormData()
    formData.append('file', uploadFile.value)
    formData.append('role', uploadRole.value)
    formData.append('type', uploadContractType.value)
    formData.append('review_type', 'quick')

    const review = await api.post('/review_document', formData, { loadingRef: uploadLoading })
    if (!review?.json || !review?.path) {
        ping('レビュー結果の取得に失敗しました。')
        return
    }

    const payload = await api.post(`/projects/${props.selectedProject.id}/contract`, {
        contract_data: review.json,
        file_path: review.path,
        contract_role: review.role,
        contract_type: review.type,
    }, { loadingRef: uploadLoading, toast: '契約書を更新しました。' })

    if (payload) {
        contractState.value = payload
        clearUploadFile()
        deepResult.value = null
        fetchContract(true)
    }
}
</script>

<style scoped>
.legal-tab {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.legal-tab__body {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px;
    height: 100%;
    box-sizing: border-box !important;
}

.legal-summary {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 220px) auto;
    gap: 24px;
    align-items: center;
    padding: 24px;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
}

.legal-summary__file {
    display: flex;
    gap: 16px;
    align-items: center;
}

.legal-summary__icon {
    width: 64px;
    height: 64px;
    min-width: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.legal-summary__info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.legal-summary__title {
    font-size: 16px;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: normal;
}

.legal-summary__meta,
.legal-summary__timestamp {
    font-size: 12px;
    color: var(--font-color, #666);
    margin: 0;
}

.legal-summary__metrics {
    display: flex;
    flex-direction: column;
    gap: 12px;
    justify-content: center;
}

.legal-summary__metric {
    display: flex;
    gap: 12px;
    align-items: center;
}

.legal-summary__metric-label {
    font-size: 12px;
    color: var(--font-color, #666);
}

.legal-summary__metric-value {
    font-size: 18px;
    font-weight: 600;
    color: var(--primary-color);
}

.legal-summary__badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid var(--calendarBorder);
}

.legal-summary__badge--high {
    color: #d14343;
    border-color: rgba(209, 67, 67, 0.4);
    background: rgba(209, 67, 67, 0.08);
}

.legal-summary__badge--medium {
    color: #ff8a00;
    border-color: rgba(255, 138, 0, 0.4);
    background: rgba(255, 138, 0, 0.08);
}

.legal-summary__badge--low {
    color: #4c566a;
    border-color: rgba(76, 86, 106, 0.3);
    background: rgba(76, 86, 106, 0.08);
}

.legal-summary__badge--unknown {
    color: var(--font-color, #666);
    background: rgba(0, 0, 0, 0.04);
}

.legal-summary__actions {
    display: flex;
    gap: 12px;
}

.legal-summary__button {
    min-width: 120px;
}

.legal-upload-panel {
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.legal-upload-panel__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.legal-upload-panel__title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.legal-upload-panel__caption {
    font-size: 12px;
    margin: 0;
    color: var(--font-color, #666);
}

.legal-upload-panel__badge {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid var(--calendarBorder);
    color: var(--font-color, #666);
}

.legal-upload-panel__form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}

.legal-upload-panel__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.legal-upload-panel__label {
    font-size: 12px;
    color: var(--font-color, #666);
}

.legal-upload-panel__select {
    border: 1px solid var(--calendarBorder);
    padding: 8px 12px;
    background: var(--background-color);
    color: var(--primary-color);
}

.legal-upload-panel__hint {
    font-size: 11px;
    color: var(--font-color, #777);
    margin: 0;
}

.legal-upload-panel__chips {
    display: flex;
    gap: 8px;
}

.legal-upload-panel__chip {
    border: 1px solid var(--calendarBorder);
    background: transparent;
    padding: 6px 14px;
    font-size: 12px;
    cursor: pointer;
    color: var(--font-color, #555);
    transition: all 0.2s ease;
}

.legal-upload-panel__chip--active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--background-color);
}

.legal-upload-panel__actions {
    display: flex;
    justify-content: flex-end;
}

.legal-upload {
    display: flex;
    flex-direction: column;
    gap: 16px;
    border: 1px dashed var(--primary-color);
    background: var(--bg3);
    padding: 24px;
    cursor: pointer;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}

.legal-upload:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.legal-upload--filled {
    border-style: solid;
}

.legal-upload__input {
    display: none;
}

.legal-upload__placeholder {
    display: flex;
    gap: 20px;
    align-items: center;
}

.legal-upload__icon {
    width: 64px;
    min-width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.legal-upload__text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.legal-upload__title {
    font-weight: 600;
    font-size: 14px;
    margin: 0;
    color: var(--primary-color);
}

.legal-upload__hint,
.legal-upload__cta {
    font-size: 12px;
    margin: 0;
    color: var(--font-color, #555);
}

.legal-upload__content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.legal-upload__info {
    display: flex;
    gap: 16px;
    align-items: center;
}

.legal-upload__details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.legal-upload__filename {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    color: var(--primary-color);
    word-break: break-all;
}

.legal-upload__meta {
    font-size: 12px;
    color: var(--font-color, #666);
    margin: 0;
}

.legal-upload__actions {
    display: flex;
    gap: 12px;
}

.legal-upload__btn {
    background: var(--primary-color);
    color: var(--background-color);
    border: none;
    padding: 6px 18px;
    font-size: 12px;
    cursor: pointer;
}

.legal-upload__btn--ghost {
    background: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.legal-detail {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
    gap: 24px;
    flex: 1;
    min-height: 0;
}

.legal-detail__preview,
.legal-detail__findings {
    background: var(--background-color);
    border: 1px solid var(--calendarBorder);
    padding: 20px;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden auto;
}

.legal-detail__preview-frame {
    flex: 1;
    border: 1px solid var(--calendarBorder);
    overflow: hidden;
}

.legal-detail__preview-frame iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: var(--background-color);
}

.legal-detail__preview-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--font-color, #666);
    font-size: 13px;
    text-align: center;
}

.legal-tab__empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-align: center;
    color: var(--font-color, #666);
}

.legal-tab__hint {
    font-size: 12px;
    color: var(--font-color, #888);
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.25s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

@media (max-width: 1199px) {
    .legal-summary {
        grid-template-columns: minmax(0, 1fr);
        gap: 16px;
    }

    .legal-summary__actions {
        justify-content: flex-start;
    }

    .legal-detail {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 599px) {
    .legal-tab__body {
        padding: 16px;
    }

    .legal-summary {
        padding: 16px;
    }

    .legal-upload {
        padding: 18px;
    }

    .legal-upload-panel__head {
        flex-direction: column;
        align-items: flex-start;
    }

    .legal-upload-panel__form {
        grid-template-columns: 1fr;
    }
}
</style>
