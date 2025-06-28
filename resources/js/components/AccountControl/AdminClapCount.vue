<template>
    <div style="height: 100%;background: var(--bg3);position: relative">     
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition> 
        <div class="record-container-inner" style="height: 100%;">    
            <div style="display: flex;align-items: center;flex-wrap: wrap;">
                <p style="padding:20px;width: fit-content;">クラップ数集計</p>
                <div style="display: flex;margin-left: auto;align-items: center;padding: 15px;gap:15px">    
                    <ShortInput 
                        name="startDate" 
                        customClass="date"
                        type="date"
                        v-model="startDate"
                    /> 
                    <ShortInput 
                        name="endDate" 
                        customClass="date"
                        type="date"
                        v-model="endDate"
                    />   
                    <div class="admin-button" @click="downloadCSV">CSV出力</div>
                </div>
            </div>
            
            <div class="employee" style="height: calc(100% - 70px);overflow: auto;position: relative;">
                
                <div class="row justify-content-center">
                   
                    <table id="customers">
                        <thead style="position: sticky;top: 0;">
                            <tr>
                                <th>氏名</th>
                                <th>ポスト</th>
                                <th>コメント</th>
                                <th>ポートフォリオ</th>
                                <th>合計</th>
                            </tr>
                        </thead>    
                        <tbody>
                            <tr v-for="data in clapData">
                                <td>{{data.name}}</td>
                                <td>{{data.post}}</td>
                                <td>{{data.comment}}</td>
                                <td>{{data.portfolio}}</td>
                                <td>{{data.sum}}</td>
                            </tr>
                        </tbody>
                        
                    </table>
                </div>
            </div>
        </div>        
    </div>
   
</template>
<script setup>

import ShortInput from '../Form/ShortInput.vue';
import { onMounted, ref, watch } from 'vue';
import { mkConfig, generateCsv, download } from "export-to-csv";
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';

    const clapData = ref([])
    const startDate = ref('2020-12-01')
    const endDate = ref(DateTime.now().toISODate())
    const fetch = ref(0)
    const api = useApi()

    onMounted(async() => {
        await allClapData()
        fetch.value ++
    })
    watch(() => [startDate.value, endDate.value], () => {
        allClapData()
    })

    const allClapData = async() => {
        clapData.value = await api.post('/clap_statistics',{start:startDate.value, end: endDate.value});   
    }
    const downloadCSV = () => {
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `【${startDate.value} - ${endDate.value}】クラップ数集計`});
        const dataSet = []
        clapData.value.forEach(data => {
            const v = {
                "氏名" : data.name,
                "ポスト" : data.post,
                "コメント": data.comment,
                "ポートフォリオ" : data.portfolio,
                "合計" : data.sum
            }
            dataSet.push(v)
        });
        const csv = generateCsv(csvConfig)(dataSet);
        
        download(csvConfig)(csv);
    }

</script>
<style scoped>
#customers {
  
  border-collapse: collapse;
  font-size: 14px;
  width: -webkit-fill-available;
  width: -moz-available;
}
#customers tr {
    border: 1px solid var(--formBorder);
}
#customers td, #customers th {
  border: 1px solid var(--formBorder);
  padding: 8px;
}

#customers tr:nth-child(even){background-color:var(--bg3)}

#customers tr:hover {
    background-color: var(--primary-color);
    color: var(--background-color);
}

#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #363636;
  color: white;
}
</style>