<template>
    <div class="recordFile">                                                
        <div class="recordFile-inner">                                        
            <div class="swiper" style="border:none;">
                <div class="swiper-wrapper">
                    <div class="swiper-slide" v-for="(image, index) in images" :key="index">
                        <img @click="previewImage(image, index)" class="cursor-pointer" :src="`/cdn/${path}/thumbnail/${image.id}_${image.user_id}_${image.path}_thumbnail.webp`" style="width: auto;max-width: 100%;max-height: 130px;">
                    </div>  
                </div>                                                          
            </div>        
            <div class="file-area-content" style="gap: 10px;margin: 15px 0 0 0">
                <div @click="previewFile(file, index)" class="file-wrap-rec" v-for="(file, index) in fileList" style="padding: 0;">   
                    <div class="file-area-container" style="flex-direction: row;">                    
                        <div v-if="file.mime_type !== 'image'" style="position:relative;">
                            <FileIcon :ext="file.extension"/>                                                  
                        </div>
                        <div style="text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">
                            <p class="shared-file-name">{{fileNameFilter(file.name, file.extension)}}</p>   
                            <p style="font-size: 10px !important;" class="shared-file-name">{{fileSize(file.size)}}</p>   
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
import { computed, onMounted } from 'vue';
import { useFilePreview } from '@/store/filePreview';
import { CommonFile } from '@/interface/globalInterface';
    // const props = defineProps(['items', 'path'])
    const props = defineProps<{
        items: CommonFile[]
        path: string
    }>()
    const filePreview = useFilePreview()
    onMounted(() => {
        new Swiper('.swiper', {
            slidesPerView: 5,
            spaceBetween: 20
        })
    })
    const images = computed(() => {
        return props.items.filter(ob => ob.mime_type == 'image')
    })
    const fileList = computed(() => {
        return props.items.filter(ob => ob.mime_type !== 'image')
    })
                
    const previewFile = (file: CommonFile, index: number) => {
        const files = fileList.value.map(fileData => ({
            ...fileData,
            file_path: `/cdn/${props.path}/thumbnail/${fileData.id}_${fileData.user_id}_${fileData.path}_thumbnail.webp`,
            doc_path: `/${props.path}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
        }));
        const data = {
            active: true,
            files,
            target: file,
            source: 'post',
            source_board_id: null,
            index: index,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
    const previewImage = (file: CommonFile, index: number) => {
        const files = images.value.map(fileData => ({
            ...fileData,
            file_path: `/cdn/post_entry_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
            thumbnail_path: `/cdn/post_entry_files/${fileData.id}_${fileData.user_id}_${fileData.path}_thumbnail.webp`
        }));
        const data = {
            active: true,
            files,
            target: file,
            source: 'post',
            source_board_id: null,
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
    const fileSize = (bytes: number) => {
        if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
        else return filesize(bytes, {standard: "jedec", round: 0});
    }
</script>
    