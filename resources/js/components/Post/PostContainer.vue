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
                        <PostIcon which="0" size="12" class="hidden under400:block"/>
                        <PostIcon which="0" size="16" class="under400:hidden"/>
                        {{ apps[0] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=2`" :class="['cat-chip', { 'cat-chip--active': getQuery?.app_type == '2' }]">
                        <PostIcon which="2" size="12" class="hidden under400:block"/>
                        <PostIcon which="2" size="16" class="under400:hidden"/>
                        {{ apps[2] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=3`" :class="['cat-chip', { 'cat-chip--active': getQuery?.app_type == '3' }]">
                        <PostIcon which="3" size="12" class="hidden under400:block"/>
                        <PostIcon which="3" size="16" class="under400:hidden"/>
                        {{ apps[3] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=6`" :class="['cat-chip', { 'cat-chip--active': getQuery?.app_type == '6' }]">
                        <PostIcon which="6" size="12" class="hidden under400:block"/>
                        <PostIcon which="6" size="16" class="under400:hidden"/>
                        {{ apps[6] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=7`" :class="['cat-chip', { 'cat-chip--active': getQuery?.app_type == '7' }]">
                        <span v-if="rakuawardResultUnread" title="新しい結果発表があります"
                            class="w-1.5 min-w-1.5 h-1.5 rounded-full bg-[tomato] custom-heartbeat"></span>
                        <PostIcon which="7" size="12" class="hidden under400:block"/>
                        <PostIcon which="7" size="16" class="under400:hidden"/>
                        {{ apps[7] }}
                    </router-link>
                </div>

                <!-- Challenge category filter -->
                <template v-if="showCategoryFilter">
                    <div class="cat-filter-row mt-4 under960:mt-1">
                        <router-link
                            v-for="cat in challengeCategories"
                            :key="cat.label"
                            :to="buildCategoryPath(activeMainCategory === cat.label ? null : cat.label)"
                            :class="['cat-chip', { 'cat-chip--active': activeMainCategory === cat.label }]"
                        >
                            {{ cat.label }}
                        </router-link>
                    </div>
                    <div v-if="activeChallengeCategory" class="cat-filter-subshell" :class="{ 'cat-filter-subshell--visible': !!activeChallengeCategory }">
                        <div class="cat-filter-subshell__inner mt-3">
                            <Transition name="subRowSwap" mode="out-in">
                                <div
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
                    <div class="cat-filter-row cat-filter-row--donation mt-3">
                        <router-link
                            :to="buildDonationTargetPath(activeDonationFilter === 'exists' ? null : 'exists')"
                            :class="['cat-chip', { 'cat-chip--active': activeDonationFilter === 'exists' }]"
                        >
                            寄付先あり
                        </router-link>
                        <router-link
                            :to="buildDonationTargetPath(activeDonationFilter === 'missing' ? null : 'missing')"
                            :class="['cat-chip', { 'cat-chip--active': activeDonationFilter === 'missing' }]"
                        >
                            寄付先なし
                        </router-link>
                    </div>
                </template>
            </div>
            <div v-if="getQuery?.app_type == '7' && isRakuawardDirector && rakuawardPending.length" class="rakuaward-mvp-banner rakuaward-mvp-banner--provisional">
                <p class="rakuaward-mvp-title rakuaward-mvp-title--provisional">
                    <PrivateChip />
                    <span>{{ DateTime.fromFormat(rakuawardPendingMonth, 'yyyy-MM').month }}月 暫定順位（確定前・社外秘）</span>
                </p>
                <div class="rakuaward-mvp-list">
                    <div
                        v-for="row in rankedPending"
                        :key="row.id"
                        class="rakuaward-mvp-item group"
                        @click="jumpToPost(row.id)"
                    >
                        <span class="rakuaward-mvp-rank">{{ row.rankLabel }}</span>
                        <span class="rakuaward-mvp-people">
                            <UserPanel v-if="row.creator" :user="row.creator" :disableInstant="true" size="18" />
                            <span class="rakuaward-mvp-name">{{ row.creator?.name ?? '—' }}</span>
                            <svg class="rakuaward-mvp-arrow" viewBox="0 0 47 32" xmlns="http://www.w3.org/2000/svg"><path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"/></svg>
                            <UserPanel v-if="row.nominee" :user="row.nominee" :disableInstant="true" size="18" />
                            <span class="rakuaward-mvp-name">{{ row.nominee?.name ?? '—' }}</span>
                        </span>
                        <span class="rakuaward-mvp-post">{{ row.title }}</span>
                        <span class="rakuaward-mvp-score">{{ row.total_score }}点</span>
                        <div>
                            <Back size="9" class="rotate-180 transition-transform duration-200 ease-out group-hover:scale-125"/>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 mt-3 flex-wrap">
                    <span v-if="pendingMonthInProgress" class="rakuaward-announce-caution">
                        ※ {{ DateTime.fromFormat(rakuawardPendingMonth, 'yyyy-MM').month }}月は進行中です（順位は変動します）
                    </span>
                    <button
                        type="button"
                        class="rakuaward-announce-button"
                        :disabled="announcing"
                        @click="announceRakuaward"
                    >
                        {{ announcing ? '発表中...' : `${DateTime.fromFormat(rakuawardPendingMonth, 'yyyy-MM').month}月度の結果を発表する` }}
                    </button>
                </div>
            </div>
            <Transition name="modalFade">
                <div v-if="getQuery?.app_type == '7' && showAnnouncementNotice" class="rakuaward-announce-notice">
                    <span class="rakuaward-announce-notice-dot"></span>
                    <span>{{ DateTime.fromFormat(rakuawardMvpMonth, 'yyyy-MM').month }}月度の楽アワードランキング発表が完了しました</span>
                </div>
            </Transition>
            <div v-if="getQuery?.app_type == '7' && rakuawardMvps.length" class="rakuaward-mvp-banner">
                <p class="rakuaward-mvp-title">{{DateTime.fromFormat(rakuawardMvpMonth, 'yyyy-MM').month}}月度結果発表</p>
                <div class="rakuaward-mvp-list">
                    <div
                        v-for="mvp in visibleMvps"
                        :key="mvp.id"
                        class="rakuaward-mvp-item group"
                        @click="jumpToPost(mvp.id)"
                    >
                        <span class="rakuaward-mvp-rank">{{ mvp.rankLabel }}</span>
                        <span class="rakuaward-mvp-people">
                            <UserPanel v-if="mvp.creator" :user="mvp.creator" :disableInstant="true" size="18" />
                            <span class="rakuaward-mvp-name">{{ mvp.creator?.name ?? '—' }}</span>
                            <svg class="rakuaward-mvp-arrow" viewBox="0 0 47 32" xmlns="http://www.w3.org/2000/svg"><path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"/></svg>
                            <UserPanel v-if="mvp.nominee" :user="mvp.nominee" :disableInstant="true" size="18" />
                            <span class="rakuaward-mvp-name">{{ mvp.nominee?.name ?? '—' }}</span>
                        </span>
                        <span class="rakuaward-mvp-post">{{ mvp.title }}</span>
                        <span v-if="isRakuawardDirector" class="rakuaward-mvp-score">{{ mvp.total_score }}点</span>
                        <div>
                            <Back size="9" class="rotate-180 transition-transform duration-200 ease-out group-hover:scale-125"/>
                        </div>
                    </div>
                </div>
                <div v-if="rakuawardMvps.length > 5 && isRakuawardDirector" class="flex mt-2">
                    <button
                        
                        type="button"
                        class="jump-link p-2"
                        @click="mvpShowAll = !mvpShowAll"
                    >
                        {{ mvpShowAll ? '閉じる' : '詳細' }}
                    </button>
                    <PrivateChip />
                </div>
                
            </div>
            <TransitionGroup
                v-if="postNoticeRows.length"
                name="slidePop"
                tag="div"
                class="post-notice-list"
            >
                <div
                    v-for="notice in postNoticeRows"
                    :key="`${notice.type}_${notice.id}`"
                    class="post-notice-row"
                >
                    <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5"></div>
                    <span class="post-notice-title">{{ notice.title || 'タイトルなし' }}</span>
                    <span class="post-notice-message">{{ notice.message }}</span>
                    <div class="post-notice-link" type="button" @click="jumpToBadgePost(notice)">
                        見に行く
                    </div>
                </div>
            </TransitionGroup>
            
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
            

        
      
        <div v-if="getQuery.app_type !== '7' || auth.user.position_id === 6" title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="newRecord">
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
                :isRakuaward="isRakuaward"
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
import PrivateChip from '../Global/PrivateChip.vue';
import UserPanel from '../Global/UserPanel.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { LocationQueryValue, onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { provide } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useSharingDataStore } from '@/store/sharingData'
import { useBadgeStore } from '@/store/badge'
import { instance } from '@/utils/broadcaster';
import { onUnmounted } from 'vue';
import { useApi } from '@/composables/api';
import { Post, PostEntry, PostQuery, TopEntryUser } from '@/interface/postInterface';
import { User } from '@/interface/globalInterface';
import { PostMethodsKey } from '@/interface/keys';
import PostEntryCreate from './PostEntryCreate.vue';
import PostEntryRanking from './PostEntryRanking.vue';
import CloseIcon from '../Form/CloseIcon.vue';
import { challengeCategories } from '@/utils/challengeCategory';
import Back from '../Icons/Back.vue';
import { DateTime } from 'luxon';
type PostNoticeType = 'changed' | 'progress_report' | 'last_chargeable'
type PostNoticeRow = {
    id: number
    title: string | null
    type: PostNoticeType
    message: string
}
type DonationFilter = 'exists' | 'missing'
const normalizeDonationFilter = (value: unknown): DonationFilter | null => {
    const filter = Array.isArray(value) ? value[0] : value
    return filter === 'exists' || filter === 'missing' ? filter : null
}
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
        return !!(q.app_type || q.member || q.search_tags || q.main_category || q.sub_category || normalizeDonationFilter(q.donation_target))
    })
    const chargeTarget =  ref<number | null>(null)
    const isMini = ref(false)
    const isRakuaward = ref(false)
    const editTarget = ref<Post | null>(null)
    const updateTarget = ref<Post | null>(null)
    const searchWindow = ref(false)
    const route = useRoute()    
    const router = useRouter()
    const infiniteLoader = ref(false)
    const queryRefreshing = ref(false)
    const apps = ['ナイス', 'ナレッジ','チャレンジ', 'ニュース', 'ヘルプ', 'グラリンピック', 'リフレッシュ', 'ノミネート']
    const api = useApi()
    type RakuawardRankRow = { id: number; title: string; total_score: number; granted?: boolean; nominee: User | null; creator: User | null }
    const rakuawardMvps = ref<RakuawardRankRow[]>([])
    const rakuawardMvpMonth = ref('')
    const rakuawardPending = ref<RakuawardRankRow[]>([])
    const rakuawardPendingMonth = ref('')
    const announcing = ref(false)
    const showAnnouncementNotice = ref(false)
    const mvpShowAll = ref(false)
    // Competition ranking by score: equal scores share a rank and get a "タイ" suffix.
    const withRanks = (rows: RakuawardRankRow[]) => rows.map(row => {
        const rank = rows.filter(other => other.total_score > row.total_score).length + 1
        const tied = rows.filter(other => other.total_score === row.total_score).length > 1
        return {
            ...row,
            rankLabel: rank === 1 ? 'MVP' : `${rank}位${tied ? 'タイ' : ''}`,
        }
    })
    const rankedMvps = computed(() => withRanks(rakuawardMvps.value))
    const rankedPending = computed(() => withRanks(rakuawardPending.value))
    const visibleMvps = computed(() => mvpShowAll.value ? rankedMvps.value : rankedMvps.value.slice(0, 5))
    const rakuawardResultUnread = ref(false)
    const fetchRakuawardMvps = async () => {
        const data = await api.get('/rakuaward_mvps', null, { silent: true })
        if (data) {
            rakuawardMvps.value = data.mvps ?? []
            rakuawardMvpMonth.value = data.month ?? ''
            rakuawardPending.value = data.pending ?? []
            rakuawardPendingMonth.value = data.pending_month ?? ''
            rakuawardResultUnread.value = !!data.result_unread
        }
    }
    // Acknowledge the monthly results announcement (stored in user read history).
    const markRakuawardResultRead = async () => {
        if (!rakuawardResultUnread.value) return
        rakuawardResultUnread.value = false
        badge.post.rakuaward_result = 0
        // Briefly explain why the badge was there, then fade it out.
        showAnnouncementNotice.value = true
        setTimeout(() => { showAnnouncementNotice.value = false }, 3000)
        await api.post('/rakuaward_result_read', { month: rakuawardMvpMonth.value }, { silent: true })
        badge.getbadgeSummary()
    }
    // True while the pending month is still running (scoring and charging remain open).
    const pendingMonthInProgress = computed(() => {
        if (!rakuawardPendingMonth.value) return false
        const target = DateTime.fromFormat(rakuawardPendingMonth.value, 'yyyy-MM')
        return target.isValid && target.hasSame(DateTime.now(), 'month')
    })
    const announceRakuaward = async () => {
        if (!rakuawardPendingMonth.value || announcing.value) return
        const month = DateTime.fromFormat(rakuawardPendingMonth.value, 'yyyy-MM').month
        const confirmMessage = pendingMonthInProgress.value
            ? `${month}月はまだ進行中です。\n採点・チャージの受付が続いているため、順位はこの後も変動する可能性があります。\n\nこのまま${month}月度の結果を発表して確定しますか？\n（上位5名がMVPとして確定し、チャージ金額が付与されます。発表後は取り消せません）`
            : `${month}月度の楽アワードを発表して確定します。\n上位5名がMVPとして確定し、チャージ金額が付与されます。\n発表後は取り消せません。よろしいですか？`
        const result = await api.post('/rakuaward_announce', { month: rakuawardPendingMonth.value }, {
            loadingRef: announcing,
            ask: confirmMessage,
            toast: `${month}月度の結果を発表しました。`,
        })
        if (!result) return
        await fetchRakuawardMvps()
        badge.getbadgeSummary()
    }
    const jumpToPost = (id: number) => {
        router.push({ name: appName.value, query: { app_type: '7', id: String(id) } })
        fetchPosts({ ...getQuery.value, app_type: '7', id: String(id) }, id)
    }
    const isRakuawardDirector = computed(() => {
        const pid = auth.user?.position_id
        return pid != null && Number(pid) < 6
    })
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
    const activeDonationFilter = computed(() => {
        return normalizeDonationFilter(route.query.donation_target)
    })
    const activeChallengeCategory = computed(() => {
        return challengeCategories.find(c => c.label === activeMainCategory.value) ?? null
    })
    const showCategoryFilter = computed(() => {
        return appName.value === 'challenge' || (appName.value === 'post' && getQuery.value?.app_type === '2')
    })
    const postNoticeRows = computed<PostNoticeRow[]>(() => [
        ...badge.postNoticeItems.progress_report.map(item => ({
            ...item,
            type: 'progress_report' as const,
            message: '進捗報告が追加されました',
        })),
        ...badge.postNoticeItems.changed.map(item => ({
            ...item,
            type: 'changed' as const,
            message: 'ステータスが変更されました',
        })),
        ...badge.postNoticeItems.last_chargeable.map(item => ({
            ...item,
            type: 'last_chargeable' as const,
            message: 'チャージ最終日です',
        })),
    ])
    let feedRequestId = 0
    const buildCategoryPath = (main: string | null, sub: string | null = null): string => {
        const params = new URLSearchParams()
        if (appName.value === 'post' && getQuery.value?.app_type) {
            params.set('app_type', String(getQuery.value.app_type))
        }
        if (main) params.set('main_category', main)
        if (sub) params.set('sub_category', sub)
        if (activeDonationFilter.value) params.set('donation_target', activeDonationFilter.value)
        const qs = params.toString()
        return `/${appName.value}${qs ? '?' + qs : ''}`
    }
    const buildDonationTargetPath = (target: DonationFilter | null): string => {
        const params = new URLSearchParams()
        if (appName.value === 'post') {
            params.set('app_type', String(getQuery.value?.app_type ?? 2))
        }
        if (activeMainCategory.value) params.set('main_category', activeMainCategory.value)
        if (activeSubCategory.value) params.set('sub_category', activeSubCategory.value)
        if (target) params.set('donation_target', target)
        const qs = params.toString()
        return `/${appName.value}${qs ? '?' + qs : ''}`
    }
    const setChargeTarget = (record: Post) => {
        chargeTarget.value = record.id
        isMini.value = record.mini ? true : false
        // Rakuaward nice charges use the same 500 cap as mini challenges, shown with its own message.
        isRakuaward.value = record.app_type == 7
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
    onBeforeRouteLeave((to) => {
        if (to.name !== route.name) {
            badge.clearPostNoticeItems()
        }
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
            donation_target: route.query.donation_target,
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

                query['id'] = id

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

                query['id'] = id
 
            fetchPosts(query, id)
        }
        
    }
    const closeEntryCreate = (flag: boolean, id?:number) => {
        entryData.value = { record: null, editData: null }
        if(id){                
            let query = getQuery.value

                query['id'] = id.toString()
 
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
        const donation_target = normalizeDonationFilter(route.query.donation_target)
        const query = {
            id: id,
            search_tags: search_tags,
            member: search_member,
            app_type: search_type,
            main_category: main_category,
            sub_category: sub_category,
            donation_target: donation_target,
        }
        return query
    })

    // Fetch once up-front so the ノミネート chip can show the unread badge from any tab,
    // then refresh + acknowledge the announcement whenever that tab is opened.
    let rakuawardFetched = false
    watch(() => getQuery.value.app_type, async (type) => {
        if (appName.value !== 'post') return
        if (!rakuawardFetched || type === '7') {
            rakuawardFetched = true
            await fetchRakuawardMvps()
        }
        if (type === '7') {
            await markRakuawardResultRead()
        }
    }, { immediate: true })

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
    const jumpToBadgePost = (notice: PostNoticeRow) => {
        badge.clearPostNoticeItem(notice.type, notice.id)

        const query = {
            ...route.query,
            id: String(notice.id),
        }
        router.push({ name: appName.value, query })

        fetchPosts({ ...getQuery.value, id: String(notice.id) }, notice.id)
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

.post-notice-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 0px 20px;
    background: var(--bg3);
    padding: 10px;
}

.rakuaward-mvp-banner {
    margin: 0 20px 10px;
    padding: 12px 14px;
    background: var(--background-color);
}

.rakuaward-mvp-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    margin: 0 0 8px;
    color: var(--primary-color);
}



.rakuaward-mvp-title--provisional {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rakuaward-announce-button {
    padding: 6px 14px;
    border: 1px solid var(--primary-button);
    background: var(--primary-button);
    color: #fff;
    font-size: 12px;
    cursor: pointer;
    white-space: nowrap;
}

.rakuaward-announce-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.rakuaward-announce-caution {
    font-size: 11px;
    color: #a33d3d;
}

.rakuaward-announce-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 20px 8px;
    padding: 8px 12px;
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 12px;
}

.rakuaward-announce-notice-dot {
    width: 6px;
    height: 6px;
    flex-shrink: 0;
    border-radius: 9999px;
    background: tomato;
}

@media (max-width: 640px) {
    .rakuaward-announce-notice {
        margin: 0 12px 8px;
    }
}

.rakuaward-mvp-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rakuaward-mvp-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    cursor: pointer;
    font-size: 13px;
    color: var(--primary-color);
    min-width: 0;
}

.rakuaward-mvp-item:hover {
    background: var(--bg2);
}

.rakuaward-mvp-rank {
    min-width: 36px;
    height: 20px;
    padding: 0 8px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: var(--primary-color);
    color: var(--background-color);
    font-size: 11px;
    white-space: nowrap;
}

.rakuaward-mvp-name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rakuaward-mvp-people {
    flex-shrink: 1;
    min-width: 0;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    overflow: hidden;
}

.rakuaward-mvp-arrow {
    width: 14px;
    height: 10px;
    flex-shrink: 0;
    fill: var(--sub-color);
}

.rakuaward-mvp-post {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--sub-color);
}

.rakuaward-mvp-score {
    flex-shrink: 0;
}

@media (max-width: 640px) {
    .rakuaward-mvp-banner {
        margin: 0 12px 10px;
        padding: 10px;
    }

    .rakuaward-mvp-item {
        gap: 6px;
        padding: 6px 4px;
    }

    /* Post title is secondary on small screens; keep rank + people + score readable. */
    .rakuaward-mvp-post {
        display: none;
    }

    .rakuaward-mvp-people {
        flex: 1;
    }

    .rakuaward-mvp-name {
        max-width: 96px;
    }

    .rakuaward-mvp-rank {
        min-width: 30px;
        padding: 0 6px;
    }
}

.rakuaward-mvp-detail {
    margin-top: 8px;
    padding: 4px 12px;
    border: 1px solid var(--formBorder);
    border-radius: 9999px;
    background: transparent;
    color: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
}

.rakuaward-mvp-detail:hover {
    background: var(--bg2);
}

.post-notice-row {
    display: flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    max-width: 100%;
    padding: 6px 10px;
    font-size: 12px;
}

.post-notice-title {
    max-width: min(360px, 46vw);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 600;
    color: var(--primary-color);
}

.post-notice-message {
    color: var(--primary-color);
    white-space: nowrap;
}

.post-notice-link {
    color: var(--link-color);
    cursor: pointer;
    white-space: nowrap;
}
.post-notice-link:hover {
  text-decoration: underline;
  font-weight: 600;
}
.cat-filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}

.cat-filter-row--sub {
    padding-left: 4px;
}

.cat-filter-row--donation {
    gap: 4px;
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
    font-size: 12px;
    font-weight: 500;
    background: transparent;
    color: var(--primary-color);
    border: 1px solid transparent;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background 0.2s ease-in-out, border-color 0.2s ease-in-out,
                color 0.2s ease-in-out, transform 0.15s ease, opacity 0.15s ease;
    line-height: 1.4;
    will-change: transform, opacity;
}


.cat-chip:hover {
    background: color-mix(in srgb, var(--primary-color) 10%, transparent);
}

.cat-chip--active {
    background: var(--bg3);
    border-color: var(--primary-color);
}
.cat-chip--active:hover {
    background: var(--bg3);
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
    .post-notice-list {
        padding: 2px 14px 12px;
    }
    .post-notice-row {
        width: 100%;
        flex-wrap: wrap;
    }
    .post-notice-title {
        max-width: 100%;
    }
}
@media screen and (max-width: 480px) {
    .cat-chip {
        padding: 4px 8px;
        font-size: 11px;
    }
}
</style>
