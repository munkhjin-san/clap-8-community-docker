import { defineStore } from "pinia";
interface State {
    active: boolean;
    message_id: number | null;
}

export const useMessageSchedule = defineStore('messageSchedule', {
    state: (): State => ({
        active: false,
        message_id: null,
    }),
    actions: {
        setMessageSchedule(payload: State) {
            this.active = payload.active
            this.message_id = payload.message_id
        }
    }
})