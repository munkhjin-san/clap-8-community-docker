<template>
<div>
    <div>  
        <div class="flex flex-col gap-[15px]">
            <div>{{ asset.item_name }}</div>
            <div>
                <p>詳細（スペックなど）</p>
                <p class="whitespace-break-spaces leading-normal" v-html="urlCheck(asset.specs)"></p>
            </div>
            <div @click="historyView = !historyView" class="jump-link">移動履歴({{ asset.request_logs.length }})</div>
        </div>
        
        <div v-if="historyView" class="my-[20px]">
            <div v-if="asset.request_logs.length" class="flex flex-col gap-[20px]">
                <div v-for="assetRequest in asset.request_logs" :key="assetRequest.id" class="flex flex-col">
                    <div class="mt-[10px]">ステータス : {{ assetRequest.status == 2 ? '完了' : '差し戻し' }}</div>
                    <div class="flex items-center gap-[10px] my-[10px]">
                        <UserPanel v-if="assetRequest.send_user" :user="assetRequest.send_user" size="20" with-name/>
                        <div>➞</div>
                        <UserPanel v-if="assetRequest.recieve_user" :user="assetRequest.recieve_user" size="20" with-name/>
                    </div>
                    <div v-for="item in assetRequest.steps">
                        <div>            
                            <div v-if="item.value == 1">
                                <div class="flex items-center py-[5px]">
                                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                    <div>移動申請 : {{ assetRequest?.send_user?.name }}</div>
                                </div>
                                <div v-if="item.approver">
                                    <div class="flex items-center py-[5px]">
                                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                        <div>PM承認済み : {{ item?.approver?.name }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="item.value == 2">                
                                <div class="flex items-center py-[5px]">
                                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                    <div>受け取り : {{ assetRequest?.recieve_user?.name }}</div>
                                </div>
                                <div v-if="item.approver">
                                    <div class="flex items-center py-[5px]">
                                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                        <div>受取人承認済み : {{ item?.approver?.name }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="item.value == 3">
                                <div class="flex items-center py-[5px]">
                                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                    <div>プロジェクト内利用申請 : {{ assetRequest?.recieve_user?.name }}</div>
                                </div>
                                <div v-if="item.approver">
                                    <div class="flex items-center py-[5px]">
                                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                        <div>受取PM承認済み : {{ item?.approver?.name }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="item.value == 4">
                                <div class="flex items-center py-[5px]">
                                    <div class="text-[12px] text-[gray]">【{{ customParser(item.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                    <div>移動完了申請 : {{ item?.creator?.name }}</div>
                                </div>
                                <div v-if="item.approver">
                                    <div class="flex items-center py-[5px]">
                                        <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                        <div>経営管理本部承認済み : {{ item?.approver?.name }}</div>
                                    </div>
                                </div>
                            </div>                            
                        </div> 
                    </div>
                </div>
            </div>
            <div v-else>
                <div class="text-[gray]">移動履歴はありません</div>
            </div>  
        </div>
    </div>
    <div v-if="hasMovePrivilege && !asset.requests.length" class="flex gap-[10px] mt-[20px]">
        <CommandButton 
            :buttons="[
                {title: '移動', action: () => move(null, '物品を移動する')},
                {title: '返却', action: () => returnTo(610, '物品を返却する')},
            ]"
        />
    </div>

    <Teleport to="body">
        <Modal @close="moveTarget.active = false" v-if="moveTarget.active">
            <template #title>
                <p>{{ moveTarget.title}}</p>
            </template>
            <template #content>
                <div class="si-box">
                    <MemberSelector 
                        place-holder="移動先メンバー"
                        :options="possibleMembers.filter(member => member.id != asset.current_user?.id)"
                        :multiple="false"
                        v-model="reciever"
                    />
                </div>
                <div class="si-box" v-if="reciever && reciever.id" :key="reciever.id">
                    <ItemSelector
                        :path="`get_possible_projects_by_user?user_id=${reciever.id}`"
                        :clearable="true"
                        label="name"
                        :reduce="option => option.id"
                        :closeOnSelect=true
                        :multiple="false"
                        place-holder="移動先プロジェクト"
                        rules="required"
                        ref="projectSelectorRef"
                        v-model="selectedProject"
                    />
                </div>
                <div class="si-box">
                    <label class="cal-member-check" style="align-self: center;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
                        <input v-model="notBroken" name="memberCheckBox" type="checkbox">
                        <span class="cal-check-mark" style="top: 5px;"></span>
                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">                    
                            <p class="userName">動作確認・故障チェック</p>                                    
                        </div>
                    </label>
                </div>

                <div class="si-box">
                    <p class="mb-[20px]">現状の様子「写真データ」</p>
                    <FileUploader 
                        path="/asset_files"
                        v-model="asset_files"
                    />  
                </div>

                <div class="si-box">
                    <LoaderButton content="申請する" :loading="loading" @triggered="applyRequest"/>
                </div>
            </template>
        </Modal>
    </Teleport>
    <Teleport to="body">
        <Modal @close="returnTarget.active = false" v-if="returnTarget.active">
            <template #title>
                <p>{{ returnTarget.title}}</p>
            </template>
            <template #content>
                <div class="si-box">
                    <label class="cal-member-check" style="align-self: center;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
                        <input v-model="notBroken" name="memberCheckBox" type="checkbox">
                        <span class="cal-check-mark" style="top: 5px;"></span>
                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">                    
                            <p class="userName">動作確認・故障チェック</p>                                    
                        </div>
                    </label>
                </div>

                <div class="si-box">
                    <p class="mb-[20px]">現状の様子「写真データ」</p>
                    <FileUploader 
                        path="/asset_files"
                        v-model="asset_files"
                    />  
                </div>
                <div class="si-box">
                    <LoaderButton content="申請する" :loading="loading" @triggered="applyReturnRequest"/>
                </div>
            </template>
        </Modal>
    </Teleport>
</div>
</template>
<script setup lang="ts">
import { Asset } from '@/interface/assetInterface';
import { useAuthUserStore } from '@/store/auth';
import CommandButton from '../Global/CommandButton.vue';
import { computed, inject, reactive, ref, watch } from 'vue';
import Modal from '../Global/Modal.vue';
import MemberSelector from '../Form/MemberSelector.vue';
import { DialogMethods, User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import FileUploader from '../Form/FileUploader.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import axios from 'axios';
import { customParser, urlCheck } from '@/utils/tools';
import UserPanel from '../Global/UserPanel.vue';
import ItemSelector from '../Form/ItemSelector.vue';
const { confirm, info, notify } = inject('dialog') as DialogMethods
const auth = useAuthUserStore()
const props = defineProps<{
    asset: Asset
    possibleMembers: User[]
    possibleProjects: Project[]
}>()
const emit = defineEmits<{
    reload: []
}>()
const reciever = ref<User>()
watch(() => reciever.value, () => {
    selectedProject.value = null
})
const asset_files = ref<any[]>([])
const notBroken = ref(false)
const loading = ref(false)
const selectedProject = ref<number | null>(null)
const moveTarget = reactive({
    title: '',
    active: false
})
const returnTarget = reactive({
    title: '',
    active: false
})  
const historyView = ref(false)
const move = (target:number | null, title: string) => {
    moveTarget.title = title
    moveTarget.active = true
    
}
const hasMovePrivilege = computed(() => {
    if(!auth.activeUser || !auth.activeUser.id){
        return false
    }   
    const assetProject = props.asset.current_project
    const managers = assetProject?.manager.map(m => m.id) ?? []
    const privilageMembers = [...managers, 608, 610]
    return (props.asset?.current_user?.id == auth.activeUser.id || privilageMembers.includes(auth.activeUser.id))
})
const applyReturnRequest = async() => {
    try{
        loading.value = true
        const data = {
            asset_id: props.asset.id,
            file_ids: asset_files.value.map(file => file.id),
            not_broken: notBroken.value

        }
        await axios.post('/asset_return_request', data)
        loading.value = false
        returnTarget.active = false
        info('申請が完了しました。')
        emit('reload')
    }   
    catch(e){
        loading.value = false
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }

}
const applyRequest = async() => {
    try{
        let confirmed = true
        if(props.asset.current_project){
            const anwser = await confirm('この物品はプロジェクトに紐づいています。移動しますか？<br>※PMの承認が必要です。')
            if(!anwser.value){
                confirmed = false
            }
        }

        if(!confirmed){
            return
        }
        loading.value = true
        const data = {
            asset_id: props.asset.id,
            to_user: reciever.value?.id,
            file_ids: asset_files.value.map(file => file.id),
            not_broken: notBroken.value,
            to_project: selectedProject.value
        }
        await axios.post('/asset_move_request', data)
        loading.value = false
        moveTarget.active = false
        info('申請が完了しました。')
        emit('reload')
    }   
    catch(e){
        loading.value = false
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }

}
const returnTo = (target:number, title: string) => {
    returnTarget.title = title
    returnTarget.active = true
}

</script>