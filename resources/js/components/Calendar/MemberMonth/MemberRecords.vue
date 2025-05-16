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
    </div>
</template>
<script setup lang="ts">
import CardWrap from './CardWrap.vue';
const props = defineProps(['dayRecords', 'user'])
</script>