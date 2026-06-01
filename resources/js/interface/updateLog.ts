import { User } from './globalInterface';

export type UpdateLogAction = 'created' | 'updated' | 'status_changed' | 'deleted' | 'restored' | (string & {});

export interface UpdateLog {
    id: number;
    loggable_type: string | null;
    loggable_id: number | null;
    user_id: number | null;
    action: UpdateLogAction;
    field: string | null;
    old_value: unknown | null;
    new_value: unknown | null;
    changes: Record<string, {
        old?: unknown;
        new?: unknown;
        display_old?: unknown;
        display_new?: unknown;
    }> | null;
    display_changes?: Record<string, {
        old?: unknown;
        new?: unknown;
        display_old?: unknown;
        display_new?: unknown;
    }> | null;
    note: string | null;
    is_unread?: boolean;
    user?: User;
    created_at?: string;
}
