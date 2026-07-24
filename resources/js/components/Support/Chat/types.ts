export interface ChatItem {
    id: number
    message: string
    role: 'user' | 'assistant'
    source: string[] | null
    keywords: string[] | null
    created_at: string
}

export interface Conversation {
    id: number
    title: string | null
    items: ChatItem[]
    created_at: string
    updated_at: string
}

export type StreamStatus = 'connecting' | 'searching' | 'answering'

export interface ChatStreamEvent {
    type: 'conversation' | 'status' | 'delta' | 'done' | 'error'
    conversation?: Conversation
    status?: StreamStatus
    delta?: string
    message?: string
}
