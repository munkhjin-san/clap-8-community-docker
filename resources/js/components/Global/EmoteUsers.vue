<template>
    <div @mousedown="setEmoteUsers([])" class="overlay">
        <div class="users-list-popup !bg-[var(--bg2)] !p-0" @mousedown.stop>
            <div class="flex items-end overflow-x-auto">
                <label v-for="emote in sortedEmotesByOccurence" class="p-2 flex shrink-0" :class="{'sticky left-0 right-0 bg-[var(--background-color)]' : activeEmoteId == emote.emoteId}">
                    <input type="radio" v-model="activeEmoteId" :value="emote.emoteId" class="hidden"/>
                    <Character :size="40" :emoteId="emote.emoteId"/>
                    <div class="num-block">{{ emote.occurence }}</div>
                </label>
            </div>
            <div v-if="activeEmoteId" class="min-h-[180px] bg-[var(--background-color)] flex flex-col gap-2 p-4 max-h-[300px] overflow-y-auto">                
                <div v-for="user in sortedEmotesByOccurence.find(e => e.emoteId === activeEmoteId)?.users || []" :key="user.id">
                    <UserPanel disable-instant with-name :user="user" size="25"/>             
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useModal } from '@/composables/modal';
import { EmoteUser } from '@/interface/globalInterface';
import { computed, onMounted, ref } from 'vue';
import UserPanel from './UserPanel.vue';
import Character from './Character.vue';

const { setEmoteUsers, emoteUsers } = useModal()
const activeEmoteId = ref<number | null>();

const sortedEmotesByOccurence = computed(() => {
    const data: {occurence: number, users: EmoteUser[], emoteId: number}[] = []
    emoteUsers.value.forEach(emote => {
        const existing = data.find(d => d.emoteId === emote.pivot.emote_id);
        if (existing) {
            existing.occurence++;
            existing.users.push(emote);
        } else {
            data.push({ occurence: 1, users: [emote], emoteId: emote.pivot.emote_id });
        }
    });
    data.sort((a, b) => b.occurence - a.occurence);
    return data
}); 
onMounted(() => {
    if(sortedEmotesByOccurence.value.length){
        activeEmoteId.value = sortedEmotesByOccurence.value[0].emoteId
    }
})


</script>
<style scoped>
    .num-block{
        background: #000;
        text-align: center;
        width: -moz-fit-content;
        width: fit-content;
        padding: 0 6px;
        font-size: 10px;
        border-radius: 25px;
        color: #fff;
        text-indent: 1px;
        height: 16px;
        line-height: 16px;
        margin-top: auto;
        margin-left: -10px;
        margin-bottom: -3px;
    }
</style>