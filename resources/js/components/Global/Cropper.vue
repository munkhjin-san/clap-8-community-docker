<template>
    <div ref="cropperContainer" class="flex h-full w-full relative">
        <div class="filedrop-area" v-if="!tempImage">
            <p v-if="placeHolder">{{ placeHolder }}</p>
            <div class="w-full flex">
                <div v-if="mode !== 'scan'" class="m-auto">
                    <CommandButton :buttons="[{ title: 'アップロード', action: () => { el?.click() } }]" />
                </div>

                <div v-if="mode == 'scan'"
                    class="bg-[var(--bg3)] border-[var(--bg2)] w-full min-h-[150px] flex items-center justify-center cursor-pointer">
                    <div class="text-[gray] text-[14px]">
                        
                        <p class="leading-normal px-[10px] mt-[10px] text-[12px] text-left">名称の写真をアップロードすることで、名称情報や企業情報を自動で取得できます。</p>
                            <div class="flex items-center gap-[25px] justify-center mt-[20px]">
                                <label>
                                    <input type="file" class="hidden" @change="preUpload" accept="image/*" />
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="28" viewBox="0 0 27 32" fill="gray">
                                        <path d="M25.954 7.013c-0.479-0.575-4.378-4.56-5.978-5.816-0.623-0.489-1.284-0.853-2.127-0.949-1.178-0.125-2.97-0.182-4.091-0.22-1.36-0.029-2.472-0.029-3.832-0.029-1.36 0.010-3.008 0.077-4.474 0.172-1.36 0.077-2.328 0.134-2.845 0.22-0.69 0.105-1.188 0.489-1.265 1.303-0.077 0.805-0.172 4.905-0.172 7.454 0.010 2.558 0.115 5.835 0.201 6.822 0.096 0.987 0.556 1.447 1.083 1.504 0.527 0.067 0.843-0.537 0.853-1.159 0.019-0.623 0.019-1.226 0.019-1.734s-0.048-2.913-0.019-5.432c0.029-2.098 0.086-4.206 0.192-6.304 0.010-0.134 0.115-0.24 0.249-0.24 0.92-0.029 1.849-0.048 2.778-0.058 1.341-0.019 2.683-0.019 4.024-0.010s2.683 0.029 4.024 0.058c0.987 0.019 1.983 0.048 2.96 0.086 0.153 0.010 0.268 0.134 0.268 0.287-0.010 0.901-0.019 3.612-0.019 3.612 0 0.546 0.010 1.083 0.019 1.629v0.010c0.010 0.546 0.45 0.987 0.996 0.987l1.705 0.019h1.705c0.441 0 1.428-0.019 1.926-0.029 0.153 0 0.287 0.125 0.297 0.278 0.048 1.399 0.067 2.807 0.077 4.216 0.010 1.878 0 3.756-0.029 5.634s-0.077 3.756-0.153 5.624c-0.067 1.514-0.144 3.037-0.268 4.532-0.019 0.201-0.182 0.355-0.383 0.364-1.418 0.038-2.778 0.067-4.302 0.077-1.648 0.010-6.266 0.010-8.163 0-1.964-0.010-5.365-0.029-7.042-0.086-0.125 0-0.24-0.153-0.259-0.278-0.058-0.374-0.105-0.834-0.163-1.389-0.067-0.623-0.469-1.092-1.035-1.025-0.45 0.048-0.824 0.45-0.891 1.198-0.067 0.738-0.019 1.619 0.067 2.213s0.441 1.016 1.198 1.14c1.006 0.163 5.72 0.249 8.057 0.268 2.347 0.019 6.275-0.019 8.259-0.019 1.974-0.010 3.286-0.019 4.857-0.182 1.121-0.115 1.408-0.747 1.552-1.715 0.24-1.667 0.345-3.325 0.469-4.982 0.134-1.887 0.24-3.775 0.326-5.672 0.086-1.887 0.144-3.784 0.192-5.672 0.029-1.428 0.038-3.21 0.019-4.235-0.010-0.948-0.287-1.782-0.862-2.472zM19.832 7.023c-0.019-0.537-0.077-2.060-0.096-2.692 0-0.096 0.105-0.144 0.182-0.086 0.537 0.489 2.491 2.271 3.152 2.874 0.077 0.067 0.029 0.192-0.077 0.182-0.719-0.029-2.434-0.086-2.98-0.105-0.096 0.010-0.182-0.077-0.182-0.172z"></path><path d="M18.405 25.61l2.050-6.189c0.029-0.086 0.048-0.182 0.048-0.268 0-0.45-0.383-0.843-0.881-0.843h-18.74c-0.24 0-0.46 0.096-0.623 0.249s-0.259 0.364-0.259 0.604v6.189c0 0.23 0.096 0.441 0.259 0.594s0.383 0.249 0.623 0.249h16.69c0.374-0.010 0.709-0.24 0.834-0.584zM22.41 11.89c0.019-0.383-0.278-0.719-0.671-0.738-1.284-0.067-2.568-0.096-3.842-0.115-0.642-0.010-1.284-0.029-1.926-0.029l-1.926-0.010-1.926 0.010-1.926 0.029c-1.284 0.019-2.568 0.038-3.842 0.086-0.374 0.019-0.69 0.316-0.699 0.699-0.010 0.402 0.297 0.738 0.699 0.757 1.284 0.048 2.568 0.067 3.842 0.086l1.926 0.019 1.926 0.010 1.926-0.010c0.642 0 1.284-0.019 1.926-0.029 1.284-0.019 2.568-0.048 3.842-0.115 0.364-0.010 0.651-0.287 0.671-0.652zM15.875 14.63c-0.527-0.010-1.054-0.029-1.581-0.029l-1.59-0.010-1.581 0.010-1.59 0.029c-1.054 0.019-2.117 0.038-3.171 0.086-0.374 0.019-0.68 0.316-0.69 0.699-0.019 0.402 0.297 0.738 0.69 0.757 1.054 0.048 2.117 0.067 3.171 0.086l1.59 0.029 1.581 0.010 1.59-0.010c0.527 0 1.054-0.019 1.581-0.029 1.054-0.019 2.117-0.048 3.171-0.115 0.345-0.019 0.632-0.297 0.661-0.661 0.019-0.383-0.268-0.719-0.661-0.738-1.054-0.057-2.117-0.086-3.171-0.115z"></path>
                                    </svg>
                                </label>
                                <p> | </p>
                                <label>
                                    <input type="file" class="hidden" @change="preUpload" accept="image/*" capture="environment"/>
                                    <svg version="1.1" fill="gray" xmlns="http://www.w3.org/2000/svg" width="30" height="32"
                                        viewBox="0 0 40 32">
                                        <path d="M39.981 14.258c-0.008-1.304-0.034-2.608-0.072-3.91l-0.013-0.488-0.008-0.147c-0.002-0.053-0.005-0.104-0.010-0.16-0.008-0.107-0.021-0.216-0.034-0.326-0.062-0.438-0.181-0.904-0.413-1.365-0.227-0.458-0.576-0.901-1.005-1.234s-0.914-0.554-1.379-0.678c-0.234-0.064-0.462-0.106-0.686-0.133-0.222-0.027-0.45-0.038-0.642-0.040l-5.653-0.126-0.434-2.070-0.166-0.8-0.085-0.4-0.006-0.029-0.008-0.034-0.029-0.104c-0.019-0.067-0.038-0.138-0.062-0.197-0.043-0.125-0.096-0.246-0.155-0.363-0.238-0.472-0.606-0.874-1.048-1.162-0.442-0.286-0.966-0.458-1.494-0.482h-13.248c-0.528 0.024-1.050 0.195-1.494 0.482-0.442 0.286-0.808 0.69-1.048 1.16-0.059 0.118-0.112 0.24-0.155 0.365-0.022 0.059-0.043 0.13-0.062 0.197l-0.027 0.104-0.008 0.034-0.006 0.026-0.083 0.4-0.166 0.802-0.435 2.070-5.517 0.12c-0.019 0-0.061 0.005-0.091 0.006-0.062 0.005-0.133 0.010-0.19 0.016l-0.17 0.021c-0.117 0.016-0.23 0.034-0.347 0.058-0.462 0.094-0.941 0.251-1.398 0.509-0.456 0.258-0.888 0.622-1.218 1.067-0.333 0.442-0.557 0.957-0.67 1.466-0.058 0.25-0.090 0.515-0.102 0.746l-0.026 0.522c-0.018 0.347-0.032 0.698-0.042 1.043-0.050 1.389-0.070 2.774-0.074 4.157-0.002 2.766 0.074 5.523 0.206 8.282 0.067 1.379 0.154 2.757 0.256 4.138l0.019 0.256 0.029 0.302c0.024 0.21 0.058 0.429 0.106 0.656 0.053 0.229 0.122 0.472 0.23 0.728 0.106 0.254 0.256 0.525 0.462 0.781 0.205 0.256 0.474 0.485 0.75 0.65 0.275 0.168 0.554 0.278 0.805 0.352 0.506 0.146 0.926 0.182 1.309 0.216 0.384 0.032 0.722 0.042 1.075 0.059 0.349 0.014 0.701 0.035 1.046 0.045 0.694 0.024 1.389 0.048 2.080 0.066 1.384 0.037 2.766 0.066 4.149 0.088s2.763 0.042 4.142 0.045c1.381 0.003 2.762 0.013 4.142-0.002 0 0 5.853 0.010 7.995-0.043 2.664-0.066 6.666 0.003 7.994-0.221s2.035-0.939 2.277-1.926c0.24-0.99 0.35-2.55 0.466-3.853 0.107-1.301 0.205-2.605 0.275-3.909 0.126-2.608 0.19-5.219 0.166-7.829zM29.059 29.288c-2.749-0.013-5.501-0.016-8.248-0.050l-8.248-0.080c-1.373-0.018-2.747-0.035-4.117-0.067-0.688-0.013-3.501-0.056-4.056-0.144-0.557-0.088-1.011-0.781-1.117-1.357-0.104-0.573-0.174-2.706-0.235-4.062-0.122-2.714-0.186-5.432-0.173-8.142 0.006-1.357 0.032-2.71 0.085-4.056 0.010-0.339 0.022-0.675 0.042-1.011l0.027-0.504c0.006-0.109 0.018-0.186 0.035-0.269 0.038-0.16 0.099-0.285 0.178-0.39 0.080-0.106 0.182-0.197 0.325-0.277 0.142-0.082 0.325-0.144 0.536-0.186 0.053-0.011 0.109-0.019 0.165-0.027l0.086-0.010 0.067-0.006c0.013 0 0.014-0.002 0.037-0.002l0.064-0.002 0.13-0.002 0.256-0.006 2.056-0.035 4.114-0.077c0.666-0.011 1.259-0.475 1.402-1.15v-0.005c0 0 0.563-2.661 0.813-3.838 0.069-0.322 0.354-0.552 0.685-0.552 2.155 0 9.829 0.003 11.984 0.005 0.331 0 0.618 0.23 0.685 0.554l0.811 3.832 0.002 0.006c0.136 0.646 0.706 1.138 1.398 1.149l6.845 0.117c0.134 0 0.232 0.005 0.333 0.016s0.19 0.027 0.27 0.050c0.163 0.043 0.28 0.101 0.365 0.166s0.15 0.141 0.213 0.261c0.062 0.118 0.114 0.286 0.144 0.488 0.008 0.051 0.014 0.101 0.018 0.155 0.003 0.027 0.005 0.056 0.006 0.083l0.005 0.096 0.018 0.482c0.043 1.283 0.077 2.565 0.093 3.848 0.014 1.282 0.019 2.563 0.011 3.846-0.011 1.282-0.042 2.56-0.086 3.838-0.066 2.117-0.186 4.234-0.342 6.349-0.037 0.515-0.467 0.915-0.987 0.928-0.853 0.018-1.71 0.029-2.57 0.030-1.374 0.008-2.747 0.011-4.122 0.008z"></path>
                                        <path d="M26.002 12.024c-0.773-0.794-1.704-1.44-2.734-1.878-1.029-0.438-2.149-0.661-3.262-0.662-1.114 0.005-2.234 0.229-3.259 0.67-1.026 0.443-1.952 1.093-2.72 1.886-1.536 1.594-2.397 3.798-2.36 5.982l0.045 0.813c0.034 0.272 0.082 0.541 0.123 0.81 0.056 0.266 0.13 0.531 0.197 0.794 0.080 0.262 0.179 0.515 0.272 0.773 0.414 1.011 1.037 1.934 1.819 2.693s1.712 1.357 2.722 1.749c1.008 0.395 2.094 0.576 3.162 0.554v-0.048c1.062 0.034 2.144-0.144 3.149-0.534 1.011-0.384 1.936-0.984 2.72-1.734 0.782-0.75 1.416-1.666 1.84-2.672 0.429-1.008 0.651-2.102 0.662-3.194 0.014-1.094-0.184-2.2-0.592-3.226-0.406-1.027-1.014-1.976-1.782-2.774zM23.93 21.946c-0.499 0.531-1.107 0.962-1.774 1.267-0.669 0.306-1.398 0.485-2.15 0.522v-0.050c-0.746-0.026-1.472-0.2-2.139-0.501-0.664-0.302-1.269-0.73-1.768-1.253-1-1.046-1.629-2.453-1.603-3.909 0.021-0.728 0.178-1.437 0.469-2.091 0.288-0.651 0.714-1.24 1.229-1.723 0.514-0.488 1.122-0.866 1.771-1.117 0.648-0.253 1.341-0.384 2.040-0.382s1.39 0.136 2.037 0.392c0.646 0.254 1.251 0.635 1.76 1.123 0.51 0.485 0.931 1.070 1.219 1.72 0.288 0.648 0.443 1.355 0.462 2.078 0.022 1.442-0.552 2.862-1.552 3.923z"></path>
                                    </svg>
                                </label>
                            </div>
                    </div>
                    
                    
                    
                </div>

            </div>
            <input ref="tempInput" accept="image/*" type="file" name="userIcon" id="userIcon" @change="preUpload"
                style="display: none;">
        </div>
        <div v-else class="cropping-area">
            <div class="absolute right-[10px] top-[10px] z-[10] flex flex-col gap-[15px]">
                <CommandButton :buttons="commands" />
            </div>
            <div v-if="mode == 'scan'" @click="rotate"
                class="absolute left-[10px] top-[10px] w-[40px] h-[40px] rounded-full bg-black flex items-center justify-center z-[10] cursor-pointer">
                <div :class="['w-[10px] h-[18px] border-2 border-solid border-white']"
                    :style="{ transform: `rotate(${aspect.x == 1.75 ? 0 : 90}deg)` }"></div>
            </div>
            <img class="hidden" ref="hiddenImageWrap" :src="tempImage">
        </div>

    </div>
</template>
<script setup lang=ts>
import { onUnmounted, ref, useTemplateRef } from 'vue';
import Cropper from 'cropperjs';
import { CommandButtonInterface, CropperComplete, ImageMeta } from '@/interface/globalInterface'
import CommandButton from './CommandButton.vue';
import 'cropperjs/dist/cropper.css';
import { onMounted } from 'vue';
import { computed } from 'vue';
import { reactive } from 'vue';
const props = defineProps<{
    placeHolder?: string
    mode?: string
}>()

const emit = defineEmits<{
    scan: [],
    crop: []
}>()
const cropperInstance = ref<InstanceType<typeof Cropper> | null>(null)
const tempImage = ref<string>('');
const hiddenImageWrap = ref<HTMLImageElement | null>(null)
const fileMeta = ref<ImageMeta | null>(null)

const cropperContainer = ref<HTMLElement | null>(null)
const original = ref<File | null>(null)
const el = useTemplateRef('tempInput')
const border = ref('50%')
const aspect = reactive({
    x: props.mode == 'scan' ? 1.75 : 1,
    y: 1
})
onMounted(() => {
    if (props.mode == 'scan') {
        border.value = '0px'
    }
})

onUnmounted(() => {
    destroy()
})

const commands = computed(() => {
    const items: CommandButtonInterface[] = []
    items.push(
        { title: 'リセット', action: () => destroy() },
    )
    if (props.mode == 'scan') {
        items.unshift(
            { title: '完了', action: () => emit('crop') }
        )
        items.unshift(
            { title: 'スキャン', action: () => emit('scan') }
        )
    }

    return items
})
const destroy = () => {
    if (cropperInstance.value) {
        cropperInstance.value.destroy()
    }
    tempImage.value = ''
}
const getFileExtension = (fileName) => {
    const lastDotIndex = fileName.lastIndexOf('.');
    if (lastDotIndex === -1) {
        return '';
    }
    return fileName.substring(lastDotIndex + 1).toLowerCase();
}
const preUpload = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target && target.files ? target.files[0] : null
    if (file) {

        const fileExtension = getFileExtension(file.name);
        const reader = new FileReader();

        reader.onload = () => {
            const image = {
                name: file.name,
                url: reader.result,
                mime_type: file.type,
                extension: fileExtension,
                size: file.size
            };
            fileMeta.value = image;
        }
        reader.readAsDataURL(file);

        original.value = file


        tempImage.value = URL.createObjectURL(file);
        setTimeout(() => {

            var width = 300;
            var height = 300;
            const container = cropperContainer.value;
            if (container) {
                width = container.offsetWidth
                if (props.mode !== 'scan' && container.offsetHeight) {
                    height = container.offsetHeight
                }

            }
            if (cropperInstance.value && cropperInstance.value) {
                cropperInstance.value.destroy();
                cropperInstance.value = null;
            }
            if (hiddenImageWrap.value) {
                const options: Cropper.Options = {
                    dragMode: 'move',
                    preview: '.preview',
                    aspectRatio: aspect.x / aspect.y,
                    minContainerWidth: width,
                    minContainerHeight: height,
                    viewMode: 1,
                    responsive: true,
                    autoCrop: true,
                    background: false,
                    guides: false,
                    crop() { },
                }

                cropperInstance.value = new Cropper(hiddenImageWrap.value, options);
            }
        }, 0);
    }
}
const getBlob = (canvas: HTMLCanvasElement) => {
    return new Promise<Blob | null>((resolve) => {
        canvas.toBlob((blob) => {
            resolve(blob);
        });
    });
};
const complete = async (): Promise<CropperComplete> => {
    if (!cropperInstance.value) {
        return { meta: null, blob: null, source: null };
    }
    const meta = JSON.stringify(fileMeta.value);
    const canvas: HTMLCanvasElement = cropperInstance.value.getCroppedCanvas()
    const blob = await getBlob(canvas);
    const source = original.value
    return { meta, blob, source };
}
const rotate = () => {
    if (cropperInstance.value) {
        [aspect.x, aspect.y] = [aspect.y, aspect.x];
        cropperInstance.value.setAspectRatio(aspect.x / aspect.y)
    }

}
defineExpose({ complete, destroy })
</script>
<style>
.cropper-view-box,
.cropper-face {
    border-radius: v-bind('border') !important;
    outline: #fff !important;
}
</style>