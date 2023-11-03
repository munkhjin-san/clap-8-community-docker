<template>
    <div class="file-area-content" :style="reminder ? 'margin-top: 5px; margin-bottom: 0px' : ''">
        <div class="file-wrap" draggable="true" @dragover.prevent @dragstart.prevent="fileExportStart(file, message.record_id)" v-for="(file, index) in filteredFiles" :class="{ hasMessage: (message.message && message.message.length)}">   
            <div class="file-area-container" @click="previewFile(file, index)">
                <div class="flex-centered">             
                    <div v-if="!file.removed_at" style="max-width:65px;height:40px;display: flex;">                   
                        <img
                            style="max-width:100%;margin:auto;max-height:100%;" 
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile" 
                            :src="`${$store.state.baseLocation}/shared_files/${message.record_id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`" 
                            loading="lazy"
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image' && !file.removed_at" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div style="line-height: 1.5;max-width: calc(100% - 35px);margin-left:5px;">
                        <p :title="file.name" class="shared-file-name">{{fileNameFilter(file)}}</p> 
                        <div>
                            <p v-if="!file.removed_at" style="font-size: 10px !important;" class="shared-file-name">{{fileSizeView(file.size )}}</p>
                        </div> 
                        <p style="font-size: 10px !important;" v-if="file.sign_flag == 1 && canSign(file) && !file.removed_at" class="shared-file-name">{{$t('request')}}</p>
                        <p v-if="file.multiple_flag == 1 && (canSign(file) || file.user_id == $store.state.user.id)" style="font-size: 13px !important; position:absolute; top:8px; right:27px; background:black; color:white;margin:0;" class="shared-file-name">確認用</p>                      
                    </div>
                </div>   
                <div v-if="file.sign_flag == 1" style="display:flex;margin-top:10px;align-items:center;height: 15px;justify-content:space-between;">
                    <div class="flex-centered" @click.stop="viewUsersList(file.signed_users, $t('signedMembers'))" v-if="file.signed_users">
                        <p class="cursor-pointer" style="font-size:12px; margin-right:3px;">{{$t('signed')}} {{ file.signed_users.length ? `(${file.signed_users.length})` : '(0)' }}</p>                                            
                        <!-- <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in file.signed_users.slice(0,3)">  
                            <UserIconPreLoad :title="user.name" size="30" :user="user" imgClass="userSmallIcon"/>                                         
                        </div> -->
                        <!-- <span style="margin: auto 0; cursor: pointer; font-size: 12px;">({{file.signed_users.length}})</span> -->
                    </div>
                    <div class="flex-centered" style="margin-left:10px;text-align:right;" @click.stop="viewUsersList(file.unsigned_users, $t('unsignedMembers'))" v-if="file.unsigned_users">
                        <p class="cursor-pointer" style="font-size:12px; margin-right:3px;">{{$t('unsigned')}} {{ file.unsigned_users.length ? `(${file.unsigned_users.length})` : '(0)' }}</p>                                            
                        <!-- <div :key="user.id" style="width:15px;margin: auto 0;" v-for="user in file.unsigned_users.slice(0,3)">  
                            <UserIconPreLoad :title="user.name" size="30" :user="user" imgClass="userSmallIcon"/>                                         
                        </div> -->
                        <!-- <span style="margin: auto 0; cursor: pointer; font-size: 12px;">({{file.unsigned_users.length}})</span> -->
                    </div>
                </div>

                <div @click.stop="$store.commit('setMenu', { id: file.id, name: 'sharedFileMenu'}), this.fileMenuLayer = 0" id="messageFileMenuButton" style="position: absolute;right: 2px;top: 6px;" class="boardMenuContainer cursor-pointer">                
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="margin:auto;min-width: 3px;">
                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                    </svg>
                </div>

                <div @click.stop id="sharedFileMenu" class="boxMenuComment cursor-pointer" v-if="$store.state.menu.name == 'sharedFileMenu' && $store.state.menu.id == file.id" :style="{lineHeight:'normal',top: mIndex == 0 ? 'auto' : '20px',bottom: mIndex == 0 ? '40px' : 'auto', right: '25px', zIndex: 4}">
                    <div style="position: relative;">
                        
                        <div v-if="fileMenuLayer == 0" style="right:0;overflow:hidden;box-shadow: rgb(60 64 67 / 30%) 0px 1px 2px 0px, rgb(60 64 67 / 15%) 0px 2px">
                            <ul> 
                                <li @click="downloadFile(file)" class="boxMenuItems cursor-pointer">{{$t('download')}}</li> 
                                <li @click="fileMenuLayer = 1" class="boxMenuItems cursor-pointer" style="display: flex;">
                                    <span style="margin-right:10px;">{{$t('share')}}</span>
                                    <svg style="transform:rotate(180deg);margin: auto 0 auto auto;" version="1.1" width="10" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                    </svg> 
                                </li>
                                <li v-if="!file.sign_flag && file.user_id == this.$store.state.user.id && file.extension == 'pdf'" @click="signRequest()" class="boxMenuItems cursor-pointer">{{ $t('signatureRequest') }}</li>
                            </ul>
                        </div>
                        <div v-if="fileMenuLayer == 1" style="overflow:hidden;box-shadow: rgb(60 64 67 / 30%) 0px 1px 2px 0px, rgb(60 64 67 / 15%) 0px 2px">
                            <ul style="width:100px">  
                                <li @click="fileMenuLayer = 0" class="boxMenuItems cursor-pointer">
                                <svg version="1.1" width="10" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                </svg>  
                                <span style="margin-left:10px;">{{$t('back')}}</span></li>      
                                <li @click="shareTo('board', file)" class="boxMenuItems cursor-pointer">ボード</li>   
                                <li @click="shareTo('knowledge', file)" class="boxMenuItems cursor-pointer">ナレッジ</li>  
                                <li @click="shareTo('nice', file)" class="boxMenuItems cursor-pointer">ナイス</li>  
                                <li @click="shareTo('challenge', file)" class="boxMenuItems cursor-pointer">チャレンジ</li>     
                                <li @click="shareTo('calendar', file)" class="boxMenuItems cursor-pointer">カレンダー</li>                      
                            </ul> 
                        </div>
                    </div> 
                                                                            
                </div>
            </div>                                             
        </div>
    </div>
</template>

<script>
    import {filesize} from "filesize";
    import FileIcon from "../Mixed/FileIcon.vue";
    import UserIconPreLoad from "../Mixed/UserIcon.vue";
    import MessageUsers from "./MessageUsers.vue"
    import moment from 'moment';
    export default {
        props: ['list', 'message', 'reminder', 'mIndex'],
        data(){
            return{
                fileMenuLayer: 0
            }
        },
        mounted() {

        },
        components:{
            FileIcon,
            UserIconPreLoad,
            MessageUsers
        },
        computed:{
            
            filteredFiles(){
                const filteredFiles = []
                for(let item of this.list){
                    const unsignedUsers = item.unsigned_users || [];
                    const signedUsers = item.signed_users || [];
                    const includesUser = unsignedUsers.some(user => user.id === this.$store.state.user.id) ||
                    signedUsers.some(user => user.id === this.$store.state.user.id) || item.user_id === this.$store.state.user.id;

                    if (item.sign_flag === 0 || item.sign_flag === null) {
                        filteredFiles.push(item)
                    }else if(item.multiple_flag === 1 && (!includesUser || item.user_id === this.$store.state.user.id)){
                        filteredFiles.push(item)
                    }else if(item.sign_flag === 1 && item.multiple_flag === 0 && includesUser){
                        filteredFiles.push(item)
                    }else if(item.multiple_flag === 2 && item.user_id === this.$store.state.user.id){
                        filteredFiles.push(item)
                    }
                }
                return filteredFiles
            },
        },
        methods:{
            downloadFile(file){
                this.closeMenu()
                let src, name;               
                
                const path = file.board_id + '/' + file.id + '_' + file.user_id + '_' + file.message_id + '.' + file.extension        
                name = file.name
                src = this.$store.state.baseLocation + '/shared_files/'+ path;               
                const link = document.createElement('a');
                link.href = src;
                link.download = '';
                link.setAttribute('download', name);
                document.body.appendChild(link);            
                link.click();  
                document.body.removeChild(link); 
            },
            closeMenu(){
                this.$store.commit('setMenu', {id: null, name: ''})
            },
            signRequest(){
                
            },
            shareTo(to, file){
                    const shareData = {
                        title: '',
                        text: '',
                        files: [{path :`/shared_files/${this.message.record_id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`, record: file}],
                        from: 'message',
                        to: to,
                        drag: false,
                        instruction: to == 'board' ? '送る先のボードを選択してください' : null
                    }
                    this.$store.commit('setSharingData', shareData)
                    this.$store.commit('setMenu', {id : null, name: ''})
                    if(to !== 'board'){
                        this.$router.push({name: to})
                    }
                
            },
            canSign(file){
                
                const unsignedUsers = file.unsigned_users;
                if(unsignedUsers && (file.multiple_flag == 2 || file.multiple_flag == 0)){
                    const includesUser = Object.values(unsignedUsers).some(user => user.id === this.$store.state.user.id);
                    return includesUser
                }
                return false
                
            },
            viewUsersList(users, title){
                const data = {
                    active: true,
                    userList: users,
                    title: title
                }
                this.$store.commit('setMessageUsers', data)
                
            },
            fileExportStart(file, record_id){

                const shareData = {
                    title: '',
                    text: '',
                    files: [{path :`/shared_files/${this.message.record_id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`, record: file}],
                    from: 'message',
                    to: '',
                    drag: true 
                }
                this.$store.commit('setSharingData', shareData)
            },
            multipleFile(file, index){
                let select = []
                const unsignedUsers = file.unsigned_users || [];
                const signedUsers = file.signed_users || [];

                const includesUser = unsignedUsers.some(user => user.id === this.$store.state.user.id) ||
                    signedUsers.some(user => user.id === this.$store.state.user.id);
                if (file.sign_flag === 0) {
                    for(let item of this.filteredFiles){
                        if(!item.original_file_id){
                            select.push(item)
                        }
                    }
                    this.file_index = index
                }else if(file.multiple_flag === 1 && file.user_id === this.$store.state.user.id){
                    select.push(file)
                    for (let item of this.list){
                        if(item.multiple_flag === 2 && item.original_file_id === file.id){
                            select.push(item)
                        }
                    }
                    this.file_index = 0
                }else if(file.sign_flag === 1 && file.multiple_flag === 0 && includesUser){
                    select.push(file)
                    this.file_index = 0
                }else if(file.multiple_flag === 2 && file.user_id === this.$store.state.user.id){
                    select.push(file)
                    this.file_index = 0
                }else if(!includesUser || file.multiple_flag === 1){
                    for(let item of this.filteredFiles){
                        if(item.multiple_flag === 0 || item.multiple_flag === 1){
                            select.push(item)
                        }
                    }
                    this.file_index = index
                }
                return select
            },
            previewFile(file, index){
                if(this.$store.state.shareData) return
                let selectedItem = this.multipleFile(file, index)
                let file_list = selectedItem
                const files = file_list.map(fileData => ({
                    ...fileData,
                    source_board_id: this.message.record_id,
                    file_path: `${this.$store.state.baseLocation}/shared_files/${this.message.record_id}/${fileData.id}_${fileData.user_id}_${fileData.message_id}.${fileData.extension}`,
                    doc_path: `${this.$store.state.baseLocation}/shared_docs/${this.message.record_id}/${fileData.id}_${fileData.user_id}_${fileData.message_id}.${fileData.extension}`
                }));
                
                let target_data = selectedItem
                let reminder = this.reminder ? this.reminder : 'board'
                target_data['source_board_id'] = this.message.record_id
                const data = {
                    active: true,
                    files,
                    target: target_data,
                    source: 'message',
                    index: this.file_index,
                    message: this.message,
                    reminder: reminder
                }
                this.$store.commit('setFilePreview', data)
            },
            fileNameFilter(file){
                // const lastDot = file.name.lastIndexOf('.');
                // const fileName = file.name.substring(0, lastDot);
                // var str_lenght = fileName.length;
                // if (str_lenght > 20) {
                //     var sliced = fileName.slice(0, 20) + " ..." + file.extension;
                //     return sliced;
                // }
                return file.name;
            },
            fileSizeView (bytes) {
                if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
                else return filesize(bytes, {standard: "jedec", round: 0});
            },
            
        }
    }
</script>
