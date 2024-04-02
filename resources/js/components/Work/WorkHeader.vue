<template>
    <div class="workButtons-wrapper">
        <HamBurger v-if="responsive.mobile"/>

        <button class="work-button" @click="clickButton('selectShift')">
            勤怠予定
        </button>
        <button class="work-button" @click="clickButton('confirmAttendance')">
            勤怠確定
        </button>
        <div class="work-button" @click.stop="clickButton('selectMember')">
            メンバー
        </div>
        <Transition name="modalFade">
            <div v-if="menu.id == 98 && menu.name == 'workMemberSelector'" id="workMemberSelector" class="workMemberSelector" style="width: min-content;">
                <div class="searchBarInner" style="height: 40px;margin: 10px 15px 0;width: auto;min-width: 270px"> 
                    <input v-model="keywords" class="searchBarArea searchInputArea memberSearch" id="workMemberSearch" placeholder="ユーザー検索" type="text" style="margin: auto 0 auto auto;width:100%;background:#fff"/>
                    <div style="position: absolute;left: 10px;display: flex;height: 30px;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="margin: 7px auto auto auto;fill:#767676">
                            <path d="M31.875 28.185c-0.034-0.444-0.159-0.888-0.376-1.275-0.102-0.194-0.239-0.387-0.387-0.547-0.171-0.194-0.239-0.251-0.342-0.353-0.752-0.752-1.526-1.492-2.278-2.232-0.387-0.376-0.763-0.74-1.15-1.116l-0.865-0.831-0.091-0.091c-0.034-0.034-0.080-0.068-0.125-0.102-0.080-0.068-0.171-0.137-0.262-0.194-0.729-0.49-1.651-0.626-2.471-0.376-0.148 0.046-0.285 0.091-0.421 0.159-0.068 0.034-0.148 0.023-0.205-0.034-0.251-0.262-0.854-0.9-1.139-1.207-0.057-0.068-0.068-0.159-0.011-0.228 0.717-0.911 1.275-1.902 1.697-2.938 0.592-1.469 0.888-3.029 0.888-4.589s-0.296-3.12-0.888-4.601c-0.592-1.469-1.492-2.847-2.676-4.043-1.173-1.196-2.54-2.095-4.009-2.688-1.469-0.604-3.029-0.9-4.589-0.9-1.549 0-3.109 0.296-4.578 0.9-1.469 0.592-2.847 1.492-4.031 2.688-1.184 1.184-2.084 2.562-2.676 4.031s-0.888 3.041-0.888 4.601 0.296 3.12 0.888 4.589c0.592 1.469 1.492 2.847 2.676 4.043s2.562 2.084 4.031 2.688c1.469 0.604 3.029 0.9 4.589 0.9s3.12-0.296 4.578-0.9c1.036-0.421 2.038-1.002 2.949-1.72 0.046-0.034 0.114-0.034 0.159 0.011 0.273 0.273 1.002 0.957 1.253 1.196 0.034 0.034 0.046 0.091 0.023 0.137-0.205 0.444-0.307 0.945-0.285 1.446 0.023 0.421 0.137 0.854 0.342 1.23 0.102 0.194 0.228 0.376 0.364 0.535 0.171 0.194 0.228 0.251 0.33 0.353 0.74 0.774 1.469 1.549 2.209 2.3l1.116 1.15 0.558 0.569 0.376 0.376c0.034 0.034 0.080 0.080 0.125 0.114 0.080 0.068 0.171 0.137 0.262 0.205 0.74 0.512 1.708 0.683 2.574 0.444 0.433-0.114 0.843-0.319 1.196-0.615 0.046-0.034 0.091-0.068 0.125-0.114l0.114-0.102 0.421-0.421c0.319-0.319 0.558-0.706 0.717-1.127s0.216-0.877 0.182-1.321zM15.795 21.159c-1.15 0.467-2.391 0.706-3.621 0.706s-2.46-0.239-3.621-0.706c-1.15-0.467-2.243-1.173-3.177-2.118-0.945-0.945-1.64-2.027-2.118-3.189-0.467-1.162-0.706-2.403-0.706-3.633 0-1.241 0.239-2.471 0.706-3.633s1.173-2.243 2.118-3.189c0.945-0.957 2.027-1.651 3.189-2.13 1.15-0.467 2.38-0.706 3.621-0.706 1.23 0 2.46 0.239 3.621 0.706 1.15 0.467 2.232 1.173 3.177 2.118v0c0.945 0.945 1.64 2.027 2.118 3.189 0.467 1.162 0.706 2.403 0.706 3.633 0 1.241-0.239 2.471-0.706 3.633s-1.173 2.243-2.118 3.189c-0.957 0.957-2.038 1.663-3.189 2.13zM29.153 28.823l-0.478 0.478c-0.057 0.057-0.137 0.091-0.216 0.114-0.159 0.046-0.342 0.011-0.478-0.080-0.011-0.011-0.034-0.023-0.046-0.034l-0.068-0.068-0.285-0.273-1.708-1.674c-0.763-0.752-1.526-1.48-2.3-2.221-0.239-0.239-0.251-0.239-0.319-0.342-0.057-0.080-0.091-0.182-0.102-0.285-0.034-0.205 0.046-0.433 0.182-0.592 0.125-0.159 0.364-0.399 0.558-0.535 0.273-0.194 0.604-0.125 0.797 0.068s1.697 1.754 2.061 2.141c0.74 0.763 1.48 1.537 2.232 2.289 0.239 0.239 0.239 0.239 0.285 0.33 0.034 0.068 0.057 0.159 0.068 0.239 0.011 0.159-0.057 0.319-0.182 0.444z"></path>
                        </svg>
                    </div>
                </div>
                <div id="checkUserSelecter" style=" max-height: 50vh; overflow-y: auto;">                
                    <div>
                        <div style="padding:0 15px;display:flex;">                                
                            <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                <input @change="selectAll" :checked="searchUsers.length == selectedUsersList.length"  name="memberCheckBox" type="checkbox">
                                <span class="cal-check-mark" style="top: 13px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                               
                                    <p class="userName" style="line-height: 30px;margin-left: 0;">全員選択</p>                                    
                                </div>
                            </label>  
                            
                        </div>    
                        <div :key="user.id" v-for="user in searchUsers" style="padding:0 15px;display:flex;">                                
                            <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                <input v-model="selectedUsersList" :value="user.id" name="memberCheckBox" type="checkbox">
                                <span class="work-check-mark" style="top: 10px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                    <UserIcon size="30" :title="user.name" :user="user" imgClass="userNormalIcon"/>                      
                                    <p class="userName">{{user.name}}</p>                                    
                                </div>
                            </label>  
                            
                        </div>
                    </div>                             
                </div>
            </div>
        </Transition>
        
        <button class="work-button" @click="clickButton('jumpToToday')">
            今日
        </button>
        <button class="work-button pc" @click="clickButton('jumpToTotal')">
            集計
        </button>
        
       
    </div>
</template>
<script setup lang="ts">
    import { ref, computed } from 'vue';
    import HamBurger from '../Global/HamBurger.vue'
    import UserIcon from '../Board/Mixed/UserIcon.vue';
    import { useMenuStore } from "../../store/menu";
    import { useResponsive } from '../../store/responsive';
    import { useAuthUserStore } from '../../store/auth';
    import { User } from '../../interface/workInterface';
    const menu = useMenuStore()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    interface Props {
        usersCheckArray: Array<number | null>
        workGroups: Array<User>
    }
    const props = defineProps<Props>()
    const emit = defineEmits(['selectShift', 'confirmAttendance', 'todayScroll', 'toBottomScroll'])
    const keywords = ref<string>()

    const selectedUsersList = defineModel<any>()
    
    const searchUsers = computed(() => {
        if(keywords.value && Array.isArray(props.workGroups)){
            let lowSearch: string = keywords.value.toLowerCase()
            return props.workGroups.filter(user => 
                Object.values(user).some(val => 
                    String(val).toLowerCase().includes(lowSearch)
                )
            )
        }else{         
            return props.workGroups
        }
    })
    
    const checked = (id: number) => {
        return props.usersCheckArray.includes(id)
    }
    const clickButton = (action: string) => {
        if(action == 'selectShift'){
            emit('selectShift')
        }else if(action == 'confirmAttendance'){
            emit('confirmAttendance')
        }else if(action == 'selectMember'){
            menu.setMenu( { id: 98, name: 'workMemberSelector'})
        }else if(action == 'jumpToToday'){
            emit('todayScroll')
        }else if(action == 'jumpToTotal'){
            emit('toBottomScroll')
        }
    }

    // const setKeyord = (event: any) => {
    //     keywords.value = event.target.value
    // }
    const selectAll = (event: any) => {        
        selectedUsersList.value = event.target.checked ? searchUsers.value.map(ob => ob.id) : []        
    }
</script>
