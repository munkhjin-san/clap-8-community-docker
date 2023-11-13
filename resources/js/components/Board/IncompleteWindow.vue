<template>
    <Transition name="modalFade">
    <div v-if="incompleteShow && viewModal && !isEdit && !isJumpToMessage" class="overlay">
            
        <div style="display:flex;flex-direction:column;width:100%;height:100%; margin: 20px 0;overflow: hidden auto;">
            <div @click="$emit('closePopup')" class="modalCloseButton cursor-pointer">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" fill="#fff" style="margin: auto;">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>
            
            <div v-if="shiftNotSubmittedList.length" style="padding: 0 10px;">
                <div class="incompleted-title">勤怠予定が未提出です。対応をお願いします。</div>
                <masonry-wall :items="shiftNotSubmittedList" :column-width="350" :gap="30">
                    <template v-slot:default="{item}">
                        <WorkNotSubmitted 
                            v-if="item"
                            :item="item" 
                        />
                    </template>
                </masonry-wall>
            </div>
            <div v-if="timecardNotSubmittedList.length" style="padding: 0 10px;">
                <div class="incompleted-title">日報が未提出です。対応をお願いします。</div>
                <masonry-wall :items="timecardNotSubmittedList" :column-width="350" :gap="30">
                    <template v-slot:default="{item}">
                        <WorkNotSubmitted 
                            v-if="item"
                            :item="item" 
                        />
                    </template>
                </masonry-wall>
            </div>
            <div v-if="incompletedTasksList.length" style="padding: 0 10px;">
                <div class="incompleted-title">{{ $tc('expiredTaskWarning', incompletedTasksList.length, {number: incompletedTasksList.length})}}</div>

            <masonry-wall :items="incompletedTasksList" :column-width="350" :gap="30">
                <template v-slot:default="{item}">
                    <TaskBoxpreload 
                        boxClass="incompleted-task-box-container"
                        v-if="item"
                        :item="item" 
                        :taskUserViewId="taskUserViewId" 
                        :taskUserViewFlag="taskUserViewFlag"
                        :reminder="reminder" 
                        :myColor="myColor"
                        :inTrash="0"
                        @editTask="editTask" 
                        @taskUserViewToggle="taskUserViewToggle"
                        @completeTaskBefore="completeTaskBefore"
                        @taskDeleted="taskDeleted"
                    />
                </template>
            </masonry-wall>
            </div>
            <div v-if="uncheckedMessages.length" style="padding: 0 10px;">
                <div class="incompleted-title" style="">未確認メッセージが{{uncheckedMessages.length}}件あります。対応をお願いします。</div>
                <masonry-wall :items="uncheckedMessages" :column-width="350" :gap="30">
                    <template v-slot:default="{item}">
                        <UncheckedMessageItem 
                            boxClass="incompleted-task-box-container"
                            v-if="item"
                            :message="item"
                            :reminder="reminder"
                            @reload="() => reload()"
                        />
                    </template>
                </masonry-wall>
            </div>
            <div v-if="unsignedMessages.length" style="padding: 0 10px;">
                <div class="incompleted-title" style="">{{ $tc('signatureRequestWarning', unsignedMessages.length,  {number: unsignedMessages.length})}}</div>
                <masonry-wall :items="unsignedMessages" :column-width="350" :gap="30">
                    <template v-slot:default="{item}">
                        <UncheckedMessageItem 
                            boxClass="incompleted-task-box-container"
                            v-if="item"
                            :message="item"
                            :reminder="reminder"
                            @reload="() => getUnsignedMessages()"
                        />
                    </template>
                </masonry-wall>
            </div>
            <div v-if="remindMessages.length" style="padding: 0 10px;">
                <div class="incompleted-title" style="">リマインドメッセージが{{remindMessages.length}}件あります。対応をお願いします。</div>
                <masonry-wall :items="remindMessages" :column-width="350" :gap="30">
                    <template v-slot:default="{item}">
                        <UncheckedMessageItem 
                            boxClass="incompleted-task-box-container"
                            v-if="item"
                            :message="item"
                            @remindRequest="remindRequest"
                            @reload="() => reload()"
                        />
                    </template>
                </masonry-wall>
            </div>    

        
        </div>
        <!--<Transition name="modalFade">
            <IncompleteFeedBack 
                v-if="selectedComplete.status && selectedComplete.record" 
                :task="selectedComplete"
                @taskCompleted="taskCompleted"
                @closeMe="closeFeedBack"
            />
        </Transition>-->
    </div>
    </Transition>
</template>

<script>
import TaskBoxpreload from "./Tray/Task/TaskBox.vue"
import IncompleteFeedBack from "./IncompleteFeedBack.vue"
import colors from '../../../assets/colors.json'
import UncheckedMessageItem from "./Message/UncheckedMessageItem.vue"
import WorkNotSubmitted from "../Work/WorkNotSubmitted.vue"
import { ref, onMounted } from 'vue';
    export default {
        data(){
            return{
                incompletedTasksList: [],
                unsignedMessages: [],
                viewModal: true,
                taskUserViewId: null,
                taskUserViewFlag: false,
                selectedComplete: {
                    status: false,
                    record: null
                },
                reminder: 'reminder',
                avialableColors: colors,
                shiftNotSubmittedList: [],
                timecardNotSubmittedList: [],
                nextShiftSubmittedList: [],
                remindMessages: [],
                uncheckedMessages: []
            }
        },
        components:{
            IncompleteFeedBack,
            TaskBoxpreload,
            UncheckedMessageItem,
            WorkNotSubmitted
        },
        mounted() {
            this.getIncompletedTasks()
            this.getUnsignedMessages()
            this.getNotSubmitted()
            this.getRemindMessages()
            this.getUncheckedMessages()
            this.isJumpToMessage
            if(!this.incompleteShow){
                const currentTime = new Date().getTime();
                const user_id = this.$store.state.user.id
                localStorage.setItem('popupCloseTime_' + user_id, currentTime);
            }
        },
        watch:{
            // '$store.state.focused' (after, before) {
            //     if(after){
            //         this.getIncompletedTasks()
            //     }
                
            // },
            '$store.state.taskFeedBack.active' (after, before) {
                if(after == false){
                    this.getIncompletedTasks()
                }
                
            },
        },
        computed:{
            incompleteShow(){
                return this.incompletedTasksList.length || 
                       this.unsignedMessages.length || 
                       this.shiftNotSubmittedList.length || 
                       this.timecardNotSubmittedList.length || 
                       this.remindMessages.length || 
                       this.uncheckedMessages.length
            },
            isJumpToMessage(){
                const url_string = window.location.href;
                const url = new URL(url_string);
                const b_id = url.searchParams.get("jump_message");
                if(b_id){
                    return true
                }
                return false
            },
            isEdit(){
                const url_string = window.location.href;
                const url = new URL(url_string);
                const b_id = url.searchParams.get("task_edit");
                const action = url.searchParams.get("action");
                if(b_id || action){                
                    return true          
                }
                return false
            },
            myColor(){
                if(this.$store.state.user && this.avialableColors){
                    const color = this.avialableColors.filter(ob => ob.id == this.$store.state.user.color)
                    if(color.length){
                        return color[0].light
                    }
                    return ''
                }
                return ''
            },
        },
        methods: {
            remindRequest(data){
                axios.post('/remind_add', {
                        id: data.id
                }).then(response => {
                    if(response.data == true){
                        emitter.emit('setToast', {
                            active: true,  
                            type: 'info', 
                            content: 'リマインドしました。',
                            closeButton: false, 
                            autoClose: false,
                            answers: ['OK']
                        })
                    }else{
                        emitter.emit('setToast', {
                            active: true,  
                            type: 'info', 
                            content: 'リマインドを取り消しました。',
                            closeButton: false, 
                            autoClose: false,
                            answers: ['OK']
                        })
                    }
                    this.getRemindMessages()
                });
            },
            reload(){
                this.getUnsignedMessages()
                this.getRemindMessages()
                this.getUncheckedMessages()
            },
            taskCompleted(){
                this.getIncompletedTasks()
                this.resetSelectedComplete()

            },
            resetSelectedComplete(){
                const data = {
                    status : false,
                    record: null
                }
                this.selectedComplete = data
            },
            getIncompletedTasks(){
                axios.post('/get_incompleted_tasks').then(response => {  
                    this.incompletedTasksList = response.data
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
            },
            getUnsignedMessages(){
                axios.post('/get_unsigned_messages').then(response => {  
                    this.unsignedMessages = response.data.message_list
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
            },
            editTask(task){
                const url = '/board/' + task.board_id + '?t='+ task.id + '&task_edit=true'
                window.open(url, '_blank').focus();
            },
            getNotSubmitted(){
                axios.post('/not_submitted').then(response => {
                    this.shiftNotSubmittedList = response.data.shiftNotSubmittedList;
                    this.timecardNotSubmittedList = response.data.timecardNotSubmittedList;
                    this.nextShiftSubmittedList = response.data.nextShiftSubmittedList;
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
            },
            getUncheckedMessages(){
                axios.post('/get_unchecked_messages').then(response => {  
                    this.uncheckedMessages = response.data
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
            },
            getRemindMessages(){
                axios.post('/get_remind_messages').then(response => {  
                    this.remindMessages = response.data                   
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
            },
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['OK']
                })                
            },
            taskUserViewToggle(){

            },
            completeTaskBefore(task){
                // this.selectedComplete.record = task
                // this.selectedComplete.status = true
                const data = {
                    active: true,
                    data : task
                }
                this.$store.commit('setTaskFeedback', data)
            },
            taskDeleted(){
                this.getIncompletedTasks()
            },
            closeTaskModal(){

            }
        }
    }
</script>
<style lang="scss" scoped>
.incompleted-title{
    font-size:14px;
    color:#fff;
    margin: 20px auto 0;
    text-align: center;
}


</style>

