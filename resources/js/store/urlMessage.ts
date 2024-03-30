import { defineStore } from "pinia";
interface State {
    id: number | null
}

export const useUrlMessage = defineStore('urlMessage', {
    state: (): State => ({
        id: null
    }),
    actions: {
        setUrlMessageId(payload: number | null){
            this.id = payload
        }
    }
})