import type { CommonFile } from '@/interface/globalInterface'

export type ShotChecklistKey =
  | 'quantityCondition'
  | 'fixedDesk'
  | 'surplusWork'
  | 'workingDays'
  | 'breakEvenCount'
  | 'shrinkRule'

export type EquipmentChecklistKey = 'costSplitClear' | 'manageRule'
export type MembersChecklistKey = 'administrator' | 'salesStaff' | 'adminStaff'
export type NecessaryItemsChecklistKey =
  | 'pcLoan'
  | 'smartphoneLoan'
  | 'emailAccount'
  | 'glowdAccount'
  | 'companyCar'
  | 'parkingLot'

type MembersCountKey = `${MembersChecklistKey}_count`

export type ChecklistQuestion<T extends string> = {
  key: T
  label: string
  hasCountInput?: boolean
}

export interface ProjectApplyPayload {
  case_type: {
    continuing_expected: boolean
    spot_limited: boolean
    spot_period: {
      start: string | null
      end: string | null
    }
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
    inventory_required: boolean
    split_billing_required: boolean
    reference_text: string | null
    reference_file_ids: number[]
  }
  members_checklist: {
    administrator: boolean
    administrator_count: number | null
    salesStaff: boolean
    salesStaff_count: number | null
    adminStaff: boolean
    adminStaff_count: number | null
  }
  necessary_items_checklist: {
    pcLoan: boolean
    smartphoneLoan: boolean
    emailAccount: boolean
    glowdAccount: boolean
    companyCar: boolean
    parkingLot: boolean
  }
  necessary_items_detail: string | null
  other_notes: string | null
}

export type MembersChecklistState = Record<MembersChecklistKey, boolean> &
  Record<MembersCountKey, number | null>

export interface ConfirmApplyFormState {
  caseType: {
    continuingExpected: boolean
    spotLimited: boolean
  }
  spot_period: {
    start: string
    end: string
  }
  contractType: {
    fixed: boolean
    shot: boolean
  }
  shotChecklist: Record<ShotChecklistKey, boolean>
  shotNotes: string
  equipmentPurchaseRequired: boolean
  equipmentChecklist: Record<EquipmentChecklistKey, boolean>
  membersCheckList: MembersChecklistState
  equipmentNotes: string
  inventoryRequired: boolean
  splitBillingRequired: boolean
  referenceFiles: CommonFile[]
  referenceText: string
  necessaryItemsChecklist: Record<NecessaryItemsChecklistKey, boolean>
  necessaryItemsDetail: string
  otherNotes: string
}

export const shotQuestions: ChecklistQuestion<ShotChecklistKey>[] = [
  { key: 'quantityCondition', label: 'クライアントからの数量条件・最低保証がある' },
  { key: 'fixedDesk', label: 'デスク（固定席）が必要である' },
  { key: 'surplusWork', label: '固定メンバーの余剰稼働時に実施する業務が整理されている' },
  { key: 'workingDays', label: '想定稼働日数が明確である' },
  { key: 'breakEvenCount', label: '最低採算件数を把握している' },
  { key: 'shrinkRule', label: '最低採算件数を下回った場合の縮小・撤退ルールがある' },
]

export const equipmentQuestions: ChecklistQuestion<EquipmentChecklistKey>[] = [
  { key: 'costSplitClear', label: 'クライアント負担／自社負担の切り分けは明確ですか' },
  { key: 'manageRule', label: '管理・返却・廃棄ルールはありますか' },
]

export const membersQuestions: ChecklistQuestion<MembersChecklistKey>[] = [
  { key: 'administrator', label: '管理者', hasCountInput: true },
  { key: 'salesStaff', label: '営業担当', hasCountInput: true },
  { key: 'adminStaff', label: '事務担当', hasCountInput: true },
]

export const necessaryItemsQuestions: ChecklistQuestion<NecessaryItemsChecklistKey>[] = [
  { key: 'pcLoan', label: '新規PC貸与' },
  { key: 'smartphoneLoan', label: '新規業務端末貸与' },
  { key: 'emailAccount', label: 'メールアカウント' },
  { key: 'glowdAccount', label: 'グラウドアカウント' },
  { key: 'companyCar', label: '社用車' },
  { key: 'parkingLot', label: '新規駐車場契約' },
]

export function createChecklistState<T extends string>(
  questions: ChecklistQuestion<T>[]
): Record<T, boolean> {
  return questions.reduce((acc, q) => {
    acc[q.key] = false
    return acc
  }, {} as Record<T, boolean>)
}

export function createMembersChecklistState(
  questions: ChecklistQuestion<MembersChecklistKey>[]
): MembersChecklistState {
  return questions.reduce((acc, q) => {
    acc[q.key] = false
    acc[`${q.key}_count`] = null
    return acc
  }, {} as MembersChecklistState)
}

const toNullableNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') return null
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

const toBoolean = (value: unknown): boolean => {
  if (value === true || value === 1 || value === '1') return true
  if (value === false || value === 0 || value === '0' || value === null || value === undefined || value === '') return false
  if (typeof value === 'string') return value.toLowerCase() === 'true'
  return Boolean(value)
}

export const createDefaultConfirmApplyFormState = (): ConfirmApplyFormState => ({
  caseType: {
    continuingExpected: false,
    spotLimited: false,
  },
  spot_period: {
    start: '',
    end: '',
  },
  contractType: {
    fixed: false,
    shot: false,
  },
  shotChecklist: createChecklistState(shotQuestions),
  shotNotes: '',
  equipmentPurchaseRequired: false,
  equipmentChecklist: createChecklistState(equipmentQuestions),
  membersCheckList: createMembersChecklistState(membersQuestions),
  equipmentNotes: '',
  inventoryRequired: false,
  splitBillingRequired: false,
  referenceFiles: [],
  referenceText: '',
  necessaryItemsChecklist: createChecklistState(necessaryItemsQuestions),
  necessaryItemsDetail: '',
  otherNotes: '',
})

export const hasMembersInput = (members: MembersChecklistState): boolean =>
  Object.entries(members).some(([key, value]) => (key.endsWith('_count') ? value !== null : value === true))

export const hasNecessaryItemsInput = (
  checklist: Record<NecessaryItemsChecklistKey, boolean>,
  detail: string
): boolean => Object.values(checklist).some(Boolean) || detail.trim().length > 0

export const resetMembersChecklist = (members: MembersChecklistState): void => {
  for (const key of membersQuestions.map((q) => q.key)) {
    members[key] = false
    members[`${key}_count`] = null
  }
}

export const resetChecklist = <T extends string>(target: Record<T, boolean>): void => {
  for (const key of Object.keys(target) as T[]) {
    target[key] = false
  }
}

export const hydrateConfirmApplyForm = (
  form: ConfirmApplyFormState,
  payload?: ProjectApplyPayload
): number[] => {
  if (!payload) return []

  const caseType = payload.case_type ?? {
    continuing_expected: false,
    spot_limited: false,
    spot_period: { start: null, end: null },
  }
  const period = (caseType as any).spot_period ?? (caseType as any).sport_period ?? { start: null, end: null }
  form.caseType.continuingExpected = toBoolean(caseType.continuing_expected)
  form.caseType.spotLimited = toBoolean(caseType.spot_limited)
  form.spot_period.start = period?.start || ''
  form.spot_period.end = period?.end || ''

  const contractType = payload.contract_type ?? { fixed: false, shot: false, checklist: null, notes: null }
  const shotChecklist = contractType.checklist ?? {}
  form.contractType.fixed = toBoolean(contractType.fixed)
  form.contractType.shot = toBoolean(contractType.shot)
  for (const key of shotQuestions.map((q) => q.key)) {
    form.shotChecklist[key] = toBoolean((shotChecklist as any)[key])
  }
  form.shotNotes = contractType.notes || ''

  const supplies = payload.supplies_and_billing ?? {
    equipment_purchase_required: false,
    equipment_checklist: null,
    equipment_notes: null,
    inventory_required: false,
    split_billing_required: false,
    reference_text: null,
    reference_file_ids: [],
  }
  const equipmentChecklist = supplies.equipment_checklist ?? {}
  form.equipmentPurchaseRequired = toBoolean(supplies.equipment_purchase_required)
  for (const key of equipmentQuestions.map((q) => q.key)) {
    form.equipmentChecklist[key] = toBoolean((equipmentChecklist as any)[key])
  }
  form.equipmentNotes = supplies.equipment_notes || ''
  form.inventoryRequired = toBoolean((supplies as any).inventory_required)
  form.splitBillingRequired = toBoolean(supplies.split_billing_required)
  form.referenceText = supplies.reference_text || ''
  form.referenceFiles = []

  const members = payload.members_checklist ?? {
    administrator: false,
    administrator_count: null,
    salesStaff: false,
    salesStaff_count: null,
    adminStaff: false,
    adminStaff_count: null,
  }
  for (const key of membersQuestions.map((q) => q.key)) {
    form.membersCheckList[key] = toBoolean((members as any)[key])
    form.membersCheckList[`${key}_count`] = toNullableNumber((members as any)[`${key}_count`])
  }

  const necessary = payload.necessary_items_checklist ?? {
    pcLoan: false,
    smartphoneLoan: false,
    emailAccount: false,
    glowdAccount: false,
    companyCar: false,
    parkingLot: false,
  }
  for (const key of necessaryItemsQuestions.map((q) => q.key)) {
    form.necessaryItemsChecklist[key] = toBoolean((necessary as any)[key])
  }
  form.necessaryItemsDetail = payload.necessary_items_detail || ''

  form.otherNotes = payload.other_notes || ''

  return Array.isArray(supplies.reference_file_ids)
    ? supplies.reference_file_ids
        .map((id) => Number(id))
        .filter((id) => Number.isInteger(id) && id > 0)
    : []
}

export const buildConfirmApplyPayload = (
  form: ConfirmApplyFormState,
  persistedReferenceFileIds: number[]
): ProjectApplyPayload => ({
  case_type: {
    continuing_expected: form.caseType.continuingExpected,
    spot_limited: form.caseType.spotLimited,
    spot_period: {
      start: form.spot_period.start || null,
      end: form.spot_period.end || null,
    },
  },
  contract_type: {
    fixed: form.contractType.fixed,
    shot: form.contractType.shot,
    checklist: form.contractType.shot ? { ...form.shotChecklist } : null,
    notes: form.contractType.shot ? form.shotNotes.trim() || null : null,
  },
  supplies_and_billing: {
    equipment_purchase_required: form.equipmentPurchaseRequired,
    equipment_checklist: form.equipmentPurchaseRequired ? { ...form.equipmentChecklist } : null,
    equipment_notes: form.equipmentPurchaseRequired ? form.equipmentNotes.trim() || null : null,
    inventory_required: form.inventoryRequired,
    split_billing_required: form.splitBillingRequired,
    reference_text: form.referenceText.trim() || null,
    reference_file_ids: Array.from(
      new Set([...persistedReferenceFileIds, ...form.referenceFiles.map((file) => file.id)])
    ),
  },
  members_checklist: {
    administrator: form.membersCheckList.administrator,
    administrator_count: form.membersCheckList.administrator_count,
    salesStaff: form.membersCheckList.salesStaff,
    salesStaff_count: form.membersCheckList.salesStaff_count,
    adminStaff: form.membersCheckList.adminStaff,
    adminStaff_count: form.membersCheckList.adminStaff_count,
  },
  necessary_items_checklist: { ...form.necessaryItemsChecklist },
  necessary_items_detail: hasNecessaryItemsInput(form.necessaryItemsChecklist, form.necessaryItemsDetail)
    ? form.necessaryItemsDetail.trim() || null
    : null,
  other_notes: form.otherNotes.trim() || null,
})
