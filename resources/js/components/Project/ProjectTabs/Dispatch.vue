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
                                    <th>名前</th>
                                    <th>甲（派遣先）</th>
                                    <th>乙（派遣元）</th>
                                    <th>派遣期間（自）</th>
                                    <th>派遣期間（至）</th>
                                    <th>詳細</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="dispatchData.length">
                                    <template v-for="dispatchRecord in dispatchData">                                
                                        <tr >
                                            <td>
                                                <div class="inner-col"><span class="mobile">ID</span>
                                                    <a class="jump-link flex items-center whitespace-nowrap" target="_blank" :href="`https://glowd-hldgs.cybozu.com/k/262/show#record=${dispatchRecord['レコード番号']}`">
                                                        <span>{{ dispatchRecord['レコード番号'] }}</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" height="15" fill="var(--link-color)" class="ml-[1px] mb-[1px]" viewBox="0 0 32 32">
                                                            <path d="M 18 5 L 18 7 L 23.5625 7 L 11.28125 19.28125 L 12.71875 20.71875 L 25 8.4375 L 25 14 L 27 14 L 27 5 Z M 5 9 L 5 27 L 23 27 L 23 14 L 21 16 L 21 25 L 7 25 L 7 11 L 16 11 L 18 9 Z"/>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                            <td><div class="inner-col"><span class="mobile">ステータス</span>{{ dispatchRecord['カスタマステータス'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">名前</span>{{ dispatchRecord['名前'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">甲（派遣先）</span>{{ dispatchRecord['派遣先会社名'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">乙（派遣元）</span>{{ dispatchRecord['派遣元会社名'] }}</div></td>
                                            <td><div class="inner-col"><span class="mobile">派遣期間（自）</span>{{ dispatchRecord['派遣期間_自'] ? DateTime.fromISO(dispatchRecord['派遣期間_自']).toLocaleString(DateTime.DATE_SHORT) : ''}}</div></td>
                                            <td><div class="inner-col"><span class="mobile">派遣期間（至）</span>{{ dispatchRecord['派遣期間_自'] ? DateTime.fromISO(dispatchRecord['派遣期間_至']).toLocaleString(DateTime.DATE_SHORT) : ''}}</div></td>
                                            <td>
                                                <label>
                                                    <input type="checkbox" class="hidden" :value="dispatchRecord['レコード番号']" v-model="selectedDispatches">
                                                    <span class="jump-link whitespace-nowrap">詳細</span>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr v-if="selectedDispatches.includes(dispatchRecord['レコード番号'])" class="additional-row bg-[var(--bg3)] ">
                                            <td colspan="9">
                                                <div>  
                                                    <div class="flex flex-col gap-[15px]">
                                                        <div>
                                                            派遣就業場所 : {{ dispatchRecord['派遣就業場所'] }}
                                                        </div>
                                                        <div>
                                                            事業所抵触日 : {{ dispatchRecord['事業所抵触日'] }}
                                                        </div>
                                                        <div>
                                                            組織（個人）単位抵触日 : {{ dispatchRecord['組織個人単位抵触日'] }}
                                                        </div>
                                                        <div>
                                                            派遣料金 : {{ dispatchRecord['派遣料金'] }}
                                                        </div>
                                                    </div>
                                                    <div class="mt-[20px]">
                                                        <div v-for="fileDataName in fileFields">
                                                            <div v-if="dispatchRecord[fileDataName] && Array.isArray(dispatchRecord[fileDataName]) && dispatchRecord[fileDataName].length">
                                                                <div class="mb-[20px]">
                                                                    <p>{{ `${fileDataName}${dispatchRecord[fileDataName].length}件` }}</p>
                                                                    <div class="flex flex-col gap-[10px]]">
                                                                        <a v-for="file in dispatchRecord[fileDataName]" class="jump-link text-[12px] p-[5px] bg-[var(--bg3)] w-fit" :href="kintoneFileUrlBuilder(file)" target="_blank">{{ file.name }} ({{ fileSizeParser(Number(file.size)) }})</a>
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
import { Project } from '@/interface/projectInterface';
import { fileSizeParser, kintoneFileUrlBuilder } from '@/utils/tools';
import axios from 'axios';
import { DateTime } from 'luxon';
import { inject, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
const setLoader = inject('setLoader') as (flag: boolean) => void
const props = defineProps<{
    selectedProject: Project;
    userList: any;
    hasPrivilage: boolean;
}>();
const fileFields = [
    '確認用データ',
    '原本データ'

]
const route = useRoute();
onMounted(() => {
    if(!props.hasPrivilage) return;
    setLoader(true);
    getDispatchData();
});
const selectedDispatches = ref<string[]>([]);
const dispatchData = ref<any[]>([]);
const fetchCount = ref(0);
const getDispatchData = async() => {
    const response = await axios.get('/get_dispatch_data', {params: {project_id: route.params.projectId}}).then(res => res.data);
    dispatchData.value = response;
    setLoader(false);
    fetchCount.value++;
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