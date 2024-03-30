import { defineStore } from 'pinia'
interface State {
  dark: boolean
}

export const useTheme = defineStore('theme', {
  state: (): State => ({
    dark: false
  }),
  actions: {
    setDark(payload: boolean){
        this.dark = payload
    }
  }
})