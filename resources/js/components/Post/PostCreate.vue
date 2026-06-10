<template>
    <div class="overlay" @mousedown="closeModal(false)">                         
        <div class="chatCreate scrollable" @mousedown.stop>     
            <div class="recordFormTitle" style="display:flex">
                <p>{{ editTarget ? `${appNameJp}を編集する` : `新しい${appNameJp}を作成する`}}</p>
                <div class="m-close-button" @click="closeModal(false)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            
            <div v-if="appName == 'post'" style="display: flex; gap: 15px;font-size: 14px;flex-wrap: wrap;">
                <div @click="app_type = 0" :class="['pt-selector', { ptSelected: app_type == 0}]">
                    <PostIcon which="0" size="20"/>
                    ナイス
                </div>
                <div @click="app_type = 2" :class="['pt-selector', { ptSelected: app_type == 2}]">
                    <PostIcon which="2" size="20"/>
                    チャレンジ
                </div>
                <div @click="app_type = 3" :class="['pt-selector', { ptSelected: app_type == 3}]">
                    <PostIcon which="3" size="20"/>
                    ニュース
                </div>
                <div @click="app_type = 6" :class="['pt-selector', { ptSelected: app_type == 6}]">
                    <PostIcon which="6" size="20"/>
                    リフレッシュ
                </div>
            </div>
             <div class="si-box" v-if="app_type == 2">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">NPO団体に寄付する</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;margin-top: 10px;">    
                    <input v-model="donatable" type="checkbox" id="donate">
                    <label for="donate" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div> 
            </div>    
            
           
            <div class="si-box" v-if="app_type == 2 && donatable">
                <ItemSelector 
                    placeHolder="寄付先"
                    :options="npoList"
                    rules="required"
                    v-model="selectedNpo"
                    :multiple="false"
                    :clearable="true"
                    :reduce="option => option"
                    :close-on-select="true"
                />
                <p class="m-0 text-xs text-[gray]">新しい寄付先を追加したい場合は、経営管理本部に連絡してください。</p>
                
            </div>
            <div class="si-box" v-if="app_type == 2">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">チャレンジの種類</p>
                </div>
                <div
                    :class="['selectSwitchArea', 'mini-switch-area', { 'mini-switch-area-disabled': isMiniLocked }]"
                    style="display: flex;width: 100%;margin-top: 10px;"
                >    
                    <input :disabled="isMiniLocked" v-model="mini" type="checkbox" id="mini">
                    <label
                        for="mini"
                        style="min-width: 80px;"
                        :class="['cursor-pointer', { 'mini-switch-label-disabled': isMiniLocked }]"
                    ><span class="mini-switch-splash"></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div> 
                <p class="text-[12px] text-[gray] mt-3" v-if="mini">ミニチャレンジの場合、最大チャージ額は500円となり、経費は発生しません</p>
            </div>  
            <div v-if="app_type == 2" class="si-box flex flex-col gap-5">
                <LongInput 
                    place-holder="アイデア（任意)"
                    v-model="challengeIdea"
                />
               
                <p class="text-xs text-[gray]">
                    アイデアの入力は任意です。入力・選択した内容をもとにチャレンジを自動生成できます。
                </p>
                <!-- <CommandButton
                    :custom-style="loading ? 'opacity: 0.75; pointer-events: none;' : undefined"
                    :buttons="[
                        { title: loading ? '生成中...' : '自動生成', action: () => execute()}
                    ]" 
                /> -->
                <LoaderButton class="!m-0" :content="loading ? '生成中...' : '自動生成'" :loading="loading" @triggered="execute">
                    <template #icon>
                        <AiIcon size="20" fill="#fff" class="mr-3"/>
                    </template>
                </LoaderButton>
                <div v-if="loading" class="ai-generation-loader" role="status" aria-live="polite">
                    <div class="image-wrap">
                        <img
                            class="oikawa-normal"
                            src="/images/minisuke.webp"
                            alt=""
                        />
                    </div>
                    <div class="ai-generation-loader-copy">
                        <p class="ai-generation-loader-title">AIがチャレンジを自動生成中です</p>
                        <div class="flex items-center gap-1">
                            <p class="ai-generation-loader-text">入力内容を整理して、タイトル・内容・達成条件を作成しています</p>
                        </div>
                    </div>                    
                </div>
            </div>
            <div class="si-box flex flex-col gap-3" v-if="generated_challenges.length">
                <p class="text-sm">自動生成されたチャレンジ</p>
                <div v-for="challenge in generated_challenges">
                    
                    <div 
                        @click="selectGeneratedChallenge(challenge)"
                        :class="['difficult-chip', { active: challenge.title == title}]"
                    >
                        <p class="text-xs">{{ challenge.title }}</p>

                    </div>
                </div>
            </div>
            <div class="si-box">
                <TagSelector 
                    placeHolder="タグ選択（＃なし）"
                    :suggestion="tagSuggestionText"
                    v-model="tags"
                />
            </div>

            <div class="si-box">
                <ShortInput 
                    name="recordTitle" 
                    placeHolder="タイトルを入力（必須）" 
                    :rules="'required'"
                    customClass="full"
                    ref="recordTitle"
                    type="text"
                    v-model="title"
                />
            </div>
                    
            
            <div class="si-box" v-if="app_type == 0">
                <MemberSelector 
                    :placeHolder="'宛先選択（必須）'"
                    rules="required"
                    name="recordUsers"
                    :multiple="true"
                    ref="recordUsers"
                    :path="possiblePath"
                    :closeOnSelect="false"
                    v-model="to_users"
                />
            </div>
            <div class="si-box" v-if="app_type == 2">
                <MemberSelector 
                    :placeHolder="'プレイヤー選択（必須）'"
                    rules="required"
                    name="recordUsers"
                    :multiple="true"
                    ref="recordUsers"
                    :path="possiblePath"
                    :closeOnSelect="false"
                    v-model="to_users"
                />
            </div>
            <div v-if="appName == 'challenge' || app_type == 2" class="si-box">   
                <LongInput
                    v-model="content_rule"  
                    ref="contentRuleRef"
                    :placeHolder="`${appNameJp}内容を入力（必須）`"
                    name="contentRuleRef"
                    rules="required|max:2000"
                />  
            </div>

            <div v-else class="si-box">     
                <LongInput
                    v-model="content"  
                    ref="contentRef"
                    :placeHolder="`${appNameJp}内容を入力（必須）`"
                    name="contentRef"
                    rules="required|max:2000"
                />  
            </div>
        
        
            <div class="si-box" v-if="appName == 'challenge' || app_type == 2">    
                <LongInput
                    v-model="content_goal"  
                    ref="contentGoalRef"
                    placeHolder="達成条件（必須）"
                    name="recordRule"
                    rules="required|max:2000"
                /> 
            </div>
            <div class="si-box" v-if="app_type == 2">
                <div class="challenge-category-header">
                    <p class="form-lbl challenge-category-label">チャレンジカテゴリ</p>
                    <button
                        v-if="shouldShowSuggestedCategoryAction"
                        type="button"
                        class="challenge-category-suggest-button"
                        @click="applySuggestedCategory(true)"
                    >
                        提案を適用
                    </button>
                </div>
                <p v-if="suggestedChallengeCategoryLabel" class="challenge-category-hint">
                    入力内容から「{{ suggestedChallengeCategoryLabel }}」を提案しています。
                </p>
                <div class="challenge-category-section">
                    <p class="challenge-category-section-title">メインカテゴリ</p>
                    <div class="challenge-category-grid">
                        <button
                            v-for="category in challengeCategories"
                            :key="category.label"
                            type="button"
                            :class="['challenge-category-chip', { active: selectedChallengeMainCategory == category.label }]"
                            @click="selectChallengeMainCategory(category.label, true)"
                        >
                            {{ category.label }}
                        </button>
                    </div>
                </div>
                <div v-if="activeChallengeCategory" class="challenge-category-section">
                    <p class="challenge-category-section-title">サブカテゴリ</p>
                    <div class="challenge-category-grid">
                        <button
                            v-for="subcategory in activeChallengeCategory.subcategories"
                            :key="subcategory"
                            type="button"
                            :class="['challenge-category-chip', 'sub', { active: selectedChallengeSubCategory == subcategory }]"
                            @click="selectChallengeSubCategory(subcategory, true)"
                        >
                            {{ subcategory }}
                        </button>
                    </div>
                </div>
                <p v-if="challengeCategoryValidationError" class="challenge-category-error">
                    チャレンジカテゴリとサブカテゴリを選択してください。
                </p>
            </div>
            <div class="si-box" v-if="app_type == 2 && !mini">
                <p class="form-lbl" style="font-size: 14px;">必要経費</p>
               <PostExpenses 
                    v-for="cost, index in costs"
                    :key="index"
                    v-model:content="cost.content"
                    v-model:expenses="cost.expenses"
                    v-model:file_path="cost.file_path"
                    :fieldIndex="index"
                    @addCostField="addCostField"
                    @removeCostField="removeCostField"
                    @removeFile="removeFile"
               />
            </div>
            <div class="si-box" v-if="appName == 'challenge' || app_type == 2">
                <p class="form-lbl" style="font-size: 14px;">実施期間（必須）</p>
                <div style="display:flex;margin-top: 10px;position: relative;width:100%">
                    <ShortInput 
                        name="recordDateStart" 
                        :rules="'required'"
                        :initialValue="date_start"
                        customClass="date"
                        ref="recordDateStart"
                        type="date"
                        v-model="date_start"
                    />
                    <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                    <ShortInput 
                        name="recordDateEnd" 
                        :rules="'required'"
                        :initialValue="date_end"
                        customClass="date"
                        ref="recordDateEnd"
                        type="date"
                        v-model="date_end"
                    />
                </div>
                <span v-if="dateComparsionError.hasError" class="form-error" style="font-size: 12px;color:tomato;position: absolute; bottom: -15px">{{ dateComparsionError.message }}</span>       
            </div>
            <!-- <div class="si-box" v-if="app_type == 2">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">チャージ</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;margin-top: 10px;">    
                    <input v-model="chargeable" type="checkbox" id="charge">
                    <label for="charge" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div> 
            </div>        -->
            
            <div class="si-box" v-if="app_type == 6">
                <ShortInput 
                    placeHolder="金額 (必須)"
                    :rules="'required'"
                    customClass="full"
                    ref="refreshAmountRef"
                    type="number"
                    v-model="refresh_amount"
                />
                <div class="refresh-balance-panel">
                    <div class="refresh-balance-row">
                        <span>現在保有額</span>
                        <strong v-if="!refreshSummaryLoading">{{ formatCurrency(refreshCurrentBalance) }}</strong>
                        <strong v-else>確認中...</strong>
                    </div>
                    <p v-if="!refreshSummaryLoading" class="refresh-balance-note">申請時点で使える残高です。</p>
                    <p v-if="refreshBalanceWarning" class="refresh-balance-warning">{{ refreshBalanceWarning }}</p>
                </div>
            </div>
            <div class="si-box">
                <FileUploader
                    :customPlaceHolder="refreshPlaceholder"
                    v-model="uploadedFiles"
                    path="/post_files"
                    :rules="app_type == 6 ? 'required' : ''"
                    :key="`file-uploader-${app_type}`"
                    ref="uploadedRefreshRef"
                />
            </div>
            <div class="si-box" v-if="app_type == 6">
                <FileUploader 
                    customPlaceHolder="領収（必須）（非公開）" 
                    v-model="uploadedReceipts" 
                    path="/post_receipts"
                    rules="required"
                    ref="uploadedReceiptsRef"
                />
            </div>
            <div class="si-box">
                <ShortInput 
                    name="recordUrl" 
                    placeHolder="URL" 
                    :initialValue="referrer"
                    customClass="full"
                    ref="recordUrl"
                    type="text"
                    v-model="referrer"
                />
            </div>        
                    
                    
            <div class="si-box">
                <LoaderButton @triggered="createSend" :loading="processing" :content="editTarget ? '保存する' : '投稿する'"/>
            </div>               
        
        </div>
    </div>      
</template>

<script setup lang="ts">      
import TagSelector from '../Form/TagSelector.vue'
import LoaderButton from '../Global/LoaderButton.vue'
import { computed, onMounted, ref, useTemplateRef, reactive, watch } from 'vue'
import ShortInput from '../Form/ShortInput.vue'
import LongInput from '../Form/LongInput.vue'
import MemberSelector from '../Form/MemberSelector.vue'
import { useAuthUserStore } from '@/store/auth'
import { useSharingDataStore } from '@/store/sharingData'
import FileUploader from '../Form/FileUploader.vue'
import PostIcon from './PostIcon.vue'
import { DateTime } from 'luxon'
import { useApi } from '@/composables/api'
import { Post, PostQuery } from '@/interface/postInterface'
import { useDialog } from '@/composables/dialog'
import PostExpenses from './PostExpenses.vue'
import ItemSelector from '../Form/ItemSelector.vue'
import { User } from '@/interface/globalInterface'
import {
    challengeCategories,
    challengeSuggestionRules,
    type ChallengeCategorySuggestion
} from '@/utils/challengeCategory'
import { donationTargets } from '@/utils/donationTargets'
import CommandButton from '../Global/CommandButton.vue'
import AiIcon from '../Icons/AiIcon.vue'
import { useRoute } from 'vue-router'
import { useDashboardStore } from '@/store/dashboard'
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const route = useRoute()
    const { getBatchDashboardData } = useDashboardStore()

    const props = defineProps<{
        appNameJp: string,
        appName: string,
        editTarget: Post | null,
        getQuery: PostQuery
        popup?: boolean
    }>()

    const emit = defineEmits<{
        'postFinish': [flag: boolean, id?: number]
    }>()
    type generatedChallenge = {
        title: string
        content_rule: string
        achievement_condition: string
        main_category: string
    }
    const app_type = ref(props.editTarget && props.editTarget.app_type ? props.editTarget.app_type : props.getQuery?.app_type ? props.getQuery?.app_type : 0)
    const title = ref(props.editTarget && props.editTarget.title ? props.editTarget.title : "")
    const content = ref(props.editTarget && props.editTarget.content ? props.editTarget.content : "")
    const content_rule = ref(props.editTarget && props.editTarget.content_rule ? props.editTarget.content_rule : "")
    const content_goal = ref(props.editTarget && props.editTarget.content_goal ? props.editTarget.content_goal : "")
    const selectedChallengeMainCategory = ref(props.editTarget?.challenge_main_category ?? '')
    const selectedChallengeSubCategory = ref(props.editTarget?.challenge_sub_category ?? '')
    const to_users = ref<User[]>(props.editTarget && props.editTarget.to_users ? props.editTarget.to_users : [])
    const referrer = ref(props.editTarget && props.editTarget.referrer ? props.editTarget.referrer : "")
    const refresh_amount = ref(props.editTarget && props.editTarget.refresh_amount ? props.editTarget.refresh_amount : "")
    const challengeRelayId = computed(() => {
        const id = parseInt(String(route.query.relay_id ?? ''))
        return Number.isNaN(id) ? null : id
    })
    const isRelayChallengeCreate = computed(() => app_type.value == 2 && !props.editTarget && Boolean(challengeRelayId.value))
    
    const tags = ref(props.editTarget && props.editTarget.tags ? props.editTarget.tags : [])    
    const date_start = ref(props.editTarget && props.editTarget.date_start ? props.editTarget.date_start : "")
    const date_end = ref(props.editTarget && props.editTarget.date_end ? props.editTarget.date_end : "")
    const processing = ref(false)
    const uploadedFiles = ref(props.editTarget && props.editTarget.files ? props.editTarget.files : [])
    const uploadedReceipts = ref(props.editTarget && props.editTarget.receipts ? props.editTarget.receipts : [])
    const recordTitle = useTemplateRef('recordTitle')
    const recordUsers = useTemplateRef('recordUsers')
    const refreshAmountRef = useTemplateRef('refreshAmountRef')
    const contentRuleRef = useTemplateRef('contentRuleRef')
    const contentRef = useTemplateRef('contentRef')
    const contentGoalRef = useTemplateRef('contentGoalRef')
    const recordDateEnd = useTemplateRef('recordDateEnd')
    const recordDateStart = useTemplateRef('recordDateStart')
    const uploadedReceiptsRef = useTemplateRef('uploadedReceiptsRef')
    const uploadedRefreshRef = useTemplateRef('uploadedRefreshRef')
    const selectedNpo = ref(props.editTarget && props.editTarget.donation_target ? props.editTarget.donation_target : null)
    const chargeable = ref(true)
    const donatable = ref(false)
    const mini = ref(props.editTarget?.mini ?? false)
    const npoList = donationTargets
    const api = useApi()
    const { ping, ask } = useDialog()
    const refreshSummary = ref<{ current_balance: number } | null>(null)
    const refreshSummaryLoading = ref(false)
    const challengeCategoryTouched = ref(Boolean(selectedChallengeMainCategory.value || selectedChallengeSubCategory.value))
    const challengeCategoryValidationError = ref(false)
    const challengeIdea = ref('')
    const generated_challenges = ref<generatedChallenge[]>([])
    const validateTargets = computed(() => {
        return [
            recordTitle.value,
            recordUsers.value,
            contentRuleRef.value,
            contentRef.value,
            contentGoalRef.value,
            recordDateEnd.value,
            recordDateStart.value,
            // npoRef.value,
            uploadedReceiptsRef.value,
            refreshAmountRef.value,
            app_type.value == 6 ? uploadedRefreshRef.value : null
        ]
    })
    const costs = reactive<{
        content: string
        expenses: number | null
        file_path: string | null
    }[]>([])
    const loading = ref(false)
    const isMiniLocked = computed(() => Boolean(props.popup) || isRelayChallengeCreate.value)
    const refreshPlaceholder = computed(() => {
        return app_type.value == 6 ? 'リフレッシュ写真（必須）（公開）' : 'ファイル'
    })
    const activeChallengeCategory = computed(() => {
        return challengeCategories.find(category => category.label == selectedChallengeMainCategory.value) ?? null
    })
    const suggestedChallengeCategory = computed<ChallengeCategorySuggestion | null>(() => {
        const sourceText = `${title.value}\n${content_rule.value}\n${content_goal.value}`

        for (const rule of challengeSuggestionRules) {
            if (rule.pattern.test(sourceText)) {
                return {
                    main: rule.main,
                    sub: rule.sub
                }
            }
        }

        return null
    })
    const suggestedChallengeCategoryLabel = computed(() => {
        if (!suggestedChallengeCategory.value || app_type.value !== 2) {
            return ''
        }

        return `${suggestedChallengeCategory.value.main} / ${suggestedChallengeCategory.value.sub}`
    })
    const shouldShowSuggestedCategoryAction = computed(() => {
        if (app_type.value !== 2 || !suggestedChallengeCategory.value) {
            return false
        }

        return (
            selectedChallengeMainCategory.value !== suggestedChallengeCategory.value.main
            || selectedChallengeSubCategory.value !== suggestedChallengeCategory.value.sub
        )
    })
    const refreshCurrentBalance = computed(() => {
        return Number(refreshSummary.value?.current_balance ?? 0)
    })
    const refreshBalanceWarning = computed(() => {
        const requestedAmount = Number(refresh_amount.value || 0)

        if (app_type.value !== 6 || requestedAmount <= 0) {
            return ''
        }

        if (requestedAmount > refreshCurrentBalance.value) {
            return '現在保有額を超えています。承認時に残高までで調整される場合があります。'
        }

        return ''
    })
    const selectChallengeMainCategory = (categoryLabel: string, markTouched = false) => {
        selectedChallengeMainCategory.value = categoryLabel

        if (!activeChallengeCategory.value?.subcategories.includes(selectedChallengeSubCategory.value)) {
            selectedChallengeSubCategory.value = ''
        }

        if (markTouched) {
            challengeCategoryTouched.value = true
        }

        challengeCategoryValidationError.value = false
    }
    const selectChallengeSubCategory = (subcategory: string, markTouched = false) => {
        selectedChallengeSubCategory.value = subcategory

        if (markTouched) {
            challengeCategoryTouched.value = true
        }

        challengeCategoryValidationError.value = false
    }
    const applySuggestedCategory = (markTouched = false) => {
        if (!suggestedChallengeCategory.value) {
            return
        }

        selectChallengeMainCategory(suggestedChallengeCategory.value.main, markTouched)
        selectChallengeSubCategory(suggestedChallengeCategory.value.sub, markTouched)
    }
    const possiblePath = computed(() => {
        return app_type.value == 2 ? 'post_get_challenge_users' : `post_get_post_users`
    })
    const applyRelayChallengeDefaults = () => {
        if (!isRelayChallengeCreate.value || !auth.activeUser?.id) {
            return
        }

        mini.value = true
        const currentUser = {
            ...auth.activeUser,
            name: auth.activeUser.name ?? '',
        } as User

        if (!to_users.value.some(user => user && user.id == currentUser.id)) {
            to_users.value.push(currentUser)
        }
    }
    const dateComparsionError = computed(() =>{
        const duration =
            (DateTime.fromISO(date_end.value)
                .diff(DateTime.fromISO(date_start.value), 'days')
                .days ?? 0) + 1;        
        
        if (!DateTime.fromISO(date_start.value).isValid || !DateTime.fromISO(date_end.value).isValid) {
            return {
                hasError: false,
                message: ''
            }
        }
        if (duration < 0) {
            return{
                hasError: duration < 0,
                message: '終了日は開始日より前にすることはできません。'
            }                      

        } else if (!mini.value && duration < 14) {
            return {
                hasError: duration < 14,
                message: '実施期間は最低14日間以上必要です。'
            }
        } else if (mini.value && duration < 7) {
            return {
                hasError: mini.value && duration < 7,
                message: 'ミニは実施期間を7日以上にする必要があります。'
            }
        } else if (mini.value && duration > 30) {
            return {
                hasError: mini.value && duration > 30,
                message: 'ミニは実施期間を30日以内にする必要があります。'
            }
            
        } else {
            return {
                hasError: false,
                message: ''
            }
        }
    })
    onMounted(() => {
        if(!props.editTarget && sharingData.active){
            if(app_type.value == 2){
                content_rule.value = sharingData.text
            }else{
                content.value = sharingData.text
            }
        }
        costsFill()
        loadRefreshSummary()

    })
    watch(donatable, async(newVal) => {
        if (newVal) {
            const result = await ask('必要経費以外のチャージ総額はNPOに寄付します。よろしいでしょうか?')
            console.log('User response to donation confirmation:', result.value)
            if(!result.value){
                donatable.value = false
            }
        }
    })
    watch([() => props.popup, isRelayChallengeCreate], ([isPopup, isRelay]) => {
        if (isPopup || isRelay) {
            mini.value = true
        }
    }, { immediate: true })
    watch(mini, async (newVal) => {
        if (isMiniLocked.value) {
            if (!newVal) {
                mini.value = true
            }
            return
        }

        if (newVal) {
            if (costs.some(cost => cost.expenses && cost.expenses > 0)) {
                costs.forEach(cost => {
                    cost.expenses = null
                })
            }
        }
    })
    watch([title, content_rule, content_goal, app_type], () => {
        if (app_type.value !== 2) {
            return
        }

        if (challengeCategoryTouched.value) {
            return
        }

        applySuggestedCategory(false)
    }, { immediate: true })
    watch(app_type, (newVal) => {
        applyRelayChallengeDefaults()
        if(newVal == 0 && auth.user){
            to_users.value = to_users.value.filter(user => user && user.id != auth.id)
        }
        if(newVal == 2 && auth.user && !to_users.value.length){
            if(!to_users.value.find(user => user && user.id == auth.id)){
                to_users.value.push(auth.user as unknown as User)
            }
        }
        if(newVal == 6){
            loadRefreshSummary()
        }else{
            refreshSummary.value = null
        }
    }, { immediate: true })
    watch(() => auth.activeUser?.id, () => {
        applyRelayChallengeDefaults()
    })
    watch(to_users, () => {
        applyRelayChallengeDefaults()
    }, { deep: true })
    watch(isRelayChallengeCreate, () => {
        applyRelayChallengeDefaults()
    })
    watch(selectedChallengeMainCategory, () => {
        challengeCategoryValidationError.value = false
    })
    watch(selectedChallengeSubCategory, () => {
        challengeCategoryValidationError.value = false
    })
    const loadRefreshSummary = async() => {

        if(app_type.value != 6){
            return
        }
        
        const data = await api.get('/refresh/me/summary', null, {
            loadingRef: refreshSummaryLoading,
            silent: true
        })

        if(data){
            refreshSummary.value = data
        }
    }
    const formatCurrency = (value: number) => {
        return `${Number(value || 0).toLocaleString()}円`
    }
    
    const addCostField = () => {
        if(costs.length >= 10){
            ping('上限は10個です。')
            return
        }
        costs.push({
            content: '',
            expenses: null,
            file_path: null,
        })
    }
    const removeCostField = async(index:number) => {
        costs.splice(index, 1)
        if(costs.length == 0){
            addCostField()
        }
    }
    const removeFile = async(index:number) => {
        await api.post('post_remove_file', { file_path: costs[index].file_path })
        costs[index].file_path = null
    }
    const tagSuggestionText = computed(() => {
        const gTitle = title.value ? `${title.value}` : ''
        const gContent = content.value ? `${content.value}` : ''
        const gContentRule = content_rule.value ? `${content_rule.value}` : ''
        const gContentGoal = content_goal.value ? `${content_goal.value}` : ''
        return `${gTitle}\n${gContent}\n${gContentRule}\n${gContentGoal}` 
    })
    const costsFill = () => {
        // if(timeCard.value?.timecard_costs?.length){
        //     timeCard.value.timecard_costs.forEach(cost => {
        //         const boil = { ...cost}
        //         costs.push(boil)
        //     });
        // }
        if(!costs.length){
            addCostField()
        }
    }
    const createSend = async() => {
        const targets = validateTargets.value.filter(ob => ob !== null)
        let result = true
        for(const target of targets){
            
            const val = await target?.validate() || {valid: false}
            result = result && val.valid
        }
        if (!result || dateComparsionError.value.hasError) {
            ping('入力内容に不備があります。')
            return
        }
        if (app_type.value == 2 && (!selectedChallengeMainCategory.value || !selectedChallengeSubCategory.value)) {
            challengeCategoryValidationError.value = true
            ping('チャレンジカテゴリを選択してください。')
            return
        }
        if (app_type.value == 2) {
            const confirm = await ask('入力内容には間違いないかを確認してください。\n作成後は編集できません。')
            if (!confirm.value) return
        }
        
        processing.value = true      

            
        const params = {
            edit_id: props.editTarget ? props.editTarget.id : null,
            to_users: to_users.value.length ? to_users.value.map(ob => ob.id) : [], 
            title: title.value, 
            content_rule: content_rule.value, 
            content_goal: content_goal.value, 
            date_start: date_start.value, 
            date_end: date_end.value,  
            tags: tags.value.length ? tags.value.map(ob => ob.text).map(text => text.replace(/[＃#]/g, '')) : [], 
            file_ids : uploadedFiles.value.length ? uploadedFiles.value.map(ob => ob.id) : [], 
            receipt_ids: uploadedReceipts.value.length ? uploadedReceipts.value.map(ob => ob.id) : [],
            referrer: referrer.value, 
            path: props.appName,
            post_content: content.value,
            award_entry: 0,
            app_type: app_type.value,
            chargeable: chargeable.value,
            grantable: costs.some(cost =>
                cost.content.trim() !== '' && cost.expenses !== null
            ),
            donatable: donatable.value,
            donation_target: selectedNpo.value,
            refresh_amount: refresh_amount.value,
            challenge_main_category: app_type.value == 2 ? selectedChallengeMainCategory.value : null,
            challenge_sub_category: app_type.value == 2 ? selectedChallengeSubCategory.value : null,
            grants: costs,
            mini: mini.value,
            challenge_relay_id: app_type.value == 2 ? challengeRelayId.value : null,
        }

        const data = await api.post('/post_add_record', params, {
            toast: props.editTarget ? '編集しました。' : '投稿しました。',
            loadingRef: processing,
            
        })
        if (!props.editTarget && [0, 2].includes(Number(app_type.value))) {
            await getBatchDashboardData(['challenges'])
        }

        closeModal(true, data.record.id)               
    }
    const closeModal = (flag: boolean, id?: number) => {
        processing.value = false
        const shareData = {
            active: false,
            title: '',
            text: '',
            files: [],
            from: '',
            to: '',
            drag: false,
            instruction: ''
        }
        sharingData.setSharingData(shareData)
        emit('postFinish',flag, id);          
    }

    const execute = async () => {
        if(loading.value) return

        const user_ids = to_users.value.map(u => u.id)
        const user_id = user_ids.includes(auth.user.id)
            ? auth.user.id
            : user_ids[0] ?? null
        if (!user_id) {
            ping('プレイヤーを選択してください。')
            return
        }
        const data = await api.post('/suggest_challenge', { challenger: user_id, idea: challengeIdea.value, mini: mini.value }, {
            loadingRef: loading
        })
        if (data?.generated_challenge) {
            title.value = data.generated_challenge.title
            content_rule.value = data.generated_challenge.content_rule
            content_goal.value = data.generated_challenge.content_goal
        }
        if (data?.generated_challenges.length && mini.value) {
            generated_challenges.value = data.generated_challenges
        }
        
    }
    const selectGeneratedChallenge = (challenge: generatedChallenge) => {
        title.value = challenge.title
        content_rule.value = challenge.content_rule
        content_goal.value = challenge.achievement_condition
        selectedChallengeMainCategory.value = challenge.main_category
    }


   
</script>
<style scoped>
.challenge-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.challenge-category-label {
    font-size: 14px;
}

.challenge-category-hint {
    margin: 10px 0 0;
    font-size: 12px;
    line-height: 1.5;
    color: var(--sub-color);
}

.challenge-category-section {
    margin-top: 14px;
}

.challenge-category-section-title {
    margin: 0 0 8px;
    font-size: 12px;
    color: var(--sub-color);
}

.challenge-category-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.challenge-category-chip,
.challenge-category-suggest-button {
    border: 1px solid var(--check-inactive);
    background: transparent;
    color: var(--primary-color);
    font-size: 13px;
    line-height: 1.4;
    padding: 8px 12px;
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.challenge-category-chip.sub {
    font-size: 12px;
}

.challenge-category-chip.active,
.challenge-category-suggest-button {
    border-color: var(--primary-color);
    background: var(--bg3);
}

.challenge-category-error {
    margin: 10px 0 0;
    font-size: 12px;
    color: tomato;
}
.difficult-chip {
    cursor: pointer;
    border: 1px solid var(--check-inactive);
    padding: 8px;
}
.difficult-chip.active {
    border-color: var(--primary-color);
    background: var(--bg3);
}
.ai-generation-loader {
    position: relative;
    display: flex;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    min-height: 68px;
    padding: 12px 14px;
    overflow: hidden;
    border: 1px solid var(--check-inactive);
    background: var(--bg3);
    color: var(--primary-color);
}
.image-wrap {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--background-color);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    /* box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28); */
    border: 1px solid var(--calendarBorder);
}
.oikawa-normal {
    width: 40px;
    object-fit: contain;
    opacity: 1;
}
/* .ai-generation-loader::before {
    content: "";
    position: absolute;
    inset: 0;
    width: 45%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.32), transparent);
    animation: ai-loader-shimmer 1.8s ease-in-out infinite;
    pointer-events: none;
} */

.ai-generation-loader-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: 1px solid var(--check-inactive);
    border-radius: 50%;
    background: var(--background-color);
    animation: ai-loader-pulse 1.4s ease-in-out infinite;
}

.ai-generation-loader-copy {
    position: relative;
    min-width: 0;
}

.ai-generation-loader-title {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
}

.ai-generation-loader-text {
    margin: 2px 0 0;
    color: var(--sub-color);
    font-size: 12px;
    line-height: 1.45;
}

.ai-generation-loader-dots {
    position: relative;
    display: flex;
    gap: 4px;
    align-items: center;
}

.ai-generation-loader-dots span {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--primary-color);
    opacity: 0.35;
    animation: ai-loader-dot 1.1s ease-in-out infinite;
}

.ai-generation-loader-dots span:nth-child(2) {
    animation-delay: 0.16s;
}

.ai-generation-loader-dots span:nth-child(3) {
    animation-delay: 0.32s;
}
.ai-eye {
  animation: blink 2.4s infinite;
  transform-box: fill-box;
  transform-origin: center;
}

@keyframes blink {
  0%, 88%, 100% { transform: scaleY(1); }
  92%, 96% { transform: scaleY(.15); }
}
.refresh-balance-panel {
    margin-top: 10px;
    padding: 10px 12px;
    background: var(--bg3);
    color: var(--primary-color);
}

.refresh-balance-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    line-height: 1.5;
}

.refresh-balance-row + .refresh-balance-row {
    margin-top: 4px;
}

.refresh-balance-note {
    margin: 6px 0 0;
    font-size: 12px;
    line-height: 1.45;
    color: var(--sub-color);
}

.refresh-balance-warning {
    margin: 8px 0 0;
    font-size: 12px;
    line-height: 1.45;
    color: #c45a3a;
}

.mini-switch-area-disabled {
    opacity: 0.6;
}

.mini-switch-label-disabled {
    cursor: not-allowed !important;
}

.mini-switch-area-disabled .switch-toggle {
    filter: grayscale(0.5);
}
.mini-switch-splash::after{
    content: "通常";
}
.mini-switch-area input[type="checkbox"]:checked+label .mini-switch-splash:after {
    content: "ミニ";
}

@keyframes ai-loader-pulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.78;
    }
    50% {
        transform: scale(1.06);
        opacity: 1;
    }
}

@keyframes ai-loader-dot {
    0%,
    100% {
        transform: translateY(0);
        opacity: 0.35;
    }
    50% {
        transform: translateY(-4px);
        opacity: 1;
    }
}

@keyframes ai-loader-shimmer {
    0% {
        transform: translateX(-120%);
    }
    100% {
        transform: translateX(240%);
    }
}

@media (max-width: 480px) {
    .ai-generation-loader {
        grid-template-columns: 30px minmax(0, 1fr);
        gap: 10px;
    }

    .ai-generation-loader-icon {
        width: 30px;
        height: 30px;
    }

    .ai-generation-loader-dots {
        grid-column: 2;
    }
}

</style>
    
    
    
    
    
    
