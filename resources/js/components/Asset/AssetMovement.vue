<template>
<div :class="['p-3 text-[var(--primary-color)]']">
    <div>
        <div v-if="assetRequest" class="mb-[10px]">
            <p class="mb-4 bg-[var(--bg3)] w-fit px-2 py-1 rounded-lg">{{ isReturnRequest ? '返却申請' : '移動申請' }}</p>
            <div class="flex items-center gap-[10px]">
                <div v-if="assetRequest.from_external_user">{{ assetRequest.from_external_user }}</div>
                <UserPanel v-else-if="assetRequest.send_user" :user="assetRequest.send_user" size="20" with-name disable-instant/>                
                <div>➞</div>
                <div v-if="assetRequest.to_external_user">{{ assetRequest.to_external_user }}</div>
                <UserPanel v-else-if="assetRequest.recieve_user" :user="assetRequest.recieve_user" size="20" with-name disable-instant/>                
            </div>
        </div>
        <div v-if="assetRequest && assetRequest.files && assetRequest.files.length > 0" class="flex flex-col mb-[20px]">
            <div class="mb-[10px]">現状の様子</div>
            <AssetFiles :list="assetRequest.files"/>
        </div>
        <div v-if="auth.isAdmin" class="w-full flex flex-col mt-4">
            <div class="flex gap-2">
                <CommandButton :buttons="[
                    {title: '対応する', action: () => confirmWindow = true}
                ]"/>
            </div>
        </div>
        <Teleport to="body">
            <Transition name="modalFade">
                <AssetMovementConfirm
                    v-if="confirmWindow" 
                    :asset="asset"
                    :assetRequest="assetRequest" 
                    :isReturnRequest="isReturnRequest" 
                    @close="(flag) => {confirmWindow = false; if(flag) emit('reload')}"
                />
            </Transition>
        </Teleport>
    </div>
</div>
</template>
<script setup lang="ts">
import { Asset, AssetRequest } from '@/interface/assetInterface';
import { computed, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import UserPanel from '../Global/UserPanel.vue';
import AssetFiles from './AssetFiles.vue';
import CommandButton from '../Global/CommandButton.vue';
import AssetMovementConfirm from './AssetMovementConfirm.vue';
const props = defineProps<{
    asset: Asset
    assetRequest: AssetRequest
}>()

const emit = defineEmits<{
    reload: []
}>()

const auth = useAuthUserStore()



const confirmWindow = ref(false)
const isReturnRequest = computed(() => {
    return props.assetRequest.steps.some(step => step.value == 7)
})

</script>