<template>
    <div @mousedown="setEmoteUsers([])" class="overlay !z-[47]">
        <div class="users-list-popup !bg-[var(--bg2)] !p-0" @mousedown.stop>
            <div class="flex items-end overflow-x-auto">
                <label v-for="emote in sortedEmotesByOccurence" class="p-2 flex shrink-0" :class="{'sticky left-0 right-0 bg-[var(--background-color)]' : activeEmoteName == emote.emoteName}">
                    <input type="radio" v-model="activeEmoteName" :value="emote.emoteName" class="hidden"/>
                    <Character :size="40" :emote-name="emote.emoteName"/>
                    <!-- <div class="num-block">{{ emote.occurence }}</div> -->
                </label>
            </div>
            <div v-if="activeEmoteName" class="min-h-[180px] bg-[var(--background-color)] flex flex-col gap-2 p-4 max-h-[300px] overflow-y-auto">                
                <div v-for="user in sortedEmotesByOccurence.find(e => e.emoteName === activeEmoteName)?.users || []" :key="user.id">
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
const activeEmoteName = ref<string | null>();

const sortedEmotesByOccurence = computed(() => {
    const data: {occurence: number, users: EmoteUser[], emoteName: string}[] = []
    emoteUsers.value.forEach(emote => {
        const existing = data.find(d => d.emoteName === emote.pivot.emote_name);
        if (existing) {
            existing.occurence++;
            existing.users.push(emote);
        } else {
            data.push({ occurence: 1, users: [emote], emoteName: emote.pivot.emote_name });
        }
    });
    data.sort((a, b) => b.occurence - a.occurence);
    return data
}); 
onMounted(() => {
    if(sortedEmotesByOccurence.value.length){
        activeEmoteName.value = sortedEmotesByOccurence.value[0].emoteName
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