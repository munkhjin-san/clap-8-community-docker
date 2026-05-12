<template>
    <div class="post-root" v-if="auth.id == auth.activeUser.id">
        <div class="post-header">
            <HamBurger v-if="responsive.mobile"/>
            <div class="post-search-wrap">
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`${appNameJp}検索`" 
                    @focus="searchWindow = true"
                />                
            </div>            
        </div>
       
        <Transition name="modalFade">
            <PostSearchWindow 
                v-if="searchWindow"
                :appName="appName"
                :appTitle="appNameJp"
                @closePostSearch="searchWindow = false"
            />
        </Transition> 
        <Transition name="modalFade">                              
            <PostCreate 
                v-if="create"
                :key="componentKey" 
                :currentStatus="null" 
                :editTarget="editTarget"
                :sharedFrom="sharedFrom"
                @postFinish="(flag, id) => postFinish(flag, id)"
                :filesToShare="filesToShare"  
                :getQuery="getQuery"
                :appName="String(appName)"
                :appNameJp="appNameJp"                
            />            
        </Transition>
          
        <div class="post-container scrollable" @scroll="scrollListen">
            
            <!-- <div class="active-query-shell" :class="{ 'active-query-shell--visible': hasQuery }">
                <div class="active-query-shell__inner">
                    <transition-group tag="div" name="badgeAnim" class="active-query-row">
                        <div v-if="getQuery?.app_type" :key="`type_${getQuery.app_type}`" class="active-query">
                            <PostIcon :which="getQuery?.app_type" size="20"/>
                            {{ getQuery?.app_type ? apps[Number(getQuery.app_type)] : ''}}
                            <div @click="router.push({name: appName})" style="cursor:pointer; display: flex; align-items: center;">
                                <CloseIcon size="10" />
                            </div>
                        </div>
                        <div v-if="getQuery?.member" :key="`member_${getQuery.member}`" class="active-query">
                            <div>{{ getQuery?.member }}</div>
                            <div @click="router.push({name: appName})" style="cursor:pointer; display: flex; align-items: center;">
                                <CloseIcon size="10" />
                            </div>
                        </div>
                        <div v-if="getQuery?.main_category" :key="`main_category_${getQuery.main_category}`" class="active-query">
                            <div>{{ getQuery.main_category }}</div>
                            <div @click="router.push({name: appName, query: getQuery?.app_type ? { app_type: getQuery.app_type } : {}})" style="cursor:pointer; display: flex; align-items: center;">
                                <CloseIcon size="10" />
                            </div>
                        </div>
                        <div v-if="getQuery?.sub_category" :key="`sub_category_${getQuery.sub_category}`" class="active-query">
                            <div>{{ getQuery.sub_category }}</div>
                            <div @click="router.push({name: appName, query: { ...(getQuery?.app_type ? { app_type: getQuery.app_type } : {}), main_category: getQuery.main_category }})" style="cursor:pointer; display: flex; align-items: center;">
                                <CloseIcon size="10" />
                            </div>
                        </div>
                    </transition-group>
                </div>
            </div> -->

            <!-- Category / Type filter strip -->
            <div class="cat-filter-strip" :class="{ 'cat-filter-strip--busy': queryRefreshing }">
                <!-- Post app: type filter -->
                <div v-if="appName == 'post'" class="cat-filter-row">
                    <router-link :to="`/${appName}`" :class="['cat-chip', { 'cat-chip--active': !getQuery?.app_type }]">
                        すべて
                    </router-link>
                    <router-link :to="`/${appName}?app_type=0`" :class="['cat-chip', { 'cat-chip--active': getQuery?.app_type == '0' }]">
                        <PostIcon which="0" size="16"/>
                        {{ apps[0] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=2`" :class="['cat-chip', { 'cat-chip--active': getQuery?.app_type == '2' }]">
                        <PostIcon which="2" size="16"/>
                        {{ apps[2] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=6`" :class="['cat-chip', { 'cat-chip--active': getQuery?.app_type == '6' }]">
                        <PostIcon which="6" size="16"/>
                        {{ apps[6] }}
                    </router-link>
                </div>

                <!-- Challenge category filter -->
                <template v-if="showCategoryFilter">
                    <div class="cat-filter-row">
                        <router-link
                            v-for="cat in challengeCategories"
                            :key="cat.label"
                            :to="buildCategoryPath(activeMainCategory === cat.label ? null : cat.label)"
                            :class="['cat-chip', { 'cat-chip--active': activeMainCategory === cat.label }]"
                        >
                            {{ cat.label }}
                        </router-link>
                    </div>
                    <div class="cat-filter-subshell" :class="{ 'cat-filter-subshell--visible': !!activeChallengeCategory }">
                        <div class="cat-filter-subshell__inner">
                            <Transition name="subRowSwap" mode="out-in">
                                <div
                                    v-if="activeChallengeCategory"
                                    :key="activeMainCategory ?? 'challenge-subcategories'"
                                    class="cat-filter-row cat-filter-row--sub"
                                >
                                    <router-link
                                        v-for="sub in activeChallengeCategory.subcategories"
                                        :key="sub"
                                        :to="buildCategoryPath(activeMainCategory, activeSubCategory === sub ? null : sub)"
                                        :class="['cat-chip', 'cat-chip--sub', { 'cat-chip--active': activeSubCategory === sub }]"
                                    >
                                        {{ sub }}
                                    </router-link>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </template>
            </div>
            
            <transition-group name="slidePop" tag="div" class="post-record-list" :class="{ 'post-record-list--refreshing': queryRefreshing }">
                <PostRecord 
                    v-for="(record, index) in records"
                    :key="`${record?.id}_${index}`"
                    :record="record"
                    :appName="String(appName)"
                    :appNameJp="appNameJp"
                    :apps="apps"  
                    @setChargeTarget=" val => setChargeTarget(val)"
                    @setCommentCount="setCommentCount"
                    @setClap="setClap"
                    @editRecord="editRecord"
                    @updateStatus="val => updateTarget = val"
                    @deleteRecord="deleteRecordConfirm"
                    @set-entry-data="val => entryData = val"
                    
                />                
            </transition-group>
        </div>  
            

        
      
        <div title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="newRecord">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <Transition name="modalFade">
            <div v-if="infiniteLoader" style="position: absolute;left: 0;right: 0;bottom: 25px;margin: auto;width: fit-content;">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>
 
        <router-link v-if="hasQuery" :to="`/${String(appName)}`" class="post-list-reset">一覧表示に戻す</router-link>
        <Transition name="modalFade">
            <Charge 
                v-if="chargeTarget" 
                @close="closeCharge" 
                :chargeTarget="chargeTarget"
                :isMini="isMini"
            />
        </Transition>
        <Transition name="modalFade">
            <Status 
                v-if="updateTarget" 
                :record="updateTarget"
                @close="closeStatus" 
            />
        </Transition>
        <Transition name="modalFade">
            <PostEntryCreate 
                v-if="entryData.record" 
                :record="entryData.record" 
                :edit-data="entryData.editData"
                @close="closeEntryCreate"
            />
        </Transition>
        <Transition name="modalFade">
            <PostEntryRanking :ranking="topRecords" v-if="viewFullRanking" @close="viewFullRanking = false"/>
        </Transition>
    </div>
    <div v-else style="height: 100%;width: 100%;">
        <div v-if="responsive.mobile" style="min-height: 60px;display: flex;align-items: center">
            <HamBurger/>
        </div>        
        <div style="color:var(--primary-color);height: 100%;width: 100%;text-align: center;justify-content: center;display: flex;align-items: center;flex-direction: column;">
            <p>アクセス権限ありません。</p>
            <router-link class="l-button" style="margin: 30px 0 70px 0;" to="/board">チャットへ戻る</router-link>
        </div>        
    </div>
</template>
<script setup lang="ts">
import HamBurger from '../Global/HamBurger.vue';
import PostRecord from './PostRecord.vue';
import PostCreate from './PostCreate.vue';
import PostSearchBar from './PostSearchBar.vue'
import Charge from './Charge.vue';
import Status from './Status.vue';
import PostSearchWindow from './PostSearchWindow.vue'
import PostIcon from './PostIcon.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { LocationQueryValue, useRoute, useRouter } from 'vue-router'
import { provide } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useSharingDataStore } from '@/store/sharingData'
import { useBadgeStore } from '@/store/badge'
import { instance } from '@/utils/broadcaster';
import { onUnmounted } from 'vue';
import { useApi } from '@/composables/api';
import { Post, PostEntry, PostQuery, TopEntryUser } from '@/interface/postInterface';
import { PostMethodsKey } from '@/interface/keys';
import PostEntryCreate from './PostEntryCreate.vue';
import PostEntryRanking from './PostEntryRanking.vue';
import CloseIcon from '../Form/CloseIcon.vue';
import { challengeCategories } from '@/utils/challengeCategory';
    const badge = useBadgeStore()
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const postList = ref<Post[]>([])
    const create = ref(false)
    const componentKey = ref(0)
    const sharedFrom = ref(null)
    const filesToShare = ref(null)
    const hasQuery = computed(() => {
        const q = route.query
        return !!(q.app_type || q.member || q.search_tags || q.main_category || q.sub_category)
    })
    const chargeTarget =  ref<number | null>(null)
    const isMini = ref(false)
    const editTarget = ref<Post | null>(null)
    const updateTarget = ref<Post | null>(null)
    const searchWindow = ref(false)
    const route = useRoute()    
    const router = useRouter()
    const infiniteLoader = ref(false)
    const queryRefreshing = ref(false)
    const apps = ['ナイス', 'ナレッジ', 'チャレンジ', 'ノート', 'ヘルプ', 'グラリンピック', 'リフレッシュ']
    const api = useApi()
    const viewFullRanking = ref(false)
    const entryData = ref({
        record: <Post | null>null,
        editData: <PostEntry | null>null,
    })
    const topRecords = ref<TopEntryUser[]>([])
    const records = computed(() =>{
        return postList.value && postList.value.length ? postList.value : []
    })
    const appName = computed(() => {
        return route.name as string
    })
    const appNameJp = computed(() => {
        return appName.value == 'challenge' ? 'チャレンジ' : appName.value == 'post' ? 'ポスト' : ''
    })
    const skeletonCount = computed(() => {
        return responsive.mobile ? 6 : 20
    })
    const activeMainCategory = computed(() => {
        return route.query.main_category ? String(route.query.main_category) : null
    })
    const activeSubCategory = computed(() => {
        return route.query.sub_category ? String(route.query.sub_category) : null
    })
    const activeChallengeCategory = computed(() => {
        return challengeCategories.find(c => c.label === activeMainCategory.value) ?? null
    })
    const showCategoryFilter = computed(() => {
        return appName.value === 'challenge' || (appName.value === 'post' && getQuery.value?.app_type === '2')
    })
    let feedRequestId = 0
    const buildCategoryPath = (main: string | null, sub: string | null = null): string => {
        const params = new URLSearchParams()
        if (appName.value === 'post' && getQuery.value?.app_type) {
            params.set('app_type', String(getQuery.value.app_type))
        }
        if (main) params.set('main_category', main)
        if (sub) params.set('sub_category', sub)
        const qs = params.toString()
        return `/${appName.value}${qs ? '?' + qs : ''}`
    }
    const setChargeTarget = (record: Post) => {
        chargeTarget.value = record.id
        isMini.value = record.mini ? true : false
    }
    onMounted(() => {
        if(route.meta.data && Array.isArray(route.meta.data) && route.meta.data.length){
            postList.value = route.meta.data as Post[];
            const last_chargeable = badge.post.last_chargeable_ids
            if (last_chargeable.length) {
                postList.value = [...postList.value].sort((a, b) => {
                    const aPriority = last_chargeable.includes(a.id) ? 0 : 1
                    const bPriority = last_chargeable.includes(b.id) ? 0 : 1
                    return aPriority - bPriority
                })
            }
        }else{
            const query = getQuery.value
            refreshFeed(query)
        }
        instance.on('post:new', postSocketHandler)



        setTimeout(() => {
            if(route.name && (typeof route.name === 'string' && (route.name.includes('challenge') || route.name.includes('post'))) && !auth.isPartner && appName.value){
                badge.updatePostBadge(appName.value.toString())
            }            
        }, 2000);
        if(sharingData.active){
            newRecord()
        } else {
            openCreateFromRoute()
        }
    })
    onUnmounted(() => {
        instance.off('post:new', postSocketHandler)
    })
    watch(() => route.query.create, (value, oldValue) => {
        if(value && value !== oldValue){
            openCreateFromRoute()
        }
    })
    watch(
        () => ({
            app_type: route.query.app_type,
            main_category: route.query.main_category,
            sub_category: route.query.sub_category,
            member: route.query.member,
            search_tags: route.query.search_tags,
        }),
        (newQ, oldQ) => {
            const changed = JSON.stringify(newQ) !== JSON.stringify(oldQ)
            if (changed && !route.query.id && !route.query.create) {
                refreshFeed(getQuery.value)
            }
        }
    )
    const getTopRecords = async () => {
        const data = await api.post('/get_top_posts')
        topRecords.value = data
    }
    const postSocketHandler = (data:any) =>{
        console.log(data)
        const payload = data
      
        if(payload && payload?.app_name == appName.value && !hasQuery.value){
            console.log('payload', payload)
            const query = {
                id: payload.record_id,
                search_tags: null
            }
            fetchPosts(query, payload.record_id)
        }
    }
    
    const deleteRecordConfirm = async(record:Post) => {
        const data = await api.post('/delete_post', {
            path: appName.value,
            id: record.id
        }, {
            ask: `${appNameJp.value}を削除しますか。`,
            toast: '削除しました。',
        })
        postList.value = postList.value.filter(ob => ob.id !== data)
    }
    const scrollListen = (event: Event) => {
        const target = event.currentTarget as HTMLElement;
        const percent = 100 * target.scrollTop / (target.scrollHeight - target.clientHeight);  
        if(percent > 99){          
            if (infiniteLoader.value || queryRefreshing.value){
                return;
            }                       
            infiniteLoader.value = true;
            let query = getQuery.value
            fetchPosts(query, undefined, { requestId: feedRequestId })                                   
        }
    }
    const closeStatus = (id?: number) => {
        updateTarget.value = null
        if(id){
            let query:Record<string, any> = getQuery.value
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            fetchPosts(query, id)
        }
    }
    const editRecord = (record: Post) => {
        editTarget.value = record
        create.value = true
    }
    const closeCharge = (id?: number) => {
        chargeTarget.value = null
        if(id){                
            let query:Record<string, any> = getQuery.value
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            fetchPosts(query, id)
        }
        
    }
    const closeEntryCreate = (flag: boolean, id?:number) => {
        entryData.value = { record: null, editData: null }
        if(id){                
            let query = getQuery.value
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id.toString()
            }
            fetchPosts(query, id)
        }
    }
    const getQuery = computed(():PostQuery => {
        const id = route.query.hasOwnProperty('id') && route.query.id ? route.query.id : null
        const search_tags = route.query.hasOwnProperty('search_tags') && route.query.search_tags ? route.query.search_tags : null
        const search_member = route.query.hasOwnProperty('member') && route.query.member ? route.query.member : null
        const search_type = route.query.hasOwnProperty('app_type') && route.query.app_type ? route.query.app_type : null
        const main_category = route.query.hasOwnProperty('main_category') && route.query.main_category ? route.query.main_category : null
        const sub_category = route.query.hasOwnProperty('sub_category') && route.query.sub_category ? route.query.sub_category : null
        const query = {
            id: id,
            search_tags: search_tags,
            member: search_member,
            app_type: search_type,
            main_category: main_category,
            sub_category: sub_category,
        }
        return query
    })
    
    const openTagPicker = () => {}
    const closeTagPicker = () => {}
    
    const postFinish = (flag: boolean, id?: number) => {
        create.value = false
        editTarget.value = null
        if(flag && id){
            const query: Record<string, any> = {
                id: id,
                search_tags: null
            }
            fetchPosts(query, id)
        }
    }
    const newRecord = () => {
        create.value = true
    }
    const openCreateFromRoute = () => {
        if(route.query.create && !create.value){
            newRecord()
        }
    }
    const refreshFeed = (query: Record<string, any>) => {
        queryRefreshing.value = true
        feedRequestId += 1
        return fetchPosts(query, undefined, { reset: true, requestId: feedRequestId })
    }
    const fetchPosts = async (
        query: Record<string, any>,
        replace?:number,
        options: { reset?: boolean; requestId?: number } = {}
    ) => {
        const { reset = false, requestId = feedRequestId } = options

        try {
            const data = await api.post('/get_posts', {
                path: appName.value,
                query: query,
                skip: reset || replace ? 0 : postList.value.length,
            })

            if (requestId !== feedRequestId && (reset || !replace)) {
                return
            }

            if (reset) {
                postList.value = data
                return
            }

            if(replace ){
                const index = postList.value.findIndex(ob => ob.id == replace)
                if(index > -1){
                    postList.value[index] = data[0]
                }else{
                    postList.value.unshift(data[0])
                }
                return
            }

            data.forEach((responseItem: Post) => {
                const existingPost = postList.value.find((post) => post.id === responseItem.id);
                if (existingPost) {
                    Object.assign(existingPost, responseItem);
                } else {
                    postList.value.push(responseItem);
                }
            });
        } finally {
            setTimeout(() => {
                if (requestId === feedRequestId) {
                    queryRefreshing.value = false
                }
                infiniteLoader.value = false
            }, 180);
        }
    }
    const setCommentCount = (num: number, id: number) => {
        const index = postList.value.findIndex(item => item.id === id);
        if(index > -1){
            postList.value[index].comments_count = num
        }
    }
    const setClap = (id: number) => {
        if(id){
            const idStr = String(id)
            let query = getQuery.value
            if(!query.hasOwnProperty('id') || query.id !== idStr){
                query['id'] = idStr
            }
            fetchPosts(query, id)
        }
    }    
    const sanitized = (text: string | LocationQueryValue[] | null) => {
        if (Array.isArray(text)) {
            return text.map(t => t ? String(t).replace(/#|♯|＃/g, '') : '').join(',');
        }
        return text ? String(text).replace(/#|♯|＃/g, '') : '';
    }
    provide(PostMethodsKey, {
        commentCount: (num, id) => setCommentCount(num, id),
        updateRecord: (record) => {
            const index = postList.value.findIndex(item => item.id === record.id)
            if (index > -1) {
                postList.value[index] = record
            }
        },
    })

</script>
<style scoped lang="scss">
.active-query-shell {
    display: grid;
    grid-template-rows: 0fr;
    opacity: 0;
    transform: translateY(-6px);
    transition: grid-template-rows 0.24s ease, opacity 0.2s ease, transform 0.24s ease;
}

.active-query-shell--visible {
    grid-template-rows: 1fr;
    opacity: 1;
    transform: translateY(0);
}

.active-query-shell__inner {
    overflow: hidden;
}

.active-query-row {
    position: relative;
    margin: 0 20px;
    padding: 4px 0 6px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    min-height: 36px;
}

.badgeAnim-move,
.badgeAnim-enter-active,
.badgeAnim-leave-active {
    transition: transform 0.24s ease, opacity 0.18s ease;
}
.badgeAnim-enter-from,
.badgeAnim-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.96);
}
.badgeAnim-leave-active {
    position: absolute;
}

.active-query{
    font-size: 13px;
    background: color-mix(in srgb, var(--primary-color) 8%, transparent);
    color: var(--primary-color);
    padding: 6px 12px;
    border-radius: 20px;
    width: fit-content;
    display: flex;
    gap: 10px;
    align-items: center;
    position: relative;
    will-change: transform, opacity;
}

/* Category filter strip */
.cat-filter-strip {
    padding: 8px 20px 4px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.cat-filter-strip--busy {
    opacity: 0.82;
}

.cat-filter-row {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}

.cat-filter-row--sub {
    padding-left: 4px;
    border-left: 2px solid color-mix(in srgb, var(--primary-color) 20%, transparent);
}

.cat-filter-subshell {
    display: grid;
    grid-template-rows: 0fr;
    opacity: 0;
    transition: grid-template-rows 0.24s ease, opacity 0.2s ease;
}

.cat-filter-subshell--visible {
    grid-template-rows: 1fr;
    opacity: 1;
}

.cat-filter-subshell__inner {
    overflow: hidden;
}

.subRowSwap-enter-active,
.subRowSwap-leave-active {
    transition: opacity 0.2s ease, transform 0.24s ease;
}

.subRowSwap-enter-from,
.subRowSwap-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

.cat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    border: 1px solid color-mix(in srgb, var(--primary-color) 20%, transparent);
    background: transparent;
    color: var(--primary-color);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background 0.2s ease-in-out, border-color 0.2s ease-in-out,
                color 0.2s ease-in-out, transform 0.15s ease, opacity 0.15s ease;
    line-height: 1.4;
    will-change: transform, opacity;
}

.cat-chip :deep(.side-app-icon) {
    transition: fill 0.2s ease-in-out;
}

.cat-chip:hover {
    background: color-mix(in srgb, var(--primary-color) 8%, transparent);
    border-color: color-mix(in srgb, var(--primary-color) 40%, transparent);
    transform: translateY(-1px);
}

.cat-chip--active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--background-color);
    font-weight: 600;
    transform: translateY(-1px);
}

.cat-chip--active:hover {
    background: var(--primary-color);
    opacity: 0.85;
}

.cat-chip--active :deep(.side-app-icon) {
    fill: var(--background-color);
}

.cat-chip--sub {
    font-size: 11px;
    padding: 4px 10px;
    opacity: 0.85;
}

.post-record-list {
    display: flex;
    flex-direction: column;
    gap: 40px;
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.post-record-list--refreshing {
    opacity: 0.7;
    transform: translateY(3px);
    pointer-events: none;
}

@media screen and (max-width: 959px) {
    .cat-filter-strip {
        padding: 8px 14px 4px;
    }
}
</style>
