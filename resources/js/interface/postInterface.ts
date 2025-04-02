
import { CommonFile, User } from "./globalInterface";

export interface Post{
    app_type: number;
    claps: Clap[];
    comments_count: number;
    content: string;
    files: CommonFile[];
    id: number;
    key_tags: any;
    key_users: any;
    referrer: string;
    tags: PostTag[];
    title: string;
    user_id: number;
    to_users: User[];
    created_at: string;
    content_rule: string;
    content_goal: string;
    date_start: string;
    date_end: string;
    status_flag: number;
    result: string;
    result_files: CommonFile[];
    community_user_id: number
}

interface Clap {
    from_user: number;
    record_id: number;
}



export interface PostTag{
    id: number;
    text: string;
    pivot: TagPivot;
    hits: number;
    occurrence: number;
}

interface FilePivot {
    record_id: number;
    file_id: number;
}
interface TagPivot {
    record_id: number;
    tag_id: number;
}

export interface PostSearchHistory {
    content: string;
    hits: number;
    id: number;
    user_id: number;
}
export interface PostComment {
    app_name: string;
    created_at: string;
    emoji_flag: number;
    id: number;
    messages: string;
    record_id: number;
    user: User;
    user_id: number;
    claps: Clap[];
}