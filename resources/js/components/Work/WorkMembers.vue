<template>
    <div id="workMemberSelector" class="workMemberSelector" :style="customStyle">
        <div id="checkUserSelecter" style=" max-height: 50vh; overflow: hidden auto;">
            <div style="position: sticky; top:0;background: var(--bg3);z-index: 2;">
                <div class="sub-tab-container">
                    <div @click="byWorkGroups = 0" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 0}]">メンバー</div>
                    <div @click="byWorkGroups = 1" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 1}]">プロジェクト</div>
                    <div v-if="auth.activeUser.id === 610" @click="byWorkGroups = 2" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 2}]">車両</div>
                </div>
                <div class="searchBarInner" style="margin: 10px 15px 0;width: auto;min-width: 270px"> 
                    <PostSearchBar  
                        className="newChatMemberSearch" 
                        @search-start="(word) => {keywords = word}"
                        :custom-place-holder=placeHolder
                    />
                </div> 
            </div>         
            <div v-if="searchUsers.length">
                <div style="padding:0 15px;display:flex;" v-if="byWorkGroups == 0">                                
                    <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                        <input @change="selectAll"  name="memberCheckBox" type="checkbox">
                        <span class="work-check-mark" style="top: 13px;"></span>
                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                    
                            <p class="userName" style="line-height: 30px;margin-left: 0;">全員選択</p>                                    
                        </div>
                    </label>  
                </div>
                <div v-if="byWorkGroups !== 2">
                    <div :key="group.id" v-for="group in searchUsers" style="padding:0 15px;display:flex;">
                        <div v-if="(group.members && group.members.length) || (group.manager && group.manager.length)">
                            <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                <input :value="group.id" :checked="selectedGroups.includes(group.id)" @change="value = group.members.map(ob => ob.id).concat(group.manager.map(manager => manager.id)), selectGroup(group.id)" name="memberCheckBox" type="checkbox">
                                <span class="work-check-mark" style="top: 13px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                    <p class="userName" style="line-height: 30px; margin-left: 0;">{{group.name}}</p>                                    
                                </div>
                            </label>
                            <div v-if="selectedGroups.includes(group.id)" v-for="member in [...(group?.manager || []), ...(group?.members || [])]" style="padding:0 15px 0 30px;display:flex;">
                                <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                    <input v-model="value" :value="member.id" name="memberCheckBox" type="checkbox">
                                    <span class="work-check-mark" style="top: 10px;"></span>
                                    <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                        <UserPanel :disable-instant="true" :with-name="true" size="30" :title="member.name" :user="member" imgClass="userNormalIcon"/>                      
                                    </div>
                                </label>
                            </div>
                        </div>                                
                        <div v-else>
                            <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                <input v-model="value" :value="group.id" name="memberCheckBox" type="checkbox">
                                <span class="work-check-mark" style="top: 10px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                    <UserPanel :disable-instant="true" size="30" :with-name="true" :title="group.name" :user="group" imgClass="userNormalIcon"/>                      
                                </div>
                            </label>
                        </div>
                    </div>
                </div>    
                <div v-else>
                    <div v-for="vehicle in searchVehicles" style="padding:0 15px;display:flex;">
                        <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                            <input :value="vehicle.value" v-model="vehicles" name="memberCheckBox" type="checkbox">
                            <span class="work-check-mark" style="top: 13px;"></span>
                            <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                <p class="userName" style="line-height: 30px; margin-left: 0;">{{vehicle.label}}</p>                                    
                            </div>
                        </label>
                    </div>
                </div>
                
            </div>
            <div v-else-if="byUserOrGroup.length" style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center;white-space: nowrap;font-size: 13px;padding: 30px;">
                検索結果はありません。
            </div>
            <div v-else style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center;white-space: nowrap;font-size: 13px;padding: 30px;">
                現在予定申請中のメンバーはいません。
            </div>                          
        </div>
    </div>
</template>
<script setup>
import PostSearchBar from '../Post/PostSearchBar.vue';
import UserPanel from '../Global/UserPanel.vue';
import { computed, ref } from 'vue';
import { vehicleAsOptions } from '@/utils/workApi';
import { useAuthUserStore } from '@/store/auth';
    const props = defineProps(['workUsers', 'workGroups', 'customStyle'])
    const auth = useAuthUserStore()
    const byWorkGroups = ref(0)
    const keywords = ref('')
    const selectedGroups = ref([])
    const placeHolder = computed(() => {
        return byWorkGroups.value === 1 ? 'プロジェクト検索' : byWorkGroups.value === 2 ? '車両検索' : 'メンバー検索'
    })
    const searchUsers = computed(() => {
        if(keywords.value && Array.isArray(byUserOrGroup.value)){
            let lowSearch = keywords.value.toLowerCase()
            return byUserOrGroup.value.filter(user => 
                Object.values(user).some(val => 
                    String(val).toLowerCase().includes(lowSearch)
                )
            )
        }else{         
            return byUserOrGroup.value
        }
    })
    const searchVehicles = computed(() => {
        if (keywords.value) {
            let lowSearch = keywords.value.toLowerCase()
            return vehicleAsOptions.filter(vehicle =>
                vehicle.label.toLowerCase().includes(lowSearch)
            )
        } 
        
        return vehicleAsOptions
    
        
    })
    const byUserOrGroup = computed(() => {
        if(byWorkGroups.value == 0){
            return props.workUsers
        } else {
            return props.workGroups
        }
    })
    const selectAll = (event) => {        
        value.value = event.target.checked ? searchUsers.value.map(ob => ob.id) : []   
    }
    const selectGroup = (groupId) => {
        const index = selectedGroups.value.indexOf(groupId);
        if (index !== -1) {
            selectedGroups.value.splice(index, 1);
            value.value = []
        } else {
            selectedGroups.value = [groupId];
        }
    }
    const value = defineModel('users')
    const vehicles = defineModel('vehicles')
</script>
<style scoped lang="scss">
    .sub-tab-item{
        padding: 10px 15px;
        font-size: 14px;
        border-bottom: solid thin transparent;
        box-sizing: border-box;
        cursor: pointer;
    }
    .selected-sub-tab{
        border-bottom: solid thin var(--primary-color);
    }
    .sub-tab-container{
        display: flex;
    }
</style>