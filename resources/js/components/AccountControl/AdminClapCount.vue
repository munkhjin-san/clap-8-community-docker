<template>
    <div class="container" style="height: calc(100% - 60px);">
        <!--<div class="appTitle">
            <div class="appTitle-inner">
                <h2>社員一覧</h2>
                
                <div class="clear-both"></div>
            </div>            
        </div>-->
      
        <div class="record-container-inner" style="height: 100%;">    
            <div style="display: flex;align-items: center;flex-wrap: wrap;">
                <p style="padding:20px;width: fit-content;">クラップ数集計</p>
                <div style="display: flex;margin-left: auto;align-items: center;padding: 15px;">                          
                        <DatePicker
                            :initialValue="startDate"
                            ref="startDate"
                            uId="startDate"
                            name="startDate"
                            rules=""
                            @setValue="setStart"
                        />
                        <div style="font-size: 30px;margin: 0 15px;"> ~ </div>
                        <DatePicker
                            :initialValue="endDate"
                            ref="endDate"
                            uId="endDate"
                            name="endDate"
                            rules=""
                            @setValue="setEnd"
                        />
                <!-- <div class="datepicker-task" style="display:inline-block;">
                    <vuejs-datepicker placeholder="終了日を選択してください" class="datePickerFormatArea" v-model="startDate" name="date_end" :value="startDate" format="yyyy-MM-dd" :language="ja"></vuejs-datepicker>
                </div>
                <div class="datepicker-task" style="display:inline-block;">
                    <vuejs-datepicker placeholder="終了日を選択してください" class="datePickerFormatArea" v-model="endDate" name="date_end" :value="startDate" format="yyyy-MM-dd" :language="ja"></vuejs-datepicker>
                </div> -->
                </div>
            </div>
            
            <div class="employee" style="height: 100%;overflow: auto;">
                
                <div class="row justify-content-center">
                   
                    <table id="customers">
                        <tr style="position: sticky;top: 0;">
                            <th>氏名</th>
                            <th>ナレッジ</th>
                            <th>ナイス</th>
                            <th>チャレンジ</th>
                            <th>合計</th>
                        </tr>
                        <tr v-for="data in clapList">
                            <td>{{data.name}}</td>
                            <td>{{data.knowledge}}</td>
                            <td>{{data.nice}}</td>
                            <td>{{data.challenge}}</td>
                            <td>{{data.sum}}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>        
    </div>
   
</template>
<script>

import moment from 'moment'
import DatePicker from '../Global/DatePicker.vue';
export default {
    props: ['searchUser'],
    components:{
        DatePicker
    },
    data() {
        return {
            clapData: [],
            startDate: '2020-12-01',
            endDate: moment().format('YYYY-MM-DD'),
        }
    },
    mounted() {
        this.allClapData()
    },
    
    computed: {
        clapList(){
            return this.clapData
        }
    }, 
    methods: {
        setStart(val){
            this.startDate = val
            this.allClapData()
        },
        setEnd(val){
            this.endDate = val
            this.allClapData()
        },
        allClapData(){
            axios.post('/clap_statistics',{start:this.startDate, end: this.endDate}).then( response => { this.clapData = response.data });   
        }
    }
}
</script>
<style scoped>
#customers {
  
  border-collapse: collapse;
  width: 100%;
  font-size: 14px;
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