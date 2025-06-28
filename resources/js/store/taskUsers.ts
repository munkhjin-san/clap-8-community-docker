import { Task, TaskUser } from '@/interface/globalInterface';
import { defineStore } from 'pinia'
interface State {
  active: boolean
  userList: TaskUser[];
  title: string | null;
  task: Task | null
  siblings: Task[]
}

export const useTaskUsers = defineStore('taskUsers', {
  state: (): State => ({
    active: false,
    title: null,
    userList: [],
    task: null,
    siblings: []
  }),
  actions: {
    setTaskUsers(payload: any){
        this.active = payload.active
        this.title = payload.title
        this.userList = payload.userList
        this.task = payload.task
        if(payload?.siblings){
            this.siblings = payload.siblings
        }
    }
  }
})

