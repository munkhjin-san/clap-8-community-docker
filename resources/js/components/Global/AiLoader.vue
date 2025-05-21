<template>
    <Teleport to="body">
        <div class="fixed w-full h-full bg-[#00000061] flex items-center justify-center z-[65] top-0 left-0">
            <div class="bg-[#00000085] flex flex-col justify-center items-center gap-[15px] p-[15px]">
                <!-- <div id="loaderMini" style="width: fit-content;">
                    <div class="spinner-micro" style="border-color: transparent #fff #fff"></div>
                </div> -->
                <AiIcon size="30" fill="#fff" class="animate-pulse" />
                <p class="text-white leading-normal text-center" v-html="message"></p>
            </div>
        </div>
    </Teleport>
</template>
<script setup lang="ts">
import AiIcon from '../Icons/AiIcon.vue';

const props = defineProps<{
    message: string;
}>();
</script>