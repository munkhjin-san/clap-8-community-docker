<template>
    <div class="bg-[var(--background-color)] relative">
        <div class="">
            <div v-if="auth.isAdmin" class="flex justify-end my-[20px] mr-[20px]">
                <LoaderButton content="CSV出力" style="margin: 0" :loading="exporting" @triggered="exportCSV"/>
            </div>
            <div class="min-h-[calc(100%-50px)]">
                <table class="asset-table mx-4 w-[calc(100%-40px)]">
                    <AssetTableHeader 
                        :offices="allOffices"
                        v-model:user_id="searchQuery.user_id"
                        v-model:classification="searchQuery.classification"
                        v-model:status="searchQuery.status"
                        v-model:office_id="searchQuery.office_id"
                        v-model:item_name="searchQuery.item_name"
                        v-model:model_number="searchQuery.model_number"
                        v-model:gl_number="searchQuery.gl_number"
                        v-model:confirm_status="searchQuery.confirm_status"
                    />
                    <tbody v-if="assetsData && assetsData.data">
                        <template v-if="assetsData.data.length">                    
                            <template v-for="asset in assetsData.data">
                                <tr @click.stop="toggleAssetDetail(asset.id)" class="data-row cursor-pointer" :class="{ expanded: (asset?.requests?.length ?? 0) > 0 || selectedAssetIds.includes(asset.id) }">
                                    <td><div class="inner-col"><span class="mobile">GL番号</span>{{ `GL${padNumber(asset.id)}` }}</div></td>
                                    <td class="max-w-[150px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">品名</span>{{ asset.item_name }}</div></td>
                                    <td class="max-w-[150px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">型番</span>{{ asset.model_number }}</div></td>
                                    <td>
                                        <div class="inner-col"><span class="mobile">使用者</span>
                                            
                                            <div class="mb-2" v-if="asset.external_user">
                                                <span>{{ asset?.external_user }}</span>
                                            </div>
                                            <div v-if="asset.current_user">
                                                <div class="leading-normal">
                                                    <span v-if="asset.external_user">責任者：</span>
                                                    <span>{{ asset.current_user.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><div class="inner-col"><span class="mobile">ステータス</span>{{ asset.requests.length ? '移動中' : AssetStatus.find(ob => ob.value === asset.status)?.label }}</div></td>
                                    <td>
                                        <div class="leading-normal">
                                            <span>{{ asset?.current_office?.name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        {{ asset.confirm_logs.find(log => new Date(log.created_at).getFullYear() === new Date().getFullYear()) ? '確認済み' : '未確認' }}
                                    </td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="row-toggle"
                                            :aria-expanded="isExpanded(asset)"
                                            aria-label="詳細を開閉"
                                            @click.stop="toggleAssetDetail(asset.id)"
                                        >
                                            <span class="toggle-icon" :class="{ open: isExpanded(asset) }">
                                                <Back size="10" fill="var(--primary-color)" />
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="detail-row" v-if="asset?.requests && asset.requests.length || selectedAssetIds.includes(asset.id)">
                                    <td colspan="8" class="detail-cell" :class="{ open: isExpanded(asset) }">
                                        <Transition name="asset-accordion">
                                            <div v-show="isExpanded(asset)" class="asset-accordion-body">
                                                <div v-if="asset?.requests && asset.requests.length" class="bg-[var(--background-color)] w-fit rounded mb-4">
                                                    <AssetMovement 
                                                        v-for="assetRequest in asset.requests"
                                                        :asset="asset" 
                                                        :assetRequest="assetRequest"
                                                        @reload="getAssets(assetsData.current_page)"                                                       
                                                        
                                                    />
                                                </div>
                                                <div v-if="selectedAssetIds.includes(asset.id)">
                                                    <AssetDetail 
                                                        :asset="asset" 
                                                        :tagOptions="tagOptions"
                                                        @reload="getAssets(assetsData.current_page)"
                                                        @edit="(data) => { 
                                                            editData = data
                                                            openModal = true
                                                        }"
                                                    />
                                                </div>
                                            </div>
                                        </Transition>
                                    </td>

                                </tr>
                            </template>
                        </template>
                        <template v-else-if="fetchCount > 0">
                            <tr>
                                <td colspan="6" class="!text-center">データがありません</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <PostSearchPager 
                    style="margin: 0;"
                    :possiblePage="assetsData.last_page" 
                    :activePath="assetsData.current_page" 
                    @setNavi="(index) => getAssets(assetsData.current_page + index)"
                    @setActivePage="(index) => getAssets(index)"/>
            </div>
        </div>

        <FloatButton class="fixed" type="plus" @action="openModal = true">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>  
        <Teleport to="body">
            <Transition name="modalFade">                
                <AssetCreate 
                    v-if="openModal" 
                    :edit-data="editData"
                    :all-projects="possibleProjects"
                    :tagOptions="tagOptions"
                    :offices="allOffices"
                    @close="closeModal"
                />
            </Transition>
        </Teleport>
    </div>
</template>
<script lang="ts" setup>
import { onMounted, provide, reactive, ref, watch } from 'vue';
import { Asset } from '@/interface/assetInterface';
import FloatButton from '../Global/FloatButton.vue';
import AssetCreate from './AssetCreate.vue';
import AssetDetail from './AssetDetail.vue';
import AssetMovement from './AssetMovement.vue';
import PostSearchPager from '../Post/PostSearchPager.vue';
import AssetStatus from 'assets/AssetStatus.json'
import { useAuthUserStore } from '@/store/auth';
import AssetTableHeader from './AssetTableHeader.vue';
import { Office, User } from '@/interface/globalInterface';
import AddIcon from '../Form/AddIcon.vue';
import Back from '../Icons/Back.vue';
import { useApi } from '@/composables/api';
import LoaderButton from '../Global/LoaderButton.vue';
import { DateTime } from 'luxon';
import { useAsset } from '@/composables/asset';


const openModal = ref(false)
const editData = ref<Asset | null>(null)
const possibleProjects = ref([])

const auth = useAuthUserStore()
const searchQuery = reactive({
    item_name: '',
    model_number: '',
    classification: <number[]>[],
    status: <number[]>[],
    office_id: <number[]>[],
    user_id: <number[]>(auth.isAdmin ? [] : [auth.activeUser.id]),
    gl_number: '',
    confirm_status: <string[]>[]
})

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
const api = useApi()
const fetchCount = ref(0)

const selectedAssetIds = ref<number[]>([])
const exporting = ref(false)
const { userList, fetchAssetUsers } = useAsset()

const tagOptions = ref<{title: string, requiredData: string}[]>([
    {title: "ノートPC", requiredData: "メーカー・OS・バージョン"},
    {title: "デスクトップ", requiredData: "メーカー・OS・バージョン"},
    {title: "業務端末（本体）", requiredData: "メーカー"},
    {title: "SIM", requiredData: "電話番号"},
    {title: "事務所キー", requiredData: "キー番号"},
    {title: "ロッカーキー", requiredData: "キー番号"},
    {title: "ETCカード", requiredData: "カード番号"},
    {title: "ガソリンカード", requiredData: "カード番号・TFC番号"},
    {title: "レンタカーカード", requiredData: "カード番号"},
    {title: "ICカード", requiredData: "カード番号"},
    {title: "Times Business Card", requiredData: "カード番号"}
])
const allOffices = ref<Office[]>([])

onMounted(() => {

    getAssets()
    fetchAssetUsers([])
    getOffices()


})
const exportCSV = async() => {

    exporting.value = true
    const data = await api.get('/export_asset_csv', {
        ...searchQuery,
        mode: 'export',
    },{},
    {
        responseType: 'blob'
    })
    
    if(data){
        const url = window.URL.createObjectURL(new Blob([data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `物品${DateTime.now().toLocaleString(DateTime.DATETIME_SHORT)}.xlsx`) 
        document.body.appendChild(link)
        link.click()
        
    }
    setTimeout(() => {
        exporting.value = false
    }, 100);

}
const getOffices = async() => {
    const data = await api.get('/get_office_list')
    allOffices.value = data
}  
const getAssets = async(page?:number) => {
    const pageIndex = page ?? assetsData.value.current_page
        
    const response = await api.get(`/get_assets?page=${pageIndex}`, {        
        ...searchQuery     
    })
    assetsData.value = response
    fetchCount.value++
}
// const getPossibleProjects = async() => {

//     const response = await api.get('/get_possible_projects')
//     possibleProjects.value = response

// }
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}

const isExpanded = (asset: Asset) => {
    return (asset?.requests?.length ?? 0) > 0 || selectedAssetIds.value.includes(asset.id)
}

const toggleAssetDetail = (assetId: number) => {
    if (selectedAssetIds.value.includes(assetId)) {
        selectedAssetIds.value = selectedAssetIds.value.filter(id => id !== assetId)
        return
    }
    selectedAssetIds.value = [...selectedAssetIds.value, assetId]
}


const closeModal = (flag: boolean) => {
    openModal.value = false
    editData.value = null
    if(flag) getAssets()
}
watch(searchQuery, () => {
    getAssets()
}, { deep: true })
provide('getAssets', () => getAssets(assetsData.value.current_page))
</script>
<style lang="scss">
    .asset-header{
        display: flex;
        gap:20px;
    }
    .asset-table{
        background-color: var(--background-color);
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        table-layout: fixed;
        color: var(--primary-color);
    }
    .asset-table td{
        padding: 10px;
        font-size: 13px;
        border-bottom: solid thin var(--calendarBorder);
        vertical-align: middle;
        // border-bottom: 1px solid rgb(102, 102, 102);
        //border-right: 1px solid rgb(102, 102, 102);
    }

    // .asset-table tbody td{
    //     overflow: hidden;
    // }

    .asset-table thead td{
        padding: 16px 12px;
        font-size: 12px;
        font-weight: 700;
        background: var(--bg3);
        color: var(--primary-color);
        border-bottom: 1px solid var(--calendarBorder);
        overflow: visible;
    }

    .data-row{
        cursor: default;
    }

    .data-row:hover{
        background: var(--bg3);
    }

    .data-row.expanded{
        background: var(--selected-background);
    }

    .data-row.expanded td{
        border-bottom: none;
    }

    .td-center{
        text-align: center;
        vertical-align: middle;
    }

    .row-toggle{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: inherit;
    }

    .row-toggle:hover{
        background: var(--bg3);
    }

    .toggle-icon{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transform: rotate(-90deg);
        transition: transform 0.18s ease;
    }

    .toggle-icon.open{
        transform: rotate(90deg);
    }

    .detail-cell{
        padding: 0;
        background: var(--selected-background);
        border-bottom: 1px solid var(--calendarBorder);
    }

    .asset-accordion-body{
        padding: 12px;
    }

    .asset-table .asset-accordion-enter-active,
    .asset-table .asset-accordion-leave-active{
        transition: max-height 0.25s ease, opacity 0.2s ease;
        overflow: hidden;
    }

    .asset-table .asset-accordion-enter-from,
    .asset-table .asset-accordion-leave-to{
        max-height: 0;
        opacity: 0;
    }

    .asset-table .asset-accordion-enter-to,
    .asset-table .asset-accordion-leave-from{
        max-height: 1200px;
        opacity: 1;
    }
    // table td:first-child {
    //     border-left: 1px solid rgb(102, 102, 102);
    // }
    // thead td:first-child{
    //     border-left: 1px solid rgb(102, 102, 102);
    // }
    @media screen and (max-width: 959px) {
    .asset-table{
        thead{
            display: none;
        }
        tbody{
            tr{
                display: block;
                margin-bottom: 20px;
                td{
                    display: block;
                    border-left: solid thin var(--calendarBorder);
                    border-right: solid thin var(--calendarBorder);
                    border-bottom: none;
                    max-width: 100%;
                }
                border-bottom: solid thin var(--calendarBorder);
                border-top: solid thin var(--calendarBorder);
            }
            tr:first-of-type{
                margin-top: 20px;
            }
        }
    }   
    .h-cell{
        width: auto;
        text-align: start;
        border-right: none;
        border-left: none;
    }
    .inner-col{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }
    .additional-row{
        margin-top: -21px;
    }

    .detail-row{
        margin-top: -21px;
    }
    .inner-col{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }
}
</style>