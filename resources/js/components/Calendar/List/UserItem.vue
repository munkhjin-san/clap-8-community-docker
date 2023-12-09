<template>
<div style="display: flex;">
    <div class="left-member-tile">{{userData.user.name}}</div>
     <ListRow
        v-for="hour in hoursOfDay"
        :data="hour"
        :facilitiesList="facilitiesList"
        :colors="colors"
        @edit="val => $emit('edit', val)"
        @delete="val => $emit('delete', val)"
    /> 
</div>
</template>
<script>
import ListRow from './ListRow.vue';
import moment from 'moment';
export default{
    props: ['userData', 'colors', 'facilitiesList', 'delete', 'edit'],
    components:{
        ListRow
    },
    computed:{
        hoursOfDay() {
            const hours = [];
            let currentHour = moment().startOf('day');
            const records = this.userData.records
            const sortedList = records.slice().sort((a, b) => {
                return new Date(a.date_start) - new Date(b.date_start);
            });         
            const ordered = this.orderCreator(0, sortedList, this.userData.date)    
            for (let i = 0; i < 24; i++) {
                const hourRecords = ordered.filter(ob => moment(ob.date_start).format('H') == currentHour.format('H'))
                       
                
                // return ordered
                hours.push({hour: currentHour.format('H:mm'), records: hourRecords, user: this.userData.user});
                currentHour.add(1, 'hour');
            }
            return hours;
        },
    },
    methods:{
        orderCreator(order, list, date){
            let break_point_rear = moment(date).startOf('day')
            let cooked = [];
            let reserved = [];
            for (let i = 0; i < list.length; i++) {
                let item = list[i]
                if(i == 0){
                    item['order'] = order
                    cooked.push(item)
                    break_point_rear = moment(item.date_end)
                }else{
                    if(moment(item.date_start).isSameOrAfter(break_point_rear)){
                        item['order'] = order
                        cooked.push(item)
                        break_point_rear = moment(item.date_end)
                    }
                    else{
                        reserved.push(item)
                    }
                }
            }
            if(reserved.length){
                let uld = this.orderCreator(order + 1, reserved, date);
                cooked = cooked.concat(uld)
            }
            return cooked
            

        },
    }
}
</script>