<template>
    <div class="overlay" @mousedown="closeModal(false)">                         
        <div class="chatCreate scrollable" @mousedown.stop>     
            <div class="recordFormTitle" style="display:flex">
                <p>{{ editTarget ? `${appNameJp}を編集する` : `新しい${appNameJp}を作成する`}}</p>
                <div class="cursor-pointer" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
        
            <div v-if="appName == 'challenge'" class="si-box" style="position:relative;">
                <div>
                    <p :class="['form-title-small', {'form-title-active' : switchEntrySelectModel}]">楽アワードエントリー</p>
                </div>
                <div v-if="!editTarget || (editTarget && editTarget.award_entry == 0)" class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input type="checkbox" id="switchEntrySelect" v-model="switchEntrySelectModel">
                    <label for="switchEntrySelect" style="min-width: 80px;" class="cursor-pointer"><span></span></label>
                    <div id="swImgEntrySelect">
                    </div>
                </div>
                <div v-if="editTarget && editTarget.award_entry == 1" class="selectSwitchArea" style="display: flex;width: 100%;">
                    <span style="padding: 5px; margin-right: 10px;">現在エントリー中です。</span>
                </div>    
            </div>        
        
            <div class="si-box">
                <FormShortText
                    :initialValue="title"
                    ref="recordTitle"
                    placeHolder="タイトルを入力（必須）"
                    uId="recordTitle"
                    name="recordTitle"
                    rules="required|max:48"
                    label="タイトル"
                    @setValue="val => title = val"
                />
            </div>
                    

            <div class="si-box" v-if="appName == 'challenge' || appName == 'nice'">
                <UserSelector 
                    :selfInclude="selfInclude" 
                    :initialSelected="to_users"
                    :placeHolder="appName == 'challenge' ?  'プレイヤー選択（必須）' : appName == 'nice' ? '宛先選択（必須）' : ''"
                    rules="required"
                    @setUser="val => to_users = val"
                    uId="recordUsers"
                    name="recordUsers"
                    ref="recordUsers"
                />
            </div>

            <div v-if="appName == 'challenge'" class="si-box">                
                <FormLongText
                    :initialValue="content_rule"   
                    ref="recordBody"
                    :placeHolder="`${appNameJp}内容を入力（必須）`"
                    uId="recordBody"
                    name="recordBody"
                    rules="required|max:2000"
                    label="タイトル"
                    @setValue="val => content_rule = val"
                />
            </div>

            <div v-else class="si-box">                
                <FormLongText
                    :initialValue="content"   
                    ref="recordBody"
                    :placeHolder="`${appNameJp}内容を入力（必須）`"
                    uId="recordBody"
                    name="recordBody"
                    rules="required|max:2000"
                    label="タイトル"
                    @setValue="val => content = val"
                />
            </div>
        
        
            <div class="si-box" v-if="appName == 'challenge'">                   
                <FormLongText
                    :initialValue="content_goal"  
                    ref="recordRule"
                    :placeHolder="`達成条件（必須）`"
                    uId="recordRule"
                    name="recordRule"
                    rules="required|max:2000"
                    label="タイトル"
                    @setValue="val => content_goal = val"
                />                    
            </div>

            <div class="si-box" v-if="appName == 'challenge'">
                <p class="form-lbl" style="font-size: 14px;">実施期間（必須）</p>
                <div style="display:flex;margin-top: 10px;position: relative;width:100%">
                    <DatePicker
                        :initialValue="date_start"
                        ref="recordDateStart"
                        uId="recordDateStart"
                        name="recordDateStart"
                        @setValue="val => date_start = val"
                    />
                    <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                    <DatePicker
                        :initialValue="date_end"
                        ref="recordDateEnd"
                        uId="recordDateEnd"
                        name="recordDateEnd"
                        @setValue="val => date_end = val"
                    />                       
                </div>
                <span v-if="dateComparsionError.hasError" class="form-error" style="font-size: 12px;color:tomato;position: absolute; bottom: -15px">{{ dateComparsionError.message }}</span>       
            </div>
                    
            <div class="si-box">
                <TagSelector 
                    placeHolder="タグ選択"
                    :initialValue="tags"
                    @updated="val => tags = val"
                />
            </div>
            
            <div class="si-box">
                <FormFileUploader
                    :initialValue="uploadedFiles"
                    @updated="val => uploadedFiles = val"
                />
            </div>
        
            <div class="si-box">
                <FormShortText
                    :initialValue="referrer"  
                    placeHolder="参照元URLを入力"
                    uId="recordUrl"
                    name="recordUrl"
                    rules=""
                    label="タイトル"
                    @setValue="(val) => referrer = val"
                />
            </div>        
                    
                    
            <div class="si-box">
                <LoaderButton @click="createSend" :loading="processing" content="投稿する"/>
            </div>               
        
        </div>
    </div>      
</template>

<script>      
import FormShortText from '../Global/FormShortText.vue'
import FormLongText from '../Global/FormLongText.vue'
import UserSelector from '../Global/UserSelector.vue'
import { Field  } from 'vee-validate'
import TagSelector from '../Global/TagSelector.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import DatePicker from '../Global/DatePicker.vue'
import FormFileUploader from '../Global/FormFileUploader.vue'
import moment from 'moment'
    export default {
        props: ['formIs', 'currentStatus','sharedFrom', 'filesToShare', 'appNameJp', 'appName', 'editTarget'],
        data (){
            return {
                title: this.editTarget && this.editTarget.title ? this.editTarget.title : "",
                content: this.editTarget && this.editTarget.content ? this.editTarget.content : "",
                content_rule: this.editTarget && this.editTarget.content_rule ? this.editTarget.content_rule : "",
                content_goal: this.editTarget && this.editTarget.content_goal ? this.editTarget.content_goal : "",
                to_users: this.editTarget && this.editTarget.to_users ? this.editTarget.to_users : this.appName == 'challenge' ? [this.$store.state.user] : [],
                referrer: this.editTarget && this.editTarget.referrer ? this.editTarget.referrer : "",
              
                tags: this.editTarget && this.editTarget.tags ? this.editTarget.tags : [],    
                date_start: this.editTarget && this.editTarget.date_start ? this.editTarget.date_start : "",
                date_end: this.editTarget && this.editTarget.date_end ? this.editTarget.date_end : "",
                processing: false,
                switchEntrySelectModel: false,
                uploadedFiles: this.editTarget && this.editTarget.files ? this.editTarget.files : [],
            }
        },
        components:{
            FormShortText, 
            FormLongText, 
            UserSelector, 
            Field, 
            TagSelector, 
            LoaderButton, 
            DatePicker, 
            FormFileUploader
        },
        computed: {           
            
            selfInclude(){
                return this.appName == 'challenge' ? true : false
            },
            dateComparsionError(){
                if(this.date_start && this.date_end){

                    const wrongDuration = moment(this.date_start).isAfter(this.date_end, 'day');                    
                    return{
                        hasError: wrongDuration,
                        message: wrongDuration ? '終了日は開始日より前にすることはできません。' : ''
                    }                       

                }else{
                    return {
                        hasError: false,
                        message: ''
                    }
                }
            }

        },
        methods : {
            async validation(){               
                
                try {                    
                    
                    let checkRef = ['recordTitle', 'recordBody'];
                    if(this.appName == 'challenge'){
                        checkRef.push('recordRule', 'recordDateStart', 'recordDateEnd', 'recordUsers')
                    }
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
            async createSend(){
                
                this.processing = true
                const valid = await this.validation()
                if(!valid){
                    this.processing = false
                    return
                }
                try {
                    
                    const params = {
                        edit_id: this.editTarget ? this.editTarget.id : null,
                        to_users: this.to_users.length ? this.to_users.map(ob => ob.id) : [], 
                        title: this.title, 
                        content_rule: this.content_rule, 
                        content_goal: this.content_goal, 
                        date_start: this.date_start, 
                        date_end: this.date_end,  
                        tags: this.tags.length ? this.tags.map(ob => ob.text) : [], 
                        file_ids : this.uploadedFiles.length ? this.uploadedFiles.map(ob => ob.id) : [], 
                        referrer: this.referrer, 
                        path: this.appName,
                        content: this.content,
                        award_entry: this.switchEntrySelectModel ? 1 : 0
                    }
            
                    axios.post('post_add_record',params)
                    .then(response => setTimeout(() => {this.closeModal(true)},0))
                    .catch(function (error) {
                        if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                        else if (error.request) this.errorToast('エラーが発生しました。')
                        else this.errorToast('エラーが発生しました。 ' + error.message)   
                        this.$store.commit('setUrlMessageId', null)      
                        this.processing = false                    
                    }.bind(this));
                    
                } catch (error) {
                    console.error('Error fetching data:', error);
                    this.processing = false
                }
            },
            closeModal(flag){
                this.processing = false
                this.$emit('postFinish',flag);              
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
        },
    }
    
</script>
<style>

    .post-error{
        bottom: -12px !important;
    }
    .selectSwitchArea {
        line-height    : 32px;            
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
    .selectSwitchArea input[type="checkbox"]{
        display        : none;            /* チェックボックス非表示 */
    }

    /* === チェックボックスのラベル（標準） ==================== */
    .selectSwitchArea label{
        display        : block;               /* ボックス要素に変更 */
        box-sizing     : border-box;          /* 枠線を含んだサイズ */
        height         : 32px;                /* ボタンの高さ       */
        /*border         : 2px solid #000000;*/   /* 未選択タブのの枠線 */
        border-radius  : 30px;                /* 角丸               */
        background     : var(--check-inactive);
    }

    /* === チェックボックスのラベル（ONのとき） ================ */
    .selectSwitchArea input[type="checkbox"]:checked +label{
                /* 選択タブの枠線     */
        background     : #424242;
    }

    /* === 表示する文字（標準） ================================ */
    .selectSwitchArea label span:after{
        content        : "OFF";               /* 表示する文字       */
        padding        : 0 0 0 38px;          /* 表示する位置       */
        color          : #000000;             /* 文字色             */
    }

    /* === 表示する文字（ONのとき） ============================ */
    .selectSwitchArea input[type="checkbox"]:checked + label span:after{
        content        : "ON";                /* 表示する文字       */
        padding        : 0 40px 0 10px;          /* 表示する位置       */
        color          : #ffffff;             /* 文字色             */
    }

    /* === 丸部分のSTYLE（標準） =============================== */
    .selectSwitchArea #swImgEntrySelect{
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
    .selectSwitchArea input[type="checkbox"]:checked ~ #swImgEntrySelect{
        transform      : translateX(46px);    /* 丸も右へ移動       */
        background     : #fff;             /* カーソルタブの背景 */
        border: 1px solid #ccc;
    }
    .switchElement{
        width: 100%;
    }
    .switchLabel{
        width: calc(100% / 5 - 30px);
        text-align: left;
        line-height: 30px;
    }
    .form-title-small{
        white-space: nowrap;
        color:gray;
        font-size:14px;
        transition: color 0.3s ease;
    }
    .form-title-active{
        color: var(--primary-color);
    }

</style>
    
    
    
    
    
    