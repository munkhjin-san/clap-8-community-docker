<template>
    <div class="overlay" @mousedown="emit('close', null)">          
        <div class="chatCreate" style="height:auto;max-width: 70%;" @mousedown.stop>            
            <div class="recordFormTitle" style="display:flex">
                <p>{{ isProgressReportInput ? '進捗報告' : 'ステータス変更' }}</p>
                <div class="cursor-pointer" @click="emit('close', null)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>   
            <div class="si-box" v-if="!progressReportMode">
                <div style="display: flex;gap: 15px;">
                    <div @click="selectStatus(status.id)" :class="['status-button', {'current-state' : status.id == selected}]" :key="status.id" v-for="status in statuses">{{ status.state }}</div>
                </div>
            </div>

            <div class="si-box" v-if="selected > 0 || isProgressReportInput">
                <LongInput
                    :initialValue="resultMessage"   
                    ref="recordBody"
                    :placeHolder="isProgressReportInput ? `現在の進捗を入力` : `結果内容を入力`"
                    name="recordBody"
                    :rules="isProgressReportInput ? 'required|max:2000' : 'max:2000'"
                    label="タイトル"
                    v-model="resultMessage"
                />
            </div>
            <div class="si-box" v-if="selected > 0 || isProgressReportInput">
                <FileUploader
                    v-model="uploadedFiles"
                    path="/post_files"
                />
            </div>
            <div class="si-box" v-if="challengeRelayHistory.length">
                <p class="text-[13px] mb-2">チャレンジリレー履歴</p>
                <div class="grid gap-1">
                    <p class="text-[12px] text-[gray]" v-for="relay in challengeRelayHistory" :key="relay.id">
                        {{ challengeRelayStatusText(relay) }}
                    </p>
                </div>
            </div>
            <div class="si-box" v-if="canPassChallengeRelay">
                <label class="challenge-relay-option">
                    <input type="checkbox" v-model="challengeRelayEnabled">
                    <span>ミニチャレンジのバトンを渡しますか？</span>
                </label>
                <MemberSelector
                    v-if="challengeRelayEnabled"
                    placeHolder="バトンを渡すメンバー"
                    rules="required"
                    name="challengeRelayUser"
                    :multiple="false"
                    path="post_get_challenge_users"
                    :exclude="auth.id ? [auth.id] : []"
                    v-model="challengeRelayUser"
                />
            </div>
            <div class="si-box">
                <LoaderButton @triggered="update" :loading="processing" content="保存する"/>
            </div>            
        </div>
    </div>  
</template>
<script setup lang="ts"> 
import LoaderButton from '../Global/LoaderButton.vue';
import FileUploader from '../Form/FileUploader.vue';
import LongInput from '../Form/LongInput.vue';
import MemberSelector from '../Form/MemberSelector.vue';
import { computed, onMounted, ref } from 'vue';
import { customParser } from '@/utils/tools';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { Post, PostRelay } from '@/interface/postInterface';
import { User } from '@/interface/globalInterface';
import { useBadgeStore } from '@/store/badge';
import { useRoute } from 'vue-router';
import { useDashboardStore } from '@/store/dashboard';
import { useDialog } from '@/composables/dialog';
import { useAuthUserStore } from '@/store/auth';
    const props = defineProps<{
        record: Post
    }>()
    const emit = defineEmits(['close'])
    const selected = ref(props.record ? props.record.status_flag : 0)
    const uploadedFiles = ref(props.record.result_files && props.record.result_files.length ? props.record.result_files : [])
    const resultMessage = ref(props.record.result ? props.record.result : '')
    const processing = ref(false)
    const api = useApi()
    const route = useRoute()
    const { getBatchDashboardData } = useDashboardStore()
    const { ping } = useDialog()
    const auth = useAuthUserStore()
    const challengeRelayEnabled = ref(false)
    const challengeRelayUser = ref<User | null>(null)
    const progressReportMode = computed(() => String(route.query.status ?? '') === '5')
    const canSelectProgressReport = computed(() => {
        const start = DateTime.fromISO(props.record.date_start)
        const end = DateTime.fromISO(props.record.date_end)
        const now = DateTime.now()

        return start.isValid && end.isValid && now >= start && now <= end && [0, 5].includes(props.record.status_flag)
    })
    const isProgressReportInput = computed(() => progressReportMode.value || (selected.value === 5 && canSelectProgressReport.value))
    const canPassChallengeRelay = computed(() => !progressReportMode.value && props.record.app_type == 2 && props.record.mini && selected.value === 1)
    const challengeRelayHistory = computed(() => (props.record.post_relays ?? []).filter(relay => relay.relay_type === 'challenge'))
    const progressCheckpoint = computed(() => {
        const queryCheckpoint = parseInt(String(route.query.progress_checkpoint ?? ''))
        if (!Number.isNaN(queryCheckpoint)) {
            return queryCheckpoint
        }

        const start = DateTime.fromISO(props.record.date_start)
        const end = DateTime.fromISO(props.record.date_end)
        const now = DateTime.now()

        if (!start.isValid || !end.isValid || end <= start) {
            return null
        }

        const elapsed = start.diff(now).as('seconds') * -1
        const total = start.diff(end).as('seconds') * -1
        const percent = Math.max(0, Math.min(100, Math.round((elapsed / Math.max(total, 1)) * 100)))

        return percent >= 75 ? 75 : percent >= 50 ? 50 : null
    })
    onMounted(() => {
        if (isProgressReportInput.value) {
            resultMessage.value = ''
        }

        if(route.query.status && !progressReportMode.value){
            const statusParam = parseInt(route.query.status as string)
            if(!isNaN(statusParam)){
                selected.value = statusParam
            }
        }
    })
    const statuses = computed(() => {      
        const endDate = props.record?.mini ? DateTime.fromISO(props.record.created_at).plus({ days: 7 }) :  DateTime.fromISO(props.record.created_at).plus({ days: 14 });     
        const items = [
            { id: 0, state : DateTime.now() <= endDate ? 'チャージ受付中' : '結果待ち' },
        ]

        if (canSelectProgressReport.value) {
            items.push({ id: 5, state : '進捗報告' })
        }

        items.push(
            { id: 1, state : '達成' },
            { id: 2, state : '未達成' },
            { id: 3, state : '中止' },
        )

        return items
    })
    const badge = useBadgeStore()
    const update = async() => {
        if (isProgressReportInput.value) {
            if (!resultMessage.value.trim()) {
                ping('進捗報告を入力してください。')
                return
            }
            await api.post('post_comment_add', {
                app_name: 'post',
                record_id: props.record.id,
                message: resultMessage.value,
                comment_type: 'progress_report',
                progress_checkpoint: progressCheckpoint.value,
                progress_files: uploadedFiles.value.map(ob => ob.id)
            }, {
                toast: '進捗報告を保存しました。',
                loadingRef: processing,
            })
            emit('close', props.record.id)
            getBatchDashboardData(['challenges'])
            badge.updatePostBadge('post')
            return
        }
        if (canPassChallengeRelay.value && challengeRelayEnabled.value && !challengeRelayUser.value?.id) {
            ping('バトンを渡すメンバーを選択してください。')
            return
        }
        const params = {
            id: props.record.id,
            status: selected.value,
            result: resultMessage.value,
            resultFiles: uploadedFiles.value.map(ob => ob.id),
            challenge_relay_to_user_id: canPassChallengeRelay.value && challengeRelayEnabled.value ? challengeRelayUser.value?.id : null,
        }
        await api.post('post_status_update', params, {
            toast: 'ステータスを更新しました。',
            loadingRef: processing,
        })
        emit('close', props.record.id)
        getBatchDashboardData(['challenges'])
        
    }
    const selectStatus = (id: number) => {
        selected.value = id
        resultMessage.value = id === 5 ? '' : props.record.result ?? ''
        if (id !== 1) {
            challengeRelayEnabled.value = false
            challengeRelayUser.value = null
        }
    }
    const relayDate = (value?: string | null) => {
        if (!value) return ''

        const parsed = DateTime.fromISO(value)
        return parsed.isValid ? parsed.toFormat('yyyy/MM/dd') : ''
    }
    const challengeRelayStatusText = (relay: PostRelay) => {
        const toUserName = relay.to_user?.name ?? 'メンバー'
        if (relay.status === 0) {
            const deadline = relayDate(relay.deadline_at)
            return deadline ? `${toUserName}さんへ送信中（締切: ${deadline}）` : `${toUserName}さんへ送信中`
        }

        if (relay.status === 1) {
            return `${relay.declined_by_user?.name ?? toUserName}さんがパスしました。`
        }

        if (relay.status === 2) {
            return `${relay.closed_by_user?.name ?? '誰か'}さんがリレーを終了しました。`
        }

        if (relay.status === 3) {
            return `${toUserName}さんが「${relay.accepted_post?.title ?? 'チャレンジ'}」を作成しました。`
        }

        return `${toUserName}さんへ送信しました。`
    }
</script>
<style>
    .status-button{
        font-size: 14px;
        border: solid 1px transparent;
        box-sizing: border-box;
        padding: 10px 15px;
        cursor: pointer;
        
    }
    .current-state{
        border: solid 1px var(--hoverBorder);
        background: var(--bg2);
    }
    .challenge-relay-box{
        border: solid 1px var(--hoverBorder);
        border-radius: 8px;
        padding: 12px;
        background: var(--bg2);
    }
    .challenge-relay-option{
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        cursor: pointer;
        margin-bottom: 12px;
    }
    .challenge-relay-note{
        font-size: 12px;
        color: gray;
        margin: 8px 0 12px;
    }
</style>
