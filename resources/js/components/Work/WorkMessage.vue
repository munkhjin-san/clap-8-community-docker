<template>
    <div>
        <div class="shift-submitted-masonry-inner">
            <div style="display:flex;align-items:center;position:relative">
                <UserPanel :disableInstant="true" :withName="true" size="30" :user="item.notification_user" imgClass="userNormalIcon"/>
            </div>
            <div>
                <p style="line-height:2;">{{ item.date }}から{{ item.endDate }}までの計画有給</p> 
                <!-- <br>期間：2024年1月31日 -->
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

</style>
<script setup lang="ts">
    import { useRouter } from 'vue-router';
    import type { RouteRecordName } from 'vue-router';
    import { WorkItem } from '../../interface/workInterface';
    import UserPanel from '../Global/UserPanel.vue';
    const emit = defineEmits(['close'])

    interface Props {
        item : WorkItem
    }
    const props = defineProps<Props>()

    const router = useRouter()

    const shiftPlannedLeave = () => {
        const { date } = props.item
        router.push({name: `timesheet` as RouteRecordName, query: { startDate: date }})
        emit('close')
    }
</script>