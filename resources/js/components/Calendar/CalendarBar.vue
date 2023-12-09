<template>
    <div class="c-bar-wrap">
        <Transition name="modalFade">
            <div class="overlay" @mousedown="closeModal(false)" v-if="addUsersWindow">                         
                <div class="chatCreate scrollable" @mousedown.stop>    
                    <div class="recordFormTitle" style="display:flex">
                        <p v-if="!tempGroup && !createWindow">グループ設定</p>
                        <div v-if="!tempGroup && !createWindow" class="cursor-pointer" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>                        
                        </div> 

                        <div v-if="tempGroup || createWindow" class="cursor-pointer" @click="createWindow = false, tempGroup = null" style="position:unset;margin: auto 20px auto 0;">
                            <svg data-v-4935b920="" class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                <path data-v-4935b920="" d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>                       
                        </div> 
                        <p v-if="tempGroup || createWindow">{{ tempGroup ? 'グループを編集する' : '新しいグループ作成する' }}</p>
                        <div v-if="tempGroup" style="margin-left: auto;" @click="deleteConfirm" class="commentEditButton">削除する</div>
                    </div> 
                    <div v-if="!tempGroup && !createWindow">                        
                        <div class="si-box">
                            <p style="margin-bottom: 15px;">編集するグループを選択してください。</p>
                            <div @click="editGroupStart(group)" v-for="group in myGroups">
                                <div style="padding: 20px;border: solid thin var(--calendarBorder);cursor: pointer;margin: 15px 0;">{{ group.name }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="tempGroup || createWindow" style="background: inherit;">    

                        <div class="si-box">
                            <FormShortText
                                :initialValue="tempGroup ? tempGroup.name : ''"
                                ref="groupTitle"
                                placeHolder="タイトルを入力（必須）"
                                uId="groupTitle"
                                name="groupTitle"
                                rules="required|max:48"
                                label="タイトル"
                                @setValue="val => title = val"
                            />
                        </div>
                        <div class="si-box">
                            <UserSelector 
                                :selfInclude="true" 
                                :initialSelected="tempGroup ? tempGroup.users : []"
                                placeHolder="メンバー選択"
                                rules="required"
                                @setUser="val => editingUserList = val"
                                uId="groupUsers"
                                name="groupUsers"
                                ref="groupUsers"
                                path="calendar_more_users"
                            />
                        </div>
                        <div style="margin-top: auto;padding-top: 30px;">
                            <LoaderButton @triggered="submit" :loading="loading" content="保存する"/>
                        </div>
                    </div>
                    <div v-if="!tempGroup && !createWindow" title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="createWindow = true">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                            <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </Transition>

        <div @click.stop="$store.commit('setMenu', { id: 6, name: 'calendarMemberSelector'})" class="c-bar-button" style="margin-left: 15px;">メンバー</div>
        <div @click.stop="$store.commit('setMenu', { id: 7, name: 'calendarFacilitySelector'})" class="c-bar-button">施設</div>
        <div @click="$emit('jumpToday')" class="c-bar-button">本日</div>
        <Transition name="modalFade">
            <div v-if="$store.state.menu.id == 6 && $store.state.menu.name == 'calendarMemberSelector'" id="calendarMemberSelector" class="calendarMemberSelector">
                <div id="checkUserSelecter" style=" max-height: 50vh; overflow-y: auto;color: var(--primary-color)">        
                    <!-- <div style="display: flex;padding: 15px 15px 0;">
                        <div @click="groupType = 0" :class="['ch-selector', {'chSelected' : groupType == 0}]">マイグループ</div>
                        <div @click="groupType = 1" :class="['ch-selector', {'chSelected' : groupType == 1}]">ワークグループ</div>
                    </div>     -->
                    <div v-if="workGroups.length">
                        <div style="padding: 15px;display: flex;justify-content: center;font-weight: 600;">
                            <div>ワークグループ</div>
                        </div>
                        <div v-for="group in workGroups">  
                            <div style="display: flex;align-items: center;white-space: nowrap;padding: 0 15px;gap: 15px;">
                                <div>
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input @change="selectWorkGroup" :value="group.id" :checked="myWorkGroupList.includes(group.id)" name="memberRadioBox" type="radio">
                                        <span class="cal-check-mark" style="top: 13px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                    
                                            <p class="userName" style="line-height: 30px;margin-left: 0;">{{ group.name }}</p>                                    
                                        </div>
                                    </label>  
                                </div>
                            </div>
                            <div v-if="myWorkGroupList.includes(group.id) && group.work_group_user">
                                <div :key="work_group_user.id" v-for="work_group_user in group.work_group_user" style="padding:0 15px 0 30px;display:flex;">                                
                                    <!-- <label class="cal-member-check" style="align-self: center;padding-bottom: 0;margin-bottom: 0;"> -->
                                        <!-- <input disabled="true" :checked="true" name="memberCheckBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 10px;"></span> -->
                                        <div v-if="work_group_user.user" class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                            <UserIcon size="25" :title="work_group_user?.user?.name" :user="work_group_user.user" imgClass="userMidIcon"/>                      
                                            <p class="userName">{{work_group_user?.user?.name}}</p>                                    
                                        </div>
                                    <!-- </label>   -->
                                    
                                </div>
                            </div>
                        </div> 
                        
                    </div>   
                    <div v-if="myGroups.length">
                        <div style="padding: 15px;display: flex;justify-content: center;font-weight: 600;">
                            <div>マイグループ</div>
                        </div>
                        <div v-for="group in myGroups">  
                            <div style="display: flex;align-items: center;white-space: nowrap;padding: 0 15px;gap: 15px;">
                                <div>
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input @change="selectAll(group)" :checked="group.selected" name="memberRadioBox" type="radio">
                                        <span class="cal-check-mark" style="top: 13px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                    
                                            <p class="userName" style="line-height: 30px;margin-left: 0;">{{ group.name }}</p>                                    
                                        </div>
                                    </label>  
                                </div>
                            </div>
                            <div v-if="group.selected">
                                <div :key="user.id" v-for="user in group.users" style="padding:0 15px 0 30px;display:flex;">                                
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input @change="update($event, group)" :checked="user.pivot.selected_as_calendar_member" :value="user.id" name="memberCheckBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 10px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                            <UserIcon size="25" :title="user.name" :user="user" imgClass="userMidIcon"/>                      
                                            <p class="userName">{{user.name}}</p>                                    
                                        </div>
                                    </label>  
                                    
                                </div>
                            </div>
                        </div> 
                    </div>
 
                                                
                </div>
                <div @click="addMoreUsers" class="left-panel-items" style="width: auto; padding: 10px 15px;margin:0;user-select: none;cursor:pointer;background: inherit;">
                    <!-- <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" fill="var(--primary-color)">
                        <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                    </svg> -->
                    <div style="margin-left: 10px;">グループ設定</div>
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
import FormShortText from '../Global/FormShortText.vue'
export default{
    props: ['facilitiesList'],
    emits: ['jumpToday', 'updated', 'setFacility', 'setActiveMembers'],
    data(){
        return{
            list: [],
            checked: [],
            addUsersWindow: false,
            selectedUsers: [],
            loading: false,
            expandedGroups: [],
            tempGroup: null,
            editingUserList: [],
            title: '',
            groupType: 0,
            workGroupList: [],
            myWorkGroupList: [],
            createWindow: false
        }
    },
    components: {
        UserIcon,
        UserSelector,
        LoaderButton,
        FormShortText
    },
    computed:{
        myUsers(){
            return this.list ? this.list : []
        },
        myGroups(){
            return this.list ? this.list : []
        },
        workGroups(){
            return this.workGroupList
        }
        
        
    },
    mounted(){
        this.getMyGroup()
        
    },
    methods: {
        editGroupStart(group){
            this.tempGroup = group
            this.title = group.name
        },
        selectWorkGroup(event){
            const id = parseInt(event.target.value)
            const value = event.target.checked
            axios.post('/select_work_group',{work_group_id: id, value: value}).then(response => {  
                this.$emit('updated')
                this.getMyGroup()        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));

        },
        allSelected(group){
            // this.myUsers.forEach(item => {
            //     if(item.pivot.selected_as_calendar_member == 0 || item.pivot.selected_as_calendar_member == false){
            //         return false
            //     }
            // });
            const hasUnselected = group.users.map( ob=> ob.pivot ).filter(ob => ob.selected_as_calendar_member == 0 || ob.selected_as_calendar_member == false)
            return !hasUnselected.length
        },
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
        async validation(){              
            try {          
                let result = true        
                let checkRef = ['groupTitle', 'groupUsers']
                for(const check of checkRef){
                    const exec = await this.$refs[check].$refs[check].validate()
                    result = result * exec.valid
                }                
                
                return result
            } catch (error) {
                console.error('Error fetching data:', error);
                throw error; 
            }               
        },
        deleteConfirm(){
            var uniqueChannell = Math.random().toString(36).substring(5);  
            const answers = ['はい', 'いいえ']
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: 'グループを削除しますか。',
                closeButton: true, 
                autoClose: false,
                answers: answers,
                channel: uniqueChannell

            })            
            emitter.on(uniqueChannell, (data) => {                 
                if(data.answer == answers[0]){
                    this.deleteExecute()
                }                
            });
        },
        deleteExecute(){
            if(!this.tempGroup) return
            axios.post('/delete_my_group', {id: this.tempGroup.id} ).then(response => {  
                
                this.$emit('updated')                
                this.getMyGroup(1)
                this.title = ''
                this.tempGroup = null
                this.editingUserList = []
                this.createWindow = false
        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));
        },
        async submit(){
            this.loading = true
            this.processing = true
            const valid = await this.validation()
            if(!valid){
                this.loading = false
                return
            }
            const params = {
                id: this.tempGroup ? this.tempGroup.id : null,
                title: this.title,
                users: this.editingUserList.map(ob => ob.id)
            }
            axios.post('/set_more_members', params ).then(response => {  
                
                this.$emit('updated')                
                this.getMyGroup(1)
                // this.closeModal()
                this.title = ''
                this.tempGroup = null
                this.editingUserList = []
                this.createWindow = false
        
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
                
                this.list = response.data.my_groups
                this.selectedUsers = response.data.my_groups
                this.workGroupList = response.data.work_groups
                this.myWorkGroupList = response.data.my_work_groups

                const uniqueUserIds = new Set();
                const memberList = [];
                this.selectedUsers.forEach((group) => {
                    if(group.selected){
                        group.users.forEach(user => {
                            if (!uniqueUserIds.has(user.id) && user.pivot && user.pivot.selected_as_calendar_member) {
                                uniqueUserIds.add(user.id);
                                memberList.push(user);
                            }
                        });
                    }                    
                });
                this.workGroupList.forEach((group) => {
                    if(this.myWorkGroupList.includes(group.id)){
                        group.work_group_user.forEach(work_group_user => {
                            if (work_group_user.user && !uniqueUserIds.has(work_group_user.user.id)) {
                                uniqueUserIds.add(work_group_user.user.id);
                                memberList.push(work_group_user.user);
                            }
                        });
                    }                    
                });
                this.$emit('setActiveMembers', memberList)
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
        update(event, group){
            
            const val = event.target.checked
            const id = event.target.value
            this.updateSelectedUsers(id, val, group.id)
        },
        selectAll(group){
            group.users.forEach(item => {
                item.pivot.selected_as_calendar_member = event.target.checked
            });
            const val = event.target.checked
            const user_id = -1
            this.updateSelectedUsers(user_id, val, group.id)
        },
        updateSelectedUsers(user_id, val, group_id){
            axios.post('/update_selected_calendar_members',{user_id: user_id, value: val, group_id: group_id}).then(response => {  
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