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
                        <div :title="file.name" class="shared-file-name flex items-center gap-1">
                            
                        <div>{{fileNameFilter(file)}}</div>
                        </div>
                        <div class="flex items-center">
                            <div
                                v-if="file.chat_supported"
                                :class="['sync-badge ml-1', syncStatusClass(file.ai_sync_status)]"
                                :title="file.ai_sync_status === 'error' ? (file.ai_sync_error || '同期エラー') : syncStatusLabel(file.ai_sync_status)"
                            >
                                <svg fill="currentColor" version="1.1" width="15" height="15" viewBox="0 0 34.02 33.29" xmlns="http://www.w3.org/2000/svg"><path d="M27.57.33c.22.46.33.93.48,1.38.15.45.31.9.5,1.33.72,1.64,1.59,3.11,3.01,4.16.34.25.7.49,1.09.69.3.17.63.25.91.46.11.08.24.22.33.36.25.36.11.86-.29,1.04-.58.27-1.1.04-1.65-.11-1.49-.44-2.85-1.39-3.75-2.67-1.08-1.46-1.5-3.2-1.7-4.94-.06-.51-.12-1.02-.04-1.55.08-.57.87-.68,1.11-.16h0Z"></path><path d="M26.47,17.91c-.08-.52-.01-1.03.04-1.55.19-1.73.61-3.48,1.7-4.94.98-1.4,2.5-2.38,4.15-2.78.42-.12.81-.21,1.25,0,.42.19.54.75.24,1.11-.29.44-.77.52-1.19.76-1.14.6-2.09,1.44-2.83,2.51-.73,1.07-1.36,2.38-1.77,3.67-.15.46-.26.93-.48,1.39-.25.51-1.02.39-1.1-.17h0Z"></path><path d="M27.57.5c.08.52.01,1.03-.04,1.55-.19,1.73-.61,3.48-1.7,4.94-.98,1.4-2.5,2.38-4.15,2.79-.42.12-.81.21-1.25,0-.42-.19-.54-.75-.24-1.11.29-.44.77-.52,1.19-.76,1.14-.6,2.09-1.44,2.83-2.51.73-1.07,1.36-2.38,1.77-3.67.15-.46.26-.93.48-1.39.25-.51,1.02-.39,1.1.17h0Z"></path><path d="M26.47,18.08c-.22-.46-.33-.93-.48-1.38-.15-.45-.31-.9-.5-1.33-.72-1.64-1.59-3.11-3.01-4.16-.34-.25-.7-.49-1.09-.69-.3-.17-.63-.25-.91-.46-.11-.08-.24-.22-.33-.36-.25-.36-.11-.86.29-1.04.58-.27,1.1-.04,1.65.11,1.49.44,2.85,1.39,3.75,2.67,1.08,1.46,1.5,3.2,1.7,4.94.06.51.12,1.02.04,1.55-.08.57-.87.68-1.11.16h0Z"></path><path d="M11.41,5.24c.29.72.45,1.46.67,2.17,1.03,3.36,2.6,6.71,5.47,8.81.61.45,1.3.86,2,1.2.62.28,1.28.46,1.74,1.01.29.33.26.84-.08,1.13-.44.33-1.07.3-1.55.21-.39-.08-.82-.21-1.21-.34-2.45-.77-4.6-2.46-5.97-4.65-1.38-2.14-1.95-4.53-2.28-6.98-.1-.79-.2-1.57-.13-2.37.07-.73,1.07-.87,1.34-.2h0Z"></path><path d="M10.07,32.66c-.07-.8.03-1.58.13-2.37.1-.79.24-1.58.42-2.37.96-4.5,3.86-8.21,8.43-9.43.62-.18,1.22-.33,1.88-.11.42.14.64.6.5,1.01-.11.31-.42.56-.67.72-.46.29-.98.43-1.46.68-3.92,1.88-6.03,5.85-7.24,9.89-.22.72-.38,1.46-.67,2.18-.28.67-1.27.52-1.34-.2h0Z"></path><path d="M11.41,5.43c.07.8-.03,1.58-.13,2.37-.1.79-.24,1.58-.42,2.37-.96,4.5-3.86,8.21-8.43,9.43-.62.18-1.22.33-1.88.11-.42-.14-.64-.6-.5-1.01.11-.31.42-.56.67-.72.46-.29.98-.43,1.46-.68,3.92-1.88,6.03-5.85,7.24-9.89.22-.72.38-1.46.67-2.18.28-.67,1.27-.52,1.34.2h0Z"></path><path d="M10.07,32.86c-.29-.72-.45-1.46-.67-2.17-1.03-3.36-2.6-6.71-5.47-8.81-.61-.45-1.3-.86-2-1.2-.62-.28-1.28-.46-1.74-1.01-.29-.33-.26-.84.08-1.13.44-.33,1.07-.3,1.55-.21.39.08.82.21,1.21.34,2.45.77,4.6,2.46,5.97,4.65,1.38,2.14,1.95,4.53,2.28,6.97.1.79.2,1.57.13,2.37-.07.73-1.07.87-1.34.2h0Z"></path></svg>
                            </div> 
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
import { CommonFile } from "@/interface/globalInterface";
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
    const previewFile = (file: RegulationFiles[], index: number) => {
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
    const fileNameFilter = (file: RegulationFiles) => {
        return file.name;
    }
    const fileSizeView = (bytes: number) => {
        if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
        else return filesize(bytes, {standard: "jedec", round: 0});
    }      
    const changeSupport = (event:Event, file:RegulationFiles) => {
        const target = event.target as HTMLInputElement
        const checked = target.checked
        emit('update', file.id, 'chat_supported', checked)

    }    
    const syncStatusLabel = (status?: RegulationFiles['ai_sync_status']) => {
        switch (status) {
            case 'syncing':
                return '同期中'
            case 'synced':
                return '同期済み'
            case 'error':
                return 'エラー'
            case 'not_synced':
            default:
                return '未同期'
        }
    }
    const syncStatusClass = (status?: RegulationFiles['ai_sync_status']) => {
        switch (status) {
            case 'syncing':
                return 'sync-badge--syncing'
            case 'synced':
                return 'sync-badge--synced'
            case 'error':
                return 'sync-badge--error'
            case 'not_synced':
            default:
                return 'sync-badge--pending'
        }
    }
</script>
<style scoped>
.sync-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 10px;
    line-height: 1.4;
}
.sync-badge--pending {
    background: var(--bg3);
    color: var(--secondary-color);
}
.sync-badge--syncing {
    background: rgba(59, 130, 246, 0.14);
    color: #2563eb;
}
.sync-badge--synced {
    background: rgba(34, 197, 94, 0.14);
    color: #16a34a;
}
.sync-badge--error {
    background: rgba(239, 68, 68, 0.14);
    color: #dc2626;
}
</style>
