<template>
    <div class="h-full relative">
        <div class="bg-[var(--background-color)] h-full">   
            <div v-if="!hasPrivilage" class="h-full flex items-center justify-center">
                <p class="text-[gray]">権限がありません</p>         
            </div>       
            <div v-else class="h-full relative">
                <div class="bg-[var(--background-color)] !h-full">
                    <div class="project-table-container" style="border: none;">              
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ステータス</th>
                                    <th>案件名</th>
                                    <th>担当者</th>
                                    <th>取引先</th>
                                    <th>契約期間</th>                                    
                                    <th>詳細</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="contractsData.length">
                                    <template v-for="contract in contractsData">                                
                                        <tr >
                                            <td>
                                                <div class="inner-col"><span class="mobile">ID</span>
                                                    <a class="jump-link flex items-center whitespace-nowrap" target="_blank" :href="`https://glowd-hldgs.cybozu.com/k/138/show#record=${contract['レコード番号']}`">
                                                        <span>{{ contract['レコード番号'] }}</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" height="15" fill="var(--link-color)" class="ml-[1px] mb-[1px]" viewBox="0 0 32 32">
                                                            <path d="M 18 5 L 18 7 L 23.5625 7 L 11.28125 19.28125 L 12.71875 20.71875 L 25 8.4375 L 25 14 L 27 14 L 27 5 Z M 5 9 L 5 27 L 23 27 L 23 14 L 21 16 L 21 25 L 7 25 L 7 11 L 16 11 L 18 9 Z"/>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                            <td><div class="inner-col"><span class="mobile">ステータス</span>{{ contract['ステータス'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">契約案件名</span>{{ contract['契約案件名'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">案件担当者</span>{{ contract['案件担当者'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">取引先</span>{{ contract['取引先'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">契約期間開始日</span>{{ contract['契約期間開始日'] && contract['契約期間終了日'] ? 
                                                    `${DateTime.fromISO(contract['契約期間開始日']).toLocaleString(DateTime.DATE_SHORT)} ~ ${DateTime.fromISO(contract['契約期間終了日']).toLocaleString(DateTime.DATE_SHORT)}` : ''}}
                                                </div>
                                            </td>                                            
                                            <td>
                                                <label>
                                                    <input type="checkbox" class="hidden" :value="contract['レコード番号']" v-model="selectedContracts">
                                                    <span class="jump-link whitespace-nowrap">詳細</span>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr v-if="selectedContracts.includes(contract['レコード番号'])" class="additional-row bg-[var(--bg3)]">
                                            <td colspan="7">
                                                <div>  
                                                    <div class="flex flex-wrap gap-[20px]">
                                                        <div>
                                                            <p>甲</p>
                                                            <div>{{ contract['甲会社名'] }}</div>
                                                            <div>{{ `${contract['甲役職']} ${contract['甲代表者名']}` }}</div>
                                                        </div>
                                                        <div>
                                                            <p>乙</p>
                                                            <div>{{ contract['乙会社名'] }}</div>
                                                            <div>{{ `${contract['乙役職']} ${contract['乙代表者名']}` }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-[20px]">
                                                        <div class="mb-[20px]" v-if="contract['specs'] && Array.isArray(contract['specs']) && contract['specs'].length">                                                    
                                                            <p>仕様書{{ contract['specs'].length }}件</p>
                                                            <div v-for="spec in contract['specs']">
                                                                <div class="flex items-center">
                                                                    <span>ID: </span>
                                                                    <a class="jump-link flex items-center whitespace-nowrap ml-[5px]" target="_blank" :href="`https://glowd-hldgs.cybozu.com/k/156/show#record=${spec['$id']}`">
                                                                        <span>{{ spec['$id'] }}</span>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" height="15" fill="var(--link-color)" class="ml-[1px] mb-[1px]" viewBox="0 0 32 32">
                                                                            <path d="M 18 5 L 18 7 L 23.5625 7 L 11.28125 19.28125 L 12.71875 20.71875 L 25 8.4375 L 25 14 L 27 14 L 27 5 Z M 5 9 L 5 27 L 23 27 L 23 14 L 21 16 L 21 25 L 7 25 L 7 11 L 16 11 L 18 9 Z"/>
                                                                        </svg>
                                                                    </a>
                                                                </div>
                                                                <div class="flex flex-col gap-[15px]">
                                                                    <div>
                                                                        委託料合計 : {{ spec['委託料合計'] }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div v-for="fileDataName in columnTypes.file">
                                                            <div v-if="contract[fileDataName] && Array.isArray(contract[fileDataName]) && contract[fileDataName].length">
                                                                <div class="mb-[20px]">
                                                                    <p>{{ `${fileDataName}${contract[fileDataName].length}件` }}</p>
                                                                    <div class="flex flex-col gap-[10px]]">
                                                                        <a v-for="file in contract[fileDataName]" class="jump-link text-[12px] p-[5px] bg-[var(--bg3)] w-fit" :href="kintoneFileUrlBuilder(file)" target="_blank">{{ file.name }} ({{ fileSizeParser(Number(file.size)) }})</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
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
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { DialogMethods } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import axios from 'axios';
import { DateTime } from 'luxon';
import { inject, onMounted, ref } from 'vue';
import { kintoneFileUrlBuilder, fileSizeParser } from '@/utils/tools';
const props = defineProps<{
    selectedProject: Project;
    userList: any;
    hasPrivilage: boolean;
}>();
const setLoader = inject('setLoader') as (flag: boolean) => void
const { notify, info, confirm } = inject('dialog') as DialogMethods
const contractsData = ref<any[]>([])
const columnTypes = ref<{
    array: string[];
    date: string[];
    html: string[];
    file: string[];
    action: string[];
}>({
    array: [],
    date: [],
    html: [],
    file: [],
    action: []
})
const selectedContracts = ref<any[]>([])
const tableColumns = ref<string[]>([])
const fetchCount = ref(0)
onMounted(async() => {
    if(!props.hasPrivilage) return;
    setLoader(true)
    await getContracts();
    setTimeout(() => {
        setLoader(false)
    }, 100);
})

const getContracts = async() => {
    const response = await axios.get('/get_contracts', {params: {project_name: props.selectedProject.name}}).then(res => res.data);
    contractsData.value = response.contracts
    columnTypes.value = response.column_types
    tableColumns.value = response.table_columns
    fetchCount.value++
}
</script>
<style scoped lang="scss">
table{
    margin: 0 20px;
    width: calc(100% - 40px);
    border-collapse: separate;
    font-size: 13px;
    line-height: 1.5;
    thead{
        th{
            padding: 10px;
            border-bottom: 1px solid var(--calendarBorder);
            font-weight: 500;
            text-align: left;
        }
        position: sticky;
        top: 0;
        background-color: var(--background-color);
        z-index: 1;
    }
    tbody{
        tr{
            td{
                padding: 10px;
                border-bottom: 1px solid var(--calendarBorder);
                font-weight: 400;
                text-align: left;
                border-left: none;
                span{
                    display: block;
                }
            }
        }
    }
}
@media screen and (max-width: 959px) {
    table{
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