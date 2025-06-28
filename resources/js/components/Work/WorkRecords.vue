<template>
    <div class="records-wrapper" ref="wrapper" :style="{height: `calc(100% - ${headerHeight.value}px)`}">
        
        <div v-if="!records.length" class="absolute-div">
            メンバーを選択してください。
        </div>  
        <v-data-table-virtual
            :headers="headers"
            :items="records"
            height="100%"
            :loading="loading == 0"
            :hide-no-data="true"
            item-value="name"
            id="dt-responsive-table"
            class="p-datatable-table"
            dense
            disable-sort
        >
            <template v-slot:loading>
                <Transition name="modalFade">
                    <div class="work-loader">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div> 
                </Transition>
            </template>
            <template v-slot:item="{ item }">
                <WorkRecordRow 
                    :item="item" 
                    :hasHeader="hasHeader" 
                    @callModal="item => tempItem = item"
                    @procedureStart="procedureStart"
                    :holidays="holidays"
                    :wrapper="wrapper"
                    @workRecordRowCreated="workRecordRowCreated"
                />                
            </template>
            <template v-slot:body.append>
                <WorkRecordTotal
                    v-for="(data, index) in monthAverage"  
                    :data="data"
                    :dIndex="index"
                    :hasHeader="hasHeader"
                    @csvGenerate="csvGenerate"
                />
            </template>            
                
            
        </v-data-table-virtual>
        <Transition name="modalFade">
            <WorkProcedureButtons 
                :item="tempItem"
                @dailyButtons="dailyButtons"
                @closeModal="tempItem = null"
                @reload="emit('reload')"
                v-if="tempItem"
            />
        </Transition>
        <Transition name="modalFade">
            <OverTimeRequest v-if="overTimeRequestData" :data="overTimeRequestData" @close="closeOverTimeRequest"/>
        </Transition>
        
    </div>
</template>
<script setup>
import { VDataTableVirtual } from 'vuetify/components/VDataTable'
import { ref, computed } from 'vue';
import holiday_jp from '@holiday-jp/holiday_jp'
import { mkConfig, generateCsv, download } from "export-to-csv";
import WorkRecordRow from './WorkRecordRow.vue';
import WorkProcedureButtons from './WorkProcedureButtons.vue'
import OverTimeRequest from './OverTimeRequest.vue';
import WorkRecordTotal from './WorkRecordTotal.vue'
import { useBadgeStore } from '@/store/badge';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
    const props = defineProps([
        'monthAverage',
        'usersData',
        'selectedMonth',
        'records',
        'loading',
        'selectedYear',
        'headerHeight',
    ]) 
    const api = useApi()
    const overTimeRequestData = ref(null)
    const emit = defineEmits(['reload'])
    const tempItem = ref(null)
    const badge = useBadgeStore()
    const holidays = computed(() => {
        const holidays = holiday_jp.between(new Date(props.selectedYear + '-01-01'), new Date(props.selectedYear + '-12-31'));
        return holidays
    })
    const wrapper = ref(null)
    const procedureStart = (item) => {
        tempItem.value = item
    }
    const dailyButtons = (value, item) => {
        tempItem.value = null
        const targets = [dailyApproval, timeCardRemand, dailyCancel, overtTimeRequest]
        targets[value](item)
        badge.getRemindBadge()
    }
    const overtTimeRequest = (item) => {
        overTimeRequestData.value = item
    }

    const includeRegistered = computed(() => {
        return !!props.usersData.find(ob => ob.position_id === 15)
    })
    const hasHeader = (title) => {
        return headers.value.findIndex(element => element.title == title) !== -1
    }
    const headers = computed(() => {
        let headersArray = [
            { title: '日付'},
            { title: 'メンバー'},
            { title: '予定' },
            { title: '出勤'},
            { title: '退勤'},
            { title: '労働時間'},
            { title: '時間外'},
            { title: '休憩時間'},
            { title: '部門'},
            { title: '諸手当'},
            { title: 'インシデント'},
            { title: '目標達成率'},
            { title: 'コンディション'},
            { title: 'コメント'},
            { title: '経費'},
            { title: '車両使用' },
            { title: 'ステータス'},
            { title: '報告'},
        ];
        if(includeRegistered.value){
            const index = headersArray.findIndex(element => element.title == 'ステータス')
            headersArray.splice(index, 0, {title: 'インセンティブ'})
        }      

        return headersArray;
    })
    const timeCardRemand = async(item) => {
        
        const params = {
            user_id: item.user_id,
            record_day: item.day_full
        }

        await api.post('/remand_time_card', params, {
            ask: `${item.day_full}日報を差し戻します。`,
            toast: '差戻ししました。'
        })
        emit('reload')

    }
    
    const dailyApproval = async(item) => {
        const params = {
            user_id: item.user_id,
            record_day: item.day_full,
            overTimeRequest: item?.shift?.overtime_request,
        };

        await api.post('/approve_time_card', params, {
            toast: '承認しました。',
        })
        emit('reload')

    }
    const dailyCancel = async(item) => {
        const params = {
            user_id: item.user_id,
            record_day: item.day_full
        };
        await api.post('/cancel_time_card', params, {
            toast: '承認取消しました。',
        })
    }
  
    const closeOverTimeRequest = (val) => {
        overTimeRequestData.value = null
        if(val){
            emit('reload')
        }
    }
    const csvGenerate = async() => {
        const members = props.usersData.map(user => user.id)
        const today = DateTime.now().toFormat('yyyyMMddHHmmss')
        const response = await api.get(`/work_generate_csv?year=${props.selectedYear}&month=${props.selectedMonth}&users=${members}`)
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `タイムシート${props.selectedMonth}月_${today}`});
        const data = response        
        const csv = generateCsv(csvConfig)(data);
        download(csvConfig)(csv);
    }
</script>
<style lang="scss">
::-webkit-scrollbar {
    height: 4px;
}
.w-hover-button{
    display: flex;
    justify-content: center;
}
.absolute-div{
    position:absolute; 
    color: var(--primary-color); 
    display: flex; 
    justify-content: center; 
    align-items: center;
    width: 100%;
    height: 100%;
}
.workButton-wrapper{
    display: flex;
    justify-content: center;
    gap: 5px;
    align-items: center;
}
.text-wrap {
    white-space: break-spaces;
    max-height: 40px;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}
.v-table{
    height: 100%;
    background: var(--bg2) !important;
    table{
        font-size: 12px;
        background: var(--background-color);
        border-collapse: separate;
        border-spacing: 0;
        color: var(--primary-color);
        thead{
            position: sticky;
            top: 0;
            line-height: 40px;
            text-align: center;
            width: 90px;
            background-color: #606060;
            font-size: 12px;
            color: #fff;
            z-index: 1;
            vertical-align: middle;
            white-space: nowrap;
            height: 40px;
            th{
                border-right: 1px solid var(--calendarBorder);
                border-left: none;
                border-top: none;
                text-align: center;
                font-weight: 400;
                padding: 0 !important;
                height: 40px !important;

                .v-data-table-header__content{
                    justify-content: center;
                }
            }
            
        }
        tbody{
            .w-row{
                td{
                    border-bottom: 1px solid var(--calendarBorder);
                    border-right: 1px solid var(--calendarBorder);
                    vertical-align: middle;
                    width: 90px;
                    text-align: center;
                    height: 40px !important;
                    box-sizing: border-box;
                    padding: 0 !important;
                    white-space: nowrap;
                }
            }
            .w-row:hover{
                background: var(--bg3);
            }
            
            
        }
    }   
}



.v-table .v-table__wrapper > table > tbody {
    tr:not(:last-child)>td{
        border-bottom: none;
    }
}
.last-row > td{
    border-bottom: thin solid var(--calendarBorder) !important;
}

@media (max-width: 959px) {
    .comment-wrap {
        -webkit-line-clamp: 1;
        line-clamp: 1;
        display: -webkit-box;
        -webkit-box-orient: vertical;
    }
    .text-wrap {
        white-space: nowrap;
    }
    .mb-space{
        margin: 10px 0;
    }
    .w-hover-button{
        justify-content: flex-start;
    }
    .center-mobile{
        justify-content: center;
        margin-top: 10px;
    }
    
    .workButton-wrapper{
        justify-content: flex-start;
    }
    .mt-10 {
        margin-bottom: 5px;
    }
    .last-row{
        margin-bottom: 25px !important;
    }
    .td-first{
        padding: 15px 0px 0px 0px;
        text-align: center;
        margin-bottom: 5px;
    }
    .today .td-first{
        padding: 10px 0px;
    }
    .v-table{
        table{            
            font-size: 14px;        
            width: 100% !important;
            background: var(--bg2);
            .memberName{
                // font-weight: 600;
                white-space: nowrap;
            }
             thead {
                display: none !important; /* Hide the table header on mobile */
            }
            tfoot {
                display: none !important; /* Hide the table header on mobile */
            }
            tbody {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                min-height: auto !important; 
            

                /* Styles for individual table rows (cards) */
                .w-row {
                    border: 1px solid var(--calendarBorder);
                    margin: 0 20px;
                    display: table-row !important;
                    background: var(--background-color);
                    height: auto !important;
                    box-sizing: border-box;
                    font-size: 13px;
                    padding-bottom: 20px;
                    position: relative;
                    .date-cell{
                        padding: 5px 20px !important;
                        text-align: center !important;
                    }
                }
                
                /* Styles for table cells within rows */
                .w-row td {
                    text-align: left !important;
                    border: none !important;
                    border-bottom: none !important;
                    display: block;
                    height: fit-content !important;
                    
                    width: -webkit-fill-available;
                    line-height: 2;
                    padding: 0 20px !important;
                    max-width: calc(100vw - 45px);
                }
                .w-row:hover{
                    background: var(--background-color);
                }
                .command-cell{
                    display: flex !important;
                    width: 100% !important;
                    justify-content: center;
                }

            }
            
            
            .work-loader{
                height: 100%;
            }
        }
    }
}

</style>
<style scoped>
.tc{
    border-bottom: 1px solid var(--calendarBorder);
    vertical-align: middle;
    text-align: center;
    white-space: nowrap;
    box-sizing: border-box;
    min-height: 40px;
    height: 40px;
}
.tc:last-of-type{
    border-bottom: none;
}
.csv-button{
    padding: 3px 10px;
    background: var(--background-color);
    color: var(--primary-color);
    border-radius: 5px;
    margin: 0 10px;
    cursor: pointer;
}
</style>