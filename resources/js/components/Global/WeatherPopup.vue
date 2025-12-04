<template>
    <div class="w-outer" v-if="isOpen">
        <Transition name="slidePop">
            <div class="w-inner" v-if="isOpenInner" @click.stop="menu.close()" :class="{'is-mobile': isMobile}">
                <div v-if="loading" class="fixed top-0 left-0 w-full h-full bg-black opacity-50 z-[10] flex justify-center items-center">
                    <div style="border:4px #fff solid; border-top: solid 4px transparent;" class="spinner-micro"></div>
                </div>
                <!-- <WeatherPopupSkeleton v-if="loading" /> -->
                <div class="popup-body">
                    <div class="popup-scroll">
                        <p class="greeting-text" v-html="combinedMessage"></p>
                        <transition-group name="modalFade" v-if="(schedules.length || tasks.length) && !isMobile" tag="div" class="popup-grid">
                            <div v-if="schedules.length" class="flex flex-col gap-2 pb-2">
                                <div class="section-header">
                                    <p class="leading-normal">スケジュール</p>
                                    <button v-if="showSchedulesMore" class="more-link" type="button" @click="toggleSchedules">
                                        {{ showAllSchedules ? 'とじる' : `もっと見る（${schedules.length}）` }}
                                    </button>
                                </div>
                                <CardWrap
                                    v-for="record in visibleSchedules"
                                    :record="record"
                                    :key="record.id"
                                    @click.prevent
                                    class="pointer-events-none"
                                />
                            </div>
                            <div v-if="tasks.length" class="flex flex-col gap-2 pb-2">
                                <div class="section-header">
                                    <p class="leading-normal">タスク</p>
                                    <button v-if="showTasksMore" class="more-link" type="button" @click="toggleTasks">
                                        {{ showAllTasks ? 'とじる' : `もっと見る（${tasks.length}）` }}
                                    </button>
                                </div>
                                <SimpleBox
                                    v-for="item in visibleTasks" 
                                    :item="item"  
                                    :isBoard="false"
                                    box-class=""
                                    @click.prevent
                                    class="pointer-events-none"
                                />
                                
                            </div>
                        </transition-group>
                        <transition-group
                            v-if="members.length"
                            name="modalFade"
                            tag="div"
                            >
                            <div class="members-block" key="daily-messages">
                                <p class="leading-normal">みんなのひとこと</p>
                                <DailyMemberMessages :members="members" />
                                <!-- <div v-else class="member-chip-row" key="chips">
                                    <div
                                        v-for="member in members"
                                        :key="member.id"
                                        class="member-chip"
                                    >
                                        <UserPanel
                                            :user="member"
                                            :disable-instant="true"
                                            :with-name="false"
                                            size="22"
                                        />
                                        <WeatherIcon
                                            v-if="member?.custom_field_data_records?.length"
                                            :which="member?.custom_field_data_records[0]?.value_int"
                                            size="14"
                                            class="min-w-[18px]"
                                        />
                                        <span class="member-chip-text truncate">{{ member?.custom_field_data_records?.[0]?.value_text }}</span>
                                    </div>
                                </div> -->
                            </div>
                        </transition-group>
                    </div>
                    <div class="condition-block">
                        <p class="leading-normal">コンディションを選択してください。</p>
                        <div :class="['condition-row', {'stacked': isMobile}]">
                            <div class="icon-row">
                                <div @click.stop="saving = 0" :class="['icon-container', { 'selected-w-icon': saving === 0 }]">
                                    <Rainbow size="26"/>
                                </div>
                                <div @click.stop="saving = 1" :class="['icon-container', { 'selected-w-icon': saving === 1 }]">
                                    <Sun size="26"/>
                                </div>
                                <div @click.stop="saving = 2" :class="['icon-container', { 'selected-w-icon': saving === 2 }]">
                                    <Cloud size="32"/>
                                </div>
                                <div @click.stop="saving = 3" :class="['icon-container', { 'selected-w-icon': saving === 3 }]">
                                    <Umbrella size="26"/>
                                </div>
                                <div @click.stop="saving = 4" :class="['icon-container', { 'selected-w-icon': saving === 4 }]">
                                    <Snowman size="26"/>
                                </div>
                                <div @click.stop="saving = 5" :class="['icon-container', { 'selected-w-icon': saving === 5 }]">
                                    <Lightning size="26"/>
                                </div>
                            </div>
                            <div class="comment-row">
                                <ShortInput 
                                    place-holder="ひとこと（任意）"
                                    v-model="todayComment"
                                />
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="footer-btn">
                        <LoaderButton @triggered="start" :content="buttonLabel"/>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

</template>
<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
import { markTodayDone } from '@/utils/tools';
import CardWrap from '../Calendar/NormalMonth/CardWrap.vue';
import { CalendarRecord } from '@/interface/calendarInterface';
import LoaderButton from './LoaderButton.vue';
import { Task } from '@/interface/globalInterface';
import SimpleBox from '../Task/List/SimpleBox.vue';
import DailyMemberMessages from './DailyMemberMessages.vue';
import ShortInput from '../Form/ShortInput.vue';
import UserPanel from './UserPanel.vue';
import WeatherIcon from './WeatherIcon.vue';
import { useMenuStore } from '@/store/menu';
const morningButtons = [
  'ほないくで🐙',
  'へばいぐべし👹',
  'ちぇすとー🌋',
  'いくぜよ🌊',

  'Let’s go🏃‍➡️',
  'Come on🎾',
  'Here we go👍',
  'Have a nice day🤗',

  'お仕事するゾー🍭',
  'グラウド〜🐴',
  '押忍✊️',
  '整いました🧘‍♀️',
  'いくわよ〜🫶',
  'よしこい🍜',
]
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
const todayComment = ref('')
const tasks = ref<Task[]>([])
const loading = ref(false)
const showAllSchedules = ref(false)
const showAllTasks = ref(false)
const members = ref<{
    id: number;
    name: string;
    icon_bg: string;
    icon_path: string;
    custom_field_data_records?: commentType[]
    pivot: any 
}[]>([])
type commentType = {
    value_text: string;
    date: string;
    type_id:number;
    value_int: number;
}
const menu = useMenuStore()
const { toast } = useDialog()
const schedules = ref<CalendarRecord[]>([])
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1200)
const isMobile = computed(() => windowWidth.value <= 959)
onMounted(() => {
    window.addEventListener('resize', handleResize)
    getTodayThings()
    pickRandomLabel();
    getWelcomeMessage()
});
onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
})
const buttonLabel = ref('');

const pickRandomLabel = () => {
  const idx = Math.floor(Math.random() * morningButtons.length);
  buttonLabel.value = morningButtons[idx];
};

const getTodayThings = async() => {
    const data = await api.get('/get_today_things')
    if (data) {
        members.value = isMobile.value ? randomPick(data.members, 10) : randomPick(data.members, 20);
        schedules.value = data.schedules
        tasks.value = data.tasks
    }
    loading.value = false
}
const handleResize = () => {
    windowWidth.value = window.innerWidth
}
const greetings = computed(() => {
    const hours = new Date().getHours();
    if (hours >= 5 && hours < 12) return 'おはようございます！';
    if (hours >= 12 && hours < 17) return 'こんにちは！';
    if (hours >= 17 && hours < 21) return 'こんばんは！';
    return '遅くまでお疲れさまです';
});
const visibleSchedules = computed(() => {
    if (showAllSchedules.value) return schedules.value
    return schedules.value.slice(0, 4)
})
const showSchedulesMore = computed(() => schedules.value.length > 4)
const toggleSchedules = () => showAllSchedules.value = !showAllSchedules.value
const visibleTasks = computed(() => {
    if (showAllTasks.value) return tasks.value
    return tasks.value.slice(0, 4)
})
const showTasksMore = computed(() => tasks.value.length > 4)
const toggleTasks = () => showAllTasks.value = !showAllTasks.value
const randomPick = (arr: any[], count: number) => {
    const copy = [...arr];
    for (let i = copy.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [copy[i], copy[j]] = [copy[j], copy[i]];
    }
    return copy.slice(0, count);
}
const start = async () => {
    if (!saving.value) return
    loading.value = true;
    const user = await api.post('/save_weather', { value: saving.value, comment: todayComment.value })
    if(user && Object.hasOwn(user, 'id')){
        auth.setUser(user)           
    } 
    isOpenInner.value = false;
    setTimeout(() => {
        isOpen.value = false;
        toast('保存しました。')
        markTodayDone(auth.id)
        emit('close')
    }, 300);
    loading.value = false;
    saving.value = null;
}
const closePopup = () => {
    if (saving.value) return
    isOpenInner.value = false
    setTimeout(() => {
        isOpen.value = false;
        emit('close')
    }, 200);
}
const getWelcomeMessage = async() => {

    messageData.value = await api.get('/welcome_message', {}, {silent: true});
    isOpen.value = true;
    setTimeout(() => {
        isOpenInner.value = true;
    }, 0);  

}
const combinedMessage = computed(() => {
  if (!messageData.value) return greetings.value
  return greetings.value + markedConverter(messageData.value.content)
})

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
    box-sizing: border-box !important;
}
.w-inner{
    
    background-color: var(--background-color);
    padding: 30px;
    box-sizing: border-box !important;
    max-width: 80%;
    max-height: 85vh;
    width: 80%;
    height: auto;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 12px;
    overflow: hidden auto;
}
.popup-body{
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.popup-scroll{
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding-right: 4px;
}
.greeting-text{
    line-height: 1.5;
}
.popup-grid{
    display: grid;
    gap: 12px;
    grid-template-columns: 1fr;
}
.popup-grid > div,
.members-block,
.condition-block{
    min-width: 0;
}
.section-header{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.more-link{
    font-size: 12px;
    color: var(--primary-color);
    text-decoration: underline;
}
.members-block{
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.member-chip-row{
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 4px 2px;
}
.member-chip{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--bg3);
    padding: 6px 10px;
    border-radius: 9999px;
    white-space: nowrap;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.25), 0 0 24px rgba(128, 128, 128, 0.08) inset;
}
.member-chip-text{
    font-size: 12px;
    max-width: 140px;
}
.condition-block{
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-shrink: 0;
}
.condition-row{
    display: flex;
    gap: 20px;
    align-items: center;
}
.condition-row.stacked{
    flex-direction: column;
    align-items: flex-start;
}
.icon-row{
    display: flex;
    gap: 12px;
    flex-wrap: nowrap;
    overflow-x: auto;
    min-width: fit-content;
    padding: 4px;
}
.comment-row{
    width: 100%;
    min-width: 0;
}
.footer-btn{
    margin-top: auto;
    display: flex;
    justify-content: center;
    flex-shrink: 0;
}
.icon-container{
    display: flex;
    justify-content: center;
    align-items: center;
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 9999px;
    /* background-color: var(--bg3); */
    transition: background-color 0.3s, transform 0.3s;
    cursor: pointer;
}
.icon-container:hover{
    background-color: var(--bg3);
    transform: scale(1.1);
    svg{
        .cloud{
            fill: #fff !important;
        }
    }
}
.selected-w-icon{
    transform: scale(1.2);
    background-color: var(--bg3);
}
.selected-w-icon:hover{
    transform: scale(1.2);
}
@media screen and (max-width: 959px) {
    .w-inner{
        max-width: 100%;
        width: 100%;
        max-height: 100%;
        height: 100vh;
        padding: 14px;
    }
    .popup-grid{
        grid-template-columns: 1fr;
    }
    .member-chip-text{
        max-width: 110px;
    }
    .icon-row{
        gap: 8px;
        width: 100%;
        justify-content: space-evenly;
    }
    .icon-container{
        width: 40px;
        height: 40px;
        min-width: 40px;
    }
    .w-inner.is-mobile{
        height: 100%;
    }
}
@media screen and (min-width: 960px) {
    .popup-grid{
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
