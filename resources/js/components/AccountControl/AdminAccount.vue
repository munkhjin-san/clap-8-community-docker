<template>
    <div class="admin-account-outwrapper">
        <div class="filterButton-wrapper">
            <div @click="retire = 0" :class="['admin-button', { rtSelected: retire == 0}]">在籍者</div>
            <div @click="retire = 1" :class="['admin-button', { rtSelected: retire == 1}]">退職者</div>
        </div>   
        <div class="user-record-parent" @scroll="handleScroll" ref="scrollContainer">
            <div class="admin-account-center-inner" :key="item.id" v-for="item in paginatedOrders">
                <div class="account-wrapper">
                    <div style="display:flex; align-items:center;margin-bottom:10px;">
                        <UserIconPreLoad :disableInstant="true" size="30" :title="item.name" :user="item" imgClass="userNormalIcon"/>
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
                    <div style="display:flex;overflow:hidden;margin-right:20px;padding-bottom:10px;" >
                        <p style="white-space:nowrap">メモ:</p>
                        <p style="margin-left:10px;white-space:break-spaces;">{{ item.user_detail ? item.user_detail.memo : ' ' }}</p>
                    </div>
                </div>
                <div class="button-wrapper">
                    <button type="submit" @click="userEdit(item)" class="account-btn cursor-pointer">
                        編集
                    </button>
                </div>        
            </div>
        </div>
        <div class="createBoardButton fileNewButton" @click="newRecord()">
            <!-- :style="{bottom : $store.state.mobile ? '70px' : '30px'}" -->
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>           
        <Transition name="modalFade">
            <div class="overlay" v-if="showModalContent">                      
                <UserCreate 
                    :positionData="positionLabel"
                    :officeData="officeLabel"
                    :editUserData="editUserData"
                    :editFlag="editFlag"
                    :workGroup="workGroup"
                    :passwordFlag="passwordFlag"
                    @postFinish="postFinish"
                />
            </div>   
        </Transition>
    </div>
   
</template>
<script>
import UserSearchBar from './UserSearchBar.vue'
import UserCreate from './UserCreate.vue'
import UserIconPreLoad from '../Board/Mixed/UserIcon.vue'
    export default{
        props: ['searchUser', 'positionLabel', 'officeLabel', 'workGroup'],
        data(){
            return{                
                showModalContent: false,
                editUserData: null,
                editFlag: false,
                retireFlag: false,
                createHidden: false,
                scrollPosition: 0,
                keywords: null,
                passwordFlag: false,
                currentPage: 1,
                maxPerPage: 30,
                scrollContainer: null,
                retire: 0
            }
        },
        mounted(){
            this.scrollContainer = this.$refs.scrollContainer;
        },
        computed: {
            userListSort(){
                return this.searchUser.filter(user => user.retire == this.retire)
            },
            totalResults() {
                return Object.keys(this.userListSort).length;
            },
            pageCount() {
                return Math.ceil(this.totalResults / this.maxPerPage);
            },
            pageOffest() {
                return this.maxPerPage * this.currentPage;
            },
            paginatedOrders() {
                return this.userListSort.slice(0, this.currentPage * this.maxPerPage);
            }
        },
        
        methods: {
            retiredUser(){
                this.retireFlag = true
            },
            activeUser(){
                this.retireFlag = false
            },
            handleScroll() {
                const scrollTop = this.scrollContainer.scrollTop;
                const scrollHeight = this.scrollContainer.scrollHeight;
                const clientHeight = this.scrollContainer.clientHeight;

                if (scrollTop + clientHeight >= scrollHeight) {
                    this.loadMore(); 
                }
            },
            loadMore() {
                this.currentPage += 1;
            },      
            postFinish() {
                this.showModalContent = false;
                this.editUserData = null;
                this.$emit('getUsers')
            },
            
            userMemo(user_detail){
                return user_detail?user_detail.memo:' '
            },
            newRecord() {
                this.openModal();
                this.editFlag = false;
            },
            userEdit(item) {
                this.editUserData = item;
                this.openModal();
                this.editFlag = true;
            },
            openModal: function () {
                this.showModalContent = true;
            },

        },
        watch: {
                
            },
        components:{
            UserIconPreLoad,
            UserSearchBar,
            UserCreate
        }
    }
</script>
<style scoped lang="scss">
   
    .admin-account-outwrapper{
        
        width: 100%;
        height: 100%;
        overflow: hidden;
    }
    
    .rtSelected{
        border: solid thin gray;
        background-color: #000 !important;
    }
    .admin-account-center-inner{
        padding: 15px;
        background: var(--background-color);
        display: flex;
        flex-direction: column;
        height: fit-content;
        position: relative;
    }
    .admin-account-title{
        margin-top: 30px;
        font-size: 20px;
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
      .filter-btn{
        display: block;
        width: 120px;
        height: 40px;
        text-align: center;
        color: #000;
        background-color: #efefef;
        margin-right:20px;
      }
      .filter-btn.is-active{
        background-color: #000;
        color: #fff;
      }
      .filterButton-wrapper{
        display: flex;
        position: absolute;
        top: 0;
        right: 15px;
        gap: 10px;
        height: 60px;
      }
      .user-record-parent {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        height: calc(100% - 105px);
        overflow: hidden auto;
        margin-left:15px;
      }

      @media screen and (max-width: 959px) {
        .user-record-parent{
            grid-template-columns: 100%;
            height: calc(100% - 140px);
        }
        .filterButton-wrapper{
            position: static;
            margin: 0 15px 10px;
            height: auto;
        }
      }
    
</style>