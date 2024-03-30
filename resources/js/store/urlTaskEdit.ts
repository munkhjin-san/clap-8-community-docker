import { defineStore } from "pinia";
interface State {
    active: boolean
}

export const useUrlTaskEdit = defineStore('urlTaskEdit', {
    state: (): State => ({
        active: false
    }),
    actions: {
        setUrlTaskEdit(payload: boolean){
            this.active = payload
        }
    }
})