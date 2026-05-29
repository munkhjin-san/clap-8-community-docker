<template>
    <div class="recordFile">                                                
        <div class="recordFile-inner">                                        
            <div :class="`swiper swiper-${uniqueId}`" style="border:none;">
                <div class="swiper-wrapper">
                    <div class="swiper-slide" v-for="(image, index) in images" :key="index">
                        <div class="swiper-slide-square" @click="previewImage(image, index)">
                            <img class="p-image cursor-pointer" :src="`/cdn/${path}/thumbnail/${image.id}_${image.user_id}_${image.path}_thumbnail.webp`">
                        </div>
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
import { ref } from 'vue';
import { useFilePreview } from '@/store/filePreview';
import { CommonFile } from '@/interface/globalInterface';
    // const props = defineProps(['items', 'path'])
    const props = withDefaults(defineProps<{
        items: CommonFile[]
        path?: string
        slidesCount?: number
    }>(), {
        path: 'post_files',
        slidesCount: 10
    });
    const path = ref(props.path ?? 'post_files')
    const filePreview = useFilePreview()
    const uniqueId = computed(() => {
        return Math.random().toString(36).substring(5)
    })
    onMounted(() => {
        new Swiper(`.swiper-${uniqueId.value}`, {
            slidesPerView: 4,
            spaceBetween: 6,
            breakpoints: {
                640: { slidesPerView: 5, spaceBetween: 8 },
                1024: { slidesPerView: props.slidesCount, spaceBetween: 20 },
            }
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
            file_path: `/cdn/${path.value}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
            doc_path: `/${path.value}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
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
            file_path: `/cdn/${path.value}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
            thumbnail_path: `/cdn/${path.value}/${fileData.id}_${fileData.user_id}_${fileData.path}_thumbnail.webp`
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
<style scoped>
.swiper-slide-square {
    position: relative;
    width: 100%;
    padding-bottom: 100%;
    overflow: hidden;
}
.swiper-slide-square .p-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
</style>
    