<template>
    <div class="w-outer" v-if="isOpen">
        <Transition name="slidePop">
            <div class="w-inner" v-if="isOpenInner">
                <div v-if="saving" class="absolute top-0 left-0 w-full h-full bg-black opacity-50 z-[10] flex justify-center items-center">
                    <div style="border:4px #fff solid; border-top: solid 4px transparent;" class="spinner-micro"></div>
                </div>
                <div class="pb-5 leading-normal">
                    <p class="text-[18px]">{{ greetings }}</p>
                    <p v-if="messageData" class="whitespace-break-spaces" v-html="markedConverter(messageData.content)"></p>
                    <div v-if="messageData && messageData.chunks && messageData.chunks.length" class="flex flex-wrap gap-[15px] mt-[5px] mb-[10px] text-[14px]">
                        <span class="text-[gray]">参照 :</span>
                        <a v-for="chunk in messageData.chunks" target="_blank" :key="chunk.web.uri" :href="chunk.web.uri">{{ chunk.web.title }}</a>
                    </div>
                    <p>それでは、今日のコンディションを選択してください。</p>
                </div>
                <div class="flex justify-center">
                    <div class="flex items-center gap-5 flex-wrap justify-center">
                        <div @click="setWeather(0)" :class="['icon-container', { 'selected-w-icon': saving === 0 }]">
                            <Rainbow size="30"/>
                        </div>
                        <div @click="setWeather(1)" :class="['icon-container', { 'selected-w-icon': saving === 1 }]">
                            <Sun size="30"/>
                        </div>
                        <div @click="setWeather(2)" :class="['icon-container', { 'selected-w-icon': saving === 2 }]">
                            <Cloud size="42"/>
                        </div>
                        <div @click="setWeather(3)" :class="['icon-container', { 'selected-w-icon': saving === 3 }]">
                            <Umbrella size="30"/>
                        </div>
                        <div @click="setWeather(4)" :class="['icon-container', { 'selected-w-icon': saving === 4 }]">
                            <Snowman size="30"/>
                        </div>
                        <div @click="setWeather(5)" :class="['icon-container', { 'selected-w-icon': saving === 5 }]">
                            <Lightning size="30"/>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

</template>
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import Rainbow from '../Icons/Rainbow.vue';
import Sun from '../Icons/Sun.vue';
import Cloud from '../Icons/Cloud.vue';
import Umbrella from '../Icons/Umbrella.vue';
import Snowman from '../Icons/Snowman.vue';
import Lightning from '../Icons/Lightning.vue';
import { DateTime } from 'luxon';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { marked } from 'marked'
const emit = defineEmits(['close']);
interface MessageData {
    content: string;
    chunks: Chunk[];
}
interface Chunk {
  web: {
    title: string;
    uri: string;
  };
}
const isOpen = ref(false);
const isOpenInner = ref(false);
const messageData = ref<MessageData | null>(null);
const saving = ref<number | null>(null);
const auth = useAuthUserStore()
const api = useApi()
const { toast } = useDialog()
onMounted(() => {
    getWelcomeMessage();
});


const greetings = computed(() => {
    const hours = new Date().getHours();
    if (hours >= 5 && hours < 12) return 'おはようございます！';
    if (hours >= 12 && hours < 17) return 'こんにちは！';
    if (hours >= 17 && hours < 21) return 'こんばんは！';
    return '遅くまでお疲れさまです';
});

const getWelcomeMessage = async() => {

    messageData.value = await api.get('/welcome_message', {}, {silent: true});
    isOpen.value = true;
    setTimeout(() => {
        isOpenInner.value = true;
    }, 0);  

}

const setWeather = async (num: number) => {
    let today = DateTime.now().toISODate();
    if(saving.value == num) return;
 
    saving.value = num;
    await api.post('/save_weather', { today, value: num })
    const user = await api.post('/profile_get_update_user', {id: auth.id})
    if(user && Object.hasOwn(user, 'id')){
        auth.setUser(user)           
    } 
    isOpenInner.value = false;
    setTimeout(() => {
        isOpen.value = false;
        toast('保存しました。')
        emit('close')
    }, 300);

    saving.value = null;

}
const markedConverter = (content: string) => {
    const formattedHtml = marked.parse(content)
    return formattedHtml
}
</script>
<style>
.w-outer{
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 150;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--primary-color);
    box-sizing: border-box;
}
.w-inner{
    
    background-color: var(--background-color);
    padding: 20px;
    box-sizing: border-box;
    max-width: 500px;
    max-height: 50%;
    position: relative;
}
.icon-container{
    display: flex;
    justify-content: center;
    align-items: center;
    width: 60px;
    height: 60px;
    min-width: 60px;
    background-color: var(--background-color);
    transition: background-color 0.3s, transform 0.3s;
    cursor: pointer;
}
.icon-container:hover{
    background-color: var(--bg2);
    transform: scale(1.1);
    svg{
        .cloud{
            fill: #fff !important;
        }
    }
}
.selected-w-icon{
    transform: scale(1.2);
}
.selected-w-icon:hover{
    transform: scale(1.2);
}
@media screen and (max-width: 959px) {
    .w-inner{
        max-width: 85%;
        max-height: 95%;
    }
}
</style>