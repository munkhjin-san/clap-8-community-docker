import { defineStore } from 'pinia'
interface State {
  name: string
  id: number | null
  user_id: number | null
  parent: string | null
}

export const useMenuStore = defineStore('menu', {
  state: (): State => ({
    name: '',
    id: null,
    user_id: null,
    parent: null
  }),
  actions: {
    setMenu(payload: any){
        this.id = payload.id
        this.name = payload.name
        this.user_id = payload?.user_id
        
        if(payload.parent){
            console.log(payload.parent)
            this.parent = payload.parent
        }
    },
    close(){
        this.id = null
        this.name = ''
        this.user_id = null
        this.parent = ''
    }
  }
})