import { defineStore } from 'pinia'
interface State {
  active: boolean
  userList: {
    id: number;
    icon_id: number;
    name: string;
  }[];
  title: string | null
}

export const useProjectUsers = defineStore('projectUsers', {
  state: (): State => ({
    active: false,
    title: null,
    userList: [],
  }),
  actions: {
    setProjectUsers(payload: any){
        this.active = payload.active
        this.title = payload.title
        this.userList = payload.userList
    }
  }
})

