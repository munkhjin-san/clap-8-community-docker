import type { FlowSubjectType, FlowOptionUser, FlowOptionPosition } from '@/types/flow'

const LABELS: Record<string, string> = {
    creator: '作成者',
    everyone: '全員',
    project_member: 'プロジェクトメンバー',
    project_manager: 'プロジェクトマネージャー',
    project_director: 'ディレクター',
}

export function subjectLabelFor(
    type: FlowSubjectType | string,
    id: number | null | undefined,
    users: FlowOptionUser[],
    positions: FlowOptionPosition[],
    fieldLabel?: string,
): string {
    if (LABELS[type]) return LABELS[type]
    if (type === 'user') return users.find((u) => u.id === id)?.name ?? `ユーザー#${id}`
    if (type === 'position') return positions.find((p) => p.id === id)?.name ?? `役職#${id}`
    if (type === 'field') return fieldLabel ? `${fieldLabel}の担当` : `フィールド参照#${id}`
    return String(type)
}
