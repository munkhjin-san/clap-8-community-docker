<template>
    <div class="overlay" @mousedown="closeModal(false, null)">                         
        <div class="chatCreate scrollable" @mousedown.stop>     
            <div class="recordFormTitle" style="display:flex">
                <p>{{ editTarget ? `${appNameJp}を編集する` : `新しい${appNameJp}を作成する`}}</p>
                <div class="m-close-button" @click="closeModal(false, null)" style="position:unset; margin:auto 0 auto auto">
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
                <div @click="app_type = 1" :class="['pt-selector', { ptSelected: app_type == 1}]">
                    <PostIcon which="1" size="20"/>
                    ナレッジ
                </div>
                <div @click="app_type = 2" :class="['pt-selector', { ptSelected: app_type == 2}]">
                    <PostIcon which="2" size="20"/>
                    チャレンジ
                </div>
                <!-- <div @click="app_type = 3" :class="['pt-selector', { ptSelected: app_type == 3}]">
                    <PostIcon which="3" size="20"/>
                    ツィート
                </div> -->
                <!-- <div @click="app_type = 4" :class="['pt-selector', { ptSelected: app_type == 4}]">
                    <PostIcon which="4" size="20"/>
                    ヘルプ
                </div> -->
                <div @click="app_type = 5" :class="['pt-selector', { ptSelected: app_type == 5}]">
                    <PostIcon which="5" size="20"/>
                    グラリンピック
                </div>
                <!-- <div @click="app_type = 6" :class="['pt-selector', { ptSelected: app_type == 6}]">
                    <PostIcon which="6" size="20"/>
                    リフレッシュ
                </div> -->
            </div>
            <div class="si-box" v-if="app_type == 2">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">チャレンジ補助金</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;margin-top: 10px;">    
                    <input v-model="grantable" type="checkbox" id="charge">
                    <label for="charge" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div> 
            </div>    
            <div class="si-box" v-if="app_type == 5">
                <p class="mb-[20px]">寄付先</p>
                <OptionSelector 
                    :options="npoList"
                    rules="required"
                    name="npo"
                    unit=""
                    ref="npoRef"
                    v-model="selectedNpo"
                />
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
                    
            
            <div class="si-box" v-if="appName == 'challenge' || app_type == 0 || app_type == 2">
                <MemberSelector 
                    :placeHolder="app_type == 2 ?  'プレイヤー選択（必須）' : appName == 'post' ? '宛先選択（必須）' : ''"
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
            <div class="si-box" v-if="app_type == 2 && grantable">
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
            <div class="si-box" v-if="app_type == 2">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">チャージ</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;margin-top: 10px;">    
                    <input v-model="chargeable" type="checkbox" id="charge">
                    <label for="charge" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div> 
            </div>       
            
            <div class="si-box" v-if="app_type == 6">
                <ShortInput 
                    placeHolder="額 (必須)"
                    :rules="'required'"
                    customClass="full"
                    ref="refreshAmountRef"
                    type="number"
                    v-model="refresh_amount"
                />
            </div>
            <div class="si-box">
                <FileUploader
                    :customPlaceHolder="refreshPlaceholder"
                    v-model="uploadedFiles"
                    path="/post_files"
                    :key="`file-uploader-${app_type}`"
                />
            </div>
            <div class="si-box" v-if="app_type == 6">
                <FileUploader 
                    customPlaceHolder="領収（必須）" 
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
import { computed, onMounted, ref, useTemplateRef, reactive } from 'vue'
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
import OptionSelector from '../Form/OptionSelector.vue'
import { useDialog } from '@/composables/dialog'
import PostExpenses from './PostExpenses.vue'
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()

    const props = defineProps<{
        appNameJp: string,
        appName: string,
        editTarget: Post | null,
        getQuery: PostQuery
    }>()

    const emit = defineEmits<{
        'postFinish': [boolean, number | null]
    }>()
    const app_type = ref(props.editTarget && props.editTarget.app_type ? props.editTarget.app_type : props.getQuery?.app_type ? props.getQuery?.app_type : 0)
    const title = ref(props.editTarget && props.editTarget.title ? props.editTarget.title : "")
    const content = ref(props.editTarget && props.editTarget.content ? props.editTarget.content : "")
    const content_rule = ref(props.editTarget && props.editTarget.content_rule ? props.editTarget.content_rule : "")
    const content_goal = ref(props.editTarget && props.editTarget.content_goal ? props.editTarget.content_goal : "")
    const to_users = ref(props.editTarget && props.editTarget.to_users ? props.editTarget.to_users : app_type.value === 2 ? [auth.user] : [])
    const referrer = ref(props.editTarget && props.editTarget.referrer ? props.editTarget.referrer : "")
    const refresh_amount = ref(props.editTarget && props.editTarget.refresh_amount ? props.editTarget.refresh_amount : "")
    
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
    const npoRef = useTemplateRef('npoRef')
    const selectedNpo = ref(props.editTarget && props.editTarget.donation_target ? props.editTarget.donation_target : null)
    const chargeable = ref(true)
    const grantable = ref(true)
    const npoList = [
        'e-Education',
        'にこスマ九州',
        'カラフルチェンジラボ',
        'アニマルレスキューハッピーりぼん',
        '日本赤十字社',
        '日本パラリンピック委員会',
        'カタリバ',
        'グリーンピース・ジャパン',
        '動物愛護団体NYANS',
        '宇和島NPOセンター',
        'はぴねすDOG',
        '大学女性協会',
        'NPO法人 Baby ぼけっと',
        '一般社団法人バクチャー普及研究協議会'
    ]
    const api = useApi()
    const { ping } = useDialog()
    const validateTargets = computed(() => {
        return [
            recordTitle.value,
            recordUsers.value,
            contentRuleRef.value,
            contentRef.value,
            contentGoalRef.value,
            recordDateEnd.value,
            recordDateStart.value,
            npoRef.value,
            uploadedReceiptsRef.value,
            refreshAmountRef.value
        ]
    })
    const costs = reactive<{
        content: string
        expenses: number | null
        file_path: string | null
    }[]>([])

    const refreshPlaceholder = computed(() => {
        return app_type.value === 6 ? 'リフレッシュ写真' : 'ファイル'
    })
    const possiblePath = computed(() => {
        return app_type.value === 2 ? 'post_get_challenge_users' : `post_get_post_users`
    })
    const dateComparsionError = computed(() =>{
        if(date_start.value && date_end.value){
            const wrongDuration = (DateTime.fromISO(date_start.value).diff(DateTime.fromISO(date_end.value), 'days').toObject().days ?? 0) > 0               
            return{
                hasError: wrongDuration,
                message: wrongDuration ? '終了日は開始日より前にすることはできません。' : ''
            }                      

        }else{
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

    })
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
    const removeCostField = async(index) => {
        costs.splice(index, 1)
        if(costs.length == 0){
            addCostField()
        }
    }
    const removeFile = async(index) => {
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
            grantable: grantable.value,
            donation_target: selectedNpo.value,
            refresh_amount: refresh_amount.value,
            grants: costs
        }

        const data = await api.post('post_add_record', params, {
            toast: props.editTarget ? '編集しました。' : '投稿しました。',
            loadingRef: processing,
            
        })

        closeModal(true, data.record.id)               
    }
    const closeModal = (flag, id) => {
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
</script>
    
    
    
    
    
    