import { MessageFile, User } from './globalInterface';

export interface AppComment {
    id: number;
    commentable_type: string;
    commentable_id: number;
    user_id: number | null;
    content: string;
    mentioned_user_ids: number[] | null;
    user?: User | null;
    /** 移行してきたコメントの元の書き手。こちらのユーザーには結び付けない（表示だけ）。 */
    legacy_author?: string | null;
    files?: MessageFile[];
    created_at: string;
    updated_at: string;
}
