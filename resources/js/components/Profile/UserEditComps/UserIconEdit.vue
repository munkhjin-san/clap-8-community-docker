<template>
    <div class="main-bar">
        <div class="overlay" v-show="iconEditModal" style="z-index:99;">   
            <div class="chatCreate">
                <div class="recordFormTitle" style="display:flex">
                    <h1 style="font-size: 17px;margin: -10px 0 15px;">{{$t('IconChangeTitle')}}</h1>
                    <div @click="closeIconEditModal" class="m-close-button">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>              
                </div>        
                <div id="cropperContainer" class="" style="display:flex;height: 100%;width: 100%;">                 
                        
                    <div class="filedrop-area" v-if="!cropperIs" style="width:100% !important;height:80% !important;display:flex;margin: auto;">
                        <label for="userIcon" class="file-label cursor-pointer">
                            {{$t('uploadIcon')}}
                        </label>
                        <input  type="file" name="userIcon" id="userIcon" v-on:change="preUpload" style="display: none;">
                    </div>
                    <div v-else style="height: auto;min-height:200px;background:var(--bg3);width: 100%;max-height: 80%;margin: auto;">
                        <img style="display:none;" id="hiddenImageWrap" :src="tempImage">
                    </div>                      
                    
                </div>
                <div style="width:100%; margin-top:auto;display:flex;text-align:center">        
                    <button v-on:click="cropComplete()" class="l-button cursor-pointer" style="position:relative;">
                        <span v-if="!sendLoader">{{$t('save')}}</span>
                        <div v-if="sendLoader" id="loaderMini" style="position: absolute;">
                            <div style="border: 4px #ffffff solid;border-top: 4px var(--primary-button) solid;" class="spinner-mini"></div>
                        </div>
                    </button>   
                </div>
            </div>
        </div>
        <div>
            <!-- 20201207 -->
            <div class="profile-icon-content">
                <div id="imageWrap" style="position: relative;width: fit-content;margin: auto;min-height: 120px;">
                    <div @click.stop="iconClickMenu" class="cursor-pointer">
                        <UserIconPreLoad size="120" :title="UserAllData.name" :user="UserAllData" imgClass="profile-image"/>
                    </div>
                    <div id="iconMenuWrap" class="iconChange" v-if="$store.state.menu.name == 'iconMenuWrap' && $store.state.menu.id == 23">
                        <div @click="iconEditModal = true" class="cursor-pointer">{{$t('uploadIcon')}}</div>
                        <div v-if="UserAllData.a_version > 0" @click="iconDeleteConfirm()" class="cursor-pointer">{{$t('deleteIcon')}}</div>
                        
                    </div>
                </div>
            </div>

            <!-- 20201207 -->
            <div class="bar01">
                <div style="font-size: 20px;margin-bottom: 20px;" v-if="UserAllData.name !== null">
                    <p><span>{{UserAllData.name}}</span>
                    
                </p>
                </div>
                <div v-if="$store.state.user.id !== UserAllData.id" style="display: flex;place-content: center;margin-bottom: 20px;">                   

                    <MemberInteraction :user="UserAllData" @reload="$emit('updateUser')" type="button"/>
                    
                </div>
                
                <div v-if="UserAllData.user_detail && UserAllData.user_detail.phone !== null && isAccessible" class="bar02">
                    <p>{{UserAllData.user_detail.phone_prefix}} {{UserAllData.user_detail.phone}}</p>
                </div>
                <div v-if="UserAllData.user_detail && UserAllData.user_detail.email !== null && isAccessible" class="bar03">                       
                    <p>{{UserAllData.user_detail.email}}</p>
                </div>
            </div>
            <div v-if="UserAllData.tags && UserAllData.tags.length" style="padding: 20px">
                <UserTags :tags="UserAllData.tags" :user="UserAllData"/>
            </div>
            <!-- <div @click="showIntroduction = !showIntroduction" style="margin: 0 auto 20px auto " class="secondaryButton">{{ $t('introduceMember', {name: UserAllData.name}) }}</div>
            <div v-if="showIntroduction" class="record" style="display: flex;margin-top: 5px;width: fit-content;margin: 0 auto;">

                
            </div>   -->
            
            
        </div>
    </div>
</template>
<script>
    import { Swiper, SwiperSlide } from 'swiper/vue';
    import 'swiper/css'
    import Cropper from 'cropperjs';
    import UserIconPreLoad from '../../Board/Mixed/UserIcon.vue'
    import 'cropperjs/dist/cropper.css';
    import {
        disableBodyScroll,
        clearAllBodyScrollLocks
    } from 'body-scroll-lock';
    import UserTags from '../UserTags'
    import MemberInteraction from '../../Members/MemberInteraction.vue';
    import UserQRCode from '../UserQRCode.vue';
    export default{
        props: ['UserAllData', 'deviceWidth', 'isAccessible'],
        data(){
            return {
                iconViewModal: false,
                iconEditModal: false,
                cropperIs: false,
                isEnter: false,
                sendLoader: false,
                swiperOption02: {
                    zoom: true,
                    initialSlide: '',
                    pagination: {
                        el: '.swiper-pagination'
                    },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev'
                    }
                },
                tempImage: null,
                uniqueImage: null,
                cropperInstance: null,
                tempLock: false,
                inviteLock: false,
                showIntroduction: false,
            }
        },
        components: {
            UserIconPreLoad,
            Swiper,
            SwiperSlide,
            UserTags,
            MemberInteraction,
            UserQRCode
        },
        computed: {
            // deviceWidthStyle(){
            //     if(this.deviceWidth < 500){
            //         return { width: '50%'}
            //     }
            //     return null
            // }
        },  
        methods: {
            iconClickMenu(){
                if(this.UserAllData.id == this.$store.state.user.id){
                    this.$store.commit('setMenu', {name: 'iconMenuWrap', id: 23})
                }
            },
            cropComplete(){
                if(!this.cropperInstance || this.sendLoader){
                    return;
                }
                this.sendLoader = true;
                this.cropperInstance.getCroppedCanvas({
                    }).toBlob((blob) => {
                    const formData = new FormData();
                    // Pass the image file name as the third parameter if necessary.
                    formData.append('croppedImage', blob/*, 'example.png' */);
                    formData.append('unique_id', this.uniqueImage)            
                    // Use `jQuery.ajax` method for example
                    axios.post('/user_icon_cropped_up_api',formData)
                        .then(response => {
                            
                            this.$emit('getUserInfo');
                            this.iconEditModal = false;
                            this.cropCancel();
                            this.sendLoader = false;
                            this.$emit('updateUser')
                            
                        }).catch(function (error) {
                            if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                            else if (error.request) this.errorToast(this.$t('commonError'))
                            else this.errorToast(this.$t('commonError') + error.message)   
                            this.sendLoader = false;                       
                        }.bind(this));;  
                }/*, 'image/png' */);
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
            preUpload() {
                this.cropperIs = true;
                this.tempImage = URL.createObjectURL(event.target.files[0]);


                setTimeout(() => {
                    var image = document.getElementById('hiddenImageWrap');
                    var width = 300;
                    var height = 300;
                    var container = document.getElementById('cropperContainer');            
                    if(container){
                        width = container.offsetWidth * 0.8;
                        height = container.offsetHeight * 0.8;
                    }            
                    if(this.cropperInstance){
                        this.cropperInstance.destroy();
                        this.cropperInstance = null;
                    }            
                    this.cropperInstance = new Cropper(image, {              
                        dragMode: 'move',
                        preview: '.preview',
                        aspectRatio: 1 / 1,
                        minContainerWidth: width,
                        maxContainerWidth: width,
                        minContainerHeight: height,
                        maxContainerHeight: height,
                        viewMode: 1,
                        responsive:true,
                        autoCrop: true,
                        background: false,
                        guides: false,
                        crop(event) { 
                        },            
                    });
                },0)
            },            
            closeIconEditModal(){
                this.iconEditModal = false;
                this.cropCancel();
            },
            dragEnter: function(){    
                this.isEnter = true;           
            },
            dragLeave: function(){            
                this.isEnter = false;
            }, 
            cropCancel(){
                this.cropperIs = false;
                if(this.cropperInstance){
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                }
                            
            },
            cropImage(which){
                var image = document.getElementById(this.uniqueImage);
                var width = 300;
                var height = 300;
                var container = document.getElementById('cropperContainer');            
                if(container){
                    width = container.offsetWidth * 0.8;
                    height = container.offsetHeight * 0.8;
                }            
                if(this.cropperInstance){
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                }            
                this.cropperInstance = new Cropper(image, {              
                    dragMode: 'move',
                    preview: '.preview',
                    aspectRatio: 1 / 1,
                    minContainerWidth: width,
                    maxContainerWidth: width,
                    minContainerHeight: height,
                    maxContainerHeight: height,
                    viewMode: 1,
                    responsive:true,
                    autoCrop: true,
                    background: false,
                    guides: false,
                    crop(event) { 
                    },            
                });
                this.tempLock = true;
                this.loaderOff();           
            },
            loaderOff(){
                var sp = document.getElementsByClassName('cropper-view-box');
                
                if(!sp.length){
                    setTimeout(function() {
                        this.loaderOff();                       
                    }.bind(this), 300);
                }else{
                    this.uploadSpinner = false;
                }
            },
            iconDeleteConfirm: function(id){
                emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('confirmToDeleteIcon') ,
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                        channel: 'userIconDelete'

                    })            
                    emitter.on('userIconDelete', (data) => { 
                        if(data.answer == this.$t('confirmToAction')){
                            this.defaultIconCreate(id)
                        }
                    });        
            }, 
            defaultIconCreate(callback){           
                axios.post('/user_icon_create_api',{create: 1})
                    .then(response => {                       
                        
                        this.$emit('updateUser');
                                     
                    }).catch(function (error) {
                        if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                        else if (error.request) this.errorToast('エラーが発生しました。')
                        else this.errorToast('エラーが発生しました。 ' + error.message)                                                 
                    }.bind(this));; 
            },
        },
        watch: {
            iconViewModal(before, after){
                if(this.iconViewModal){
                    const modal = document.querySelector('#modalContent04');
                    disableBodyScroll(modal);
                }else{
                    clearAllBodyScrollLocks()
                }
            },
            iconEditModal(before, after){
                if(this.iconEditModal){
                    const modal = document.querySelector('#modalContent02');
                    disableBodyScroll(modal);
                }else{
                    clearAllBodyScrollLocks()
                }
            }
        },
        mounted(){
            
        }
    }
</script>