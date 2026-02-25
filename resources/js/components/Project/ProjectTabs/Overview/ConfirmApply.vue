<template>
    <div v-if="hasPrivilage" class="project-apply-form">
        <div v-if="validationErrors.length" class="apply-error-box">
            <p class="font-semibold">入力内容を確認してください。</p>
            <ul class="apply-error-list">
                <li v-for="error in validationErrors" :key="error">{{ error }}</li>
            </ul>
        </div>
        <section>
            <p class="apply-section-title">案件タイプ</p>

            <div class="apply-question">
                <p class="apply-question-title">1. 当該案件の種類を選択してください <span class="required-mark">※必須</span></p>
                <div class="apply-option-list">
                    <label class="apply-option">
                        <input v-model="form.caseType.continuingExpected" class="custom-f-checkbox" :disabled="confirming" type="checkbox">
                        継続見込み案件
                    </label>
                    <label class="apply-option">
                        <input v-model="form.caseType.spotLimited" class="custom-f-checkbox" :disabled="confirming" type="checkbox">
                        スポット（期間限定）案件
                    </label>
                </div>
                <div v-if="form.caseType.spotLimited" class="apply-branch">
                    <p>期間を入力してください <span class="required-mark">※必須</span></p>
                    <div class="flex gap-4"> 
                        <ShortInput customClass="date" type="date" v-model="form.spot_period.start" :disabled="confirming" />
                        <ShortInput customClass="date" type="date" v-model="form.spot_period.end" :disabled="confirming" />
                    </div>
                </div>
            </div>

            <div class="apply-question">
                <p class="apply-question-title">2. 契約形態を選択してください <span class="required-mark">※必須</span></p>
                <div class="apply-option-list">
                    <label class="apply-option">
                        <input v-model="form.contractType.fixed" class="custom-f-checkbox" :disabled="confirming" type="checkbox">
                        固定案件
                    </label>
                    <label class="apply-option">
                        <input v-model="form.contractType.shot" class="custom-f-checkbox" :disabled="confirming" type="checkbox">
                        ショット案件
                    </label>
                </div>

                <div v-if="form.contractType.shot" class="apply-branch">
                    <p class="apply-sub-title">ショット案件の追加確認</p>
                    <div v-for="question in shotQuestions" :key="question.key" class="apply-sub-question">
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.shotChecklist[question.key]" class="custom-f-checkbox" :disabled="confirming" :name="`shot_${question.key}`" type="checkbox">
                                {{question.label}}
                            </label>
                        </div>
                    </div>
                    <div v-if="confirming && form.shotNotes">
                        <p class="mb-2">備考</p>
                        <p class="p-2 bg-[var(--background-color)]">{{form.shotNotes}}</p>
                    </div>
                    <textarea
                        v-else-if="!confirming"
                        v-model="form.shotNotes"
                        class="custom-a-input !w-full !min-h-[120px]"
                        placeholder="備考"
                    />
                </div>
            </div>
        </section>

        <section>
            <p class="apply-section-title">物品・設備関連</p>

            <div class="apply-question">
                <div class="apply-option-inline">
                    <label class="apply-option">
                        <input v-model="form.equipmentPurchaseRequired" class="custom-f-checkbox" :disabled="confirming" name="equipment_purchase" type="checkbox">
                        3. 物品・備品の購入が発生する
                    </label>
                </div>

                <div v-if="form.equipmentPurchaseRequired" class="apply-branch">
                    <div v-for="question in equipmentQuestions" :key="question.key" class="apply-sub-question">
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.equipmentChecklist[question.key]" class="custom-f-checkbox" :disabled="confirming" :name="`equipment_${question.key}`" type="checkbox">
                                {{ question.label }}
                            </label>
                        </div>
                    </div>
                    <div v-if="confirming && form.equipmentNotes">
                        <p class="mb-2">備考</p>
                        <p class="p-2 bg-[var(--background-color)]">{{form.equipmentNotes}}</p>
                    </div>
                    <textarea
                        v-else-if="!confirming"
                        v-model="form.equipmentNotes"
                        class="custom-a-input !w-full !min-h-[120px]"
                        placeholder="備考"
                    />
                </div>
            </div>

            <div class="apply-question">
                <div class="apply-option-inline">
                    <label class="apply-option">
                        <input v-model="membersRequired" class="custom-f-checkbox" :disabled="confirming" name="procurement_required" type="checkbox" value="yes">
                        4. 各メンバーの想定人数を入力してください
                    </label>
                </div>
                <div v-if="membersRequired" class="apply-branch">
                    <div v-for="question in membersQuestions" :key="question.key" class="apply-sub-question">
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.membersCheckList[question.key]" class="custom-f-checkbox" :disabled="confirming" :name="`members_${question.key}`" type="checkbox">
                                {{ question.label }}
                            </label>
                        </div>
                        <div v-if="form.membersCheckList[question.key] && question.hasCountInput" class="apply-option-inline">
                            <div v-if="confirming">
                                <p>【数字入力】名 : {{ form.membersCheckList[`${question.key}_count`] }}</p>
                            </div>
                           
                            <input
                                v-else
                                v-model.number="form.membersCheckList[`${question.key}_count`]"
                                class="custom-a-input !w-full max-w-[200px]"
                                type="number"
                                min="0"
                                placeholder="【数字入力】名"
                            />

                        </div>
                    </div>
                </div>
            </div>

            <div class="apply-question">
                <div class="apply-option-inline">
                    <label class="apply-option">
                        <input v-model="form.inventoryRequired" class="custom-f-checkbox" :disabled="confirming" name="inventory_required" type="checkbox">
                        5. 商品・資材等の仕入れを伴う業務（在庫を保有する可能性）がある
                    </label>
                </div>
            </div>
            <div class="apply-question">
                <div class="apply-option-inline">
                    <label class="apply-option">
                        <input v-model="form.splitBillingRequired" class="custom-f-checkbox" :disabled="confirming" name="split_billing_required" type="checkbox">
                        6. 請求方法は、一括計上ではなく契約期間・役務提供期間に応じた分割計上が必要である
                    </label>
                </div>
            </div>
            <div class="apply-question">
                <p class="apply-question-title">7. 下記URLより年間収支計画を入力してください <span class="required-mark">※必須</span></p>
                <a href="https://docs.google.com/spreadsheets/d/1Xo9s8n7j3nqLh0kKZt2r5c6e7f8g9h0i1j2k3l4m5/edit?usp=sharing" target="_blank" class="text-blue-600 underline">
                    年間収支計画フォーマット
                </a>
            </div>
            <div class="apply-question">
                <p class="apply-question-title">8. 個別の計画シミュレーションがあれば添付またはURLの貼付</p>
                <div v-if="confirming && files?.length">
                    <Files 
                        :items="files"
                        :path="'project_files'"
                    />
                </div>
                <div v-else-if="!confirming" class="mt-[10px]">
                    <FileUploader
                        v-model="form.referenceFiles"
                        path="/project_files"
                        custom-place-holder="添付"
                        custom-style="border: 1px solid var(--formBorder);"
                        custom-class="uploader-hover"
                    />
                </div>
                <div v-if="confirming">
                    <p class="mb-2">URL共有用</p>
                    <template v-for="(p, i) in parts" :key="i">
                        <a
                            v-if="p.type === 'link'"
                            :href="p.href"
                            target="_blank"
                            rel="noopener noreferrer"
                        >{{ p.value }}</a>
                        <span v-else>{{ p.value }}</span>
                    </template>
                </div>
                <div v-else class="mt-[15px]">
                    <textarea
                        v-model="form.referenceText"
                        class="custom-a-input !w-full !min-h-[120px]"
                        placeholder="URL共有用"
                    />
                </div>
            </div>
        </section>

        <section>
            <p class="apply-section-title">アカウント・設備支給</p>

            <div class="apply-question">
                <div class="apply-option-inline">
                    <label class="apply-option">
                        <input v-model="necessaryItemsRequired" class="custom-f-checkbox" :disabled="confirming" name="necessary_items_required" type="checkbox">
                        9. 必要な項目にチェックをしてください
                    </label>
                </div>
                <div v-if="necessaryItemsRequired" class="apply-branch">
                    <div v-for="question in necessaryItemsQuestions" :key="question.key" class="apply-sub-question">
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.necessaryItemsChecklist[question.key]" class="custom-f-checkbox" :disabled="confirming" :name="`necessary_items_${question.key}`" type="checkbox">
                                {{ question.label }}
                            </label>
                        </div>
                        

                    </div>
                    <div v-if="confirming && form.necessaryItemsDetail">
                        <p class="mb-2">その他</p>
                        <p class="p-2 bg-[var(--background-color)]">{{form.necessaryItemsDetail}}</p>
                    </div>
                    <textarea
                        v-else-if="!confirming"
                        v-model="form.necessaryItemsDetail"
                        class="custom-a-input !w-full !min-h-[120px]"
                        placeholder="その他"
                    />
                </div>
            </div>

            
        </section>

        <section>
            <p class="apply-section-title">その他</p>
            <div class="apply-question">
                <p class="apply-question-title">10. その他共有事項等あれば入力をおねがいします</p>
                <p v-if="confirming">{{ form.otherNotes }}</p>
                <textarea v-else v-model="form.otherNotes" class="custom-a-input !w-full !min-h-[120px]" placeholder="備考（任意）" />
            </div>
        </section>

        <div v-if="!confirming" class="flex justify-center gap-[30px]">
            <LoaderButton @triggered="saveDraft" :loading="props.saving" content="下書き保存" style="margin: 0;"/>
            <LoaderButton @triggered="submitForm" :loading="props.saving" content="申請する" style="margin: 0;"/>
        </div>
    </div>
    <div v-else class="h-[calc(100%-115px)] w-full flex items-center justify-center">
        権限がありません
    </div>
</template>

<script setup lang="ts">
import FileUploader from '@/components/Form/FileUploader.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useDialog } from '@/composables/dialog';
import { computed, reactive, ref, watch } from 'vue';
import {
    buildConfirmApplyPayload,
    createDefaultConfirmApplyFormState,
    equipmentQuestions,
    hasMembersInput,
    hasNecessaryItemsInput,
    hydrateConfirmApplyForm,
    membersQuestions,
    necessaryItemsQuestions,
    type ProjectApplyPayload,
    resetChecklist,
    resetMembersChecklist,
    shotQuestions,
} from './confirmApplyMapper';
import { CommonFile } from '@/interface/globalInterface';
import Files from '@/components/Global/Files.vue';
import { linkifyParts } from '@/utils/tools';

const emit = defineEmits<{
    (e: 'close', flag: boolean): void
    (e: 'save-draft', payload: ProjectApplyPayload): void
    (e: 'submit', payload: ProjectApplyPayload): void
}>()
const props = withDefaults(defineProps<{
    hasPrivilage: boolean
    editData?: ProjectApplyPayload
    saving?: boolean
    files?: CommonFile[]
    confirming?: boolean
}>(), {
    editData: undefined,
    saving: false,
    files: undefined,
    confirming: false
})
const { ping } = useDialog()
const validationErrors = ref<string[]>([])

const form = reactive(createDefaultConfirmApplyFormState())
const specReferenceFileIds = ref<number[]>([])
const membersSectionEnabled = ref(false)
const necessaryItemsSectionEnabled = ref(false)

const membersRequired = computed({
    get: () => membersSectionEnabled.value || hasMembersInput(form.membersCheckList),
    set: (value: boolean) => {
        membersSectionEnabled.value = value
        if (!value) {
            resetMembersChecklist(form.membersCheckList)
        }
    },
})

const necessaryItemsRequired = computed({
    get: () =>
        necessaryItemsSectionEnabled.value ||
        hasNecessaryItemsInput(form.necessaryItemsChecklist, form.necessaryItemsDetail),
    set: (value: boolean) => {
        necessaryItemsSectionEnabled.value = value
        if (!value) {
            resetChecklist(form.necessaryItemsChecklist)
            form.necessaryItemsDetail = ''
        }
    },
})
const parts = computed(() => linkifyParts(form.referenceText))
const resetFormState = () => {
    Object.assign(form, createDefaultConfirmApplyFormState())
    specReferenceFileIds.value = []
    validationErrors.value = []
    membersSectionEnabled.value = false
    necessaryItemsSectionEnabled.value = false
}
watch(
    [() => props.editData, () => props.files],
    ([editData, files]) => {
        resetFormState()
        if (editData) {
            specReferenceFileIds.value = hydrateConfirmApplyForm(form, editData)
        }
        if (files?.length) {
            form.referenceFiles = [...files]
            specReferenceFileIds.value = []
        }
    },
    { immediate: true, deep: true }
)
const autoResetOnFalse = (source: () => boolean, resetFn: () => void): void => {
    watch(source, (value) => {
        if (!value) {
            resetFn()
        }
    })
}

autoResetOnFalse(
    () => form.caseType.spotLimited,
    () => {
        form.spot_period.start = ''
        form.spot_period.end = ''
    }
)
autoResetOnFalse(
    () => form.contractType.shot,
    () => {
        resetChecklist(form.shotChecklist)
        form.shotNotes = ''
    }
)
autoResetOnFalse(
    () => form.equipmentPurchaseRequired,
    () => {
        resetChecklist(form.equipmentChecklist)
        form.equipmentNotes = ''
    }
)

const validateForm = (): string[] => {
    const errors: string[] = []
    
    if (!form.caseType.continuingExpected && !form.caseType.spotLimited) {
        errors.push('1. 案件の種類を1つ以上選択してください。')
    }
    if (form.caseType.spotLimited && (!form.spot_period.start || !form.spot_period.end)) {
        errors.push('1. スポット案件の期間を入力してください。')
    }

    if (!form.contractType.fixed && !form.contractType.shot) {
        errors.push('2. 契約形態を1つ以上選択してください。')
    }
   
    return errors
}

const submitForm = () => {
    const errors = validateForm()
    validationErrors.value = errors
    if (errors.length) {
        ping('必須項目を確認してください。')
        return
    }

    const payload = buildConfirmApplyPayload(form, specReferenceFileIds.value)
    emit('submit', payload)
}
const saveDraft = () => {
    validationErrors.value = []
    const payload = buildConfirmApplyPayload(form, specReferenceFileIds.value)
    emit('save-draft', payload)
}
</script>

<style scoped>
.uploader-hover:hover {
    border: 1px solid var(--primary-color) !important;
    cursor: pointer;
}
.custom-a-input {
    box-sizing: border-box !important;
}
.project-apply-form {
    display: flex;
    flex-direction: column;
    gap: 30px;
    height: calc(100% - 90px);
    overflow: hidden auto;
    padding: 0 30px 30px;
}

.apply-section-title {
    font-size: 16px;
    font-weight: 600;
}

.apply-question {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.apply-question-title {
    font-size: 16px;
    line-height: 1.6;
}

.apply-sub-title {
    font-size: 13px;
    margin-bottom: 8px;
}

.apply-sub-question {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
}

.apply-option-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.apply-option-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
}

.apply-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.apply-branch {
    padding: 12px;
    border: 1px solid var(--formBorder);
    background: var(--bg3);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.apply-note {
    font-size: 12px;
    color: gray;
}

.required-mark {
    color: tomato;
    font-size: 12px;
}

.apply-error-box {
    border: 1px solid tomato;
    padding: 12px;
    background: rgba(255, 99, 71, 0.08);
    font-size: 13px;
}

.apply-error-list {
    margin-top: 8px;
    padding-left: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
</style>
