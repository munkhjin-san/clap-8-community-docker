<template>
    <div class="shift-submitted-masonry">
        <div class="shift-submitted-masonry-inner">
            <div style="display:flex;align-items:center;position:relative">
                <UserIcon :disableInstant="true" size="30" :user="item.notification_user" imgClass="userNormalIcon"/>
                <p class="userName" style="margin-left:10px;">{{ item.notification_user.name }}</p>
            </div>
            <div>
                <p style="line-height:2;">{{ item.date }}から{{ item.endDate }}までの計画有給を入れてください。<br>期間：2024年1月31日</p>
                <div style="width:100%;margin-top:10px;">
                    <button class="shift-button" @click="shiftPlannedLeave()">計画有給を入力</button>
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
<script setup lang="ts">
    import { useRouter } from 'vue-router';
    import type { RouteRecordName } from 'vue-router';
    import { WorkItem } from '../../interface/workInterface';
    import UserIcon from '../Board/Mixed/UserIcon.vue';
    const emit = defineEmits(['close'])

    interface Props {
        item : WorkItem
    }
    const props = defineProps<Props>()

    const router = useRouter()

    const shiftPlannedLeave = () => {
        const { date } = props.item
        router.push({name: `work` as RouteRecordName, query: { startDate: date }})
        emit('close')
    }
</script>