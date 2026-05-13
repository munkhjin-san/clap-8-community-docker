<template>
    <div class="file-area-content mt-3 max-w-full overflow-hidden">
        <div class="file-wrap max-w-full" v-for="(file, index) in list" :key="file.id">
            <div class="file-area-container" @click="previewFile(file, index)">
                <div class="flex-centered">
                    <div class="flex h-10 max-w-[65px]">
                        <img
                            v-if="file.mime_type == 'image'"
                            class="list-image-mobile m-auto max-h-full max-w-full"
                            v-lazy="{src: `/cdn/system_update_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`}"
                        />
                    </div>
                    <div v-if="file.mime_type !== 'image' && !file.removed_at" class="relative">
                        <FileIcon :ext="file.extension" />
                    </div>
                    <div class="max-w-[calc(100%-35px)] overflow-hidden leading-normal">
                        <p :title="file.name" class="shared-file-name mr-0 text-[12px]">{{ file.name }}</p>
                        <div class="flex">
                            <p class="shared-file-name !text-[10px]">{{ fileSizeView(file.size) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { filesize } from 'filesize';
import FileIcon from '@/components/Board/Mixed/FileIcon.vue';
import { useFilePreview } from '@/store/filePreview';
import { useSharingDataStore } from '@/store/sharingData';
import { CommonFile } from '@/interface/globalInterface';

const props = defineProps<{
    list: CommonFile[];
}>();

const sharingData = useSharingDataStore();
const filePreview = useFilePreview();

const previewFile = (file: CommonFile, index: number) => {
    if (sharingData.active) return;

    const files = props.list.map((fileData: CommonFile) => ({
        ...fileData,
        file_path: `/cdn/system_update_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
        doc_path: `/system_update_files/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
    }));

    filePreview.setFilePreview({
        active: true,
        files,
        target: file,
        source: 'system_updates',
        index,
        message: null,
    });
};

const fileSizeView = (bytes: number) => {
    if (bytes > 1000000) return filesize(bytes, { standard: 'jedec', round: 1 });
    return filesize(bytes, { standard: 'jedec', round: 0 });
};
</script>
