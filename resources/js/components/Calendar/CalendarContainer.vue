<template>

    <div class="post-root">
        <div class="post-header">
            <HamBurger/>
            <div class="post-search-wrap">
                <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="`スケジュールを検索`"/>
            </div>
            <div style="flex: 1;display: flex;">
                <CalendarBar
                    @jumpToday="jumpToToday"
                />
                <div style="margin: auto 10px auto auto;">
                    <MonthPicker 
                        :selectedMonth="selectedMonth"
                        :selectedYear="selectedYear"
                        right="0"
                        @setDate="setDate"
                        ref="monthpicker"
                    />
                </div>
            </div>
            
            
            
        </div>
        <DayView
            ref="DayView"
            :daysOfMonth="daysOfMonth"
            :records="recordList"
            @scroll="scrollListen"
            @releaseScroll="appendLock = false"
        />
        <div title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="createWindow = true">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <Transition name="modalFade">                              
            <CalendarCreate 
                v-if="createWindow"   
                :editTarget="editTarget"     
                @close="closeCreate"      
            />
            
        </Transition> 
    </div>
        
</template>
<script>
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue'
import DayView from './DayView.vue'
import moment from 'moment';
import MonthPicker from '../Global/MonthPicker.vue'
import { nextTick } from 'vue'
import { dragscroll } from 'vue-dragscroll'
import CalendarCreate from './CalendarCreate.vue';
import CalendarBar from './CalendarBar.vue'
export default{
    data() {
        return {
            currentDate: moment(),
            daysInMonth: [],
            topOffset: 0,
            bottomOffset: 0,
            appendLock: true,
            records: [],
            selectedMonth: moment().month(),
            selectedYear: moment().year(),  
            activeMonth: moment().month(),
            activeYear: moment().year(), 
            createWindow: false,
            editTarget: null
        };
    },
    directives: {
        dragscroll
    },
    components:{
        HamBurger,
        PostSearchBar,
        DayView,
        MonthPicker,
        CalendarCreate,
        CalendarBar
    },
    mounted(){
        const date = moment().format('YYYY-MM-DD')
        this.getCalendar(date)
    },
    computed:{
        daysOfMonth() {
            const thisMonth = moment([this.activeYear, this.activeMonth]);
            const firstDayOfMonth = thisMonth.clone().subtract(this.topOffset, 'months').startOf('month');
            const lastDayOfMonth = thisMonth.clone().add(this.bottomOffset, 'months').endOf('month');
            const days = [];

            let currentDay = firstDayOfMonth.clone();
            while (currentDay.isSameOrBefore(lastDayOfMonth, 'day')) {
                days.push({
                    full: currentDay.format('YYYY-MM-DD'),
                    day: currentDay.format('D')
                });
                currentDay.add(1, 'day');
            }

            return days;
        },
        recordList()
        {
            return this.records && this.records.length ? this.records : []
        }
    },
    methods:{
        jumpToToday(){
            this.appendLock = true
            this.topOffset = 0
            this.bottomOffset = 0
            const year = moment().year()
            const month = moment().month()
            const date = {
                year: year,
                month: month + 1
            }
            this.setDate(date)
        },
        closeCreate(val){
            this.createWindow = false
            this.editTarget = null
            if(val){
                const date = moment().format('YYYY-MM-DD')
                this.getCalendar(date, 'today')
            }
        },
        setDate(date, param){
            this.appendLock = true
            this.bottomOffset = 0
            this.topOffset = 0
            this.activeMonth = date.month - 1
            this.activeYear = date.year
            this.selectedMonth = date.month - 1
            this.selectedYear = date.year
            
            this.records = []
            this.getCalendar(`${date.year}-${date.month}-01`, param)
            
            
        },
        
        scrollListen(){

            if(!this.appendLock){
                var percent = 100 * event.currentTarget.scrollTop / (event.currentTarget.scrollHeight - event.currentTarget.clientHeight);  
                if(percent > 99){   
                        
                    this.bottomOffset ++ 
                    this.appendLock = true
                    const current = moment([this.activeYear, this.activeMonth])
                    const next = current.clone().add(this.bottomOffset, 'month').startOf('month').format('YYYY-MM-DD')
                    
                    this.getCalendar(next)

                    
                }else if(percent < 5){
                    this.topOffset ++ 
                    this.appendLock = true
                    const currentScroll = event.currentTarget.scrollTop
                    const currentScrollHeight = event.currentTarget.scrollHeight
                    
                    const current = moment([this.activeYear, this.activeMonth])
                    const prev = current.clone().subtract(this.topOffset, 'month').startOf('month').format('YYYY-MM-DD')
                    
                    this.getCalendar(prev)
                    nextTick(() => {                   
                        
                        this.$refs.DayView.$refs.cal_day_view.scrollTop = this.$refs.DayView.$refs.cal_day_view.scrollHeight - ( currentScrollHeight - currentScroll)
                        // setTimeout(() => {
                        //     this.appendLock = false   
                        // }, 100);
                        
                    });  
                    
                }

                const parent = this.$refs['DayView'].$refs['dayParent']

                for (const el of parent.children) {                    
                    const ref = el
                    const month = el.id.substring(8, 15) 
                    const day = el.id.substring(8, 18) 
                    const lastDay = moment(day).endOf('month').format('YYYY-MM-DD')
                    if(moment(day).endOf('month').isSame(moment(day), 'day')){
                        const rect = el.getBoundingClientRect()
                        const cath = rect.y + el.clientHeight;
                        
                        if(cath < 60){
                            if(el.nextSibling){
                                const nextId = el.nextSibling.id.substring(8, 15) 
                                const [yearStr, monthStr] = nextId.split('-');
      
                                const year = parseInt(yearStr, 10); 
                                const month = parseInt(monthStr, 10) - 1; 
                                if(this.selectedMonth !== month){
                                    this.selectedMonth = month
                                }
                                if(this.selectedYear !== year){
                                    this.selectedYear = year
                                }
                            
                            }
                        }else if(cath > window.innerHeight && cath < window.innerHeight + 100){
                            
                            
                            if(el.previousSibling){
                                const nextId = el.id.substring(8, 15) 
                                const [yearStr, monthStr] = nextId.split('-');
      
                                const year = parseInt(yearStr, 10); 
                                const month = parseInt(monthStr, 10) - 1; 
                                if(this.selectedMonth !== month){
                                    this.selectedMonth = month
                                }
                                if(this.selectedYear !== year){
                                    this.selectedYear = year
                                }
                            
                            }
                        }
                    }                   
                }

            }
            const rect = {
                x: event.currentTarget.scrollLeft,
                y: event.currentTarget.scrollTop
            }
            this.$store.commit('setCalendarOffset', rect)
            
            
        },
        getCalendar(day, method){
            axios.post('/get_calendar_data',{day: day}).then(response => {  
                const index = moment(day).format('YYYY-MM')
                // let data = this.records

                // data[index] = response.data
                // if(method == 'concat'){
                //     this.records = this.records.concat(response.data)
                // }else{
                //     this.records = response.data
                // }

                response.data.forEach(item => {
                    const existingItem = this.records.find(record => record.id === item.id);
                    if (!existingItem) {
                        this.records.push(item);
                    }
                    if(method == 'today'){
                        this.$store
                    }
                    setTimeout(() => {
                        this.appendLock = false
                    }, 200);

                });
                
        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));
        },
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })   
        },
    }
}
</script>
<style>

</style>