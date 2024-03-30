import { defineStore } from "pinia";
interface State {
    id: number | null
}

export const useUrlTask = defineStore('urlTask', {
    state: (): State => ({
        id: null
    }),

    actions: {
        setUrlTaskId(payload: number | null){
            this.id = payload
        }
    }
})