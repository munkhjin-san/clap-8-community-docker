import { Evaluation } from "./projectInterface"

export type DialogMethods = {
    confirm: (message: string, options?: ConfirmOptions) => Promise<boolean>
    notify: (message: string) => void
    info: (message: string) => void
}

interface ConfirmOptions {
    answers: Array< Answer | null >
}
interface Answer {
    label: string | null
    value: boolean | null
}

export interface MenuList {
    title: string;
    action: () => void;
    children?: MenuList[]; 
    parent?: HTMLElement | null
}
export interface CommandButtonInterface {
    title: string;
    action: () => void;
}

export interface Task{
    id: number,
    executors: User[],
    supervisors: User[]
}

export interface User{
    id: number,
    name: string,
    icon_id: number
    pivot?: TaskUserPivot
    position_id: number;
    evaluation: Evaluation
}
export interface TaskUserPivot{
    comment: string | null
    comp_flag: number
    late_answer: number
    late_answer_custom: string | null
    status_flag: number
}

export type Dialog = {
    confirm: (question: string, options?: any) => Promise<Answer>;
    notify: (message: string) => void;
    info: (message: string) => void;
}