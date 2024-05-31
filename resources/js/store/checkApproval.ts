import { defineStore } from "pinia";

interface State { 
    approved: boolean
}

export const useCheckApproval = defineStore('checkApproval', {
    state: (): State => ({
        approved: false
    }),

    actions: {
        setCheckApproval(payload: boolean) {
            this.approved = payload
        }
    }
})