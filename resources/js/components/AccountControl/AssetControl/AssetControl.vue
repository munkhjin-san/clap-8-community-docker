<template>
    <div class="admin-window">
        <div class="admin-sub-c-bar">
            <PostSearchBar 
                custom-place-holder="物品検索"
                className="newChatMemberSearch" 
                style="width:auto;min-width: 300px;"
                @search-start="(word) => {keywords = word}"
            />
        </div>
        <FloatButton @action="openModal = true" type="plus"/> 
        <AssetCreate 
            v-if="openModal" 
            @close="openModal = false, editData = null"
            :statuses="statuses"
            :classifications="classifications"
            :edit-data="editData"
            :all-members="allMembers"
            :all-projects="allProjects"
            @getAssets="getAssets"
        />
        <div>
            <table class="admin-asset-table">
                <thead style="background:#363636;color:#fff;position:sticky; top:0px;">
                    <tr style="border:1px solid rgb(102, 102, 102);">
                        <td class="admin-asset-data">GL番号</td>
                        <td class="admin-asset-data">品名</td>
                        <td class="admin-asset-data">型番</td>
                        <td class="admin-asset-data">使用プロジェクト</td>
                        <td class="admin-asset-data">使用者</td>
                        <td class="admin-asset-data">分類</td>
                        <td class="admin-asset-data">価値</td>
                        <td class="admin-asset-data">ステータス</td>
                        <td class="admin-asset-data">アクション</td>
                    </tr>
                    
                </thead>
                <tbody>
                    <tr v-for="(item, index) in searchAssets" :key="index">
                        <td>{{ `GL${padNumber(item.id)}` }}</td>
                        <td>{{ item.item_name }}</td>
                        <td>{{ item.model_number }}</td>
                        <td>
                            <div class="leading-normal" v-for="project in item.projects">
                                <p>{{ project.name }}</p>
                            </div>
                           
                        </td>
                        <td>
                            <div class="leading-normal" v-for="user in item.users">
                                <p>{{ user.name }}</p>
                            </div>
                        </td>
                        <td>{{ classifications.find(ob => ob.value === item.classification)?.label }}</td>
                        <td>{{ item.value }}</td>
                        <td>{{ statuses.find(ob => ob.value === item.status)?.label }}</td>
                        <td>
                            <div class="flex gap-[10px]">
                                <CommandButton 
                                    :buttons="[
                                        { title: '変更', action: () => editAsset(item) },
                                        { title: '削除', action: () => deleteAsset(item.id) }
                                    ]"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script lang="ts" setup>
import FloatButton from '@/components/Global/FloatButton.vue';
import { computed, onMounted, ref, inject, provide } from 'vue';
import AssetCreate from './AssetCreate.vue';
import axios from 'axios';
import { Asset } from '@/interface/assetInterface';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import CommandButton from '@/components/Global/CommandButton.vue'
import { DialogMethods } from '@/interface/globalInterface'
const openModal = ref(false)
const assets = ref<Asset[]>([])
const statuses = [
    { value: 1, label: '使用中' },
    { value: 2, label: '故障' },
    { value: 3, label: '廃棄' },
    { value: 4, label: '保管' },
    { value: 5, label: '移動' },
    { value: 6, label: '返却' }
]
const classifications = [
    { value: 1, label: '資産' },
    { value: 2, label: '消耗品' },
    { value: 3, label: '重要資産' }
]
const { confirm, info } = inject('dialog') as DialogMethods
const keywords = ref('')
const editData = ref<Asset | null>(null)
const allProjects = ref([])
const allMembers = ref([])
const searchAssets = computed(() => {
    if (!keywords.value) return assets.value;

    const lowSearch = keywords.value.toLowerCase();

    return assets.value.filter(asset => {
        const formattedId = `GL${padNumber(asset.id)}`;
        
        if (formattedId.toLowerCase().includes(lowSearch)) {
            return true;
        }

        const statusLabel = statuses.find(ob => ob.value === asset.status)?.label || ""
        const classificationLabel = classifications.find(ob => ob.value === asset.classification)?.label || ""

        if (statusLabel.toLowerCase().includes(lowSearch) || classificationLabel.toLowerCase().includes(lowSearch)) {
            return true
        }

        const deepSearch = (obj) => {
            if (typeof obj === 'string' || typeof obj === 'number') {
                return String(obj).toLowerCase().includes(lowSearch);
            } 
            else if (Array.isArray(obj)) {
                return obj.some(item => deepSearch(item));
            } 
            else if (typeof obj === 'object' && obj !== null) {
                return Object.values(obj).some(val => deepSearch(val));
            }
            return false;
        };

        return deepSearch(asset);
    });
});

const getAssets = async() => {
    try {
        const response = await axios.get('/get_control_assets')
        assets.value = response.data
    } catch (e) {

    }
}
const padNumber = (num: number | null) => {
    return num?.toString().padStart(5, "0")
}
const editAsset = (asset: Asset) => {
    editData.value = asset
    openModal.value = true
}
const deleteAsset = async(id: number | null) => {
    const answer = await confirm('物品を削除しますよろしいでか？')
    if (!answer) return
    try {
        await axios.delete(`/delete_asset?id=${id}`)
        info('削除しました。')
    } catch (e) {

    }
}
const getProjects = async() => {
    try {
        const response = await axios.get('/get_possible_projects')
        allProjects.value = response.data
    } catch (e) {

    }
}
const getMembers = async() => {
    try {
        const response = await axios.get('/get_possible_members')
        allMembers.value = response.data
    } catch (e) {

    }
}
onMounted(() => {
    getAssets()
    getProjects()
    getMembers()
})
provide('padNumber', padNumber)
</script>
<style lang="scss" scoped>
    .admin-asset-data{
        font-size: 13px;
        vertical-align: middle;
    }
    .admin-asset-header{

        display: flex;
        gap:20px;
    }
    .admin-asset-table{
        background-color: var(--background-color);
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
    }
    .admin-asset-table td{
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