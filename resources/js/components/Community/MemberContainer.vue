<template>
    <div ref="container" @scroll="handleScroll" class="w-full h-full bg-[var(--bg2)] overflow-x-hidden overflow-y-auto">
        <div class="mem-header-section" :style="{'transform': `translateY(${offset}px)`}">
            <div class="post-header">
                <div class="post-search-wrap member-search-wrap">
                    <PostSearchBar @search-start="(word) => {keyword = word}" className="newChatMemberSearch" :customPlaceHolder="`コンタクト検索`"/>                
                </div>
            </div>
        </div>
        <div class="relative h-[calc(100%-80px)]">
            <div class="overflow-hidden h-full">
                <Transition name="modalFade">
                    <div class="member-loader" v-if="initialLoader">
                        <div id="loaderMini">
                            <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                        </div>
                    </div>
                </Transition>
                <div class="member-container" @scroll="scrollListen" @click="menu.close()" ref="memberContainerRef">            
                    <div v-for="(position, index) in positions" style="">
                        <p class="position-title" :class="{'first-position' : index == 0}">{{position.name}}</p>
                        <div v-if="position.employees" class="employee-container">
                            <MemberBlock 
                                v-for="member in position.employees"
                                :key="member.id"
                                :member="member" 
                            />
                        </div>
                    </div>
                </div>
                     
            </div>   
        </div>
        <FloatButton :order="2" @action="getTodayComments()" title="みんなのひとこと">
            <template #icon>
                <Comment size="20" style="height:20px; width: auto;" fill="black"/>
            </template>
        </FloatButton>   
        <FloatButton :order="1" @action="switchView">
            <template #icon>
                <SortIcon size="12" fill="black"/>
            </template>
        </FloatButton>
        
        <Teleport to="body">
            <Modal size="large" @close="today_members = []" v-if="today_members.length">
                <template #title>
                    みんなのひとこと
                </template>
                <template #content>
                    <DailyMemberMessages :members="today_members"/>
                </template>
            </Modal>
        </Teleport>
        
    </div>
</template>
<script setup lang="ts">
import { useResponsive } from '@/store/responsive';
import { computed, onActivated, onMounted, ref, useTemplateRef } from 'vue';
import HamBurger from '@/components/Global/HamBurger.vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useAuthUserStore } from '@/store/auth';
import MemberBlock from './MemberBlock.vue';
import { useMenuStore } from '@/store/menu';
import Modal from '../Global/Modal.vue';
import DailyMemberMessages from '../Global/DailyMemberMessages.vue';
import { useBadgeStore } from '@/store/badge';
import CommandButton from '../Global/CommandButton.vue';
import FloatButton from '../Global/FloatButton.vue';
import Comment from '../Icons/CommentIcon.vue';
import SortIcon from '../Icons/SortIcon.vue';
const keyword = ref('')
const route = useRoute()
const router = useRouter()
const prevScrollPosition = ref(0)
const container = useTemplateRef('container')
const offset = ref(0)
const menu = useMenuStore()
const today_members = ref<{
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
 const scrollPos = ref(0)
    const memberList = ref<Array<any>>([])
    const sortByShokkai = ref(false)
    const initialLoader = ref(true)
    const memberContainerRef = ref(null)
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const { toast, ping } = useDialog()
    const api = useApi()
    const badge = useBadgeStore()
    const sortableList = computed(() => {
        if (!keyword.value.trim()) {
            return memberList.value.map(position => ({
                ...position,
                employees: [...position.employees]
            }));
        }
        return memberList.value.map(position => {
            const filteredEmployees = position.employees.filter(employee => {
                const name = employee.name || '';
                const email = employee.work_email || '';
                const phone = employee.phone_number || '';
                const name_kana = employee.name_kana || '';

                return (
                    name.toLowerCase().includes(keyword.value.toLowerCase()) ||
                    email.toLowerCase().includes(keyword.value.toLowerCase()) ||
                    name_kana.toLowerCase().includes(keyword.value.toLowerCase()) ||
                    phone.includes(keyword.value)
                );
            });
            return {
                ...position,
                employees: filteredEmployees
            };
        });
    
    })
    const positions = computed(() => {
        const hasMembers = sortableList.value.filter( pos => pos.employees && pos.employees.length)
        return hasMembers.length ? hasMembers : []
    })
    onMounted(async() => {
        const val = JSON.parse(localStorage.getItem('memberViewType') || 'false')
        sortByShokkai.value = val == null ? false : val
        await getMembers()
        await markAsRead()
        badge.getTodayReadableBadge()
    })
 
    const switchView = () => {
        sortByShokkai.value = !sortByShokkai.value
        localStorage.setItem('memberViewType', sortByShokkai.value.toString())
        getMembers()    
        const type = sortByShokkai.value ? '職階別' : '役職別'
        toast(type)        
    }
    const getMembers = async() => {

        initialLoader.value = true
        const response = await api.post('/get_members_list', {byShokkai: sortByShokkai.value})
        memberList.value = response.members      
        today_members.value = response.today_unread_comments
        setTimeout(() => {
            initialLoader.value = false
        }, 200); 
    
    }
    const markAsRead = async() => {
        await api.get('/mark_condition_asread')
    }
    const scrollListen = (event) => {
        scrollPos.value = event.target.scrollTop
    }
    const getTodayComments = async() => {
        const response = await api.get('/get_today_comments')
        if (response) {
            today_members.value = response
        }
    }
onActivated(() => {
    offset.value = 0
})
const handleScroll = () => {
    if(!container.value || keyword.value.length) return
    const currentScrollPosition = container.value.scrollTop
    offset.value = currentScrollPosition > prevScrollPosition.value ? -95 : 0
    prevScrollPosition.value = currentScrollPosition   
}

</script>
<style>

.member-container{
    height: 100%;
    overflow: hidden auto;
    padding: 0 20px 20px 20px;
    color: var(--primary-color);
    position: relative;
}
.employee-container{
  display: flex;
  flex-wrap: wrap;
  flex-direction: row;
  overflow: visible;
  -webkit-overflow-scrolling: touch;
  background: var(--background-color);
}
.employee-card{
  width:33.33%;
  margin: 10px 0;
  transition: transform .1s;
  align-self: center;
  position: relative;
}
.employee-card > .employee-card-inner{
  margin:0 10px;
  background: var(--background-color);
}
.employee-card-body{
  padding: 10px;
  display: flex;
  align-items: center;
}
.employee-info{  
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  height: fit-content;
  margin: auto 0 auto 20px;
  font-size: 14px;
}
.employee-user-list{
  padding: 10px 0 0 0; 
  line-height: 17px;
  align-items: center;
  text-wrap: balance;
}
.position-title{
  font-size: 15px;
  padding: 25px 0;
}
.first-position{
    padding-top: 10px;
}
.shokkai-selector{
    display: flex;
    font-size: 12px;
    gap: 10px;
    flex-wrap: wrap;
}
.shokkai-selector > div{
    padding: 8px;
    background: var(--background-color);
    color: var(--primary-color);
    cursor:pointer;
    transition: all 0.2s;
}
.selected-shokkai{
    background: var(--primary-color) !important;
    color: var(--background-color) !important;
}
@media screen and (max-width: 959px) {
    .employee-card{
        width: 100%;
        transition: transform .1s;
    }  
}
</style>