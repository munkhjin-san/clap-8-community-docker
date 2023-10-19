<template>
    <div class="recordFile">                                                
        <div class="recordFile-inner">                                        
            <swiper class="swiper" :slides-per-view="5" :space-between="20" style="border:none;">
                <swiper-slide v-for="(image, index) in images" :key="index">
                    <img @click="previewImage(image, index)" class="cursor-pointer" :src="$store.state.baseLocation + '/post_files/' + image.id + '_' + image.user_id + '_' + image.path + '.' + image.extension" style="width: auto;max-width: 100%;max-height: 130px;">
                </swiper-slide>                                                           
            </swiper>        
            <div class="file-area-content hasMessage" style="gap: 10px;">
                <div @click="previewFile(file, index)" class="file-wrap-rec" v-for="(file, index) in files">   
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
    import { Swiper, SwiperSlide } from 'swiper/vue';
    import 'swiper/css'
    import { Navigation, Thumbs } from 'swiper';
    import 'swiper/css/navigation'
    import 'swiper/css/thumbs'
        export default {
            props: ['items'],
            data(){
                return{
                    swiperOption01: {
                        slidesPerView: 5,
                        spaceBetween: 20,
                        freeMode: true,
                        pagination: {
                            el: '.swiper-pagination',
                            clickable: true
                        }
                    },
                }
            },
            components:{
                Swiper,
                SwiperSlide,
                FileIcon
            },
            mounted() {
                
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
                    
                    const data = {
                        active: true,
                        files: this.files,
                        target: file,
                        source: 'post',
                        source_board_id: null,
                        index: index,
                        message: null
                    }
                    this.$store.commit('setFilePreview', data)
                },
                previewImage(file, index){
                    
                    const data = {
                        active: true,
                        files: this.images,
                        target: file,
                        source: 'post',
                        source_board_id: null,
                        index: index,
                        message: null
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
    