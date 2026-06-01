import { CommonFile, User } from './globalInterface';
import type { Project } from './projectInterface';
import type { UpdateLog } from './updateLog';
import type { AppComment } from './appComment';
import type { UserReadHistory } from './userReadHistory';

export type IncidentStatus = 'open' | 'in_progress' | 'resolved' | 'closed' | '完了' | (string & {});

export interface IncidentCategory {
    id: number;
    name: string | null;
    description: string | null;
    sort_order: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface IncidentPunishment {
    id: number;
    name: string | null;
    description: string | null;
    sort_order: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface IncidentReport {
    id: number;
    incident_id: number;
    user_id: number | null;
    report: string | null;
    user?: User;
    created_at?: string;
    updated_at?: string;
}

export interface Incident {
    id: number;
    title: string | null;
    description: string | null;
    reported_by?: number | null;
    caused_by: number | null;
    incident_category_id: number | null;
    incident_punishment_id?: number | null;
    reason?: string | null;
    prevention?: string | null;
    prevention_apply_status?: string | null;
    instruction?: string | null;
    resolution?: string | null;
    occured_location?: string | null;
    memo?: string | null;
    aftermath_comment?: string | null;
    occurred_date: string | null;
    instruction_date?: string | null;
    related_parties?: string | null;
    project_record_id: number | null;
    status: IncidentStatus | null;
    amount_of_damage?: number | null;
    payee?: string | null;
    expense_details?: string | null;
    risk_level?: number | null;
    severity_level?: number | null;
    private_notes?: string | null;
    committee_members?: string | null;
    committee_decision?: string | null;
    committee_decision_date?: string | null;
    deleted_at?: string | null;
    created_at?: string;
    updated_at?: string;
    last_read_at?: string | null;
    comments_count?: number;
    unread_comments_count?: number;
    unread_update_logs_count?: number;
    file_ids?: number[];

    // Relationships
    reportedByUser?: User;
    causedByUser?: User;
    projectRecord?: Pick<Project, 'id' | 'name' | 'date_start' | 'date_end' | 'category' | 'members' | 'manager'>;
    reported_by_user?: User;
    caused_by_user?: User;
    project_record?: Pick<Project, 'id' | 'name' | 'date_start' | 'date_end' | 'category' | 'members' | 'manager'>;
    category?: IncidentCategory;
    punishment?: IncidentPunishment;
    reports?: IncidentReport[];
    logs?: UpdateLog[];
    comments?: AppComment[];
    files?: CommonFile[];
    read_histories?: UserReadHistory[];
}
