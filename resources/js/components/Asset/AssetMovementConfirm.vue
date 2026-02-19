<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <div class="text-lg font-bold">{{ isReturnRequest ? '返却申請の対応' : '移動申請の対応' }}</div>
        </template>
        <template #content>
            <div class="flex">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" :value="2" name="decision" v-model="statusFlag" class="custom-f-radio"/>
                    承認
                </label>
                <label class="flex items-center gap-2 cursor-pointer ml-4">
                    <input type="radio" :value="3" name="decision" v-model="statusFlag" class="custom-f-radio"/>
                    差戻
                </label>
            </div>
            <div class="si-box" v-if="!isReturnRequest && assetRequest.to_external_user">
                <MemberSelector
                    place-holder="社内での責任者を選択"
                    :options="userList"
                    :multiple="false"
                    v-model="responsibleInternal"
                />
            </div>
            <div class="si-box">
                <LongInput v-model="memo" place-holder="メモ"/>
            </div>
            <div v-if="isReturnRequest" class="si-box">
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
                <LoaderButton :loading="saving" @triggered="assetDesicion" content="保存する"/>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { Asset, AssetRequest } from '@/interface/assetInterface';
import { User } from '@/interface/globalInterface';
import { onMounted, ref } from 'vue';
import MemberSelector from '../Form/MemberSelector.vue';
import ItemSelector from '../Form/ItemSelector.vue';
import Modal from '../Global/Modal.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import LongInput from '../Form/LongInput.vue';
import { useAsset } from '@/composables/asset';

const emit = defineEmits<{
    close: [flag: boolean]
}>()

const props = defineProps<{
    asset: Asset
    assetRequest: AssetRequest
    isReturnRequest: boolean
}>()

const saving = ref(false)
const statusFlag = ref<number | null>(null)
const selectedOffice = ref<number | null>(null)
const memo = ref('')
const api = useApi()
const { ping, ask, toast } = useDialog()
const responsibleInternal = ref<User | null>(props.assetRequest.recieve_user ?? null)
const { userList, fetchAssetUsers } = useAsset()

onMounted(() => {
    if(userList.value.length === 0) {
        fetchAssetUsers([])
    }
})

const assetDesicion = async() => {
    if(statusFlag.value === null) {
        ping('承認または差戻を選択してください。')
        return
    }
    if(statusFlag.value == 2 && props.isReturnRequest && !selectedOffice.value){
        ping('保管場所を選択してください。')
        return
    }
    const message = statusFlag.value == 2 ? '承認' : '差戻'
    const confirm = await ask(`${message}しますか？`)
    if(!confirm.value) return
    saving.value = true
    const params = {
        asset_request_id: props.assetRequest.id,
        status: statusFlag.value,
        memo: memo.value,
        office_id: selectedOffice.value,
        to_user: responsibleInternal.value ? responsibleInternal.value.id : null
    }
    await api.post('/asset_decision', params)
    saving.value = false
    toast('保存しました。')
    memo.value = ''
    selectedOffice.value = null
    emit('close', true)
}
</script>