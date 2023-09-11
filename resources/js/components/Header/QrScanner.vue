<template>
    <div class="overlay">
        <Transition name="slidePop">
            <div v-if="copied" class="copySuccess">    
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 38 32" fill="#fff">
                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                </svg>
                <span>{{ $t('copied') }}</span>
            </div>
        </Transition> 
        <div class="scannerInner">
            <!-- <div v-if="active" class="mask">
                <div class="hole"></div>
            </div> -->
            <div class="camWindow">
                <QrStream :key="cameraKey" v-if="active" @decode="onDecode"/>
                <h1 v-if="!active && (targetUser || targetChat)" style="margin: 20px;color: var(--primary-color);">{{ targetUser ? $t('foundUser') : targetChat ? $t('foundGroup') : '' }}</h1>
                <div v-if="!active && targetUser" class="foundUserCard">                    
                    <div style="display:flex;align-items:center;gap:10px;">
                        <UserIcon :user="targetUser" imgClass="userNormalIcon" size="30"/>
                        <span>{{targetUser.name}}</span>
                    </div>

                    <div style="margin-left: auto;" v-if="targetUser.id !== $store.state.user.id">
                        <MemberInteraction :user="targetUser" :has-alert="false" type="button" @reload="refreshTargetUser" :disable-block="true" :private-override="true"/>

                    </div>
                    

                </div>
                <div v-if="!active && targetChat" class="foundUserCard">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <BoardIcon :item="targetChat.chat" imgClass="userNormalIcon" size="30"/>
                        <span>{{targetChat.chat.title}}</span>
                    </div>
                    <div style="display:flex;margin-left:auto; gap:10px;">
                        
                        <button @click="sendJoinRequest" :class="['commentEditButton' ]">{{$t('joinToChat')}} </button>
                    </div>
                </div>
                <div v-if="loading" class="loadingWindow">
                    <div class="spinner-mini color-change"></div>
                </div>
            </div>
            <div v-if="active" class="my-qr-area" :class="{zoomInQr : enlarge}"> 
                <img v-if="hasQrCode" id="currentQrCode" style="width: auto;height: 80%;margin: 0 auto;" :src="`${this.$store.state.baseLocation}/qr/${this.$store.state.user.q_token}_${this.$store.state.user.id}.png`"/>
                <div style="width: 80%;position: relative;margin: 0 auto">
                    <input style="height: 30px;color:inherit;background-color: #ddd;padding: 0 10px;" disabled class="recordText" type="text" :value="`${this.$store.state.baseLocation}/invite?token=${this.$store.state.user.q_token}&id=${this.$store.state.user.id}`"/>
                    <button style="position: absolute;right: -10px;top: 0;height:32px;" @click="copyUrl" class="commentEditButton">{{$t('copy')}}</button>
                </div>
                
            </div>

            <button v-if="!active" style="margin-top: 13px;padding: 15px;position: absolute; bottom: 25px;left:0;right:0;margin:0 auto" @click="$emit('close')" class="commentEditButton">{{$t('close')}} </button>
            <div v-if="active" class="camWindowClose" @click="$emit('close')">
                <svg fill="#fff" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>
            <div v-if="active" class="camWindowClose" @click="shareTo" style="right: auto;left:15px">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="#fff" viewBox="0 0 32 32">
                    <path d="M32.088 26.495c-0.044-0.117-0.103-0.22-0.161-0.338-0.059-0.103-0.117-0.22-0.176-0.308l-0.103-0.147-0.044-0.073-0.073-0.088c-0.088-0.117-0.191-0.235-0.279-0.352l-0.117-0.132-0.015-0.015c-0.073-0.059-0.132-0.103-0.206-0.161l-0.088-0.059-0.22-0.132c-0.103-0.059-0.206-0.117-0.323-0.176-0.103-0.059-0.22-0.117-0.338-0.161-0.455-0.191-0.969-0.294-1.483-0.308-0.998-0.029-2.011 0.338-2.76 1.028-0.044 0.029-0.088 0.044-0.132 0.015l-2.598-1.321-3.039-1.512-3.039-1.483-3.053-1.468c-1.028-0.484-2.040-0.954-3.068-1.439-0.881-0.411-1.776-0.822-2.672-1.218-0.088-0.044-0.132-0.132-0.132-0.235 0.015-0.117 0.029-0.235 0.029-0.367s0-0.279-0.015-0.411c-0.015-0.088 0.044-0.176 0.132-0.22 0.881-0.396 1.761-0.807 2.642-1.218 1.028-0.484 2.055-0.954 3.068-1.439l3.053-1.468 3.039-1.483 3.039-1.512 2.598-1.321c0.044-0.029 0.103-0.015 0.132 0.015 0.749 0.675 1.761 1.042 2.76 1.028 0.514-0.015 1.028-0.132 1.483-0.308 0.117-0.044 0.22-0.103 0.338-0.147 0.103-0.059 0.22-0.103 0.323-0.176l0.22-0.132 0.088-0.059c0.073-0.044 0.132-0.103 0.206-0.161l0.015-0.015 0.117-0.132c0.103-0.117 0.191-0.235 0.279-0.352l0.073-0.088 0.044-0.073 0.103-0.147c0.073-0.103 0.117-0.205 0.176-0.308s0.117-0.22 0.161-0.323c0.191-0.455 0.308-0.954 0.323-1.483 0.029-1.042-0.382-2.099-1.116-2.862-0.367-0.382-0.807-0.675-1.306-0.895-0.484-0.205-1.028-0.323-1.556-0.323s-1.057 0.103-1.527 0.279-0.91 0.44-1.292 0.719l-0.132 0.088-0.088 0.132c-0.279 0.382-0.543 0.807-0.719 1.292-0.176 0.47-0.279 0.998-0.279 1.527 0 0.117 0 0.235 0.015 0.352 0 0.059-0.029 0.103-0.073 0.117-0.881 0.396-1.747 0.807-2.628 1.218l-3.068 1.439-3.053 1.468-3.009 1.439c-1.013 0.499-2.026 1.013-3.024 1.512-0.851 0.426-1.688 0.851-2.525 1.292-0.147 0.073-0.338 0.059-0.47-0.044-0.294-0.235-0.602-0.426-0.939-0.572-0.484-0.206-1.028-0.323-1.556-0.323s-1.057 0.103-1.527 0.279-0.91 0.426-1.292 0.719l-0.147 0.103-0.088 0.117c-0.294 0.382-0.543 0.807-0.719 1.292-0.176 0.47-0.279 0.998-0.279 1.527 0 1.072 0.455 2.128 1.204 2.862 0.749 0.749 1.82 1.145 2.862 1.116 0.514-0.015 1.028-0.132 1.483-0.308 0.117-0.044 0.22-0.103 0.338-0.147 0.103-0.059 0.22-0.103 0.323-0.176l0.147-0.103 0.073-0.044 0.088-0.073 0.029-0.029c0.103-0.073 0.235-0.088 0.352-0.029 0.881 0.455 1.747 0.895 2.628 1.336 1.013 0.499 2.011 1.013 3.024 1.512l3.039 1.483 3.053 1.468 3.068 1.439c0.866 0.396 1.732 0.807 2.598 1.204 0.059 0.029 0.103 0.088 0.088 0.161-0.015 0.103-0.015 0.206-0.015 0.323 0 0.528 0.103 1.057 0.279 1.527s0.426 0.91 0.719 1.292l0.088 0.132 0.132 0.088c0.382 0.279 0.807 0.543 1.292 0.719 0.47 0.176 0.998 0.279 1.527 0.279s1.057-0.103 1.556-0.323c0.484-0.206 0.939-0.514 1.306-0.895 0.734-0.749 1.145-1.82 1.116-2.862 0-0.499-0.117-0.998-0.308-1.453z"></path>
                </svg>
            </div>
            <!-- <div v-if="active" class="qrUpload">
                <label class="qrUpload" style="cursor: pointer;position:unset" for="qrUploader">{{ $t('uploadQR') }}</label>
                <qr-capture @decode="onDecode" id="qrUploader" ref="qrCaptureComponent" accept="image/png" style="display: none;" class="mb"></qr-capture>
            </div> -->
            
        </div>
    </div>
   
</template>

<script>
import { QrStream, QrCapture, QrDropzone  } from 'vue3-qr-reader';
import UserIcon from '../Board/Mixed/UserIcon'
import BoardIcon from '../Board/Mixed/BoardIcon'
import MemberInteraction from '../Members/MemberInteraction.vue';
    export default {
        props: ['inviting', 'active', 'joining'],
        data(){
            return{
                targetUser: null,
                loading: false,
                friendLoading: false,
                targetChat: null,
                enlarge: false,
                cameraKey: 0,
                copied: false
                
                
            }
        },
        components: {
            QrStream,
            UserIcon,
            QrCapture,
            QrDropzone,
            MemberInteraction
        },
        computed:{
            hasQrCode(){
                return this.$store.state.user && this.$store.state.user.q_token
            },
        },
        mounted() {
            if(this.$refs.qrCaptureComponent){
                const qrCaptureElement = this.$refs.qrCaptureComponent.$el;
                qrCaptureElement.removeAttribute('capture');
                qrCaptureElement.removeAttribute('multiple');
            }
            

            if(this.inviting){
                // this.targetUser = this.inviting
                const url = `${window.location.origin}/invite?token=${this.inviting.q_token}&id=${this.inviting.id}`
                console.log(url)
                this.getUserDetail(url)
            }
            if(this.joining){
                const join_url = `${window.location.origin}/join?token=${this.joining.q_token}&id=${this.joining.id}`
                this.getChatDetail(join_url)
                this.loading = true
            }

        },
        methods: {
            copyUrl(){
                const url = `${this.$store.state.baseLocation}/invite?token=${this.$store.state.user.q_token}&id=${this.$store.state.user.id}`
                navigator.clipboard.writeText(url)
                .then(() => {
                    this.copied = true
                    setTimeout(() => {
                        this.copied = false
                    }, 1500);
                })

            },  
            refreshTargetUser(){
                const url = `${window.location.origin}/invite?token=${this.targetUser.q_token}&id=${this.targetUser.id}`
                this.getUserDetail(url)
            },
            uploadQr(data){
                console.log(data)
            },
            sendJoinRequest(){
                if(this.targetChat && this.targetChat.isMember){
                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('alreadyJoinedGroupChat'),
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('openGroupChat'), this.$t('cancelToAction')],
                        channel: uniqueChannell

                    })            
                    emitter.on(uniqueChannell, (data) => {
                        if(data.answer == this.$t('openGroupChat')){
                            this.openChat(this.targetChat.chat.id)
                        }
                    });
                    return;
                    
                    return;
                }
                const params = {
                    id: this.targetChat.chat.id,

                }
                axios.post('/members_join_request', params).then(response => { 
                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t(response.data.message),
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK'],
                        channel: uniqueChannell

                    })            
                    emitter.on(uniqueChannell, () => this.$emit('close'));
                    emitter.emit('notifyUpdateCompleted')               
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + this.$t(error.message))   
            
                }.bind(this));

            },
            sendChatRequest(){
                if(this.targetUser && this.targetUser.hasChat){
                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('alreadyHasPrivateBoard'),
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('openPrivateChat'), this.$t('cancelToAction')],
                        channel: uniqueChannell

                    })            
                    emitter.on(uniqueChannell, (data) => {
                        if(data.answer == this.$t('openPrivateChat')){
                            this.openChat(this.targetUser.hasChat.id)
                        }
                    });
                    return;
                }
                const params = {
                    id: this.targetUser.user.id,
                }
                this.inviteLock = true
                axios.post('/members_chat_request', params).then(response => { 
                    this.errorToast(this.$t('requestSent'))                   
                }).catch(function (error) {                
                    if (error.response) this.errorToast(this.$t('commonError') + error.response.data.message)
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)   
                    this.inviteLock = false
            
                }.bind(this));
            },
            openChat(id){
                window.open(`${window.location.origin}/chat/${id}`);
            },
            toggleFriend(){
                if(this.friendLoading) return
                this.friendLoading = true
                axios.post('/set_member_link', {id: this.targetUser.user.id, token: this.targetUser.user.q_token}).then(response => {  
                    setTimeout(() => {
                        // this.friendLoading = false
                        
                    }, 500);
                    this.getUserDetail(`${window.location.origin}/invite?token=${this.targetUser.user.q_token}&id=${this.targetUser.user.id}`)
                    
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.loading = false                  
                }.bind(this));
            },
            onDecode(e){

                const val = e;
                console.log('scanned', val)
                if(e.includes(window.location.hostname)){
                    if(e.includes('invite')){
                        this.getUserDetail(val)
                        this.loading = true
                        this.$emit('setActive', false)
                    }else if('join'){
                        this.getChatDetail(val)
                        this.loading = true
                        this.$emit('setActive', false)
                    }else{
                        this.unSupportedCodeHandle()
                    }
                    
                }else{
                    this.unSupportedCodeHandle()
                }
                

                
            },
            unSupportedCodeHandle(){
                const uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('inValidQrCode'),
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction')],
                        channel: uniqueChannell

                    })            
                    emitter.on(uniqueChannell, (data) => {
                        if(data.answer == this.$t('confirmToAction')){
                            this.cameraKey++
                        }
                    });
            },
            getChatDetail(val){
                console.log(val)
                const url_string = val;
                const url = new URL(url_string);
                const id = url.searchParams.get("id");
                const token = url.searchParams.get("token");
                if(!id || !token) {
                    this.loading = false 
                    return;
                }
                axios.post('/check_join', {id: id, token: token}).then(response => {  
                    this.targetChat = response.data
                    setTimeout(() => {
                        this.loading = false
                        this.friendLoading = false
                    }, 500);                   
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.loading = false     
                    this.$emit('close')             
                }.bind(this));
            },
            getUserDetail(val){
                const url_string = val;
                const url = new URL(url_string);
                const id = url.searchParams.get("id");
                const token = url.searchParams.get("token");
                if(!id || !token) {
                    this.loading = false 
                    return;
                }
                
                axios.post('/check_invite', {id: id, token: token}).then(response => {  
                    this.targetUser = response.data
                    setTimeout(() => {
                        this.loading = false
                        this.friendLoading = false
                    }, 500);
                    
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)         
                    this.loading = false     
                    this.$emit('close')             
                }.bind(this));
            },
            errorToast(message){
                console.log(message)
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['OK']

                })   
            },
            shareTo(){
                const data = {
                    url: `${this.$store.state.baseLocation}/invite?token=${this.$store.state.user.q_token}&id=${this.$store.state.user.id}`,

                    text: this.$t('inviteMessage', {name: this.$store.state.user.name}),
                }                
                if (navigator.canShare) {
                   navigator.share(data)
                } else {
                    this.errorToast(this.$t('commonError'))
                }               
                
            }
        }
    }
</script>
<style lang="scss">
    .mask {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 2;
    }
    .hole {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 300px;
        height: 300px;
        border-radius: 5px;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5); /* Set a box-shadow to create a hole effect */
    }
    .camWindowClose{
        width: 35px;
        height: 35px;
        border-radius: 50px;
        right:15px;
        top: 15px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        background: var(--hoverBorder);
        cursor: pointer;
        z-index: 4;
    }
    .camWindowClose:hover{
        background: var(--normalBorder);
    }
    .camWindow{
        height: 100%;
        position:relative;
    }
    .my-qr-area{
        display: flex;
        // width: fit-content;
        place-content: center;
        position: absolute;
        left: 15px;
        height: calc(50% - 15px);
        bottom: 15px;
        transition: height 0.1s;
        z-index: 4;
        width: calc(100% - 30px);
        flex-direction: column;
        gap: 20px;

    }
    .zoomInQr{
        height: 50%
    }
    .disabledChatButton{
        opacity: 0.5;
        cursor: not-allowed;
    }
    .loadingWindow{
        position: absolute;
        top:0;
        left:0;
        width: 100%;
        height: 100%;
        background: var(--background-color);
        z-index: 4;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .foundUserCard{
        border: solid thin gray;
        padding: 15px;
        display: flex; 
        flex-wrap: wrap;
        gap: 20px;
        margin: 15px;
        color: var(--primary-color)
    }
    .scannerWrap{
        position: fixed;
        width: 100%;
        height: 100%;
        z-index: 52;
        display: flex;
        top: 0;
        left: 0;
        background-color:var(--overlay);
        display: flex;
        align-items: center;
        justify-content: center;
        
    }
    .scannerInner{
        width: 30vw;
        height: 80vh;
        margin: auto;
        background: var(--background-color);
        // text-align: center;
        position:relative;

    }
    .readerClose{
        width:100%;
        display:flex;
        position:absolute;
        width:60px;
        height:60px;
        right:0;
        top:0;
        background: var(--background-color);
        cursor:pointer;
        color: var(--primary-color);
    }
    .qrUpload{
        position: absolute;
        right: 15px;
        bottom: 15px;
        width: 80px;
        height: 80px;
        background-color: #000000;
        color: #fff;
        opacity: 0.7;
        user-select: none;
        display: flex;
        align-items: center;
        place-content: center;
        cursor: pointer;
        z-index: 3;
        font-size: 13px;
    }
@media screen and (max-width: 959px) {
    .scannerInner{
        width: 100%;
        height: 100%;
        padding: 0;
    }
    .hole {
        width: 70vw;
        height: 70vw;
    }
}
</style>