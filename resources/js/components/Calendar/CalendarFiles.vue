<template>
    <div class="file-area-content" style="overflow: hidden;max-width: 100%;">
        <div class="file-wrap" v-for="(file, index) in list" style="max-width: 100%;">   
            <div class="file-area-container" @click="previewFile(list, index)">
                <div class="flex-centered">             
                    <div style="max-width:65px;height:40px;display: flex;">                   
                        <img
                            style="max-width:100%;margin:auto;max-height:100%;" 
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile" 
                            v-lazy="{src: `${$store.state.baseLocation}/calendar_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`}"
                           
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image' && !file.removed_at" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div style="line-height: 1.5;max-width: calc(100% - 35px);overflow: hidden;">
                        <p style="font-size: 12px;" :title="file.name" class="shared-file-name">{{fileNameFilter(file)}}</p>
                        <div style="display:flex;">
                            <p style="font-size: 10px !important;" class="shared-file-name">{{fileSizeView(file.size )}}</p>
                        </div>                            
                    </div>
                </div>   
            </div>                                             
        </div>
    </div>
</template>

<script>
    import {filesize} from "filesize";
    import FileIcon from "../Board/Mixed/FileIcon.vue";
    import moment from 'moment';
    export default {
        props: ['list'],
        
        mounted() {
        },
        components:{
            FileIcon,
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
                if(this.$store.state.sharingData) return
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
