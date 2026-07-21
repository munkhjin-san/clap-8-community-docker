import { MessageFile, User } from "./globalInterface"


export interface ContactRecord {
    id: number | null
    name: string
    name_kana: string
    company_name: string
    company_name_kana: string
    department: string
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
    enrichment_status?: 'pending' | 'completed' | 'failed' | null
    position: string
    comments: ContactComment[]
    files?: ContactFile[]
    histories?: ContactHistory[]
    type: ContactType | null
    types?: ContactType[]
    contact_type_id: number | null
    pseudo_type: string
    projects?: ContactProject[]
    related_contacts?: ContactRecord[]
}
export interface ContactProject {
    id: number
    name: string
}
export interface ContactFile {
    id: number
    user_id: number
    name: string
    mime_type: string
    extension: string
    size: number
    contact_file_kind: 'image' | 'file'
}
export interface ContactPrivateMemo {
    id: number
    contact_record_id: number
    user_id: number
    body: string
    created_at: string
    updated_at: string
}
export interface ContactHistory {
    id: number
    event: 'created' | 'updated'
    field: string | null
    old_value: string | null
    new_value: string | null
    created_at: string
    user?: User
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
    types: string[];
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
