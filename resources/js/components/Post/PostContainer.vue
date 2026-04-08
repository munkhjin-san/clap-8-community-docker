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
            
            <div v-if="hasQuery" style="height: auto;margin: 0 20px;display: flex;gap: 20px;">
                <div v-if="getQuery?.app_type" class="active-query">
                    <PostIcon v-if="Number(getQuery?.app_type) != 6" :which="getQuery?.app_type" size="20"/>
                    {{ getQuery?.app_type ? apps[Number(getQuery.app_type)] : ''}}
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div v-if="getQuery?.member" class="active-query">
                    <div>{{ getQuery?.member }}</div>
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div v-if="getQuery?.search_tags" class="active-query"> 
                    <div>#{{ sanitized(getQuery.search_tags) }}</div>
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div v-else>
                <div v-if="appName == 'post'" style="display: flex; gap: 20px;font-size: 14px;flex-wrap: wrap;margin: 0 20px">
                    <router-link :to="`/${appName}?app_type=0`" :class="['pt-selector']">
                        <PostIcon which="0" size="20"/>
                        {{ apps[0] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=2`" :class="['pt-selector']">
                        <PostIcon which="2" size="20"/>
                        {{ apps[2] }}
                    </router-link>
                    <!-- <router-link :to="`/${appName}?app_type=6`" :class="['pt-selector']">
                        <PostIcon which="6" size="20"/>
                        {{ apps[6] }}
                    </router-link> -->
                </div>                
            </div>
            <div class="p-tag-container">
                <div class="tag-strip">
                    

                    <div v-if="tagLoading == 0" class="p-tag-wrap p-tag-wrap--skeleton">
                        <div
                            v-for="num in skeletonCount"
                            :key="num"
                            class="tag-skeleton"
                            :style="{ width: randomWidth() }"
                        ></div>
                    </div>

                    <div v-else class="tag-strip__body">
                        

                        <div class="p-tag-wrap">
                            <router-link
                                v-for="tag in previewTags"
                                :key="tag.id"
                                :to="tagLink(tag.text)"
                                :class="['tag-chip', { 'tag-chip--active': isCurrentTag(tag.text) }]"
                            >
                                <span>#{{ sanitized(tag.text) }}</span>
                                <span>({{ tagCount(tag) }})</span>
                            </router-link>
                        </div>
                    </div>
                    <div class="tag-strip__footer" @click="openTagPicker">
                        <div title="すべて見る" class="selector-accordion-el">
                            <Back :class="['selector-accordion-inactive' , {'selector-accordion-active' : topTags.expanded}]" v-show="tagLoading > 0" size="11" fill="var(--primary-color)"/>
                        </div>
                    </div>
                </div>
            </div>
            
            <transition-group name="slidePop" tag="div" style="display: flex;flex-direction: column;gap: 40px;">
                <PostRecord 
                    v-for="(record, index) in records"
                    :key="`${record?.id}_${index}`"
                    :record="record"
                    :appName="String(appName)"
                    :appNameJp="appNameJp"
                    :apps="apps"  
                    @setChargeTarget=" val => chargeTarget = val"
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
        <Transition name="modalFade">
            <div
                v-if="tagPickerOpen"
                class="tag-picker-overlay"
                @mousedown="closeTagPicker"
            >
                <div
                    class="tag-picker-sheet"
                    @mousedown.stop
                >
                    <div class="tag-picker-sheet__header">
                        <div>
                            <p class="tag-picker-sheet__title">タグから探す</p>
                        </div>
                        <button type="button" class="tag-picker-sheet__close" @click="closeTagPicker">
                            <CloseIcon size="12" />
                        </button>
                    </div>

                    <div class="tag-picker-sheet__toolbar">
                        <div v-if="currentTagLabel" class="tag-picker-sheet__active">
                            <span class="tag-picker-sheet__active-label">選択中のタグ</span>
                            <router-link
                                :to="`/${String(appName)}?search_tags=${currentTagLabel}`"
                                class="tag-chip tag-chip--active"
                                @click="closeTagPicker"
                            >
                                #{{ currentTagLabel }}
                            </router-link>
                        </div>

                        <router-link
                            v-if="currentTagLabel"
                            :to="`/${String(appName)}`"
                            class="tag-picker-sheet__reset"
                            @click="closeTagPicker"
                        >
                            タグ絞り込みを解除
                        </router-link>
                    </div>

                    <div class="tag-picker-sheet__search">
                        <input
                            v-model="tagSearch"
                            type="text"
                            class="tag-picker-sheet__search-input"
                            placeholder="タグ名で検索"
                        >
                    </div>

                    <div class="tag-picker-sheet__body scrollable">
                        <router-link
                            v-for="tag in filteredTags"
                            :key="tag.id"
                            :to="tagLink(tag.text)"
                            :class="['tag-picker-item', { 'tag-picker-item--active': isCurrentTag(tag.text) }]"
                            @click="closeTagPicker"
                        >
                            <span class="tag-picker-item__name">#{{ sanitized(tag.text) }}</span>
                            <span class="text-sm">({{ tagCount(tag) }})</span>
                        </router-link>

                        <div v-if="tagLoading > 0 && !filteredTags.length" class="tag-picker-sheet__empty">
                            該当するタグがありません。
                        </div>
                    </div>
                </div>
            </div>
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
import { useTopTags } from '@/store/topTags'
import { instance } from '@/utils/broadcaster';
import { onUnmounted } from 'vue';
import { useApi } from '@/composables/api';
import { Post, PostEntry, PostQuery, TopEntryUser } from '@/interface/postInterface';
import { PostMethodsKey } from '@/interface/keys';
import PostEntryCreate from './PostEntryCreate.vue';
import PostEntryRanking from './PostEntryRanking.vue';
import CloseIcon from '../Form/CloseIcon.vue';
import Back from '../Icons/Back.vue';
    const badge = useBadgeStore()
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const postList = ref<Post[]>([])
    const create = ref(false)
    const componentKey = ref(0)
    const sharedFrom = ref(null)
    const filesToShare = ref(null)
    const hasQuery = ref(false)
    const chargeTarget =  ref<number | null>(null)
    const editTarget = ref<Post | null>(null)
    const updateTarget = ref<Post | null>(null)
    const searchWindow = ref(false)
    const route = useRoute()    
    const router = useRouter()
    const infiniteLoader = ref(false)
    const tagLoading = ref(0)
    const topTags = useTopTags()
    const apps = ['ナイス', 'ナレッジ', 'チャレンジ', 'ノート', 'ヘルプ', 'グラリンピック', 'リフレッシュ']
    const api = useApi()
    const viewFullRanking = ref(false)
    const tagPickerOpen = ref(false)
    const tagSearch = ref('')
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
    const previewTags = computed(() => {
        return topTags.tags.slice(0, responsive.mobile ? 6 : 25)
    })
    const currentTagLabel = computed(() => {
        const currentTag = getQuery.value?.search_tags
        return currentTag ? sanitized(currentTag) : ''
    })
    const filteredTags = computed(() => {
        const keyword = tagSearch.value.trim().toLowerCase()
        if (!keyword) {
            return topTags.tags
        }

        return topTags.tags.filter(tag =>
            sanitized(tag.text).toLowerCase().includes(keyword)
        )
    })

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
            fetchPosts(query)
        }
        instance.on('post:new', postSocketHandler)
        hasQuery.value = Object.getOwnPropertyNames(route.query).length ? true : false
        
    

        setTimeout(() => {
            if(route.name && (typeof route.name === 'string' && (route.name.includes('challenge') || route.name.includes('post'))) && !auth.isPartner && appName.value){
                badge.updatePostBadge(appName.value.toString())
            }            
        }, 2000);
        if(sharingData.active){
            newRecord()
        }
        getTopTags()
        // getTopRecords()
    })
    onUnmounted(() => {
        instance.off('post:new', postSocketHandler)
    })
    watch(() => route.fullPath, () => {
        tagPickerOpen.value = false
        tagSearch.value = ''
    })
    const getTopRecords = async () => {
        const data = await api.post('/get_top_posts')
        topRecords.value = data
    }
    const postSocketHandler = (data:any) =>{
        console.log(data)
        const payload = data && data.length ? data[0] : null
      
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
            if (infiniteLoader.value){
                return;
            }                       
            infiniteLoader.value = true;
            let query = getQuery.value
            fetchPosts(query)                                   
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
        const query = {
            id: id,
            search_tags: search_tags,
            member: search_member,
            app_type: search_type,
        }
        return query
    })
    
    const getTopTags = async() => {
        // if(topTags.appName == appName.value) {
        //     tagLoading.value ++
        //     return
        // }
        await topTags.getTags({appName: appName.value, reset: true, currentTag: getQuery.value?.search_tags})
        setTimeout(() => {
            tagLoading.value ++
        }, 300);
    }
    const openTagPicker = () => {
        tagSearch.value = ''
        tagPickerOpen.value = true
    }
    const closeTagPicker = () => {
        tagPickerOpen.value = false
        tagSearch.value = ''
    }
    
    const postFinish = (flag: boolean, id?: number) => {
        create.value = false
        editTarget.value = null
        if(flag && id){
            const query: Record<string, any> = {
                id: id,
                search_tags: null
            }
            fetchPosts(query, id)
            topTags.getTags({appName: appName.value, reset: false})
        }
    }
    const newRecord = () => {
        create.value = true
    }
    const fetchPosts = async (query: Record<string, any>, replace?:number) => {
        const data = await api.post('/get_posts', {
            path: appName.value,
            query: query,
            skip: postList.value.length,
        })

        if(replace ){
            const index = postList.value.findIndex(ob => ob.id == replace)
            if(index > -1){
                postList.value[index] = data[0]
            }else{
                postList.value.unshift(data[0])
            }
        }else{
            data.forEach((responseItem: Post) => {
                const existingPost = postList.value.find((post) => post.id === responseItem.id);
                if (existingPost) {
                    Object.assign(existingPost, responseItem);
                } else {
                    postList.value.push(responseItem);
                }
            });
        }
        setTimeout(() => {
            infiniteLoader.value = false
        }, 500);

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
    const tagLink = (text: string) => {
        return `/${String(appName.value)}?search_tags=${sanitized(text)}`
    }
    const isCurrentTag = (text: string) => {
        return sanitized(text) === currentTagLabel.value
    }
    const tagCount = (tag: Record<string, any>) => {
        return tag[`${String(appName.value)}_occurence_count` as keyof typeof tag] ?? tag.occurrence ?? 0
    }
    const randomWidth = () => {        
        const range = (3 - 1) / 0.2;
        const index = (Math.floor(Math.random() * range) * 0.2) + 1;
        return `${(Math.floor(Math.random() * (90 - 70 + 1)) + 70) * index}px`;

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
.active-query{
    font-size: 14px;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 10px 10px;
    width: fit-content;
    display: flex;
    gap: 15px;
    align-items: center;
}

.tag-skeleton{
    overflow: hidden;
    height: 22px;
    animation: pulse-bg 2s infinite;
    border-radius: 3px;
}

.p-tag-container {
    padding: 8px 20px 4px;
    overflow: visible;
}

.tag-strip {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 0;
    border: none;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}

.tag-strip__footer {
    display: flex;
    align-items: center;
    justify-content: center;
}

.tag-strip__eyebrow,
.tag-picker-sheet__eyebrow {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--primary-color) 60%, transparent);
}

.tag-strip__title,
.tag-picker-sheet__title {
    margin-top: 4px;
    font-size: 17px;
    font-weight: 700;
    line-height: 1.35;
    color: var(--primary-color);
}

.tag-strip__action,
.tag-picker-sheet__close,
.tag-picker-sheet__reset {
    border: none;
    outline: none;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
}

.tag-strip__action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.tag-strip__body {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tag-strip__active,
.tag-picker-sheet__active {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.tag-strip__active-label,
.tag-picker-sheet__active-label,
.tag-picker-sheet__description {
    font-size: 13px;
    line-height: 1.5;
    color: color-mix(in srgb, var(--primary-color) 74%, transparent);
}

.p-tag-wrap {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    align-items: flex-start;
    align-content: flex-start;
    overflow: visible;
    padding: 2px !important;
    height: auto !important;
    max-height: none !important;
    min-height: 0;
}

.p-tag-wrap--skeleton {
    gap: 8px;
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-height: 0;
    padding: 8px 10px;
    box-sizing: border-box;
    background: transparent;
    font-size: 13px;
    overflow: visible;
    transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    text-decoration: none;
}

.tag-chip:hover,
.tag-picker-item:hover,
.tag-strip__action:hover,
.tag-picker-sheet__close:hover,
.tag-picker-sheet__reset:hover {
    transform: translateY(-1px);
}

.tag-chip__count,
.tag-picker-item__count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 7px;
    border-radius: 4px;
    background: var(--calendarBorder);
    font-size: 12px;
    color: var(--primary-color);
}


.tag-picker-overlay {
    position: fixed;
    inset: 0;
    z-index: 40;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    background-color: var(--overlay);
}

.tag-picker-sheet {
    width: min(460px, 100%);
    height: 100%;
    max-height: 100%;
    box-sizing: border-box !important;
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 22px;
    background: var(--background-color);
    color: var(--primary-color);
    box-shadow: -24px 0 48px rgba(15, 23, 42, 0.18);
}

.tag-picker-sheet * {
    box-sizing: border-box;
}

.tag-picker-sheet__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.tag-picker-sheet__close,
.tag-picker-sheet__reset {
    font-size: 13px;
    font-weight: 700;
}

.tag-picker-sheet__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 0;
    opacity: 0.8;
}

.tag-picker-sheet__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.tag-picker-sheet__search {
    position: relative;
}

.tag-picker-sheet__search-input {
    width: 100%;
    height: 44px;
    padding: 0 14px;
    border: 1px solid color-mix(in srgb, var(--primary-color) 18%, transparent);
    background: color-mix(in srgb, var(--background-color) 92%, #ffffff 8%);
    color: var(--primary-color);
    font-size: 14px;
    box-sizing: border-box !important;  
}

.tag-picker-sheet__body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding-right: 4px;
}

.tag-picker-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-radius: 6px;
    transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    text-decoration: none;
}

.tag-picker-item__name {
    font-size: 14px;
    line-height: 1.45;
    word-break: break-word;
}

.tag-picker-sheet__empty {
    padding: 28px 12px;
    text-align: center;
    font-size: 14px;
    color: color-mix(in srgb, var(--primary-color) 70%, transparent);
}

@keyframes pulse-bg {
    0% {
        background-color: var(--skItem1);
    }
    50% {
        background-color: var(--skItem2);
    }
    100% {
        background-color: var(--skItem1);
    }
}

@media screen and (max-width: 959px) {
    .p-tag-container {
        padding: 8px 14px 4px;
    }

    .tag-strip {
        padding: 0;
        border-radius: 0;
    }

    .tag-strip__footer,
    .tag-picker-sheet__header,
    .tag-picker-sheet__toolbar {
        align-items: stretch;
    }

    .tag-picker-overlay {
        align-items: flex-end;
        justify-content: center;
    }

    .tag-picker-sheet {
        width: 100%;
        height: min(78vh, 720px);
        box-shadow: 0 -24px 48px rgba(15, 23, 42, 0.18);
    }
}
</style>
