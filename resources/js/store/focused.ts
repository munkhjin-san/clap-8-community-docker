import { defineStore } from "pinia";

interface State {
    active: boolean
}

export const useFocused = defineStore('focused', {
    state: (): State => ({
        active: true
    }),

    actions: {
        setFocused(payload: boolean){
            this.active = payload
        }
    }
})