import axios from "axios";
import { defineStore } from "pinia";

interface State {
    inBreak: boolean
}


export const useBreakTime = defineStore('breakTime', {
    state: (): State => ({
        inBreak: false
    }),

    actions: {
        async checkBreakTime(){
            this.inBreak = await axios.get('/check_break_time').then(res => res.data)
        }
    }
})