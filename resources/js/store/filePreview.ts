import { defineStore } from 'pinia'
interface State {
  active: boolean
  files: any
  source: string | null
  source_board_id: number | null
  index: number | 0
  message: any
}

export const useFilePreview = defineStore('filePreview', {
  state: (): State => ({
    active: false,
    files: null,
    source: null,
    source_board_id: null,
    index: 0,
    message: null
  }),
  actions: {
    setFilePreview(payload: any){
        this.active = payload.active
        this.files = payload.files
        this.source = payload.source
        this.source_board_id = payload.source_board_id
        this.index = payload.index
        this.message = payload.message
    }
  }
})

