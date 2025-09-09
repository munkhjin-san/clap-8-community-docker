<template>
    <div class="post-item-outer" :id="`record-${record.id}`" v-if="record">
        <div class="post-item-header-wrap">
            <div class="flex gap-2.5 items-center">
                <PostIcon v-if="appName == 'post' && record.app_type != 6" :which="record.app_type" size="20"/>
                <div v-html="title" class="post-title"></div>
            </div>
            <ItemMenu v-if="isOwner || auth.id === 516" :items="postMenu"/> 
        </div>
        <div class="post-second-wrap">
            <div :class="['post-user-wrap', {'post-users-wrap' : isMultipleUsers}]">
                <RouterLink class="user-link flex items-center cursor-pointer" :to="`${appName}?member=${record.user.name}`" v-if="record.app_type !== 2">
                    <UserPanel :user="record.user" :disableInstant="true" imgClass="userNormalIcon" size="30"/>
                    <p class="userName">{{ record.user ? record.user.name : '' }}</p>
                </RouterLink>              
                <div v-if="record.app_type == 2 || record.app_type == 0" class="relative">
                    <div class="flex items-center">
                        <svg v-if="record.app_type == 0" version="1.1" xmlns="http://www.w3.org/2000/svg" class="nice-arrow mr-4" viewBox="0 0 47 32">
                            <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
                        </svg>
                        <div ref="toUsersRef" :class="['toUserListContainer', {expandToUserListContainer : expand}]">   
                            <RouterLink class="user-link flex items-center cursor-pointer" :to="`${appName}?member=${user.name}`" :key="user.id" v-for="user in record.to_users">                                                    
                                <UserPanel size="30" :disableInstant="true"  :user="user" :imgClass="isMultipleUsers ? 'toUsersIconSmall' : 'toUsersIcon'"/>                               
                                <p class="w-max userName">{{ user.name }}</p>    
                            </RouterLink>                                                              
                        </div>
                    </div>
                    
                    <div v-if="viewExpand" @click="expand = !expand" class="toUserExpandButton">                                                                                  
                        <svg :class="['userListArrow', {reverse : expand}]" version="1.1" width="25" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg> 
                        <span class="mx-1.5">({{record.to_users.length}})</span>                        
                    </div> 
                </div>
            </div>
            <div class="flex items-center gap-4 flex-1 flex-wrap justify-end">            
                <PostDate :record="record" dateClass="dateText"/> 
                <div @click="updateStatus" v-if="record.app_type == 2" class="text-sm whitespace-nowrap cursor-pointer">{{ status }}</div>
            </div>
        </div>
        <div v-if="record.app_type == 5 && record.donation_target">
            <p>寄付先: {{ record.donation_target }}</p>
        </div>
        <div>
            <div>
                <div class="record-content" v-html="body"></div>
                <span @click="showAll = !showAll" class="jump-link" v-if="truncated">{{ showAll ? '閉じる' : '続きを表示する' }}</span>
            </div>
            <div v-if="goal" class="mt-4">
                <div class="post-separetor">
                    <div>達 成 条 件</div>
                </div>
                <div class="record-content" v-html="goal"></div>
            </div>
            <PostFiles class="mt-4" v-if="record.files.length" :items="record.files"/>
            <div v-if="result" class="mt-4">
                <div class="post-separetor">
                    <div>結 果 発 表</div>
                </div>
                <div class="record-content" v-html="result"></div>
            </div>
            <PostFiles class="mt-4" v-if="record.result_files && record.result_files.length" :items="record.result_files"/>
            <div v-if="record.app_type == 6 && (auth.activeUser.id == 610 || record.user_id == auth.id)" class="mt-4">
                <div class="post-separetor">
                    <div>領収（非公開）</div>
                </div>
                <PostFiles class="mt-4" path="/post_receipts" v-if="record.receipts.length" :items="record.receipts"/>
            </div>

            <div class="post-url" v-if="record.referrer">
                参照元 : <a :href="record.referrer">{{ record.referrer }}</a>
            </div>
            <div v-if="tags.length" class="flex gap-x-2.5 gap-y-2 flex-wrap">
                <PostTag
                    v-for="tag in tags"
                    :tag="tag"
                    :key="tag.id"
                />
            </div>
            <div class="my-5 flex flex-col gap-5" v-if="record.entries && record.entries.length">
                <div v-for="entry in record.entries" :key="entry.id">
                    <PostEntryRecord :entry="entry" @setClap="setClap"/>
                </div>

                <!-- <div v-if="record.entries.length > 1" class="pt-2">
                    <button
                    type="button"
                    class="text-sm text-blue-600 hover:underline"
                    @click="viewEntries = !viewEntries"
                    >
                    {{ viewEntries ? '閉じる' : `他 ${record.entries.length - 1} 件を見る` }}
                    </button>
                </div> -->
            </div>
            <div v-if="challengeButtonView">                                    
                <button @click="emit('setChargeTarget', record.id)" v-if="challengeButtonSwitch" id="chargeAddButton" class="chargeFormeAddButton cursor-pointer">チャレンジにチャージする</button>
                <button v-else class="chargeFormeAddButton" disabled>{{canNotCharge}}</button>
            </div>  
            <div @click="emit('setEntryData', {record: record, editData: null})" v-if="record.app_type == 5">                                    
                <button id="glowlympicButton" class="chargeFormeAddButton cursor-pointer">参加する</button>
            </div>  
        </div>
        <div class="post-footer mb-2.5 text-sm" v-if="record.app_type == 2 && record.chargeable">
            <div>現在のチャージ総額 {{ totalChargeAmmount }}円</div>
        </div>
        <div class="post-footer">
             <div class="post-footer-wrap" v-if="record.app_type == 2 && record.grantable">
                <div class="text-[14px]">経費合計: {{ totalExpenses }}円</div>
            </div>
            <div v-if="record.app_type == 2 && record.chargeable" class="post-footer-wrap">
                <div class="text-[14px] cursor-pointer" @click="viewSupporters" v-if="supporters.length">サポーター {{ supporters.length }}人</div>
            </div>
            <div v-if="record.app_type == 5" class="post-footer-wrap">
                <div class="text-[14px] mr-[20px]">カロリー合計: <span v-if="totalCalories">🔥 </span>{{ amountOfMoneyParser(totalCalories) }} kcal</div>
                <div class="text-[14px] cursor-pointer flex items-center gap-1" @click="viewEntries = !viewEntries">
                    <People size="25"/>
                    {{ record.entries.length }}
                </div>
            </div>
            <div v-if="record.app_type == 6 && record.refresh_amount && (auth.activeUser.id === 610 || auth.id === record.user_id)" class="post-footer-wrap">
                <div class="text-[14px] cursor-pointer">リフレッシュ総額: {{ record.refresh_amount }}円</div>
            </div>
           
            <div class="post-footer-wrap">
                <svg @click="isExpanded = !isExpanded" class="comment-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 32">
                    <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path>
                    <path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                </svg>
                <span class="comment-count leading-none" v-if="record.comments_count">{{ record.comments_count }}</span>
            </div>
            <ClapButton @updateClap="setClap" :item="record" :appName="appName"/> 
        </div>
        <transition name="commentArea">
            <PostComment 
                v-if="isExpanded"
                :key="`comment-${record.id}-${isExpanded ? 'open' : 'close'}`"
                :app_name="appName"
                :record="record"
            
            />
        </transition>
        
        
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue'
import PostDate from './PostDate.vue'
import Autolinker from 'autolinker';
import PostTag from './PostTag.vue';
import ClapButton from './ClapButton.vue';
import PostComment from './PostComment.vue'
import PostFiles from './PostFiles.vue';
import { computed, inject, nextTick, onMounted, ref, useTemplateRef } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useMessageUsers } from '@/store/messageUsers'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import PostIcon from './PostIcon.vue';
import { DateTime } from 'luxon';
import { amountOfMoneyParser, customParser } from '@/utils/tools';
import { Post, PostEntry } from '@/interface/postInterface';
import { User } from '@/interface/globalInterface';
import PostEntryRecord from './PostEntryRecord.vue';
    const messageUsers = useMessageUsers()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const props = defineProps<{
        record: Post,
        appNameJp: string,
        appName: string,
        apps: string[]
    }>()
    const emit = defineEmits<{
        'setChargeTarget': [number],
        'setClap': [number],
        'editRecord': [Post],
        'updateStatus': [Post],
        'deleteRecord': [Post],
        'setEntryData': [{ record: Post, editData: PostEntry | null }],
    }>()
    const route = useRoute()
    const maxLength = ref(200)
    const truncated = ref(false)
    const showAll = ref(false)
    const expand = ref(false)
    const viewExpand = ref(false)
    const isExpanded = ref(false)
    const toUsersRef = useTemplateRef('toUsersRef');
    const viewEntries = ref(false)
    onMounted(() => {
        const to_user = toUsersRef.value
        if(to_user && to_user.scrollHeight > to_user.clientHeight){
            viewExpand.value = true
        }
        const queryId = route.query.id as string
        if(queryId){
            const id = parseInt(queryId)
            if(id == props.record.id){
                isExpanded.value = true
            }           
        }  
    })  
    
    const closeAndScroll = (id: number) => {
        viewEntries.value = false
        nextTick(() => {
            const el = document.getElementById(`record-${id}`)
            console.log(el)
            el?.scrollIntoView({ behavior: "smooth", block: "start" })
        })
    }
    const postMenu = computed(() => {
        const items = [    
            {title: `${props.apps[props.record.app_type]}を編集する`, action: () => emit('editRecord', props.record)},
            {title: `${props.apps[props.record.app_type]}を削除する`, action: () => emit('deleteRecord', props.record)}
        ]
        if(props.record.app_type == 2){
            items.push({
                title: 'ステータスを変更する', action: () => updateStatus()
            })
        }
        return items
    })
    const totalExpenses = computed(() => {
        if(props.record.grants && props.record.grants.length){
            const amounts = props.record.grants.map(ob => ob.expenses ? ob.expenses : 0)
            const sum = amounts.reduce((accumulator, currentValue) => accumulator + currentValue, 0);
            return sum
        }
        return 0
    })
    const isMultipleUsers = computed(() => {
        return responsive.mobile && props.record && props.record.to_users && props.record.to_users.length > 1
    })
    const status = computed(() => {
        if (props.record.app_type !== 2) return;
        const statusMap = {
            0: DateTime.now() <= customParser(props.record.date_end) ? '実施中' : '結果待ち',
            1: '達成',
            2: '未達成',
            3: '中止',
            4: '不成立',
        };
        return statusMap[props.record.status_flag];
    });
    const supporters = computed(() => {
        if(props.record.app_type == 2){
            const amounts = props.record.awards
            return amounts
        }
        return []
    })
    const totalChargeAmmount = computed(() => {
        if(props.record.app_type == 2){
            const amounts = props.record.awards.map(ob => {
                return ob.pivot ? ob.pivot.award_bet : 0
            })
            const sum = amounts.reduce((accumulator, currentValue) => accumulator + currentValue, 0);
            return sum
        }
        return ''
    })
    const challengeButtonSwitch = computed(() => {               
        var charged_user = props.record.awards.filter(obj => obj.id == auth.id);
        if(DateTime.now() <= customParser(props.record.date_end) && props.record.status_flag == 0 && charged_user.length == 0){
            return true
        }                
    })
    const canNotCharge = computed(() => {
        if(props.record.status_flag > 0){
            return 'チャレンジの結果が確定しました'
        }else{
            const charged_user = props.record.awards.filter(obj => obj.id == auth.id);
            if(charged_user.length){
                return '既にチャージしています'
            }else if(DateTime.now() > customParser(props.record.date_end)){
                return 'チャージ期間を終了しました'
            }
        }
    })
    const challengeButtonView = computed(() => {
        if(props.record.app_type == 2 && props.record.chargeable){
            let flag = props.record.to_users.filter(obj => obj.id == auth.id);  
            return !flag.length ? true : false   
        }
        return false  
    })
    const isOwner = computed(() => {
        if(props.record && auth.user){
            if(props.record.app_type == 2){
                const player = props.record.to_users.filter(ob => ob.id == auth.id)
                return player && player.length ? true : false
            }else {
                return props.record.user_id == auth.id
            }
        }
    })
    const tags = computed(() => {
        return props.record.tags ? props.record.tags : []
    })
    const title = computed(() => {
        return props.record && props.record.title ? props.record.title : ''
    })
    const body = computed(() => {           
        const text = props.record.app_type == 2 ? props.record.content_rule : props.record.content
        const truncate = cutter(text, maxLength.value)
        const urlParse = Autolinker.link(truncate, {stripPrefix: false});   
        return urlParse          
        
    })
    const goal = computed(() => {
        const text = props.record.app_type == 2 ? props.record.content_goal : ''
        const truncate = cutter(text, maxLength.value)
        const urlParse = Autolinker.link(truncate, {stripPrefix: false});   
        return urlParse    
    })
    const result = computed(() => {
        const text = props.record.app_type == 2 ? props.record.result : props.record.result
        const truncate = cutter(text, maxLength.value)
        const urlParse = Autolinker.link(truncate, {stripPrefix: false});   
        return urlParse    
    })
    const updateStatus = () => {
        if(isOwner.value && props.record.status_flag < 4){
            emit('updateStatus', props.record)
        }
    }
    const closeMenu = () => {
        menu.setMenu( { name: '', id : null})
    }
    const viewSupporters = () => {
        const data = {
            active: true,
            userList: supporters.value,
            title: 'サポーター'
        }
        messageUsers.setMessageUsers(data)
    }
    const viewParticipants = () => {
        const data = {
            active: true,
            userList: participants.value,
            title: '参加者'
        }
        messageUsers.setMessageUsers(data)
    }
    const setClap = () => {
        emit('setClap', props.record.id)
    }
    const cutter = (string, len) => {
        if(!string){
            return ''
        }
        if(showAll.value || string.length <= len || string.length <= len + 50){
            return string
        }
        const last = string.substring(len - 5, len + 5)
        const check_emoji = last.match(/[\p{Emoji}\u200d]+/gu)
        if(!check_emoji){
            truncated.value = true
            return string.substring(0, len) + '...'
        
        }else{
            return cutter(string, len + 5)
        }
        
    }
    const participants = computed(() => {
        // Map entries to users and filter out duplicates by creating a Map with user.id as key
        const userMap = new Map<number, User>();
        props.record.entries.forEach(entry => {
            if (entry.user && !userMap.has(entry.user.id)) {
                userMap.set(entry.user.id, entry.user);
            }
        });
        return Array.from(userMap.values()) as User[];
    });
    const totalCalories = computed(() => {
        return props.record.entries.reduce((total, entry) => {
            return total + (entry.calories || 0);
        }, 0);
    });
</script>
<style>


</style>