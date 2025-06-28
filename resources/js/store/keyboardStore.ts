import { defineStore } from "pinia";
interface State {
    height: number;
}

export const useKeyboardStore = defineStore('keyboardStore', {
    state: (): State => ({
        height: 0
    }),

    actions: {
        setKeyboardHeight(payload: number){
            this.height = payload
        }
    }
})