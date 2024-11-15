<template>
    <div class="mobileFletty">
        <div style="height: 100%;position: relative;">
            <Transition name="modalFade">
                <div class="cal-month-loader" style="height: calc(100% - 60px); top: 60px;" v-if="initialLoader">
                    <div id="loaderMini">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
            </Transition>
            <div class="file-header-section" ref="memberHeader" :class="{ 'hiddenSearch': headerHidden }" >                
                <div class="file-header__inner">
                    <div class="mem-search-area" style="width:100%;">
                        <div class="searchBarInner memberSearchBar" style="width: 100%;margin-left: 0;">   
                            <input @input="setKeyWord" id="fileSearchInput" v-model="keyword" class="searchBarArea searchInputArea memberSearch" :placeholder="'ファイル名、拡張子、ユーザー名...'" type="text" style="margin: 0;width:100%;"/>
                            <div style="position: absolute;left: 10px;display: flex;height: 30px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="margin:auto;fill:#767676">
                                    <path d="M31.875 28.185c-0.034-0.444-0.159-0.888-0.376-1.275-0.102-0.194-0.239-0.387-0.387-0.547-0.171-0.194-0.239-0.251-0.342-0.353-0.752-0.752-1.526-1.492-2.278-2.232-0.387-0.376-0.763-0.74-1.15-1.116l-0.865-0.831-0.091-0.091c-0.034-0.034-0.080-0.068-0.125-0.102-0.080-0.068-0.171-0.137-0.262-0.194-0.729-0.49-1.651-0.626-2.471-0.376-0.148 0.046-0.285 0.091-0.421 0.159-0.068 0.034-0.148 0.023-0.205-0.034-0.251-0.262-0.854-0.9-1.139-1.207-0.057-0.068-0.068-0.159-0.011-0.228 0.717-0.911 1.275-1.902 1.697-2.938 0.592-1.469 0.888-3.029 0.888-4.589s-0.296-3.12-0.888-4.601c-0.592-1.469-1.492-2.847-2.676-4.043-1.173-1.196-2.54-2.095-4.009-2.688-1.469-0.604-3.029-0.9-4.589-0.9-1.549 0-3.109 0.296-4.578 0.9-1.469 0.592-2.847 1.492-4.031 2.688-1.184 1.184-2.084 2.562-2.676 4.031s-0.888 3.041-0.888 4.601 0.296 3.12 0.888 4.589c0.592 1.469 1.492 2.847 2.676 4.043s2.562 2.084 4.031 2.688c1.469 0.604 3.029 0.9 4.589 0.9s3.12-0.296 4.578-0.9c1.036-0.421 2.038-1.002 2.949-1.72 0.046-0.034 0.114-0.034 0.159 0.011 0.273 0.273 1.002 0.957 1.253 1.196 0.034 0.034 0.046 0.091 0.023 0.137-0.205 0.444-0.307 0.945-0.285 1.446 0.023 0.421 0.137 0.854 0.342 1.23 0.102 0.194 0.228 0.376 0.364 0.535 0.171 0.194 0.228 0.251 0.33 0.353 0.74 0.774 1.469 1.549 2.209 2.3l1.116 1.15 0.558 0.569 0.376 0.376c0.034 0.034 0.080 0.080 0.125 0.114 0.080 0.068 0.171 0.137 0.262 0.205 0.74 0.512 1.708 0.683 2.574 0.444 0.433-0.114 0.843-0.319 1.196-0.615 0.046-0.034 0.091-0.068 0.125-0.114l0.114-0.102 0.421-0.421c0.319-0.319 0.558-0.706 0.717-1.127s0.216-0.877 0.182-1.321zM15.795 21.159c-1.15 0.467-2.391 0.706-3.621 0.706s-2.46-0.239-3.621-0.706c-1.15-0.467-2.243-1.173-3.177-2.118-0.945-0.945-1.64-2.027-2.118-3.189-0.467-1.162-0.706-2.403-0.706-3.633 0-1.241 0.239-2.471 0.706-3.633s1.173-2.243 2.118-3.189c0.945-0.957 2.027-1.651 3.189-2.13 1.15-0.467 2.38-0.706 3.621-0.706 1.23 0 2.46 0.239 3.621 0.706 1.15 0.467 2.232 1.173 3.177 2.118v0c0.945 0.945 1.64 2.027 2.118 3.189 0.467 1.162 0.706 2.403 0.706 3.633 0 1.241-0.239 2.471-0.706 3.633s-1.173 2.243-2.118 3.189c-0.957 0.957-2.038 1.663-3.189 2.13zM29.153 28.823l-0.478 0.478c-0.057 0.057-0.137 0.091-0.216 0.114-0.159 0.046-0.342 0.011-0.478-0.080-0.011-0.011-0.034-0.023-0.046-0.034l-0.068-0.068-0.285-0.273-1.708-1.674c-0.763-0.752-1.526-1.48-2.3-2.221-0.239-0.239-0.251-0.239-0.319-0.342-0.057-0.080-0.091-0.182-0.102-0.285-0.034-0.205 0.046-0.433 0.182-0.592 0.125-0.159 0.364-0.399 0.558-0.535 0.273-0.194 0.604-0.125 0.797 0.068s1.697 1.754 2.061 2.141c0.74 0.763 1.48 1.537 2.232 2.289 0.239 0.239 0.239 0.239 0.285 0.33 0.034 0.068 0.057 0.159 0.068 0.239 0.011 0.159-0.057 0.319-0.182 0.444z"></path>
                                </svg>
                            </div>
                            <div @click="cancelSearch" v-if="keyword.length" style="min-width:30px;min-height:28px;display:flex;position: absolute;right: 1px;cursor:pointer;background: var(--background-color);z-index: 3;">
                                <svg class="smallCancelButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 32 32" style="margin: auto;">
                                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="file-main-section">
                <div style="height: fit-content;max-width: 100%;" 
                :key="'file_' + file.id"                                 
                :id="'file_' + file.id"            
                v-for="(file, index) in fileList" 
                class="fletty mblist">
                    <div class="innerContainer">            
                        <div name="fileContainer" @click="previewFile(file, index)" class="listItem-mobile">                   
                            <div style="display:flex;align-items: center;cursor:pointer;max-width:100%;overflow:hidden"> 
                                
                                <div v-if="file.mime_type == 'image'" class="">                                        
                                    <img class="list-image-mobile" loading="lazy" :src="`/shared_thumbnail/${board.id}/${file.id}_${file.user_id}_${file.message_id}.${file.extension}`"/>
                                </div>
                                <div v-else>
                                    <FileIcon :ext="file.extension"/>
                                </div>
                                <div style="height:37px;margin-left: 3px;text-overflow: ellipsis; white-space:nowrap;overflow: hidden;">
                                    
                                    <p :title="file.name" class="cursor-pointer item-name-list-mobile">{{file.name}}</p>
                                    <div style="display:flex;margin:5px 0 0 5px">
                                        <p style="color:gray;font-size:10px;">{{ file.user ? file.user.name : "非アクティブユーザー"}}</p>
                                        <p style="color:gray;font-size:10px;margin-left:5px;">  |  {{fileSizeView(file.size)}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="margin-right: 5px;">
                            <ItemMenu :items="[
                                {title: 'ダウンロード', action: () => downloadFile(file)},
                                {title: 'メッセージに移動', action: () => jumpToMessage(file)}
                            ]"/>
                        </div>                 
                    </div>
                </div> 
            </div>
        </div>
    </div>
</template>

<script setup>
import {filesize} from 'filesize';
import FileIcon from '../../Mixed/FileIcon.vue';
import { computed, inject, onMounted, ref } from 'vue';
import { useFilePreview } from '@/store/filePreview';
import { useMenuStore } from "@/store/menu";
import ItemMenu from '@/components/Global/ItemMenu.vue'
    const menu = useMenuStore()
    const board = inject('openedBoard')
    const emit = defineEmits(['jumpToMessage'])
    const fileListAll = ref([])
    const keyword = ref('')
    const headerHidden = ref(false)
    const fileMenuLayer = ref(0)
    const timeout = ref(0)
    const { notify } = inject('dialog')
    const filePreview = useFilePreview()
    const initialLoader = ref(true)
    onMounted(() => {
        if(board.value){
            getFileList()
        }
    })
        
    const fileList = computed(() => {
        if(keyword.value.length){
            const searchkeyword = keyword.value.toLowerCase().trim();
            return fileListAll.value.filter((file) => {
                const extension = file.extension.toLowerCase();
                const name = file.name.toLowerCase();
                const userName = file.user ? file.user.name.toLowerCase() : '非アクティブユーザー';
                return extension.includes(searchkeyword) || name.includes(searchkeyword) || userName.includes(searchkeyword);
            })
        }else{
            return fileListAll.value
        }
        
    })
       
    const downloadFile = (file) => {
        let src, name;
        const path = file.board_id + '/' + file.id + '_' + file.user_id + '_' + file.message_id + '.' + file.extension        
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
    const jumpToMessage = (file) => {
        emit('jumpToMessage', file);
        closeMenu()
    } 
    const closeMenu = () => {
        menu.setMenu( {name: '', id: null})
    }
    const previewFile = (file, index) => {
        let target_data = file
        if(!target_data.removed_at){
            const files = fileList.value.map(fileData => ({
                ...fileData,
                source_board_id: fileData.board_id,
                file_path: `/cdn/shared_files/${fileData.board_id}/${fileData.id}_${fileData.user_id}_${fileData.message_id}.${fileData.extension}`,
                doc_path: `/shared_files/${fileData.board_id}/${fileData.id}_${fileData.user_id}_${fileData.message_id}.${fileData.extension}`
            }));
            
            const data = {
                active: true,
                files: files,
                target: target_data,
                source: 'message',
                index: index,
                message: {record_id: file.board_id},
            }
            filePreview.setFilePreview(data)
        }else{
            notify('このファイルは消去されました') 
        }
    }
    const cancelSearch = () => {
        keyword.value = ''
        searchStart(keyword.value)
    }
    const setKeyWord = () => { 
        if(event.which === 38 || event.which === 40 || event.which === 13){
            event.preventDefault()
            return
        }else{
            keyword.value = event.currentTarget.value
            autoFillDebounce()
        }
    }
    const autoFillDebounce = (val) => {
        if (timeout.value) clearTimeout(timeout.value)
            timeout.value = setTimeout(() => {
            searchStart(keyword.value)
        }, 300)
    }
    const searchStart = (key) => {

    }
    const getFileList = async() => {
        try{
            const response = await axios.post('/get_file_list', {board_id: board.value.id})
            fileListAll.value = response.data
            initialLoader.value = false          
        }catch (error){
            if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。 ' + error.message)  
        }
    }
    const fileSizeView = (bytes) => {
        if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
        else return filesize(bytes, {standard: "jedec", round: 0});
    }
    
</script>
<style lang="scss">
    
    .file-header-section{
        position: absolute;
        width: 100%;
        left:0; 
        top: 0;
        height: 50px;
        display: flex;
        align-items: center;

    }
    .file-header__inner{
        width: -webkit-fill-available;
        padding:0 10px;
        width: -moz-available;
    }
    .file-main-section{
        height: calc(100% - 50px);
        overflow: auto;
        position: absolute;
        width: 100%;
        top: 50px;
    }
    .mobileFletty{
        outline: none;
        position:relative;
        height: 100%;
        transition: height 0.2s;
    }
    @media screen and (max-width: 959px) {
        .mobileFletty{
            height: calc(100% - 40px);
            padding: 0 10px;
        }
    }
</style>
