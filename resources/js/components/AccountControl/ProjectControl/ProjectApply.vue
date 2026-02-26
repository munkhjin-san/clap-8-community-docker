<template>
    <Modal @close="emit('close', false)" size="large">
        <template #title>
            <p>新規プロジェクト登録フォーム</p>
        </template>
        <template #content>
            <div class="project-apply-form">
                <div v-if="validationErrors.length" class="apply-error-box">
                    <p class="font-semibold">入力内容を確認してください。</p>
                    <ul class="apply-error-list">
                        <li v-for="error in validationErrors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <section class="si-box">
                    <p class="apply-section-title">基本情報</p>

                    <div class="apply-question">
                        <p class="apply-question-title">1. プロジェクト名を入力してください <span class="required-mark">※必須</span></p>
                        <input v-model="form.projectName" class="custom-a-input !w-full" type="text" placeholder="プロジェクト名" />
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">2. プロジェクト情報の入力は完了していますか？ <span class="required-mark">※必須</span></p>
                        <div class="apply-option-list">
                            <label class="apply-option">
                                <input v-model="form.projectInfoCompleted" class="custom-f-radio" name="project_info_completed" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.projectInfoCompleted" class="custom-f-radio" name="project_info_completed" type="radio" value="no">
                                いいえ
                            </label>
                        </div>
                        <p class="apply-note">※必須入力項目：概要、業務マニュアル</p>
                    </div>
                </section>

                <section class="si-box">
                    <p class="apply-section-title">案件タイプ</p>

                    <div class="apply-question">
                        <p class="apply-question-title">3. 当該案件の種類を選択してください <span class="required-mark">※必須</span></p>
                        <div class="apply-option-list">
                            <label class="apply-option">
                                <input v-model="form.caseType.continuingExpected" class="custom-f-checkbox" type="checkbox">
                                継続見込み案件
                            </label>
                            <label class="apply-option">
                                <input v-model="form.caseType.spotLimited" class="custom-f-checkbox" type="checkbox">
                                スポット（期間限定）案件
                            </label>
                        </div>
                        <div v-if="form.caseType.spotLimited" class="apply-branch">
                            <p>期間を入力してください <span class="required-mark">※必須</span></p>
                            <input v-model="form.spotPeriod" class="custom-a-input !w-full" type="text" placeholder="例：2026年4月1日〜2026年6月30日" />
                        </div>
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">4. 契約形態を選択してください <span class="required-mark">※必須</span></p>
                        <div class="apply-option-list">
                            <label class="apply-option">
                                <input v-model="form.contractType.fixed" class="custom-f-checkbox" type="checkbox">
                                固定案件
                            </label>
                            <label class="apply-option">
                                <input v-model="form.contractType.shot" class="custom-f-checkbox" type="checkbox">
                                ショット案件
                            </label>
                        </div>

                        <div v-if="form.contractType.shot" class="apply-branch">
                            <p class="apply-sub-title">ショット案件の追加確認（はい／いいえ）</p>
                            <div v-for="question in shotQuestions" :key="question.key" class="apply-sub-question">
                                <p>{{ question.label }}</p>
                                <div class="apply-option-inline">
                                    <label class="apply-option">
                                        <input v-model="form.shotChecklist[question.key]" class="custom-f-radio" :name="`shot_${question.key}`" type="radio" value="yes">
                                        はい
                                    </label>
                                    <label class="apply-option">
                                        <input v-model="form.shotChecklist[question.key]" class="custom-f-radio" :name="`shot_${question.key}`" type="radio" value="no">
                                        いいえ
                                    </label>
                                </div>
                            </div>
                            <textarea
                                v-model="form.shotNotes"
                                class="custom-a-input !w-full !min-h-[120px]"
                                placeholder="備考"
                            />
                        </div>
                    </div>
                </section>

                <section class="si-box">
                    <p class="apply-section-title">物品・設備関連</p>

                    <div class="apply-question">
                        <p class="apply-question-title">5. 備品購入は発生しますか？ <span class="required-mark">※必須</span></p>
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.equipmentPurchaseRequired" class="custom-f-radio" name="equipment_purchase" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.equipmentPurchaseRequired" class="custom-f-radio" name="equipment_purchase" type="radio" value="no">
                                いいえ
                            </label>
                        </div>

                        <div v-if="form.equipmentPurchaseRequired === 'yes'" class="apply-branch">
                            <div v-for="question in equipmentQuestions" :key="question.key" class="apply-sub-question">
                                <p>{{ question.label }}</p>
                                <div class="apply-option-inline">
                                    <label class="apply-option">
                                        <input v-model="form.equipmentChecklist[question.key]" class="custom-f-radio" :name="`equipment_${question.key}`" type="radio" value="yes">
                                        はい
                                    </label>
                                    <label class="apply-option">
                                        <input v-model="form.equipmentChecklist[question.key]" class="custom-f-radio" :name="`equipment_${question.key}`" type="radio" value="no">
                                        いいえ
                                    </label>
                                </div>
                            </div>
                            <textarea
                                v-model="form.equipmentNotes"
                                class="custom-a-input !w-full !min-h-[120px]"
                                placeholder="備考"
                            />
                        </div>
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">6. 商品・資材の仕入れ業務は発生しますか？（在庫保有可能性） <span class="required-mark">※必須</span></p>
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.procurementRequired" class="custom-f-radio" name="procurement_required" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.procurementRequired" class="custom-f-radio" name="procurement_required" type="radio" value="no">
                                いいえ
                            </label>
                        </div>
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">7. 請求方法は分割計上が必要ですか？ <span class="required-mark">※必須</span></p>
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.splitBillingRequired" class="custom-f-radio" name="split_billing_required" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.splitBillingRequired" class="custom-f-radio" name="split_billing_required" type="radio" value="no">
                                いいえ
                            </label>
                        </div>
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">8. 見積書・資料などを添付またはURL共有してください <span class="required-mark">※いずれか必須</span></p>
                        <div class="mt-[10px]">
                            <FileUploader
                                v-model="form.referenceFiles"
                                path="/project_files"
                                custom-place-holder="見積書・資料を添付"
                                custom-style="border: 1px solid var(--formBorder);"
                                custom-class="uploader-hover"
                            />
                        </div>
                        <div class="mt-[15px]">
                            <textarea
                                v-model="form.referenceText"
                                class="custom-a-input !w-full !min-h-[120px]"
                                placeholder="URLまたは補足情報"
                            />
                        </div>
                    </div>
                </section>

                <section class="si-box">
                    <p class="apply-section-title">アカウント・設備支給</p>

                    <div class="apply-question">
                        <p class="apply-question-title">9. PCや端末の支給は必要ですか？ <span class="required-mark">※必須</span></p>
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.deviceProvisionRequired" class="custom-f-radio" name="device_provision" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.deviceProvisionRequired" class="custom-f-radio" name="device_provision" type="radio" value="no">
                                いいえ
                            </label>
                        </div>
                        <div v-if="form.deviceProvisionRequired === 'yes'" class="apply-branch">
                            <input
                                v-model="form.deviceProvisionDetail"
                                class="custom-a-input !w-full"
                                type="text"
                                placeholder="期日・場所・台数など"
                            />
                        </div>
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">10. メールアカウント作成は必要ですか？ <span class="required-mark">※必須</span></p>
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.mailAccountRequired" class="custom-f-radio" name="mail_account_required" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.mailAccountRequired" class="custom-f-radio" name="mail_account_required" type="radio" value="no">
                                いいえ
                            </label>
                        </div>
                        <div v-if="form.mailAccountRequired === 'yes'" class="apply-branch grid grid-cols-1 md:grid-cols-2 gap-[10px]">
                            <input v-model="form.mailAccount.name" class="custom-a-input !w-full" type="text" placeholder="氏名（漢字）" />
                            <input v-model="form.mailAccount.kana" class="custom-a-input !w-full" type="text" placeholder="氏名（読み）" />
                        </div>
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">11. クラウドアカウント作成は必要ですか？ <span class="required-mark">※必須</span></p>
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.cloudAccountRequired" class="custom-f-radio" name="cloud_account_required" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.cloudAccountRequired" class="custom-f-radio" name="cloud_account_required" type="radio" value="no">
                                いいえ
                            </label>
                        </div>
                        <div v-if="form.cloudAccountRequired === 'yes'" class="apply-branch grid grid-cols-1 md:grid-cols-3 gap-[10px]">
                            <input v-model="form.cloudAccount.name" class="custom-a-input !w-full" type="text" placeholder="氏名（漢字）" />
                            <input v-model="form.cloudAccount.kana" class="custom-a-input !w-full" type="text" placeholder="氏名（読み）" />
                            <input v-model="form.cloudAccount.email" class="custom-a-input !w-full" type="email" placeholder="メールアドレス" />
                        </div>
                    </div>

                    <div class="apply-question">
                        <p class="apply-question-title">12. 社用車は利用しますか？ <span class="required-mark">※必須</span></p>
                        <div class="apply-option-inline">
                            <label class="apply-option">
                                <input v-model="form.companyCarRequired" class="custom-f-radio" name="company_car_required" type="radio" value="yes">
                                はい
                            </label>
                            <label class="apply-option">
                                <input v-model="form.companyCarRequired" class="custom-f-radio" name="company_car_required" type="radio" value="no">
                                いいえ
                            </label>
                        </div>
                        <div v-if="form.companyCarRequired === 'yes'" class="apply-branch">
                            <input v-model="form.companyCarUserName" class="custom-a-input !w-full" type="text" placeholder="使用予定者名" />
                        </div>
                    </div>
                </section>

                <section class="si-box">
                    <p class="apply-section-title">その他</p>
                    <div class="apply-question">
                        <p class="apply-question-title">13. 備考</p>
                        <textarea v-model="form.otherNotes" class="custom-a-input !w-full !min-h-[120px]" placeholder="備考（任意）" />
                    </div>
                </section>

                <div class="si-box flex justify-center">
                    <LoaderButton @triggered="submitForm" :loading="saving" content="申請内容を作成する" />
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import FileUploader from '@/components/Form/FileUploader.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { useDialog } from '@/composables/dialog';
import { CommonFile } from '@/interface/globalInterface';
import { reactive, ref, watch } from 'vue';

type YesNo = '' | 'yes' | 'no'
type ShotChecklistKey = 'quantityCondition' | 'fixedDesk' | 'surplusWork' | 'workingDays' | 'breakEvenCount' | 'shrinkRule'
type EquipmentChecklistKey = 'costSplitClear' | 'manageRule'

type ChecklistQuestion<T extends string> = {
    key: T
    label: string
}

interface ProjectApplyPayload {
    project_name: string
    project_info_completed: boolean
    required_project_inputs: string[]
    case_type: {
        continuing_expected: boolean
        spot_limited: boolean
        spot_period: string | null
    }
    contract_type: {
        fixed: boolean
        shot: boolean
        checklist: Record<ShotChecklistKey, boolean> | null
        notes: string | null
    }
    supplies_and_billing: {
        equipment_purchase_required: boolean
        equipment_checklist: Record<EquipmentChecklistKey, boolean> | null
        equipment_notes: string | null
        procurement_required: boolean
        split_billing_required: boolean
        reference_text: string | null
        reference_file_ids: number[]
    }
    accounts_and_devices: {
        device_provision_required: boolean
        device_detail: string | null
        mail_account_required: boolean
        mail_account: { name: string; kana: string } | null
        cloud_account_required: boolean
        cloud_account: { name: string; kana: string; email: string } | null
        company_car_required: boolean
        company_car_user_name: string | null
    }
    other_notes: string | null
    internal_spec: {
        uncontracted_flag: true
        clear_uncontracted_when_contracted: true
        viewable_roles: string[]
    }
}

const emit = defineEmits<{
    (e: 'close', flag: boolean): void
    (e: 'submit', payload: ProjectApplyPayload): void
}>()

const { ping } = useDialog()
const saving = ref(false)
const validationErrors = ref<string[]>([])

const shotQuestions: ChecklistQuestion<ShotChecklistKey>[] = [
    { key: 'quantityCondition', label: 'クライアントからの数量条件・最低保証はありますか' },
    { key: 'fixedDesk', label: '固定席デスクは必要ですか' },
    { key: 'surplusWork', label: '稼働が余った場合の別業務は整理されていますか' },
    { key: 'workingDays', label: '想定稼働日数は明確ですか' },
    { key: 'breakEvenCount', label: '最低採算件数を把握していますか' },
    { key: 'shrinkRule', label: '採算割れ時の縮小・撤退ルールはありますか' },
]

const equipmentQuestions: ChecklistQuestion<EquipmentChecklistKey>[] = [
    { key: 'costSplitClear', label: 'クライアント負担／自社負担の切り分けは明確ですか' },
    { key: 'manageRule', label: '管理・返却・廃棄ルールはありますか' },
]

const form = reactive({
    projectName: '',
    projectInfoCompleted: '' as YesNo,

    caseType: {
        continuingExpected: false,
        spotLimited: false,
    },
    spotPeriod: '',

    contractType: {
        fixed: false,
        shot: false,
    },
    shotChecklist: {
        quantityCondition: '' as YesNo,
        fixedDesk: '' as YesNo,
        surplusWork: '' as YesNo,
        workingDays: '' as YesNo,
        breakEvenCount: '' as YesNo,
        shrinkRule: '' as YesNo,
    },
    shotNotes: '',

    equipmentPurchaseRequired: '' as YesNo,
    equipmentChecklist: {
        costSplitClear: '' as YesNo,
        manageRule: '' as YesNo,
    },
    equipmentNotes: '',
    procurementRequired: '' as YesNo,
    splitBillingRequired: '' as YesNo,
    referenceFiles: [] as CommonFile[],
    referenceText: '',

    deviceProvisionRequired: '' as YesNo,
    deviceProvisionDetail: '',
    mailAccountRequired: '' as YesNo,
    mailAccount: {
        name: '',
        kana: '',
    },
    cloudAccountRequired: '' as YesNo,
    cloudAccount: {
        name: '',
        kana: '',
        email: '',
    },
    companyCarRequired: '' as YesNo,
    companyCarUserName: '',

    otherNotes: '',
})

const resetYesNoMap = <T extends string>(target: Record<T, YesNo>) => {
    ;(Object.keys(target) as T[]).forEach((key) => {
        target[key] = ''
    })
}

watch(() => form.caseType.spotLimited, (value) => {
    if (!value) {
        form.spotPeriod = ''
    }
})

watch(() => form.contractType.shot, (value) => {
    if (!value) {
        resetYesNoMap(form.shotChecklist)
        form.shotNotes = ''
    }
})

watch(() => form.equipmentPurchaseRequired, (value) => {
    if (value !== 'yes') {
        resetYesNoMap(form.equipmentChecklist)
        form.equipmentNotes = ''
    }
})

watch(() => form.deviceProvisionRequired, (value) => {
    if (value !== 'yes') {
        form.deviceProvisionDetail = ''
    }
})

watch(() => form.mailAccountRequired, (value) => {
    if (value !== 'yes') {
        form.mailAccount.name = ''
        form.mailAccount.kana = ''
    }
})

watch(() => form.cloudAccountRequired, (value) => {
    if (value !== 'yes') {
        form.cloudAccount.name = ''
        form.cloudAccount.kana = ''
        form.cloudAccount.email = ''
    }
})

watch(() => form.companyCarRequired, (value) => {
    if (value !== 'yes') {
        form.companyCarUserName = ''
    }
})

const isValidEmail = (value: string): boolean => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
const asBool = (value: YesNo): boolean => value === 'yes'

const validateForm = (): string[] => {
    const errors: string[] = []

    if (!form.projectName.trim()) {
        errors.push('1. プロジェクト名を入力してください。')
    }
    if (!form.projectInfoCompleted) {
        errors.push('2. プロジェクト情報の入力完了状況を選択してください。')
    } else if (form.projectInfoCompleted === 'no') {
        errors.push('2. 概要・業務マニュアルの入力完了後に申請してください。')
    }

    if (!form.caseType.continuingExpected && !form.caseType.spotLimited) {
        errors.push('3. 案件の種類を1つ以上選択してください。')
    }
    if (form.caseType.spotLimited && !form.spotPeriod.trim()) {
        errors.push('3. スポット案件の期間を入力してください。')
    }

    if (!form.contractType.fixed && !form.contractType.shot) {
        errors.push('4. 契約形態を1つ以上選択してください。')
    }
    if (form.contractType.shot) {
        shotQuestions.forEach((question) => {
            if (!form.shotChecklist[question.key]) {
                errors.push(`4. ショット案件確認：「${question.label}」に回答してください。`)
            }
        })
    }

    if (!form.equipmentPurchaseRequired) {
        errors.push('5. 備品購入の有無を選択してください。')
    }
    if (form.equipmentPurchaseRequired === 'yes') {
        equipmentQuestions.forEach((question) => {
            if (!form.equipmentChecklist[question.key]) {
                errors.push(`5. 備品購入確認：「${question.label}」に回答してください。`)
            }
        })
    }

    if (!form.procurementRequired) {
        errors.push('6. 商品・資材の仕入れ業務の有無を選択してください。')
    }
    if (!form.splitBillingRequired) {
        errors.push('7. 分割計上の要否を選択してください。')
    }
    if (!form.referenceText.trim() && !form.referenceFiles.length) {
        errors.push('8. 見積書・資料の添付またはURL共有を入力してください。')
    }

    if (!form.deviceProvisionRequired) {
        errors.push('9. PCや端末支給の要否を選択してください。')
    }
    if (form.deviceProvisionRequired === 'yes' && !form.deviceProvisionDetail.trim()) {
        errors.push('9. PCや端末支給の詳細を入力してください。')
    }

    if (!form.mailAccountRequired) {
        errors.push('10. メールアカウント作成の要否を選択してください。')
    }
    if (form.mailAccountRequired === 'yes' && (!form.mailAccount.name.trim() || !form.mailAccount.kana.trim())) {
        errors.push('10. メールアカウント作成対象の氏名（漢字・読み）を入力してください。')
    }

    if (!form.cloudAccountRequired) {
        errors.push('11. クラウドアカウント作成の要否を選択してください。')
    }
    if (form.cloudAccountRequired === 'yes') {
        if (!form.cloudAccount.name.trim() || !form.cloudAccount.kana.trim() || !form.cloudAccount.email.trim()) {
            errors.push('11. クラウドアカウント作成対象の氏名・読み・メールアドレスを入力してください。')
        } else if (!isValidEmail(form.cloudAccount.email.trim())) {
            errors.push('11. クラウドアカウント作成対象のメールアドレス形式が正しくありません。')
        }
    }

    if (!form.companyCarRequired) {
        errors.push('12. 社用車利用の有無を選択してください。')
    }
    if (form.companyCarRequired === 'yes' && !form.companyCarUserName.trim()) {
        errors.push('12. 社用車の使用予定者名を入力してください。')
    }

    return errors
}

const buildPayload = (): ProjectApplyPayload => {
    const shotChecklist = {
        quantityCondition: asBool(form.shotChecklist.quantityCondition),
        fixedDesk: asBool(form.shotChecklist.fixedDesk),
        surplusWork: asBool(form.shotChecklist.surplusWork),
        workingDays: asBool(form.shotChecklist.workingDays),
        breakEvenCount: asBool(form.shotChecklist.breakEvenCount),
        shrinkRule: asBool(form.shotChecklist.shrinkRule),
    }
    const equipmentChecklist = {
        costSplitClear: asBool(form.equipmentChecklist.costSplitClear),
        manageRule: asBool(form.equipmentChecklist.manageRule),
    }

    return {
        project_name: form.projectName.trim(),
        project_info_completed: asBool(form.projectInfoCompleted),
        required_project_inputs: ['概要', '業務マニュアル'],
        case_type: {
            continuing_expected: form.caseType.continuingExpected,
            spot_limited: form.caseType.spotLimited,
            spot_period: form.caseType.spotLimited ? form.spotPeriod.trim() : null,
        },
        contract_type: {
            fixed: form.contractType.fixed,
            shot: form.contractType.shot,
            checklist: form.contractType.shot ? shotChecklist : null,
            notes: form.contractType.shot ? (form.shotNotes.trim() || null) : null,
        },
        supplies_and_billing: {
            equipment_purchase_required: asBool(form.equipmentPurchaseRequired),
            equipment_checklist: form.equipmentPurchaseRequired === 'yes' ? equipmentChecklist : null,
            equipment_notes: form.equipmentPurchaseRequired === 'yes' ? (form.equipmentNotes.trim() || null) : null,
            procurement_required: asBool(form.procurementRequired),
            split_billing_required: asBool(form.splitBillingRequired),
            reference_text: form.referenceText.trim() || null,
            reference_file_ids: form.referenceFiles.map((file) => file.id),
        },
        accounts_and_devices: {
            device_provision_required: asBool(form.deviceProvisionRequired),
            device_detail: form.deviceProvisionRequired === 'yes' ? form.deviceProvisionDetail.trim() : null,
            mail_account_required: asBool(form.mailAccountRequired),
            mail_account: form.mailAccountRequired === 'yes'
                ? {
                    name: form.mailAccount.name.trim(),
                    kana: form.mailAccount.kana.trim(),
                }
                : null,
            cloud_account_required: asBool(form.cloudAccountRequired),
            cloud_account: form.cloudAccountRequired === 'yes'
                ? {
                    name: form.cloudAccount.name.trim(),
                    kana: form.cloudAccount.kana.trim(),
                    email: form.cloudAccount.email.trim(),
                }
                : null,
            company_car_required: asBool(form.companyCarRequired),
            company_car_user_name: form.companyCarRequired === 'yes' ? form.companyCarUserName.trim() : null,
        },
        other_notes: form.otherNotes.trim() || null,
        internal_spec: {
            uncontracted_flag: true,
            clear_uncontracted_when_contracted: true,
            viewable_roles: ['管理部', '役員'],
        },
    }
}

const submitForm = () => {
    const errors = validateForm()
    validationErrors.value = errors
    if (errors.length) {
        ping('必須項目を確認してください。')
        return
    }

    saving.value = true
    const payload = buildPayload()
    emit('submit', payload)
    saving.value = false
    emit('close', true)
}
</script>

<style scoped>
.uploader-hover:hover {
    border: 1px solid var(--primary-color) !important;
    cursor: pointer;
}
input, textarea {
    box-sizing: border-box !important;
}
.project-apply-form {
    display: flex;
    flex-direction: column;
    gap: 30px;
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
    font-size: 14px;
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
