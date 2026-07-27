export type FlowInputType =
    | 'short' | 'long' | 'number' | 'date' | 'time' | 'datetime'
    | 'select' | 'radio' | 'checkbox' | 'toggle'
    | 'user' | 'member' | 'formula' | 'file' | 'table' | 'reference' | 'project'
    | 'password'
    | 'heading' | 'label' | 'spacer' | 'divider'

export type FlowRule = 'edit' | 'read' | 'hide'

export interface FlowFieldValidation {
    min_length?: number | null
    max_length?: number | null
    format?: 'none' | 'email' | 'tel' | 'url'
    min?: number | null
    max?: number | null
    integer_only?: boolean
    min_select?: number | null
    max_select?: number | null
    accept?: string[]
    max_size_mb?: number | null
    allow_multiple?: boolean
    /** user/member: allow selecting multiple users (default true). */
    multiple?: boolean
    min_date?: string | null
    max_date?: string | null
    min_time?: string | null
    max_time?: string | null
    height?: number | null
    line_style?: 'solid' | 'dashed' | 'dotted'
    /** Shown in the form but not user-editable (lookup auto-fill still populates it). Mutually
     *  exclusive with is_required — a required field nobody can fill would block submission. */
    disabled?: boolean
    /** Default value applied on record create. */
    default?: any
    /** date/datetime/time: seed with the current timestamp on create. */
    default_now?: boolean
    /** user/member: seed with the creating user on create. */
    default_me?: boolean
    /** table: nested column definitions (each column is a mini field-def). */
    columns?: TableColumn[]
    /** reference: the target app to link records from. */
    target_definition_id?: number | null
    /** reference: a built-in system source (e.g. 'office') to link from instead of an app. Mutually
     *  exclusive with target_definition_id — set one or the other. See App\Support\FlowSystemSources. */
    target_source?: string | null
    /** reference: which field key of the target app to show as the label (falls back to record number). */
    label_field?: string | null
    /**
     * reference (lookup): kintone-style field copy. When a record is picked, each mapping copies the
     * source record's `from` field (a field key in the target app) into this app's `to` field (a field
     * key here). Snapshot at pick time (later source edits are ignored); cleared when the lookup is
     * cleared; the copied fields stay editable. Because destinations are real fields, the copied
     * values are searchable / exportable / columnable / filterable. Top-level reference fields only.
     */
    field_mappings?: FlowLookupMapping[]
}

export interface FlowLookupMapping {
    /** field key in the target (source) app */
    from: string
    /** field key in this app to populate */
    to: string
}

/** A column inside a `table` field — same shape as a field, nested in the parent's validation. */
export interface TableColumn {
    key: string
    label: string
    input_type: FlowInputType
    options?: string[] | null
    validation?: FlowFieldValidation | null
    required?: boolean
    width?: number
    /** formula column: the expression; variables are sibling column keys (+ top-level field keys). */
    formula?: string | null
    result_type?: 'number' | 'text' | 'toggle' | null
    /** reference column: target app + label field (mirrors the top-level reference field config). */
    target_definition_id?: number | null
    label_field?: string | null
}

export interface FlowField {
    id?: number
    key: string
    label: string
    input_type: FlowInputType
    options?: string[] | null
    is_required?: boolean
    hidden?: boolean
    order_number?: number
    layout_row?: number
    width: number
    depends_on?: any[] | null
    validation?: FlowFieldValidation | null
    formula?: string | null
    result_type?: 'number' | 'text' | 'toggle' | null
    /** Transient builder-only stable id (selection/v-for key); not persisted. */
    uid?: string
}

export const FLOW_FIELD_MIN_WIDTH = 140
export const FLOW_FIELD_DEFAULT_WIDTH = 260

// Per-type initial width — text needs room, pickers/toggles/dates stay compact.
export const FLOW_FIELD_DEFAULT_WIDTHS: Partial<Record<FlowInputType, number>> = {
    short: 340,
    long: 560,
    number: 180,
    date: 190,
    time: 160,
    datetime: 230,
    select: 260,
    radio: 260,
    checkbox: 320,
    toggle: 160,
    user: 300,
    member: 300,
    formula: 240,
    reference: 320,
    project: 280,
    file: 380,
    table: 640,
    heading: 640,
    label: 520,
    divider: 640,
    spacer: 200,
}
export const defaultWidthFor = (type: FlowInputType): number => FLOW_FIELD_DEFAULT_WIDTHS[type] ?? FLOW_FIELD_DEFAULT_WIDTH

export const FLOW_FILE_ACCEPT: { value: string; label: string }[] = [
    { value: 'image', label: '画像' },
    { value: 'document', label: '文書' },
    { value: 'video', label: '動画' },
    { value: 'audio', label: '音声' },
    { value: 'any', label: 'すべて' },
]

export interface FlowStatusFieldRule {
    id?: number
    flow_field_id: number
    rule: FlowRule
}

/** Who may press an action button. */
export interface ActionSubject {
    subject_type: 'creator' | 'user' | 'position' | 'creator_project_manager' | 'field_project_manager'
    subject_id?: number | null
}

/** API shape of a status (with persisted fieldRules relation). */
export interface FlowStatusApi {
    id?: number
    name: string
    order_number?: number
    is_initial?: boolean
    is_locked?: 'start' | 'end' | null
    color?: string | null
    ui_x?: number | null
    ui_y?: number | null
    field_rules?: FlowStatusFieldRule[]
}

/** API shape of a status action button (belongs to a "from" status). */
export interface FlowStatusActionApi {
    id?: number
    flow_status_id: number
    to_status_id: number | null
    name?: string | null
    label: string
    color?: string | null
    eligible?: ActionSubject[] | null
    sort_order?: number
}

/** Builder-side action button (from = the status it lives under; to = target key). */
export interface BuilderStatusAction {
    id?: number
    name: string
    label: string
    color: string
    to_status_key: string | null
    eligible: ActionSubject[]
}

/** Builder-side status: rules held as a fieldKey -> rule map; actions held inline. */
export interface BuilderStatus {
    id?: number
    key: string
    name: string
    is_initial: boolean
    color?: string | null
    ui_x?: number | null
    ui_y?: number | null
    rules: Record<string, FlowRule>
    actions: BuilderStatusAction[]
}

export type FlowSubjectType =
    | 'creator' | 'everyone' | 'user' | 'position'
    | 'project_member' | 'project_manager' | 'project_director'

export interface AppPermissionRow {
    subject_type: FlowSubjectType
    subject_id?: number | null
    can_view: boolean
    can_add: boolean
    can_edit: boolean
    can_delete: boolean
    can_manage: boolean
    can_import: boolean
    can_export: boolean
    can_bulk: boolean
    sort_order?: number
}

export interface RecordPermConditionRow {
    source: 'creator' | 'updater' | 'status' | 'field'
    field_id?: number | null
    operator: 'includes_any' | 'includes_all' | 'equals' | 'is_empty' | 'not_empty'
    values: any[]
}

export interface RecordPermGrantRow {
    subject_type: FlowSubjectType | 'field'
    subject_id?: number | null
    can_view: boolean
    can_edit: boolean
    can_delete: boolean
}

export interface RecordPermSetRow {
    match_mode: 'all' | 'any'
    conditions: RecordPermConditionRow[]
    grants: RecordPermGrantRow[]
}

export interface FieldPermRow {
    field_id: number
    subject_type: FlowSubjectType
    subject_id?: number | null
    can_view: boolean
    can_edit: boolean
}

/* ---- Views (kintone-style, builder-defined) ---- */

// System (non-field) columns, addressable in a view's column list via sentinel keys.
export const FLOW_SYS_RECORD_NUMBER = '$record_number'
export const FLOW_SYS_STATUS = '$status'
export const FLOW_SYS_CREATED_AT = '$created_at'
export const FLOW_SYS_UPDATED_AT = '$updated_at'

const FLOW_SYS_STATUS_COLUMN = { key: FLOW_SYS_STATUS, label: 'ステータス' }

// Always-available system columns (independent of status flow).
export const FLOW_SYSTEM_COLUMNS: { key: string; label: string }[] = [
    { key: FLOW_SYS_RECORD_NUMBER, label: 'ID' },
    { key: FLOW_SYS_CREATED_AT, label: '作成日時' },
    { key: FLOW_SYS_UPDATED_AT, label: '更新日時' },
]

/** System columns for this app — the status column is only offered when the app uses the status flow. */
export const flowSystemColumns = (hasStatus: boolean): { key: string; label: string }[] =>
    hasStatus
        ? [FLOW_SYSTEM_COLUMNS[0], FLOW_SYS_STATUS_COLUMN, FLOW_SYSTEM_COLUMNS[1], FLOW_SYSTEM_COLUMNS[2]]
        : FLOW_SYSTEM_COLUMNS

/** Label for any system column key (status included), or undefined if not a known system column. */
export const flowSystemColumnLabel = (key: string): string | undefined =>
    key === FLOW_SYS_STATUS ? FLOW_SYS_STATUS_COLUMN.label : FLOW_SYSTEM_COLUMNS.find((c) => c.key === key)?.label

export const isSystemColumn = (c: unknown): c is string => typeof c === 'string' && c.startsWith('$')

export type FlowViewOperator =
    | 'equals' | 'not_equals' | 'contains' | 'not_contains'
    | 'includes_any' | 'is_empty' | 'not_empty'
    | 'gt' | 'gte' | 'lt' | 'lte'

export const FLOW_VIEW_OPERATOR_LABEL: Record<FlowViewOperator, string> = {
    equals: '等しい', not_equals: '等しくない',
    contains: '含む', not_contains: '含まない',
    includes_any: 'いずれかを含む',
    is_empty: '空', not_empty: '空でない',
    gt: 'より大きい', gte: '以上', lt: 'より小さい', lte: '以下',
}

export interface FlowViewFilter {
    field: number | string   // field id, or system sentinel
    operator: FlowViewOperator
    values: any[]
}
export interface FlowViewSort {
    field: number | string
    direction: 'asc' | 'desc'
}
/** Ad-hoc (session-only) record-list filter opened via the search bar's filter icon. Unlike a
 *  saved view's filters (always AND), the user picks a single AND/OR across all conditions. */
export interface FlowAdhocFilter {
    logic: 'and' | 'or'
    conditions: FlowViewFilter[]
}
export interface FlowViewApi {
    id?: number
    name: string
    is_default?: boolean
    view_mode?: string
    columns?: (number | string)[] | null
    filters?: FlowViewFilter[] | null
    sort?: FlowViewSort[] | null
}
export interface BuilderView {
    id?: number
    name: string
    is_default: boolean
    columns: (number | string)[]
    filters: FlowViewFilter[]
    sort: FlowViewSort[]
}

export interface FlowShare {
    id?: number
    user_id?: number | null
    position_id?: number | null
    access_level: 'use' | 'view'
}

/* ---------------- Tools (ツール): app add-ons like PDF generation ---------------- */

export type PdfElementType = 'text' | 'field' | 'today' | 'image' | 'box' | 'line' | 'table'

export interface PdfElementStyle {
    fontSize?: number
    bold?: boolean
    italic?: boolean
    underline?: boolean
    align?: 'left' | 'center' | 'right'
    color?: string
    lineHeight?: number
    borderWidth?: number
    borderColor?: string
    fill?: string
    radius?: number
}

export interface PdfValueFormat {
    kind?: 'text' | 'number' | 'date'
    decimals?: number
    pattern?: string
}

export interface PdfTableColumn {
    colKey: string        // source column key inside the bound table field
    label: string
    width?: number        // % of table width
    align?: 'left' | 'center' | 'right'
    format?: PdfValueFormat
}

/** One element on the A4 design canvas (px @96dpi, 794 x 1123). */
export interface PdfElement {
    id: string
    type: PdfElementType
    x: number
    y: number
    w: number
    h: number
    style?: PdfElementStyle
    // text
    text?: string
    // field binding
    fieldKey?: string
    format?: PdfValueFormat
    prefix?: string
    suffix?: string
    fallback?: string
    // image
    src?: string          // data-uri
    fit?: 'contain' | 'cover'
    // table (明細)
    sourceFieldKey?: string
    columns?: PdfTableColumn[]
    amountColKey?: string
    showSubtotal?: boolean
    showTax?: boolean
    showTotal?: boolean
    tax?: { rate?: number }
    currency?: string
    fontSize?: number
    borderColor?: string
    showHeader?: boolean   // 見出し行の表示（既定 true）
    showBorder?: boolean   // 罫線の表示（既定 true）
}

export interface PdfTemplate {
    paper: { orientation: 'portrait' | 'landscape' }
    elements: PdfElement[]
    filename?: string
}

export interface FlowAppTool {
    id?: number
    tool_type: 'pdf'
    name: string
    is_active: boolean
    config: PdfTemplate
}

export const TOOL_META: Record<string, { label: string; icon: string }> = {
    pdf: { label: 'PDF帳票', icon: 'file' },
}

export const emptyPdfTemplate = (): PdfTemplate => ({
    paper: { orientation: 'portrait' },
    elements: [],
})

export interface FlowDefinitionApi {
    id?: number
    name: string
    description?: string | null
    color_id?: number | null
    icon_svg?: string | null
    icon_image?: string | null
    visibility?: 'limited' | 'all_staff'
    is_active?: boolean
    use_status_flow?: boolean
    fields: FlowField[]
    statuses: FlowStatusApi[]
    status_actions?: FlowStatusActionApi[]
    shares?: FlowShare[]
    app_permissions?: AppPermissionRow[]
    record_permission_sets?: any[]
    field_permissions?: any[]
    views?: FlowViewApi[]
    tools?: FlowAppTool[]
    project_record_id?: number | null
}

export interface BuilderDefinition {
    id?: number
    name: string
    description?: string | null
    color_id?: number | null
    icon_svg?: string | null
    icon_image?: string | null
    is_active: boolean
    use_status_flow: boolean
    fields: FlowField[]
    statuses: BuilderStatus[]
    appPermissions: AppPermissionRow[]
    recordPermissions: RecordPermSetRow[]
    fieldPermissions: FieldPermRow[]
    views: BuilderView[]
    tools: FlowAppTool[]
    project_record_id?: number | null
}

export interface FlowOptionUser {
    id: number
    name: string
    position_id?: number | null
    icon_path?: string | null
    icon_bg?: string | null
}

export interface FlowOptionPosition {
    id: number
    name: string
}

export type FlowAuditAction = 'record_view' | 'csv_export' | 'settings_change' | 'file_download'

/** App-level audit trail entry (「監査ログ」builder tab) — distinct from a record's own 変更履歴. */
export interface FlowAuditLogEntry {
    id: number
    user: FlowOptionUser | null
    action: FlowAuditAction
    record: { id: number; record_number: number } | null
    /** Shape depends on `action` — see FlowController::logAudit() call sites for each event's fields. */
    meta: Record<string, any> | null
    created_at: string
}

export interface FlowOptionProject {
    id: number
    name: string
}

export interface FlowDefinitionListItem {
    id: number
    name: string
    description?: string | null
    color_id?: number | null
    icon_svg?: string | null
    icon_image?: string | null
    is_public?: boolean
    is_active: boolean
    created_by?: number | null
    created_at?: string
    updated_at?: string
    pinned?: boolean
    /** current user has 管理 on this app → may open 設定 / 削除 (both manage-gated server-side) */
    can_manage?: boolean
    /** unread notification events for the current user (per-app bell badge) */
    unread_notifications?: number
    /** 対応待ち — live count of records whose current status names this user as worker */
    pending_actions?: number
    creator?: FlowOptionUser | null
    records_count?: number
}

export interface FlowAppPermissionsDto {
    view: boolean
    add: boolean
    edit: boolean
    delete: boolean
    manage: boolean
    import: boolean
    export: boolean
    bulk: boolean
}

export interface FlowRecordDto {
    id: number
    record_number?: number
    values: Record<string, any>
    created_by?: number | null
    creator?: FlowOptionUser | null
    current_status_id?: number | null
    current_status?: string | null
    source?: string | null
    source_id?: string | null
    created_at?: string
    updated_at?: string
    can_edit?: boolean
    can_delete?: boolean
    /** 要対応 — an action on the record's current status explicitly names the viewer */
    pending_action?: boolean
}

export interface FlowRecordsResponse {
    definition: FlowDefinitionApi
    permissions: FlowAppPermissionsDto
    records: FlowRecordDto[]
    views: any[]
}

export interface FlowTypeMeta {
    type: FlowInputType
    label: string
    icon: string
    group: '入力' | '選択' | '高度' | 'レイアウト' | 'その他'
    hasOptions?: boolean
    projectOnly?: boolean
}

export const FLOW_FIELD_TYPES: FlowTypeMeta[] = [
    { type: 'short', label: '短文', icon: 'short', group: '入力' },
    { type: 'long', label: '長文', icon: 'long', group: '入力' },
    { type: 'number', label: '数値', icon: 'number', group: '入力' },
    { type: 'date', label: '日付', icon: 'date', group: '入力' },
    { type: 'time', label: '時刻', icon: 'time', group: '入力' },
    { type: 'datetime', label: '日時', icon: 'datetime', group: '入力' },
    { type: 'select', label: 'ドロップダウン', icon: 'select', group: '選択', hasOptions: true },
    { type: 'radio', label: 'ラジオ', icon: 'radio', group: '選択', hasOptions: true },
    { type: 'checkbox', label: 'チェックボックス', icon: 'checkbox', group: '選択', hasOptions: true },
    { type: 'toggle', label: 'オン/オフ', icon: 'toggle', group: '選択' },
    { type: 'user', label: 'ユーザー', icon: 'user', group: '高度' },
    { type: 'member', label: 'メンバー', icon: 'member', group: '高度', projectOnly: true },
    { type: 'formula', label: '計算', icon: 'formula', group: '高度' },
    { type: 'reference', label: 'ルックアップ', icon: 'reference', group: '高度' },
    { type: 'project', label: 'プロジェクト', icon: 'project', group: '高度' },
    { type: 'password', label: 'パスワード（暗号化）', icon: 'password', group: '高度' },
    { type: 'file', label: 'ファイル', icon: 'file', group: 'その他' },
    { type: 'table', label: 'テーブル', icon: 'table', group: 'その他' },
    { type: 'heading', label: '見出し', icon: 'heading', group: 'レイアウト' },
    { type: 'label', label: 'ラベル', icon: 'label', group: 'レイアウト' },
    { type: 'spacer', label: 'スペース', icon: 'spacer', group: 'レイアウト' },
    { type: 'divider', label: '罫線', icon: 'divider', group: 'レイアウト' },
]

/** Layout/decoration types that hold no record value. */
export const FLOW_LAYOUT_TYPES: FlowInputType[] = ['heading', 'label', 'spacer', 'divider']
export const isLayoutType = (t: FlowInputType) => FLOW_LAYOUT_TYPES.includes(t)

/**
 * Encrypted-at-rest field types (server-side AccountVault). Their record value is only ever a
 * boolean "is one stored?" — the plaintext comes from the audited reveal endpoint alone. Keep them
 * out of anything that moves values around: CSV export, search, view columns/filters/sort,
 * formulas, lookup copy, PDF tools, record duplicate, change-history values.
 */
export const FLOW_SECRET_TYPES: FlowInputType[] = ['password']
export const isSecretType = (t: FlowInputType) => FLOW_SECRET_TYPES.includes(t)

export const FLOW_TYPE_LABEL: Record<string, string> = Object.fromEntries(
    FLOW_FIELD_TYPES.map((t) => [t.type, t.label])
)
