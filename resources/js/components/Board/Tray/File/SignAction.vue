<template>
    <div>
        <Teleport :to="canvasTarget">
            <!-- Only while the pad is actually shown: during placement this
                 wrapper would otherwise stay as an empty absolutely-positioned
                 full-size box and swallow clicks on the 保存 row. -->
            <div v-if="canvasElementShow && !isDragging" :class="['signCanvas', {'overlay' : !isDragging}]">

                <div v-if="!isDragging" class="chatCreate scrollable" style="align-items: center;">
                    <div style="padding-top: 20px;align-self: baseline;">マイサイン</div>
                    <div @click="canvasElementShow = false" class="messageMenuContainer cursor-pointer" style="min-width: 30px;position: absolute;z-index: 5;right: 20px;top: 20px;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32" style="margin: auto; min-width: 14px;"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>
                    </div>
                    <div>
                        
                        <div v-if="auth.activeUser.sign_path" style="max-height: 30vh;">
                            <div style="width: 100%;display: flex;justify-content: center;max-height: 160px;">
                                <img style="width: auto;" :src="`/cdn/user_signatures/${auth.activeUser.id}_${auth.activeUser.sign_path}.png`"/>                                    
                            </div>
                            <div style="display: flex;justify-content: center;flex-direction: column;gap: 15px;margin-bottom: 15px;align-items: center;">
                                <button @click="useMySignature" class="signatureButton cursor-pointer" style="width: fit-content;">マイサインを利用する</button>
                                <div style="font-size: 14px;color: gray;">または下にサインを書いてください</div>
                            </div>
                            
                        </div>
                        
                        <div v-else style="display: flex;padding: 30px;justify-content: center;">
                            現在マイサインはありません。
                        </div>
                        

                    </div>
                    <div style="position: relative;">                            
                        <canvas :id="'canvas' + uid" class="canvasClass" ref="signaturePadDraw"  style="background:white; z-index:1;border:1px dotted black;"></canvas>
                        <div style="position: absolute;top: 10px;left: 10px;">
                            <div style="display: flex;position: relative;border: solid thin var(--formBorder);box-sizing: border-box;">
                                <div v-if="menu.id == 54 && menu.name == 'widthSelector'" id="widthSelector" class="lineOptions" style="left: -25px;top: 30px;right: auto;">
                                    <div v-for="num in 3" class="lineOption" @click.stop="selectLineWidth(num)" :class="{ selected: selectedLineWidth == num }">
                                        <div class="line" :style="{borderBottom: `${num}px solid black`}"></div>
                                    </div>
                                </div>
                                
                                <button class="cursor-pointer cbar-command" @click.stop="menu.setMenu( {id: 54, name: 'widthSelector'})">ペンの幅</button>                      
                                <button class="cursor-pointer cbar-command" style="border-left: solid thin var(--formBorder);" @click="undo">元に戻す</button>
                                <button class="cursor-pointer cbar-command" style="border-left: solid thin var(--formBorder);" @click="reset">リセット</button>
                            </div>                           
                        </div>                            
                    </div>
                    <div style="width: 100%;display: flex;justify-content: center;padding: 15px 0;">
                        <button class="signatureButton cursor-pointer" style="width: fit-content;" @click="signImageAdd" :loading="processing">配置する</button>
                    </div>
                </div>
                
            </div>
        </Teleport>
        <Teleport :to="dragTarget">
            <div v-if="isDragging" ref="resizable" :id="'signImage' + uid" style="z-index: 2;display:flex; flex-direction: column;position:absolute;">
                <img ref="imgRef" :class="['resizeable', { 'resizeable--fluid': !isChat }]" id="resizeable" :src="imgData"/>
                <div class="corner" id="topRight"></div>
                <div class="corner" id="bottomLeft"></div>
            </div>
                
        </Teleport>
        <div class="pdfButton-wrapper">
            <button class="signatureButton cursor-pointer" v-if="!canvasElementShow && signableFile" @click="electronicSignatureRequest">サインする</button>
            <button class="signatureButton cursor-pointer" v-if="isChat && !canvasElementShow && signableFile" @click="notSign">サインしない</button>
            <button v-if="isDragging" :disabled="processing" class="signatureButton cursor-pointer" style="margin-right:5px;" @click="savePdf()">
                <span v-if="!processing">保存</span>
                <div v-if="processing" id="loaderMini">
                    <div class="spinner-nano" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                </div>
            </button>
            <button v-if="isDragging" :disabled="processing" class="signatureButton cursor-pointer" style="margin-right:5px;" @click="cancelSign()">
                <span>キャンセル</span>
            </button>
            <button v-if="isChat && file.multiple_flag == 1 && !file.unsigned_users.length && file.user_id == auth.activeUser.id" class="signatureButton cursor-pointer" style="margin-right:5px;" type="button" @click="downloadAll()">すべてダウンロード</button>
        </div>
    </div>

</template>
<script setup>
import { ref, nextTick, inject, computed } from 'vue';
import SignaturePad from 'signature_pad'
import { useAuthUserStore } from '@/store/auth';
import { useFilePreview } from '@/store/filePreview';
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useBadgeStore } from '@/store/badge';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useDashboardStore } from '@/store/dashboard';
    const menu = useMenuStore()
    const responsive = useResponsive()
    // `file` stays the chat contract. The optional props below let another
    // feature (learning 誓約書) reuse this signer without touching chat: every
    // default reproduces the previous chat-only behaviour.
    const props = defineProps({
        file: { type: Object, default: () => ({}) },
        viewer: { type: Function, required: true },
        source: { type: String, required: true },
        // 'chat' keeps the message-file flow (lock check, upload, notSign).
        mode: { type: String, default: 'chat' },
        // When provided, receives the signed PDF bytes instead of the chat upload.
        saveHandler: { type: Function, default: null },
        // Host containers for the pad / draggable stamp.
        canvasTarget: { type: String, default: '.mySwiper-wrapper' },
        dragTarget: { type: String, default: '.md-window' },
        // Element the stamp is constrained to.
        boundsSelector: { type: String, default: '#mainPdfParent' },
        // Overrides the chat unsigned-users computation.
        canSign: { type: Boolean, default: null },
    })
    const isChat = computed(() => props.mode === 'chat')
    // Unique suffix for the pad/stamp element ids.
    const uid = computed(() => `${props.file?.id ?? 'x'}_${props.file?.message_id ?? props.mode}`)
    const canvasElementShow = ref(false)
    const isDragging = ref(false)
    const signaturePad = ref(null)
    const selectedLineWidth = ref(1)
    const imgData = ref(null)
    const modifiedPdfBytes = ref(null)
    const processing = ref(false)
    const signaturePadDraw = ref(null)
    const mySignature = ref(null)
    const resizable = ref(null)
    const imgRef = ref(null)
    const badge = useBadgeStore()
    const auth = useAuthUserStore()
    const refresh = inject('refreshRemind')
    const filePreview = useFilePreview()
    const api = useApi()
    const { ask, ping } = useDialog()
    const { getBatchDashboardData } = useDashboardStore()
    const refreshMessage = inject('refreshMessage')
    const signableFile = computed(() => {
        // Same Boolean-casting caveat as PdfViewer: branch on the mode, not on a
        // null sentinel, so chat always uses its own unsigned-users rule.
        if (!isChat.value) return props.canSign !== false
        const unsignedUsers = props.file.unsigned_users;
        const includesUser = Object.values(unsignedUsers).some(user => user.id === auth.activeUser.id && user.pivot.cancel_flag === 0);
        return includesUser && (props.file.multiple_flag == 2 || props.file.multiple_flag == 0)
    })
    const savePdf = async () => {
        const viewer = props.viewer()
        let pageIndex = 0
        pageIndex = viewer.currentPageNumber - 1
        if(pageIndex < 0){
            pageIndex = 0
        }

        const signImageGet = document.getElementById('signImage' + uid.value);
        const contentRect = viewer._pages[pageIndex].canvas.getBoundingClientRect()
        const markRect = signImageGet.getBoundingClientRect();
        let markX, markY
        if(isChat.value){
            // Chat keeps its original calculation (relative to #docViewer) so
            // existing signature placement is unchanged.
            const parent = document.getElementById('docViewer')
            const parentRect = parent.getBoundingClientRect()
            markX = markRect.left - parentRect.left - contentRect.left
            markY = markRect.top - parentRect.top - contentRect.top
        }else{
            // The page canvas lives inside the pdf.js iframe, so its rect is in
            // IFRAME coordinates while the stamp's rect is in this document's.
            // Shift the canvas into page coordinates before comparing, or every
            // signature lands offset by the iframe's own position. (Chat gets
            // this for free by subtracting its #docViewer wrapper.)
            const frame = viewer._pages[pageIndex].canvas.ownerDocument?.defaultView?.frameElement
            const frameRect = frame ? frame.getBoundingClientRect() : { left: 0, top: 0 }
            markX = markRect.left - (contentRect.left + frameRect.left)
            markY = markRect.top - (contentRect.top + frameRect.top)
        }
        const percentLeft = markX / viewer._pages[pageIndex].width * 100;
        const percentTop = markY / viewer._pages[pageIndex].height * 100;
        let percentLeft1 = Math.max(0, Math.min(100, percentLeft));
        let percentTop1 = Math.max(0, Math.min(100, percentTop));
        const existingPdfBytes = await fetch(props.source).then(res => res.arrayBuffer());
        const {PDFDocument} = await import('pdf-lib')
        const pdfDoc = await PDFDocument.load(existingPdfBytes);
        const imageBytes = await fetch(imgData.value).then(res => res.arrayBuffer());
        const pngImage = await pdfDoc.embedPng(imageBytes);
        const page = pdfDoc.getPages()[pageIndex];
        const pageWidth = page.getWidth();
        const pageHeight = page.getHeight();
        const perImgWidth = markRect.width / viewer._pages[pageIndex].width * 100
        const perImgHeight = markRect.height / viewer._pages[pageIndex].height * 100
        if(!isChat.value){
            // Keep the whole signature on the page: a stamp resting past an edge
            // would otherwise be drawn partly outside and come back clipped.
            percentLeft1 = Math.max(0, Math.min(percentLeft1, 100 - perImgWidth))
            percentTop1 = Math.max(0, Math.min(percentTop1, 100 - perImgHeight))
        }
        const fromLeft = percentLeft1
        const fromBottom = 100 - percentTop1 - perImgHeight
        const x1 = pageWidth * fromLeft / 100;
        const y1 = pageHeight * fromBottom / 100;
        const imgWidth = pageWidth * perImgWidth / 100;
        const imgHeight = pageHeight * perImgHeight / 100;
        page.drawImage(pngImage, {
            x: x1,
            y: y1,
            width: imgWidth,
            height: imgHeight,
        });
        const modifiedPdfBytes = await pdfDoc.save();
        downloadPdf(modifiedPdfBytes)
        
    }
    const electronicSignatureRequest = async() => {

        try{
            if(!isChat.value){
                // No cross-user file lock outside chat: each learner signs their own copy.
                if(auth.activeUser?.sign_path){
                    mySignature.value = `${auth.activeUser.id}_${auth.activeUser.sign_path}.png`
                }
                canvasElementShow.value = true;
                nextTick(() => { canvasCreate() })
                return
            }

            const response = await api.post('/get_edit_user', {file_id: props.file.id})

            if(response.sign_path){
                mySignature.value = response.sign_path
            }
            if(response.user){
                ping(`<strong>${response.user.name}</strong>さんが現在このファイルにサイン中です。同時にサインすることはできません<br>30分後にもう一度お試しください`)
                return
            }
            canvasElementShow.value = true;
            nextTick(() => {
                canvasCreate()    
            })

        }catch (e) {

            canvasElementShow.value = false;
            signaturePad.value = null
        }
    }
    const canvasCreate = () => {
        const canvas = signaturePadDraw.value
        signaturePad.value = new SignaturePad(canvas)
        signaturePad.value.maxWidth = 2
        const w = window.innerWidth - 50;
        if(responsive.mobile){
            canvas.width = w
            canvas.height = 200
        }else{
            canvas.width = 600
            canvas.height = 300
        }
      
        
    }
    const downloadAll = () => {
        let src, name;
        let index = 0;
        const delay = 100;
        const downloadNextFile = () => {
            if (index < filePreview.files.length) {
                const file = filePreview.files[index];
                if (file.multiple_flag == 2) {
                    const path = file.source_board_id + '/' + file.id + '_' + file.user_id + '_' + file.message_id + '.' + file.extension;        
                    name = file.name;
                    src = '/cdn/shared_files/' + path;
                    const link = document.createElement('a');
                    link.href = src;
                    link.download = '';
                    link.setAttribute('download', name);
                    document.body.appendChild(link);            
                    link.click();  
                    document.body.removeChild(link); 
                }
                index++;
                setTimeout(downloadNextFile, delay);
            } else {
                menu.setMenu({name: '', id: null});
            }
        };

        downloadNextFile();
    }
    const selectLineWidth = (width) => {
        signaturePad.value.maxWidth = width;
        selectedLineWidth.value = width
        menu.setMenu( {id: null, name: ''})
    }
    const notSign = async() => {
        let params = ''
        if(props.file.original_file_id){
            params = {
                original_id: props.file.original_file_id,
                file_id: props.file.id,
            };
        }else{
            params = {
                file_id: props.file.id,
            };
        }
        await api.post('/cancel_sign', params)            
        closePdf()
            
        
    }
    const closePdf = () => {
        const data = {
            active: false,
            files: [],
            source: null,
            source_board_id: null
        }
        filePreview.setFilePreview(data)
        setTimeout(() => {
            refresh('remind_unsigned_messages')
            getBatchDashboardData(['mustSignMessages'])
        }, 100);
    }
    const useMySignature = () => {
        imgData.value = '/cdn/user_signatures/' + mySignature.value 
        isDragging.value = true
        nextTick(() => {
            interactPDF()
        })
    }
    const downloadPdf = async(modifiedPdf) => {
        if(processing.value) return
        processing.value = true     
        const answer = await ask('一度サインすると、変更することはできません。よろしいですか?')  
        if(!answer.value){
            processing.value = false
            return
        }

        if(props.saveHandler){
            try{
                await props.saveHandler(modifiedPdf)
                closePdf()
                modifiedPdfBytes.value = null
            }finally{
                processing.value = false
            }
            return
        }

        const formData = new FormData()
        const name = props.file.name
        const file = new File([modifiedPdf], name, { type: 'application/pdf' });
        formData.append('file', file)
        formData.append('file_id', props.file.id)
        formData.append('board_id', props.file.board_id)
        const data = await api.post('/signature_upload_api', formData)   
                    
        closePdf()
        modifiedPdfBytes.value = null
        processing.value = false 
        ping('サインを保存しました。')
        refreshMessage?.()
    }

    const signImageAdd = async() => {
        if(!signaturePad.value.isEmpty()){
            imgData.value = signaturePad.value.toDataURL();
            const answer = await ask('このサインをマイサインとして保存しますか?')
            if(answer.value) {
                const response = await api.post('/save_user_signature', {sign: imgData.value})
                if(response.sign_path){
                    mySignature.value = `${response.id}_${response.sign_path}.png`
                }            
                if(response && Object.hasOwn(response, 'id')){   
                    auth.setUser(response)                     
                }
            }
                           
            isDragging.value = true
            
            setTimeout(() => {               
                interactPDF()
            }, 100)            
            
        }else{
            ping('サインは必須です。')
        }
    }
    const cancelSign = () => {
        imgData.value = null
        isDragging.value = false
        modifiedPdfBytes.value = null
        setTimeout(() => {
            canvasCreate()
        })
        
    }
    const reset = () => {
        signaturePad.value.clear()
    }
    const undo = () => {
        var data = signaturePad.value.toData();
        if (data) {
            data.pop(); // remove the last dot or line
            signaturePad.value.fromData(data);
        }            
    }
    const lastScale = ref(1)
    const interactPDF = async() => {
        const instance = document.querySelector(props.boundsSelector)
        const angleScale = {
            angle: 0,
            scale: 1
        }

        const viewerWidth = instance.clientWidth
        const viewerRect = instance.getBoundingClientRect()
        // Chat's teleport target happens to drop the stamp over the document.
        // Other hosts append it after their own content, so it starts BELOW the
        // page — and then saves as "off the bottom edge", clipped. Give it a
        // real starting box inside the visible page instead. Chat is untouched.
        if(!isChat.value){
            const v = props.viewer()
            const idx = Math.max(0, (v?.currentPageNumber ?? 1) - 1)
            const canvas = v?._pages?.[idx]?.canvas
            const host = resizable.value.offsetParent
            if(canvas && host){
                // Same frame conversion as savePdf: the canvas rect comes from
                // inside the pdf.js iframe.
                const frame = canvas.ownerDocument?.defaultView?.frameElement
                const fr = frame ? frame.getBoundingClientRect() : { left: 0, top: 0 }
                const raw = canvas.getBoundingClientRect()
                const cr = {
                    left: raw.left + fr.left,
                    top: raw.top + fr.top,
                    right: raw.right + fr.left,
                    bottom: raw.bottom + fr.top,
                    width: raw.width,
                    height: raw.height,
                }
                const hr = host.getBoundingClientRect()
                const visTop = Math.max(cr.top, viewerRect.top)
                const visBottom = Math.min(cr.bottom, viewerRect.bottom)
                // Size from the PNG's own pixels: the rendered image is
                // width/height:100% of this box, so reading it back would be
                // circular. Setting BOTH keeps box and image identical, which is
                // what savePdf measures.
                const naturalW = imgRef.value.naturalWidth || imgRef.value.clientWidth || 1
                const naturalH = imgRef.value.naturalHeight || imgRef.value.clientHeight || 1
                const width = Math.min(naturalW, cr.width * 0.35)
                const height = width * (naturalH / naturalW)
                resizable.value.style.width = `${Math.round(width)}px`
                resizable.value.style.height = `${Math.round(height)}px`
                resizable.value.style.left = `${Math.round(cr.left - hr.left + cr.width - width - 40)}px`
                resizable.value.style.top = `${Math.round(Math.max(visTop, visBottom - height - 40) - hr.top)}px`
                await nextTick()
            }
        }
        const imageWidth = imgRef.value.clientWidth
        const minScale = 0.3;
        const maxScale = viewerWidth / imageWidth;
        const minWidth = responsive.mobile ? 80 : 100
        const minHeight = responsive.mobile ? 40 : 50
        let lastWidth = imageWidth
        let x = ''
        let y = ''
        const interact = await import('interactjs')
        interact.default(resizable.value).resizable({
            edges: { 
                right: '#topRight',  
                top: '#topRight',
                left: '#bottomLeft',  
                bottom: '#bottomLeft',  
            },
            listeners: {
                move (event) {
                    var target = event.target
                    target.style.width = event.rect.width + 'px'
                    target.style.height = event.rect.height + 'px'
                }
            },
            modifiers: [
            // keep the edges inside the parent
                interact.default.modifiers.restrictEdges({
                    outer: 'parent'
                }),
                interact.default.modifiers.aspectRatio({
                    ratio: 'preserve',
                }),

                // minimum size
                interact.default.modifiers.restrictSize({
                    min: { width: minWidth, height: minHeight }
                })
            ],

            inertia: true
        })
        .draggable({
            listeners: { move: dragMoveListener },
            inertia: true,
            modifiers: [
            interact.default.modifiers.restrictRect({
                    restriction: viewerRect,
                    endOnly: true
                })
            ]
        })
        .gesturable({
            listeners: {
               move (event) {
                    var target = event.target

                    const newscale = lastScale.value * event.scale
                    const newWidth = lastWidth * event.scale
                    if (newscale > minScale && newscale < maxScale) {
                        target.style.width = newWidth + 'px' 
                    }
               },
               end (event) {
                    const newscale = lastScale.value * event.scale
                    const newWidth = lastWidth * event.scale
                    if (newscale > minScale && newscale < maxScale) {
                        lastScale.value = newscale;
                        lastWidth = newWidth
                    }
               }
            },
        })
        function dragMoveListener(event){
            const target = event.target;
            x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
            y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
            target.style.transform = `translate(${x}px, ${y}px) scale(${angleScale.scale})`;
            target.setAttribute('data-x', x);
            target.setAttribute('data-y', y);
        }
    }
</script>
<style scoped>
    .resizeable{
        z-index:2; 
        border: 1px solid black;
        touch-action: none;
        user-select: none;
    }
</style>

<!-- The signer used to rely on its chat hosts (FilePreview/PdfViewer) for these
     styles. Declaring them here (unscoped) lets other features mount it and get
     the same look; the chat copies remain untouched. -->
<style>
/* Chat lets the wrapper shrink-wrap the image, so the measured box always
   equals the image. Other hosts size the wrapper (to fit their narrower
   viewer), so the image must follow it — otherwise savePdf measures the box
   while the user positions a differently-sized image, and the signature lands
   offset. */
.resizeable--fluid{
    width: 100%;
    height: 100%;
    display: block;
    box-sizing: border-box;
}
.pdfButton-wrapper{
    width: 100%;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 15px;
    bottom: 0;
    z-index: 1;
}
.signCanvas{
    display: flex;
    width: 100%;
    height: 100%;
    justify-content: center;
    align-items: center;
    position: absolute;
    flex-direction: column;
}
.signatureButton{
    padding: 5px 10px;
    font-size: 12px;
    line-height: 1.5;
    border-radius: 0;
    background: var(--primary-button);
    color: #fff !important;
    cursor: pointer;
}
.cbar-command{
    padding: 5px;
    background: var(--bg3);
}
.canvasClass{
    -webkit-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
.lineOptions{
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
.lineOption{
    display: flex;
    align-items: center;
    padding: 10px;
    cursor: pointer;
    width: 100px;
}
.lineOption:hover{
    background-color: #f0f0f0;
}
.lineOption .line{
    flex-grow: 1;
}
.corner{
    position: absolute;
    width: 8px;
    height: 8px;
    background-color: #fff;
    border: 1px solid black;
    z-index: 3;
}
</style>
