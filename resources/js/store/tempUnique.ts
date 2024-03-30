import { defineStore } from "pinia";
interface State{
    ids: number[]
}

export const useTempUnique = defineStore('tempUnique', {
    state: (): State => ({
        ids: []
    }),
    actions: {
        setTempUniqueIds(payload: number[]){
            this.ids = payload
        }
    }
})