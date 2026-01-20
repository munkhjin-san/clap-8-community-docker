import { DateTimeUnit, Interval } from "luxon";
import { MessageFile, Task, User } from "./globalInterface";

export type ContractFindingSeverity = 'high' | 'medium' | 'low' | 'unknown'

export interface ProjectContractFinding {
    section?: string;
    issue: string;
    severity: ContractFindingSeverity;
    rationale: string;
    suggestion: string;
}

export interface ProjectContractResult {
    overall_risk: ContractFindingSeverity;
    findings: ProjectContractFinding[];
}

export interface ProjectContractResponse {
    id: number;
    project_record_id: number;
    review_type: 'quick' | 'deep';
    overall_risk: ContractFindingSeverity;
    findings_count: number;
    result_json?: ProjectContractResult;
    response_hash?: string | null;
    file_path?: string | null;
    file_url?: string | null;
    file_size?: number | null;
    size?: number | null;
    created_at?: string;
    updated_at?: string;
    role: string;
    contract_type: string;
    active: boolean;
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
    status: number;
    members: (User & {
        pivot: {
            authority: number;
        }
    })[];
    manager: (User & {
        pivot: {
            authority: number;
        }
    })[];
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
    is_new: boolean
    has_goals?: boolean
    has_actual_func: boolean
    unit_id?: 'JPY' | 'COUNT' | 'HOUR' | 'CUSTOM'
    custom_unit_label?: string | null
    actual_statuses?: ProjectActualStatus[]
    transitioned_at?: string
}
export type ProjectActualStatus = {
    status_id: number | null;
    label: string;
    sort_order?: number;
    is_system_default?: boolean;
    custom_label?: string;
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
    project: Project;
    comment: string | null;
    salary_issue: SalaryIssue;
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
}
interface ProjectGoalReport {
    content: string;
    user: User;
    created_at: string;
    files: MessageFile[]
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
}
interface Evaluation {
    id: number;
    user_id: number;
    mentor_id: number;
    current_salary_rank: string;
    after_salary_rank: string;
    mentor: User
    general_position: number;
    current_level: string
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
    ProjectGoalReport
}
