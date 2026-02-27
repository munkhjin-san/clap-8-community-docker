<template>
<thead ref="assetHeader">
    <tr>
        <td class="relative">
            <div class="relative">
                <div class="flex items-center gap-[5px] h-p">                                        
                    GL番号
                </div>
               
                <div v-if="idQuery" class="mt-2 text-[12px] text-[gray] italic">”{{ idQuery }}”</div>
            </div>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="flex items-center gap-[5px] h-p">                                        
                    物品名
                </div>                
                <div v-if="nameQuery" class="mt-2 text-[12px] text-[gray] italic">”{{ nameQuery }}”</div>
            </div>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="flex items-center gap-[5px] h-p">                                        
                    型番
                </div>                
                <div v-if="modelQuery" class="mt-2 text-[12px] text-[gray] italic">”{{ modelQuery }}”</div>
            </div>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="flex items-center gap-[5px] h-p">                                        
                    使用者
                </div>           
                <div v-if="userQuery.length">
                    <div class="flex mt-2 items-center flex-wrap">
                        <UserPanel :user="user" disable-instant size="15" v-for="user in selectedUsers.slice(0, 5)"/>
                        <span v-if="selectedUsers.length > 5">...({{ selectedUsers.length - 5 }})</span>
                    </div>
                </div>
            </div>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="flex items-center gap-[5px] h-p">                                        
                    ステータス                  
                </div>    
                <div v-if="statusQuery.length">
                    <div class="mt-2 text-[12px] text-[gray] italic">
                        {{ statusQuery.map(status => AssetStatus.find(s => s.value == status)?.label).join('、') }}
                    </div>
                </div> 
                           
            </div>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="flex items-center gap-[5px] h-p">                                        
                    使用場所
                </div>
                <div v-if="officeQuery.length">
                    <div class="mt-2 text-[12px] text-[gray] italic">
                        {{ officeQuery.map(officeId => offices.find(o => o.id === officeId)?.name).join('、') }}
                    </div>
                </div>                
            </div>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="flex items-center gap-[5px] h-p">                                        
                    確認状況
                </div>   
                <div v-if="confirmQuery.length">
                    <div class="mt-2 text-[12px] text-[gray] italic">
                        {{ confirmQuery.map(status => status === 'confirmed' ? '確認済み' : '未確認').join('、') }}
                    </div>
                </div>             
            </div>
        </td>
        <td class="relative text-center">詳細</td>
    </tr>
</thead>
</template>
<script setup lang="ts">
import { Office, User } from '@/interface/globalInterface';
import { computed } from 'vue';
import AssetStatus from 'assets/AssetStatus.json'
import 'styles/customForm.css'
import UserPanel from '@/components/Global/UserPanel.vue';
import { useAsset } from '@/composables/asset';
const props = defineProps<{
    offices: Office[]
}>()


const userQuery = defineModel<number[]>('user_id', {required: true})
const statusQuery = defineModel<number[]>('status', {required: true})
const officeQuery = defineModel<number[]>('office_id', {required: true})
const modelQuery = defineModel<string>('model_number', {required: true})
const nameQuery = defineModel<string>('item_name', {required: true})
const idQuery = defineModel<string>('gl_number', {required: true})
const confirmQuery = defineModel<string[]>('confirm_status', {required: true})
const { userList } = useAsset()

const selectedUsers = computed(() => {
    return userList.value.filter(user =>  userQuery.value.includes(user.id))
})
</script>
<style scoped>
    thead {
        background: var(--bg3);
        color: var(--primary-color);
        position: sticky;
        top: 50px;
        z-index: 1;
    }
    td {
        padding: 16px 12px;
        font-size: 12px;
        font-weight: 700;
        border-bottom: 1px solid var(--calendarBorder);
    }
    .filter-icon {
        fill: var(--primary-color) !important;
        opacity: 1;
        margin-top: 3px;
    }
    /* .h-p:hover .filter-icon {
        fill: var(--primary-color);
        opacity: 1;
    } */
    
</style>