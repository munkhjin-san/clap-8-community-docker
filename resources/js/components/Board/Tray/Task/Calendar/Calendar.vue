<template>
    <div class="c-container" :style="{zIndex: rootZindex}">
        <Transition name="pickerPop" tag="div">   
            <MonthPicker 
                :selectedMonth="selectedMonth"
                :selectedYear="selectedYear"
                @setDate="setDate"
                ref="monthpicker"
            />
        </Transition>  
        <MonthContainer 
            :records="recordList"
            :selectedYear="selectedYear" 
            :selectedMonth="selectedMonth"
            :months="months"
            :myColor="myColor" 
            @addTask="addTask"
            @increase="increase"
        />
    </div>
</template>

<script>
import "./calendarStyle.scss"
import { dragscroll } from 'vue-dragscroll'
import moment from 'moment'
import MonthContainer from './Month/MonthContainer.vue'
import MonthPicker from '../../../../Global/MonthPicker.vue'
    export default {
        props: ['records', 'calendarHide', 'myColor'],
        data(){
            return{                
                selectedYear: moment().year(),
                selectedMonth: moment().month(),
                rootZindex: 1,
                members: [],
                recordList: this.records,
                desireDate: moment().format('YYYY-MM-DD'),
                months: [],
                selected: false
            }
        },
        created() {
            for(let i = 0; i < 12; i++){
                this.months.push(i);
            }
        },  
        components:{
            MonthContainer,
            MonthPicker
        },
        directives: {
            dragscroll
        },
        mounted() {
            emitter.on('addCalendar', (day) => this.addCalendar(day))
            
        },
        methods:{
            
            jumpToToday(){
                this.selectedYear = moment().year()
                this.selectedMonth = moment().month()
            },
            
            increase(realIndex, previous){
                if(previous == 11 && realIndex == 0 && !this.selected){
                    this.selectedYear++
                }else if(previous == 0 && realIndex == 11 && !this.selected){
                    this.selectedYear--
                }
                    this.selectedMonth = realIndex
                    this.selected = false
            },
            addTask(day){
                this.$emit('newTask', day)
            },
            setDate(date){
                this.selectedYear = date.year
                this.selectedMonth = date.month - 1
                this.selected = date.select
            },
        
        },  
        computed:{
            
            dayTimes(){
                let arr = []
                for(let i=0; i < 23; i++){
                    arr.push(i + ':00')
                }
                return arr
            },            
        },
        watch: {
            records: {
                immediate: true,
                handler(newValue, oldValue) {
                    this.recordList = newValue
                }
            }
        }
    }
</script>
