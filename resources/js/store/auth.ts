import axios from 'axios'
import { defineStore } from 'pinia'
interface State {
  name: string
  id: number | null
  user: User | null
  isPartner: boolean
  isRegistered: boolean
  isOnLeave: boolean
  linked: Array<subUser>
}
interface subUser {
    position_id: number
    id: number | null
    name: string| null
    icon_path: string | null
    linkable: boolean | false
    user_code: number
    icon_bg: string | null
}
interface User{
  id: number | null
  user_code: number | null
  weathers: Weather
  footer_view: boolean
  sign_path: string | null
  color: number | null
  ical_key: string | null
  icon_path: string | null
  position_id: number | null
  work_authority: number | null
  linkable: boolean | false
  linked: User[]
  icon_bg: string | null
  partner_flag?: number | null
  project_settings?: {project_id: number, color: string | null}[]
}
interface Weather{
  value_int: number | null
}
export const useAuthUserStore = defineStore('authUser', {
  state: (): State => ({
    name: '',
    id: null,
    user: null,
    isPartner: false,
    isRegistered: false,
    isOnLeave: false,
    linked: [],
  }),
  actions: {
    setUser(payload: any){
        this.id = payload.id
        this.name = payload.name
        this.user = payload
        this.isPartner = payload.partner_flag == 1
        this.isRegistered = payload.position_id == 15
        this.isOnLeave = payload.on_leave
        this.linked = payload?.linked
    },
    setFooterView(payload: boolean){
        this.user.footer_view = payload
    },
    async setActiveUser(payload:number){
        const response = await axios.patch('/set_active_linked_account', {id: payload}).then(res => res.data)
        this.setUser(response?.user)
    }    
  },
  getters:{
    activeUser():subUser{
        if(this.user?.linked){
            const active = this.user.linked.find((ob: { pivot: { active: number } }) => ob.pivot.active)
            return active ? active : this.user
        }
        return this.user        
    },
    hasPrivilage():boolean{
      return this.activeUser?.position_id <= 6 || this.activeUser.id === 610 || this.activeUser.id === 608
    }
  }
})
