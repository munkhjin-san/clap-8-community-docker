<template>
    <div class="recordFile">                                                
        <div class="recordFile-inner">                                        
            <div class="swiper" style="border:none;">
                <div class="swiper-wrapper">
                    <div class="swiper-slide" v-for="(image, index) in images" :key="index">
                        <img @click="previewImage(image, index)" class="cursor-pointer" :src="$store.state.baseLocation + '/post_files/' + image.id + '_' + image.user_id + '_' + image.path + '.' + image.extension" style="width: auto;max-width: 100%;max-height: 130px;">
                    </div>  
                </div>                                                          
            </div>        
            <div class="file-area-content" style="gap: 10px;margin: 15px 0 0 0">
                <div @click="previewFile(file, index)" class="file-wrap-rec" v-for="(file, index) in files" style="padding: 0;">   
                    <div class="file-area-container" style="flex-direction: row;">                    
                        <div v-if="file.mime_type !== 'image'" style="position:relative;">
                            <FileIcon :ext="file.extension"/>                                                  
                        </div>
                        <div style="text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">
                            <p class="shared-file-name">{{fileNameFilter(file.name, file.extension)}}</p>   
                            <p style="font-size: 10px !important;" class="shared-file-name">{{fileSize(file.size)}}</p>   
                        </div>
                    </div>                                              
                </div> 
            </div>                                              
        </div>
    </div>
    </template>
    
    <script>
    import FileIcon from '../Board/Mixed/FileIcon.vue';
    import {filesize} from 'filesize';
    import  Swiper  from 'swiper';
    import 'swiper/css'
    
        export default {
            props: ['items'],
            
            components:{
                FileIcon
            },
            mounted() {
                new Swiper('.swiper', {
                    slidesPerView: 5,
                    spaceBetween: 20
                })
            },
            computed:{
                images(){
                    return this.items.filter(ob => ob.mime_type == 'image')
                },
                files(){
                    return this.items.filter(ob => ob.mime_type !== 'image')
                },
                
            },
            methods:{
                previewFile(file, index){
                    const files = this.files.map(fileData => ({
                        ...fileData,
                        file_path: `${this.$store.state.baseLocation}/post_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
                        doc_path: `${this.$store.state.baseLocation}/post_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
                    }));
                    const data = {
                        active: true,
                        files,
                        target: file,
                        source: 'post',
                        source_board_id: null,
                        index: index,
                        message: null,
                        doc_path: `${this.$store.state.baseLocation}/post_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`,
                        file_path: `${this.$store.state.baseLocation}/post_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`,
                    }
                    this.$store.commit('setFilePreview', data)
                },
                previewImage(file, index){
                    const files = this.images.map(fileData => ({
                        ...fileData,
                        file_path: `${this.$store.state.baseLocation}/post_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
                    }));
                    const data = {
                        active: true,
                        files,
                        target: file,
                        source: 'post',
                        source_board_id: null,
                        index: index,
                        message: null,
                    }
                    this.$store.commit('setFilePreview', data)
                },
                fileNameFilter(name, ext) {
                    var str_lenght = name.length;
                    if (str_lenght > 20) {
                        var sliced = name.slice(0, 20) + " ..." + ext;
                        return sliced;
                    }
                    return name;
    
                },
                fileSize (bytes) {
                    if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
                    else return filesize(bytes, {standard: "jedec", round: 0});
                },
                iconColorFilter: function (ext) {
                    var extensions = ["xlsx", "xlsm", "xlsb", "xltx", "xls", "xml", "xlam", "xlr", "xlw", "xla",
                        "doc", "docm", "docx", "dot", "dotx",
                        "potm", "potx", "ppam", "pps", "ppsm", "ppsx", "ppt", "pptm", "pptx",
                        "pdf",
                    ]
                    var format = extensions.indexOf(ext);
                    var result;
    
                    switch (true) {
                        case (format >= 0 && format <= 9):
                            result = "fill: #1D6F42";
                            break;
                        case (format >= 10 && format <= 14):
                            result = "fill: #0078d7";
                            break;
                        case (format >= 15 && format <= 23):
                            result = "fill: #d04423";
                            break;
                        case (format == 24):
                            result = "fill: #ff0000";
                            break;
                        default:
                            result = null;
                    }
                    return result;
                },
            }
        }
    </script>
    