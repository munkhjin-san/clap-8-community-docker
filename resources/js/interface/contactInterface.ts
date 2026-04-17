import { MessageFile, User } from "./globalInterface"


export interface ContactRecord {
    id: number | null
    name: string	
    name_kana: string		
    company_name: string		
    company_name_kana: string		
    address: string		
    phone: string		
    fax: string		
    email: string		
    description: string		
    strategy: string	
    created_at: string	
    updated_at: string	
    icon_path: string | null	
    card_path: string | null
    card_hash?: string | null
    is_duplicate?: boolean
    duplicate_of?: number | null
    creator?: User
    updater?: User
    collaborators?: Collaborator[]
    data: string
    position: string
    comments: ContactComment[]
    type: ContactType | null
    contact_type_id: number | null
    pseudo_type: string
}
export interface ContactComment {
    id: number
    contact_record_id: number
    user_id: number
    comment: string
    user: User
    created_at: string
    files: MessageFile[]
}
export interface ContactType{
    id: number | null
    title: string
}
export interface Collaborator extends User{
    pivot: CollaboratorPivot;
}
interface CollaboratorPivot {
    role: string;
    private_memo: string;
    created_at: string;
    updated_at: string;
}
interface ContactBatchItemSummary {
    id: number;
    index: number;
    original_filename: string;
    status: string;
    error: string | null;
    scan_result: unknown;
    enrich_result: unknown;
    card_hash?: string | null;
    needs_review?: boolean;
    duplicate_candidates?: Array<Record<string, unknown>>;
    contact_record_id: number | null;
    contact: {
        id: number;
        name: string | null;
        company_name: string | null;
        position: string | null;
        card_path?: string | null;
        card_hash?: string | null;
        is_duplicate?: boolean;
        duplicate_of?: number | null;
        collaborators?: Array<{ id: number; name: string; role: string }>;
    } | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface ContactBatchSummary {
    id: number;
    status: string;
    scan_operation: string | null;
    enrich_operation: string | null;
    scan_attempts?: number;
    enrich_attempts?: number;
    scan_requested_at?: string | null;
    scan_completed_at?: string | null;
    enrich_requested_at?: string | null;
    enrich_completed_at?: string | null;
    error: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    dismissed_at?: string | null;
    duration_seconds?: number | null;
    counts: Record<string, number>;
    items: ContactBatchItemSummary[];
    logs?: Array<{
        id: number;
        stage: string;
        model: string | null;
        message: string | null;
        context: unknown;
        created_at: string | null;
    }>;
}

export interface ContactBatchNotificationSummary {
    id: number;
    title: string;
    message: string;
    status: string;
    url?: string | null;
    read_at?: string | null;
    created_at?: string | null;
    batch: ContactBatchSummary | null;
}
export interface BatchPayload {
    files: File[];
    type: number | null;
    p_type: string | null;
}

export interface DuplicateCandidateSummary {
    id: number;
    name: string | null;
    company_name: string | null;
    email: string | null;
    card_path?: string | null;
    card_hash?: string | null;
    updated_at?: string | null;
}

export interface DuplicateSummary {
    contact: Partial<ContactRecord> & { id: number | null; card_hash?: string | null; duplicate_of?: number | null; collaborators?: Array<{ id: number; name: string; role: string }> };
    candidates: DuplicateCandidateSummary[];
}
