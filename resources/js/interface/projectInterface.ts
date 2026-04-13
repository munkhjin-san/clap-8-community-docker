import { DateTimeUnit, Interval } from "luxon";
import { CommonFile, MessageFile, StatusLog, Task, User } from "./globalInterface";
import { AssignmentFitEvaluationResponse } from "./assign";
import { FileRecord } from "./trayInterface";
import { CustomFormBlock } from "./customFormInterface";

export type ContractFindingSeverity = 'high' | 'medium' | 'low' | 'unknown'

export interface ContractFindingAnchor {
    clause_id?: string;
    page?: number;
    query?: string;
    fallback_query?: string;
    matched_text?: string;
    paragraph_index?: number;
}

export interface ProjectContractFinding {
    section?: string;
    location?: string;
    issue: string;
    severity: ContractFindingSeverity;
    rationale: string;
    suggestion: string;
    quote?: string;
    page?: number;
    category?: string;
    score?: number;
    negotiation_tip?: string;
    anchor?: ContractFindingAnchor | null;
}

export interface ProjectContractResult {
    overall_risk: ContractFindingSeverity;
    findings: ProjectContractFinding[];
}

export interface ContractComparisonChange {
    id: string;
    change_type: 'added' | 'removed' | 'modified';
    clause_label?: string;
    base_page?: number;
    target_page?: number;
    before_text?: string;
    after_text?: string;
    anchor_base?: ContractFindingAnchor | null;
    anchor_target?: ContractFindingAnchor | null;
}

export interface ContractComparisonResult {
    base_contract_id: number;
    target_contract_id: number;
    summary: {
        added: number;
        removed: number;
        modified: number;
    };
    changes: ContractComparisonChange[];
}

export interface ProjectContractResponse {
    id: number;
    project_record_id: number;
    review_type: 'quick' | 'deep';
    overall_risk: ContractFindingSeverity;
    findings_count: number;
    version?: number;
    result_json?: ProjectContractResult;
    response_hash?: string | null;
    file_path?: string | null;
    file_url?: string | null;
    download_url?: string | null;
    file_size?: number | null;
    size?: number | null;
    created_at?: string;
    updated_at?: string;
    role: string;
    contract_type: string;
    active: boolean;
    comparison_json?: ContractComparisonResult | null;
}
export interface ProjectMember extends User {
    pivot: {
        authority: number;
        compatibility_number: number | null;
        review: string | null;
        role_record?: MemberRole | null
        assign_data?: AssignmentFitEvaluationResponse | null
        overall_assign_score?: number | null
    },
    assign_records?: ProjectAssignRecord[]
}
export interface ProjectType {
    id: number
    key: string
    label: string
}
interface Project {
    id: number;
    name: string;
    date_start: string;
    date_end: string;
    overview: string;
    strategy: string;
    kgi: string;
    kpi: string;
    budget: string;
    stakeholder: string;
    members: ProjectMember[];
    manager: ProjectMember[];
    status: string;
    mission: string;
    innovation: string;
    operation: string;
    strategy_miso: string;
    director: User;
    tasks: Task[];
    pseudo_start?: string
    pseudo_end?: string
    duration?: number
    order?: number;
    board_id?: number;
    tasks_count: number;
    director_id: number;
    project_conditions: ProjectCondition[]
    category: string[]
    partners: string[]
    customers: string[]
    industry_type: string[]
    description: string
    private_memo: string
    contract?: ProjectContractResponse | null
    contracts?: ProjectContractResponse[]
    is_new: boolean
    is_renewable: boolean
    has_goals?: boolean
    has_actual_func: boolean
    unit_id?: 'JPY' | 'COUNT' | 'HOUR' | 'CUSTOM'
    custom_unit_label?: string | null
    actual_statuses?: ProjectActualStatus[]
    transitioned_at?: string
    completed_at?: string | null
    member_roles?: MemberRole[]
    checkitems: ProjectCheckItem[]
    reports: ProjectGoalReport[]
    has_confirm_badge?: boolean
    has_comment_badge?: boolean
    contract_started_at: string
    project_type_id?: number | null
    projectType?: ProjectType | null
    project_type?: ProjectType | null
    specs?: ProjectSpecs | null
    projectAssignRecords?: ProjectAssignRecord[]
    total_work_time?: number
}
export interface ProjectAssignRecord {
    id: number;
    created_user_id: number | null;
    score: number | null;
    assign_data: AssignmentFitEvaluationResponse | null;
    status: string | null;
    compatibility: string | null;
    project_record_id: number | null;
    user_id: number | null;
    created_at: string;
    updated_at: string;
    confirmed_at?: string | null;
    deleted_at?: string | null;
    created_user?: User | null;
    support_level: 'green' | 'orange' | 'red' | null;
    user?: User | null;
    project_record?: Project | null;
    status_histories?: ProjectAssignStatusHistory[];
    questions?: CustomFormBlock[];
    actions?: ProjectAssignAction[];
}

export interface ProjectAssignStatusHistory {
    id: number;
    project_assign_record_id: number;
    project_record_id: number | null;
    user_id: number | null;
    from_status: string | null;
    to_status: string;
    changed_at: string | null;
    created_at: string;
    updated_at: string;
    user?: User | null;
}

export type ProjectAssignActionAdditionalLevel = {
    value: string;
    label: string;
    color: string;
    class: string;
};

export interface ProjectAssignAction {
    id: number;
    project_assign_record_id: number;
    content: string;
    created_at: string;
    updated_at: string;
    user?: User | null;
    action_type: string | null;
    additional_data?: {
        previous_level: ProjectAssignActionAdditionalLevel;
        new_level: ProjectAssignActionAdditionalLevel;
    };
}

export type ProjectCheckItem = {
    id: number
    project_record_id: number
    project_checkitem_template_id?: number | null
    project_checkitem_category_id?: number | null
    category: string
    label: string
    status: string
    is_applicable?: boolean
    sort_order: number
    checked_by: number | null
    checked_at: string | null
    check_user: User
    link_user: User
    children: ProjectCheckItem[]
}
export type ProjectActualStatus = {
    status_id: number | null;
    label: string;
    sort_order?: number;
    is_system_default?: boolean;
    custom_label?: string;
}
type ProjectSpecs = {
    id: number | null;
    project_id: number | null;
    spec_data: any;
    created_by: number;
    updated_by: number;
    files: CommonFile[]
    plan_data: any;
}
interface ProjectCondition {
    project_record_id: number;
    user_id: number;
    week_date_start: Date;
    value: number;
}
interface VirtualSpan {
    interval: Interval
    unit: DateTimeUnit,
    expanded: boolean,
    selectedMonth: number| null
    selectedYear: number| null
    selectedWeek: number| null
    selectedIndex: number| null
}
export interface ProjectOfGoal extends Project {
    is_member: boolean;
    is_manager: boolean;
}
interface ProjectGoal {
    id: number;
    project_id: number;
    user_id: number;
    employment_type: string;
    title: string;
    start_date: string;
    end_date: string;
    target_period: string;
    outcome_goal: string;
    action_plan: string;
    expected_effect: string;
    situation_analysis: string;
    target_value: string;
    status: number;
    ai_review: string;
    achievement_rate: number;
    report: string;
    result: string;
    project: ProjectOfGoal;
    comment: string | null;
    salary_issue: SalaryIssue | null;
    evaluation: Evaluation;
    custom_instruction: string;
    private_memo: string;
    kgi: string;
    miso: string;
    steps: ProjectGoalStep[]
    reports: ProjectGoalReport[]
    stakeholder_name: string | null;
    stakeholder_point: number | null;
    stakeholder_review: string | null;
    files?: FileRecord[];
    year: number;
    which_half: string
    user: User;
    goal_notifications_count?: number;
    status_logs?: StatusLog[];
}
interface ProjectGoalReport {
    content: string;
    user: User;
    created_at: string;
    files: MessageFile[]
    type?: string;
}
interface ProjectGoalStep {
    id?: number; 
    content: string;
    status: number;
    progress: number;
}
interface SalaryIssue {
    id: number;
    user_id: number;
    project_goal_id: number;
    title: string;
    theme: string;
    content: string;
    ability: string;
    review: string;
    date: string;
    status: number;
    result: string;
    reports: ProjectGoalReport[]
    actions: SalaryIssueAction[]
    comment: string | null;
    files?: MessageFile[];
    status_logs?: StatusLog[];
    issue_notifications_count?: number;
}
export interface SalaryIssueAction {
    id: number;
    salary_issue_id: number;
    user_id: number;
    content: string;
    learning_content: string;
    learning_title: string;
    status: number;
}
interface Evaluation {
    id: number;
    user_id: number;
    mentor_id: number;
    current_salary_rank: string;
    after_salary_rank: string;
    mentor: User
    general_position: number;
    current_level: string;
    candidate: Candidate[];
}
interface Increase {
    id: number;
    user_id: number;
    candidate: Candidate[];
    last_set: number;
    last_achieved: number;
    change_in_position: number;
    position_approved: number;
    target_period: string;
    reason: string;
    checklist: any;
    evaluation: any;
    mentor_entry: string;
    outcome_goals: any;
    salary_issues: any;
}
interface Candidate {
    increase_id: number;
    last_candidate: string;
    next_candidate: string;
}
export interface FinanceComment {
    id: number;
    project_record: number;
    user_id: number;
    comment: string;
    type: string;
    period?: string;
    author: User;
    created_at: string;
    checked_users: User[];
    reply: FinanceComment;
}
export interface ResourceComment {
    id: number;
    member_name: string;
    user_id: number;
    comment: string;
    period?: string;
    author: User;
    created_at: string;
    checked_users: User[];
    reply: ResourceComment;
}
interface QuickEditText {
    text:string, 
    id: number | null, 
    editable: boolean
}
export interface MemberRole {
    id: number;
    project_record_id: number;
    user_id: number;
    title: string | null;
    description: string | null;
    comment: string | null;
    risk: string | null;
    risk_management: string | null;
    member_limit: number | null;

    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
    work_conditions: string[]

    user?: User;
}
interface SubTaskPreData {
    mainTaskId: number | null,
    subTaskData: Partial<Task>
    active: boolean
}
export interface FinancialData {
    sales: number;
    expense: number;
    profit: number;
    profit_rate: number;
    id?: number;
    has_data?: boolean;
}
  
export interface MonthlyData {
    yearly_plan: FinancialData;
    profit: FinancialData;
    settlement: FinancialData;
}
  
export interface YearlyFinancialData {
    [project_name: string]: MonthlyData;
}
export type {
    Project,
    ProjectGoal,
    Evaluation,
    SalaryIssue,
    Increase,
    VirtualSpan,
    QuickEditText,
    SubTaskPreData,
    ProjectGoalStep,
    ProjectGoalReport,
    Candidate
}
