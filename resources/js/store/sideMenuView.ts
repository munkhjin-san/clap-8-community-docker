import { defineStore } from "pinia";
interface State {
    active: boolean
}

export const useSideMenuView = defineStore('sideMenuView', {
    state: (): State => ({
        active: false
    }),
    actions: {
        setSideMenuView(payload: boolean){
            this.active = payload
        }
    }
})