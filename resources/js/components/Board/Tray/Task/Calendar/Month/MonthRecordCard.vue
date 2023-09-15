<template>
    <div>   
        <div class="recordCardContainer paper" :title="record.title" :style="{color: dateColor, background: colorCalendar}">
            {{ record.title }}
        </div>
    </div>
</template>

<script>
import moment from 'moment';
    export default {
        props: ['record', 'myColor', 'thisMonth'],
        mounted(){
            if(this.$store.state.urlTaskId == this.record.id){
                                 
                this.$store.commit('setUrlTaskId', null)                               
                
                if(this.$store.state.urlTaskEditFlag){             
                    emitter.emit('editTask', this.record)      
                }
                
            } 
        },
        computed:{
            timeSpan(){
                return moment(this.record.end_at).format('H:mm')
            },
            dateColor(){
                const now = moment().format('YYYY-MM-DD')
                const date_end = moment(this.record.end_at).format('YYYY-MM-DD')

                return now > date_end ? 'tomato' : 'var(--primary-color)'
            },
            colorCalendar(){
                return this.thisMonth ? 'var(--kebab-bg1)' : 'var(--normalBorder)'
            }
        },
        methods: {
            taskModal(){
                // this.openMethod()
                const data = {
                    active: true,
                    record: this.record,
                    taskColor: this.colorCalendar
                }
                this.$store.commit('setTaskModal', data)
            },
        },
    }
</script>
