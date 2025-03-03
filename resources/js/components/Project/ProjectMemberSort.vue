<template>
    <div class="workMemberSelector">
        <div id="checkUserSelecter" style=" max-height: 50vh; overflow: hidden auto;">
            <div style="position: sticky; top:0;background: var(--bg3);z-index: 2;">
                <div class="searchBarInner" style="margin: 10px 15px 0;width: auto;min-width: 270px"> 
                    <PostSearchBar  
                        className="newChatMemberSearch" 
                        @search-start="(word) => {keywords = word}"
                        :custom-place-holder="customPlaceHolder"
                    />
                    <CommandButton :buttons="[{title: 'リセット', action: () => {selectedUsers = []; keywords = ''; menu.close()}}]"/>
                </div> 
            </div>         
            <div v-if="filteredMembers.length">
                <div>                   
                    <div v-for="member in filteredMembers" style="padding:0 15px 0 30px;display:flex;">
                        <label class="work-member-check" style="align-self: center;padding-left: 30px;padding-bottom: 0;margin-bottom: 0;">
                            <input v-model="selectedUsers" :value="member.id" name="memberCheckBox" type="checkbox">
                            <span class="work-check-mark" style="top: 10px;"></span>
                            <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">
                                <UserPanel :disable-instant="true" :with-name="true" size="25" :title="member.name" :user="member" imgClass="userNormalIcon"/>                      
                            </div>
                        </label>
                    </div>                   
                </div>    
                
            </div>
            <div v-else style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center;white-space: nowrap;font-size: 13px;padding: 30px;">
                検索結果はありません。
            </div>                      
        </div>
    </div>
</template>
<script setup lang="ts">
import PostSearchBar from '../Post/PostSearchBar.vue';
import UserPanel from '../Global/UserPanel.vue';
import { computed, ref } from 'vue';
import CommandButton from '../Global/CommandButton.vue';
import { useMenuStore } from '@/store/menu';
    const props = defineProps(['members', 'customPlaceHolder'])
    const menu = useMenuStore()
    const keywords = ref('')
    const filteredMembers = computed(() => {
        if(keywords.value && Array.isArray(props.members)){
            let lowSearch = keywords.value.toLowerCase()
            return props.members.filter(user => 
                Object.values(user).some(val => 
                    String(val).toLowerCase().includes(lowSearch)
                )
            )
        }else{         
            return props.members
        }
    })



    const selectedUsers = defineModel('selectedUsers')
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