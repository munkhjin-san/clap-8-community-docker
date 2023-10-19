<template>
    <div class="overlay" @mousedown="$emit('close', null)">          
        <div class="chatCreate" style="height:auto;max-width: 70%;" @mousedown.stop>            
            <div class="recordFormTitle" style="display:flex">
                <p>ステータス変更</p>
                <div class="cursor-pointer" @click="$emit('close', null)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>   
            <div class="si-box">
                <div style="display: flex;gap: 15px;">
                    <div @click="selectStatus(status.id)" :class="['status-button', {'current-state' : status.id == selected}]" :key="status.id" v-for="status in statuses">{{ status.state }}</div>
                </div>
            </div>

            <div class="si-box" v-if="selected > 0">                
                <FormLongText
                    :initialValue="resultMessage"   
                    ref="recordBody"
                    :placeHolder="`結果発表を入力`"
                    uId="recordBody"
                    name="recordBody"
                    rules="max:2000"
                    label="タイトル"
                    @setValue="val => resultMessage = val"
                />
            </div>
            <div class="si-box" v-if="selected > 0">
                <FormFileUploader
                    :initialValue="uploadedFiles"
                    @updated="val => uploadedFiles = val"
                    path="/post_files"
                />
            </div>
            <div class="si-box">
                <LoaderButton @triggered="update" :loading="processing" content="保存する"/>
            </div>            
        </div>
    </div>  
</template>
<script>
import LoaderButton from '../Global/LoaderButton.vue';
import FormFileUploader from '../Global/FormFileUploader.vue'
import FormLongText from '../Global/FormLongText.vue';
import moment from 'moment';
    export default{
        props: ['record'],
        data(){
            return{
                selected: this.record ? this.record.status_flag : 0,
                uploadedFiles: this.record.result_files && this.record.result_files.length ? this.record.result_files : [],
                resultMessage: this.record.result ? this.record.result : '',
                processing: false
            }
        },
        components: {
            LoaderButton,
            FormFileUploader,
            FormLongText
        },
        computed:{
            statuses(){
                var todayDate = (moment().format("YYYY-MM-DD"));            
                const active_status = todayDate <= this.record.date_end ? '実施中' : '結果待ち'
                return [
                    { id: 0, state :active_status },
                    { id: 1, state : '達成' },
                    { id: 2, state : '未達成' },
                    { id: 3, state : '中止' }
                ]           
            },
        },
        methods: {
            update(){
                const params = {
                    id: this.record.id,
                    status: this.selected,
                    result: this.resultMessage,
                    resultFiles: this.uploadedFiles.map(ob => ob.id)

                }
                axios.post('post_status_update',params)
                .then(response => {
                    this.$emit('close', this.record.id)
                })
                .catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)   
                    this.$store.commit('setUrlMessageId', null)      
                    this.processing = false                    
                }.bind(this));
            },
            selectStatus(id){
                this.selected = id
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
        }
    }
</script>
<style>
    .status-button{
        font-size: 14px;
        border: solid 1px transparent;
        box-sizing: border-box;
        padding: 10px 15px;
        cursor: pointer;
        
    }
    .current-state{
        border: solid 1px var(--hoverBorder);
        background: var(--bg2);
    }
</style>