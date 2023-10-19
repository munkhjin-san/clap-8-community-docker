<template>
    <div class="position-outwrapper">
        <div class="position-wrapper">
            <div v-for="(position,index) in positionList" :key="index">
                <div class="employees-wrapper" v-if="position.employees.length">
                    <div class="employees" v-if="position.id != 14" v-for="(user, index) in position.employees" :key="index">
                        
                        <div class="icon-wrapper">
                            <UserIcon :user="user" imgClass="userNormalIcon" size="30"/>
                        </div>
                        <div>
                            <p class="employee-pos">{{position.name}}</p> 
                            <p class="employee-name">{{user.name}}</p>
                        </div>
                        
                    </div>
                </div>
                
            </div>
            
            
            <!-- <table class="position-content">
                <tr>
                    <th>表示順</th>
                    <th>役職名</th>
                    <th>人数</th> -->
                    <!-- <th>既定権限</th> -->
                    <!-- <th></th>
                    
                </tr>
                <draggable forceFallback="true" dragClass="view" filter=".ignore" item-key="name" :list="positionList" @end="handleDragEnd" :options="{animation:100}" tag="tbody">
                    <template #item="{ element }">
                        <tr>
                            <td>{{ element.sort_flag }}</td>
                            <td class="name">{{ element.name }}</td>
                            <td class="user">{{ element.employees.length }}</td>
                            <td class="ignore" ><button type="submit" @click="positionEdit(element)" class="btn btn-primary account-btn">
                                編集
                            </button></td>
                        </tr>
                    </template>
                </draggable>
            </table> -->
        </div>
        <!-- <div :style="{bottom : $store.state.mobile ? '70px' : '30px', position:'fixed'}" class="createBoardButton fileNewButton" @click="newPosition()">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div> -->
        <!-- <Transition name="modalFade">
            <div id="overlay" v-if="showModalContent">
                <PositionCreate
                    :editFlag="editFlag"
                    :editPositionData="editPositionData" 
                    :positionData="position_list"
                    @postFinish="postFinish"
                />
            </div>
        </Transition> -->
        
    </div>
</template>
<script>
import draggable from 'vuedraggable'
import UserIcon from '../Board/Mixed/UserIcon.vue'
export default{
    props: ['positionList'],
    components: {
         draggable,
         UserIcon
     },
    data(){
        return {
            showModalContent: false,
            editPositionData: null,
            editFlag: false,
            dragging: false
        }
    },
    
    methods: {
        postFinish() {
            this.showModalContent = false
            this.getPositions()
            this.editPositionData = null
        },
        newPosition(){
            this.showModalContent = true
            this.editFlag = false
        },
        positionEdit(item){
            this.editPositionData = item
            this.showModalContent = true
            this.editFlag = true
        },
        handleDragEnd(e) {
            let new_index = []
            for(let i = 0; i < this.position_list.length; i++){
                const d = {
                    new_sort_flag: i + 1,
                    id: this.position_list[i].id,
                    name: this.position_list[i].name
                }
                new_index.push(d)
                console.log(new_index)
            }
            
            axios.post('/position_sort', { new_list : new_index }).then(response => {
                this.getPositions()
            }).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                    this.processing = false                      
                }.bind(this));
        },
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']
            })   
        },
    },
}
</script>
<style scoped lang="scss">
.icon-wrapper{
    height: 100%;
    display: flex;
    align-items: center;
    padding: 0 10px;
    border-right: solid thin var(--formBorder);
}
.employee-pos{
    padding: 10px;
    background-color: var(--calendarToday);
}
.employee-name{
    padding: 10px;
}
.employees{
    text-align:center; 
    border: solid thin var(--formBorder);
    white-space:nowrap;
    display: flex;
}
.employees-wrapper{
    display:flex;
    justify-content:center;
    margin-bottom:20px;
    gap:20px;
    flex-wrap:wrap;
    color:var(--primary-color);
}
.position-wrapper{
    background-color:var(--background-color); 
    padding:10px;
}
.admin-position-title{
    display: none;
}
.position-outwrapper{
    height: calc(100% - 60px);
    display: block;
    overflow: hidden auto;
}
table{
    border: solid 1px #ccc;
    border-collapse:collapse;
    cursor: pointer;
} 
th {
    padding: 5px;
    border: solid 1px #ccc;
    background-color: #eee;
  }
  td {
    padding: 5px;
    border: solid 1px #ccc;
    background-color: #fff;
  }
  .view td{
    opacity: 1;
    width: 10%;
  }
.view td.name{
    width: 30%;
}
.view td.user{
    width: 8%;
}
.view td.title{
    width:40%;
}
.account-btn{
    display: block;
    width: 120px;
    height: 40px;
    text-align: center;
    color: #000;
    background-color: #e2e2e2;
  }
  @media screen and (max-width: 959px){
    .position-outwrapper{
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
    .admin-position-title{
        display: block;
        font-size:20px;
        margin-bottom: 20px;
    }
  }
</style>