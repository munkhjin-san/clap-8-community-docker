import { defineStore } from "pinia";
interface State {
    active: boolean,
    data: any
}

export const useTaskFeedback = defineStore('taskFeedback', {
    state: (): State => ({
        active: false,
        data: null
    }),
    actions: {
        setTaskFeedback(payload: any){
            this.active = payload.active
            this.data = payload.data
        }
    }
})