import { defineStore } from 'pinia'
interface State {
  active: boolean
  userList: {
    id: number;
    title: string;
    icon_id: number;
  }[];
  title: string | null
}

export const useMessageUsers = defineStore('messageUsers', {
  state: (): State => ({
    active: false,
    title: null,
    userList: [],
  }),
  actions: {
    setMessageUsers(payload: any){
        this.active = payload.active
        this.title = payload.title
        this.userList = payload.userList
    }
  }
})

