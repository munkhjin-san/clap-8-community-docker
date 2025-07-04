import { defineStore } from 'pinia'
import axios from "axios";
import { Tag } from '@/interface/globalInterface';
interface State {
  tags: Tag[],
  appName: String | '',
  expanded: boolean,
  currentTag: null,
}

export const useTopTags = defineStore('tags', {
  state: (): State => ({
    tags: [],
    appName: '',
    expanded: false,
    currentTag: null,
  }),
  actions: {
    async getTags(payload: any){
        if(payload.reset){
            this.expanded = false
        }
        this.appName = payload.appName
        const params = {
          app_name: payload.appName,
          current_tag: payload.currentTag || ''
        }        
        const data = await axios.get('/get_top_tags', { params }).then( response => response.data)
        this.tags = data
    },
    setExpanded(){
        this.expanded = !this.expanded
    }
  }
})