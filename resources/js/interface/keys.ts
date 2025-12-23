import type { InjectionKey, Ref } from 'vue'
import { Board, Message, CopyData, Division, Task } from './globalInterface'
import { FastCreateData } from './calendarInterface'
import { DateTime } from 'luxon'
import { SubTaskPreData } from './projectInterface'



type BoardMethods = {
    remove: (item: Board) => void,
    edit: (item: Board) => void,
    create: () => void,
    reload: () => void,
    close: () => void,
    open: (item: Board, second_atr?:any) => void,
    detail: (item: Board) => void,
    invite: (item: Board) => void,
    members: (item: Board) => void,
    pin: (item: Board) => void,
    leave: (item: Board) => void,
    refreshMessages: (message: Message, targetId?: number) => void,
    privateSearch: () => void,
    setNotification: (item: Board) => void
    messageLoader:(val: boolean) => void

}
type MessageMethods = {
    addQueue: (item: Message) => void,
    copy: (item: CopyData) => void,
    check: (item: Message, request?:string) => void,
    sent: (item: Message, list: Message[], last_message: any) => void,
    sendError: (item: Message) => void,
    removeError: (id: number | string | null) => void,
    resetReplyQuot: () => void,
    remind: (item: Message) => void,
}
type KeyboardMethods = {
    setKeyboardHeight: (value: number) => void,
    getKeyboardHeight: Ref<number>
}
type PostMethods = {
    commentCount: (num: number, id: number) => void
}
type DivisionMethods = {
    remove: (item: Division) => void
    edit: (item: Division) => void
    create: (item: Division) => void
}


type GanttMethods = {
    create: (args: Partial<Task>) => void
    reload: (args) => Promise<void>;
    fastCreate: (args: FastCreateData) => void

    jumpTo: (instance: DateTime) => void
    refreshBoardTasks: () => void
}

type GanttProjectMethods = {
    createTask: (args: Partial<Task>) => void
    refreshProject: (args) => Promise<void>;
    remove: (task: Task) => void
    addSubTask: (data: SubTaskPreData) => void
}
const BoardMethodsKey = Symbol('boardItem') as InjectionKey<BoardMethods>


const MessageMethodsKey = Symbol('messageItem') as InjectionKey<MessageMethods>

const KeyboardMethodsKey = Symbol('ketyboard') as InjectionKey<KeyboardMethods>

const PostMethodsKey = Symbol('postComment') as InjectionKey<PostMethods>

const DivisionMethodsKey = Symbol('divisionItem') as InjectionKey<DivisionMethods>

const GanttMethodsKey = Symbol('ganttItem') as InjectionKey<GanttMethods>

const GanttProjectMethodsKey = Symbol('ganttProjectItem') as InjectionKey<GanttProjectMethods>

export { BoardMethodsKey, MessageMethodsKey, KeyboardMethodsKey, PostMethodsKey, DivisionMethodsKey, GanttMethodsKey, GanttProjectMethodsKey }
export type { BoardMethods, MessageMethods, KeyboardMethods, PostMethods, DivisionMethods, GanttMethods, GanttProjectMethods }
