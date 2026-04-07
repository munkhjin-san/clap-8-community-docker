<template>
    <div class="file-area-content">
        <div class="file-wrap hasMessage" v-for="(file, index) in props.list">   
            <div class="file-area-container" @click="previewFile(file, index)">
                <div class="flex-centered">             
                    <div style="max-width:65px;height:40px;display: flex;">                   
                        <img
                            style="max-width:100%;margin:auto;max-height:100%;" 
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile" 
                            :src="`/cdn/contact_comment_files/${file.id}_${file.user_id}.${file.extension}`" 
                            loading="lazy"
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image'" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div style="line-height: 1.5;overflow: hidden;;margin-left:5px;">
                        <p :title="file.name ? file.name : ''" class="shared-file-name">{{fileNameFilter(file)}}</p>                                               
                        <p style="font-size: 10px !important;" class="shared-file-name">{{fileSizeView(file.size )}}</p>                     
                    </div>
                    <ItemMenu style="align-self: flex-start;" :items="fileMenuItems(file)" fit="boardListInner"/>
                </div>   
            </div>                                             
        </div>
    </div>
</template>

<script setup lang="ts">
import {filesize} from "filesize";
import FileIcon from "@/components/Board/Mixed/FileIcon.vue";
import { ref } from "vue";
import { useFilePreview } from "@/store/filePreview";
import { useMenuStore } from "@/store/menu";
import { useSharingDataStore } from '@/store/sharingData'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import { MenuList, MessageFile } from "@/interface/globalInterface";
    const sharingData = useSharingDataStore()    
    const menu = useMenuStore()
    const props = defineProps<{
        list: MessageFile[]
    }>()
    const filePreview = useFilePreview()
    const file_index = ref(0)

    const fileMenuItems = (file:MessageFile) => {
        const list:MenuList[] = []; 
        function addItem(title: string, action: () => void) {
            list.push({ title, action });
        }
        addItem('ダウンロード', () => downloadFile(file))        
        return list
    }
    const downloadFile = (file:MessageFile) => {
        closeMenu()
        let src:string, name:string;
        const path = `${file.id}_${file.user_id}.${file.extension}`
        name = file.name
        src = `/cdn/project_goal_report_files/${path}`;
        const link = document.createElement('a');
        link.href = src;
        link.download = '';
        link.setAttribute('download', name);
        document.body.appendChild(link);            
        link.click();  
        document.body.removeChild(link); 
    }
    const closeMenu = () => {
        menu.setMenu( {id: null, name: ''})
    }

    const previewFile = (file:MessageFile, index: number) => {
        if(sharingData.active) return
        let file_list = [file]
        const files = file_list.map(fileData => ({
            ...fileData,
            file_path: `/cdn/project_goal_report_files/${fileData.id}_${fileData.user_id}.${fileData.extension}`,
            doc_path:  `/cdn/project_goal_report_files/${fileData.id}_${fileData.user_id}.${fileData.extension}`
        }));

        let target_data = file
        const data = {
            active: true,
            files,
            target: target_data,
            source: 'message',
            index: file_index.value,
        }
        filePreview.setFilePreview(data)
    }
    const fileNameFilter = (file:MessageFile) => {
        return file.name;
    }
    const fileSizeView = (bytes:number) => {
        if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
        else return filesize(bytes, {standard: "jedec", round: 0});
    }           
    
</script>
