<script lang="ts" setup>
import { useApi } from '@/composables/api';
import { Post } from '@/interface/postInterface';
import { DateParser } from '@/utils/tools';
import { onMounted, ref, useTemplateRef, watch } from 'vue';
import PostFiles from '@/components/Post/PostFiles.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useMenuStore } from '@/store/menu';
import PostSearchPager from '@/components/Post/PostSearchPager.vue';
import Filter from '@/components/Icons/Filter.vue';
const api = useApi()
const posts = ref<{
    data: Post[],
    first_page_url: string,
    next_page_url: string | null,
    prev_page_url: string | null,
    last_page_url: string,
    current_page: number,
    last_page: number,
    total: number
}>({
    data: [], 
    first_page_url: '', 
    next_page_url: null, 
    prev_page_url: null, 
    last_page_url: '', 
    current_page: 1, 
    last_page: 0, 
    total: 0
})
const openedDetails = ref<number[]>([])
const refreshStatuses = ['申請中', '対応済み']
const searchQuery = ref<any>({
    status: []
})
const menu = useMenuStore()
const refreshheader = useTemplateRef('refreshheader')
const viewDetail = (id: number) => {
  const index = openedDetails.value.findIndex((value) => value === id)
  if (index === -1) {
    openedDetails.value.push(id)
  } else {
    openedDetails.value.splice(index, 1) // remove that element
  }
}

const getRefreshPosts = async (page?: number) => {
    const data = await api.get('/get_refresh_post', {
        ...searchQuery.value,
        page: page ?? 1,
    });
    if(data){
        posts.value = data
    }
};
const statusMap = (status: number) => {
    return status == 0 ? '申請中' : '対応済み'
}
const handleRefresh = (id: number) => {
    api.patch(`/post/refresh_approve/${id}`)
    getRefreshPosts()
}
const deleteRefresh = (id: number) => {
    api.del(`/post/refresh_delete/${id}`)
    getRefreshPosts()
}
watch(searchQuery.value, () => {
    getRefreshPosts()
}, { deep: true })
onMounted(() => {
    getRefreshPosts();
});
</script>
<template>
    <div class="admin-window">
        <div class="h-full w-full p-4">
            <table className="asset-table w-[calc(100%-40px)]">
            <thead ref="refreshheader">
                <tr>
                    <td>社員名</td>
                    <td>日付</td>
                    <td>タイトル</td>
                    <td>内容</td>
                    <td>利用金額</td>
                    <td>
                        <div class="relative">
                            <div class="cursor-pointer flex items-center gap-[5px] h-p" @click.stop="menu.setMenu({parent: 'statusPick'})">                                        
                                ステータス
                                <Filter class="filter-icon" size="12"/>
                            </div>
                            <Transition name="slidePop">
                                <div v-if="menu.parent == 'statusPick'" id="statusPick" class="shadow-me absolute right-0 bg-[var(--bg3)] text-[var(--primary-color)] flex flex-col gap-[10px] text-[12px] p-[10px]" :style="{'top': `${(refreshheader?.clientHeight ?? 30) - 4}px`}">
                                    <button class="text-[11px] min-w-[50px] bg-[var(--primary-color)] text-[var(--background-color)] h-[26px] px-[3px]" @click.stop="searchQuery.status = [], menu.close()">リセット</button>
                                    <div v-for="status, index in refreshStatuses">
                                        <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                                            <input type="checkbox" class="custom-f-checkbox" name="class-selector"  v-model="searchQuery.status" :value="index"/>
                                            {{ status }}
                                        </label>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </td>
                    <td>詳細</td>
                </tr>
            </thead>
            <tbody>    
                <template v-for="post in posts.data" :key="post.id">
                    <tr>
                        <td>{{ post.user.name }}</td>
                        <td>{{ DateParser(post.created_at) }}</td>
                        <td>{{ post.title }}</td>
                        <td>{{ post.content }}</td>
                        <td>{{ post.refresh_amount }}</td>
                        <td>{{  statusMap(post.status_flag) }}</td>
                        <td class="cursor-pointer select-none jump-link" @click="viewDetail(post.id)">
                            詳細
                        </td>
                    </tr>
                    <tr v-if="openedDetails.includes(post.id)">
                        <td colspan="6">
                            <div class="bg-[var(--bg2)] p-4 shadow-md space-y-4">

                            <!-- Title & Content -->
                            <div class="border-b border-[var(--calendarBorder)] pb-2">
                                <h2 class="font-semibold text-lg text-[var(--text1)]">
                                {{ post.title }}
                                </h2>
                                <p class="mt-1 text-[var(--text2)] leading-relaxed text-base">
                                {{ post.content }}
                                </p>
                            </div>

                            <!-- Files -->
                            <div class="space-y-3">
                                <div>
                                <span class="font-medium text-[var(--text1)]">リフレッシュ写真:</span>
                                <PostFiles class="mt-2" v-if="post.files.length" :items="post.files" />
                                </div>

                                <div>
                                <span class="font-medium text-[var(--text1)]">領収:</span>
                                <PostFiles class="mt-2" path="/post_receipts" v-if="post.receipts.length" :items="post.receipts" />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div v-if="post.status_flag !== 1" class="flex gap-3 pt-3 border-t border-[var(--calendarBorder)]">
                                <CommandButton
                                :buttons="[
                                    { title: '対応', action: () => handleRefresh(post.id) },
                                    { title: '削除', action: () => deleteRefresh(post.id) },
                                ]"
                                />
                            </div>
                            </div>
                        </td>
                    </tr>

                </template>
                
            </tbody>
            </table>
        </div>
        <div>
            <PostSearchPager 
                style="margin: 0;"
                :possiblePage="posts.last_page" 
                :activePath="posts.current_page" 
                @setNavi="(index) => getRefreshPosts(posts.current_page + index)"
                @setActivePage="(index) => getRefreshPosts(index)"/>
        </div>
    </div>
</template>
<style lang="scss" scoped>
    thead {
        background: var(--third-color);
        color: var(--background-color);
        position: sticky;
        top: 0px;
        z-index: 1;
    }
    .asset-header{
        display: flex;
        gap:20px;
    }
    .asset-table{
        background-color: var(--background-color);
        border-collapse: separate; 
        border-spacing: 0;
        color: var(--primary-color);
    }
    .asset-table td{
        padding: 10px;
        font-size: 13px;
        border-bottom: 1px solid rgb(102, 102, 102);
        border-right: 1px solid rgb(102, 102, 102);
    }
    table td:first-child {
        border-left: 1px solid rgb(102, 102, 102);
    }
    thead td:first-child{
        border-left: 1px solid rgb(102, 102, 102);
    }
    .filter-icon {
        opacity: 0;
    }
    .h-p:hover .filter-icon {
        fill: var(--background-color);
        opacity: 1;
    }
</style>