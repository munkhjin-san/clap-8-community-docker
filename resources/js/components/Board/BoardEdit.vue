<template>
    <div @mousedown="closeModal" class="overlay" style="z-index: 27;font-size:14px">
        <Form ref="form" v-slot="{ errors }" style="width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;">  
            <div id="editModal" class="chatCreate" ref="editModal" @mousedown.stop>
                                  
                <div style="display:flex;align-items: center;">
                    <div style="font-size: 17px;margin: -10px 0 15px;max-width: 90%;overflow: hidden;text-overflow: ellipsis;line-height: 1.5;" v-html="$t('editGroupTitle', {title : this.item.title})"></div>
                    <div @click="$emit('close')" class="m-close-button">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div style="height: -webkit-fill-available;overflow:hidden auto;">
                    <div class="">
                        <p style="font-size: 14px;margin: 15px 0px;">{{ $t('groupChatTitle') }}</p>
                        <div class="" style="position:relative">                                
                            <Field @input="validateTitle" rules="required|max:100|nameCase" autocomplete="off" class="recordText" v-model="title" type="text" name="title" />   
                            <span class="form-error" style="font-size: 11px;">{{ errors.title }}</span>
                        </div>  
                    </div> 
                    <div>
                        <p style="font-size: 14px;margin: 15px 0px;">{{ $t('Icon') }}</p>
                        <div class="form-border" style="text-align:center;padding: 10px;position:relative">                        
                            <div v-if="tempImage" style="height: auto;max-height:calc(80vh / 2);background:#efefef;width: 100%;margin: auto;position:relative">
                                <div @click="cancelCrop" style="position:absolute;right:10px;top:10px;background:#fff;border-radius:50px;min-width:30px;width:30px;height:30px;cursor:pointer;z-index: 5;display: flex;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;"> 
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width:10px;height:10px;margin:auto;" fill="#000" viewBox="0 0 32 32">
                                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                    </svg>
                                </div>
                                <img id="cropImage" :src="tempImage"> 
                                
                            </div>
                            <div v-else>
                                <p style="margin:auto 10px auto 0;">{{ $t('iconPreview') }}</p>
                                <div style="width: fit-content;padding: 15px;margin: auto;">
                                    <div id="boardIconPreview" class="iconPreview">
                                        <div v-show="!upFileArray.length" id="iconPreviewInnerText" :style="previewFontSize" class="iconPreviewInner" v-html="titleText"></div>
                                        <div v-if="upFileArray.length" style="position:absolute;">
                                            <!-- <img style="height:45px;width:45px;border-radius:50%" :src="$store.state.baseLocation + '/content/board_icon/' + upFileArray[0]"/> -->
                                            <BoardIcon :item="item" :imgClass="'boardNormalIcon'"/>
                                            <div @click.stop="iconDelete()" style="position:absolute; top:-5px;right:-5px;border-radius:50%;background:rgb(181 181 181);">
                                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="padding:4px;fill:#fff;" width="10" height="10" viewBox="0 0 32 32">
                                                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                                </svg>
                                            </div>                                                
                                        </div>
                                    </div>
                                </div>                                    
                                
                                <div style="display: flex;width: fit-content;margin: auto;">
                                    <div class="commentEditButton" style="margin:0">
                                        <label for="boardIcon" class="cursor-pointer" :class="{uploadPassive: upFileArray.length}">
                                            {{ $t('uploadIcon') }}
                                        </label>
                                        <input type="file" name="boardIcon" id="boardIcon" v-on:change="iconSelected" style="display: none;" accept="image/*">
                                    </div>                                       
                                </div>
                            </div>
                        </div> 
                                                    
                    </div>
                    <div>
                        <p style="font-size: 14px;margin: 15px 0px;">{{ $t('privacySettings') }}</p>
                        <div class="form-border" style="padding:10px">
                            
                            <p style="font-size: 14px;margin-bottom: 15px;">{{ $t('newMemberRequest') }}</p>
                            <div style="display: flex;gap: 15px;flex-wrap: wrap;">
                                <div v-for="num in [0, 1, 2]">
                                    <label :for="`chat_pr_st_${num}`" class="check-container privacy-check" style="align-self: center;">
                                        <input v-model="ableJoin" :value="num" :id="`chat_pr_st_${num}`" name="privacy" type="radio">
                                        <span class="checkmark-mini"></span>
                                        {{ $t(privacyTitle(num)) }}
                                    </label>  
                                </div>   
                            </div>   
                            
                            <p style="font-size: 14px;margin: 15px 0px;">{{ $t('messagePermission') }}</p>
                            <div style="display: flex;gap: 15px;flex-wrap: wrap;">
                                <div v-for="num in [0, 1]">
                                    <label :for="`msg_pr_st_${num}`" class="check-container privacy-check" style="align-self: center;">
                                        <input v-model="messageFrom" :value="num" :id="`msg_pr_st_${num}`" name="messagePermission" type="radio">
                                        <span class="checkmark-mini"></span>
                                        {{ $t(permissionTitle(num)) }}
                                    </label>  
                                </div>  
                            </div>
                        </div>
                    </div>
                    
                </div>                
                <div style="text-align: center;margin-top: auto;padding-top: 30px;">
                    <LoaderButton @triggered="cropComplete('edit')" :loading="loader" :content="$t('save')"/>
                </div>        
            </div>
        </Form>
    </div>
</template>

<script>
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import ToolTip from '../Global/ToolTip.vue';
import LoaderButton from '../Global/LoaderButton.vue'
import boardIcon from './Mixed/BoardIcon.vue'
    import { Field, Form  } from 'vee-validate'
    

    
    export default {
        props: ['privateFlag', 'editIndex', 'item'],
        emits: ['reload', 'closeModal'],
        data(){
            return{
                upFileArray: [],
                currentAdmins: [],
                selectedUsers: [],
                selectedPartner: null,
                options: [],
                iconFileInfo: [],
                previewFontSize: null,
                title: '',
                id: null,
                iconDeleteFlag: 0,
                iconId: null,
                titleText: '',
                tempImage: null,
                cropperInstance: null,
                loader: false,
                ableJoin: 0,
                messageFrom: 0,
                vali: false,
                nameVali: true                
            }
        },        
        mounted() {
            if(this.editIndex){
                this.editBoard();
            }
            
        },
        components:{
            ToolTip,
            LoaderButton,
            Field,
            Form,
            boardIcon
        },
        watch: {
            title(after, before){                
                this.iconPreviewText()                
            },
        },
        methods:{
            privacyTitle(num){
                return num == 0 ? 
                'approvalNeeded' : num == 1 ? 
                'openPrivacy' : 
                'closedPrivacy'
            },
            permissionTitle(num){
                return num == 0 ? 
                'byAddedTime' : 
                'byAll' 
            },
            closeModal(){
                if (!this.$refs.editModal.contains(event.target)) {
                    this.$emit('close')
                }
            },
            getAllUsers(){
                axios.post('/get_all_members')
                .then(response => {  
                    this.options = response.data
                }).catch(function (error) {                
                                      
                }.bind(this));
            },
            cancelCrop(){
                if(this.cropperInstance){
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                }
                this.tempImage = null 
            },
            iconSelected() {          
                this.tempImage = URL.createObjectURL(event.target.files[0]);


                setTimeout(() => {
                    var image = document.getElementById('cropImage');
                    var width = 300;
                    var height = 300;
                    var container = document.getElementById('uploadContainer');          
                    if(container){
                        // width = container.offsetWidth * 0.8;
                        // height = container.offsetHeight * 0.8;
                        
                    }       

                    if(this.cropperInstance){
                        this.cropperInstance.destroy();
                        this.cropperInstance = null;
                    }            
                    this.cropperInstance = new Cropper(image, {              
                        dragMode: 'move',
                        preview: '.preview',
                        aspectRatio: 1 / 1,
                     
                        viewMode: 1,
                        responsive:true,
                        autoCrop: true,
                        background: false,
                        guides: false,
                        zoomable:false,
                        crop(event) {
                            
                            
                        },            
                    });
                },0)
                
               
                
            },
            iconPreviewText(){
                const no_space = this.title ? this.title.replace(/\s/g, '', '　') : ''                
                switch(true) {
                case (no_space.length == 0):
                    this.titleText = ''
                    break;
                case (no_space.length == 1):
                    this.previewFontSize = 'font-size:22px;margin-bottom:13px;';
                    this.titleText = no_space;
                    break;
                case (no_space.length == 2):
                    this.previewFontSize = 'font-size:17px;';
                    this.titleText = no_space;
                    break;
                case (no_space.length == 3):
                    this.previewFontSize = 'font-size:13px;';
                    this.titleText = no_space;
                    break;
                case (no_space.length == 4):
                    var upper = no_space.slice(0, 2);
                    var lower = no_space.slice(2, 4);
                    this.previewFontSize = 'font-size:13px;';
                    this.titleText = `${upper}<br>${lower}`;
                    break;
                case (no_space.length == 5):
                    var upper = no_space.slice(0, 3);
                    var lower = no_space.slice(3, 5);
                    this.previewFontSize = 'font-size:12px;';
                    this.titleText = `${upper}<br>${lower}`;
                    break;
                case (no_space.length >= 6):
                    var upper = no_space.slice(0, 3);
                    var lower = no_space.slice(3, 6);
                    this.previewFontSize = 'font-size:12px;';
                    this.titleText = `${upper}<br>${lower}`;
                    break;
                default:                   
                }                
            },
            iconDelete(){
                this.iconDeleteFlag = 1;
                this.upFileArray = [];
                this.iconId = null;
                this.iconPreviewText();            
            },
            editBoard(){
                
                this.id = this.item.id
                this.title = this.item.title
                var path = 'board_' + this.item.icon_id + '.' + this.item.icons.extension;
                this.upFileArray[0] = path; 
                this.ableJoin = this.item.able_join;
                this.messageFrom = this.item.message_from;
            },
            async validateTitle(){
                const result = await this.$refs.form.validate();
            },
            async boardEditSend(){
                const result = await this.$refs.form.validate();
                if(!result.valid) {
                    this.loader = false;
                    return
                }
                const UpFileArray = this.upFileArray;                   
                const editId = this.id;
                const noSpaceEdit = this.title.replace(/\s/g, '', '　')
                const titleLenEdit = noSpaceEdit.length;
                                 
                axios.post('/messages_edit_api', {
                    icon_delete_flag: this.iconDeleteFlag,
                    str_len : titleLenEdit,
                    title_no_space: noSpaceEdit,
                    icon_id: this.iconId,
                    id: this.id,
                    title: this.title,
                    file: UpFileArray,
                    message_from: this.messageFrom,
                    able_join: this.ableJoin
                }).then(response => {   
                    setTimeout(() => {
                        if(response.data == 'saved'){
                            this.$emit('reload')
                        }else if(response.data == 'error'){                            
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: 'Алдаа гарлаа',
                                closeButton: true, 
                                autoClose: true,

                            }) 
                        }
                        this.loader = false
                    }, 500)
                    
                }).catch(function (error) {
                    if (error.response) this.errorToast($t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast($t('commonError'))
                    else this.errorToast($t('commonError') + error.message)   
                    this.loader = false                         
                }.bind(this));;
            },
            cropComplete(flag){
                if(this.loader) return
                if(this.cropperInstance){
                    this.loader = true
                    
                    this.cropperInstance.getCroppedCanvas({

                    }).toBlob((blob) => {                        
                        const formData = new FormData()
                        formData.append('file', blob)            
                        axios.post('/icon_up_api', formData)
                        .then(response => {
                            if(response.data.icon_id && response.data.set_path){
                                this.upFileArray = []
                                this.upFileArray[0] = response.data.set_path;                        
                                this.iconId = response.data.icon_id;                            
                                flag == 'edit' ? this.boardEditSend() : this.loader = false  
                            }else{
                                this.errorToast('Алдаа гарлаа.')
                            }
                           
                        }).catch(function (error) {
                            if(error.response.status == 413){
                                this.errorToast(this.$t('imageIsTooBig'))
                                this.cancelCrop()
                            }else{
                                this.errorToast(this.$t('errorOnUpload'))  
                            }
                            this.loader = false                         
                        }.bind(this));                      
                    });
                }else{
                    this.loader = true
                    flag == 'edit' ? this.boardEditSend() : false
                }
            },            
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: true, 
                    autoClose: true,
                    answers: ['OK']

                }) 
            },
        }
    }
</script>
<style lang="scss">
    .form-error{
        color: tomato;
        position: absolute; 
        bottom: -10px;
        font-size: 11px;
        color: inherit;
        left: 0;
    }
</style>
