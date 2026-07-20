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
        <div class="admin-command-bar">
            <div class="sub-tab-container">
                <div @click="retire = 0, on_leave = 0" :class="['sub-tab-item', { 'selected-sub-tab': retire == 0 && on_leave == 0}]">在籍者</div>
                <div @click="on_leave = 1, retire = 0" :class="['sub-tab-item', { 'selected-sub-tab': on_leave == 1 && retire == 0}]">休職者</div>
                <div @click="retire = 1, on_leave = 0" :class="['sub-tab-item', { 'selected-sub-tab': retire == 1 && on_leave == 0}]">退職者</div>
            </div>
            <div class="admin-command-tools">
                <PostSearchBar
                    :key="searchBarKey"
                    className="newChatMemberSearch"
                    @search-start="(word) => {keywords = word}"
                />
                <div ref="filterRef" class="filter-toggle-wrap">
                    <button type="button" class="filter-toggle" :class="{ active: filtersOpen || filtersActive }" title="絞り込み" @click="filtersOpen = !filtersOpen">
                        <Filter :size="18" :filtered="filtersActive"/>
                        <span v-if="filtersActive" class="filter-dot"></span>
                    </button>
                    <div v-if="filtersOpen" class="filter-panel">
                        <select v-model="selectedPositionId" class="account-filter-select">
                            <option value="">役職: すべて</option>
                            <option v-for="position in positions" :key="position.id" :value="String(position.id)">
                                {{ position.name }}
                            </option>
                        </select>
                        <select v-model="selectedOfficeId" class="account-filter-select">
                            <option value="">営業所: すべて</option>
                            <option v-for="office in offices" :key="office.id" :value="String(office.id)">
                                {{ office.name }}
                            </option>
                        </select>
                        <select v-model="selectedWorkType" class="account-filter-select">
                            <option value="">雇用形態: すべて</option>
                            <option v-for="workType in workTypeOptions" :key="workType.value" :value="String(workType.value)">
                                {{ workType.label }}
                            </option>
                        </select>
                        <button type="button" class="account-filter-reset" @click="resetFilters">フィルタ解除</button>
                    </div>
                </div>
                <p class="account-count-summary">
                    表示 {{ filteredUsers.length }} / {{ tabUsers.length }} 件（全 {{ usersList.length }}）
                </p>
            </div>
        </div>
        
        <div style="flex: 1;overflow: hidden;">
            <div id="admin-account-scroll" class="user-record-parent" ref="scrollContainer">
                <div class="admin-account-center-inner" :key="item.id" v-for="item in filteredUsers">
                    <div class="account-card-menu">
                        <ItemMenu :items="[{title: '編集', action: () => openModal(item)}]" fit="admin-account-scroll"/>
                    </div>
                    <div class="account-card-head">
                        <UserPanel :disableInstant="true" size="26" :title="item.name" :user="item" imgClass="userNormalIcon"/>
                        <span class="account-card-name">{{ item.name }}</span>
                    </div>
                    <p class="account-card-email" :title="item.email">{{ item.email }}</p>
                    <div class="account-card-meta">
                        <div class="account-card-meta-row">
                            <span class="account-card-label">役職</span>
                            <span class="account-card-value" :title="item.positions?.name">{{ item.positions?.name ? item.positions.name : '—' }}</span>
                        </div>
                        <div class="account-card-meta-row">
                            <span class="account-card-label">営業所</span>
                            <span class="account-card-value" :title="item.offices?.name">{{ item.offices?.name ? item.offices.name : '—' }}</span>
                        </div>
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
                    :roles="roles"
                    @postFinish="postFinish"
                />
            </div>   
        </Transition>
    </div>
   
</template>
<script setup>
import ItemMenu from '@/components/Global/ItemMenu.vue';
import Filter from '../Icons/Filter.vue';
import UserCreate from './UserCreate.vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import { computed, onMounted, onUnmounted, ref } from 'vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import { useApi } from '@/composables/api';
    const showModalContent = ref(false)
    const editUserData = ref(null)
    const passwordFlag = ref(false)
    const scrollContainer = ref(null)
    const retire = ref(0)
    const on_leave = ref(0)
    const keywords = ref('')
    const searchBarKey = ref(0)
    const selectedPositionId = ref('')
    const selectedOfficeId = ref('')
    const selectedWorkType = ref('')
    const filtersOpen = ref(false)
    const filterRef = ref(null)
    const usersList = ref([])
    const fetch = ref(0)
    const workGroups = ref([])
    const linkables = ref([])
    const positions = ref([])
    const offices = ref([])
    const roles = ref([])
    const workTypeOptions = [
        { value: 0, label: 'フレックス' },
        { value: 1, label: '通常' },
    ]
    const api = useApi()

    onMounted(async() => {
        await getUsers()
        fetch.value++
    })
    const getUsers = async() => {
        const { w, o, p, l, u, r } = await api.get('/get_controllable_users')
        usersList.value = u
        workGroups.value = w
        linkables.value = l
        positions.value = p
        offices.value = o
        roles.value = r ?? []
    }
    const tabUsers = computed(() => {
        return usersList.value.filter(user => user.retire == retire.value && user.on_leave == on_leave.value)
    })
    const filteredUsers = computed(() => {
        const filtered = tabUsers.value.filter(user => {
            if (selectedPositionId.value && String(user.position_id ?? '') !== selectedPositionId.value) {
                return false
            }

            if (selectedOfficeId.value && String(user.office_id ?? '') !== selectedOfficeId.value) {
                return false
            }

            if (selectedWorkType.value && String(user.work_type ?? '') !== selectedWorkType.value) {
                return false
            }

            return true
        })
        if(keywords.value){
            const lowSearch = keywords.value.toLowerCase()
            return filtered.filter(user => {
                const haystack = [
                    user.id,
                    user.name,
                    user.name_kana,
                    user.email,
                    user.positions?.name,
                    user.offices?.name,
                ]
                return haystack.some(val => val != null && String(val).toLowerCase().includes(lowSearch))
            })
        }else{
            return filtered
        }
    })

    // Reflects only the popover filters (search has its own always-visible bar).
    const filtersActive = computed(() =>
        !!(selectedPositionId.value || selectedOfficeId.value || selectedWorkType.value)
    )

    const resetFilters = () => {
        keywords.value = ''
        selectedPositionId.value = ''
        selectedOfficeId.value = ''
        selectedWorkType.value = ''
        searchBarKey.value++
    }

    const onDocumentClick = (event) => {
        if(filtersOpen.value && filterRef.value && !filterRef.value.contains(event.target)){
            filtersOpen.value = false
        }
    }
    onMounted(() => document.addEventListener('click', onDocumentClick))
    onUnmounted(() => document.removeEventListener('click', onDocumentClick))

     
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
        padding: 14px 16px;
        background: var(--background-color);
        display: flex;
        flex-direction: column;
        gap: 7px;
        height: fit-content;
        border: solid thin var(--calendarBorder);
        position: relative;
        font-size: 13px;
        line-height: 1.5;
        min-width: 0;
    }

    .account-card-menu{
        position: absolute;
        top: 8px;
        right: 8px;
    }
    .account-card-head{
        display: flex;
        align-items: center;
        gap: 8px;
        padding-right: 22px;
        min-width: 0;
    }
    .account-card-name{
        font-weight: 600;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .account-card-email{
        margin: 0;
        color: gray;
        font-size: 12px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .account-card-meta{
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
    }
    .account-card-meta-row{
        display: flex;
        gap: 8px;
        min-width: 0;
    }
    .account-card-label{
        color: gray;
        min-width: 42px;
        flex: 0 0 auto;
    }
    .account-card-value{
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Responsive card grid: 1 column on mobile up to a max of 4 on wide screens. */
    .user-record-parent {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
        height: -webkit-fill-available;
        height: -moz-available;
        overflow: hidden auto;
        background: var(--bg3);
        grid-auto-rows: max-content;
        padding: 0 20px 20px;
    }
    @media screen and (min-width: 600px){
        .user-record-parent{ grid-template-columns: repeat(2, 1fr); }
    }
    @media screen and (min-width: 992px){
        .user-record-parent{ grid-template-columns: repeat(3, 1fr); }
    }
    @media screen and (min-width: 1440px){
        .user-record-parent{ grid-template-columns: repeat(4, 1fr); }
    }

    .admin-command-bar{
        margin: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /* Second header row (below tabs): search (grows) + filter toggle + count. */
    .admin-command-tools{
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* className "newChatMemberSearch" makes PostSearchBar's inner box fill 100%. */
    .admin-command-tools :deep(.newChatMemberSearch){
        flex: 1 1 200px;
        min-width: 160px;
    }
    /* Kill the input's default side margins so it aligns flush with the toggle. */
    .admin-command-tools :deep(.newChatMemberSearch .searchBarArea){
        margin: 0 !important;
    }

    .account-count-summary{
        margin: 0;
        font-size: 13px;
        color: gray;
        white-space: nowrap;
    }

    /* ---- filter toggle + popover ---- */
    .filter-toggle-wrap{
        position: relative;
    }
    /* Match the search bar: same height, border and 5px radius for consistency. */
    .filter-toggle{
        position: relative;
        width: 38px;
        height: 31px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: solid thin var(--normalBorder);
        border-radius: 5px;
        background: var(--background-color);
        color: var(--primary-color);
        cursor: pointer;
    }
    .filter-toggle.active{
        border-color: var(--primary-color);
        background: var(--bg3);
    }
    .filter-dot{
        position: absolute;
        top: 5px;
        right: 5px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--primary-color);
    }
    .filter-panel{
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        z-index: 50;
        width: min(300px, calc(100vw - 40px));
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 14px;
        background: var(--background-color);
        border: solid thin var(--calendarBorder);
        box-shadow: #3c40434d 0 1px 2px, #3c404326 0 2px 6px 2px;
    }
    .filter-panel :deep(.newChatMemberSearch){
        width: 100%;
    }

    .account-filter-select{
        width: 100%;
        height: 36px;
        padding: 0 12px;
        border: solid thin var(--calendarBorder);
        background: var(--background-color);
        color: inherit;
    }

    .account-filter-reset{
        width: 100%;
        height: 36px;
        padding: 0 14px;
        border: solid thin var(--calendarBorder);
        background: var(--background-color);
        color: inherit;
        white-space: nowrap;
        cursor: pointer;
    }
</style>
