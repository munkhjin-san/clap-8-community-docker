import { defineStore } from "pinia";
interface State {
    active: boolean,
    message: any,
    which: string | null,
    text: string | null,
    file: boolean,
    height: number,
    width: number
}

export const useQuoteReply = defineStore('quoteReply', {
    state: (): State => ({
        active: false,
        message: null,
        which: null,
        text: null,
        file: false,
        height: 100,
        width: 100
    }), 
    actions: {
        setQuoteReply(payload: any){
            this.active = payload.active
            this.message = payload.message
            this.which = payload.which
            this.text = payload.text
            this.file = payload.file
            this.height = payload.height
            this.width = payload.width
        }
    }
})