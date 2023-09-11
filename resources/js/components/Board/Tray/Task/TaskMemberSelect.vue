<template>
    <div class="overlay" @mousedown="$emit('closeMe')" style="z-index:20">            
        <div id="modalContent04" @mousedown.stop> 
            <div style="width:100%;display:flex;">
                <p style="padding-bottom: 20px;">{{$t('chooseMember')}}</p>
                <svg style="margin:0 0 0 auto;cursor:pointer" @click="$emit('closeMe')" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>                        
            <span style="position: initial;padding-bottom:10px;display:block;font-size:12px;">{{$t('taskSelectMember')}}</span>
            <div id="checkUserSelecter" style="border: 1px solid #ccc; max-height: 60vh; overflow-y: auto;">
                
                <div>
                    <div :key="user.id" v-for="user in usersWithTask" style="padding:0 15px;display:flex;">                                
                        <label class="check-container" style="align-self: center;">
                            <input :id="'memberSelector_' + user.user.id" :value="user.user.id" :checked="checkedMemberIds && Object.values(checkedMemberIds).includes(user.user.id.toString()) ? true : false" name="memberCheckBox" type="checkbox">
                            <span class="checkmark-mini"></span>
                        </label>  
                        <div @click="checkmMemberSelect(user.user.id)" class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;">
                            <UserIconPreLoad size="30" :title="user.user.name" :user="user.user" imgClass="userNormalIcon"/>                      
                            <div style="align-self: center;padding:0 10px;">
                                <p style="line-height: 1.3;font-size: 16px;">{{user.user.name}}</p>                                          
                            </div>                                      
                        </div>
                    </div>                             
                </div>                             
            </div>
            <div v-if="usersWithTask.length" @click="checkRequest()" style="margin: 20px auto auto auto;width:auto;" class="groupEditButton cursor-pointer">{{$t('select')}}</div>
            <div v-else style="margin-top:20px; font-size: 14px;">
                <p>{{$t('currentlyNoUser')}}</p>
            </div>     
        </div> 
    </div>
</template>

<script>
// import NotifyComponent from "../../NotifyComponent.vue";
import UserIconPreLoad from '../../Mixed/UserIcon.vue'
    export default {
        props: ['taskList', 'checkedMemberIds'],
        mounted() {
        },
        components:{
            UserIconPreLoad,
        },
        computed: {
            usersWithTask(){
                const users = this.$store.state.signAbleUsers.filter(user => {
                    return this.taskList.some(task => task.task_users.some(taskUser => taskUser.user_id === user.user_id));
                });
                
                return users
            }
        },
        methods:{
            // selectAllMembers(){     
            //     var which = document.getElementById('allMemberSelector').checked       
            //     const checkboxes = document.querySelectorAll('input[name="memberCheckBox"]');
            //     if(which){
            //         checkboxes.forEach((checkbox) => {
            //             checkbox.checked = true
            //         });
            //     }else{
            //         checkboxes.forEach((checkbox) => {
            //             checkbox.checked =false
            //         });
            //     }
            // },
            checkmMemberSelect(id){
                var which = document.getElementById('memberSelector_' + id).checked;            
                document.getElementById('memberSelector_' + id).checked = !which
            },
            // checkmMemberSelectAll(){
            //     var which = document.getElementById('allMemberSelector').checked;            
            //     document.getElementById('allMemberSelector').checked = !which;
            //     this.selectAllMembers()
            // },
            checkMemberCancel(){
                this.checkRequestModalToggle = false;
                clearAllBodyScrollLocks();
                var chk = document.querySelectorAll('input[name="memberCheckBox"]');
                chk.forEach((box) => {
                box.checked = false;
                })
                var allM = document.getElementById('allMemberSelector');
                if(allM){
                    allM.checked = false
                }
            },
            checkRequest(){
                const checkboxes = document.querySelectorAll('input[name="memberCheckBox"]:checked');
                        
                let values = [];
                checkboxes.forEach((checkbox) => {
                    values.push(checkbox.value);
                });
                let users = values
                this.$emit('selectedMembers', users)
                this.$emit('closeMe')
            },
        }
    }
</script>
