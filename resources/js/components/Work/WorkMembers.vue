<template>
    <div id="workMemberSelector" class="workMemberSelector" :style="customStyle">
        <div id="checkUserSelecter" style=" max-height: 50vh; overflow: hidden auto;">
            <div style="position: sticky; top:0;background: var(--bg3);z-index: 2;">
                <div class="sub-tab-container" style="color: var(--primary-color);">
                    <div @click="byWorkGroups = 0" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 0}]">メンバー</div>
                    <div @click="byWorkGroups = 1, checkedUsers = []" :class="['sub-tab-item', { 'selected-sub-tab': byWorkGroups == 1}]">ワークグループ</div>
                </div>
                <div class="searchBarInner" style="margin: 10px 15px 0;width: auto;min-width: 270px"> 
                    <PostSearchBar  className="newChatMemberSearch" :searching="false" @searchStart="(val) => keywords = val"/>
                </div> 
            </div>         
            <div v-if="searchUsers.length">
                <div style="padding:0 15px;display:flex;" v-if="byWorkGroups == 0">                                
                    <label class="cal-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                        <input @change="selectAll" :checked="searchUsers.length && searchUsers.length == value.length"  name="memberCheckBox" type="checkbox">
                        <span class="cal-check-mark" style="top: 13px;"></span>
                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                    
                            <p class="userName" style="line-height: 30px;margin-left: 0;">全員選択</p>                                    
                        </div>
                    </label>  
                </div>    
                <div :key="group.id" v-for="group in searchUsers" style="padding:0 15px;display:flex;">
                    <div v-if="group.members && group.members.length">
                        <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                            <input :value="group.id" :checked="selectedGroups.includes(group.id)" @change="value = group.members.map(ob => ob.id), selectGroup(group.id)" name="memberCheckBox" type="checkbox">
                            <span class="work-check-mark" style="top: 13px;"></span>
                            <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                <p class="userName" style="line-height: 30px; margin-left: 0;">{{group.name}}</p>                                    
                            </div>
                        </label>
                        <div v-if="selectedGroups.includes(group.id)" v-for="member in group.members" style="padding:0 15px 0 30px;display:flex;">
                            <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                                <input v-model="value" :value="member.id" name="memberCheckBox" type="checkbox">
                                <span class="work-check-mark" style="top: 10px;"></span>
                                <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                    <UserIcon :disable-instant="true" size="30" :title="member.name" :user="member" imgClass="userNormalIcon"/>                      
                                    <p class="userName">{{member.name}}</p>                                    
                                </div>
                            </label>
                        </div>
                    </div>                                
                    <div v-else>
                        <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                            <input v-model="value" :value="group.id" name="memberCheckBox" type="checkbox">
                            <span class="work-check-mark" style="top: 10px;"></span>
                            <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                <UserIcon :disable-instant="true" size="30" :title="group.name" :user="group" imgClass="userNormalIcon"/>                      
                                <p class="userName">{{group.name}}</p>                                    
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
import UserIcon from '../Board/Mixed/UserIcon.vue';
import { computed, ref } from 'vue';
    const props = defineProps(['workUsers', 'workGroups', 'customStyle'])
    const byWorkGroups = ref(0)
    const keywords = ref('')
    const selectedGroups = ref([])
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
    const value = defineModel()
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