<template>
<div class="calendar-search-result-window">
    <div style="position: relative;">   
        <div v-if="searchResult && searchFetch" class="cal-search-result-inner">
            <div @click.stop.prevent="emit('jumpToRecord', item)" v-for="item in searchResult" class="cal-search-item">
                <p style="font-size: 14px;margin-bottom: 10px;">{{ item.title }}</p>
                <p>{{ time(item) }}</p>
                <p v-html="urlCheck(item.remarks) "></p>
                <p v-html="urlCheck(item.referrer)"></p>
            </div>

        </div>
        <div style="color: darkgray;font-size: 14px;margin-top: 40px;text-align: center;" v-if="!searchResult.length && searchFetch">
            検索結果はありません。
        </div>
    </div>
    
</div>
</template>
<script setup lang="ts">
import { CalendarRecord } from '@/interface/calendarInterface';
import { customParser, urlCheck } from '@/utils/tools';
    const props = defineProps(['searchResult', 'searchFetch'])
    const emit = defineEmits(['jumpToRecord'])
    
    const time = (item: CalendarRecord) => {
        const from = customParser(item.date_start).toFormat('yyyy/MM/dd(EEE) H:mm')
        const to = customParser(item.date_end).toFormat('H:mm')
        return `${from} ~ ${to}`
    }
</script>