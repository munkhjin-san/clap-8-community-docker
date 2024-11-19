import { defineStore } from "pinia";
interface State {
    active: boolean;
    id?: number;
    play: boolean;
}

export const useTtsStore = defineStore('ttsStore', {
    state: ():State => ({
        active: false,
        id: undefined,
        play: false
    }),
    actions: {
        setTtsStore(payload: Partial<State>) {
            this.active = payload.active
            this.id = payload.id
            this.play = payload.play
        }
    }
})
