<template>
    <div class="office-outwrapper">
        <div class="office-wrapper">
            <div class="office-title">
                事務所管理
            </div>
            <table class="office-content">
                <tr>
                    <th>事務所名</th>
                    <th>郵便番号</th>
                    <th>住所</th>
                    <th>電話番号</th>
                    <th>ファックス</th>
                    <th>従業員数</th>
                    <th></th>
                </tr>
                <tr v-for="office in officeList" v-bind:key="office.id">
                    <td>{{ office.name }}</td>
                    <td>
                        <span v-if="office.post_code_1 && office.post_code_2">{{'〒' + office.post_code_1 + '-' + office.post_code_2 }}</span>
                    </td>
                    <td>{{ office.address }}</td>
                    <td>{{ office.tel }}</td>
                    <td>{{ office.fax }}</td>
                    <td>{{ office.employees.length }}</td>
                    <td><button type="submit" @click="officeEdit(office)" class="btn btn-primary account-btn">
                        編集
                    </button></td>
                </tr>
            </table>
        </div>
        <div class="createBoardButton fileNewButton" @click="newOffice()">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <Transition name="modalFade">
            <div class="overlay" v-if="showModalContent">
                <OfficeCreate
                    :editFlag="editFlag"
                    :editOfficeData="editOfficeData" 
                    @postFinish="postFinish"
                    @closeModal="showModalContent = false"
                />
            </div>
        </Transition>
    </div>
</template>
<script>
    import OfficeCreate from './OfficeCreate.vue'
    export default{
        props: ['officeList'],
        data(){
            return{
                showModalContent: false,
                editFlag: false,
                editOfficeData: null,
            }
        },
        methods: {
            newOffice(){
                this.showModalContent = true
                this.editFlag = false
            },
            postFinish(){
                this.showModalContent = false
                this.$emit('getUsers')
                this.editOfficeData = null
            },
            officeEdit(item){
                this.editOfficeData = item
                this.showModalContent = true
                this.editFlag = true
            }
        },
        components: {
            OfficeCreate
        }
    }   
</script>
<style scoped lang="scss">
    .office-title{
        display: none;
    }
    .office-outwrapper{
        margin: 0 15px;
        height: calc(100% - 60px);
        display: block;
        overflow: hidden auto;
    }
    table{
        border: solid 1px #ccc;
        border-collapse:collapse;
        cursor: pointer;
        width: -webkit-fill-available;
    } 
    th {
        padding: 5px;
        border: solid 1px #ccc;
        background-color: var(--background-color);
        font-size: 16px;
      }
      td {
        padding: 5px;
        border: solid 1px #ccc;
        background-color: var(--background-color);
        font-size: 14px;
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
      @media screen and (max-width: 959px){
        .office-outwrapper{
            margin: 0;
            margin-top: 60px;
            margin-bottom: 60px;
            width: 94.5%;
            padding: 0 10px;
        }
        .account-btn{
            width: 80px;
            height: 30px;
        }
        th{
            font-size:12px;
        }
        td{
            font-size:12px;
        }
        .office-title {
            display: block;
            margin-bottom: 20px;
            font-size: 20px;
        }
      }
</style>