import { defineStore } from "pinia";
interface State {
    active: boolean,
    data: any
}

export const useTaskRequest = defineStore('taskRequest', {
    state: (): State => ({
        active: false,
        data: null
    }),
    actions: {
        setTaskRequest(payload: any){
            this.active = payload.active
            this.data = payload.data
        }
    }
})