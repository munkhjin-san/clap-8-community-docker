<template>
    <div class="file-area-content" style="overflow: hidden;max-width: 100%;">
        <div class="file-wrap" v-for="(file, index) in items" style="max-width: 100%;">   
            <div class="file-area-container" @click="previewFile(items, index)">
                <div class="flex-centered">             
                    <div style="max-width:65px;height:40px;display: flex;">                   
                        <img
                            style="max-width:100%;margin:auto;max-height:100%;" 
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile" 
                            v-lazy="{src: `/cdn/${path}/thumbnail/${file.id}_${file.user_id}_${file.path}_thumbnail.webp`}"
                           
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image' && !file.removed_at" style="position:relative;">
                        <FileIcon :ext="file.extension"/>
                    </div>
                    <div style="line-height: 1.5;max-width: calc(100% - 35px);overflow: hidden;">
                        <p style="font-size: 12px;" :title="file.name" class="shared-file-name">{{fileNameFilter(file.name, file.extension)}}</p>
                        <div style="display:flex;">
                            <p style="font-size: 10px !important;" class="shared-file-name">{{fileSizeView(file.size )}}</p>
                        </div>                            
                    </div>
                </div>   
            </div>                                             
        </div>
    </div>
</template>
    
<script setup lang="ts">
import FileIcon from '../Board/Mixed/FileIcon.vue';
import {filesize} from 'filesize';
import  Swiper  from 'swiper';
import 'swiper/css'
import { onMounted } from 'vue';
import { useFilePreview } from '@/store/filePreview';
const props = defineProps([
    'items',
    'path',
])
const filePreview = useFilePreview()
onMounted(() => {
    new Swiper('.swiper', {
        slidesPerView: 5,
        spaceBetween: 20
    })
})

const fileSizeView = (bytes: number) => {
    if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
    else return filesize(bytes, {standard: "jedec", round: 0});
}            
const previewFile = (file: any, index: number | string) => {
    let file_list = props.items
    const files = file_list.map((fileData: any) => ({
        ...fileData,
        file_path: `/cdn/${props.path}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
        doc_path: `/${props.path}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`
    }));
    let target_data = file
    
    
        const data = {
            active: true,
            files,
            target: target_data,
            source: 'notice',
            index: index,
            message: null,
        }
        filePreview.setFilePreview(data)
    
}
const fileNameFilter = (name: string, ext: string) => {
    var str_lenght = name.length;
    if (str_lenght > 20) {
        var sliced = name.slice(0, 20) + " ..." + ext;
        return sliced;
    }
    return name;

}

</script>
