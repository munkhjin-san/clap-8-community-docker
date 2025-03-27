<template>
    <div class="bg-[var(--background-color)] h-full relative">
        <div class="h-full overflow-y-auto">
            <div class="min-h-[calc(100%-50px)]">
                <table class="asset-table mx-[20px] mt-[20px] w-[calc(100%-40px)]">
                    <thead ref="assetHeader" style="background:var(--third-color);color:var(--background-color);position:sticky; top:0px;z-index: 1;">
                        <tr style="border:1px solid rgb(102, 102, 102);">
                            <td>GL番号</td>
                            <td>品名</td>
                            <td>型番</td>
                            <td>
                                <div class="relative">
                                    <div class="cursor-pointer" @click.stop="menu.setMenu({parent: 'assetUsersPick'})">使用者</div>
                                    <div v-if="activeMembers.length" class="flex flex-wrap mt-[5px]">
                                        <UserPanel v-for="member in activeMembers" disable-instant :user="member" size="15"/>
                                    </div>
                                    <Transition name="slidePop">
                                        <ProjectMemberSort
                                            v-if="menu.parent == 'assetUsersPick'" 
                                            id="assetUsersPick" 
                                            :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}"
                                            :members="[...selectedProject?.members ?? [], ...selectedProject?.manager ?? []]" 
                                            v-model:selected-users="userQuery"
                                            custom-place-holder="管理者検索"
                                        />
                                    </Transition>
                                </div>
                            </td>
                            <td>
                                <div class="relative">
                                    <div class="cursor-pointer" @click.stop="menu.setMenu({parent: 'classPick'})">分類</div>
                                    <div v-if="activeClasses.length" class="flex flex-wrap mt-[5px]">
                                        <div v-for="classification in activeClasses" class="px-[5px] text-[11px]">{{ classification.label }}</div>
                                    </div>
                                    <Transition name="slidePop">
                                        <div v-if="menu.parent == 'classPick'" id="classPick" class="absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                                            <button class="text-[11px]" @click.stop="classQuery = [], menu.close()">リセット</button>
                                            <div v-for="classification in AssetClass">
                                                <label class="cursor-pointer select-none whitespace-nowrap">
                                                    <input type="checkbox" name="class-selector" v-model="classQuery" :value="classification.value" class="hidden"/>
                                                    {{ classification.label }}
                                                </label>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                            </td>
                            <td>価値</td>
                            <td>
                                <div class="relative">
                                    <div class="cursor-pointer" @click.stop="menu.setMenu({parent: 'statusPick'})">ステータス</div>
                                    <div v-if="activeStatus.length" class="flex flex-wrap mt-[5px]">
                                        <div v-for="status in activeStatus" class="px-[5px] text-[11px]">{{ status.label }}</div>
                                    </div>
                                    <Transition name="slidePop">
                                        <div v-if="menu.parent == 'statusPick'" id="statusPick" class="absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                                            <button class="text-[11px]" @click.stop="statusQuery = [], menu.close()">リセット</button>
                                            <div v-for="statusData in AssetStatus">
                                                <label class="cursor-pointer select-none whitespace-nowrap">
                                                    <input type="checkbox" name="class-selector" v-model="statusQuery" :value="statusData.value" class="hidden"/>
                                                    {{ statusData.label }}
                                                </label>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                            </td>
                            <td>詳細</td>
                        </tr>
                    </thead>
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

        <FloatButton v-if="createAble" type="plus" @action="openModal = true"/>
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
import axios from 'axios';
import {  computed, inject, onMounted, provide, ref, useTemplateRef, watch, watchEffect } from 'vue';
import { useRoute,  } from 'vue-router';
import PostSearchBar from '../Post/PostSearchBar.vue';
import { DialogMethods, User } from '@/interface/globalInterface';
import { Asset,  } from '@/interface/assetInterface';
import FloatButton from '../Global/FloatButton.vue';
import AssetCreate from './AssetCreate.vue';
import AssetDetail from './AssetDetail.vue';
import AssetMovement from './AssetMovement.vue';
import PostSearchPager from '../Post/PostSearchPager.vue';
import { Project } from '@/interface/projectInterface';
import AssetClass from 'assets/AssetClass.json'
import AssetStatus from 'assets/AssetStatus.json'
import ProjectMemberSort from '../Project/ProjectMemberSort.vue';
import { useMenuStore } from '@/store/menu';
import UserPanel from '../Global/UserPanel.vue';
import { useAuthUserStore } from '@/store/auth';
const props = defineProps<{
    selectedProject?: Project;
    userList: any;
    mode?: string;
}>();

const route = useRoute()
const searchWindow = ref(false)
const openModal = ref(false)
const editData = ref<Asset | null>(null)
const selectedUser = ref<User[]>([])
const selectedClassification = ref(null)
const selectedStatus = ref(null)
const possibleProjects = ref([])
const possibleMembers = ref([])
const menu = useMenuStore()
const userQuery = ref<number[]>([]) 
const assetHeader = useTemplateRef('assetHeader')
const classQuery = ref<number[]>([])
const statusQuery = ref<number[]>([])
const auth = useAuthUserStore()
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

const fetchCount = ref(0)
const { confirm, info, notify } = inject('dialog') as DialogMethods

const selectedAssetIds = ref<number[]>([])
const setLoader = inject('setLoader') as (flag: boolean) => void

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
})
const createAble = computed(() => {
    const privilage = props.mode === 'admin' || props.mode === 'partner' 
    const allMembers = [...props.selectedProject?.members ?? [], ...props.selectedProject?.manager ?? []]
    return privilage || allMembers.some(ob => ob.id === auth.activeUser.id)
})

const getAssets = async(page?:number) => {
    const pageIndex = page ?? assetsData.value.current_page
    try {
        const params = {
            memberId: selectedUser.value.map(ob => ob.id),
            projectId: props.selectedProject?.id,
            classification: selectedClassification.value,
            status: selectedStatus.value,
            user_ids: userQuery.value,
            class_ids: classQuery.value,
            status_ids: statusQuery.value,
            mode: props.mode
        }
        const response = await axios.get(`/get_assets?page=${pageIndex}`, {params})
        assetsData.value = response.data
        fetchCount.value++
        if (typeof setLoader === 'function') {
            setLoader(false)
        }
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
const getPossibleProjects = async() => {
    try {
        const response = await axios.get('/get_possible_projects')
        possibleProjects.value = response.data
    } catch (e) {

    } 
}
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}


const closeModal = (flag: boolean) => {
    openModal.value = false
    if(flag) getAssets()
}
const activeMembers = computed(() => {
    const allMembers = [...props.selectedProject?.members ?? [], ...props.selectedProject?.manager ?? []]
    return allMembers.filter(ob => userQuery.value.includes(ob.id))
})
const activeClasses = computed(() => {
    return AssetClass.filter(ob => classQuery.value.includes(ob.value))
})
const activeStatus = computed(() => {
    return AssetStatus.filter(ob => statusQuery.value.includes(ob.value))
})
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