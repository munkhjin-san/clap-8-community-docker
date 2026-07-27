<template>
    <section class="border-t border-[var(--bg3)] pt-3">
        <div class="mb-2 flex flex-wrap items-center gap-2">
            <SystemUpdateTypeChip :type="detail.type" />
            <p class="font-semibold leading-normal">{{ detail.title }}</p>
        </div>

        <div>
            <p v-if="detail.content" class="whitespace-break-spaces leading-[1.7]" v-html="urlCheck(detail.content)"></p>
            <SystemUpdateFiles v-if="detail.files?.length" :list="detail.files" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { SystemUpdateDetail } from '@/interface/supportInterface';
import SystemUpdateFiles from './SystemUpdateFiles.vue';
import SystemUpdateTypeChip from './SystemUpdateTypeChip.vue';
import { urlCheck } from '@/utils/tools.js';

defineProps<{
    detail: SystemUpdateDetail;
}>();
</script>
