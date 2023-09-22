<template>
    <div :name="`sep_${day.full}`" class="month-separetor">
        <div class="month-separetor-text" :style="{left: `calc(50vw + ${$store.state.calendarOffset.x - 37}px)`}">{{ fistDay }}</div>
        <div class="month-separetor-line"></div>
    </div>
</template>
<script>
import moment from 'moment';
export default{
    props: ['day'],
    computed:{
        fistDay(){
            return moment(this.day.full).format('YYYY-MM')
        }
    },
}
</script>