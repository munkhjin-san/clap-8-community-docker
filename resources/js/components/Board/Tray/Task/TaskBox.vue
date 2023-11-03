<template>
    <div :class="boxClass">
        <!-- <div style="margin-top:10px;margin-bottom:10px;">
            <div :style="{fontSize: '16px', color: dateColor}">{{yearMonthDetail(item.end_at)}}</div>                         
        </div> -->
        <div style="display:flex; justify-content:space-between;">
            
            <div :id="'task_box_' + item.id" class="task-box-inner" :style="{backgroundColor: taskColor.mycolor, color: taskColor.color, position: 'relative', cursor: 'pointer'}" @dblclick.prevent="$emit('editTask', item)">
                

                <div class="task-box-header" :style="{display: 'flex', width: '100%', position: 'relative', marginTop: $store.state.mobile ? '0' : '5px'}">
                    <div @click.stop="$store.commit('setMenu', {name: 'taskUsers', id: item.id})" style="display:flex;width: fit-content;">
                        <div v-for="user in taskUsers.slice(0, 3)" style="position:relative;">
                            <div v-if="user" :title="user.name" class="column-01">
                                <UserIcon size="30" :user="user" imgClass="u_icon_15"/>                            
                            </div>
                            <div class="column-01" v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="u_icon_15">
                                    <circle cx="15" cy="15" r="15" fill="#ddd"/>
                                </svg>
                            </div>
                            <div :title="$t('taskComplete')" v-if="user.pivot.comp_flag == 1"  class="completed-badge" style="">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="5px" viewBox="0 0 38 32" style="fill:#fff;margin:auto;">
                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                </svg>                                           
                            </div>                                   
                        </div>                                                                                       
                        <p style="margin-top:2px;cursor:pointer;font-size: 12px;margin-left: 3px;" v-if="taskUsers && taskUsers.length > 3">({{taskUsers.length}})</p>                                            
                    </div> 
                    <Transition name="modalFade"> 
                    <div v-if="$store.state.menu.name == 'taskUsers' && $store.state.menu.id == item.id" id="taskUsers" class="taskUsersList" style="left: 0;top:25px;right:auto;">
                        <div @click.stop="pushInstantUser(user.id)" class="mentionBox-inner" style="align-items: center;" v-for="user in taskUsers">                                                
                            <div class="column-01">  
                                <UserIcon size="25" :user="user" imgClass="userMidIcon"/>                                   
                            </div>                        
                            <p class="cursor-pointer" style="margin: auto 0px auto 5px;font-size:13px;white-space: nowrap;">{{ user ? user.name : $t('unAvailableUserName')}}</p>
                                                                                    
                            <div :title="$t('taskComplete')" v-if="user.pivot.comp_flag == 1" :style="{backgroundColor : user.late_answer != 0 ? '#ffa500' : 'rgb(100, 188, 68)'}" style="width: 15px;height: 15px;display: flex;border-radius: 50%;margin:auto 3px;min-width:15px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill:#fff;margin:auto;">
                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                </svg>                                           
                            </div>
                            <p style="font-size:10px;word-break:break-all;" v-if="user.pivot.comp_flag == 1">
                                {{ lateAnswer(user.pivot.late_answer,user.pivot.late_answer_custom ) }} 
                            </p>
                        </div>
                    </div>
                    </Transition>  
                    <div v-if="canModify && inTrash == 0" @click.stop="$store.commit('setMenu', {name: 'taskBMenu', id: item.id})" class="taskMenuWrap cursor-pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 7 32" class="dot-menu" style="width: -webkit-fill-available;">
                            <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                            <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                            <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                        </svg>
                    </div>
                    <Transition name="modalFade"> 
                        <div id="taskBMenu" class="boxMenuComment cursor-pointer" v-if="$store.state.menu.name == 'taskBMenu' && $store.state.menu.id == item.id" style="z-index:2;top: 20px;right: 15px;box-shadow:none;background-color: unset;">                  
                            <ul class="messageMenuList">
                                <li @click="$emit('editTask', item), $store.commit('setMenu', {name: '', id: null})" class="boxMenuItems cursor-pointer">{{$t('editTask')}}</li>
                                <li @click="deleteTaskConfirm(), $store.commit('setMenu', {name: '', id: null})" class="boxMenuItems cursor-pointer">{{$t('deleteTask')}}</li>                          
                            </ul>                                                 
                        </div>
                    </Transition>

                </div>

                
                
                <div v-if="item.title" style="margin-top: 10px;width: 100%;">
                    <p style="line-height: 1.5;word-break: break-word;white-space: break-spaces;">{{ item.title }}</p>
                </div>
                <div style="margin-top:10px;line-height: 1.5;word-break: break-word;white-space: break-spaces;" v-html="urlCheck(item.remarks)"></div>

                <div v-if="item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{detailsDateText(item.end_at)}}</div>                         
                </div>
                <div v-if="completeButtonFilter" style="display:flex;align-items: center;margin-top: 15px;position:relative;white-space: nowrap;flex-wrap: wrap;gap: 10px 0;">
                    <button v-if="isTask" class="shift-button" style="margin-right: 7px;" @dblclick.stop @click="$emit('completeTaskBefore', item)" >{{ completeFlag ? $t('inComplete') : $t('finish') }}</button>
                    <button v-if="inTrash == 0" @dblclick.stop @click="$emit('editTask', item)" class="shift-button" style="margin-right: 7px;">{{$t('edit')}}</button>
                    <button v-if="isExpired && isTask" @dblclick.stop class="shift-button" @click="untilTomorrow">{{$t('finishToday')}}</button>
                </div>
            </div>
        </div>
    </div> 

</template>

<script>
    import moment from 'moment';
    import Autolinker from 'autolinker';
    import { nextTick } from 'vue'
    // import NotifyComponent from "../../NotifyComponent.vue";
    export default {
        props: ['item', 'taskUserViewFlag', 'taskUserViewId', 'boxClass', 'tooManyTask', 'myColor', 'reminder', 'inTrash'],
        mounted(){
            if(this.$store.state.urlTaskId == this.item.id){
                nextTick(() => {                  
                    var elem = document.getElementById('task_box_' + this.item.id);                    
                    elem.scrollIntoView({block: 'center' });   
                    setTimeout(() => {
                        elem.classList.add("reached");
                        setTimeout(() => {
                            elem.classList.remove("reached");                    
                        }, 5000);  
                        this.$store.commit('setUrlTaskId', null)              
                    }, 150);                  
                });
                if(this.$store.state.urlTaskEditFlag){             
                    this.$emit('editTask', this.item)      
                }
                if(this.item.user_id == this.$store.state.user.id){
                    this.item.color = "#F7D5D5"
                }
            } 
           
        },
        computed:{
            completeFlag(){
                if(this.item.comp_flag == 1){
                    return true
                }else{
                    const me = this.item.to_users.find(ob => ob.id == this.$store.state.user.id)
                    if(me && me.comp_flag == 1){
                        return true
                    }else{
                        return false
                    }
                }
            },
            isTask(){
                return this.item.end_at ? true : false
            },
            taskColor(){
                const userIds = this.taskUsers.map(ob => ob.id)
                const me = userIds.filter(ob => ob == this.$store.state.user.id)
                const colors = {
                    mycolor: me && me.length ? this.myColor : (this.$store.state.mobile ? "var(--message-background)" : "var(--task-background)"),
                    color: me && me.length ? "#000" : "var(--primary-color)"
                }
                return colors
            },
            completeButtonFilter(){            
                var userData = this.taskUsers.filter(obj => obj.id == this.$store.state.user.id);
                if(userData.length) return true;
                else return false;             
            },
            taskUsers(){
                return this.item.to_users ? this.item.to_users : []
            },
            canModify(){
                const users = this.taskUsers.map(ob => ob.id);
                const me = users.filter(ob => ob == this.$store.state.user.id)
                return me && me.length
            },
            dateColor(){
                const now = moment().format('YYYY-MM-DD')
                const date_end = moment(this.item.end_at).format('YYYY-MM-DD')

                return now > date_end ? 'tomato' : '#89898F'
            },
            userIconSize(){
                var width = window.innerWidth
                || document.documentElement.clientWidth
                || document.body.clientWidth;
                if(width > 959){
                    return 'u_icon_15'
                }else{
                    return 'u_icon_20'
                }
            },
            isExpired(){
                const taskIncomplete = this.item.comp_flag
                const me = this.taskUsers.filter( ob => ob.id == this.$store.state.user.id)
                const taskIncompleteforMe = me.length ? me[0].pivot.comp_flag : false
                const expired = moment(this.item.end_at).format('YYYY-MM-DD') <= moment().format('YYYY-MM-DD')

                return !taskIncomplete && !taskIncompleteforMe && expired
            },
            isToday(){
                const taskIncomplete = this.item.comp_flag
                const me = this.taskUsers.filter( ob => ob.id == this.$store.state.user.id)
                const taskIncompleteforMe = me.length ? me[0].pivot.comp_flag : false
                const expired = this.item.end_at <= moment().format('YYYY-MM-DD HH:mm:ss')

                return !taskIncomplete && !taskIncompleteforMe && expired
            }
        },
        watch:{
            tooManyTask: {
                immediate: true,
                handler(newValue, oldValue) {
                    if(newValue != oldValue){
                        this.scrollSmooth(newValue)
                    }
                }
            }
        },
        methods: {
            scrollSmooth(day){
                setTimeout(() => {
                    const monthDay = this.yearMonthDetail(day)
                    const taskday = document.getElementById(monthDay)
                    if(taskday){
                        taskday.scrollIntoView({ behavior: 'instant' });
                    }
                })
                
            },
            untilTomorrow(){
                const today = moment().startOf('day').add(1, 'days').format('YYYY-MM-DD HH:mm:ss');
                this.updateDate(this.item.id, today)
            },
            updateDate(id, date){
                axios.post('/task_update_api', {task_id: id, date: date}).then(response => {
                    this.$emit('taskDeleted')
                });
            },
            deleteTaskConfirm(){
                var uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: this.$t('deleteTaskConfirm'),
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                    channel: uniqueChannell
                })                      
                emitter.on(uniqueChannell, (data) => { data.answer == this.$t('confirmToAction') ? this.deleteTask() : false }); 
            },
            deleteTask(){
                axios.post('/task_delete_api', {task_id: this.item.id}).then(response => {
                    this.$emit('taskDeleted')
                });
            },
            detailsDateText(value) {
                moment.locale('ja');
                const thisYear = moment().year();
                const taskYear = moment(value).year();
                const hasTime = moment(value).format('HH:mm:ss') !== '00:00:00' && moment(value).format('HH:mm:ss') !== '12:00:00'            
                return (thisYear == taskYear) ? hasTime ? moment(value).format('M/D (dd) HH:mm') : moment(value).format('M/D (dd)')  :              
                hasTime ? moment(value).format('YYYY/M/D (dd) HH:mm') : moment(value).format('YYYY/M/D (dd)')               
            },
            yearMonthDetail(value){
                moment.locale(this.$store.state.local);           
                return moment(value).format('YYYY-MM-DD')             
            },
            weekDetail(value){
                return  moment(value).format('ddd')
            },
            dayDetail(value){            
                return  moment(value).format('D')
            }, 
            pushInstantUser(id){
                if(id == this.$store.state.user.id) return
                const cX = event.clientX;
                const cY = event.clientY;  
                const data = {
                    id: id,
                    cX: cX,
                    cY: cY
                }
                this.$store.commit('setInstantUser', data)   
                this.$store.commit('setMenu', {name: 'instantProfileWindow', id: 5000})                 
            },
            urlCheck: function (text) {
                if(text){                
                    var linkedText = Autolinker.link(text, {stripPrefix: false});              
                    return linkedText;                
                }            
            },
            lateAnswer: function(value,lateAnswerCustom){
                if(value == 1){
                    return 'タスク対応に時間がかかった。';
                }else if(value == 2){
                    return 'タスクの優先順位を変更した。';
                }else if(value == 3){
                    return '完了ボタンを押し忘れていた。';
                }else if(value == 4){
                    return 'タスクを認識していなかった。';
                }else if(value == 5){
                    return 'このタスクの担当者ではない。';                    
                }else if(value == 6){
                    return lateAnswerCustom;
                }else{
                    return '';
                }
            }
        }   
    }
</script>
<style scoped lang='scss'>
    .w-100{
        width: 100% !important;
    }
    .w-auto{
        width: auto !important;
    }
</style>
