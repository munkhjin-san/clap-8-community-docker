import { User } from "./globalInterface";

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
    members: User[];
    manager: User[];
    director: User;
    director_id: number;
}
interface ProjectGoal {
    id: number;
    project_id: number;
    user_id: number;
    employment_type: string;
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
    salary_issue: SalaryIssue;
    evaluation: Evaluation;
    edit_flag: number;
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
}
interface Evaluation {
    id: number;
    user_id: number;
    mentor_id: number;
    current_salary_rank: string;
    after_salary_rank: string;
    mentor: User
    general_position: number;
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
export type { Project, ProjectGoal, Evaluation, SalaryIssue, Increase }