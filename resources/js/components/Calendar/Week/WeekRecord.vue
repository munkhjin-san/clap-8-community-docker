<template>
    <div style="
    font-size: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    padding: 3px;">
        <div v-for="record in dayRecords" >
            <!-- {{ item.title }} -->
            <div v-if="expanded(record)" style="height: 59px;"></div>
            <div :id="`w_rec_${record.id}_${user.id}`" :class="['cal-w-wrap',{'pop-w-card' : expanded(record)}]" :style="{transform: expanded(record) ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`,}"> 
                <CalendarCard
                    :record="record"
                    :facilitiesList="facilitiesList" 
                    :colors="colors"
                    :viewable="viewable(record)"
                    :editable="editable(record)"
                    :expanded="expanded(record)"
                    @selectRecord="(val) => selectRecord(val, user)"
                    @deleteRecord="deleteRecord"
                    @edit="val => $emit('edit', val)"
                />
            </div>
           
        </div>
    </div>
</template>
<script>
import CalendarCard from '../CalendarCard.vue';
import { nextTick } from 'vue';
export default{
    props:['dayRecords', 'facilitiesList', 'colors', 'user', 'edit', 'delete'],
    data(){
        return{
            shiftRight: 0,
            shiftBottom: 0
        }
    },
    components:{
        CalendarCard
    },
    mounted(){
        if(this.dayRecords && this.dayRecords.length){
            // console.log(this.dayRecords)
        }
        
    },
    methods: {        
        viewable(record){
            return record.release_flag == 0 || this.editable(record)
        },
        editable(record){
            const me = record.calendar_users.filter(ob => ob.id == this.$store.state.user.id)
            return me.length || record.edit_all
        },
        expanded(record){
            return this.$store.state.menu.id == record.id && this.$store.state.menu.user_id == this.user.id && (this.$store.state.menu.name == `cal_${record.id}` || this.$store.state.menu.name == `calendarRecordMenu`) 
        },
        selectRecord(record, user){
            console.log(user)
       
                this.$store.commit('setMenu', {id: record.id, name: `cal_${record.id}`, user_id: user.id})
                nextTick(() => {
                const el = document.getElementById(`w_rec_${record.id}_${user.id}`)
                if(el){
                    const rect = el.getBoundingClientRect();                    
                    const right_check = rect.x + rect.width
                    if(right_check > window.innerWidth){
                        this.shiftRight = window.innerWidth - right_check - 5
                    }
                    const bottom_check = rect.y + rect.height
                    const value = this.$store.state.mobile && this.$store.state.user.footer_view ? 45 : 0
                    if(bottom_check > window.innerHeight - value){
                        this.shiftBottom = window.innerHeight - value - bottom_check - 10
                    }
                }
                
            })
         
        
        },
        deleteRecord(record){
            this.$emit('delete', record)
            this.$store.commit('setMenu', {id: null, name: ''})
        }
    }
}
</script>