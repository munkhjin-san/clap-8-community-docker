import { User } from "./globalInterface";

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
