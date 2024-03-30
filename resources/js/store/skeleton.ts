import { defineStore } from "pinia";
interface State {
    active: number
}

export const useSkeleton = defineStore('skeleton', {
    state: (): State => ({
        active: 0
    }),
    actions: {
        setSkeleton(payload: number){
            this.active = payload
        }
    }
})