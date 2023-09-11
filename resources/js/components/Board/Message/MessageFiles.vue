<template>
    <div class="file-area-content" :style="reminder ? 'margin-top: 5px; margin-bottom: 0px' : ''">
        <div class="file-wrap" v-for="(file, index) in list" :class="{ hasMessage: (message.message && message.message.length)}">   
            <div class="file-area-container" @click="previewFile(list, index)">
                <div class="flex-centered">             
                    <div v-if="!file.removed_at" style="max-width:65px;height:40px;display: flex;">                   
                        <img
                            style="max-width:100%;margin:auto;max-height:100%;" 
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile" 
                            :src="`${$store.state.baseLocation}/shared_files/${message.record_id}/thumbs/${file.id}_${file.user_id}_${file.message_id}_50.${file.extension}`" 
                            :srcset="`${$store.state.baseLocation}/shared_files/${message.record_id}/thumbs/${file.id}_${file.user_id}_${file.message_id}_100.${file.extension} 2x`" 
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image' && !file.removed_at" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div v-if="file.removed_at" style="width:40px;height:40px;display: flex;background:white;">
                    </div>
                    <div style="line-height: 1.5;max-width: calc(100% - 35px);margin-left:5px;">
                        <p :title="file.name" class="shared-file-name">{{fileNameFilter(file)}}</p>
                        <div style="display:flex;">
                            <p style="font-size: 10px !important;" class="shared-file-name">{{untilDay(file)}}</p> 
                            <p v-if="!file.removed_at" style="font-size: 10px !important;" class="shared-file-name">| {{fileSizeView(file.size )}}</p>
                        </div>   
                        <p style="font-size: 10px !important;" v-if="file.sign_flag == 1 && canSign(file) && !file.removed_at" class="shared-file-name">{{$t('request')}}</p>               
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
        props: ['list', 'message', 'reminder'],
        
        mounted() {
        },
        components:{
            FileIcon,
            UserIconPreLoad,
            MessageUsers
        },
        computed:{
            
            
        },
        methods:{
            untilDay(file){
                moment.locale(this.$store.state.local);
                const date = file.created_at   
                const deletionDate = moment(date).add(90, 'days');
                const currentDate = moment();
                const duration = moment.duration(deletionDate.diff(currentDate));
                const days = Math.round(duration.asDays());
                if(days >= 0){
                    return this.$tc('fileExpireDate', days, {days: days})
                }
                return this.$t('deleted')
            },
            canSign(file){
                
                const unsignedUsers = file.unsigned_users;
                if(unsignedUsers){
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
                let cancelCopy = {
                    active: null,
                    objects: [],
                    source_record_id: null,
                    target_record_id: null,
                    target_parent_id: null,
                    type: ''
                }
                this.$store.commit('setCopyMoveFiles', cancelCopy)
                let fileList = []
                let object = file
                object['source_board_id'] = this.message.record_id
                fileList.push(object)
                let data = {
                    active: true,
                    list: fileList,
                    drag: true
                }
                this.$store.commit('setFromBoardToFiles', data)
                // this.draggingFiles = [];
                // event.preventDefault();
                // this.exportingFiles = [];
                // this.exportingFiles.push(file);
                // this.importedFiles = []
                // this.sourceBoardId = record_id;
                // this.bounceId = null  
                // this.cancelCopy(); 
            },
            previewFile(file, index){
                if(this.$store.state.fromBoardToFiles.active || this.$store.state.fromFilesToBoard.active) return
                let file_list = this.list
                for(let file_data of file_list){
                    file_data['source_board_id'] = this.message.record_id
                }
                let target_data = file
                
                if(!target_data[0].removed_at){
                    let reminder = this.reminder ? this.reminder : 'board'
                    target_data['source_board_id'] = this.message.record_id
                    const data = {
                        active: true,
                        files: file_list,
                        target: target_data,
                        source: 'message',
                        index: index,
                        message: this.message,
                        reminder: reminder
                    }
                    this.$store.commit('setFilePreview', data)
                    console.log(data)
                }else{
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('fileExpired'),
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK']
                    })  
                }
                
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
