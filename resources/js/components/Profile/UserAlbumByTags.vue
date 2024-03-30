<template>
    <div id="mRw1" class="md-window">
        <div class="searchMessageArea" style="padding: 10px;">
            <div style="display:flex;height: 40px;min-height: 40px;line-height: 40px;">
                <p style="margin:0 20px;" class="copyareaTitle">#{{tagText}}</p>
                <div @click="emit('closeModal')" style="margin:0 0 0 auto;cursor:pointer;width:40px;height:40px;display:flex">
                    <svg style="margin:auto" class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div> 
            <div id="searchScrollOn" class="post-search-result-window">
                <div :key="item.id" v-for="item in tagAlbums" class="" style="padding: 15px;background: var(--background-color);">
                    <div class="recordBox-inner">        
                        <div class="post-second-wrap" style="gap: 10px;margin: 0;">
                            <div class="post-user-wrap">
                                <div style="display:flex;align-items: center;">
                                    <UserIcon :disableInstant="true" :user="item" imgClass="userNormalIcon" size="30"/>
                                    <router-link class="memberNameLink" :to="`/user/${item.id}`">
                                        <p class="userName">{{ item ? item.name : '' }}</p>
                                    </router-link>
                                </div>                
                            </div>
                        </div> 
                        <div class="recordContents" style="line-height:1.5;margin-top:0;font-size:14px;line-height: 1.8;margin-top:10px;">
                            <div class="recordContents-inner cursor-pointer" v-for="album in item.user_album" :key="album.id" @click="previewImage(album)">
                                <p>{{ album.title }}</p>
                                <img class="gn-image" style="height:100%;object-fit: cover;" v-if="album.mime_type == 'image'" :src="movSrc(album)" loading="lazy"/>
                                <video class="gn-image" v-else-if="isMov(album.mime_type)" controls style="pointer-events: none;max-height: 290px;">
                                    <source v-bind:src="movSrc(album)">
                                </video>
                            </div>                                           
                        </div>
                    </div>    
                </div>    
            </div> 
            
        </div>
    </div>
</template>
<script setup lang="ts">
    import { useFilePreview } from '../../store/filePreview'
    import UserIcon from '../Board/Mixed/UserIcon.vue';
    const emit = defineEmits(['closeModal'])
    const filePreview = useFilePreview()
    interface Props {
        tagText: string | null
        tagAlbums: any
    }
    const props = defineProps<Props>()
    const isMov = (type : string) => {
        return type.includes('video') 
    }
    const movSrc = (mov : any) => {
        return mov.path.includes('intro') 
            ? `/cdn/user_album/${mov.user_id}/${mov.path}` 
            : `/cdn/user_album/${mov.user_id}/${mov.id}_${mov.user_id}_${mov.path}.${mov.extension}`
    }
    const previewImage = (file : any) => {
        let target_data = file
        const old_mov = movSrc(file)
        target_data['file_path'] = old_mov
        const data = {
            active: true,
            files: [target_data],
            source: 'user',
            index: 0,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
</script>
<style scoped lang="scss">
    .post-search-result-window{
        height: -webkit-fill-available;
        overflow: hidden auto;
        font-size:13px;
        padding:15px;
        background:var(--bg2);
        margin:0px 20px 15px 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .recordContents{
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .recordContents-inner{
        position: relative;
        max-height: 170px;
        overflow: hidden;
        display: flex;
        max-width: 260px;
        flex-direction: column;
    }
    @media screen and (max-width: 959px) {
        .recordContents-inner{
            max-height: 145px;
            max-width: 290px;
        }
    }
</style>