<template>
    <div>
        <div class="post-header justify-between">
            <div class="cursor-pointer" style="display: flex;align-items: center;height: 50px;position: sticky;top: 0;background: var(--bg2);">
                <div @click="router.go(-1)" style="height: 50px;width: 50px;min-width:50px;display: flex;justify-content: center;align-items: center;fill:var(--primary-color)">
                    <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                
                <div class="project-nav-bar">
                    <div>
                        <span class="project-path">物品</span>
                    </div>
                </div>
            </div> 
            <div class="flex gap-[10px] mr-5">
                <Categorizer 
                    path="get_possible_members"
                    type="メンバー"
                    v-model="selectedUser"
                />
                <Categorizer 
                    path="get_possible_projects"
                    type="プロジェクト"
                    v-model="selectedProject"
                />
                <Categorizer
                    path="" 
                    type="分類"
                    :selectable-options="classifications"
                    v-model="selectedClassification"
                />
                <Categorizer 
                    path=""
                    type="ステータス"
                    :selectable-options="statuses"
                    v-model="selectedStatus"
                />
            </div>
            
        </div>
        
    </div>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Categorizer from '../Global/Categorizer.vue';
import { useAuthUserStore } from '@/store/auth';
const props = defineProps(['selectedProject'])
const route = useRoute()
const router = useRouter()
const statuses = [
    { id: 1, name: '使用中' },
    { id: 2, name: '故障' },
    { id: 3, name: '廃棄' },
    { id: 4, name: '保管' },
    { id: 5, name: '移動' },
    { id: 6, name: '返却' }
]
const classifications = [
    { id: 1, name: '資産' },
    { id: 2, name: '消耗品' },
    { id: 3, name: '重要資産' },
]
const auth = useAuthUserStore()
const projectId = computed(() => {
    return Number(route.query?.project_id)
})
const selectedUser = ref(auth.id ?? null)
const selectedProject = ref(projectId.value ?? null)
const selectedClassification = ref(null)
const selectedStatus = ref(null)
onMounted(() => {
    getAssets()
})

watch([selectedUser, selectedProject, selectedClassification, selectedStatus], () => {
    console.log('user: ', selectedUser.value)
    console.log('project: ', selectedProject.value)
    console.log('classification: ', selectedClassification.value)
    console.log('status: ', selectedStatus.value)
})
const getAssets = async() => {
    try {
        const params = {
            memberId: selectedUser.value,
            projectId: selectedProject.value,
            classification: selectedClassification.value,
            status: selectedStatus.value
        }
        const response = await axios.get('/get_assets', {params: {params}})
    } catch (e) {

    }
}
</script>