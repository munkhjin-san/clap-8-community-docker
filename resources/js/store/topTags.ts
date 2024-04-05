import { defineStore } from 'pinia'
import axios from "axios";
interface State {
  tags: number[],
  appName: String | '',
  expanded: boolean
}

export const useTopTags = defineStore('tags', {
  state: (): State => ({
    tags: [],
    appName: '',
    expanded: false
  }),
  actions: {
    async getTags(payload: any){
        if(payload.reset){
            this.expanded = false
        }
        this.appName = payload.appName        
        const data = await axios.get(`/get_top_tags?app_name=${payload.appName}`).then( response => response.data)
        this.tags = data
    },
    setExpanded(){
        this.expanded = !this.expanded
    }
  }
})