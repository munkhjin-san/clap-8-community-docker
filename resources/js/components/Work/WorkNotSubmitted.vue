<template>
    <div class="shift-submitted-masonry">
        <div class="shift-submitted-masonry-inner">
            <div style="display:flex;align-items:center;position:relative">
                <UserIcon size="30" :user="item.notification_user" imgClass="userNormalIcon"/>
                <p class="userName" style="margin-left:10px;">{{ item.notification_user.name }}</p>
            </div>

            <template v-if="item.day">
                <p style="line-height:50px;">{{ item.month }}月{{ item.day }}日の日報を提出してください。</p>
            </template>
            <template v-else>
                <p style="line-height:50px;">{{ item.month }}月の勤怠予定を提出してください。</p>
            </template>

            <div style="width:100%;">
                <button v-if="item.day" class="shift-button" v-on:click="timeCardAdd(1,item)">勤怠予定を入力</button>
                <button v-else class="shift-button" v-on:click="timeCardAdd(2,item)">日報を提出</button>
            </div>

        </div>
    </div>
</template>

<style>
    .shift-submitted-masonry{
        padding: 15px;
    }
    .shift-submitted-masonry-inner{
        background: var(--background-color);
        height: 110px;
        text-align: left;
        padding: 15px;
        color: var(--primary-color);
    }
    .shift-submitted-masonry-inner p{
        font-size: 14px;
    }
    button.shift-button{
        padding: 5px 10px 5px 10px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 0px;
        background: var(--primary-button);
        color: #fff;
    }
</style>

<script>
    export default {
        props:['item'],
       
        methods: {

            timeCardAdd(flag,item){
                const { value, shiftType, shiftStartTime, shiftEndTime, month_flag } = item;
                const url = `/app/public/work?action=${flag}&value=${value}&shiftType=${shiftType}&shiftStartTime=${shiftStartTime}&shiftEndTime=${shiftEndTime}`;
                window.open(url, '_blank').focus();
            }

        }
    }
</script>
