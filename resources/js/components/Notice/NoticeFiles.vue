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
                            v-lazy="{src: `${$store.state.baseLocation}/notice_files/${file.id}_${file.user_id}_${file.record_id}.${file.extension}`}"
                           
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
            previewFile(file, index){
                if(this.$store.state.sharingData) return
                let file_list = this.list
                const files = file_list.map(fileData => ({
                    ...fileData,
                    file_path: `${this.$store.state.baseLocation}/notice_files/${fileData.id}_${fileData.user_id}_${fileData.record_id}.${fileData.extension}`,
                    doc_path: `${this.$store.state.baseLocation}/notice_files/${fileData.id}_${fileData.user_id}_${fileData.record_id}.${fileData.extension}`
                }));
                let target_data = file
                
                
                    const data = {
                        active: true,
                        files,
                        target: target_data,
                        source: 'notice',
                        index: index,
                        message: null,
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
