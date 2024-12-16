import { defineStore } from 'pinia'
interface State {
  active: boolean
  userList: {
    id: number;
    title: string;
    icon_id: number;
  }[];
  title: string | null
  isTask: boolean
}

export const useMessageUsers = defineStore('messageUsers', {
  state: (): State => ({
    active: false,
    title: null,
    userList: [],
    isTask: false
  }),
  actions: {
    setMessageUsers(payload: any){
        this.active = payload.active
        this.title = payload.title
        this.userList = payload.userList
        this.isTask = payload.isTask
    }
  }
})

