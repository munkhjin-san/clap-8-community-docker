<template>
    <div @mousedown="closeTaskModal(false)" class="overlay" style="z-index:24">        
        <div class="chatCreate scrollable" style="position:relative" @mousedown.stop>            

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

            <div class="si-box">
                <FormShortText
                    :initialValue="title"
                    ref="taskTitle"
                    placeHolder="タイトルを入力（必須）"
                    uId="taskTitle"
                    name="taskTitle"
                    rules="required|max:48"
                    label="タイトル"
                    @setValue="val => title = val"
                />
            </div>

            <div class="si-box">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">{{$t('chooseAll')}}</p>
                </div>
                <div class="memberSelectSwitchArea" style="display: flex;width: 100%;">
                    <input v-model="selecAllMembers" type="checkbox" id="switchMemberSelect">
                    <label for="switchMemberSelect" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                    <div id="swImgMemberSelect" class="cursor-pointer">
                    </div>                             
                </div>                    
            </div> 

            <div class="si-box">
                <UserSelector 
                    :selfInclude="true" 
                    :initialSelected="qualified_users"
                    :placeHolder="$t('chooseMember')"
                    :board="openedBoard"
                    :selectAll="selecAllMembers"
                    rules="required"
                    @setUser="val => qualified_users = val"
                    uId="taskUsers"
                    name="taskUsers"
                    ref="taskUsers"
                />
            </div>

            <div class="si-box">
                <p class="form-lbl" style="font-size: 14px;">{{$t('lastDate')}}</p>
                <div style="display:flex;margin-top: 10px;position: relative;width:100%">
                    <DatePicker
                        :initialValue="taskEndDate"
                        ref="recordDateStart"
                        uId="recordDateStart"
                        name="recordDateStart"
                        @setValue="val => taskEndDate = val"
                    />                    
                </div>                           
            </div>


            <div style="margin-top: 20px;position:relative;">
                <p class="form-lbl" style="font-size: 14px;">{{$t('setTime')}}</p>
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

            <div class="si-box">                   
                <FormLongText
                    :initialValue="content"  
                    ref="taskContent"
                    :placeHolder="$t('memo')"
                    uId="taskContent"
                    name="taskContent"
                    rules="max:2000"
                    label="タイトル"
                    @setValue="val => content = val"
                />                    
            </div>

            <div class="si-box">
                <LoaderButton @click="complete" :loading="loading" content="保存する"/>
            </div> 
        </div>
    </div>   
</template>
<script>

import moment from 'moment'
import UserIcon from '../../Mixed/UserIcon.vue';
import FormShortText from '../../../Global/FormShortText.vue'
import FormLongText from '../../../Global/FormLongText.vue'
import UserSelector from '../../../Global/UserSelector.vue'
import DatePicker from '../../../Global/DatePicker.vue'
import LoaderButton from '../../../Global/LoaderButton.vue';

export default {
        props: ['editTaskData', 'calendarDay'],  
    data() {      
        return { 
            title: this.editTaskData ? this.editTaskData.title : '', 
            content: this.editTaskData ? this.editTaskData.remarks : '', 
            qualified_users: this.editTaskData ? this.editTaskData.to_users : [this.$store.state.user],               
            loading: false,
            taskEndTime: this.editTaskData ?  moment(this.editTaskData.end_at).format('HH:mm:ss') : moment().add(1,'days').format('HH:mm:ss'),
            taskEndDate: this.editTaskData ?  moment(this.editTaskData.end_at).format('YYYY-MM-DD') : moment().add(1,'days').format('YYYY-MM-DD'),
            showTime: this.editTaskData && moment(this.editTaskData.end_at).format('HH:mm:ss') !== '00:00:00' ? true : false, 
            selecAllMembers: this.editTaskData && this.editTaskData.to_users.length == this.$store.state.activeBoard.board_to_users.length ? true : false    
        }
    },
    components: {
        UserIcon,
        FormLongText,
        FormShortText,
        UserSelector,
        DatePicker,
        LoaderButton
              
    },
    computed:{
        openedBoard(){
            return this.$store.state.activeBoard ? this.$store.state.activeBoard : null
        }
    },
    mounted() {      
        if(this.$store.state.messageShareToTask){
            setTimeout(() => {
                this.content = this.$store.state.messageShareToTask.message_text
                this.$store.commit('setMessageShareToTask', null)
            },0)
        }  
    },
    watch: {
        qualified_users(after){
            const allUserCount = this.openedBoard.board_to_users.length

            if(after.length !== allUserCount){
                this.selecAllMembers = false;
                
            }else if(after.length == allUserCount){
                this.selecAllMembers = true;
            }
        }
    },    
    methods: { 
        async validation(){              
                
            try {                    
                
                let checkRef = ['recordDateStart', 'taskUsers', 'taskTitle', 'taskContent'];
                let result = true
                for(const check of checkRef){
                    const exec = await this.$refs[check].$refs[check].validate()
                    result = result * exec.valid
                }                   
                
                return result
            } catch (error) {
                console.error('Error fetching data:', error);
                throw error; // Re-throw the error to handle it further if needed
            }               
            
        },
        async complete(){
            if (this.loading) {
                return;
            }     
            this.loading = true
            const valid = await this.validation()
            if(!valid){
                this.loading = false
                return
            }
            this.taskCreate()
        },
        setTaskTime(){
            this.showTime = !this.showTime;
            if(this.showTime){
                this.taskEndTime = moment().format('HH:mm');
            }   
            
        },  
        closeTaskModal(update) {            
            this.title = null;
            this.content = null; 
            this.$emit('closeTaskModal', update); 
            
        },
        taskCreate(){   
                
            const params = {
                title: this.title,             
                qualified_users: this.qualified_users.map(ob => ob.id),
                remarks: this.content,
                task_end_date: this.taskEndDate,
                task_end_time: this.taskEndTime,
                board_id: this.openedBoard.id,
                edit_id: this.editTaskData ? this.editTaskData.id : null
            };
            

            axios.post('/add_task_api', params ).then(
                response => {
                    this.loading = false
                    this.closeTaskModal(true)
                    
                }
            ).catch(function (error) {
                if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) this.errorToast('エラーが発生しました。')
                else this.errorToast('エラーが発生しました。 ' + error.message)     
                this.loading = false                    
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
            this.processing = false
            
        },   
        selectAllUser(){           
            // this.qualified_users = event.target.checked ? this.qualifiedUsersOptions : [];
            
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