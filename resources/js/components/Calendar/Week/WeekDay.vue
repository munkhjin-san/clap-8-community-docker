<template>
    <div class="w-day-item">
        <WeekRecord 
            :dayRecords="dayRecordsAfter" 
            :colors="colors"
            :facilitiesList="facilitiesList"
            :user="user"
            @edit="val => $emit('edit', val)"
            @delete="val => $emit('delete', val)"
            />
    </div>
</template>
<script>
import WeekRecord from './WeekRecord.vue';
export default{
    props: ['day', 'user', 'facilitiesList', 'colors', 'edit', 'delete'],
    components:{
        WeekRecord
    },
    computed:{
        dayRecordsAfter(){
            const user_records = this.day.records.filter(ob =>  ob.calendar_users.map(item => item.id).includes(this.user.id))
            return user_records
        }
    }
}

</script>