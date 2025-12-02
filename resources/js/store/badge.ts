import { defineStore } from "pinia";
import { useAuthUserStore } from "./auth";
import axios from "axios";
interface State {
    board: any[]
    post: number
    task: number[]
    notice: number
    remind: any,
    members_goals: any[]
    managers_goals: any[]
    salary_issue: any[]
    asset: []
    task_comment: {project_id: number, task_id: number, comments: number}[],
    finance_comment: {total_unread: number, projects: {project_id: number, total_unread: number, period_counts: {[period: string]: number}}[]},
    goal_issue_comment: [],
    contact_comment: [],
    boardBadgeFetchedAt: number | null,
    boardBadgeRequest: Promise<void> | null,
}
const BOARD_BADGE_CACHE_MS = 2000;

export const useBadgeStore = defineStore('badge', {
    state: (): State => ({
        board: [],
        post: 0,
        task: [],
        notice: 0,
        remind: {},
        members_goals: [],
        managers_goals: [],
        salary_issue: [],
        asset: [],
        task_comment: [],
        finance_comment: {total_unread: 0, projects: []},
        goal_issue_comment: [],
        contact_comment: [],
        boardBadgeFetchedAt: null,
        boardBadgeRequest: null

    }),
    actions: {
        setTaskBadge(payload: number[]){
            this.task = payload
        },
        async getGoalIssueCommentBadge(){
            const data = await axios.get('/goal_issue_comment_badge').then(response => response.data)
            this.goal_issue_comment = data
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
        async getBoardBadge(force = false) {
            const now = Date.now()
            if(!force && this.boardBadgeFetchedAt && (now - this.boardBadgeFetchedAt) < BOARD_BADGE_CACHE_MS){
                return this.boardBadgeRequest ?? Promise.resolve()
            }
            if(!this.boardBadgeRequest){
                this.boardBadgeRequest = axios.get('/board_badge').then(response => response.data).then(data => {
                    this.board = data
                    this.boardBadgeFetchedAt = Date.now()
                }).finally(() => {
                    this.boardBadgeRequest = null
                })
            }
            return this.boardBadgeRequest
        },
        async updateBoardBadge(id:number) {
            const data = await axios.patch('/board_badge', {board_id: id}).then(response => response.data)   
            this.board = data
            this.boardBadgeFetchedAt = Date.now()                    
        },
        async getTaskBadge(){
            const data = await axios.get('/task_badge').then(response => response.data)       
            this.task = data    
        },
        async getRemindBadge() {
            const data = await axios.get('/remind_badge').then(response => response.data)
            this.remind = data
        },
        async getMembersGoalsBadge(){
            const data = await axios.get('/get_members_goals_badge').then(response => response.data)
            this.members_goals = data
        },
        async getManagersGoalsBadge(){
            const data = await axios.get('/get_managers_goals_badge').then(response => response.data)
            this.managers_goals = data
        },
        async getSalaryIssueBadge(){
            const data = await axios.get('/get_salary_issue_badge').then(response => response.data)
            this.salary_issue = data
        },
        async getAssetBadge(){
            const data = await axios.get('/get_asset_badge').then(response => response.data)
            this.asset = data
        },
        async getTaskCommentBadge(){
            const data = await axios.get('/get_task_comment_badge').then(response => response.data)
            this.task_comment = data
        },
        async getFinanceCommentBadge(){
            const data = await axios.get('/projects/finance/unread-badges').then(response => response.data)
            this.finance_comment = data
        },
        async clearGoalIssue({column, value}: {column: string, value: any}){
            const response = await axios.post('/clear_goal_issue_badge', {column: column, value: value})
            this.goal_issue_comment = response.data
        },
        async getContactCommentBadge(){
            const data = await axios.get('/get_contact_comment_badge').then(response => response.data)
            this.contact_comment = data
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
        sumOfAll(){
            const auth = useAuthUserStore()
            let sum = 0            
            this.board.forEach((p: { list: { [x: string]: number; }; }) => {                
                for(var i in p.list) {                    
                    sum = sum + p.list[i];
                }
            });
            const projectBadge = this.projectTotal + this.projectCommentTotal;
            const remindBadge = this.remind.total
            const postBadge = auth.activeUser?.linkable || auth.user?.linkable ? 0 : this.post; 
            sum = sum + postBadge + projectBadge + remindBadge
            return sum
        },
        goalsBadge(){
            return [...this.managers_goals, ...this.members_goals]
        },
        salaryIssueBadge(){
            return this.salary_issue
        },

        goalsBadgeByFilter(state){
            return (filterData: {by: string, value: any}[]) => {
                const userGoals = [...state.managers_goals, ...state.members_goals]
                return userGoals.filter((goal) => {
                    return filterData.every((filter) => goal[filter.by] === filter.value)
                })
            }
        },
        salaryIssueByFilter(state){
            return (filterData: {by: string, value: any}[]) => {
                const userIssues = state.salary_issue
                return userIssues.filter((issue) => {
                    return filterData.every((filter) => issue[filter.by] === filter.value)
                })
            }
        },
        taskCommentBadgeByFilter(state){
            return (filterData: {by: string, value: any}[]) => {
                const userComments = state.task_comment
                return userComments.filter((comment) => {
                    return filterData.every((filter) => comment[filter.by] === filter.value)
                })
            }
        },
        financeCommentBadgeByFilter(state) {
            return (filterData: { by: string; value: any }) =>
                state.finance_comment?.projects?.find(
                    (comment) => comment.project_id === filterData.value
                ) ?? null;
        },
        goalAndSalaryTotal(state){
            return state.managers_goals.length + state.members_goals.length + state.salary_issue.length
        },
        projectTotal(state){
            return this.goalAndSalaryTotal + state.asset.length
        },
        projectCommentTotal(state){
            return state.task_comment.length + state.finance_comment.total_unread + state.goal_issue_comment.length
        },
        assetsBadgeByFilter(state){
            return (filterData: {by: string, value: any}[]) => {
                const userAssets = state.asset
                return userAssets.filter((asset) => {
                    return filterData.every((filter) => asset[filter.by] === filter.value)
                })
            }
        },
        goalIssueCommentBadgeByFilter(state){
            return (filterData: {by: string, value: any}[]) => {
                const userComments = state.goal_issue_comment
                return userComments.filter((comment) => {
                    return filterData.every((filter) => comment[filter.by] === filter.value)
                })
            }
        },
        contactBadge(){
            return this.contact_comment
        }
        
    }
})
