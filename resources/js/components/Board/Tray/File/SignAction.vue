<template>
    <div>
        <Teleport to=".mySwiper-wrapper">
            <div v-if="canvasElementShow" :class="['signCanvas', {'overlay' : !isDragging}]">

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
                        <canvas :id="'canvas' + file.id + '_' + file.message_id" class="canvasClass" ref="signaturePadDraw"  style="background:white; z-index:1;border:1px dotted black;"></canvas>
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
        <Teleport to=".md-window">
            <div v-if="isDragging" ref="resizable" :id="'signImage' + file.id + '_' + file.message_id" style="z-index: 2;display:flex; flex-direction: column;position:absolute;">
                <img class="resizeable" id="resizeable" :src="imgData" :style="{transform: `scale(${scale})`}"/>
                <div class="corner" id="topRight"></div>
                <div class="corner" id="bottomLeft"></div>
            </div>
                
            </Teleport>
        <div class="pdfButton-wrapper">
            <button class="signatureButton cursor-pointer" v-if="!canvasElementShow" @click="electronicSignatureRequest">サインする</button>
            <button class="signatureButton cursor-pointer" v-if="!canvasElementShow" @click="notSign">サインしない</button>
            
            <button v-if="isDragging" :disabled="processing" class="signatureButton cursor-pointer" style="margin-right:5px;" @click="savePdf()">
                <span v-if="!processing">保存</span>
                <div v-if="processing" id="loaderMini">
                    <div class="spinner-nano" style="border: 4px #ffffff solid;border-top: 4px black solid;"></div>
                </div>
            </button>
            <button v-if="isDragging" :disabled="processing" class="signatureButton cursor-pointer" style="margin-right:5px;" @click="cancelSign()">
                <span>キャンセル</span>
            </button>
            <button v-if="file.multiple_flag == 1 && !file.unsigned_users.length && file.user_id == auth.id" class="signatureButton cursor-pointer" style="margin-right:5px;" type="button" @click="downloadAll()">すべてダウンロード</button>
        </div>
    </div>

</template>
<script setup>
import { ref, nextTick, inject } from 'vue';
import SignaturePad from 'signature_pad'
import { PDFDocument } from 'pdf-lib'
import { useAuthUserStore } from '@/store/auth';
import { useFilePreview } from '@/store/filePreview';
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
    const menu = useMenuStore()
    const responsive = useResponsive()
    const props = defineProps(['file', 'viewer', 'source'])
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
    const scale = ref(1)
    const posX = ref(0)
    const posY = ref(0)
    const auth = useAuthUserStore()
    const refresh = inject('getIncompleteMessage')
    const filePreview = useFilePreview()
    const { notify, confirm } = inject('dialog')
    const savePdf = async () => {
        const viewer = props.viewer()
        let pageIndex = 0
        pageIndex = viewer.currentPageNumber - 1
        if(pageIndex < 0){
            pageIndex = 0
        }
        let pdfDoc = ''
        let imageBytes = ''
        let pngImage = ''
        let page = ''
        let pageWidth = ''
        let pageHeight = ''
        let modifiedPdfBytes = ''

        const signImageGet = document.getElementById('signImage' + props.file.id + '_' + props.file.message_id);
        const parent = document.getElementById('docViewer')
        const parentRect = parent.getBoundingClientRect()
        const contentRect = viewer._pages[pageIndex].canvas.getBoundingClientRect()
        const markRect = signImageGet.getBoundingClientRect();
        const markX = markRect.left - parentRect.left - contentRect.left
        const markY = markRect.top - parentRect.top - contentRect.top
        const percentLeft = markX / viewer._pages[pageIndex].width * 100;
        const percentTop = markY / viewer._pages[pageIndex].height * 100;
        const percentLeft1 = Math.max(0, Math.min(100, percentLeft));
        const percentTop1 = Math.max(0, Math.min(100, percentTop));
        const existingPdfBytes = await fetch(props.source).then(res => res.arrayBuffer());
        pdfDoc = await PDFDocument.load(existingPdfBytes);
        imageBytes = await fetch(imgData.value).then(res => res.arrayBuffer());
        pngImage = await pdfDoc.embedPng(imageBytes);
        page = pdfDoc.getPages()[pageIndex];
        pageWidth = page.getWidth();
        pageHeight = page.getHeight();
        const perImgWidth = markRect.width / viewer._pages[pageIndex].width * 100
        const perImgHeight = markRect.height / viewer._pages[pageIndex].height * 100
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
        modifiedPdfBytes = await pdfDoc.save();
        downloadPdf(modifiedPdfBytes)
        
    }
    const electronicSignatureRequest = async() => {

        try{
            
            const response = await axios.post('/get_edit_user', {file_id: props.file.id})

            if(response.data.sign_path){
                mySignature.value = response.data.sign_path
            }
            if(response.data.user){
                notify(`<strong>${response.data.user.name}</strong>さんが現在このファイルにサイン中です。同時にサインすることはできません<br>30分後にもう一度お試しください`)
                return
            }
            canvasElementShow.value = true;
            nextTick(() => {
                canvasCreate()    
            })

        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
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
        for(let file of filePreview.files){
            if(file.multiple_flag == 2){
                const path = file.source_board_id + '/' + file.id + '_' + file.user_id + '_' + file.message_id + '.' + file.extension        
                name = file.name
                src = '/cdn/shared_files/'+ path;
                const link = document.createElement('a');
                link.href = src;
                link.download = '';
                link.setAttribute('download', name);
                document.body.appendChild(link);            
                link.click();  
                document.body.removeChild(link); 
            }
            
        }
        menu.setMenu( {name: '', id: null})
    }
    const selectLineWidth = (width) => {
        signaturePad.value.maxWidth = width;
        selectedLineWidth.value = width
        menu.setMenu( {id: null, name: ''})
    }
    const notSign = () => {
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
        axios.post('/cancel_sign', params).then(response => {
            
                closePdf()
            
        })
    }
    const closePdf = () => {
        const data = {
            active: false,
            files: [],
            source: null,
            source_board_id: null
        }
        filePreview.setFilePreview(data)
        refresh()
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
        try{         
            const answer = await confirm('一度サインすると、変更することはできません。よろしいですか?')  
            if(!answer) return
                const formData = new FormData()
                const name = props.file.name
                const file = new File([modifiedPdf], name, { type: 'application/pdf' });
                formData.append(0, file)
                formData.append('file_id', props.file.id)
                formData.append('board_id', props.file.board_id)
                await axios.post('/signature_upload_api', formData)                   
                closePdf()
                modifiedPdfBytes.value = null
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            processing.value = false 
        }
    }

    const signImageAdd = async() => {
        if(!signaturePad.value.isEmpty()){
            imgData.value = signaturePad.value.toDataURL();
            const answer = await confirm('このサインをマイサインとして保存しますか?')
            if(!answer) return
            await axios.post('/save_user_signature', {sign: imgData.value})
            const response = await axios.post('/profile_get_update_user', {id: auth.activeUser.id})             
            if(response.data && Object.hasOwn(response.data, 'id')){   
                auth.setUser(response.data)                     
            }               
            isDragging.value = true
            
            nextTick(() => {               
                interactPDF()
            })
            
            
        }else{
            notify('サインは必須です。')
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
        const instance = document.getElementById('mainPdfParent')
        const angleScale = {
            angle: 0,
            scale: 1
        }

        const viewerWidth = instance.clientWidth
        const viewerRect = instance.getBoundingClientRect()
        const imageWidth = resizable.value.clientWidth
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
    }
</style>