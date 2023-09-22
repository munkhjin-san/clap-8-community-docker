<template>
    <div class="workButton-wrapper">
        <button class="work-button" v-for="(item, index) in buttons" :key="index" @click="clickButton(item.action)">
            {{  item.label }}
        </button>
    </div>
</template>
<script>
    export default{
        data(){
            return{
                buttons: [
                    { label: '勤怠予定', action: 'selectShift'},
                    { label: '勤怠確定', action: 'confirmAttendance'},
                    { label: 'メンバー', action: 'selectMember'},
                    { label: '今日', action: 'jumpToToday'},
                    { label: '集計', action: 'jumpToTotal'}
                ]
            }
        },
        methods: {
            clickButton(action){
                if(action == 'selectShift'){
                    this.$emit('selectShift')
                }else if(action == 'confirmAttendance'){
                    this.$emit('confirmAttendance')
                }else if(action == 'selectMember'){
                    this.$emit('selectMember')
                }else if(action == 'jumpToToday'){
                    this.$emit('todayScroll')
                }else if(action == 'jumpToTotal'){
                    this.$emit('toBottomScroll')
                }
            }
        }
    }
</script>
