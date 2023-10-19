<template>
    <div class="c-bar-wrap">
        <Transition name="modalFade">
            <div class="overlay" @mousedown="closeModal(false)" v-if="addUsersWindow">                         
                <div class="chatCreate scrollable" @mousedown.stop>    
                    <div class="recordFormTitle" style="display:flex">
                        <p>メンバー</p>
                        <div class="cursor-pointer" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>                        
                        </div> 
                    </div> 
                    <div class="si-box">
                        <UserSelector 
                            :selfInclude="true" 
                            :initialSelected="selectedUsers"
                            placeHolder="メンバー選択"
                            rules="required"
                            @setUser="val => selectedUsers = val"
                            uId="calendarUsers"
                            name="calendarUsers"
                            ref="calendarUsers"
                            path="calendar_more_users"
                        />
                    </div>
                    <div style="margin-top: auto;">
                        <LoaderButton @triggered="submit" :loading="loading" content="保存する"/>
                    </div>
                    
                </div>
            </div>
        </Transition>

        <div @click.stop="$store.commit('setMenu', { id: 6, name: 'calendarMemberSelector'})" class="c-bar-button" style="margin-left: 15px;">メンバー</div>
        <div @click.stop="$store.commit('setMenu', { id: 7, name: 'calendarFacilitySelector'})" class="c-bar-button">施設</div>
        <div @click="$emit('jumpToday')" class="c-bar-button">本日</div>
        <Transition name="modalFade">
            <div v-if="$store.state.menu.id == 6 && $store.state.menu.name == 'calendarMemberSelector'" id="calendarMemberSelector" class="calendarMemberSelector">
                <div id="checkUserSelecter" style=" max-height: 50vh; overflow-y: auto;">                
                    <div>    
                        <div style="padding:0 15px;display:flex;">                                
                            <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                <input @change="selectAll" :checked="allSelected" name="memberCheckBox" type="checkbox">
                                <span class="cal-check-mark" style="top: 13px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                               
                                    <p class="userName" style="line-height: 30px;margin-left: 0;">全員選択</p>                                    
                                </div>
                            </label>  
                            
                        </div>
                        <div :key="user.id" v-for="user in myUsers" style="padding:0 15px;display:flex;">                                
                            <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                <input @change="update" :checked="user.pivot.selected_as_calendar_member" :value="user.id" name="memberCheckBox" type="checkbox">
                                <span class="cal-check-mark" style="top: 10px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                    <UserIcon size="30" :title="user.name" :user="user" imgClass="userNormalIcon"/>                      
                                    <p class="userName">{{user.name}}</p>                                    
                                </div>
                            </label>  
                            
                        </div>
                    </div>                             
                </div>
                <div @click="addMoreUsers" class="left-panel-items" style="width: auto; padding: 10px 15px;margin:0;user-select: none;cursor:pointer;background: inherit;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" fill="var(--primary-color)">
                        <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                    </svg>
                    <div style="margin-left: 10px;">メンバー追加</div>
                </div>
            </div>
        </Transition>
        <Transition name="modalFade">
            <div v-if="$store.state.menu.id == 7 && $store.state.menu.name == 'calendarFacilitySelector'" id="calendarFacilitySelector" class="calendarMemberSelector">
                <div id="calendarFacilitySelector" style=" max-height: 50vh; overflow-y: auto;">                
                    <div>    
                        <div :key="index" v-for="(facilities, index) in facilitiesList" style="padding:0 15px">     
                            <div style="margin: 10px 0;font-weight: 600;color: var(--primary-color);">{{ facilityTitle(index) }}</div>   
                            <div>                                                
                                <label v-for="(facility, sub_index) in facilities" class="cal-member-check" style="align-self: center;padding-left: 20px;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
                                    <input :checked="facility.selected" @input="$emit('setFacility', index, sub_index, $event.target.checked)" :value="facility.value" name="memberCheckBox" type="checkbox">
                                    <span class="cal-check-mark" style="top: 5px;"></span>
                                    <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">                    
                                        <p class="userName">{{facility.label}}</p>                                    
                                    </div>
                                </label>  
                            </div>     
                            
                        </div>
                    </div>                             
                </div>
            </div>
        </Transition>
    </div>
</template>
<script>
import UserIcon from '../Board/Mixed/UserIcon.vue'
import UserSelector from '../Global/UserSelector.vue'
import LoaderButton from '../Global/LoaderButton.vue'
export default{
    props: ['facilitiesList'],
    emits: ['jumpToday', 'updated', 'setFacility'],
    data(){
        return{
            list: [],
            checked: [],
            addUsersWindow: false,
            selectedUsers: [],
            loading: false,
        }
    },
    components: {
        UserIcon,
        UserSelector,
        LoaderButton
    },
    computed:{
        myUsers(){
            return this.list ? this.list : []
        },
        allSelected(){
            // this.myUsers.forEach(item => {
            //     if(item.pivot.selected_as_calendar_member == 0 || item.pivot.selected_as_calendar_member == false){
            //         return false
            //     }
            // });
            const hasUnselected = this.list.map( ob=> ob.pivot ).filter(ob => ob.selected_as_calendar_member == 0 || ob.selected_as_calendar_member == false)
            return !hasUnselected.length
        }
        
    },
    mounted(){
        this.getMyGroup()
        
    },
    methods: {
        facilityTitle(index){
            if(index == 'qualified_institution'){
                return '施設'
            }else 
            if(index == 'zoom_value'){
                return 'WEB会議'
            }else if(index == 'qualified_car'){
                return '車両'
            }
            return ''
        },
        submit(){
            if(!this.selectedUsers.length){
                this.errorToast('メンバーを選択してください')
                return
            }
            this.loading = true
            const params = {
                users: this.selectedUsers.map(ob => ob.id)
            }
            axios.post('/set_more_members', params ).then(response => {  
                
                this.$emit('updated')                
                this.getMyGroup(1)
                this.closeModal()
        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));
        },
        closeModal(){
            this.addUsersWindow = false
        },
        addMoreUsers(){
            this.addUsersWindow = true
        },
        
        getMyGroup(flag){
            axios.post('/get_my_groups').then(response => {  
                
                this.list = response.data
                this.selectedUsers = response.data
                if(flag){
                    this.loading = false
                    
                }
        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
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
        checkmMemberSelect(){

        },
        update(){
            
            const val = event.target.checked
            const id = event.target.value
            this.updateSelectedUsers(id, val)
        },
        selectAll(){
            this.list.forEach(item => {
                item.pivot.selected_as_calendar_member = event.target.checked
            });
            const val = event.target.checked
            const id = -1
            this.updateSelectedUsers(id, val)
        },
        updateSelectedUsers(id, val){
            console.log(val, id)
            axios.post('/update_selected_calendar_members',{id: id, value: val}).then(response => {  
                this.$emit('updated')
                this.getMyGroup()        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));
        }

    }
}
</script>