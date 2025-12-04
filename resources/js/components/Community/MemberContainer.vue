<template>
    <div ref="container" @scroll="handleScroll" class="w-full h-full bg-[var(--bg2)] overflow-x-hidden overflow-y-auto">
        <div class="mem-header-section" :style="{'transform': `translateY(${offset}px)`}">
            <div class="post-header">
                <div class="post-search-wrap">
                    <PostSearchBar @search-start="(word) => {keyword = word}" className="newChatMemberSearch" :customPlaceHolder="`コンタクト検索`"/>                
                </div>    
            </div>
        </div>
        <div class="relative h-[calc(100%-115px)]">
            <div>
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
                <div class="createBoardButton" :style="{position: 'fixed', bottom: auth?.user?.footer_view && responsive.mobile? '65px' : '20px'}" @click.stop="switchView">
                    <div style="width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;">       
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" viewBox="0 0 47 32" style="transform: rotate(90deg); margin-right: -5px;">
                            <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                        </svg>
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" viewBox="0 0 47 32" style="transform: rotate(270deg);">
                            <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                        </svg>
                    </div>
                </div>      
            </div>   
        </div>
        <Modal size="large" @close="today_members = []" v-if="today_members.length">
            <template #title>
                みんなのひとこと
            </template>
            <template #content>
                <DailyMemberMessages :members="today_members"/>
            </template>
        </Modal>
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
    height: calc(100% - 80px);
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