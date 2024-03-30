import { defineStore } from 'pinia'
interface State {
  mobile: boolean
}

export const useResponsive = defineStore('responsive', {
  state: (): State => ({
    mobile: window.innerWidth < 959
  }),
  actions: {
    setMobile(payload: boolean){
        this.mobile = payload
    }
  }
})