import { User } from "./globalInterface"
import { Project } from "./projectInterface"

export interface Asset {
    id: number | null
    gl_number: string
    item_name: string
    model_number: string
    classification: number
    value: number
    status: number
    users: User[]
    projects: Project[]
}