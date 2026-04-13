<template>
<div>
    <div>  
        <div class="flex flex-col gap-[15px]">
            <div>
                <p>詳細（スペックなど）</p>
                <p class="whitespace-break-spaces leading-normal mt-2" v-html="urlCheck(asset.specs)"></p>
                <div v-if="displayFieldValues.length" class="my-5">
                    <div v-for="fv in displayFieldValues" :key="fv.id" class="mb-3">
                        <div class="text-[gray] mb-3">{{ fieldLabel(fv) }}</div>

                        <div v-if="fv.field?.input_type === 'password'">
                            <span v-if="!revealedPasswords[fv.asset_category_item_field_id]" class="text-[gray] mr-2">********</span>
                            <span v-else class="mr-2">{{ revealedPasswords[fv.asset_category_item_field_id] }}</span>
                            <span
                                v-if="auth.isAdmin || auth.id === asset.current_user?.id"
                                class="jump-link"
                                @click="toggleReveal(fv.asset_category_item_field_id)"
                            >
                                {{ revealedPasswords[fv.asset_category_item_field_id] ? '非表示' : '表示' }}
                            </span>
                        </div>
                        <div
                            v-else
                            class="whitespace-break-spaces leading-normal"
                            v-html="urlCheck((fv.value ?? '') as any)"
                        ></div>
                    </div>
                </div>
                <p class="mt-3 text-[gray]">登録日 : {{ customParser(asset.created_at).toFormat('yyyy/M/d HH:mm') }}</p>
            </div>
            <div class="flex gap-3">
                <div @click="confirmView = !confirmView" class="jump-link">確認履歴({{ asset.confirm_logs.length }})</div>
                <div @click="historyView = !historyView" class="jump-link">移動履歴({{ asset.request_logs.length }})</div>
            </div>
        </div>
        <Transition name="slidePop">
            <div v-if="historyView" class="my-[20px]">
                <div v-if="asset.request_logs.length" class="flex flex-col gap-[20px]">
                    <div v-for="(assetRequest, index) in asset.request_logs" :key="assetRequest.id" class="flex flex-col">
                        <div class="mt-[10px]">ステータス : {{ assetRequest.status == 2 ? '完了' : '差し戻し' }}</div>
                        <div class="flex items-center gap-[10px] my-[10px]">
                            <div v-if="assetRequest.from_external_user">{{ assetRequest.from_external_user }}</div>
                            <UserPanel v-else-if="assetRequest.send_user" :user="assetRequest.send_user" size="20" with-name disable-instant/>
                            <div class="text-[gray]" v-if="assetRequest.from_external_user && assetRequest.send_user">責任者：{{ assetRequest.send_user.name }}</div>
                            <div>➞</div>
                            <div v-if="assetRequest.to_external_user">{{ assetRequest.to_external_user }}</div>
                            <UserPanel v-else-if="assetRequest.recieve_user" :user="assetRequest.recieve_user" size="20" with-name disable-instant/>
                            <div class="text-[gray]" v-if="assetRequest.to_external_user && assetRequest.recieve_user">責任者：{{ assetRequest.recieve_user.name }}</div>
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
                                        <div>移動申請者 : {{ item?.creator?.name }}</div>
                                    </div>
                                    <div v-if="item.approver">
                                        <div class="flex items-center py-[5px]">
                                            <div class="text-[12px] text-[gray]">【{{ customParser(item.approved_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                                            <div>経営管理本部承認者 : {{ item?.approver?.name }}</div>
                                        </div>
                                        <div class="mt-4">メモ : {{ assetRequest?.memo }}</div>
                                    </div>
                                </div>                            
                            </div> 
                        </div>
                        <div class="w-full h-[1px] bg-[gray] mt-5 opacity-45" v-show="index !== asset.request_logs.length - 1"></div>
                    </div>
                </div>
                <div v-else>
                    <div class="text-[gray]">移動履歴はありません</div>
                </div>  
            </div>
        </Transition>
        <Transition name="slidePop">
            <div v-if="confirmView" class="mt-4">
                <div v-if="asset.confirm_logs.length" class="flex flex-col gap-[20px]">
                    <div v-for="(log, index) in asset.confirm_logs" :key="log.id" class="flex flex-col">
                        <div class="flex items-center gap-[10px] my-[10px]">
                        <UserPanel v-if="log.user" :user="log.user" size="20" with-name disable-instant/>
                            <div class="text-[11px] text-[gray]">【{{ customParser(log.created_at).toFormat('yyyy/M/d HH:mm') }}】</div>
                        </div>
                        <div class="mb-[10px] whitespace-break-spaces" v-html="urlCheck(log.memo)"></div>
                        <div v-if="log.files.length">
                            <Files style="margin-top: 15px;" :items="log.files" :path="'asset_confirm_files'"/>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
    <div class="flex gap-[10px] mt-[20px]">
        <CommandButton 
            v-if="!auth.isAdmin && hasMovePrivilege && !asset.requests.length"
            :buttons="[
                {title: '移動', action: () => move(null, '物品を移動する')},
                {title: '返却', action: () => returnTo(610, '物品を返却する')},
            ]"
        />
        <CommandButton 
            v-if="auth.isAdmin || auth?.user?.general_position !== '一般職'"
            :buttons="[
                {title: '物品確認', action: () => { checkStart() }},
            ]"
        />      
        <CommandButton 
            v-if="auth.isAdmin || auth.id === asset.current_user?.id"
            :buttons="[
                {title: '編集', action: () => editAsset(asset)},
                {title: '削除', action: () => removeAsset(asset.id)},
            ]"
        />  
    </div>


    <Teleport to="body">
        <Transition name="modalFade">
            <Modal @close="closeMoveModal" v-if="moveTarget.active">
                <template #title>
                    <p>{{ moveTarget.title}}</p>
                </template>
                <template #content>
                    <div class="si-box">
                        <div class="flex gap-1 text-sm mb-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="isExternal" type="radio" name="external-member" class="custom-f-radio" :value="false"/>
                                社内メンバー
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="isExternal" type="radio" name="external-member" class="custom-f-radio" :value="true"/>
                                社外メンバー
                            </label>
                        </div>
                        <div v-if="isExternal" class="mb-7">
                            <ShortInput                                
                                name="externalUser"
                                :place-holder="'使用者名を入力'"
                                rules="required"
                                v-model="externalUser"
                                ref="externalUserNameRef"
                            />
                        </div>
                        <MemberSelector 
                            :place-holder="isExternal ? '社内での責任者を選択' : '移動先メンバー'"
                            v-model="reciever"
                            :multiple="false"
                            :options="isExternal ? userList : userList.filter(member => member.id != asset.current_user?.id)"
                            rules="required"
                            ref="memberSelectRef"
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
        </Transition>
    </Teleport>
    <Teleport to="body">
        <Transition name="modalFade">
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
        </Transition>
    </Teleport>
    <Teleport to="body">
        <Transition name="modalFade">
        <AssetConfirm 
            :asset="props.asset"
            v-if="confirmWindow"
            @close="flag => {confirmWindow = false; if(flag) emit('reload'); }"
        />
        </Transition>
    </Teleport>
</div>
</template>
<script setup lang="ts">
import { Asset } from '@/interface/assetInterface';
import { useAuthUserStore } from '@/store/auth';
import CommandButton from '../Global/CommandButton.vue';
import { computed, reactive, ref, useTemplateRef, watch } from 'vue';
import Modal from '../Global/Modal.vue';
import MemberSelector from '../Form/MemberSelector.vue';
import { User } from '@/interface/globalInterface';
import FileUploader from '../Form/FileUploader.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { customParser, urlCheck } from '@/utils/tools';
import UserPanel from '../Global/UserPanel.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import ShortInput from '../Form/ShortInput.vue';
import AssetConfirm from './AssetConfirm.vue';
import Files from '../Global/Files.vue';
import { useAsset } from '@/composables/asset';

const auth = useAuthUserStore()
const props = defineProps<{
    asset: Asset
    tagOptions: {title: string, requiredData: string}[]
}>()
const emit = defineEmits<{
    reload: []
    edit: [asset: Asset]    
}>()
const api = useApi()
const { ask } = useDialog()
const reciever = ref<User>()
const { userList } = useAsset()
watch(() => reciever.value, () => {
    selectedProject.value = null
})
const asset_files = ref<any[]>([])
const notBroken = ref(false)
const loading = ref(false)
const selectedProject = ref<number | null>(null)
const isExternal = ref(false)
const externalUser = ref('')
const externalUserNameRef = useTemplateRef('externalUserNameRef')
const memberSelectRef = useTemplateRef('memberSelectRef')
const { ping } = useDialog()
const confirmView = ref(false)
const revealedPasswords = ref<Record<number, string>>({})
const moveTarget = reactive({
    title: '',
    active: false
})
const returnTarget = reactive({
    title: '',
    active: false
})  
const confirmWindow = ref(false)
const historyView = ref(false)
const move = (target:number | null, title: string) => {
    moveTarget.title = title
    moveTarget.active = true
    
}
const detailTitle = computed(() => {
    const foundTag = props.tagOptions.find(tag => tag.title === props.asset.item_name)
    return foundTag ? foundTag.requiredData : '詳細（スペックなど）'
})
const hasMovePrivilege = computed(() => {
    if(!auth.activeUser || !auth.activeUser.id){
        return false
    }   
    const assetProject = props.asset.current_project
    const managers = assetProject?.manager.map(m => m.id) ?? []
    const privilageMembers = [...managers, 608, 610]
    return (props.asset?.current_user?.id == auth.activeUser.id || privilageMembers.includes(auth.activeUser.id))
})
const checkStart = () => {
    if(props.asset.current_user?.id == auth.activeUser?.id ){
        ping('物品確認は使用者本人では行えません。<strong>A以上の職階</strong>の他者による確認が必要です。')
        return
    }
    confirmWindow.value = true
}   
const applyReturnRequest = async() => {


    loading.value = true
    const data = {
        asset_id: props.asset.id,
        file_ids: asset_files.value.map(file => file.id),
        not_broken: notBroken.value,
        external_user: isExternal.value ? externalUser.value : null,
    }
    const res = await api.post('/asset_return_request', data, {
        ask: '物品を返却しますか？',
        toast: '申請が完了しました。'
    })
    loading.value = false
    if(res){
        returnTarget.active = false
        emit('reload')
    }

}
const applyRequest = async() => {
    if(isExternal.value){
        const valid = await externalUserNameRef.value?.validate()
        if(!valid?.valid){
            ping('社外メンバー名を入力してください。')
            return
        }
    }
    const valid = await memberSelectRef.value?.validate()
    if(!valid?.valid){
        ping( isExternal.value ? '責任者を選択してください。' : '移動先メンバーを選択してください。')
        return
    }

    loading.value = true
    const data = {
        asset_id: props.asset.id,
        to_user: reciever.value?.id ?? null,
        file_ids: asset_files.value.map(file => file.id),
        not_broken: notBroken.value,
        to_external_user: externalUser.value ?? null,
    }

    const res = await api.post('/asset_move_request', data, {
        toast: '申請が完了しました。'
    })
    loading.value = false
    moveTarget.active = false
    emit('reload')
}
const editAsset = (asset: Asset) => {
    emit('edit', asset)
}   
const removeAsset = async(id: number) => {
    const data = await api.del('/delete_asset', { id }, {
        toast: '物品を削除しました。',
        ask: '物品を削除しまか？',
    })
    emit('reload')  
}
const returnTo = (target:number, title: string) => {
    returnTarget.title = title
    returnTarget.active = true
}
const closeMoveModal = () => {
    moveTarget.active = false
    returnTarget.active = false
    isExternal.value = false
    externalUser.value = ''
    notBroken.value = false
    asset_files.value = []
    reciever.value = undefined
}

type FieldValue = NonNullable<Asset['field_values']>[number]

const displayFieldValues = computed(() => {
    const values = (props.asset.field_values ?? []) as FieldValue[]
    return values
        .filter(fv => !!fv.field)
        .slice()
        .sort((a, b) => (a.field?.id ?? 0) - (b.field?.id ?? 0))
})

const fieldLabel = (fv: FieldValue) => {
    return fv.field?.label || fv.field?.key || '項目'
}

const toggleReveal = async (fieldId: number) => {
    if (!(auth.isAdmin || auth.id === props.asset.current_user?.id)) {
        ping('パスワードを表示できるのは管理者と使用者本人のみです。')
        return
    }

    if (revealedPasswords.value[fieldId]) {
        const next = { ...revealedPasswords.value }
        delete next[fieldId]
        revealedPasswords.value = next
        return
    }

    const res = await api.get(`/asset_reveal_password`, { id: props.asset.id, field_id: fieldId })
    if (res && res.plain_password) {
        revealedPasswords.value = {
            ...revealedPasswords.value,
            [fieldId]: res.plain_password,
        }
        return
    }

    ping('パスワードを取得できませんでした。')
}
</script>