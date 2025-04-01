<template>
    <div class="admin-window">
        <div class="h-full w-full">
            <div class="w-full h-[calc(100%-30px)] overflow-auto">            
                <table class="asset-table" style="margin: 20px;width: calc(100% - 40px);">
                    <AssetTableHeader 
                        :columns="['GL番号', '品名', '型番', '使用プロジェクト', '使用者', '分類', '価値', 'ステータス', '保管場所']"
                        :projects="possibleProjects" 
                        :users="possibleMembers"
                        :offices="possibleOffices"
                        v-model:user_id="searchQuery.user_id"
                        v-model:project_id="searchQuery.project_id"
                        v-model:classification="searchQuery.classification"
                        v-model:status="searchQuery.status"
                        v-model:office_id="searchQuery.office_id"
                        v-model:item_name="searchQuery.item_name"
                        v-model:model_number="searchQuery.model_number"
                        v-model:gl_number="searchQuery.gl_number"
                    />                   
                    <tbody v-if="assetsData && assetsData.data">
                        <template v-for="asset in assetsData.data">
                            <tr>
                                <td>{{ `GL${padNumber(asset.id)}` }}</td>
                                <td class="max-w-[150px] overflow-hidden text-ellipsis">{{ asset.item_name }}</td>
                                <td class="max-w-[150px] overflow-hidden text-ellipsis">{{ asset.model_number }}</td>
                                <td>
                                    <div class="leading-normal" v-if="asset.current_project">
                                        <p>{{ asset.current_project.name }}</p>
                                    </div>                            
                                </td>
                                <td>
                                    <div v-if="asset.current_user">
                                        <div class="leading-normal">
                                            <p>{{ asset.current_user.name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ AssetClass.find(ob => ob.value === asset.classification)?.label }}</td>
                                <td>{{ asset.value }}</td>
                                <td>{{ asset.requests.length ? '移動中' : AssetStatus.find(ob => ob.value === asset.status)?.label }}</td>
                                <td>
                                    <div class="leading-normal">
                                        <p>{{ asset?.current_office?.name }}</p>
                                    </div>
                                </td>
                                <td>
                                    <label class="cursor-pointer select-none jump-link">
                                        <input type="checkbox" name="asset-collector" v-model="selectedAssetIds" :value="asset.id" class="hidden"/>
                                        詳細
                                    </label>
                                    
                                </td>
                            </tr>
                            <tr v-if="asset?.requests && asset.requests.length || selectedAssetIds.includes(asset.id)">
                                <td colspan="9">                                    
                                    <div v-if="asset?.requests && asset.requests.length" class="bg-[var(--bg2)]">
                                        <AssetMovement 
                                            :asset="asset" 
                                            :assetRequest="assetRequest"
                                            v-for="assetRequest in asset.requests
                                        "/>
                                    </div>
                                    <div v-if="selectedAssetIds.includes(asset.id)">                                        
                                        <AssetDetail 
                                            :asset="asset" 
                                            :possibleMembers="possibleMembers" 
                                            :possibleProjects="possibleProjects"
                                            @reload="getAdminAssetList(assetsData.current_page)"
                                        />
                                        <div class="flex gap-[10px] mt-[20px]">
                                            <CommandButton
                                                :buttons="[
                                                    {title: '編集', action: () => editAsset(asset)},
                                                    {title: '削除', action: () => removeAsset(asset.id)},
                                                ]"
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div>
                <PostSearchPager 
                    style="margin: 0;"
                    :possiblePage="assetsData.last_page" 
                    :activePath="assetsData.current_page" 
                    @setNavi="(index) => getAdminAssetList(assetsData.current_page + index)"
                    @setActivePage="(index) => getAdminAssetList(index)"/>
            </div>
        </div>
        <FloatButton type="plus" @action="createWindow = true"/>
        <AssetCreate
            v-if="createWindow"
            :editData="editData"
            :allMembers="possibleMembers"
            :allProjects="possibleProjects"
            mode="admin"
            @close="closeModal"
        />
    </div>
</template>
<script setup lang="ts">
import { Asset } from '@/interface/assetInterface';
import axios from 'axios';
import { inject, onMounted, reactive, ref, watch } from 'vue';
import AssetClass from 'assets/AssetClass.json'
import AssetStatus from 'assets/AssetStatus.json'
import AssetDetail from '@/components/Asset/AssetDetail.vue';
import AssetMovement from '@/components/Asset/AssetMovement.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { DialogMethods, Office } from '@/interface/globalInterface';
import AssetCreate from '@/components/Asset/AssetCreate.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import PostSearchPager from '@/components/Post/PostSearchPager.vue';
import AssetTableHeader from './AssetTableHeader.vue';

const { notify, info, confirm} = inject('dialog') as DialogMethods
const assetsData = ref<{
    data: Asset[],
    first_page_url: string,
    next_page_url: string | null,
    prev_page_url: string | null,
    last_page_url: string,
    current_page: number,
    last_page: number,
    total: number
}>({
    data: [], 
    first_page_url: '', 
    next_page_url: null, 
    prev_page_url: null, 
    last_page_url: '', 
    current_page: 1, 
    last_page: 0, 
    total: 0
})
const searchQuery = reactive({
    item_name: '',
    model_number: '',
    classification: <number[]>[],
    status: <number[]>[],
    office_id: <number[]>[],
    user_id: <number[]>[],
    project_id: <number[]>[],
    gl_number: '',
})
const selectedAssetIds = ref<number[]>([])
const possibleMembers = ref([])
const possibleProjects = ref([])
const editData = ref<Asset | null>(null)
const createWindow = ref(false)


const possibleOffices = ref<Office[]>([])
onMounted(() => {
    getAdminAssetList()
    getPossibleMembers()
    getPossibleProjects()
    getPossibleOffice()
});
const getPossibleOffice = async() => {
    try {
        const response = await axios.get('/get_possible_offices')
        possibleOffices.value = response.data
    } catch (e) {

    }
}
const getPossibleMembers = async() => {
    try {
        const response = await axios.get('/get_possible_members')
        possibleMembers.value = response.data
    } catch (e) {

    }
}
const editAsset = (asset: Asset) => {
    editData.value = asset
    createWindow.value = true
}
const getPossibleProjects = async() => {
    try {
        const response = await axios.get('/get_possible_projects')
        possibleProjects.value = response.data
    } catch (e) {

    } 
}
const getAdminAssetList = async(page?:number) => {
    const pageIndex = page ?? 1
    assetsData.value = await axios.get(`/get_assets?page=${pageIndex}`, {
        params: searchQuery
    }).then(res => res.data)
}
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}
const removeAsset = async(id: number) => {

    const answer = await confirm('物品を削除しますよろしいでか？')
    if (!answer.value) return
    try {
        await axios.delete(`/delete_asset?id=${id}`)
        info('削除しました。')
        getAdminAssetList(assetsData.value.current_page)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }    
    
}
const closeModal = (reload: boolean) => {
    createWindow.value = false
    if (reload) {
        getAdminAssetList(assetsData.value.current_page)
    }
    editData.value = null
}
watch(searchQuery, () => {
    getAdminAssetList()
}, { deep: true })
</script>
<style lang="scss" scoped>
    .asset-header{
        display: flex;
        gap:20px;
    }
    .asset-table{
        background-color: var(--background-color);
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
        color: var(--primary-color);
    }
    .asset-table td{
        padding: 10px;
        font-size: 13px;
        border-bottom: 1px solid rgb(102, 102, 102);
        border-right: 1px solid rgb(102, 102, 102);
    }
    table td:first-child {
        border-left: 1px solid rgb(102, 102, 102);
    }
    thead td:first-child{
        border-left: 1px solid rgb(102, 102, 102);
    }
</style>