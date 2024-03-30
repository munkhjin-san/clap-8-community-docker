import { defineStore } from "pinia";
interface State{
    active: boolean
}

export const useQuoteWindow = defineStore('quoteWindow', {
    state: (): State => ({
        active: false
    }),

    actions: {
        setQuoteWindow(payload: boolean){
            this.active = payload
        }
    }
})