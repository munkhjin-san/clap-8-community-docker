import { defineStore } from 'pinia'
interface State {
    active: boolean
    title: string
    text: string
    files: any
    from: string
    to: string
    drag: boolean
    instruction: string
    message: any | null
}

export const useSharingDataStore = defineStore('sharingData', {
  state: (): State => ({
    active: false,
    title: '',
    text: '',
    files: [],
    from: '',
    to: '',
    drag: false,
    instruction: '',
    message: null
  }),
  actions: {
    setSharingData(payload: any){
        this.active = payload.active
        this.title = payload.title        
        this.text = payload.text        
        this.files = payload.files        
        this.from = payload.from        
        this.to = payload.to        
        this.drag = payload.drag        
        this.instruction = payload.instruction     
        this.message = payload?.message 
    }
  }
})