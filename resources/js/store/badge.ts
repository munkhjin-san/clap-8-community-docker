import { defineStore } from "pinia";
import { useAuthUserStore } from "./auth";
import axios from "axios";
interface State {
    board: any[]
    post: number
    task: number[]
    notice: number
    project: any
}

export const useBadgeStore = defineStore('badge', {
    state: (): State => ({
        board: [],
        post: 0,
        task: [],
        notice: 0,
        project: [],
    }),
    actions: {
        setTaskBadge(payload: number[]){
            this.task = payload
        },
        async getPostBadge(){
            const auth = useAuthUserStore()  
            if(!auth.isPartner && !auth.isRegistered){
                const response = await axios.get('/post_badge')
                this.post = response.data         
            }   
        },
        async updatePostBadge(which:string){
            const response = await axios.patch('/post_badge', {which: which})
            this.post = response.data           
        },
        async getNoticeBadge(){
            const auth = useAuthUserStore()  
            if(!auth.isPartner && !auth.isRegistered){
                const response = await axios.get('/notice_badge')
                this.notice = response.data   
            }
        },
        async getBoardBadge() {
            const data = await axios.get('/board_badge').then(response => response.data)       
            this.board = data        
        },
        async updateBoardBadge(id:number) {
            const data = await axios.patch('/board_badge', {board_id: id}).then(response => response.data)   
            this.board = data                    
        },
        async getTaskBadge(){
            const data = await axios.get('/task_badge').then(response => response.data)       
            this.task = data    
        },
        async getProjectBadge(){
            const data = await axios.get('/project_badge').then(response => response.data)
            this.project = data
        }
    },
    getters: {
        activeUsersBoardBadge(){
            const auth = useAuthUserStore()
            const activeUser = auth.activeUser.id
            return this.board.find(ob => ob.user_id == activeUser)?.list
        },
        totalBoardBadge: (state) => {        
            return (userId: number) => {
                const filtered = state.board.find((data) => data.user_id === userId)
                if(filtered){
                    const list = filtered.list
                    let value = 0
                    for(var i in list) {
                        value = value + list[i];
                    }
                    return value
                }
                return 0
            }
        },
        totalUserBadge: (state) => {   
            const auth = useAuthUserStore()     
            return (userId: number) => {
                let value = 0
                const filtered = state.board.find((data) => data.user_id === userId)
                if(filtered){
                    const list = filtered.list                    
                    for(var i in list) {
                        value = value + list[i];
                    }                    
                }
                if(auth.id == userId){
                    const postBadge = state.post
                    value = value + postBadge
                }
                return value
            }
        },
        sumOfAll ()  {
            const auth = useAuthUserStore()
            let sum = 0            
            this.board.forEach((p: { list: { [x: string]: number; }; }) => {                
                for(var i in p.list) {                    
                    sum = sum + p.list[i];
                }
            });
            const projectBadge = this.project.total_sum;  
            const postBadge = auth.activeUser?.linkable || auth.user?.linkable ? 0 : this.post; 
            sum = sum + postBadge + projectBadge
            return sum
        }
    }
})