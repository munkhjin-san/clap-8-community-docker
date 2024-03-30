<template>
    <div class="md-window" style="z-index:44">    
        <div :key="fileKey" class="file-preview-container">
            <div style="width:100%;display:flex;display: flex;align-items: center;margin-bottom:10px;position: relative; fill:var(--primary-color);">
                <p class="file-preview-title">{{currentFile.name}}</p>
                <div @click.stop="menu.setMenu( {name: 'fpMenu', id: 63})" style="margin-left: auto;min-width: 30px;" class="messageMenuContainer cursor-pointer">                
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 7 32" style="margin:auto;min-width: 3px;">
                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                    </svg>
                </div>
                <div class="messageMenuContainer cursor-pointer" style="min-width: 30px;" @click="filePreviewClose">
                    <svg style="margin:auto;min-width:14px" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
                <div id="fpMenu" class="boxMenuComment cursor-pointer" v-if="menu.name == 'fpMenu' && menu.id == 63" style="line-height:normal;top: 25px;right: 50px;z-index: 4;">
                    <div>
                        <Transition name="menuSwitch">
                        <div v-if="fileMenuLayer == 0" style="position:absolute;top:0;right:0;overflow:hidden;box-shadow: rgb(60 64 67 / 30%) 0px 1px 2px 0px, rgb(60 64 67 / 15%) 0px 2px">
                            <ul> 
                                <li @click="downloadFile" class="boxMenuItems cursor-pointer">ダウンロード</li> 
                                <li v-if="source == 'file' || source == 'message'" @click="fileMenuLayer = 1" class="boxMenuItems cursor-pointer" style="display: flex;">
                                    <span style="margin-right:10px;">シェア</span>
                                    <svg style="transform:rotate(180deg);margin: auto 0 auto auto;" version="1.1" width="10" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                    </svg> 
                                </li>
                            </ul>
                        </div>
                        </Transition>
                        <Transition name="menuSwitch">
                        <div v-if="fileMenuLayer == 1" style="position:absolute;top:0;overflow:hidden;box-shadow: rgb(60 64 67 / 30%) 0px 1px 2px 0px, rgb(60 64 67 / 15%) 0px 2px">
                            <ul style="width:100px">  
                                <li @click="fileMenuLayer = 0" class="boxMenuItems cursor-pointer">
                                <svg version="1.1" width="10" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                </svg>  
                                <span style="margin-left:10px;">戻る</span></li>      
                                <li @click="shareTo('board', currentFile)" class="boxMenuItems cursor-pointer">ボード</li> 
                                <li @click="shareTo('knowledge', currentFile)" class="boxMenuItems cursor-pointer">ナレッジ</li>  
                                <li @click="shareTo('nice', currentFile)" class="boxMenuItems cursor-pointer">ナイス</li>  
                                <li @click="shareTo('challenge', currentFile)" class="boxMenuItems cursor-pointer">チャレンジ</li>     
                                <li @click="shareTo('calendar', currentFile)" class="boxMenuItems cursor-pointer">カレンダー</li>                           
                            </ul> 
                        </div>
                        </Transition>
                    </div> 
                                                                            
                </div>
            </div>
            <div v-if="filePreview.files.length" class="mySwiper-container">
                <div v-if="canView" style="height:100%;">
                    <div class="mySwiper-wrapper" ref="mySwiper">                   
                        <div ref="swiperContainer" class="swiper-container" style="background:none;border:none;width:100%;overflow: hidden;">
                            <div class="swiper-wrapper" > 
                                <div class="swiper-slide" style="background:none;border:none;width:100%" :key="file.id" v-for="(file, index) in filePreview.files">
                                    <div class="swiper-zoom-container width90">                                        
                                        <img
                                            v-if="file.mime_type == 'image'"
                                            style="max-width: 100%; margin: auto; max-height: 100%;"
                                            :src="file.file_path"
                                        />                                                                
                                        <div v-else-if="canPreview && file.mime_type == 'video'" style="display:flex;height: -webkit-fill-available;max-height: 70vh;">
                                            <video controls="controls" style="max-width: 79vw;max-height: 66vh;height: auto;background: #000;margin: auto;">
                                                <source :src="file.file_path">
                                            </video>
                                        </div>
                                        <div v-else-if="canPreview && file.mime_type == 'audio'" style="display:flex;height: -webkit-fill-available;max-height: 70vh;">                    
                                            <audio controls style="margin: auto;">
                                                <source :src="file.file_path">
                                            </audio>
                                        </div>
                                        <div id="docViewer" v-if="canPreview && file.mime_type == 'application'">
                                            <iframe id="docloaderframe" v-if="file.extension !== 'pdf'" @load="docLoader = false" class="docViewer-frame" v-show="!docLoader" :src="docUrl"></iframe>
                                            
                                            <div ref="scrollParent" v-if="file.extension == 'pdf'" id="scrollParent" style="height:100%;"> 
                                                <PdfViewer 
                                                    v-if="f_index == index"
                                                    :source="currentFile.file_path" 
                                                    :file="currentFile" 
                                                    :key="pdfKey"
                                                    @refresh="refreshReader"
                                                />

                                            </div>
                                            <div v-if="docLoader" style="position:absolute;width:100%;height:100%;left:0;top:0;background:green;display:flex;">
                                                <div id="loaderMini" style="background:var(--background-color);">
                                                    <div class="spinner-mini"></div>
                                                </div> 
                                            </div>                                            
                                        </div>
                                        <div v-else-if="canPreview && file.mime_type == 'text'" style="height: calc(100% - 37px);width:100%;">
                                            <object
                                                v-if="file.extension === 'txt'"
                                                :data="file.file_path"
                                                type="text/html"
                                                width="100%"
                                                height="100%"
                                            ></object>
                                            <div v-else class="unsupportedFileWindow">
                                                このファイルはプレビューできません
                                            </div>
                                        </div>
                                        <div v-else-if="!canPreview" class="unsupportedFileWindow">
                                            このファイルはプレビューできません
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="swiper-button-prev" style="top:40%;"></div>
                            <div class="swiper-button-next" style="top:40%;"></div>                  
                        </div>                  
                    </div>
                    <div class="second-swiper-wrapper" ref="secondswiper">                     
                        <div thumbsSlider="" class="swiper gallery-thumbs" style="background:none;border:none;">
                            <div class="swiper-wrapper"> 
                                <div class="swiper-slide ssliderItem" :key="file.id" v-for="(file) in filePreview.files">
                                    <img style="max-width:100%;margin:auto;max-height:100%;" v-if="file.mime_type == 'image'" :src="file.file_path">
                                    <div v-if="file.mime_type !== 'image'" style="position:relative;">
                                        <FileIcon :ext="file.extension"/>
                                    </div>
                                </div>
                            </div>                           
                        </div>
                    </div>
                </div>                
            </div> 
               
        </div>  
    </div> 
</template>

<script setup>
import { computed, inject, onMounted, ref, nextTick } from 'vue';
import PdfViewer from './PdfViewer.vue'
import Swiper from 'swiper';
import 'swiper/css/bundle';
import "swiper/css/zoom";
import { Zoom, Navigation, Thumbs } from 'swiper';
import 'swiper/css/navigation'
import 'swiper/css/thumbs'
import FileIcon from '../../Mixed/FileIcon.vue';
import { useRouter } from 'vue-router';
import { useFilePreview } from "@/store/filePreview";
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useSharingDataStore } from '@/store/sharingData'
    const sharingData = useSharingDataStore()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const router = useRouter()
    const f_index = ref(0)
    const fileKey = ref(Math.floor(Math.random() * 1000))
    const docUrl = ref('')
    const docLoader = ref(false)
    const fileMenuLayer = ref(0)
    const thumbsSwiper = ref(null)
    const topSwiper = ref(null)
    const filePreview = useFilePreview()
    const pdfKey = ref(0)
    const doc_extensions = ["xlsx", "xlsm", "xlsb", "xltx", "xls", "xml", "xlam", "xlr", "xlw", "xla",
        "doc", "docm", "docx", "dot", "dotx",
        "potm", "potx", "ppam", "pps", "ppsm", "ppsx", "ppt", "pptm", "pptx", "pdf"              
    ];
    const { notify } = inject('dialog')
        
    onMounted(() => {
        
        f_index.value = filePreview.index
        swiperCreate()
        if(canView.value){
            topSwiper.value.slideTo(f_index.value, false)
            thumbsSwiper.value.slideTo(f_index.value, false)
        }
        const firstFile = currentFile.value
        
        if(doc_extensions.indexOf(firstFile.extension) > -1 && f_index.value == 0 && firstFile.extension !== 'pdf'){
            // docLoader.value = true
            getDocs()
        }
    })
    const refreshReader = () => {
        if(pdfKey.value < 3){
            pdfKey.value++
        }
    }      
    const canView = computed(() => {
        return source.value == 'post' || source.value == 'message' || source.value == 'calendar' || source.value == 'user' || source.value == 'notice'
    })
    const source = computed(() => {
        return filePreview.source
    })
    const currentFile = computed(() => {
        return filePreview.files[f_index.value]
    })
    const doc_path = computed(() => {
        return filePreview.files[f_index.value].doc_path
    })
    const canPreview = computed(() => {
        const supported = [
            'image', 'text','video', 'audio'
        ]
        const exist_index = supported.indexOf(currentFile.value.mime_type)
        if(exist_index > -1){
            return true
        }else if(currentFile.value.mime_type == 'application'){                    
            if(doc_extensions.indexOf(currentFile.value.extension) > -1){
                return true
            }else{
                return false
            }  
        }else{
            return false
        }                
    })

    const swiperCreate = () => {
        thumbsSwiper.value = new Swiper('.gallery-thumbs', {
            spaceBetween: 10,
            slidesPerView: 4,
            freeMode: true,
            watchSlidesProgress: true
        })
        topSwiper.value = new Swiper('.swiper-container', {
            // Optional parameters
            direction: 'horizontal',
            spaceBetween: 10,
            zoom: true,
            navigation: true,
            centeredSlides: true,
            modules: [Navigation, Zoom, Thumbs],
            thumbs: {
                swiper: thumbsSwiper.value 
            },
            on: {
                slideChange: (swiper) => {
                    changeSwiperIndex(swiper)
                }
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    }
              
    
    
    
    const changeSwiperIndex = (swiper) => {
        docUrl.value = ''
        f_index.value = swiper.realIndex
        pdfKey.value = 0
        const firstFile = currentFile.value                
        if(doc_extensions.indexOf(firstFile.extension) > -1){
            if(firstFile.extension == 'pdf'){
            }else{
                getDocs()
                
            }   
            
        }
        
    } 
    const getDocs = async() => {               
        try{
            const response = await axios.post('/user_generate_file_key')
            // const url = doc_path.value + '/' + response.data
            const url = `${window.location.origin}/cdn_external/${auth.id}/${response.data}${doc_path.value}`
            console.log(url)
            const encodedUrl = encodeURIComponent(url);
            docUrl.value = `https://view.officeapps.live.com/op/embed.aspx?src=${encodedUrl}`
            setTimeout(() => {
                docLoader.value = false
            }, 300);                    
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            docLoader.value = false
        }
    }        
    const filePreviewClose = () => {
        const data = {
            active: false,
            files: [],
            source: null,
            source_board_id: null
        }
        filePreview.setFilePreview(data)
    }
    const downloadFile = () => {
        if(canView.value){                    
            direcDownload();
        }
        menu.setMenu( {name: '', id: null})

    }
    const direcDownload = () => {                 
    
        const link = document.createElement('a');
        link.href = currentFile.value.file_path;
        link.download = '';
        link.setAttribute('download', currentFile.value.name);
        document.body.appendChild(link);            
        link.click();  
        document.body.removeChild(link); 

    }
    const shareTo = (to, file) => {
        const shareData = {
            active: true,
            title: '',
            text: '',
            files: [{path :`/cdn/shared_files/${file.board_id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`, record: file}],
            from: 'message',
            to: to,
            drag: false,
            instruction: to == 'board' ? '送る先のボードを選択してください' : null
        }
        sharingData.setSharingData(shareData)
        menu.setMenu( {id : null, name: ''})
        if(to !== 'board'){
            router.push({name: to})
        }
        filePreviewClose()
    }
   
</script>
<style lang="scss">
    // .vue-pdf-embed > div {
      //  margin-bottom: 8px;
    //}
    .lineOptions {
        position: absolute;
        display: flex;
        flex-direction: column;
        background-color: #fff;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 2px 4px #0000001a;
        top: 33px;
        z-index: 5;
        left: 35px;
    }
    
    .lineOption {
        display: flex;
        align-items: center; /* Center the line vertically */
        padding: 10px;
        cursor: pointer;
        width: 100px;
    }
    
    .lineOption:hover {
        background-color: #f0f0f0;
    }
    
    .lineOption .line {
        flex-grow: 1;
    }
    
    .lineOption.selected .line {
        background-color: black; /* Highlight the selected line */
    }
    .canvasClass{
        -webkit-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    #bottomRight{
        right:-4px;
        bottom:-4px;
        cursor: se-resize;
    }
    #bottomLeft{
        left:-4px;
        bottom:-4px;
        cursor: sw-resize;
    }
    #topLeft{
        left:-4px;
        top:-4px;
        cursor: nw-resize;
    }
    #topRight{
        right:-4px;
        top:-4px;
        cursor: ne-resize;
    }
    .corner {
        position: absolute;
        
        width: 8px;
        height: 8px;
        background-color: white;
        border: 1px solid black;
        z-index: 3;
        
      }
    .canSign{
        z-index: 1;
        background-color: rgba(0, 0, 0, 0.3);
    }
    .signCanvas{
        display: flex;
        width:100%;
        height:100%;
        justify-content: center;
        align-items: center;
        position:absolute;
        flex-direction: column;
    }
    .file-preview-container{
        width: 90%;
        height: 90%;
        background: var(--background-color);
        padding: 20px;
        display:flex;
        flex-direction:column;
        justify-content:space-around;
        color:var(--primary-color);
    }
    .pdfButton-wrapper{
        width: 100%;
        justify-content: flex-end;
        display:flex;
        margin-top:15px;
        gap: 15px;
        bottom: 0;
        z-index: 1;
    }
    .vue-pdf-embed canvas{
        width: 100% !important;
        height: 100% !important;
    }
    .second-swiper-wrapper{
        margin-top: 20px;
        min-height: 82px;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        position: relative
    }
    .canvasStyle{
        pointer-events: none;
    }
    .pViewImage{
        box-shadow: rgb(0 0 0 / 20%) 0px 5px 15px;
        max-width: 100%;
        margin: auto;
        max-height: 100%;
        transform: scale(1);
        transition: transform 0.3s
    }
    .ssliderItem{
        max-height: 40px !important;
        padding: 15px;
        height: 30px !important;
        width: 40px !important;
        max-width: 60px !important;
        cursor: pointer;
    }
    .unsupportedFileWindow{
        font-weight: 500;
        height: 100%;
        width: 100%;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .file-preview-title{
        line-height: 17px;
        font-size: 14px;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-right: 15px;
    }
    .swiper{
    &.gallery-top {
        height: 80%;
        width: 100%;
      }
      &.gallery-thumbs {
        height: 100%;
        box-sizing: border-box;
        gap: 0;
      }
      &.gallery-thumbs .swiper-wrapper .swiper-slide {
   
        transition-property: none;
        opacity: 0.5;
        border:none;
      }
      
      &.gallery-thumbs .swiper-wrapper .swiper-slide-thumb-active {
        opacity: 1;
        border: solid thin var(--hoverBorder);
        
      }
      
    }
    .drawing{
        pointer-events: auto !important;
    }
    .swiper-button-prev:after, .swiper-button-next:after {
        font-size:25px !important;
        color:var(--primary-color);
    }
    .swiper-button-prev, .swiper-button-next{
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--background-color);
    }
    .mySwiper-container{ 
        height: calc(100% - 37px);
        display: flex;
        flex-direction: column;
        position:relative
    }
    .mySwiper-wrapper{
        display:flex;
        height:calc(100% - 82px);
    }
    
    #docViewer{
        height:100%;
        width:100%;
        position: relative;
    }
    .docViewer-frame{
        height: 100%;
        width: 100%;
    }
    .signatureButton{
        padding: 5px 10px 5px 10px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 0px;
        background: var(--primary-button);
        color: #fff;
    }
    
    
    @media screen and (min-width: 760px) {
        .swiper-zoom-container.width90{
            width: 90%;
            // height: calc(100% - 60px);
            display: flex;
            justify-content: center;
        }
        .swiper{
            &.gallery-thumbs .swiper-wrapper{
                transform: translate3d(0px, 0px, 0px) !important;
                transition-property: none;
          }
        }
    }
    @media screen and (max-width: 760px) {
        .corner{
            display: none;
        }
        // .resizeable{
        //     width: 300px;
        //     height: 150px;
        // }
        .width90{
            // height: calc(100% - 48px);
            display: flex;
        }
        .file-preview-container{
            height: calc(100% - 40px);
            width: calc(100% - 40px);
        }
        .canvasStyle{
            pointer-events: auto;
        }
        .mySwiper-container{
            max-height: 100%;   
        }
        .second-swiper-wrapper{
            min-height: fit-content;
            margin-top: 10px;
        }
        .mySwiper-wrapper{
            max-height: 100%;
            height:calc(100% - 52px);
        }
        
        .swiper-button-next{
            display: none;
            width:30px;
            height:30px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='currentColor' class='bi bi-arrow-right-circle' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z'/%3E%3C/svg%3E");
        }
        .swiper-button-prev{
            display: none;
            width:30px;
            height:30px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='currentColor' class='bi bi-arrow-left-circle' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z'/%3E%3C/svg%3E");
        }
        .ssliderItem{
            width: 30px !important;
            height: 20px !important;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .signatureButton{
            height: 30px;
            font-size: 12px;
            line-height: 0;
            padding: 5px;
        }

    }
</style>
