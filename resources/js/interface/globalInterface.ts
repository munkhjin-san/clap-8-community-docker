import { Portfolio } from "./lessonInterface"
import { Evaluation, Project } from "./projectInterface"

export type DialogMethods = {
    confirm: (question: string, options?: ConfirmOptions) => Promise<Answer>
    notify: (message: string) => void
    info: (message: string) => void
}
export type Dialog = {
    confirm: (question: string, options?: any) => Promise<Answer>;
    notify: (message: string) => void;
    info: (message: string) => void;
}
export interface ConfirmOptions {
    answers: Answer[]
}
export interface Answer {
    label: string
    value: any
}
export interface Board {
    id: number;
    title: string | null; 
    private_flag: number;
    board_to_users: BoardMember[];
    last_message: LastMessage
    icon_path: string;
    created_at: string
    user?: User
    project: Project | null

}
interface LastMessage {
    message: string | null
    message_text: string | null
    message_files_exists: boolean
}
export interface BoardMember {
    admin_flag: number;
    deleted_status: number;
    id: number;
    last_act: string | null
    last_message: number | null
    left_at: string | null
    pin_flag: number
    record_id: number
    user: User;
    user_id: number | null;
    notification: number;
}
export interface Tag {
    text: string;
    id: number;
}
export interface User {
    positions: any
    id: number;
    name: string | null;
    icon_path: string | null | undefined;
    icon_bg: string | null
    work_time_day?: number;
    position_id?: number;
    user_code?: number | null;
    weathers?: Weather;
    footer_view?: boolean;
    sign_path?: string | null;
    color?: number | null;
    ical_key?: string | null;
    work_authority?: number | null;
    linkable?: boolean | false;
    deleted_at?: string;
    portfolio?: Portfolio[];
    user_album?: UserAlbum[];
    position?: Position;
    office?: Office;
    name_kana?: string;
    today_weather?: Weather;
    retire?: number;
    email?: string;
    days_weathers?: Weather[];
    details?: Details | null;
    divisions?: Division[]
    role?: Role
    login?: string
    initial?: number;
    work_email?: string;
    phone_number?: string;
    on_leave?: number;
    evaluation: Evaluation
    general_position: string
}
export interface Details{
    recommend: string;
    intro: string;
    motto: string;
    work_email: string;
    phone_number: string
}
export interface UserAlbum {
    id: number;
    extension: string;
    intro_flag: number;
    mime_type: string;
    name: string;
    path: string;
    tags: Tag[];
    title: string;
    user_id: number;
}

interface Weather {
    value_int: number;
    id: number;
    date: string
    user_id?: number
}
export interface Position {
    id: number;
    name: string;
    members: User[]
}
export interface Office {
    id: number;
    name: string;
    address: string;
    fax: string;
    tel: string;
}
export interface Facility {
    id: number;
    name: string;
    description: string;
    use_calendar: number;
    image: string; 
    has_zoom_count?: number 
}
interface Icon {
    extension: string;
    id: number
}

export interface Message {
    id: number | string | null;
    record_id: number;
    user_id: number;
    reply_flag?: boolean | null;
    reply_id?: number | null;
    quot_id?: number | null;
    forward_message_id?:  number | string | null;
    quot_flag?: boolean | null;
    attached_temp_files?: MessageFile[] | [];
    message: string | null;
    message_text?: string | null;
    check_flag?: number;
    emoji_flag?: number;
    info_flag?: number;
    reacted_users?: User[];
    checked_users?: User[];
    unchecked_users?: User[];
    quot_message?: string | null;
    created_at: string;
    deleted_at: string | null;
    user: User;
    message_files?: MessageFile[] | null;
    message_reply: Message | null;
    message_quot: Message | null;
    message_forward: Message | null;
    message_remind_users?: {
        reminded: number;
        user_id: number;
    }[];
    task?: any;
    error?: boolean;
    message_attachments? : UploadingFile[] | [];
    u_id?: string | null;
    sharing_files?: SharingFile[];
    draft_flag?: number;
}
export interface MessageFile {
    id: number;
    message_id: number;
    board_id: number
    user_id: number;
    name: string | null;
    mime_type: string | null;
    extension: string | null;
    size: number;
    edit_flag: number;
    sign_flag: number;
    multiple_flag: number;
    original_file_id: number | null;
    unsigned_users: User[];
    signed_users: User[];
    user: User | null
}
export interface UnreadMessages {
    active: boolean;
    id: number | null;
    count: number;
}
export interface Task {
    id: number
    updated_user: number
    parent_task_id: number | null
    user_id: number
    board_id: number
    projects: Project[]
    message_id: number
    title: string | null
    start_at: string
    divisions: Division[]
    end_at: string
    remarks?: string
    comp_flag: number
    created_at: string | null
    executors: TaskUser[]
    supervisors: TaskUser[]
    response_time: number
    duration?: number
    sync_to_schedule: number
    order?: number
    sub_tasks: Task[]
    comments: TaskComment[]
    pseudo_start?: string
    pseudo_end?: string
    unread_comments?: number
    pre_executors?: TaskUser[]
    sort_number: number
    project_record_id?: number
    board?: Board
    project?: Project 
    comments_count?: number
}

export interface GanttColumnData {
    date_short: string, 
    date_full: string, 
    is_holiday: string|null, 
    projects:Project[]
}

export interface TaskComment{
    id: number
    task_record_id: number
    comment: string | null
    user: User
    created_at: string
}
export interface TaskUser {
    id: number,
    name: string,
    icon_path: string,
    pivot: TaskUserPivot,
    icon_bg: string | null
}
export interface TaskUserPivot {
    id: number;
    comp_flag: number;
    cancel_flag?: number;
    supervisor: number;
    status_flag: number;
    progress_flag: number;
    pin_flag: number;
    glowd_nine: number;
    try_flag: number;
}
export interface ActiveBoard {
    value: Board
}
export interface CopyData {
    height: number
    width: number
    text: string | null
}
export interface UploadingFile {
    src: string;
    name: string;
    uId: string;
    ext: string;
    file: File;
}

export interface SharingData {
    active: boolean,
    message: Message | null,
    title: string,
    text: string,
    files?: SharingFile[],
    from?: string,
    to: string,
    drag: boolean,
    instruction: string
}
export interface SharingFile {
    path: string;
    record: MessageFile
}

export interface YearMonth {
    year:  number;
    month: number;
    select: boolean
}

export interface CommonFile {
    id: number;
    path?: string;
    user_id: number;
    extension: string;
    removed_at?: string;
    mime_type: string;
    name: string;
    size: number;
}
export interface Division {
    id: number;
    name: string;
    parent: number;
    is_root: number;
}
export interface MemberPosition{
    id: number;
    members: User[];
    name: string;
    order: number;
}

export interface CommandButtonInterface {
    title: string;
    action: () => void;
}

export interface InstantData {
    cX: number;
    cY: number;
    name?: string | null;
    id: number | null;
}

export interface ApplicationRecord {
    id: number
    name: string
    name_jp: string
    route_path: string
    icon: string;
    creatable: number;
    is_default: number
}

export interface AppState {
    app_record: {
      name: string | null;      
      name_jp: string | null;  
    },
    writable: number | null;
    readable: number | null;
    permission: number
}
export interface UserClapStatistic {
    knowledge?: number
    nice?: number
    portfolio?: number
    sum: number
}
export interface ImageMeta {
    name: string
    url: string | ArrayBuffer | null
    mime_type: string
    extension: any
    size: number
}
export interface CropperComplete {
    meta: string | null;
    blob: Blob | null;
    source: File | null
}

export interface WorkDivision {
    id: number;
    name: string;
    parent: number;
    is_root: number;
    children:WorkDivision[];
    members: User[]
}

export interface Role {
    id: number
    name: string
    users: User[]
}
export interface MenuList {
    title: string;
    action: () => void;
    children?: MenuList[]; 
    parent?: HTMLElement | null;
    checked?: boolean;
}