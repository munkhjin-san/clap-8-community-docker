import type { User } from './globalInterface';

export interface UserReadHistory {
    id: number;
    readable_type: string;
    readable_id: number;
    user_id: number;
    last_read_at: string | null;
    created_at?: string;
    updated_at?: string;
    user?: User;
}
