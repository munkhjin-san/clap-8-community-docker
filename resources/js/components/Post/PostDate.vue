<template>
    <div style="display: inline;font-size: 14px;white-space: nowrap;margin-left: auto;" class="dateText">
        {{dateConverted}}                                                    
    </div>  
</template>

<script>
    import moment from 'moment';
    moment.locale('ja');  
    export default {
        props: ['record', 'dateClass'],
        
        mounted(){
            
        },
        computed:{
            dateConverted(){
                if(this.record.app_type == 4){
                    const startYear = moment(this.record.date_start).format('YYYY')
                    const endYear = moment(this.record.date_end).format('YYYY')
                    const thisYear = moment().format('YYYY')
                    if((startYear == endYear) && (endYear == thisYear)){
                        return `${moment(this.record.date_start).format('M / D')}  ―  ${moment(this.record.date_end).format('M / D')}`
                    }
                    return `${moment(this.record.date_start).format('YYYY / M / D')}  ―  ${moment(this.record.date_end).format('YYYY / M / D')}`
                }else{
                    return moment(this.record.created_at).isSame(moment(), 'year') ? 
                    moment(this.record.created_at).format('M / D (dd)') : 
                    moment(this.record.created_at).format('YYYY / M / D (dd)')
                }
                
            }
        },
        methods:{
            
        }
    }
</script>
