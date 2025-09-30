<template>
    <div style="
    font-size: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    padding: 3px;
    padding-bottom: 15px;">
        <CardWrap 
            v-for="record in dayRecords" 
            style="position: relative;" 
            :key="record.id"
            :record="record"
            :user="user"
        />
        <GoogleEventWrap 
            v-for="record in googleEvents" 
            style="position: relative;" 
            :key="record.id"
            :record="record"
            :user="user"
        />
    </div>
</template>
<script setup lang="ts">
import { User } from '@/interface/globalInterface';
import CardWrap from './CardWrap.vue';
import GoogleEventWrap from './GoogleEventWrap.vue';
import { CalendarRecord, GoogleEventItem } from '@/interface/calendarInterface';
const props = defineProps<{
    dayRecords: CalendarRecord[]
    user: User
    googleEvents: GoogleEventItem[]
}>()
</script>