<template>
    <div class="c-bar-wrap">
        <Transition name="modalFade">
            <div class="overlay" @mousedown="closeModal(false)" v-if="addUsersWindow">                         
                <div class="chatCreate scrollable" @mousedown.stop>    
                    <div class="recordFormTitle" style="display:flex">                     
                        <p v-if="tempGroup || createWindow">{{ tempGroup ? 'グループを編集する' : '新しいグループ作成する' }}</p>
                        <div class="cursor-pointer" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>                        
                        </div> 
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
                            <ShortInput 
                                name="groupTitle" 
                                placeHolder="タイトルを入力（必須）" 
                                :rules="'required'"
                                :initialValue="editTarget ? editTarget.title : ''"
                                customClass="full"
                                ref="groupTitle"
                                type="text"
                                v-model="title"
                            />
                        </div>
                        <div class="si-box">
                            <MemberSelector 
                                :closeOnSelect="false" 
                                placeHolder="メンバー選択"
                                rules="required"
                                v-model="editingUserList"
                                name="groupUsers"
                                ref="groupUsers"
                                path="calendar_more_users"
                            />
                        </div>
                        <div style="margin-top: auto;padding-top: 30px;">
                            <LoaderButton @triggered="submit" :loading="loading" content="保存する"/>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <div @click.stop="menu.setMenu( { id: 6, name: 'calendarMemberSelector', parent: 'calendarMemberSelector'})" v-if="!auth.isRegistered" class="c-bar-button" style="margin-left: 15px;">メンバー</div>
        <div @click.stop="menu.setMenu( { id: 7, name: 'calendarFacilitySelector', parent: 'calendarFacilitySelector'})" :style="{marginLeft : auth.isRegistered ? '15px' : '0'}" class="c-bar-button">施設</div>
        <div @click.stop="menu.setMenu( { id: 8, name: 'departmentSelector', parent: 'departmentSelector'})" class="c-bar-button">部門</div>
        <div @click="emit('jumpToday')" class="c-bar-button">本日</div>
        <Transition name="modalFade">
            <div v-if="menu.parent == 'calendarMemberSelector'" id="calendarMemberSelector" class="calendarMemberSelector" @click="menu.name = ''">
                <div id="checkUserSelecter" style=" max-height: 50vh; overflow-y: auto;color: var(--primary-color);min-height: 150px;">       
                    <div v-if="myGroups.length">
                        <div v-for="group in myGroups">  
                            <div style="display: flex;align-items: center;white-space: nowrap;padding: 0 15px;gap: 15px;position:relative;justify-content: space-between;">
                                <div>
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;justify-content: space-between;">
                                        <input @change="selectAll(group, 'byGroup')" :checked="group.selected" name="memberRadioBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 13px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                                    
                                            <p class="userName" style="line-height: 30px;margin-left: 0;">{{ group.name }}</p>                                    
                                        </div>                                        
                                    </label>  
                                </div>
                                
                                <ItemMenu :items="[
                                    {title: '編集する', action: () => editGroupStart(group)},
                                    {title: '削除する', action: () => deleteConfirm(group)}
                                ]"/>
                                
                            </div>
                             
                            <div v-if="group.selected">
                                <div style="padding:0 15px 0 30px;display:flex;"> 
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input @change="selectAll(group, 'byMember')" :checked="allSelected(group)" name="memberCheckBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 13px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">

                                            <p class="userName" style="line-height: 30px;margin-left: 0;">全員選択</p>                                    
                                        </div>
                                    </label> 
                                </div>
                                <div :key="user.id" v-for="user in group.users" style="padding:0 15px 0 30px;display:flex;">                                
                                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                        <input @change="update($event, group)" :checked="user.pivot.selected_as_calendar_member" :value="user.id" name="memberCheckBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 10px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                            <UserIcon :disableInstant="true" size="25" :title="user.name" :user="user" imgClass="userMidIcon"/>                      
                                            <p class="userName">{{user.name}}</p>                                    
                                        </div>
                                    </label>  
                                    
                                </div>
                            </div>
                        </div> 
                    </div>
                    <div v-else class="no-comment-text" style="position: unset;margin-top: 30px;padding: 20px;">
                        <div>現在マイグループありません。</div>
                    </div>
                    <div title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" style="z-index: 7;" @click="createWindow = true, addUsersWindow = true">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                            <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                        </svg>
                    </div>                                                
                </div>
            </div>
        </Transition>
        <Transition name="modalFade">
            <div v-if="menu.parent == 'calendarFacilitySelector'" id="calendarFacilitySelector" class="calendarMemberSelector">
                <div id="calendarFacilitySelector" style=" max-height: 50vh; overflow-y: auto;">                
                    <div>    
                        <div :key="index" v-for="(facilities, index) in facilitiesList" style="padding:0 15px">     
                            <div style="margin: 10px 0;font-weight: 600;color: var(--primary-color);">{{ facilityTitle(index) }}</div>   
                            <div>                                                
                                <label v-for="(facility, sub_index) in facilities" class="cal-member-check" style="align-self: center;padding-left: 20px;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
                                    <input :checked="facility.selected" @input="emit('setFacility', index, sub_index, $event.target.checked)" :value="facility.value" name="memberCheckBox" type="checkbox">
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
        <Transition>
            <div v-if="menu.parent == 'departmentSelector'" id="departmentSelector" class="calendarMemberSelector">
                <div id="departmentSelector" style=" max-height: 50vh; overflow-y: auto;"> 
                    <div style="position: sticky; padding: 10px 15px 5px; top: 0; background: var(--bg3);z-index: 2;">
                        <div class="searchBarInner" style="margin: auto;width: auto;min-width: 270px"> 
                            <PostSearchBar  
                                className="newChatMemberSearch" 
                                :searching="false" 
                                :customPlaceHolder="'部門検索'"
                                v-model="keywords"
                            />
                        </div> 
                    </div>               
                    <div>
                            
                        <div style="padding: 0 15px;">                                                
                            <label v-for="department in searchDepartment" class="cal-member-check" style="align-self: center;padding-left: 20px;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
                                <input :value="department.id" :checked="selectedDepartment.includes(department.id)" @input="emit('setDepartment', department.id)" name="memberCheckBox" type="checkbox">
                                <span class="cal-check-mark" style="top: 5px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">                    
                                    <p class="userName">{{department.name}}</p>                                    
                                </div>
                            </label>  
                        </div>     
                    </div>                             
                </div>
            </div>
        </Transition>
    </div>
</template>
<script setup>
import UserIcon from '../Board/Mixed/UserIcon.vue'
import MemberSelector from '../Form/MemberSelector.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import ShortInput from '../Form/ShortInput.vue'
import { computed, onMounted, ref, inject } from 'vue'
import { useMenuStore } from "@/store/menu";
import { useAuthUserStore } from '../../store/auth'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import PostSearchBar from '../Post/PostSearchBar.vue'
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps(['facilitiesList', 'selectedYear', 'selectedMonth', 'departmentsList', 'selectedDepartment'])
    const emit = defineEmits(['jumpToday', 'updated', 'setFacility', 'setActiveMembers', 'setDepartment'])
    const list = ref([])
    const addUsersWindow = ref(false)
    const selectedUsers = ref([])
    const loading = ref(false)
    const tempGroup = ref(null)
    const editingUserList = ref([])
    const title = ref('')
    const workGroupList = ref([])
    const myWorkGroupList = ref([])
    const createWindow = ref(false) 
    const menuId = ref(null)
    const groupTitle = ref(null)
    const groupUsers = ref(null)
    const keywords = ref('')
    const myGroups = computed(() => {
        return list.value ? list.value : []
    })        
    const { notify, confirm, info } = inject('dialog')
    onMounted(() => {
        getMyGroup()        
    })
    const searchDepartment = computed(() => {
        const { departmentsList } = props;
        const keyword = keywords.value.toLowerCase();
        
        if (keyword && Array.isArray(departmentsList)) {
            return departmentsList.filter(department => {
                return Object.values(department).some(value => {
                    return typeof value === 'string' && value.toLowerCase().includes(keyword);
                });
            });
        }
        return departmentsList
    })

    const editGroupStart = (group) => {
        tempGroup.value = group
        title.value = group.name
        editingUserList.value = group.users
        addUsersWindow.value = true
    }
    const allSelected = (group) => {
        const hasUnselected = group.users.map( ob=> ob.pivot ).filter(ob => ob.selected_as_calendar_member == 0 || ob.selected_as_calendar_member == false)
        return !hasUnselected.length
    }
    const facilityTitle = (index) => {
        if(index == 'qualified_institution'){
            return '施設'
        }else 
        if(index == 'zoom_value'){
            return 'WEB会議'
        }else if(index == 'qualified_car'){
            return '車両'
        }
        return ''
    }
    const validation = async () => {              
        try {          
            let result = true
            let checkRef = [groupTitle.value, groupUsers.value]
            for(const check of checkRef){
                const validate = await check.validate()    
                result = result * validate.valid            
            }
            return result
        } catch (error) {
            console.error('Error fetching data:', error);
            throw error; 
        }               
    }
    const deleteConfirm = async(group) => {
        const answer = await confirm('グループを削除しますか。')
        if(!answer) return
        deleteExecute(group)
         
    }
    const deleteExecute = (group) => {
        if(!group) return
        axios.post('/delete_my_group', {id: group.id} ).then(response => {  
            completed('削除した。')     
    
        }).catch(function (error) {
            if (error.response) notify(error.response.data.message)                    
        });
    }
    const submit = async () => {
        loading.value = true
        const valid = await validation()
        if(!valid){
            loading.value = false
            return
        }
        const params = {
            id: tempGroup.value ? tempGroup.value.id : null,
            title: title.value,
            users: editingUserList.value.map(ob => ob.id)
        }
        axios.post('/set_more_members', params ).then(response => {  
            completed('保存しました。')                
    
        }).catch(function (error) {
            if (error.response) notify(error.response.data.message)                      
        });
    }
    const completed = (message) => {
        emit('updated')                
        getMyGroup(1)
        title.value = ''
        tempGroup.value = null
        editingUserList.value = []
        createWindow.value = false
        addUsersWindow.value = false
        info(message)
        menu.setMenu( {name: 'calendarMemberSelector', id: 6})
        menuId.value = null
    }
    const closeModal = () => {
        addUsersWindow.value = false
        createWindow.value = false
        tempGroup.value = null
        title.value = ''
        editingUserList.value = []
        menu.setMenu( {name: 'calendarMemberSelector', id: 6})
    }
    
    const getMyGroup = (flag) => {
        axios.post('/get_my_groups', {
            year: props.selectedYear,
            month: props.selectedMonth + 1
        }).then(response => {  
            
            list.value = response.data.my_groups
            selectedUsers.value = response.data.my_groups
            workGroupList.value = response.data.work_groups
            myWorkGroupList.value = response.data.my_work_groups

            const uniqueUserIds = new Set();
            const memberList = [];
            selectedUsers.value.forEach((group) => {
                if(group.selected){
                    group.users.forEach(user => {
                        if (!uniqueUserIds.has(user.id) && user.pivot && user.pivot.selected_as_calendar_member) {
                            uniqueUserIds.add(user.id);
                            memberList.push(user);
                        }
                    });
                }                    
            });
            emit('setActiveMembers', memberList)
            if(flag){
                loading.value = false                    
            }
    
        }).catch(function (error) {
            if (error.response) notify(error.response.data.message)                        
        });
    }
    const update = (event, group) => {
        
        const val = event.target.checked
        const id = event.target.value
        updateSelectedUsers(id, val, group.id, 'byMember')
    }
    const selectAll = (group, by) => {
        group.users.forEach(item => {
            item.pivot.selected_as_calendar_member = event.target.checked
        });
        const val = event.target.checked
        const user_id = -1
        updateSelectedUsers(user_id, val, group.id, by)
    }
    const updateSelectedUsers = (user_id, val, group_id, by) => {
        axios.post('/update_selected_calendar_members',{user_id: user_id, value: val, group_id: group_id, by: by}).then(response => {  
            emit('updated')
            getMyGroup()        
        }).catch(function (error) {
            if (error.response) notify(error.response.data.message)                 
        });
    }
</script>