import { CommonFile, User } from "./globalInterface";

export interface Regulation {
    id: number;
    user_id: number | null;
    title: string | null;
    content: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    user?: User;
    regulation_files: RegulationFiles[];
}
export interface RegulationFiles {
    id: number;
    vector_file_id: string | null;
    mime_type: string;
    extension: string;
    name: string;
    path: string;
    size: number;
    chat_supported: boolean
}


