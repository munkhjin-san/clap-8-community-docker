<template>
    <div class="post-root">
        <Transition name="modalFade">
            <div class="member-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="calendar-root-header" style="min-height: 60px;">
            <HamBurger v-if="responsive.mobile"/>
            <div class="calendar-search-wrap" id="memberSearchResultWindow" >
                <PostSearchBar 
                    @search-start="(word) => {keyword = word}"
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`メンバーを検索`"
                />               
            </div>         
        </div>
        <div class="member-container" @scroll="scrollListen" ref="memberContainerRef">
            
            <div v-for="(position, index) in positions" style="">
                <p class="position-title" :class="{'first-position' : index == 0}">{{position.name}}</p>
                <div v-if="position.employees" class="employee-container">
                    <MemberItem 
                        v-for="member in position.employees"
                        :key="member.id"
                        :member="member" 
                    />
                </div>
            </div>
        </div>   
        <div title="" class="createBoardButton" @click.stop="menu.setMenu( {name: 'memberSortMenu', id: 79})">
            <div style="width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;">       
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" viewBox="0 0 47 32" style="transform: rotate(90deg); margin-right: -5px;">
                    <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                </svg>
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" viewBox="0 0 47 32" style="transform: rotate(270deg);">
                    <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                </svg>
            </div>
        </div>      
        <Transition name="modalFade">
            <div id="memberSortMenu" class="boxMenu boardMenuIcon" v-if="menu.name == 'memberSortMenu' && menu.id == 79" style="top: auto;right: 50px;z-index:6;bottom: 50px;min-width: 100px;">
                <div class="boxMenuItems cursor-pointer" @click.stop="switchView(false)">
                    役職別
                    <span v-if="!sortByShokkai">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </span>
                </div>
                <div class="boxMenuItems cursor-pointer" @click.stop="switchView(true)">
                    職階別
                    <span v-if="sortByShokkai">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </span>
                </div>    
           
            </div>
        </Transition>
    </div>        
</template>
<script setup>
import PostSearchBar from '../Post/PostSearchBar.vue';
import HamBurger from '../Global/HamBurger.vue';
import MemberItem from './MemberItem.vue';
import { computed, onActivated, onMounted, ref } from 'vue';
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useApi } from '@/composables/api';
    const scrollPos = ref(0)
    const menu = useMenuStore()
    const responsive = useResponsive()
    const memberList = ref([])
    const keyword = ref('')
    const sortByShokkai = ref(false)
    const initialLoader = ref(true)
    const memberContainerRef = ref(null)
    const api = useApi()
    onActivated(() => {
        if(scrollPos.value && memberContainerRef.value){
            setTimeout(() => {
                memberContainerRef.value.scrollTo(0, scrollPos.value)
            }, 0);
        }
    })
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
    onMounted(() => {
        const val = JSON.parse(localStorage.getItem('memberViewType'))
        const flag = val == null ? false : val
        getMembers(flag)
    })
 
    const switchView = (val) => {
        menu.setMenu( {name: '', id: null})
        localStorage.setItem('memberViewType', val)
        if(sortByShokkai.value == val) {               
            return
        }
        getMembers(val)            
    }
    const getMembers = async(sort) => {
        sortByShokkai.value = sort
        initialLoader.value = true
        const response = await api.post('/get_members_list', {byShokkai: sort}, {
            loadingRef: initialLoader,
        })
        memberList.value = response      

    }
    const scrollListen = (event) => {
        scrollPos.value = event.target.scrollTop
    }

</script>
<style>
.member-loader{
    top: 60px;
    height: calc(100% - 60px);
    z-index: 7;
    background: var(--bg2);
    position: absolute;
    left: 0;
    width: 100%;
}
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
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  background: var(--background-color);
}
.employee-card{
  width:33.33%;
  margin: 10px 0;
  transition: transform .1s;
  align-self: center;
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