<template>
    <div @mousedown="closeTaskModal(false)" class="overlay" style="z-index:24">        
        <div class="chatCreate scrollable" style="position:relative" @mousedown.stop>            
            
            <Form ref="validationObserver" v-slot="{ errors }">
                <div class="recordFormTitle" style="display:flex;">
                    <h1 style="font-size: 17px;margin-bottom: 15px;">{{ editTaskData ? $t('editTask') : $t('createTask')}}</h1>
                    <div style="margin-left:auto;display: flex;align-items: center;"> 
                        <div @click="closeTaskModal(false)" class="m-close-button">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>
                        </div>                                         
                    </div>
                </div>
                <!-- <span class="form-lbl" for="taskTitle">{{$t('title')}}</span> -->
                <div style="margin-top:10px;margin-bottom:20px;position: relative;">
                    <div>   
                        <span :class="{smallPlc : $store.state.activeInput == 'taskTitle'|| (title.length)}" class="form-plc">{{$t('title')}}</span> 
                                    
                        <Field 
                            autocomplete="off" 
                            id="taskTitle" 
                            class="recordText slide-plc" 
                            v-model="title" 
                            type="text" 
                            name="title" 
                            data-action="" 
                            rules="required|max:100" 
                            @focus="$store.commit('setActiveInput', 'taskTitle')"
                            @blur="$store.commit('setActiveInput', '')"
                        />
                        
        
                    </div>
                    <span class="valid-error">{{ errors.title }}</span>
              
                </div>             
                <div class="switchElement" style="margin-bottom: 20px;position:relative">
                    <div class="switchLabel">
                        <p class="form-lbl" style="white-space: nowrap;">{{$t('chooseAll')}}</p>
                    </div>
                    <div class="memberSelectSwitchArea" style="display: flex;width: 100%;">
                        <input @change="selectAllUser" type="checkbox" id="switchMemberSelect">
                        <label for="switchMemberSelect" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                        <div @click="selectAllUser" id="swImgMemberSelect" class="cursor-pointer">
                        </div>                             
                    </div>
                    
                </div> 
                <div style="position:relative">
                    <span :class="{smallPlc : $store.state.activeInput == 'taskUserSelector'|| (qualified_users.length)}" v-show="!qualified_users.length" class="form-plc">{{$t('chooseMember')}}</span> 
                    <v-select 
                        label="name"
                        class="userSelectArea taskUserSelecArea" 
                        v-model="qualified_users" 
                        name="qualified_users" 
                        :options="qualifiedUsersOptions"
                        multiple
                        @search:focus="$store.commit('setActiveInput', 'taskUserSelector')"
                        @search:blur="$store.commit('setActiveInput', '')"
                        :inputId="'taskUserSelector'"
                        :components="{Deselect}"
                    >
                        <template #selected-option="option">
                            <div style="display: flex;align-items: center;gap:10px;font-size: 13px;padding: 5px 0;margin-right: 5px;">
                                <UserIcon :user="option" imgClass="userMidIcon"/>
                                <p>{{ option.name }}</p>
                            </div>
                        </template>
                        <template #no-options="{ search, searching, loading }">
                            <span style="font-size: 13px;opacity: 0.5">{{ $t('noMembersFound') }}</span>
                        </template>
                        <template slot="option" slot-scope="option" v-slot:option="option" >
                            <div style="display: flex;align-items: center;gap:10px;font-size: 13px;padding: 5px 0;">
                                <UserIcon :user="option" imgClass="userMidIcon"/>
                                <p>{{ option.name }}</p>
                            </div>
                      
                        </template>
                        
                    </v-select>
                    <span style="position:unset;" v-if="qualified_users.length == 0" class="valid-error">{{$t('required')}}</span>
                </div>
                
                <div style="margin-top: 20px;position:relative">
                    <p class="form-lbl">{{$t('lastDate')}}</p>
                    <div style="display:flex;margin-top: 10px;position: relative;width:100%">
                        <Field class="taskDateTimePicker" :class="{'date-color' : $store.state.dark == true }" name="enddate" type="date" rules="required" v-model="taskEndDate"/>
                        
                    </div>
                    <span class="valid-error">{{ errors.enddate }}</span>
                    
                </div>
                <div style="margin-top: 20px;position:relative;">
                    <p class="form-lbl">{{$t('setTime')}}</p>
                     <div style="display:flex; align-items: center; margin-top:10px;">
                        <div class="timeSelectSwitchArea" style="display: flex;margin:4px 0;">
                            <input @change="setTaskTime" type="checkbox" id="timeSet" :checked="showTime">
                            <label for="timeSet" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                            <div @click="setTaskTime" id="timeSetButton" class="cursor-pointer">
                            </div>
                        </div>
                        <input class="taskDateTimePicker" v-if="showTime" :class="{'date-color' : $store.state.dark == true }" style="margin-left:15px" type="time" v-model="taskEndTime"/>      
                    </div>
                </div>
                <div style="margin-top:20px;">    
                    <!-- <span class="form-lbl" for="taskTitle">{{$t('memo')}}</span>                 -->
                    <div style="margin-top: 10px;position:relative"> 
                        <span :class="{smallPlc : $store.state.activeInput == 'taskMemo'|| (content.length)}" class="form-plc">{{$t('memo')}}</span>                        
                        <textarea 
                            class="recordTextArea slide-plc" 
                            v-model="content" 
                            name="content" 
                            id="taskMemo" 
                            @focus="$store.commit('setActiveInput', 'taskMemo')"
                            @blur="$store.commit('setActiveInput', '')"
                        ></textarea>                        
                    </div>
                    
                </div>  
                    <div v-if="editFlag" class="l-button cursor-pointer" style="margin-top:30px;" @click="editTaskSend()">
                        <span v-if="!loading">{{$t('save')}}</span>
                        <div v-else id="loaderMini">
                            <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                        </div>   
                    </div>
                    <div v-else class="l-button cursor-pointer" style="margin-top:30px;" @click="scheduleAdd()">
                        <span v-if="!loading">{{$t('save')}}</span>
                        <div v-else id="loaderMini">
                            <div class="spinner-mini" style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;"></div>
                        </div> 
                    </div>
            </Form>

        </div>

    </div>
   
</template>

<script>

import moment from 'moment'
import { Field, Form  } from 'vee-validate'
import UserIcon from '../../Mixed/UserIcon.vue';
import { markRaw } from 'vue';



export default {
        props: ['editTaskData', 'editFlag', 'calendarDay'],  
    data() {
      
        return { 
            title: '', 
            palette: [
                '#F7D5D5',
                '#ffd4a8',
                '#F8F2A6',
                '#CEE4D2',
                '#C2D2E4',
                '#D6CFED',
            ],
           colors02: {
                        // "hex": "#E8D8A6",
                        "hex": "#F7D5D5",
                        "source": "hex"
                    },
            
            colorPickerView: true,
            selectedColor: '#F7D5D5',
            qualifiedUsersOptions: [],
            content: '',
            qualified_users: [],  
             
            loading: false,
            taskEndTime: '',
            taskEndDate: moment().add(1,'days').format('YYYY-MM-DD'),
            showTime: false,     
            Deselect: markRaw({
                template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
            })         
        }
    },
    components: {
        // 'compact-picker': Compact, 
        Field, 
        Form,
        UserIcon,
              
    },
    computed:{
        openedBoard(){
            return this.$store.state.activeBoard ? this.$store.state.activeBoard : null
        }
    },
    mounted() {      
        console.log(this.$store.state)
        if(this.$store.state.messageShareToTask){
                setTimeout(() => {
                    this.content = this.$store.state.messageShareToTask.message_text
                    this.$store.commit('setMessageShareToTask', null)
                },0)
            }  
        if(this.calendarDay){
            this.taskEndDate = this.calendarDay
        }
        // if(this.editTaskData){

        // }
        if(!this.editTaskData){
            
            
        }else{
            this.editTask();
        }
        this.pushUserSelect()

    },
    watch: {
        // #20211117_0006
        showTime(newVal, oldVal){
            if(newVal !== this.showTime){
                document.getElementById('timeSet').checked = false;
            }else if(newVal == this.showTime){
                document.getElementById('timeSet').checked = true;
            }
        },
        qualified_users(after, before){
            if(after.length !== this.qualifiedUsersOptions.length){
                document.getElementById('switchMemberSelect').checked = false;
                
            }else if(after.length == this.qualifiedUsersOptions.length){
                document.getElementById('switchMemberSelect').checked = true;
            }
        }
    },    
    methods: { 
        setTaskTime(){
            this.showTime = !this.showTime;
            if(this.showTime){
                this.taskEndTime = moment().format('HH:mm');
            }   
            
        },  
        deleteTaskConfirm(){
            var uniqueChannell = Math.random().toString(36).substring(5);
            var testdata = {question: "タスクを削除しますか。", answer1: "はい", answer2: "いいえ", channel: uniqueChannell};           
                    
            this.$toast(NotifyComponent,{
                toastClassName: "toastConfirm",
                timeout: false, 
                closeButton: false,
                draggable: false,
                closeOnClick: false,
            });
            emitter.emit('confirmQuestion',testdata)                 
            emitter.on(uniqueChannell, (data) => { data == 1 ? this.deleteTask() : false }); 
        },
        deleteTask(){
            axios.post('/task_delete_api', {task_id: this.editTaskData.id}).then(response => {
                this.$emit('taskDeleted')
            });
        },
        updateValue(val){
            this.selectedColor = val.hex
        } ,
        editTask(){
            var timeEdit = moment(this.editTaskData.end_at)
            this.title = this.editTaskData.title;
            this.content = this.editTaskData.remarks;
            this.taskEndDate = moment(this.editTaskData.end_at).format('YYYY-MM-DD') 
            const timeSet = moment(this.editTaskData.end_at).format('HH:mm')           
            if(timeSet !== '12:00' && timeSet !== '00:00'){
                this.taskEndTime = timeSet
                this.showTime = true
            }
            this.selectedColor = this.editTaskData.color
            var editOptions = [];
            for(var i in this.editTaskData.task_users){
                var item = this.editTaskData.task_users[i];
                editOptions.push(item.user);               
            }
            this.qualified_users = editOptions;
            
        }, 
        editTaskSend:async function(){
            const result = await this.$refs.validationObserver.validate();
            if (this.qualified_users.length == 0 || !this.title || this.title == '' || this.loading) {
                return;
            }
            if(result.valid){
                const user_id_list = this.qualified_users.map(ob => ob.value)       
                const params = {
                    task_id: this.editTaskData.id,
                    title: this.title,             
                    qualified_users: user_id_list,
                    remarks: this.content,
                    task_end_date: this.taskEndDate,
                    task_end_time: this.taskEndTime,
                    board_id: this.openedBoard.id,
                    color: this.selectedColor
                };

                this.loading = true
                axios.post('/task_edit_api', params ).then(
                    response => {
                        if(response.data !== 'error'){    
                            this.closeTaskModal(true)
                            this.loading = false
                        }
                        
                    }
                );
            }
        },
        closeTaskModal(update) {            
            this.title = null;
            this.content = null; 
            this.$emit('closeTaskModal', update); 
            
        },
        pushUserSelect(){    
            let qualifiedUsersOptions = this.openedBoard.board_to_users.map( item  => item.user);
            if(!this.editFlag){
                const me = qualifiedUsersOptions.filter(ob => ob.id == this.$store.state.user.id)
                this.qualified_users.push(me[0]);
            }            
            this.qualifiedUsersOptions = qualifiedUsersOptions;            
            

        },
        scheduleAdd:async function(){   
            const result = await this.$refs.validationObserver.validate()
            // this.$refs.validationObserver.validate();
            if (this.qualified_users.length == 0 || !this.title || this.title == '' || this.loading) {
                return;
            }
            if(result.valid){

                const user_id_list = this.qualified_users.map(ob => ob.id)
                const params = {
                    title: this.title,             
                    qualified_users: user_id_list,
                    remarks: this.content,
                    task_end_date: this.taskEndDate,
                    task_end_time: this.taskEndTime,
                    board_id: this.openedBoard.id,
                    color: this.selectedColor
                };
                this.loading = true

                axios.post('/add_task_api', params ).then(
                    response => {
                        this.loading = false
                        this.closeTaskModal(true)
                        
                    }
                );
            }
        },
        selectAllUser(){           
            this.qualified_users = event.target.checked ? this.qualifiedUsersOptions : [];
            
        } 
                 
    }
}
</script>
<style>
    .vc-compact {
        float: left;
        width: 500px !important;
        box-sizing: none !important;
        box-shadow: none !important;
        margin-top: 5px !important;
        margin-left: 10px !important;
    }
    .vc-compact-color-item {
        height:30px !important;
        width:30px !important;
    }
    .createModalWindow{
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

     /* === ボタンを表示するエリア ============================== */
    .switchArea {
    line-height    : 26px;                /* 1行の高さ          */
    letter-spacing : 0;                   /* 文字間             */
    text-align     : center;              /* 文字位置は中央     */
    font-size      : 14px;                /* 文字サイズ         */

    position       : relative;            /* 親要素が基点       */
    margin         : auto;                /* 中央寄せ           */
    width          : 80px;               /* ボタンの横幅       */
    /*background     : #fff;*/                /* デフォルト背景色   */
    }

    /* === チェックボックス ==================================== */
    .switchArea input[type="checkbox"] {
    display        : none;            /* チェックボックス非表示 */
    }

    /* === チェックボックスのラベル（標準） ==================== */
    .switchArea label {
    display        : block;               /* ボックス要素に変更 */
    box-sizing     : border-box;          /* 枠線を含んだサイズ */
    height         : 30px;                /* ボタンの高さ       */
    border         : 2px solid #000000;   /* 未選択タブのの枠線 */
    border-radius  : 0px;                /* 角丸               */
    }

    /* === チェックボックスのラベル（ONのとき） ================ */
    .switchArea input[type="checkbox"]:checked +label {
    border-color   : #000000;             /* 選択タブの枠線     */

    }

    /* === 表示する文字（標準） ================================ */
    .switchArea label span:after{
    content        : "月";               /* 表示する文字       */
    padding        : 0 0 0 51px;          /* 表示する位置       */
    color          : #000000;             /* 文字色             */
    }

    /* === 表示する文字（ONのとき） ============================ */
    .switchArea  input[type="checkbox"]:checked + label span:after{
    content        : "日";                /* 表示する文字       */
    padding        : 0 50px 0 0;          /* 表示する位置       */
    color          : #000000;             /* 文字色             */
    }

    /* === 丸部分のSTYLE（標準） =============================== */
    .switchArea #swImg {
    position       : absolute;            /* 親要素からの相対位置*/
    width          : 22px;                /* 丸の横幅           */
    height         : 22px;                /* 丸の高さ           */
    background     : #000000;             /* カーソルタブの背景 */
    top            : 4px;                 /* 親要素からの位置   */
    left           : 4px;                 /* 親要素からの位置   */
    border-radius  : 0px;                /* 角丸               */
    transition     : .2s;                 /* 滑らか変化         */
    color          : #ffffff;
    line-height    : 22px;
    }

    /* === 丸部分のSTYLE（ONのとき） =========================== */
    .switchArea input[type="checkbox"]:checked ~ #swImg {
    transform      : translateX(49px);    /* 丸も右へ移動       */
    background     : #000000;             /* カーソルタブの背景 */
    }



                                        /* <div class="memberSelectSwitchArea">
                                            <!-- <input type="checkbox" id="switch1" v-model="switchCalendarSlideModel" v-on:change="switchCalendarSlide()"> -->
                                            <input type="checkbox" id="switchMemberSelect">
                                            <label for="switchMemberSelect" class="cursor-pointer"><span></span></label>
                                            <div id="swImgMemberSelect">
                                                <!-- <template v-if="switchCalendarSlideModel == true">31</template>
                                                <template v-else>7</template> -->
                                            </div>
                                        </div> */






    /* フォーム用　スイッチボタン ボードメンバー連携,非公開  */


    /* === ボタンを表示するエリア ============================== */
    .memberSelectSwitchArea,
    .releaseSelectSwitchArea,
    .timeSelectSwitchArea,
    .schtypeSelectSwitchArea {
    line-height    : 32px;                /* 1行の高さ          */
    letter-spacing : 0;                   /* 文字間             */
    text-align     : center;              /* 文字位置は中央     */
    font-size      : 14px;                /* 文字サイズ         */
    position       : relative;            /* 親要素が基点       */
    /*margin         : auto;*/                /* 中央寄せ           */
    width          : 80px;               /* ボタンの横幅       */
    /*background     : #fff;*/                /* デフォルト背景色   */
    margin-top: 20px;
    }

    /* === チェックボックス ==================================== */
    .memberSelectSwitchArea input[type="checkbox"] ,
    .releaseSelectSwitchArea input[type="checkbox"] ,
    .timeSelectSwitchArea input[type="checkbox"] ,
    .schtypeSelectSwitchArea input[type="checkbox"] {
    display        : none;            /* チェックボックス非表示 */
    }

    /* === チェックボックスのラベル（標準） ==================== */
    .memberSelectSwitchArea label,
    .releaseSelectSwitchArea label,
    .timeSelectSwitchArea label,
    .schtypeSelectSwitchArea label {
    display        : block;               /* ボックス要素に変更 */
    box-sizing     : border-box;          /* 枠線を含んだサイズ */
    height         : 32px;                /* ボタンの高さ       */
    /*border         : 2px solid #000000;*/   /* 未選択タブのの枠線 */
    border-radius  : 30px;                /* 角丸               */
    background     : #e6e6eb;
    }

    /* === チェックボックスのラベル（ONのとき） ================ */
    .memberSelectSwitchArea input[type="checkbox"]:checked +label,
    .releaseSelectSwitchArea input[type="checkbox"]:checked +label,
    .timeSelectSwitchArea input[type="checkbox"]:checked +label,
    .schtypeSelectSwitchArea input[type="checkbox"]:checked +label {
    border-color   : #000000;             /* 選択タブの枠線     */
    background     : #000000;
    }

    /* === 表示する文字（標準） ================================ */
    .memberSelectSwitchArea label span:after,
    .releaseSelectSwitchArea label span:after,
    .timeSelectSwitchArea label span:after,
    .schtypeSelectSwitchArea label span:after{
    content        : "OFF";               /* 表示する文字       */
    padding        : 0 0 0 38px;          /* 表示する位置       */
    color          : #000000;             /* 文字色             */
    }

    /* === 表示する文字（ONのとき） ============================ */
    .memberSelectSwitchArea input[type="checkbox"]:checked + label span:after,
    .timeSelectSwitchArea input[type="checkbox"]:checked + label span:after,
    .releaseSelectSwitchArea input[type="checkbox"]:checked + label span:after,
    .schtypeSelectSwitchArea input[type="checkbox"]:checked + label span:after{
    content        : "ON";                /* 表示する文字       */
    padding        : 0 50px 0 0;          /* 表示する位置       */
    color          : #ffffff;             /* 文字色             */
    }

    /* === 丸部分のSTYLE（標準） =============================== */
    .memberSelectSwitchArea #swImgMemberSelect,
    .timeSelectSwitchArea #timeSetButton,
    .releaseSelectSwitchArea #swImgReleaseSelect,
    .schtypeSelectSwitchArea #swImgSchtypeSelect {
    position       : absolute;            /* 親要素からの相対位置*/
    width          : 22px;                /* 丸の横幅           */
    height         : 22px;                /* 丸の高さ           */
    background     : #fff;             /* カーソルタブの背景 */
    top            : 4px;                 /* 親要素からの位置   */
    left           : 5px;                 /* 親要素からの位置   */
    border-radius  : 26px;                /* 角丸               */
    transition     : .2s;                 /* 滑らか変化         */
    color          : #ffffff;
    line-height    : 22px;
    border: 1px solid #ccc;
    }

    /* === 丸部分のSTYLE（ONのとき） =========================== */
    .memberSelectSwitchArea input[type="checkbox"]:checked ~ #swImgMemberSelect,
    .timeSelectSwitchArea input[type="checkbox"]:checked ~ #timeSetButton,
    .releaseSelectSwitchArea input[type="checkbox"]:checked ~ #swImgReleaseSelect,
    .schtypeSelectSwitchArea input[type="checkbox"]:checked ~ #swImgSchtypeSelect {
    transform      : translateX(46px);    /* 丸も右へ移動       */
    background     : #fff;             /* カーソルタブの背景 */
    border: 1px solid #ccc;
    }
    /* <div class="switchElement">
    <div class="switchLabel"> */

</style>