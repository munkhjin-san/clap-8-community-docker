import { CommonFile } from "./globalInterface"

export interface NoticeRecord {
    id: number
    title: string
    body?: string
    created_at: string
    read: boolean
    files?: CommonFile[]
}
