<template>
    <div class="admin-window assistant-control">
        <div class="assistant-control__content">

            <div v-if="slots.length" class="assistant-control__workspace">
                <nav class="assistant-control__slot-list" aria-label="AI assistant slots">
                    <button
                        v-for="slot in slots"
                        :key="slot.key"
                        type="button"
                        :class="[
                            'assistant-control__slot-button',
                            { 'assistant-control__slot-button--active': slot.key === activeSlotKey },
                        ]"
                        @click="activeSlotKey = slot.key"
                    >
                        <span class="assistant-control__slot-copy">
                            <strong>{{ slot.title }}</strong>
                            <em>{{ slotStatus(slot.key) }}</em>
                        </span>
                        <span
                            v-if="slot.key === activeSlotKey"
                            class="assistant-control__slot-arrow"
                            aria-hidden="true"
                        >
                            <BackIcon size="12" />
                        </span>
                    </button>
                </nav>

                <section v-if="activeSlot" class="assistant-control__editor">
                    <ThemeAiSettingsSection
                        v-if="slotValues[activeSlot.key]"
                        v-model:model="slotValues[activeSlot.key].model"
                        v-model:instructions="slotValues[activeSlot.key].instructions"
                        v-model:settings-json="slotValues[activeSlot.key].settingsJson"
                        :model-options="modelOptionsFor(activeSlot.key)"
                        :settings-error="settingsErrors[activeSlot.key] ?? ''"
                    />
                    <div class="si-box">
                        <LoaderButton
                            content="保存"
                            :loading="loadingKeys.includes(activeSlot.key)"
                            @triggered="save(activeSlot)"
                        />
                    </div>
                </section>
            </div>

            <div v-else class="assistant-control__empty">
                このテーマで設定できるAIアシスタントはありません。
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, inject, onMounted, ref, watch } from 'vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import { LEARNING_MATERIAL_TYPES } from '@/config/learning'
import { useDialog } from '@/composables/dialog'
import { useLearningApi } from '@/composables/learningApi'
import { isEnabled } from '@/utils/learningProgress'
import type { LearningMaterial, LearningTheme, LearningThemeAiConfig } from '@/types/learning'
import ThemeAiSettingsSection from './Authoring/ThemeAiSettingsSection.vue'
import BackIcon from '@/components/Icons/Back.vue'

interface AssistantSlot {
    key: string
    title: string
    description: string
    lessonMaterialId: number | null
}

interface SlotValue {
    model: string | null
    instructions: string | null
    settingsJson: string
}

const props = defineProps<{
    theme: LearningTheme
}>()

const learningApi = useLearningApi()
const { ping } = useDialog()
const refreshThemes = inject<() => void | Promise<void>>('getThemes')

const openAiModels = ref<string[]>([])
const materials = ref<LearningMaterial[]>([])
const slotValues = ref<Record<string, SlotValue>>({})
const settingsErrors = ref<Record<string, string>>({})
const loadingKeys = ref<string[]>([])
const activeSlotKey = ref<string | null>(null)

const portfolioSlots = computed<AssistantSlot[]>(() => {
    const slots: AssistantSlot[] = [
        {
            key: 'portfolio_before_discussion',
            title: 'グループディスカッション前',
            description: 'ポートフォリオ下書きに対するレビュー・問いかけを行うAIです。',
            lessonMaterialId: null,
        },
        {
            key: 'portfolio_after_discussion',
            title: 'グループディスカッション後',
            description: '最終ポートフォリオ作成後のレビュー・整理を行うAIです。',
            lessonMaterialId: null,
        },
    ]

    if (props.theme.previous_version) {
        slots.unshift({
            key: 'portfolio_recurring_trainee',
            title: '再受講者向け',
            description: '前バージョンのポートフォリオとフィードバックを使い、再受講者用の個別教材・ディスカッションテーマを生成するAIです。',
            lessonMaterialId: null,
        })
    }

    // Salary-issue target themes get a dedicated slot used when a learner studies
    // this theme as a salary challenge (path 3).
    if (isEnabled(props.theme.salary_issue_target)) {
        slots.push({
            key: 'salary_issue',
            title: '昇給課題向け',
            description: '昇給課題として学習する受講者に、選択した成果目標を踏まえた個別教材・ディスカッションテーマを生成するAIです。',
            lessonMaterialId: null,
        })
    }

    return slots
})

const caseStudyMaterials = computed(() => {
    return materials.value.filter(material => material.material_type === LEARNING_MATERIAL_TYPES.CASE_STUDY)
})

const slots = computed<AssistantSlot[]>(() => {
    if (isEnabled(props.theme.portfolio)) {
        return portfolioSlots.value
    }

    if (isEnabled(props.theme.has_case_study)) {
        return caseStudyMaterials.value.map(material => ({
            key: `case_study_material_${material.id}`,
            title: material.title || `ケーススタディ ${material.id}`,
            description: 'このケーススタディ教材の回答レビュー・フィードバックを行うAIです。',
            lessonMaterialId: material.id,
        }))
    }

    return []
})

const introText = computed(() => {
    if (isEnabled(props.theme.portfolio)) {
        return 'ポートフォリオテーマでは、ディスカッション前後で別々のAI設定を使います。'
    }
    if (isEnabled(props.theme.has_case_study)) {
        return 'ケーススタディテーマでは、ケーススタディ教材ごとにAI設定を持てます。'
    }
    return 'テーマ構成に応じてAI設定スロットが表示されます。'
})

const activeSlot = computed(() => {
    return slots.value.find(slot => slot.key === activeSlotKey.value) ?? slots.value[0] ?? null
})

const stringifySettings = (settings: Record<string, unknown> | null | undefined) => {
    return settings ? JSON.stringify(settings, null, 2) : ''
}

const findConfig = (slotKey: string): LearningThemeAiConfig | undefined => {
    return props.theme.ai_configs?.find(config => config.config_key === slotKey)
}

const ensureSlotValues = () => {
    const nextValues: Record<string, SlotValue> = {}
    const nextErrors: Record<string, string> = {}

    slots.value.forEach(slot => {
        const existing = slotValues.value[slot.key]
        const config = findConfig(slot.key)
        nextValues[slot.key] = existing ?? {
            model: config?.model ?? null,
            instructions: config?.instructions ?? null,
            settingsJson: stringifySettings(config?.settings),
        }
        nextErrors[slot.key] = settingsErrors.value[slot.key] ?? ''
    })

    slotValues.value = nextValues
    settingsErrors.value = nextErrors
    if (!activeSlotKey.value || !slots.value.some(slot => slot.key === activeSlotKey.value)) {
        activeSlotKey.value = slots.value[0]?.key ?? null
    }
}

const slotStatus = (slotKey: string) => {
    const values = slotValues.value[slotKey]
    if (!values) return '未設定'
    const filled = [values.model, values.instructions, values.settingsJson].filter(value => value && value.trim()).length
    if (filled === 0) return '未設定'
    if (filled < 2) return '一部設定'
    return '設定済み'
}

const modelOptionsFor = (slotKey: string) => {
    const model = slotValues.value[slotKey]?.model
    const options = [...openAiModels.value]
    if (model && !options.includes(model)) {
        options.unshift(model)
    }
    return options
}

const getModels = async() => {
    openAiModels.value = await learningApi.getOpenAiModels()
}

const getMaterials = async() => {
    if (!isEnabled(props.theme.has_case_study)) {
        materials.value = []
        return
    }

    materials.value = await learningApi.getThemeMaterials(props.theme.id)
}

const parseSettings = (slotKey: string) => {
    settingsErrors.value = {
        ...settingsErrors.value,
        [slotKey]: '',
    }
    const raw = slotValues.value[slotKey]?.settingsJson.trim() ?? ''
    if (!raw) return null

    try {
        const parsed = JSON.parse(raw)
        if (!parsed || Array.isArray(parsed) || typeof parsed !== 'object') {
            settingsErrors.value = {
                ...settingsErrors.value,
                [slotKey]: '追加設定 JSON はオブジェクト形式で入力してください。',
            }
            return undefined
        }
        return parsed as Record<string, unknown>
    } catch {
        settingsErrors.value = {
            ...settingsErrors.value,
            [slotKey]: '追加設定 JSON の形式を確認してください。',
        }
        return undefined
    }
}

const setLoading = (slotKey: string, loading: boolean) => {
    loadingKeys.value = loading
        ? [...loadingKeys.value, slotKey]
        : loadingKeys.value.filter(key => key !== slotKey)
}

const save = async(slot: AssistantSlot) => {
    const values = slotValues.value[slot.key]
    if (!values || loadingKeys.value.includes(slot.key)) return

    const settings = parseSettings(slot.key)
    if (settings === undefined) {
        ping('AI設定の入力内容を確認してください。')
        return
    }

    setLoading(slot.key, true)
    try {
        const config = await learningApi.saveThemeAiConfig(props.theme.id, {
            config_key: slot.key,
            lesson_material_id: slot.lessonMaterialId,
            model: values.model,
            instructions: values.instructions,
            settings,
        })
        slotValues.value = {
            ...slotValues.value,
            [slot.key]: {
                model: config.model,
                instructions: config.instructions,
                settingsJson: stringifySettings(config.settings),
            },
        }
        await refreshThemes?.()
    } finally {
        setLoading(slot.key, false)
    }
}

watch(() => props.theme.id, async() => {
    await getMaterials()
    ensureSlotValues()
}, { immediate: true })

watch(slots, () => {
    ensureSlotValues()
})

onMounted(() => {
    getModels()
})
</script>

<style scoped>
.assistant-control{
    overflow: auto;
    background: var(--background-color);
}

.assistant-control__content{
    max-width: 980px;
    padding: 20px;
}

.assistant-control__intro{
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 18px;
}

.assistant-control__intro p{
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}

.assistant-control__intro span,
.assistant-control__slot-header span,
.assistant-control__empty{
    color: var(--light-color);
    font-size: 12px;
    line-height: 1.6;
}

.assistant-control__workspace{
    display: grid;
    grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
    gap: 18px;
    min-height: 0;
}

.assistant-control__slot-list{
    border: 1px solid var(--secondary-color);
    display: flex;
    flex-direction: column;
    height: fit-content;
}

.assistant-control__slot-button{
    align-items: flex-start;
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--secondary-color);
    color: var(--primary-color);
    cursor: pointer;
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) 16px;
    padding: 16px;
    text-align: left;
}

.assistant-control__slot-button--active{
    background: var(--bg3);
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
    margin: -1px;
}

.assistant-control__slot-copy{
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

.assistant-control__slot-copy strong{
    font-size: 13px;
    line-height: 1.5;
}

.assistant-control__slot-copy small,
.assistant-control__slot-copy em{
    font-size: 11px;
    font-style: normal;
    line-height: 1.5;
}

.assistant-control__slot-arrow{
    display: flex;
    margin-top: 8px;
    transform: rotate(180deg);
}

.assistant-control__slot-button--active .assistant-control__slot-copy em{
    color: var(--primary-color);
}

.assistant-control__editor{
    border: 1px solid var(--secondary-color);
    padding: 0 20px;
    min-width: 0;
}

.assistant-control__editor-header{
    display: flex;
    gap: 16px;
    justify-content: space-between;
    margin-bottom: 16px;
}

.assistant-control__editor-header p{
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 4px;
}

.assistant-control__empty{
    border: 1px solid var(--secondary-color);
    padding: 18px;
}

@media screen and (max-width: 900px) {
    .assistant-control__workspace{
        grid-template-columns: 1fr;
    }

    .assistant-control__slot-list{
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .assistant-control__slot-button,
    .assistant-control__slot-button:last-child{
        border-bottom: 1px solid var(--secondary-color);
    }
}
</style>
