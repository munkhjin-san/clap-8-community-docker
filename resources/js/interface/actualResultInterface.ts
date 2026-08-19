export type ActualAccountCategory = 'sales' | 'expense';
export type ActualResultSourceMode = 'csv_finalized' | 'reserve_csv_uploaded' | 'auto_calculated';

export type ActualResultSortKey =
    | 'external_sales'
    | 'internal_sales'
    | 'cost_of_goods_sold'
    | 'sg_and_a_expenses'
    | 'indirect_allocation_expense'
    | 'normal_profit'
    | 'real_profit'
    | 'department';

export interface ActualAccount {
    account_key?: string;
    account_code: string;
    account_name: string;
    category: ActualAccountCategory;
    bucket: string;
    bucket_label: string;
    amount_source?: string;
    amount: number;
    debit: number;
    credit: number;
    balance: number;
    ending_balance: number;
    rows: number;
    source_departments: string[];
    /** 積立金の配分内訳（勤怠ベース）。自動計算された積立金にのみ入る。 */
    allocation_details?: {
        user_name: string;
        user_code: string;
        work_minutes: number;
        total_work_minutes: number;
        source_amount: number;
        amount: number;
    }[];
    /** 賞与引当金繰入額の内訳（基本賞与分＋部門ごとの業績連動分）。積立部門にのみ入る。 */
    accrual_breakdown?: {
        basic_bonus_total: number;
        basic_bonus_users: number;
        performance_bonus_total: number;
        performance_bonus_by_department: {
            department: string;
            normal_profit: number;
            rate: number;
            amount: number;
        }[];
    };
}

export interface ActualDepartment {
    id?: number;
    project_record_id?: number | null;
    department: string;
    source_departments: string[];
    manual_adjusted?: boolean;
    accounts_restricted?: boolean;
    restricted_account_count?: number;
    csv_sales: number;
    csv_expenses: number;
    csv_reserve_transfer_sales: number;
    csv_indirect_allocation_sales: number;
    csv_indirect_allocation_expense: number;
    csv_performance_bonus_reserve: number;
    external_sales: number;
    internal_sales: number;
    sales: number;
    expenses: number;
    operating_sales: number;
    reserve_transfer_sales: number;
    indirect_allocation_sales: number;
    other_sales: number;
    ordinary_expenses: number;
    reserve_expenses: number;
    basic_bonus_reserve: number;
    performance_bonus_reserve: number;
    paid_leave_reserve: number;
    welfare_reserve: number;
    refresh_reserve: number;
    indirect_allocation_expense: number;
    beginning_inventory: number;
    purchases: number;
    ending_inventory: number;
    cost_of_goods_sold: number;
    base_reserve_expenses: number;
    sg_and_a_expenses: number;
    normal_profit: number;
    csv_profit: number;
    profit_adjustment: number;
    adjusted_expenses: number;
    indirect_allocation: number;
    total_expenses: number;
    profit: number;
    real_profit: number;
    margin: number | null;
    real_margin: number | null;
    ending_sales: number;
    ending_expenses: number;
    ending_profit: number;
    row_count: number;
    accounts: ActualAccount[];
}

export interface ActualResultFile {
    name: string | null;
    title: string;
    period: string | null;
    encoding: string;
    source_rows: number;
    detail_rows: number;
    skipped_rows: number;
    calculation_month?: string | null;
    calculation_source_mode?: ActualResultSourceMode;
    calculation_sources?: Record<string, ActualResultSourceMode>;
    generated_reserve_rows?: number;
    generated_reserve_total?: number;
    generated_basic_bonus_accrual_total?: number;
    generated_bonus_accrual_expense?: number;
    generated_reserve_warnings?: string[];
    saved_upload_id?: number;
    stored_path?: string;
}

export interface ActualResultUpload {
    id: number;
    original_name: string;
    stored_path: string;
    file_hash: string;
    file_size: number;
    uploaded_by: number | null;
    created_at: string | null;
}

export interface ActualResultSummary {
    departments: number;
    csv_sales: number;
    csv_expenses: number;
    csv_reserve_transfer_sales: number;
    csv_indirect_allocation_sales: number;
    csv_indirect_allocation_expense: number;
    csv_performance_bonus_reserve: number;
    external_sales: number;
    internal_sales: number;
    sales: number;
    expenses: number;
    operating_sales: number;
    reserve_transfer_sales: number;
    indirect_allocation_sales: number;
    other_sales: number;
    ordinary_expenses: number;
    reserve_expenses: number;
    basic_bonus_reserve: number;
    performance_bonus_reserve: number;
    paid_leave_reserve: number;
    welfare_reserve: number;
    refresh_reserve: number;
    indirect_allocation_expense: number;
    beginning_inventory: number;
    purchases: number;
    ending_inventory: number;
    cost_of_goods_sold: number;
    base_reserve_expenses: number;
    sg_and_a_expenses: number;
    normal_profit: number;
    csv_profit: number;
    profit_adjustment: number;
    adjusted_expenses: number;
    indirect_allocation: number;
    total_expenses: number;
    profit: number;
    real_profit: number;
    margin: number | null;
    real_margin: number | null;
    ending_sales: number;
    ending_expenses: number;
    ending_profit: number;
}

export interface ActualResult {
    exists?: boolean;
    id?: number;
    month?: string;
    file: ActualResultFile;
    summary: ActualResultSummary;
    departments: ActualDepartment[];
    account_totals: ActualAccount[];
    uploads?: ActualResultUpload[];
}

export interface ActualResultDepartmentResponse {
    months: Record<string, ActualDepartment | null>;
}

export interface ActualAccountOption {
    account_key: string;
    account_code: string;
    account_name: string;
    category: ActualAccountCategory;
    bucket: string;
    bucket_label: string;
    amount_source?: string;
    source_department: string;
}

export interface ActualEditHistory {
    id: number;
    actual_result_department_id: number | null;
    project_record_id: number | null;
    department_name: string;
    action: string;
    account_key: string | null;
    before_value: ActualAccount | null;
    after_value: ActualAccount | null;
    note: string | null;
    edited_by: number | null;
    editor_name: string | null;
    created_at: string | null;
}
