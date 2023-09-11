<template>
    <div class="md-window" style="z-index:54">    
        <div :key="fileKey" class="file-preview-container">
            <div style="width:100%;display:flex;display: flex;align-items: center;margin-bottom:10px;position: relative; fill:var(--primary-color);">
                <p class="file-preview-title">{{currentFile.name}}</p>
                <div @click.stop="$store.commit('setMenu', {name: 'fpMenu', id: 63})" style="margin-left: auto;min-width: 30px;" class="messageMenuContainer cursor-pointer">                
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
                <div id="fpMenu" class="boxMenuComment cursor-pointer" v-if="$store.state.menu.name == 'fpMenu' && $store.state.menu.id == 63" style="line-height:normal;top: 25px;right: 50px;z-index: 4;">
                    <div>
                        <Transition name="menuSwitch">
                        <div v-if="fileMenuLayer == 0" style="position:absolute;top:0;right:0;overflow:hidden;box-shadow: rgb(60 64 67 / 30%) 0px 1px 2px 0px, rgb(60 64 67 / 15%) 0px 2px">
                            <ul> 
                                <li @click="downloadFile" class="boxMenuItems cursor-pointer">{{$t('download')}}</li> 
                                <li v-if="source == 'file' || source == 'message'" @click="fileMenuLayer = 1" class="boxMenuItems cursor-pointer" style="display: flex;">
                                    <span style="margin-right:10px;">{{$t('share')}}</span>
                                    <svg style="transform:rotate(180deg);margin: auto 0 auto auto;" version="1.1" width="10" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                    </svg> 
                                </li>
                                <li v-if="source == 'message' && !currentFile.sign_flag && canModify && currentFile.extension == 'pdf'" @click="signRequest()" class="boxMenuItems cursor-pointer">{{ $t('signatureRequest') }}</li>
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
                                <span style="margin-left:10px;">{{$t('back')}}</span></li>      
                                <li @click="shareTo('board')" class="boxMenuItems cursor-pointer">{{$t('toChat')}}</li>                          
                            </ul> 
                        </div>
                        </Transition>
                    </div> 
                                                                            
                </div>
            </div>
            <div v-if="$store.state.filePreview.files.length" class="mySwiper-container">
                <div v-if="source == 'post' || source == 'message' || source == 'notice'" style="height:100%;">
                    <div class="mySwiper-wrapper" ref="mySwiper">                   
                        <swiper class="firstswiper" style="background:none;border:none;width:100%;" 
                            :space-between="10"
                            :zoom="true"
                            :modules="modules"
                            :centeredSlides="true"
                            :navigation="true"
                            :thumbs="{ swiper: thumbsSwiper }"
                            @slideChange="changeSwiperIndex"
                            @swiper="onSwiper"
                        >
                            <swiper-slide style="background:none;border:none;width:100%" :key="file.id" v-for="(file, index) in $store.state.filePreview.files"> 
                                <div v-if="f_index == index" class="swiper-zoom-container width90">
                                    
                                    <img style="max-width:100%;margin:auto;max-height:100%;" v-if="file.mime_type == 'image' && source == 'message'" :src="$store.state.baseLocation + '/shared_files/'+ file.board_id + '/' + file.id + '_' + file.user_id + '_' + file.message_id + '.' + file.extension"> 
                                                            
                                    <div v-else-if="canPreview && file.mime_type == 'video'" style="display:flex;height: -webkit-fill-available;max-height: 70vh;">
                                        <video controls="controls" style="max-width: 79vw;max-height: 66vh;height: auto;background: #000;margin: auto;">
                                            <source :src="fileUrlSource">
                                        </video>
                                    </div>
                                    <div v-else-if="canPreview && file.mime_type == 'audio'" style="display:flex;height: -webkit-fill-available;max-height: 70vh;">                    
                                        <audio controls style="margin: auto;">
                                            <source :src="fileUrlSource">
                                        </audio>
                                    </div>
                                    <div id="docViewer" v-if="canPreview && file.mime_type == 'application'">
                                        <iframe id="docloaderframe" v-if="file.extension !== 'pdf'" @load="docLoader = false" class="docViewer-frame" v-show="!docLoader" :src="docUrl"></iframe>
                                        <div v-show="canvasElementShow" class="signCanvas" :class="{'canSign' : isDrawing == false }" :id="'signCanvas_' + file.id + '_' + file.message_id">
                                            <div v-if="!isDrawing" style="display:flex; flex-direction: column; background:white; padding: 20px 20px 0;">
                                                <canvas :id="'canvas' + file.id + '_' + file.message_id" class="canvasClass" ref="signature"  style="background:white; z-index:1;border:1px dotted black;">
                                                </canvas>
                                                <span  style="font-size:16px; color:black;z-index:1;line-height:30px;">{{ $t('signBelow') }}</span>
                                            </div>
                                            <div v-if="isDrawing" :id="'signImage' + file.id + '_' + file.message_id" style="z-index: 2;display:flex; flex-direction: column;position:relative;">
                                                <img class="resizeable" v-if="isDrawing && file.extension == 'pdf'" :src="imgData" style="z-index:2; border: 1px solid black;touch-action:none;" />
                                                <!-- <span v-if="isDrawing" style="font-size:small;color:black;z-index:1;line-height:30px;background:#ddd;">{{ $t('dragSign') }}</span> -->
                                                <div class="corner" id="topRight"></div>
                                                <!-- <div class="corner" id="topLeft"></div>
                                                <div class="corner" id="bottomRight"></div> -->
                                                <div class="corner" id="bottomLeft"></div>
                                            </div>
                                            
                                          
                                            <!-- <div style="position:absolute; bottom:0; z-index: 1; display:flex; flex-direction: column; align-items: flex-start;">
                                               
                                            </div> -->
                                        </div>
                                        <div ref="scrollParent" @scroll="scrollEvent" v-if="docUrl && file.extension == 'pdf'" id="scrollParent" style="height:100%; overflow:hidden auto;position:relative;">
                                            <!-- <div v-if="imageUrls">
                                                <img v-for="imgUrl in imageUrls" :src="imgUrl.src" style="position:absolute; z-index: 1;" :style="{ top: `${imgUrl.y}px`, left: `${imgUrl.x}px`, width: `${imgUrl.width}px`, height: `${imgUrl.height}px`}"/>
                                            </div> -->
                                            <!-- <PDFviewer :docUrl="docUrl"></PDFviewer> -->
                                            <vue-pdf-embed @loaded="onPdfLoaded(file)" style="position:relative;" :disableTextLayer="true" :disableAnnotationLayer="true" ref="pdf" :id="'pdfElement_' + file.id + '_' + file.message_id" :source="docUrl"/>
                                        </div>
                                        <div v-if="docLoader" style="position:absolute;width:100%;height:100%;left:0;top:0;background:green;display:flex;">
                                            <div id="loaderMini" style="background:var(--background-color);">
                                                <div class="spinner-mini"></div>
                                            </div> 
                                        </div>
                                        <div class="pdfButton-wrapper">
                                            <button v-if="canSign && !canvasElementShow" @click="electronicSignatureRequest(currentFile)" class="signatureButton cursor-pointer" style="margin-right:5px;">{{$t('drawSignature')}}</button>                                                                                                   
                                            
                                            <button v-show="canvasElementShow && !isDragging" :disabled="processing" class="signatureButton cursor-pointer" style="margin-right:5px;" @click="signImageAdd(file)">
                                                <span v-if="!processing">{{$t('pdfSave')}}</span>
                                                <div v-if="processing" id="loaderMini">
                                                    <div class="spinner-nano" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                                                </div>
                                            </button>
                                            <button v-show="canvasElementShow && !isDragging" class="signatureButton cursor-pointer" style="margin-right:5px;" type="button" @click="clear">{{$t('eraseSignature')}}</button>
                                            <button v-show="canvasElementShow && !isDragging" class="signatureButton cursor-pointer" style="margin-right:5px;" type="button" @click="reset">{{$t('resetSignature')}}</button>
                                            <button v-if="isDragging" :disabled="processing" class="signatureButton cursor-pointer" style="margin-right:5px;" @click="savePdf(file)">
                                                <span v-if="!processing">{{$t('signStamp')}}</span>
                                                <div v-if="processing" id="loaderMini">
                                                    <div class="spinner-nano" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                                                </div>
                                            </button>
                                            <button v-if="isDragging" :disabled="processing" class="signatureButton cursor-pointer" style="margin-right:5px;" @click="cancelSign(file)">
                                                <span>{{$t('cancelSignature')}}</span>
                                            </button>
                                            
                                        </div>
                                    </div>
                                    <div v-else-if="canPreview && file.mime_type == 'text'" style="height: calc(100% - 37px);width:100%;">
                                        <object
                                            v-if="file.extension === 'txt'"
                                            :data="fileUrlSource"
                                            type="text/html"
                                            width="100%"
                                            height="100%"
                                        ></object>
                                        <div v-else class="unsupportedFileWindow">
                                        {{ $t('unsupportedFile') }}
                                        </div>
                                    </div>
                                    <div v-else-if="!canPreview" class="unsupportedFileWindow">
                                        {{$t('unsupportedFile')}}
                                    </div>
                                </div>
                            </swiper-slide>                     
                        </swiper>                  
                    </div>
                    <div class="second-swiper-wrapper" ref="secondswiper">                     
                            <swiper class="swiper gallery-thumbs" style="background:none;border:none;" 
                                :modules="modules"
                                :slides-per-view="4"
                                :watch-slides-progress="true"
                                @swiper="setThumbsSwiper"
                            >
                                <swiper-slide :key="file.id" v-for="(file) in $store.state.filePreview.files"> 
                                    <div class="swiper-zoom-container ssliderItem">
                                        
                                        <img style="max-width:100%;margin:auto;max-height:100%;" v-if="file.mime_type == 'image' && source == 'message'" :src="$store.state.baseLocation + '/shared_files/'+ file.board_id + '/' + file.id + '_' + file.user_id + '_' + file.message_id + '.' + file.extension">                                                                               
                                         <div v-if="file.mime_type !== 'image'" style="position:relative;">
                                            <FileIcon :ext="file.extension"/>
                                        </div>
                                    </div>
                                </swiper-slide>                           
                            </swiper>
                    </div>
                </div>                
            </div> 
               
        </div>   
        <Transition name="modalFade">
            <ConfirmWindow @reload="getMessageList" v-if="confirmWindow" :requestType="requestType" :file="currentFile" :message="signRequestData" @closeMe="confirmWindow = false"/>
        </Transition>    
    </div> 
</template>

<script>
    import { Swiper, SwiperSlide } from 'swiper/vue';
    import 'swiper/css'
    import { Navigation, Thumbs } from 'swiper';
    import 'swiper/css/navigation'
    import 'swiper/css/thumbs'
    import VuePdfEmbed from 'vue-pdf-embed'
    import { filesize } from 'filesize';
    import ConfirmWindow from '../../../Board/Message/ConfirmWindow.vue'
    import { PDFDocument } from 'pdf-lib'
    import SignaturePad from 'signature_pad'
    import interact from 'interactjs'
    import FileIcon from '../../Mixed/FileIcon.vue';
    export default {
        components: {
            Swiper,
            SwiperSlide,
            VuePdfEmbed,
            ConfirmWindow,
            FileIcon
        },
        setup(){
            return {
                modules: [Navigation, Thumbs]
            }
        },
        data(){
            return{
                f_index: 0,
                fileKey: Math.floor(Math.random() * 1000),
                docUrl: '',
                docLoader: false,
                fileMenuLayer: 0,
                thumbsSwiper: null,
                topSwiper: null,
                canvasElementShow: false,
                page: '',
                isDrawing: false,
                modifiedPdfBytes: null,
                currentPage: 0,
                pageHeight: 0,
                isDragging: false,
                confirmWindow: false,
                requestType: '',
                signRequestData: null,
                edit_flag: 0,
                edit_user: [],
                processing: false,
                signaturePad: null,
                imgData: null,
                imageUrls: [],
                pdfViewer: null
            }
        },
        beforeUnmount() {
            if(this.pdfViewer){
                this.pdfViewer.removeEventListener('scroll', this.scrollEvent)
            }
            
        },
        mounted() {
            
            this.f_index = this.$store.state.filePreview.index
            if(this.source == 'message'){
                this.topSwiper.slideTo(this.f_index, false)
                this.thumbsSwiper.slideTo(this.f_index, false)
            }
            const firstFile = this.currentFile
            const doc_extensions = ["xlsx", "xlsm", "xlsb", "xltx", "xls", "xml", "xlam", "xlr", "xlw", "xla",
                        "doc", "docm", "docx", "dot", "dotx",
                        "potm", "potx", "ppam", "pps", "ppsm", "ppsx", "ppt", "pptm", "pptx","pdf"                    
                    ];
            if(doc_extensions.indexOf(firstFile.extension) > -1 && this.f_index == 0){
                this.docLoader = true
                this.getDocs()
            }
        },
        computed:{
            canSign(){
                const unsignedUsers = this.currentFile.unsigned_users;
                if(unsignedUsers){
                    const includesUser = Object.values(unsignedUsers).some(user => user.id === this.$store.state.user.id);
                    return includesUser
                }
                return false
            },
            message(){
                return this.$store.state.filePreview.message
            },
            canModify(){
                const owner = this.currentFile.user_id == this.$store.state.user.id          
                return owner         
            },
            isAdmin(){
                const users = this.$store.state.activeBoard.board_to_users
                const me = users.filter(ob => ob.user_id == this.$store.state.user.id);
                return me && me[0].admin_flag == 1
            },
            source(){
                return this.$store.state.filePreview.source
            },
            currentFile(){
                return this.$store.state.filePreview.files[this.f_index]
            },
            fileUrlSource(){
                
                if(this.source == 'message'){                    
                    return this.$store.state.baseLocation + 
                        '/shared_files/'+ 
                        this.currentFile.board_id + '/' + 
                        this.currentFile.id + '_' + 
                        this.currentFile.user_id + '_' + 
                        this.currentFile.message_id + '.' + 
                        this.currentFile.extension
                }
            },
            
            canPreview(){
                const supported = [
                    'image', 'text','video', 'audio'
                ]
                const exist_index = supported.indexOf(this.currentFile.mime_type)
                if(exist_index > -1){
                    return true
                }else if(this.currentFile.mime_type == 'application'){
                    var doc_extensions = ["xlsx", "xlsm", "xlsb", "xltx", "xls", "xml", "xlam", "xlr", "xlw", "xla",
                        "doc", "docm", "docx", "dot", "dotx",
                        "potm", "potx", "ppam", "pps", "ppsm", "ppsx", "ppt", "pptm", "pptx","pdf"                   
                    ];
                    
                    if(doc_extensions.indexOf(this.currentFile.extension) > -1){
                        return true
                    }else{
                        return false
                    }  
                }else{
                    return false
                }
                
            },
                    
        },
        methods:{
            interactPDF(file){
                const angleScale = {
                    angle: 0,
                    scale: 1
                }
                let x = ''
                let y = ''
                var gestureArea = document.getElementById('docViewer')
                
                interact(`${'#' + 'signImage' + file.id + '_' + file.message_id}`).resizable({
                    // resize from all edges and corners
                    edges: { 
                        right: '#topRight',  
                        top: '#topRight',
                        left: '#bottomLeft',  
                        bottom: '#bottomLeft',  
                    },
                    constrain: {
                        width: true,
                        height: true,
                    },
                    listeners: {
                        move (event) {
                            var target = event.target
                            var x = (parseFloat(target.getAttribute('data-x')) || 0)
                            var y = (parseFloat(target.getAttribute('data-y')) || 0)

                            target.style.width = event.rect.width + 'px'
                            target.style.height = event.rect.height + 'px'
                        }
                    },
                    modifiers: [
                    // keep the edges inside the parent
                        interact.modifiers.restrictEdges({
                            outer: 'parent'
                        }),
                        interact.modifiers.aspectRatio({
                            ratio: 'preserve',
                        }),

                        // minimum size
                        interact.modifiers.restrictSize({
                            min: { width: 100, height: 50 }
                        })
                    ],

                    inertia: true
                })
                .draggable({
                    listeners: { move: dragMoveListener },
                    inertia: true,
                    modifiers: [
                    interact.modifiers.restrictRect({
                            restriction: 'parent',
                            endOnly: true
                        })
                    ]
                })
                interact(gestureArea).gesturable({
                    listeners: {
                        move (event) {
                            var scaleElement = document.getElementById('signImage' + file.id + '_' + file.message_id)
                            var currentScale = event.scale * angleScale.scale
                            
                            scaleElement.style.transform = `translate(${x}px, ${y}px) scale(${currentScale})`
                        },
                        end (event) {
                            angleScale.scale = angleScale.scale * event.scale
                        }
                    }
                })
                function dragMoveListener(event){
                    const target = event.target;
                    x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                    target.style.transform = `translate(${x}px, ${y}px) scale(${angleScale.scale})`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                }
            },
            
            getMessageList(){
                this.filePreviewClose()
                emitter.emit('updateMessages')
            },
            signRequest(){
                this.signRequestData = this.message
                this.confirmWindow = true
                this.requestType = 'sign'
            },
            scrollEvent(){
                const container = this.$refs.scrollParent[0];
                if(container){

                
                    const scrollTop = container.scrollTop;
                    let newPage = 0
                    if(this.$store.state.mobile){
                        newPage = Math.round(scrollTop / this.pageHeight);
                    }else{
                        newPage = Math.ceil(scrollTop / this.pageHeight);
                    }
                    
                    if (newPage !== this.currentPage) {
                        this.currentPage = newPage;
                    }
                }
            },
            reset(){
                this.signaturePad.clear()
            },
            clear(){
                var data = this.signaturePad.toData();
                if (data) {
                    data.pop(); // remove the last dot or line
                    this.signaturePad.fromData(data);
                }            
            },
            canvasCreate(file){
                setTimeout(() =>{
                    const canvas = document.getElementById('canvas' + file.id + '_' + file.message_id);
                    this.signaturePad = new SignaturePad(canvas)
                    // canvas.style.position = 'absolute';
                    // canvas.style.right = 0 + 'px';
                    // canvas.style.bottom = 0 + 'px';
                    if(this.$store.state.mobile){
                        canvas.width = 300
                        canvas.height = 150
                    }else{
                        canvas.width = 500
                        canvas.height = 250
                    }
                })
                
            },
            onPdfLoaded(file) {
                setTimeout(() => {
                    this.pdfViewer = document.getElementById('pdfElement_' + file.id + '_' + file.message_id);
                    this.pdfViewer.addEventListener('scroll', this.scrollEvent)
                    this.pageHeight = this.pdfViewer.firstElementChild.clientHeight
                    
                    this.canvasCreate(file)
                        
                    this.interactPDF(file) 

                
                       
                }, 1000)
                
            },
            
            savePdf: async function(file){
                

                let pageIndex = 0
                if(this.$store.state.mobile){
                    pageIndex = this.currentPage
                }else{
                    pageIndex = this.currentPage - 1
                }
                if(pageIndex < 0){
                    pageIndex = 0
                }
                let pdfDoc = ''
                let imageBytes = ''
                let pngImage = ''
                let page = ''
                let pageWidth = ''
                let pageHeight = ''

                const signImageGet = document.getElementById('signImage' + file.id + '_' + file.message_id);
                const contentRect = this.pdfViewer.children[pageIndex].getBoundingClientRect()
                const markRect = signImageGet.getBoundingClientRect();
                const markX = markRect.left - contentRect.left;
                const markY = markRect.top - contentRect.top;
                const percentLeft = markX / contentRect.width * 100;
                const percentTop = markY / contentRect.height * 100;
                const percentLeft1 = Math.max(0, Math.min(100, percentLeft));
                const percentTop1 = Math.max(0, Math.min(100, percentTop));
                
                console.log(markX, Math.floor(markY))
                if(this.modifiedPdfBytes){
                    pdfDoc = await PDFDocument.load(this.modifiedPdfBytes);
                }else{
                    const existingPdfBytes = await fetch(this.docUrl).then(res => res.arrayBuffer());
                    pdfDoc = await PDFDocument.load(existingPdfBytes);
                }
                imageBytes = await fetch(this.imgData).then(res => res.arrayBuffer());
                pngImage = await pdfDoc.embedPng(imageBytes);
                page = pdfDoc.getPages()[pageIndex];
                pageWidth = page.getWidth();
                pageHeight = page.getHeight();
                const perImgWidth = markRect.width / contentRect.width * 100
                const perImgHeight = markRect.height / contentRect.height * 100
                const fromLeft = percentLeft1
                const fromBottom = 100 - percentTop1 - perImgHeight
                const x1 = pageWidth * fromLeft / 100;
                const y1 = pageHeight * fromBottom / 100;
                
                const imgWidth = pageWidth * perImgWidth / 100;
                const imgHeight = pageHeight * perImgHeight / 100;
                // let newY = markY
                // if(pageIndex > 0){
                //    newY = markY + (contentRect.height * pageIndex)
                // }
                
                // const newImage = {
                //     src: this.imgData,
                //     x: markX,
                //     y: newY,
                //     width: markRect.width,
                //     height: markRect.height
                // }
                // this.imageUrls.push(newImage)

                // console.log(this.imageUrls)
                page.drawImage(pngImage, {
                    x: x1,
                    y: y1,
                    width: imgWidth,
                    height: imgHeight,
                });
                // Save the modified PDF
                this.modifiedPdfBytes = await pdfDoc.save();
                console.log(this.modifiedPdfBytes)
                this.downloadPdf(file)
                
                
            },
            cancelSign(file){
                this.imgData = null
                this.isDragging = false
                this.modifiedPdfBytes = null
                this.imageUrls = []
                this.isDrawing = false
                setTimeout(() => {
                    this.canvasCreate(file)
                })
                
            },
            downloadPdf(item){
                if(this.modifiedPdfBytes){
                    const formData = new FormData()
                    const name = this.currentFile.name
                    const file = new File([this.modifiedPdfBytes], name, { type: 'application/pdf' });
                    // Add the name to the blob
                    formData.append(0, file)
                    // EventBus.$emit('uploadStart', formData, file)
                    formData.append('file_id', this.currentFile.id)
                    formData.append('board_id', this.currentFile.board_id)
                    let reminder = this.$store.state.filePreview.reminder
                    console.log(reminder);
                    if(this.processing) return

                    this.processing = true

                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('finishSignature') ,
                        closeButton: false, 
                        autoClose: false,
                        touchClose: false,
                        answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                        channel: uniqueChannell

                    })
                    emitter.on(uniqueChannell, (data) => { 
                        if(data.answer == this.$t('confirmToAction')){
                            axios.post('/signature_upload_api', formData)
                            .then(response => {
                                if(reminder == 'board'){
                                    this.getMessageList()
                                }else if(reminder == 'reminder'){
                                    emitter.emit('signedReload')
                                    this.getMessageList()
                                }
                                this.edit_flag = 0
                                this.edit_user = []
                                this.modifiedPdfBytes = null
                                this.processing = false
                            }).catch((error) => { 
                                    console.log(error)
                                    console.log('aaaaaaa')
                                    this.processing = false   
                                    emitter.emit('setToast', {
                                        active: true,  
                                        type: 'info', 
                                        content: this.$t('errorOnUpload') ,
                                        closeButton: true, 
                                        autoClose: false,
                                        answers: ['OK'],
                                    }) 
                                })
                                .then(() => {})
                        }else{
                            
                            this.cancelSign(item)
                            this.processing = false
                        
                            
                        }
                    });  
                    
                }
            },
            electronicSignatureRequest(item){

                if(item.extension != 'pdf'){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('onlyPdfFileUpload') ,
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK'],
                    })
                    return;

                }else{
                    // if(this.$store.state.mobile){
                    //     this.$refs.signature[this.f_index].style.pointerEvents = 'none'
                    // }
                    
                    const params = {
                        file_id: this.currentFile.id,
                    };
                    axios.post('/get_edit_user', params)
                    .then(response => {
                        if(response.data.user){
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: this.$t('otherUserUsing', {userName: response.data.user.name}) + '.' + this.$t('tryAgain') ,
                                closeButton: false, 
                                autoClose: false,
                                answers: ['OK'],
                            })
                            this.edit_flag = 1
                            
                        }else if(response.data.sign_path){
                            const path = response.data.sign_path
                            const uniqueChannell = Math.random().toString(36).substring(5);
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: this.$t('userYourSign') + '<br>' + this.$t('dragSign'),
                                closeButton: false, 
                                autoClose: false,
                                answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                                channel: uniqueChannell
                            })
                            emitter.on(uniqueChannell, (data) => { 
                                if(data.answer == this.$t('confirmToAction')){
                                    this.imgData = this.$store.state.baseLocation + '/user_signature/' + path 
                                    this.isDragging = true
                                    this.isDrawing = true
                                    this.canvasElementShow = true
                                }else{
                                    this.canvasElementShow = true;
                                }
                            })
                        }else{
                            const uniqueChannell = Math.random().toString(36).substring(5);
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: this.$t('dragSign') ,
                                closeButton: false, 
                                autoClose: false,
                                answers: ['OK'],
                                channel: uniqueChannell
                            })
                            emitter.on(uniqueChannell, (data) => { 
                                if(data.answer == ['OK']){
                                    this.canvasElementShow = true;
                                }
                            })
                            
                        }
                        
                    }).catch((error) => {    
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: this.$t('commonError') ,
                                closeButton: false, 
                                autoClose: false,
                                answers: ['OK'],
                            }) 
                        })
                        .then(() => {})
                    setTimeout(() => {
                        
                    if(this.edit_flag == 1){
                        return
                    }    
                    
                    
                    
                    
                    setTimeout(() => {
                        this.topSwiper.allowClick = false
                        this.thumbsSwiper.allowClick = false
                        this.topSwiper.allowSlideNext = false
                        this.topSwiper.allowSlidePrev = false
                        this.topSwiper.allowTouchMove = false
                    },100)
                    this.$store.commit('setMenu', {name: '', id: null})
                    }, 500)
                }

                },
            signImageAdd(){
                if(!this.signaturePad.isEmpty()){
                    this.imgData = this.signaturePad.toDataURL();
                    const uniqueChannell = Math.random().toString(36).substring(5); 
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('saveSignature'),
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                        channel: uniqueChannell
                    })
                    emitter.on(uniqueChannell, (data) => { 
                        if(data.answer == this.$t('confirmToAction')){
                            axios.post('/save_user_signature', {sign: this.imgData})
                                .then(response => {
                                    
                                }).catch((error) => {   
                                    emitter.emit('setToast', {
                                        active: true,  
                                        type: 'info', 
                                        content: this.$t('errorOnUpload') ,
                                        closeButton: false, 
                                        autoClose: false,
                                        answers: ['OK'],
                                    }) 
                                })
                        }
                    })
                    this.isDragging = true
                    this.isDrawing = true
                    
                }else{
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('canbeDone'),
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK'],
                    }) 
                }
                
                // canvasData = canvasData.replace(/^data:image\/png;base64,/, '');
                // localStorage.setItem('imgData', canvasData)
                // setTimeout(() => {
                //     this.savePdf()
                // }, 500)
            },
            onSwiper(swiper){
                this.topSwiper = swiper
            },
            setThumbsSwiper(swiper){
                this.thumbsSwiper = swiper
            },  
            changeSwiperIndex(swiper) {
                    this.docUrl = ''
                    this.f_index = swiper.realIndex
                    const firstFile = this.currentFile
                    const doc_extensions = ["xlsx", "xlsm", "xlsb", "xltx", "xls", "xml", "xlam", "xlr", "xlw", "xla",
                            "doc", "docm", "docx", "dot", "dotx",
                            "potm", "potx", "ppam", "pps", "ppsm", "ppsx", "ppt", "pptm", "pptx","pdf"                   
                        ];
                    
                    if(doc_extensions.indexOf(firstFile.extension) > -1){
                        this.docLoader = true
                        this.getDocs()
                    }
            },    
            getDocs(){
                

                axios.post('/user_generate_file_key').then(response => {
                    let url
                    if(this.$store.state.filePreview.source == 'message'){
                        url = this.$store.state.baseLocation + 
                        '/shared_docs/' 
                        + this.currentFile.board_id 
                        + '/' + this.currentFile.id 
                        + '_' + this.currentFile.user_id 
                        + '_' + this.currentFile.message_id 
                        + '.' + this.currentFile.extension 
                        + '/' + response.data
                        + '/' +this.$store.state.user.id
                    }
                    
                    axios.get(url, {responseType: 'blob'}).then(res => {
                        // this.docUrl = window.URL.createObjectURL(new Blob([res.data]));
                        if(this.currentFile.extension == 'pdf'){
                            const durl = 'https://docs.google.com/gview?url=' + url + '&embedded=true'
                            console.log(url)
                            this.docUrl = url
                        }else{
                            const durl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + url
                            this.docUrl = durl
                        }
                        setTimeout(() => {
                            this.docLoader = false
                        },500)
                        
                        
                    }).catch(function (error) {
                        if (error.response) this.errorToast(this.$t('fileNotFound'))
                        else if (error.request) this.errorToast(this.$t('commonError'))
                        else this.errorToast(this.$t('fileNotFound'))   
                        setTimeout(() => {
                            this.docLoader = false
                        },500)                                           
                    }.bind(this)); 
                })
                return
            },
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: true, 
                    autoClose: true,

                }) 
                
            },            
            filePreviewClose(){
                const data = {
                    active: false,
                    files: [],
                    source: null,
                    source_board_id: null
                }
                this.$store.commit('setFilePreview', data)
            },
            fileNameFilter(file){
                const lastDot = file.name.lastIndexOf('.');
                const fileName = file.name.substring(0, lastDot);
                var str_lenght = fileName.length;
                if (str_lenght > 20) {
                    var sliced = file.name.slice(0, 20) + " ..." + file.extension;
                    return sliced;
                }
                return file.name;
            },
            fileSizeView (bytes) {
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
            downloadFile(){
                if(this.source == 'message'){                    
                    this.direcDownload();
                }
                this.$store.commit('setMenu', {name: '', id: null})

            },
            direcDownload(){
                let src, name;
                
                if(this.$store.state.filePreview.source == 'message'){
                    const path = this.currentFile.board_id + '/' + this.currentFile.id + '_' + this.currentFile.user_id + '_' + this.currentFile.message_id + '.' + this.currentFile.extension        
                    name = this.currentFile.name
                    src = this.$store.state.baseLocation + '/shared_files/'+ path;
                    
                }
                const link = document.createElement('a');
                link.href = src;
                link.download = '';
                link.setAttribute('download', name);
                document.body.appendChild(link);            
                link.click();  
                document.body.removeChild(link); 

            },
            shareTo(to){
                this.$store.commit('setMenu', {name: '', id: null})
                this.fileMenuLayer = 0
                if(this.source == 'file'){          
                    emitter.emit('previewToShare', to, this.currentFile)
                    if(to == 'board'){
                        const data = {
                            active: false,
                            files: [],
                            source: null,
                            source_board_id: null,
                            index: 0,
                            message: null
                        }
                        this.$store.commit('setFilePreview', data)
                    }
                }else if(this.source == 'message'){
                    if(to == 'board'){
                        let fileList = []
                        fileList.push(this.currentFile)
                        let data = {
                            active: true,
                            list: fileList,
                            drag: false
                        }
                        this.$store.commit('setFromBoardToFiles', data)
                        const data1 = {
                            active: true,
                            target: to,
                            message: this.$t('chooseChatToForward'),
                            files: []
                        }
                        this.$store.commit('setFileShareTo', data1)
                        const hide = {
                            active: false,
                            files: [],
                            source: null,
                            source_board_id: null,
                            index: 0,
                            message: null
                        }
                        this.$store.commit('setFilePreview', hide)
                        if(this.$store.state.mobile){
                            this.$router.push({name : 'board'})
                        }
                    }else{
                        emitter.emit('messagePreviewToShare', to, this.$store.state.filePreview.message, -1, this.currentFile)
                    }
                    
                }
            }
        }
    }
</script>
<style lang="scss">
    // .vue-pdf-embed > div {
      //  margin-bottom: 8px;
    //}
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
        margin-top:20px;
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
        max-height: 40px;
        padding: 15px;
        height: 30px;
        width: 40px;
        max-width: 60px;
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
      &.gallery-thumbs .swiper-slide {
        width: 25%;
        height: 100%;
        transition-property: none;
        opacity: 0.5;
        background:none;
        border:none;
        flex-basis:100px;
      }
      &.gallery-thumbs .swiper-zoom-container{
            display: flex;
            align-items: center;
            justify-content: center;
        }
      &.gallery-thumbs .swiper-slide-thumb-active {
        opacity: 1;
        .swiper-zoom-container.ssliderItem{
            border: solid thin var(--hoverBorder);
        }
      }
      
    }
    .drawing{
        pointer-events: auto !important;
    }
    .swiper-button-prev:after, .swiper-button-next:after {
        font-size:25px;
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
        height:calc(100% - 37px);
        width:100%;
        position: relative;
    }
    .docViewer-frame{
        height: 100%;
        width: 100%;
    }
    .signatureButton{
        padding: 5px 10px 5px 10px;
        font-size: 14px;
        line-height: 1.5;
        border-radius: 0px;
        background: var(--primary-button);
        color: #fff;
    }
    
    
    @media screen and (min-width: 760px) {
        .swiper-zoom-container.width90{
            width: 90%;
            height: 95%;           
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
        .pdfButton-wrapper{
            margin-top: 3px;
        }
        .resizeable{
            width: 300px;
            height: 150px;
        }
        .width90{
            height: 100%;
            display: flex;
        }
        .file-preview-container{
            height: 95%;
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
        .swiper-zoom-container.ssliderItem{
            width: 30px;
            height: 20px;
        }
        .signatureButton{
            height: 35px;
            font-size: 14px;
            line-height: 0;
        }
    }
</style>
