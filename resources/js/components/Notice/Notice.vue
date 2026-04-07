<template>
    <div>
        <FloatButton 
            class="fixed" 
            @action="createWindow = true, editTarget = null"
            v-if="auth.isAdmin || auth.isBoss"
        >
            <template #icon>
                <AddIcon/>
            </template>
        </FloatButton>
        <div class="mt-5"> 
            <div class="w-fit min-w-[400px] under960:w-full under960:min-w-full">
                <div class="mx-[20px] mb-5">
                    <PostSearchBar 
                        @searchStart="searchStart"
                        :searching="searching"
                        className="newChatMemberSearch" 
                        :customPlaceHolder="`お知らせを検索`" 
                    />
                </div>                
            </div>
            
            <div class="oshirase-root">
                <div class="max-w-full text-ellipsis white-space-nowrap overflow-hidden text-[14px]">
                    <router-link :to="{name: 'dashboard', params: { type: 'notice'}, query: {notice_id: notice.id}}" class="notice-item" :key="notice.id" v-for="notice in noticeListInbox">
                        <div class="flex mx-2 w-full overflow-hidden">
                            <span :style="{fontWeight: isUnread(notice)}" class="notice-title">{{notice.title}}</span>
                            <span class="ml-auto pl-2 text-[11px] text-[gray]">{{noticeDate(notice.created_at)}}</span>
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
            <Transition name="modalFade">
                <NoticeDetail 
                    v-if="activeNotice" 
                    :item="activeNotice"
                    :getNotices="getNotices"
                    @edit="editNotice"
                    @delete="deleteNoticeConfirm"
                />
            </Transition>
        <Transition name="modalFade">
            <NoticeCreate v-if="createWindow" @close="closeModal" :editTarget="editTarget"/>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import PostSearchBar from '../Post/PostSearchBar.vue';
import PostSearchPager from '../Post/PostSearchPager.vue';
import NoticeCreate from './NoticeCreate.vue'
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth'
import { customParser } from '@/utils/tools';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import FloatButton from '../Global/FloatButton.vue';
import AddIcon from '../Form/AddIcon.vue';
import { NoticeRecord } from '@/interface/notice';
import NoticeDetail from './NoticeDetail.vue';
    const auth = useAuthUserStore()
    const router = useRouter()
    const noticeData = ref<
        {
            data: NoticeRecord[]
            current_page: number
            last_page: number
            total: number
        } | null
    >(null)
    const pagerKey = ref(0)
    const page = ref(1)
    const keyword = ref('')
    const searching = ref(0)
    const createWindow = ref(false)
    const editTarget = ref<NoticeRecord | null>(null)
    const api = useApi()
    const route = useRoute()
    onMounted(() => {
        getNotices()
    })

    const activeNotice = computed(() => {
        const notice_id = route.query.notice_id
        if(!notice_id || Array.isArray(notice_id)) return null
        return noticeData.value?.data.find(n => n.id === Number(notice_id)) || null
    })
    const activePath = computed(() => {
        return noticeData.value && noticeData.value.current_page ? noticeData.value.current_page : 0
    })
    const possiblePage = computed(() => {
        return noticeData.value && noticeData.value.last_page ? noticeData.value.last_page : 0
    })
    const noticeListInbox = computed(() => {
        return noticeData.value && noticeData.value.data ? noticeData.value.data : []
    })
    
    const editNotice = (val: NoticeRecord) => {
        editTarget.value = val
        createWindow.value = true
        router.push({name: 'notice'})
    }
    const deleteNoticeConfirm = async(val: NoticeRecord) => {
        await api.del(`/notice_delete`, {id: val.id}, {
            ask: 'お知らせを削除しますか。',
            toast: 'お知らせを削除しました。',
        })
        router.push({name: 'notice'})
        getNotices(page.value)
    }
    const closeModal = (val: boolean) => {
        createWindow.value = false;
        if(val){
            page.value = 1
            getNotices(page.value)
        }

    }
    const searchStart = (word: string) => {
        keyword.value = word
        getNotices()
    }
    const setActivePage = (pagenum: number) => {
        page.value = pagenum
        getNotices(pagenum)
    } 
    const setNavi = (val: number) => {
        page.value = page.value + val
        getNotices()
    }
    const isUnread = (notice: NoticeRecord) => {
        const read = notice.read
        return read ? '400' : '600'
    }
    const noticeDate = (date: string) => {
        
        const instance = customParser(date)
        const now = DateTime.now()
        return instance.hasSame(now, 'day') ? 
            instance.toFormat('HH:mm') : 
            instance.hasSame(now, 'year') ? 
            instance.toFormat('M月d日') : 
            instance.toFormat('yyyy年M月d日')

    }

       
    const getNotices = async (_pageNum: number = 1) => {
        const key = keyword.value ? `&keyword=${keyword.value}` : ''
        if(key){
            searching.value = 1
        }
        const data = await api.get(`/get_notices?page=${page.value}${key}`)
        console.log('noticeData', data)
        noticeData.value = data
        searching.value = 0
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
    display: flex;
    min-height: 40px;
    text-decoration: none;
    color: var(--primary-color);
    justify-content: space-between;
    border-bottom: solid thin var(--panel-separate);
    display: flex;
    align-items: center;
    padding: 5px 0;
}
.notice-item:hover{
    background: var(--bg3);
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 400;
}
</style>