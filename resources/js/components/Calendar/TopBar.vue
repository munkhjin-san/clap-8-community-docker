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
                                :multiple="true"
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
                <div id="checkUserSelecter" style=" max-height: 50vh; overflow-y: auto;color: var(--primary-color);">   
                    <div @click="createWindow = true, addUsersWindow = true" class="groupCreateButton">
                        <div v-html="getIcon('plus')"></div>
                        <p>グループ追加</p>                        
                    </div> 
                    <div v-if="myGroups.length">
                        <div v-for="group in myGroups">  
                            <div class="c-group-item">
                                    <label class="cal-member-check">
                                        <input @change="selectAll($event, group, 'byGroup')" :checked="group.selected ? true : false" name="memberRadioBox" type="checkbox">
                                        <span class="cal-check-mark" style="top: 13px;"></span>
                                        {{ group.name }}                                  
                                    </label>                                 
                                    <ItemMenu :items="[
                                        {title: '編集する', action: () => editGroupStart(group)},
                                        {title: '削除する', action: () => deleteConfirm(group)}
                                    ]"/>
                                
                            </div>
                             
                            <div class="active-group-members" v-if="group.selected">
                                <label class="cal-member-check">
                                    <input @change="selectAll($event, group, 'byMember')" :checked="allSelected(group)" name="memberCheckBox" type="checkbox">
                                    <span class="cal-check-mark" style="top: 13px;"></span>
                                    全員選択
                                </label>
                                <label :key="user.id" v-for="user in group.users" class="cal-member-check">
                                    <input @change="update($event, group)" :checked="user.pivot.selected_as_calendar_member" :value="user.id" name="memberCheckBox" type="checkbox">
                                    <span class="cal-check-mark" style="top: 10px;"></span>
                                    <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                        <UserPanel :disableInstant="true" size="25" :title="user.name" :user="user" imgClass="userMidIcon"/>                      
                                        <p class="userName">{{user.name}}</p>                                    
                                    </div>
                                </label>  
                                    
                            </div>
                        </div> 
                    </div>
                    <div v-else class="no-comment-text" style="position: unset;margin-top: 30px;padding: 20px;">
                        <div>現在マイグループありません。</div>
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
                                <label v-for="(facility, sub_index) in facilities" class="cal-member-check" style="align-self: center;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
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
                                :customPlaceHolder="'部門検索'"
                                @search-start="(word) => {keywords = word}"
                            />
                        </div> 
                    </div>               
                    <div>
                            
                        <div style="padding: 0 15px;">                                                
                            <label v-for="department in searchDepartment" class="cal-member-check" style="align-self: center;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
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
import UserPanel from '@/components/Global/UserPanel.vue'
import MemberSelector from '../Form/MemberSelector.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import ShortInput from '../Form/ShortInput.vue'
import { computed, onMounted, ref, inject } from 'vue'
import { useMenuStore } from "@/store/menu";
import { useAuthUserStore } from '../../store/auth'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import PostSearchBar from '../Post/PostSearchBar.vue'
import { getIcon } from 'assets/icons'
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
    const allMembers = ref([])
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
        if(!answer.value) return
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
            allMembers.value = response.data.all_members
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
    const selectAll = (event, group, by) => {
        const target = event.target
        group.users.forEach(item => {
            item.pivot.selected_as_calendar_member = target.checked ? 1 : 0
        });
        const val = target.checked
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
<style lang="scss">
.fac-select-pop{
    max-height: 50vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    padding: 15px;
    gap: 15px;
}
.c-group-item{
    display: flex;
    align-items: center;
    white-space: nowrap;
    padding: 10px 5px 10px 15px;
    gap: 15px;
    position: relative;
    justify-content: space-between;
}
.active-group-members{
    padding: 0px 15px 10px 30px;
    display: flex;
    flex-direction: column;
    white-space: nowrap;
    gap: 10px;
}
.groupCreateButton{
    display: flex;
    align-items: center;
    gap:10px;
    padding: 15px 15px 5px 15px;
    cursor:pointer;
    svg{
        width: 12px;
        height: 12px;
        fill:var(--primary-color);
    }
}
</style>