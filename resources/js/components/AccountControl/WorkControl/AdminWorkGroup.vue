<template>
    <div class="admin-workgroup-outwrapper">
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <div class="admin-command-bar" style="margin: 20px;">  
            <PostSearchBar 
                className="newChatMemberSearch" 
                customPlaceHolder="ワークグループやユーザー検索" 
                @search-start="(word) => {keywords = word}"
            />  
        </div>
        <div class="admin-workgroup-wrapper">
            <table class="admin-workgroup-innerwrapper">
                <tr>
                    <th>ワークグループ名</th>
                    <th>メンバー</th>
                    <th></th>
                </tr>
                <tr :key="item.id" v-for="item in filteredWorkgroups">
                    <td>{{ item.name }}
                    </td>
                    <td >
                        <div style="display: flex;flex-wrap: wrap;gap: 5px;padding: 5px;">
                            <div style="padding: 5px;background: var(--bg3);font-size: 12px;" v-for="member in item.members">
                                {{ member.name }}
                                <span v-if="member?.pivot?.authority == 1"><strong>(承認者)</strong></span>
                            </div>
                        </div>                        
                    </td>
                    <td>
                        <div style="display: flex; justify-content: center; gap: 10px;">
                            <CommandButton 
                                :buttons="[
                                    { title: '編集', action: () => openModal(item) },
                                    { title: '削除', action: () => deleteWorkGroup(item)}
                                ]"
                            />
                        </div>
                        
                        <!-- <button type="submit" @click="openModal(item)" class="account-btn">編集</button> -->
                    </td>
                </tr>
            </table>
        </div>       
            <div :style="{bottom : responsive.mobile ? '70px' : '20px', position:'fixed'}" class="createBoardButton fileNewButton" @click="openModal(null)">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div>
            <Transition name="modalFade">
                <div class="overlay" v-if="showModalContent"> 
                    <WorkGroupCreate
                        :userList="users"
                        :editWorkGroupData="editWorkGroupData"
                        @postFinish="postFinish"
                        @closeModal="showModalContent = false, editWorkGroupData = null"
                    />
                </div>
            </Transition>           
        </div>
</template>
<script setup>
import CommandButton from '../../Global/CommandButton.vue';
import WorkGroupCreate from './WorkGroupCreate.vue';
import { onMounted, ref, inject } from 'vue';
import { useResponsive } from '@/store/responsive';
import { computed } from 'vue';
import PostSearchBar from '../../Post/PostSearchBar.vue';
    const keywords = ref('')
    const responsive = useResponsive()
    const showModalContent = ref(false)
    const editWorkGroupData = ref(null)
    const workGroups = ref([])
    const fetch = ref(0)
    const users = ref([])
    const { notify, confirm } = inject('dialog')
    onMounted(async() => {
        await getWorkGroups()
        fetch.value ++
    })
    const filteredWorkgroups = computed(() => {
        return workGroups.value.filter(workgroup => {
            if(workgroup.name.toLowerCase().includes(keywords.value)){
                return workgroup
            }            
            const match = workgroup.members.filter(user => Object.values(user).some(val => 
                String(val).toLowerCase().includes(keywords.value)
            ))
            if(match.length){
                return workgroup
            }            
        })
    })
    const getWorkGroups = async() => {
        const { w, u } = await axios.get('/get_controllable_users?with_users=1').then(res => res.data)        
        workGroups.value = w
        users.value = u
    }
    const postFinish = () => {
        showModalContent.value = false
        editWorkGroupData.value = null
        getWorkGroups()
    }

    const openModal = (val) => {
        editWorkGroupData.value = val;
        showModalContent.value = true;
    }
    const deleteWorkGroup = async(item) => {
        const params = {
            work_group_id : item.id
        }
        const answer = await confirm('ワークグループを削除しますか？')
        if(!answer.value) return
        try {
            await axios.post('/work_group_delete', params)
            getWorkGroups()
        } catch (error) {
            if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。 ' + error.message)
        }
           
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
        margin: auto;
        position: relative;
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
        height: calc(100% - 70px);
        overflow: hidden auto;
    }
    table{
        border: solid thin var(--formBorder);
        border-collapse:collapse;
        width: -webkit-fill-available;
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