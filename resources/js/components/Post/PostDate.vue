<template>
    <div style="display: inline;font-size: 14px;white-space: nowrap;margin-left: auto;" class="dateText">
        {{dateConverted}}                                                    
    </div>  
</template>

<script setup> 
    import moment from 'moment';
    import { computed } from 'vue';
    moment.locale('ja');  
    const props = defineProps(['record', 'dateClass'])
        
     
    const dateConverted = computed(() => {
        if(props.record.app_type == 2){
            const startYear = moment(props.record.date_start).format('YYYY')
            const endYear = moment(props.record.date_end).format('YYYY')
            const thisYear = moment().format('YYYY')
            if((startYear == endYear) && (endYear == thisYear)){
                return `${moment(props.record.date_start).format('M / D')}  ―  ${moment(props.record.date_end).format('M / D')}`
            }
            return `${moment(props.record.date_start).format('YYYY / M / D')}  ―  ${moment(props.record.date_end).format('YYYY / M / D')}`
        }else{
            return moment(props.record.created_at).isSame(moment(), 'year') ? 
            moment(props.record.created_at).format('M / D (dd)') : 
            moment(props.record.created_at).format('YYYY / M / D (dd)')
        }
        
    })

        
    
</script>
