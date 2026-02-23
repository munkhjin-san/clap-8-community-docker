<template>
<thead ref="assetHeader">
    <tr>
        <td class="relative">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'idPick'})">                                        
                    GL番号
                    <Filter class="filter-icon" size="12"/>
                </div>
               
                <div v-if="idQuery" class="mt-2 text-[12px] text-[gray] italic">”{{ idQuery }}”</div>
            </div>
            <Transition name="slidePop">
                <div v-if="menu.parent == 'idPick'" id="idPick" class="shadow-me absolute bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px] max-h-[40vh] overflow-auto min-w-[150px] left-0" :style="{'top': `${(assetHeader?.offsetHeight ?? 30) - 4}px`}">
                    <input name="model-selector" type="text" class="custom-o-input" v-model="idQuery" placeholder="GL番号検索（GLなし）"/>
                </div>
            </Transition>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'namePick'})">                                        
                    物品名
                    <Filter class="filter-icon" size="12"/>
                </div>                
                <div v-if="nameQuery" class="mt-2 text-[12px] text-[gray] italic">”{{ nameQuery }}”</div>
            </div>
            <Transition name="slidePop">
                <div v-if="menu.parent == 'namePick'" id="namePick" class="shadow-me absolute left-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px] max-h-[40vh] min-w-[150px] overflow-auto" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                    <input name="model-selector" type="text" class="custom-o-input" v-model="nameQuery" placeholder="品名検索"/>
                </div>
            </Transition>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'modelPick'})">                                        
                    型番
                    <Filter class="filter-icon" size="12"/>
                </div>                
                <div v-if="modelQuery" class="mt-2 text-[12px] text-[gray] italic">”{{ modelQuery }}”</div>
            </div>
            <Transition name="slidePop">
                <div v-if="menu.parent == 'modelPick'" id="modelPick" class="shadow-me absolute left-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px] min-w-[150px] max-h-[40vh] overflow-auto" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                    <input name="model-selector" type="text" class="custom-o-input" v-model="modelQuery" placeholder="型番検索"/>
                </div>
            </Transition>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'assetUsersPick'})">                                        
                    使用者
                    <Filter class="filter-icon" size="12"/>
                </div>           
                <div v-if="userQuery.length">
                    <div class="flex mt-2 items-center flex-wrap">
                        <UserPanel :user="user" disable-instant size="15" v-for="user in selectedUsers.slice(0, 5)"/>
                        <span v-if="selectedUsers.length > 5">...({{ selectedUsers.length - 5 }})</span>
                    </div>
                </div>
            </div>
            <Transition name="slidePop">
                <ProjectMemberSort
                    v-if="menu.parent == 'assetUsersPick'" 
                    id="assetUsersPick" 
                    :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}"
                    :members="userList" 
                    v-model:selected-users="userQuery"
                    custom-place-holder="使用者検索"
                />
            </Transition>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'statusPick'})">                                        
                    ステータス
                    <Filter class="filter-icon" size="12"/>                    
                </div>    
                <div v-if="statusQuery.length">
                    <div class="mt-2 text-[12px] text-[gray] italic">
                        {{ statusQuery.map(status => AssetStatus.find(s => s.value == status)?.label).join('、') }}
                    </div>
                </div> 
                           
            </div>
            <Transition name="slidePop">
                <div v-if="menu.parent == 'statusPick'" id="statusPick" class="shadow-me absolute left-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                    <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="statusQuery = [], menu.close()">リセット</button>
                    <div v-for="statusData in AssetStatus">
                        <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                            <input type="checkbox" name="class-selector" v-model="statusQuery" :value="statusData.value" class="custom-f-checkbox"/>
                            {{ statusData.label }}
                        </label>
                    </div>
                </div>
            </Transition>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'officePick'})">                                        
                    使用場所
                    <Filter class="filter-icon" size="12"/>
                </div>
                <div v-if="officeQuery.length">
                    <div class="mt-2 text-[12px] text-[gray] italic">
                        {{ officeQuery.map(officeId => offices.find(o => o.id === officeId)?.name).join('、') }}
                    </div>
                </div>                
            </div>
            <Transition name="slidePop">
                <div v-if="menu.parent == 'officePick'" id="officePick" class="shadow-me absolute left-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                    <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="officeQuery = [], menu.close()">リセット</button>
                    <div v-for="officeData in offices">
                        <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                            <input type="checkbox" class="custom-f-checkbox" name="office-selector" v-model="officeQuery" :value="officeData.id"/>
                            {{ officeData.name }}
                        </label>
                    </div>
                </div>
            </Transition>
        </td>
        <td class="relative">
            <div class="relative">
                <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'confirmPick'})">                                        
                    確認状況
                    <Filter class="filter-icon" size="12"/>
                </div>   
                <div v-if="confirmQuery.length">
                    <div class="mt-2 text-[12px] text-[gray] italic">
                        {{ confirmQuery.map(status => status === 'confirmed' ? '確認済み' : '未確認').join('、') }}
                    </div>
                </div>             
            </div>
            <Transition name="slidePop">
                <div v-if="menu.parent == 'confirmPick'" id="confirmPick" class="shadow-me absolute left-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[11px] p-[10px]" :style="{'top': `${(assetHeader?.clientHeight ?? 30) - 4}px`}">
                    <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="confirmQuery = [], menu.close()">リセット</button>
                    <div v-for="statusData in [{ id: 'confirmed', name: '確認済み' }, { id: 'unconfirmed', name: '未確認' }]">
                        <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                            <input type="checkbox" class="custom-f-checkbox" name="confirm-selector" v-model="confirmQuery" :value="statusData.id"/>
                            {{ statusData.name }}
                        </label>
                    </div>
                </div>
            </Transition>
        </td>
        <td class="relative text-center">詳細</td>
    </tr>
</thead>
</template>
<script setup lang="ts">
import ProjectMemberSort from '@/components/Project/ProjectMemberSort.vue';
import { Office, User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import { useMenuStore } from '@/store/menu';
import { computed, ref, useTemplateRef } from 'vue';
import AssetClass from 'assets/AssetClass.json'
import AssetStatus from 'assets/AssetStatus.json'
import Filter from '@/components/Icons/Filter.vue';
import 'styles/customForm.css'
import UserPanel from '@/components/Global/UserPanel.vue';
import { useAsset } from '@/composables/asset';
const props = defineProps<{
    offices: Office[]
}>()

const menu = useMenuStore()
const assetHeader = useTemplateRef('assetHeader')

const userQuery = defineModel<number[]>('user_id', {required: true})
const classQuery = defineModel<number[]>('classification', {required: true})
const statusQuery = defineModel<number[]>('status', {required: true})
const officeQuery = defineModel<number[]>('office_id', {required: true})
const modelQuery = defineModel<string>('model_number', {required: true})
const nameQuery = defineModel<string>('item_name', {required: true})
const idQuery = defineModel<string>('gl_number', {required: true})
const confirmQuery = defineModel<string[]>('confirm_status', {required: true})
const projectNameSearch = ref('')
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