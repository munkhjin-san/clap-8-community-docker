<template>
    <div class="admin-window">
        <div class="createBoardButton fileNewButton" @click="openModal(null)">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div> 
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
        <div class="admin-command-bar" style="margin: 20px">            
            <div class="sub-tab-container" style="margin-bottom: 20px;">
                <div @click="retire = 0, on_leave = 0" :class="['sub-tab-item', { 'selected-sub-tab': retire == 0 && on_leave == 0}]">在籍者</div>
                <div @click="on_leave = 1, retire = 0" :class="['sub-tab-item', { 'selected-sub-tab': on_leave == 1 && retire == 0}]">休職者</div>
                <div @click="retire = 1, on_leave = 0" :class="['sub-tab-item', { 'selected-sub-tab': retire == 1 && on_leave == 0}]">退職者</div>                
            </div>    
            <PostSearchBar 
                className="newChatMemberSearch" 
                @search-start="(word) => {keywords = word}"
            />     
        </div>
        
        <div style="flex: 1;overflow: hidden;">
            <div class="user-record-parent" ref="scrollContainer">
                <div class="admin-account-center-inner" :key="item.id" v-for="item in filteredUsers">
                    <div class="account-wrapper">
                        <div style="display:flex; align-items:center;margin-bottom:10px;">
                            <UserPanel :disableInstant="true" size="30" :title="item.name" :user="item" imgClass="userNormalIcon"/>
                            <div style="display:flex; flex-direction:column">
                                <span style="margin-left:10px;">{{item.name}}</span>
                                <!-- <span style="margin-left:10px; margin-top:10px;">{{ item.name_kana }}</span> -->
                            </div>
                        </div>
                        <!-- <p>{{ item.phone }}</p> -->
                        <p class="account-content">{{ item.email }}</p>
                    </div>
                    <div class="detail-wrapper">
                        <div style="display:flex;overflow:hidden;margin-right:20px;padding-bottom:10px;" >
                            <p style="white-space:nowrap">役職:</p>
                            <p style="margin-left:10px;white-space:break-spaces;">{{ item.positions?.name ? item.positions.name : ' ' }}</p>
                        </div>
                        <div style="display:flex;overflow:hidden;margin-right:20px;padding-bottom:10px;" >
                            <p style="white-space:nowrap">営業所:</p>
                            <p style="margin-left:10px;white-space:break-spaces;">{{ item.offices?.name ? item.offices?.name : ' ' }}</p>
                        </div>
                    </div>
                    <div class="button-wrapper">
                        <CommandButton :buttons="[{title: '編集', action:() => openModal(item)}]"/>
                        <!-- <button type="submit" @click="openModal(item)" class="account-btn cursor-pointer">
                            編集
                        </button> -->
                    </div>        
                </div>
            </div>
        </div>
        
          
        <Transition name="modalFade">
            <div class="overlay" v-if="showModalContent">                      
                <UserCreate 
                    :positions="positions"
                    :offices="offices"
                    :editUserData="editUserData"
                    :workGroups="workGroups"
                    :passwordFlag="passwordFlag"
                    :linkables="linkables"
                    @postFinish="postFinish"
                />
            </div>   
        </Transition>
    </div>
   
</template>
<script setup>
    import CommandButton from '../Global/CommandButton.vue';
    import UserCreate from './UserCreate.vue'
    import UserPanel from '@/components/Global/UserPanel.vue'
    import { computed, onMounted, ref } from 'vue';
    import PostSearchBar from '../Post/PostSearchBar.vue';
    const showModalContent = ref(false)
    const editUserData = ref(null)
    const passwordFlag = ref(false)
    const scrollContainer = ref(null)
    const retire = ref(0)
    const on_leave = ref(0)
    const keywords = ref('')
    const usersList = ref([])
    const fetch = ref(0)
    const workGroups = ref([])
    const linkables = ref([])
    const positions = ref([])
    const offices = ref([])

    onMounted(async() => {
        await getUsers()
        fetch.value++
    })
    const getUsers = async() => {
        const { w, o, p, l, u } = await axios.get('/get_controllable_users').then(res => res.data)        
        usersList.value = u
        workGroups.value = w
        linkables.value = l
        positions.value = p
        offices.value = o
    }
    const filteredUsers = computed(() => {
        const filtered = usersList.value.filter(user => user.retire == retire.value && user.on_leave == on_leave.value)
        if(keywords.value){
            let lowSearch = keywords.value.toLowerCase()
            return filtered.filter(user => Object.values(user).some(val => 
                    String(val).toLowerCase().includes(lowSearch)
                )
            )
        }else{         
            return filtered
        }
    })

     
    const postFinish = () => {
        showModalContent.value = false;
        editUserData.value = null;
        getUsers()
    }          


    const openModal = (value) => {
        editUserData.value = value;
        showModalContent.value = true;
    }

        
</script>
<style scoped lang="scss">  

    .admin-account-center-inner{
        padding: 15px;
        background: var(--background-color);
        display: flex;
        flex-direction: column;
        height: fit-content;
        border: solid thin var(--calendarBorder);
        position: relative;
        word-break: break-word;
        font-size: 14px;
    }

    .button-wrapper{
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .account-content{
     margin-bottom: 10px;
    }
    .account-btn{
        color: #fff;
        background-color: var(--primary-button);
        padding: 5px 10px 5px 10px;
        font-size: 12px;
        line-height: 1.5;
    }
    .user-record-parent {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        height: -webkit-fill-available;
        height: -moz-available;
        overflow: hidden auto;
        background: var(--bg3);
        grid-auto-rows: max-content;
        padding: 0 20px;
    }

    @media screen and (max-width: 959px) {
        .user-record-parent{
            grid-template-columns: 100%;
        }

    }    
</style>