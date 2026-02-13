<template>
<div :class="['p-[20px]']">
    <div>
        <div v-if="assetRequest" class="mb-[10px]">
            <div class="flex items-center gap-[10px]">
                <UserPanel v-if="assetRequest.send_user" :user="assetRequest.send_user" size="20" with-name disable-instant/>
                <div v-if="assetRequest.from_external_user">{{ assetRequest.from_external_user }}</div>
                <div>➞</div>
                <UserPanel v-if="assetRequest.recieve_user" :user="assetRequest.recieve_user" size="20" with-name disable-instant/>
                <div v-if="assetRequest.to_external_user">{{ assetRequest.to_external_user }}</div>
            </div>
        </div>
        <div v-if="assetRequest && assetRequest.files && assetRequest.files.length > 0" class="flex flex-col mb-[20px]">
            <div class="mb-[10px]">現状の様子</div>
            <AssetFiles :list="assetRequest.files"/>
        </div>
        <AssetRequestBox 
            :item="item" 
            :is-sender-manager="isManager"
            :is-receive-manager="isManager"
            :assetRequest="assetRequest"
            v-for="item in steps"
        />
    </div>
</div>
</template>
<script setup lang="ts">
import { Asset, AssetRequest } from '@/interface/assetInterface';
import { computed } from 'vue';
import AssetRequestBox from './AssetRequestBox.vue';
import { useAuthUserStore } from '@/store/auth';
import UserPanel from '../Global/UserPanel.vue';
import AssetFiles from './AssetFiles.vue';
const props = defineProps<{
    asset: Asset
    assetRequest: AssetRequest
}>()

const auth = useAuthUserStore()
const steps = computed(() => {
    return props.assetRequest.steps
})

const isManager = computed(() => {
    const project = props.asset.current_project
    const managers = project?.manager.map(m => m.id) || []
    return auth.activeUser?.id && managers.includes(auth.activeUser.id) ? true : false
})
</script>