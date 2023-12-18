<template>
<div style="display: flex;">
    <div @mousedown.stop @click="$emit('viewFull', true)" class="left-member-tile" :style="{ width: hideName ? '45px' : `130px`}">
        <div style="cursor: pointer;">  
            <UserIcon :disableInstant="hideName" :user="userData.user" imgClass="userMidIcon" size="25"/>
            <div @click.stop="pushInstantUser($event, userData.user.id)" :style="{lineHeight: 1.5, visibility: hideName ? 'hidden' : 'visible'}">{{userData.user.name}}</div>
        </div>
    </div>    
     <ListRow
        v-for="hour in hoursOfDay"
        :data="hour"
        :facilitiesList="facilitiesList"
        :colors="colors"
        @edit="val => $emit('edit', val)"
        @delete="val => $emit('delete', val)"
        @create="(date, user) => $emit('create', date, user)"
    /> 
</div>
</template>
<script>
import ListRow from './ListRow.vue';
import moment from 'moment';
import UserIcon from '../../Board/Mixed/UserIcon.vue';
export default{
    props: ['userData', 'colors', 'facilitiesList', 'delete', 'edit', 'hideName'],
    emits: ['create', 'viewFull'],
    components:{
        ListRow,
        UserIcon
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
                hours.push({hour: currentHour.format('H:mm'), records: hourRecords, user: this.userData.user, date: this.userData.date});
                currentHour.add(1, 'hour');
            }
            return hours;
        },
    },
    methods:{
        pushInstantUser(event, id){
            console.log(this.$store.state.user, id)
            if(this.$store.state.user && id == this.$store.state.user.id) return
            const cX = event.clientX;
            const cY = event.clientY;  
            const data = {
                id: id,
                cX: cX,
                cY: cY
            }
            this.$store.commit('setInstantUser', data)   
            this.$store.commit('setMenu', {name: 'instantProfileWindow', id: 5000})                 
        },
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