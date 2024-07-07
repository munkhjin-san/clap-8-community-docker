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