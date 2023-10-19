<template>
    <div class="admin-workgroup-outwrapper">
        <div class="admin-workgroup-wrapper">
            <table class="admin-workgroup-innerwrapper">
                <tr>
                    <th>ワークグループ名</th>
                    <th>ワークグループユーザー</th>
                    <th></th>
                </tr>
                <tr :key="item.id" v-for="item in workgroupusers">
                    <td>{{ item.name }}
                    </td>
                    <td :group="'columnGroup'">
                          <draggable :list="item.work_group_user" @end="handleDragEnd" item-key="name" :move="checkMove" group="columnGroup" tag="tbody">
                            <template #item="{ element }">
                                <div class="list-group-item">
                                  {{ element.user.name }}
                                </div>
                              </template>
                        </draggable>
                    </td>
                    <td><button type="submit" @click="workGroupEdit(item)" class="account-btn">
                        編集
                    </button></td>
                </tr>
            </table>
        </div>       
            <div :style="{bottom : $store.state.mobile ? '70px' : '20px', position:'fixed'}" class="createBoardButton fileNewButton" @click="newWorkGroup()">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div>
            <Transition name="modalFade">
                <div class="overlay" v-if="showModalContent"> 
                    <WorkGroupCreate
                        :editFlag="editFlag"
                        :editWorkGroupData="editWorkGroupData"
                        :userList="userList"
                        @postFinish="postFinish"
                        @closeModal="showModalContent = false"
                    />
                </div>
            </Transition>           
        </div>
</template>
<script>
import draggable from 'vuedraggable'
import WorkGroupCreate from './WorkGroupCreate.vue';
    export default{
        props: ['workgroupusers', 'userList'],
        components: {
            draggable,
            WorkGroupCreate
        },
        data(){
            return{
                showModalContent: false,
                editWorkGroupData: null,
                editFlag: false,
                disableDrag: false,
                deleteGroup: false
            }
        },
        methods: {
            postFinish() {
                this.showModalContent = false;
                this.editWorkGroupData = null;
                this.$emit('getUsers')
            },
            newWorkGroup(){
                this.showModalContent = true;
                this.editFlag = false
            },
            workGroupEdit(item){
                this.editWorkGroupData = item;
                this.showModalContent = true;
                this.editFlag = true;
            },
            handleDragEnd(evt){
                if(this.disableDrag){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: 'このメンバーは既にグループに登録されています。',
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK']
                    })   
                    return;
                }
                let sorted = []
                for(const item of this.workgroupusers){
                    for(const value of item.work_group_user){
                        if(item.id != value.record_id){
                            value.record_id = item.id
                        }
                    }
                    sorted.push(item.work_group_user)
                }
                axios.post('/work_group_sort', { new_list : sorted }).then(response => {
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: '移動完了しました。',
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK']
                    })  
                    this.$emit('getUsers')
                }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                    this.processing = false                      
                }.bind(this));
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
            checkMove(evt){
                const drop = evt.relatedContext.list
                const drag = evt.draggedContext.element.user_id
                for(const child of drop){
                    if(child.user_id == drag){                
                        this.disableDrag = true
                        return false
                    }else{
                        this.disableDrag = false
                    }
                }
            },
        },
        computed: {
            
        },
       
    }
</script>
<style scoped lang="scss">
    .list-group-item{
        border: solid thin var(--formBorder);
        padding: 5px;
    }
    .admin-workgroup-outwrapper{
        height: 100%;        
        width: 100%;
        overflow: hidden;
    }
    .account-btn{
        background: var(--primary-button);
        color: #fff;
        font-size: 12px;
        white-space: nowrap;
        width: -moz-fit-content;
        width: fit-content;
        margin: auto;
        position: relative;
        min-width: auto;
        min-height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0 15px;
        flex: 1 0 auto;
      }
    .admin-workgroup-wrapper{
        height: calc(100% - 60px);
        overflow: hidden auto;
    }
    table{
        border: solid thin var(--formBorder);
        border-collapse:collapse;
        width: -webkit-fill-available;
        margin-left:15px;
        background-color: var(--background-color);
    } 
    th {
        padding:5px;
        border: solid thin var(--formBorder);
        font-size: 16px;
        font-weight: normal;
      }
      td {
        padding:5px;
        border: solid thin var(--formBorder);
        font-size: 14px;
      }
      tbody{
        display: flex;
        flex-wrap: wrap;
        min-height:27px;
        height:auto;
        cursor: pointer;
      }
</style>