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
                            v-lazy="{src: `/cdn/asset_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`}"                           
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image' && !file.removed_at" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div style="line-height: 1.5;max-width: calc(100% - 35px);overflow: hidden;">
                        <p style="font-size: 12px;margin-right: 0" :title="file.name" class="shared-file-name">{{fileNameFilter(file)}}</p>
                        <div style="display:flex;">
                            <p style="font-size: 10px !important;" class="shared-file-name">{{fileSizeView(file.size )}}</p>
                        </div>                            
                    </div>
                </div>   
            </div>                                             
        </div>
    </div>
</template>
<script setup>
import {filesize} from "filesize";
import FileIcon from "../Board/Mixed/FileIcon.vue";
import { useFilePreview } from "@/store/filePreview";
import { useSharingDataStore } from '@/store/sharingData'
    const sharingData = useSharingDataStore()
    const props = defineProps(['list'])
    const filePreview = useFilePreview()
    const previewFile = (file, index) => {
        if(sharingData.active) return
        let file_list = props.list
        const files = file_list.map(fileData => ({
            ...fileData,
            file_path: `/cdn/asset_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
            doc_path: `/asset_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`
        }));
        let target_data = file       
        
        const data = {
            active: true,
            files,
            source: 'calendar',
            index: index,
            message: null,
        }
        filePreview.setFilePreview(data)
        
    }
    const fileNameFilter = (file) => {
        return file.name;
    }
    const fileSizeView = (bytes) => {
        if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
        else return filesize(bytes, {standard: "jedec", round: 0});
    }          
</script>
