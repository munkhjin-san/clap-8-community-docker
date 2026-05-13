export interface FileRecord {
    id: number | null
    message_id: number | null
    original_file_id: number | null
    name: string
    board_id: number | null
    multiple_flag: number | null
    user_id: number | null
    unsigned_users: UnsignedUsers
    initialPage?: number
}
interface UnsignedUsers {
    id: number | null
    pivot: Pivot
    icon_path: number | null
    name: string
    icon_bg: string | null
}
interface Pivot {
    message_file_id: number | null
    signed: number | null
    user_id: number | null
    cancel_flag: number | null
}