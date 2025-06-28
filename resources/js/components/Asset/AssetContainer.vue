<template>
    <div class="bg-[var(--background-color)] h-full relative">
        <div class="h-full overflow-y-auto">
            <div class="min-h-[calc(100%-50px)]">
                <table class="asset-table mx-[20px] mt-[20px] w-[calc(100%-40px)]">
                    <AssetTableHeader 
                        :columns="['GL番号', '品名', '型番', '使用者', '分類', '価値', 'ステータス']"
                        :projects="[]" 
                        :users="assetUsers"
                        :offices="[]"
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
                        <template v-if="assetsData.data.length">                    
                            <template v-for="asset in assetsData.data">
                                <tr>
                                    <td><div class="inner-col"><span class="mobile">GL番号</span>{{ `GL${padNumber(asset.id)}` }}</div></td>
                                    <td class="max-w-[150px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">品名</span>{{ asset.item_name }}</div></td>
                                    <td class="max-w-[150px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">型番</span>{{ asset.model_number }}</div></td>
                                    <td>
                                        <div class="inner-col"><span class="mobile">使用者</span>
                                            <div v-if="asset.current_user">
                                                <div class="leading-normal">
                                                    <p>{{ asset.current_user.name }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><div class="inner-col"><span class="mobile">分類</span>{{ AssetClass.find(ob => ob.value === asset.classification)?.label }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">価値</span>{{ asset.value }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">ステータス</span>{{ asset.requests.length ? '移動中' : AssetStatus.find(ob => ob.value === asset.status)?.label }}</div></td>
                                    <td>
                                        <label class="cursor-pointer select-none jump-link">
                                            <input type="checkbox" v-model="selectedAssetIds" :value="asset.id" class="hidden"/>
                                            詳細
                                        </label>
                                        
                                    </td>
                                </tr>
                                <tr class="additional-row" v-if="asset?.requests && asset.requests.length || selectedAssetIds.includes(asset.id)">
                                    <td colspan="9">
                                        <div v-if="asset?.requests && asset.requests.length" class="bg-[var(--bg3)]">
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
                                                @reload="getAssets(assetsData.current_page)"
                                            />
                                        </div>
                                    </td>

                                </tr>
                            </template>
                        </template>
                        <template v-else-if="fetchCount > 0">
                            <tr>
                                <td colspan="9" class="!text-center">データがありません</td>
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
                    @setNavi="(index) => getAssets(assetsData.current_page + index)"
                    @setActivePage="(index) => getAssets(index)"/>
            </div>
        </div>

        <FloatButton v-if="createAble" type="plus" @action="openModal = true">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>  
        <Transition name="modalFade">
            <AssetCreate 
                v-if="openModal" 
                :edit-data="editData"
                :all-members="possibleMembers"
                :all-projects="possibleProjects"
                :mode="mode"
                @close="closeModal"
            />
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import {  computed, inject, onMounted, provide, reactive, ref, watch } from 'vue';
import { Asset,  } from '@/interface/assetInterface';
import FloatButton from '../Global/FloatButton.vue';
import AssetCreate from './AssetCreate.vue';
import AssetDetail from './AssetDetail.vue';
import AssetMovement from './AssetMovement.vue';
import PostSearchPager from '../Post/PostSearchPager.vue';
import { Project } from '@/interface/projectInterface';
import AssetClass from 'assets/AssetClass.json'
import AssetStatus from 'assets/AssetStatus.json'
import { useAuthUserStore } from '@/store/auth';
import AssetTableHeader from '../AccountControl/AssetControl/AssetTableHeader.vue';
import { useRoute } from 'vue-router';
import { User } from '@/interface/globalInterface';
import AddIcon from '../Form/AddIcon.vue';
import { useApi } from '@/composables/api';
const props = defineProps<{
    selectedProject?: Project;
    userList: any;
    mode?: string;
}>();


const openModal = ref(false)
const editData = ref<Asset | null>(null)
const possibleProjects = ref([])
const possibleMembers = ref([])
const userQuery = ref<number[]>([]) 
const classQuery = ref<number[]>([])
const statusQuery = ref<number[]>([])
const auth = useAuthUserStore()
const route = useRoute()
const searchQuery = reactive({
    item_name: '',
    model_number: '',
    classification: <number[]>[],
    status: <number[]>[],
    office_id: <number[]>[],
    user_id: <number[]>[],
    project_id: <number[]>[Number(route.params.projectId)],
    gl_number: '',
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
const setLoader = inject('setLoader') as (flag: boolean) => void
const assetUsers = ref<User[]>([])


watch([userQuery, classQuery, statusQuery], () => {
    getAssets()
})
onMounted(() => {
    //check setLoader is function and injected properly


    if (typeof setLoader === 'function') {
        setLoader(true);
    } 
    getAssets()
    getPossibleMembers()
    getPossibleProjects()
    getAssetUsers()



})
const getAssetUsers = async() => {

    const response = await api.get('/get_asset_users', {     
        project_id: props.selectedProject?.id,
        mode: props.mode,           
    })
    assetUsers.value = response
}
const createAble = computed(() => {
    const privilage = props.mode === 'admin' || props.mode === 'partner' 
    const allMembers = [...props.selectedProject?.members ?? [], ...props.selectedProject?.manager ?? []]
    return privilage || allMembers.some(ob => ob.id === auth.activeUser.id)
})

const getAssets = async(page?:number) => {
    const pageIndex = page ?? assetsData.value.current_page

        
    const response = await api.get(`/get_assets?page=${pageIndex}`, {        
        ...searchQuery,
        mode: props.mode,       
    })
    assetsData.value = response
    fetchCount.value++
    if (typeof setLoader === 'function') {
        setLoader(false)
    }

}
const getPossibleMembers = async() => { 
    const response = await api.get('/get_possible_members')
    possibleMembers.value = response
}
const getPossibleProjects = async() => {

    const response = await api.get('/get_possible_projects')
    possibleProjects.value = response

}
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}


const closeModal = (flag: boolean) => {
    openModal.value = false
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
        border-collapse: separate; 
        border-spacing: 0;
        color: var(--primary-color);
    }
    .asset-table td{
        padding: 10px;
        font-size: 13px;
        border-bottom: solid thin var(--calendarBorder);
        // border-bottom: 1px solid rgb(102, 102, 102);
        //border-right: 1px solid rgb(102, 102, 102);
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
    .inner-col{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }
}
</style>