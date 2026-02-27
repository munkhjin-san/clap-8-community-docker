/** Assignment Fit Evaluation Response (v1.0.0) */

export type Decision = "適正あり" | "条件付き適正" | "要再検討" | "不適";

export interface EmployeeInfo {
  name: string;
  employee_id?: string;
}

export interface AssignmentInfo {
  project_name: string;
  role_name: string;
}

/** 1〜10点（integer） */
export type Score1to10 = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10;

/** overall score は 0.0〜10.0 の 0.1刻み */
export type OverallScore = number; // runtime validation recommended (see note below)

export interface Criterion {
  score: Score1to10;
  reason: string;
  /** Optional evidence strings */
  evidence?: string[];
}

export interface AssignEvaluations {
  must_conditions: Criterion;
  job_fit: Criterion;
  performance_history: Criterion;
  risk_history: Criterion;
}

export type OverallMethod = "weighted_sum_with_gate";
export type RoundingRule = "round_half_up_1dp";

/** 固定ウェイト（schema const） */
export interface OverallWeights {
  must_conditions: 0.3;
  job_fit: 0.3;
  performance_history: 0.25;
  risk_history: 0.15;
}

export interface Overall {
  score: OverallScore;
  method: OverallMethod;
  weights: OverallWeights;
  rounding: RoundingRule;
}

export interface ConditionItem {
  title: string;
  detail: string;
}

export interface FinalJudgement {
  decision: Decision;
  rationale: string;
  /** 条件付き適正の場合に具体条件を列挙（他の場合は省略可） */
  conditions?: ConditionItem[];
}

export interface Notes {
  limitations: string[];
}

export interface ProjectManagerCheckItem {
  type: "checkbox" | "shorttext" | "longtext";
  content: string;
  answer?: boolean | string;
}

export interface XRules {
  /** schema const */
  gate_rule?: "If must_conditions.score <= 2 then overall.score = 0.0 and final_judgement.decision = '不適'.";
  /** schema const */
  decision_rule?: "If overall.score >= 8.0 and all criterion scores >= 7 -> 適正あり; else if overall.score >= 6.5 -> 条件付き適正; else if overall.score >= 5.0 -> 要再検討; else -> 不適 (unless gate rule triggered).";
}

/** version は x.y.z */
export type SemverString = `${number}.${number}.${number}`;

export interface AssignmentFitEvaluationResponse {
  version: SemverString;
  employee: EmployeeInfo;
  assignment: AssignmentInfo;
  evaluations: AssignEvaluations;
  overall: Overall;
  final_judgement: FinalJudgement;
  notes: Notes;
  project_manager_check_items: ProjectManagerCheckItem[];
  manager_free_notes?: string;
  "x-rules"?: XRules;
}