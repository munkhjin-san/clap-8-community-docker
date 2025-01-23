import { defineStore } from 'pinia'
interface State {
  active: boolean
  users: {
    id: number;
    name: string;
    icon_path: string;
    icon_bg: string | null;
    is_answered?: boolean;
    pivot: any;
  }[];
  title: string | null;
}

export const useSurveyUsers = defineStore('surveyUsers', {
  state: (): State => ({
    active: false,
    title: null,
    users: [],
  }),
  actions: {
    setSurveyUsers(payload: any){
        this.active = payload.active
        this.title = payload.title
        this.users = payload.users
    },
    close(){
        this.active = false
        this.title = null
        this.users = []
    }
  }
})

