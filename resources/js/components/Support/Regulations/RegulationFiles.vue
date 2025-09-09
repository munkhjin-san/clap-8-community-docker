<template>
    <div class="file-area-content" style="overflow: hidden;max-width: 100%;">
        <div class="file-wrap" v-for="(file, index) in files" style="max-width: 100%;">   
            <div class="file-area-container">
                <div class="flex-centered" @click="previewFile(files, index)">             
                    <div style="max-width:65px;height:40px;display: flex;">                   
                        <img
                            style="max-width:100%;margin:auto;max-height:100%;" 
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile" 
                            v-lazy="{src: `/cdn/regulation_files/${file.path}.${file.extension}`}"                           
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image'" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div style="line-height: 1.5;max-width: calc(100% - 35px);overflow: hidden;">
                        <p style="font-size: 12px;margin-right: 0" :title="file.name" class="shared-file-name">{{fileNameFilter(file)}}</p>
                        <div style="display:flex;">
                            <p style="font-size: 10px !important;" class="shared-file-name">{{fileSizeView(file.size )}}</p>
                        </div>  
                                                
                    </div>
                </div>   
                <div @click.stop>
                    <label class="flex items-center gap-2 text-[13px] mt-[5px] ml-[5px] cursor-pointer select-none" v-if="mode == 'edit'">
                        <input type="checkbox" :checked="file.chat_supported" @change="changeSupport($event, file)"/>
                        <span>AIチャット対象</span>
                    </label>
                </div> 
            </div>  
            <div @click="emit('remove', file.id)" v-if="mode == 'edit'" class="absolute top-0 right-[5px]">
                <CloseIcon />
            </div>   
                                                    
        </div>
    </div>
</template>
<script setup lang="ts">
import {filesize} from "filesize";
import { useFilePreview } from "@/store/filePreview";
import { useSharingDataStore } from '@/store/sharingData'
import FileIcon from "@/components/Board/Mixed/FileIcon.vue";
import { RegulationFiles } from "@/interface/regulationInterface";
import CloseIcon from "@/components/Form/CloseIcon.vue";
    const sharingData = useSharingDataStore()
    const props = defineProps<{
        files: RegulationFiles[]
        mode: 'view' | 'edit'
    }>()
    const emit = defineEmits<{
        remove: [id: number]
        update: [id: number, field: string, value: any]
    }>()
    const filePreview = useFilePreview()
    const previewFile = (file, index) => {
        if(sharingData.active) return
        let file_list = props.files
        const files = file_list.map(fileData => ({
            ...fileData,
            file_path: `/cdn/regulation_files/${fileData.path}.${fileData.extension}`,
            doc_path: `/regulation_files/${fileData.path}.${fileData.extension}`
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
    const changeSupport = (event:Event, file:RegulationFiles) => {
        const target = event.target as HTMLInputElement
        const checked = target.checked
        emit('update', file.id, 'chat_supported', checked)

    }    
</script>
