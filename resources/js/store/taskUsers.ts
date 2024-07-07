import { defineStore } from 'pinia'
interface State {
  active: boolean
  userList: {
    id: number;
    title: string;
    icon_id: number;
  }[];
  title: string | null;
  task: {
    id: number;
    approver_id: number;
    end_at: string | Date;
  }
}

export const useTaskUsers = defineStore('taskUsers', {
  state: (): State => ({
    active: false,
    title: null,
    userList: [],
    task: {
        id: 0,
        approver_id: 0,
        end_at: ''
    },
  }),
  actions: {
    setTaskUsers(payload: any){
        this.active = payload.active
        this.title = payload.title
        this.userList = payload.userList
        this.task = payload.task
    }
  }
})

