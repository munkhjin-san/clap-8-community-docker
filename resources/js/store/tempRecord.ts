import { defineStore } from "pinia";
interface State {
    id: number | null
}

export const useTempRecord = defineStore('tempRecord', {
    state: (): State => ({
        id: null
    }),
    actions: {
        setTempRecord(payload: number | null){
            this.id = payload
        }
    }
})