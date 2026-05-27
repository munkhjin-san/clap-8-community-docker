import { User } from "./globalInterface";
import { CommonFile } from "./globalInterface";

export interface SupportConversationItem {
    id: number;
    support_conversation_id: number;
    message: string | null;
    role: string;
    source: string[]
    keywords: string[]
}

export interface SupportConversation {
    id: number;
    user_id: number;
    title: string | null;
    user?: User;
    conversation_id: string | null;
    items: SupportConversationItem[];
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export type CreateSupportConversationPayload = {
    user_id: number;
    conversation_id: string | null;
};

export type CreateSupportConversationItemPayload = {
    support_conversation_id: number;
    message?: string | null;
    role?: string | null;
};

export type SystemUpdateCategory = 'maintenance_plan' | 'update_plan' | 'update_log' | 'notice';

export type SystemUpdateStatus = 'draft' | 'scheduled' | 'published' | 'completed' | 'canceled';

export type SystemUpdateDetailType =
    | 'new_feature'
    | 'improvement'
    | 'error_fix'
    | 'security'
    | 'performance'
    | 'maintenance'
    | 'ui_change'
    | 'known_issue'
    | 'notice'
    | 'other';

export interface SystemUpdateDetail {
    id?: number;
    system_update_record_id?: number;
    type: SystemUpdateDetailType;
    title: string;
    content?: string | null;
    sort_order?: number;
    files?: CommonFile[];
    created_at?: string;
    updated_at?: string;
}

export interface SystemUpdateRecord {
    id?: number;
    user_id?: number | null;
    category: SystemUpdateCategory;
    title: string;
    summary?: string | null;
    status: SystemUpdateStatus;
    is_published: boolean;
    published_at?: string | null;
    scheduled_start_at?: string | null;
    scheduled_end_at?: string | null;
    details: SystemUpdateDetail[];
    user?: User;
    created_at?: string;
    updated_at?: string;
    must_read: boolean;
    checked_by_user?: boolean;
}

export type EmergencyContactStatus = 'pending' | 'complete';

export interface EmergencyContactRecord {
    id: number;
    user_id: number;
    content: string;
    status: EmergencyContactStatus;
    actions_count: number;
    created_at: string;
    updated_at: string;
}

export interface EmergencyContactAction {
    id: number;
    emergency_contact_id: number;
    user_id: number;
    text: string;
    created_at: string;
    updated_at: string;
    user: User;
}
