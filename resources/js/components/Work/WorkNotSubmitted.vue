<template>
    <div class="shift-submitted-masonry">
        <div class="shift-submitted-masonry-inner">
            <div style="display:flex;align-items:center;position:relative">
                <UserIcon :disableInstant="true" size="30" :user="item.notification_user" imgClass="userNormalIcon"/>
                <p class="userName" style="margin-left:10px;">{{ item.notification_user.name }}</p>
            </div>
            <div v-if="plan == 'plan'">
                <p style="line-height:2;">{{ item.date }}から{{ item.endDate }}までの計画有給を入れてください。<br>期間：2023年12月29日</p>
                <div style="width:100%;margin-top:10px;">
                    <button class="shift-button" v-on:click="shiftPlannedLeave(item)">計画有給を入力</button>
                </div>
            </div>
            <div v-else>
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
    </div>
</template>

<style>
    .shift-submitted-masonry{
        padding: 15px;
    }
    .shift-submitted-masonry-inner{
        background: var(--background-color);
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
        color: #e4e6eb;
    }
</style>

<script>
    export default {
        props:['item', 'plan', 'consumedDays', 'remainingDays'],
       
        methods: {
            shiftPlannedLeave(item){
                const { date } = item
                this.$router.push({name: `work`, query: { startDate: date }})
            },
            timeCardAdd(flag,item){
                const { value, shiftType, shiftStartTime, shiftEndTime } = item;
                flag == 1 ? this.$router.push({name: 'work', query: { action: flag, date: value, shiftType: shiftType, shiftStartTime: shiftStartTime, shiftEndTime: shiftEndTime }}) : this.$router.push({name: 'work', query: { action: flag }})
            }

        }
    }
</script>
