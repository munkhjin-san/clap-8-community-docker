<template>
<div class="w-fit">
    <div>
        <div>            
            <div v-if="item.value == 1">
                <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                    <div>移動申請 : {{ assetRequest?.send_user?.name }}</div>
                </div>
                <div v-if="!item.approver">         
                        
                    <div v-if="isSenderManager" class="flex gap-[10px] my-[10px] items-center">
                        <div>PM承認待ち</div>
                        <CommandButton :buttons="[
                            {title: '承認', action: () => managerDecision('承認', 2)},
                            {title: '差戻', action: () => managerDecision('差し戻し', 3)}
                        ]"/>
                    </div>            

                </div>
                <div v-else>
                    <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                        <div>PM承認済み : {{ item?.approver?.name }}</div>
                    </div>
                </div>
            </div>
            <div v-else-if="item.value == 2">                
                <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                    <div>受け取り : {{ assetRequest?.recieve_user?.name }}</div>
                </div>
                <div class="my-[10px]" v-if="!item.approver && assetRequest.recieve_user && assetRequest.recieve_user.id !== auth.id">受取人承認待ち</div>
                <div v-if="!item.approver && assetRequest.recieve_user && assetRequest.recieve_user.id == auth.id" class="flex gap-[10px] my-[10px] items-center">
                    <div>受取人承認待ち</div>
                    <CommandButton :buttons="[
                        {title: '承認', action: () => receiverDecision('承認', 2)},
                        {title: '差戻', action: () => receiverDecision('差し戻し', 3)}
                    ]"/>
                </div>
                <div v-if="item.approver">
                    <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                        <div>受取人承認済み : {{ item?.approver?.name }}</div>
                    </div>
                </div>
            </div>

            <div v-else-if="item.value == 3">
                <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                    <div>プロジェクト内利用申請 : {{ assetRequest?.recieve_user?.name }}</div>
                </div>
                <div class="my-[10px]" v-if="!item.approver && !isReceiveManager">受取PMPM承認待ち</div>
                <div v-if="!item.approver && isReceiveManager" class="flex gap-[10px] my-[10px] items-center">
                    <div>受取PM承認待ち</div>
                    <CommandButton :buttons="[
                        {title: '承認', action: () => receiverManagerDecision('承認', 2)},
                        {title: '差戻', action: () => receiverManagerDecision('差し戻し', 3)}
                    ]"/>
                </div>
                <div v-if="item.approver">
                    <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                        <div>受取PM承認済み : {{ item?.approver?.name }}</div>
                    </div>
                </div>
            </div>
            <div v-else-if="item.value == 4">
                <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                    <div>移動完了申請 : {{ item?.creator?.name }}</div>
                </div>
                <div class="my-[10px]" v-if="!item.approver && !isAuthorized">経営管理本部承認待ち</div>
                <div v-if="!item.approver && isAuthorized" class="flex gap-[10px] my-[10px] items-center">
                    <div>経営管理本部承認待ち</div>
                    <CommandButton :buttons="[
                        {title: '承認', action: () => returnDecision('承認', 8)},
                        {title: '差戻', action: () => returnDecision('差し戻し', 3)}
                    ]"/>
                </div>
                <div v-if="item.approver">
                    <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                        <div>経営管理本部承認済み : {{ item?.approver?.name }}</div>
                    </div>
                </div>
            </div>

            <div v-else-if="item.value == 7">
                <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                    <div>返却申請 : {{ item?.creator?.name }}</div>
                </div>
                <div class="my-[10px]" v-if="!item.approver && !isAuthorized">経営管理本部承認待ち</div>
                <div v-if="!item.approver && isAuthorized" class="flex gap-[10px] mt-[10px] items-center">
                    <div>経営管理本部承認待ち</div>
                    <CommandButton :buttons="[
                        {title: '承認', action: () => setStorePlace()},
                        {title: '差戻', action: () => receiverManagerDecision('差し戻し', 3)}
                    ]"/>
                </div>
                <div v-if="item.approver">
                    <div class="flex items-center py-[5px] flex-wrap gap-[5px]">
                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                        <div>経営管理本部承認済み : {{ item?.approver?.name }}</div>
                    </div>
                </div>
            </div>


            
                
        </div>        

    </div>   
    <Teleport to="body">
        <Modal v-if="projectSelector" @close="projectSelector = false">
            <template #title>
                <p>物品を利用するプロジェクトを選択</p>
            </template>
            <template #content>
                <div class="si-box">
                    <ItemSelector 
                        :path="`get_possible_projects_by_user?user_id=${auth.id}`"
                        :clearable="true"
                        label="name"
                        :reduce="option => option.id"
                        :closeOnSelect=true
                        :multiple="false"
                        place-holder="プロジェクトを選択"
                        rules="required"
                        ref="projectSelectorRef"
                        v-model="selectedProject"
                    />
                </div>
                <div class="si-box">
                    <LoaderButton :loading="loading" @triggered="acceptAsset" content="保存する"/>

                </div>
            </template>
        </Modal>
    </Teleport> 
    <Teleport to="body">
        <Modal v-if="officeSelector" @close="officeSelector = false">
            <template #title>
                <p>物品を保管する場所を選択</p>
            </template>
            <template #content>
                <div class="si-box">
                    <ItemSelector 
                        :path="`get_possible_offices`"
                        :clearable="true"
                        label="name"
                        :reduce="option => option.id"
                        :closeOnSelect=true
                        :multiple="false"
                        place-holder="保管場所を選択"
                        rules="required"
                        ref="officeSelectorRef"
                        v-model="selectedOffice"
                    />
                </div>
                <div class="si-box">
                    <LoaderButton :loading="loading" @triggered="receiverManagerDecision('承認', 2, {office_id: selectedOffice})" content="保存する"/>

                </div>
            </template>
        </Modal>
    </Teleport> 

    
</div>
</template>
<script setup lang="ts">
import { AssetRequest, AssetRequestStep } from '@/interface/assetInterface';
import CommandButton from '../Global/CommandButton.vue';
import { inject, ref, useTemplateRef } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import Modal from '../Global/Modal.vue';
import ItemSelector from '../Form/ItemSelector.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { customParser } from '@/utils/tools';
import { ComponentExposed } from 'vue-component-type-helpers';
import { useBadgeStore } from '@/store/badge';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';

const props = defineProps<{
    item: AssetRequestStep,
    assetRequest: AssetRequest,
    isSenderManager: boolean,
    isReceiveManager: boolean,
}>()
const api = useApi()
const { ask, ping, toast } = useDialog() 
const badge = useBadgeStore()
const auth = useAuthUserStore()
const getAssets = inject('getAssets') as () => void
const selectedProject = ref(props.assetRequest.to_project || null)
const projectSelector = ref(false)
const officeSelector = ref(false)
const loading = ref(false)
const projectSelectorRef = useTemplateRef<ComponentExposed<typeof ItemSelector>>('projectSelectorRef')
const officeSelectorRef = useTemplateRef<ComponentExposed<typeof ItemSelector>>('officeSelectorRef')
const selectedOffice = ref('')
const managerDecision = async(message: string, status: number) => {
    const title = `${message}しますか？`
    const confirmed = await ask(title)
    if(!confirmed.value) return
    const params = {
        step_id: props.item.id,
        status: status
    }
    await updateAsset(params)
    toast(`${message}しました。`)
    
}
const isAuthorized = auth.activeUser?.id === 610 || auth.activeUser?.id === 608
const receiverDecision = async(message:string, status:number) => {
    if(status == 2){
        projectSelector.value = true
        return
    }
    const title = `${message}しますか？`
    const confirmed = await ask(title)
    if(!confirmed.value) return
    const params = {
        step_id: props.item.id,
        status: status
    }
    await updateAsset(params)
    toast(`${message}しました。`)
        
}
const setStorePlace = () => {
    officeSelector.value = true
}
const acceptAsset = async() => {
    const validate = await projectSelectorRef.value?.validate()
    if(!validate?.valid){
        ping('プロジェクトを選択してください。')
        return
    }
    const params = {
        step_id: props.item.id,
        project_id: selectedProject.value,
        status: 2

    }
    await updateAsset(params)
    projectSelector.value = false
    toast('承認しました。')
}
const receiverManagerDecision = async(message:string, status:number, additionalParams?:{[key:string]: any}) => {
    const title = `${message}しますか？`
    if(additionalParams && 'office_id' in additionalParams){
        const valid = await officeSelectorRef.value?.validate()
        if(!valid?.valid){
            ping('保管場所を選択してください。')
            return
        }
    }
    const confirmed = await ask(title)
    if(!confirmed.value) return
    let params = {
        step_id: props.item.id,
        status: status
    }
    if(additionalParams){
        params = {
            ...params,
            ...additionalParams
        }
    }
    await updateAsset(params)
    toast(`${message}しました。`)
}
const returnDecision = async(message:string, status:number) => {
    const title = `${message}しますか？`
    const confirmed = await ask(title)
    if(!confirmed.value) return
    const params = {
        step_id: props.item.id,
        status: status
    }
    await updateAsset(params)
    toast(`${message}しました。`)
}
const updateAsset = async(params) => {
    if(loading.value) return
    loading.value = true
    await api.post('/asset_approve', params)
    toast('更新しました。')
    getAssets()
    badge.getAssetBadge()
    loading.value = false
    return true
}
</script>