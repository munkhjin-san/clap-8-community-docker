<template>

    <div :class="['formFileUploadArea', {dropHereArea: entered}]"
        @click="triggerUploader"
        @dragenter="setEnter(true)"
        @dragleave="setEnter(false)"
        @dragover.prevent
        @drop.prevent="dropFile"
        style="position: relative;">   

        <div class="uploadMask" v-if="uploadingProgress"><div>アップロード中</div><div> {{uploadingProgress }}%</div></div>
        <div :class="['form-plc', 'smallPlc']">
            <label for="file" class="file-label">
                <span style="font-size: 14px;">{{ placeholder }}</span>
            </label>
            <input accept="image/*, video/*" type="file" ref="formUploader" name="file" id="file" @change="fileSelected" style="display: none;">
        </div> 
        <div class="file-area-content" v-if="uploadFiles" style="padding: 20px 10px 10px 10px;margin:0">
            <div class="file-wrap" v-for="(file, index) in uploadFiles" @click.stop>   
                <div class="file-area-container">
                    <div class="flex-centered">             
                        <div v-if="isImageOrVideo(file) == 'image'" style="max-width:65px;height:40px;display: flex;min-width: 40px;min-height: 40px;">  
                            <img
                                style="max-width:100%;margin:auto;max-height:100%;" 
                                
                                class="list-image-mobile" 
                                :src=fileSrc(file)
                            />
                        </div>
                        <div v-else style="position:relative;">
                            <FileIcon :ext="file.extension"/>
                        </div>
                        <div style="line-height: 1.5;max-width: calc(100% - 35px);margin-left:5px;">
                            <p :title="file.name" class="shared-file-name" style="cursor:text">{{fileName(file)}}</p>                           
                            
                        </div>
                    </div>                    
                </div>
                <div @click.stop="fileUpCancel(file.id, index)" class="cancelButton">
                    <svg @click.prevent version="1.1" xmlns="http://www.w3.org/2000/svg" width="7" height="7" viewBox="0 0 32 32" fill="var(--background-color)">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>  
                </div>  
            </div>
        </div>
    </div>   

</template>
<script>
import FileIcon from '../../Board/Mixed/FileIcon.vue'
export default{
    props: ['initialValue', 'path', 'userId', 'intro_flag', 'placeholder'],
    emits: ['updated', 'saved', 'getUserInfo', 'cancel'],
    data(){
        return{
            entered: false,
            uploadingProgress: 0,
            uploadFiles: this.initialValue ? this.initialValue : [],
            canceledImageIds: []
        }
    },
    components:{FileIcon},
    watch:{
        uploadFiles(after){
            this.$emit('updated', after)
        }
    },
    methods:{
        fileUpCancel(id, index){
            if(id){
                this.canceledImageIds.push(id)
                this.$emit('cancel', this.canceledImageIds)
            }
            this.uploadFiles.splice(index, 1);
        },
        isImageOrVideo(file) {
            if(file.mime_type){
                return file.mime_type
            }else{
                const imageExtensions = ['jpg', 'jpeg', 'png', 'gif',];

                const fileExtension = file.split('.').pop().toLowerCase();

                if (imageExtensions.includes(fileExtension)) {
                    return 'image';
                } else {
                    return 'unknown';
                }
            }
        },
        fileName(file){
            if(file.name){
                return file.name
            }else{
                const exp = file.split('/')
                return exp[1]
            }
        },
        fileSrc(file){
            return file.path ? `${this.$store.state.baseLocation}/user_album/${file.user_id}/${file.id}_${file.user_id}_${file.path}.${file.extension}` : `${this.$store.state.baseLocation}/${file}`
        },
        
        dropFile(){
            this.entered = false
        },
        fileSelected(){
            if(event.target.files && event.target.files.length){
                console.log(event)
                this.uploadStart(event.target.files[0])
            }
        },
        triggerUploader(){
            this.$refs.formUploader.click();
        },
        setEnter(val){
            this.entered = val
        },
        dropFile(){  
            if(event.dataTransfer.files && event.dataTransfer.files.length){
                
                this.uploadStart(event.dataTransfer.files[0])
            }
            this.entered = false

        },
        uploadStart(file){
            if(file){
                const formData = new FormData()                    
                                        
                formData.append('file', file)
    
            
                axios.post('/user_file_upload', formData , { onUploadProgress: (e) => this.uploadingProgress = Math.floor((e.loaded * 100) / e.total) } )
                .then(response =>{                                       
                    const file = response.data
                    if(file == 'notimage'){
                        emitter.emit('setToast', {
                            active: true,  
                            type: 'info', 
                            content: '画像またはビデオをアップロードしてください。' ,
                            closeButton: false, 
                            autoClose: false,
                            answers: ['OK'],
                        })
                        
                        
                    }else{
                        
                        this.uploadFiles.push(file)
                        this.$emit('updated', this.uploadFiles)
                    }
                    
                    
                })
                .catch((error) => {
                })
                .then(() => {
                    this.$emit('updated', this.uploadFiles)
                    setTimeout(() => {
                        this.uploadingProgress = 0
                    }, 300);
                    this.$refs.formUploader.value = ''

                });
            }
        },
        getFileExtension(fileName) {
            const lastDotIndex = fileName.lastIndexOf('.');
            if (lastDotIndex === -1) {
                return '';
            }
            return fileName.substring(lastDotIndex + 1).toLowerCase();
        },
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })              
        },
        
    }
}
</script>
<style>
.plus-svg{
    fill:var(--primary-color);
    margin:auto 10px;
    background: var(--bg3);
    border-radius: 50%;
    padding: 5px;
}
.preview-box{
    max-width:60px;
    height:60px;
    display: flex;
    max-height: 60px;
    width: 60px;
    min-width: 60px;
    min-height: 60px;
    padding: 10px;
    background: var(--bg2);
    align-items: center;
    justify-content: center;
}
.formFileUploadArea {
    width: 100%;
    border: 1px solid var(--primary-color);  
    transition: border 0.3s ease;
    position: relative;
    background: inherit;
    min-height: 60px;
    box-sizing: border-box;
}
.dropHereArea{
    border: 1px dashed var(--primary-color)!important; 
}
.uploadMask{
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,70%);
    display: flex;
    flex-direction: column;
    gap:15px;
    color: #fff;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    z-index: 5;
}
.cancelButton{
    width: 15px;
    height: 15px;
    background: var(--primary-color);
    color: var(--background-color);
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    right: 0;
    top: 0;
    border-radius: 50px;
    cursor: pointer;
}
</style>