<template>
    <div class="post-root">
        <div v-if="hasPrivilage" title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="createWindow = true, editTarget = null">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <div style="height: 100%;width: 100%;overflow: auto;">
            <div class="post-header" style="position: sticky;top: 0;background: var(--bg2);z-index: 5;">
                <HamBurger v-if="responsive.mobile"/>
                <div class="post-search-wrap">
                    <PostSearchBar 
                        @searchStart="searchStart"
                        :searching="searching"
                        className="newChatMemberSearch" 
                        :customPlaceHolder="`お知らせを検索`" 
                    />
                </div>                
            </div>
            
            <div class="oshirase-root">
                <div style="max-width: 100%;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;font-size:14px;min-height:350px">
                    <router-link :to="`/notice/${notice.id}`" class="notice-item" style="line-height:40px;" :key="notice.id" v-for="notice in noticeListInbox">
                        <div style="vertical-align: middle;display:flex;margin:0 20px;width: 100%;overflow: hidden;">
                            <!-- <span :style="unreadNoticeTitle(notice.read_users)" class="notice-title">{{notice.title}}</span> -->
                            <span :style="{fontWeight: isUnread(notice)}" class="notice-title">{{notice.title}}</span>
                            <span style="margin-left:auto;padding-left:5px;font-size:12px;">{{momentMessage(notice.created_at)}}</span>
                        </div>  
                    </router-link>
                    <div v-if="noticeData && noticeData.total == 0" style="height: 100%;width: 100%;text-align: center;margin-top: 35vh;color: gray;">
                        {{ keyword ? '検索結果はありません。' : '現在お知らせはありません。'}}
                    </div>
                </div>
                <PostSearchPager 
                    v-if="possiblePage" 
                    :key="pagerKey"
                    :possiblePage="possiblePage" 
                    :activePath="activePath" 
                    @setActivePage="setActivePage"
                    @setNavi="setNavi"
                    style="margin: auto 0px 15px;padding-top: 15px;"
                />
            </div>
            
        </div>
        <router-view v-slot="{ Component }">
            <Transition name="modalFade">
                <component 
                    :is="Component" 
                    :hasPrivilage="hasPrivilage"
                    :getNotices="getNotices"
                    @edit="editNotice"
                    @delete="deleteNoticeConfirm"
                />
            </Transition>
        </router-view>
        <Transition name="modalFade">
            <NoticeCreate v-if="createWindow" @close="closeModal" :editTarget="editTarget"/>
        </Transition>
    </div>
</template>
<script setup>
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import moment from 'moment';
import PostSearchPager from '../Post/PostSearchPager.vue';
import NoticeCreate from './NoticeCreate.vue'
import { computed, onMounted, ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const router = useRouter()
    const noticeData = ref(null)
    const pagerKey = ref(0)
    const page = ref(1)
    const keyword = ref('')
    const searching = ref(0)
    const createWindow = ref(false)
    const editTarget = ref(null)
    const { confirm } = inject('dialog')
    onMounted(() => {
        getNotices()
    })
    const activePath = computed(() => {
        return noticeData.value && noticeData.value.current_page ? noticeData.value.current_page : 0
    })
    const hasPrivilage = computed(() => {
        return auth.activeUser ? [540,690,610, 516, 519, 517, 518, 526, 494, 604, 765].includes(auth.activeUser.id) : false
    })
    const possiblePage = computed(() => {
        return noticeData.value && noticeData.value.last_page ? noticeData.value.last_page : 0
    })
    const noticeListInbox = computed(() => {
        return noticeData.value && noticeData.value.data ? noticeData.value.data : []
    })
    
    const editNotice = (val) => {
        editTarget.value = val
        createWindow.value = true
        router.push({name: 'notice'})
    }
    const deleteNoticeConfirm = async(val) => {
        const answer = await confirm('お知らせを削除しますか。')
        if(!answer.value) return
        deleteNotice(val)
    }
    const deleteNotice = async(val) => {
        router.push({name: 'notice'})
        await axios.delete(`/notice_delete?id=${val.id}`)
        getNotices(page.value)
    }
    const closeModal = (val) => {
        createWindow.value = false;
        if(val){
            page.value = 1
            getNotices(page.value)
        }

    }
    const searchStart = (word) => {
        keyword.value = word
        getNotices()
    }
    const setActivePage = (pagenum) => {
        page.value = pagenum
        getNotices(pagenum)
    } 
    const setNavi = (val) => {
        page.value = page.value + val
        getNotices()
    }
    const isUnread = (notice) => {
        // const line = moment('2023-10-01')

        // if()
        const list = notice.readers.map(ob => ob.id)
        const read = list.includes(auth.activeUser.id)
        return read ? '400' : '600'
    }
    const momentMessage = (date) => {
        moment.locale('ja')
        return moment(date).isSame(moment(), 'day') ? 
        moment(date).format('HH:mm') : 
        moment(date).isSame(moment(), 'year') ? 
        moment(date).format('M月D日') : 
        moment(date).format('YYYY年M月D日')       
    }
    const unreadNoticeTitle = (list) => {
                
         
        var userCheck = JSON.parse("[" + list + "]"); 
        if(!list || list == null || userCheck.indexOf(auth.activeUser.id) == -1){
            return 'font-weight: 600;'
        }  
        
        
    }
       
    const getNotices = () => {
        const key = keyword.value ? `&keyword=${keyword.value}` : ''
        if(key){
            searching.value = 1
        }
        axios.get(`/get_notices?page=${page.value}${key}`).then(response => {
            noticeData.value = response.data
            searching.value = 0
        })
    }

  

</script>
<style>
.oshirase-root{
    background: var(--background-color);
    margin: 0 20px;
    display: flex;
    flex-direction: column;
    min-height: calc(100% - 80px);
}
.notice-title {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}
.noticeRow{
    cursor: pointer;
    background: var(--background-color);
    color:var(--primary-color);
    transition: color 0.1s, background-color 0.1s ;
}
.noticeRow:hover{
    /* background: red !important;
    color:var(--background-color) !important;
    border-radius: 2px; */
    background-color: black;
    text-decoration: none;
    font-weight: 400;
    background-color: red !important;
}
.notice-item{
    cursor: pointer;
    white-space: nowrap;
    position: relative;
    gap: 10px;
    overflow: hidden;
    height: 40px;
    display: flex;
    min-height: 40px;
    text-decoration: none;
    color: var(--primary-color);
    justify-content: space-between;
}
.notice-item:hover{
    background: var(--primary-color);
    color: var(--background-color);
    text-decoration: none;
    font-weight: 400;
}
</style>