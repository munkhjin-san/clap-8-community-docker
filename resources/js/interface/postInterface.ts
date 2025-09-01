
import { LocationQueryValue } from "vue-router";
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
    entries: PostEntry[];
    awards: PostAward[];
    chargeable: boolean;
    user: User;
    award_entry: number
    donation_target: string | null;
    receipts: CommonFile[]
    refresh_amount: string;
}
interface PostAward extends User {
    pivot: {
        award_bet: number;
    }
}
interface Clap {
    from_user: number;
    record_id: number;
}
export interface PostEntry {
    id: number;
    user: User;
    calories: number;
    comment: string;
    files: CommonFile[];
    created_at: string;
    updated_at: string;
    comments: PostComment[];
    comments_count: number;
}

export interface PostQuery {
    id: string | LocationQueryValue[] | null;
    app_type: string | LocationQueryValue[] | null;
    search_tags: string | LocationQueryValue[] | null;
    member: string | LocationQueryValue[] | null;

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
export interface TopEntryUser {
    user: User;
    post_count: number;
    sum_calories: number;
    award: string
}