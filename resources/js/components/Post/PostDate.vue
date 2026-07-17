<template>
    <div class="text-inherit inline whitespace-nowrap ml-auto">{{dateConverted}}</div>  
</template>
<script setup lang="ts"> 
    import { Post } from '@/interface/postInterface';
    import { computed } from 'vue';
    import { DateTime } from 'luxon';
    const props = defineProps<{
        record: Post;
        which: string;
    }>()       
     
    const dateConverted = computed(() => {
        const now = DateTime.now();
    
        if(props.record.app_type == 2){
            let startDate = DateTime.fromISO(props.record.date_start);
            let endDate = DateTime.fromISO(props.record.date_end);
            if (props.which == 'charge_period') {
                startDate = DateTime.fromISO(props.record.created_at);
                endDate = props.record?.mini ? DateTime.fromISO(props.record.created_at).plus({ days: 6 }) :  DateTime.fromISO(props.record.created_at).plus({ days: 13 });
            }
            
            
            if(startDate.year === endDate.year && endDate.year === now.year){
                return `${startDate.toFormat('M / d')}  ~  ${endDate.toFormat('M / d')}`;
            }
            return `${startDate.toFormat('yyyy / M / d')}  ―  ${endDate.toFormat('yyyy / M / d')}`;
        } else if(props.record.app_type == 7 && props.which == 'charge_period'){
            let startDate = DateTime.fromISO(props.record.created_at);
            let endDate = DateTime.now().endOf('month');
            return `${startDate.toFormat('M / d')}  ~  ${endDate.toFormat('M / d')}`;
        } else {
            const createdDate = DateTime.fromISO(props.record.created_at);
            return createdDate.year === now.year ? 
                createdDate.toFormat('M / d (ccc)') : 
                createdDate.toFormat('yyyy / M / d (ccc)');
        }
        
    })   
</script>
