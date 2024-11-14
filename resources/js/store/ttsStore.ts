import { defineStore } from "pinia";
interface State {
    active: boolean;
    id?: number;
}

export const useTtsStore = defineStore('ttsStore', {
    state: ():State => ({
        active: false,
        id: undefined
    }),
    actions: {
        setTtsStore(payload: Partial<State>) {
            this.active = payload.active
            this.id = payload.id
        }
    }
})
