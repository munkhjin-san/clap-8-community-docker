import { User } from './globalInterface';
import type { Project } from './projectInterface';

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
    instruction?: string | null;
    resolution?: string | null;
    occured_location?: string | null;
    memo?: string | null;
    occurred_date: string | null;
    instruction_date?: string | null;
    related_parties?: string | null;
    project_record_id: number | null;
    status: IncidentStatus | null;
    amount_of_damage?: number | null;
    risk_level?: number | null;
    severity_level?: number | null;
    private_notes?: string | null;
    committee_members?: string | null;
    committee_decision?: string | null;
    committee_decision_date?: string | null;
    deleted_at?: string | null;
    created_at?: string;
    updated_at?: string;

    // Relationships
    reportedByUser?: User;
    causedByUser?: User;
    projectRecord?: Pick<Project, 'id' | 'name' | 'date_start' | 'date_end' | 'category'>;
    reported_by_user?: User;
    caused_by_user?: User;
    project_record?: Pick<Project, 'id' | 'name' | 'date_start' | 'date_end' | 'category'>;
    category?: IncidentCategory;
    punishment?: IncidentPunishment;
    reports?: IncidentReport[];
}
