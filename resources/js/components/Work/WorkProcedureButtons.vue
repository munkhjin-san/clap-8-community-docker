<template>
    <div class="work-modal" @mousedown="emit('closeModal')">
        <div class="work-modal-inner overstyle" @mousedown.stop>
            <div class="recordFormTitle" style="z-index: 26;gap:30px;">
                <p style="font-size: 18px;">{{ formatedDay }}</p>
                <div @click="emit('closeModal')" class="cursor-pointer" style="margin: auto 0 auto auto;">
                    <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
                <div style="display: flex; gap: 30px; flex-wrap: wrap;margin-top:10px; justify-content: center;">
                    <LoaderButton 
                        style="margin:0"
                        v-if="item.ability.overtime_approve"
                        content="残業承認"
                        @triggered="respondOvertime(item?.shift?.overtime_request, 2, '残業承認')"
                    />
                    <LoaderButton 
                        style="margin:0"
                        v-if="item.ability.overtime_approve"
                        content="残業差戻"
                        @triggered="respondOvertime(item?.shift?.overtime_request, 0, '残業差戻')"
                    />
                    <LoaderButton 
                        style="margin:0"
                        v-if="item.ability.overtime_cancel"
                        content="残業承認取消"
                        @triggered="respondOvertime(item?.shift?.overtime_request, 1, '残業承認取消')"
                    />
                    <LoaderButton 
                        style="margin:0"
                        content="日報承認"
                        v-if="item.ability.daily_report_approve"
                        @triggered="emit('dailyButtons', 0, props.item)"
                    /> 
                    <LoaderButton 
                        style="margin:0"
                        content="日報差戻"
                        v-if="item.ability.daily_report_approve"
                        @triggered="emit('dailyButtons', 1, props.item)"
                    />   
                    <LoaderButton 
                        style="margin:0"
                        content="日報承認取消"
                        v-if="item.ability.daily_report_cancel"
                        @triggered="emit('dailyButtons', 2, props.item)"
                    /> 
                    <LoaderButton 
                        style="margin:0"
                        v-if="item.ability.overtime_request" 
                        content="残業申請"
                        @triggered="emit('dailyButtons', 3, props.item)" 
                    />
                    <LoaderButton
                        style="margin:0" 
                        v-if="item.ability.daily_report_modify"
                        content="日報編集"
                        @triggered="edit(item), emit('closeModal')"
                    />
                    <LoaderButton
                        style="margin:0" 
                        v-if="item.ability.daily_report_modify"
                        content="日報削除"
                        @triggered="stampDelete(item), emit('closeModal')"
                    />
                    <LoaderButton
                        style="margin:0" 
                        v-if="item.ability.daily_report_create"
                        content="日報作成"
                        @triggered="edit(item), emit('closeModal')"
                    />
                    
                               
                    
                    
                </div>
            
        </div>
    </div>
</template>
<script setup>
    import moment from 'moment';
    import { useAuthUserStore } from '../../store/auth';
    import LoaderButton from '../Global/LoaderButton.vue';
    import { inject, ref, computed } from 'vue';
    import OverTimeRequest from './OverTimeRequest.vue';
    import { useCheckApproval } from '../../store/checkApproval';
    const overtimeRequestData = ref(null)
    const props = defineProps(['currentDay', 'statuses', 'item'])
    const emit = defineEmits(['reload', 'closeModal'])
    const { confirm, notify, info } = inject('dialog')
    const auth = useAuthUserStore()
    const { edit, stampDelete } = inject('stamps')
    const checkApproval = useCheckApproval()
    const respondOvertime = async(data, status, action) => {
        if(status == 0){
            const answer = await confirm(`${data?.overtime_day}申請を差し戻しますか。差し戻した場合、申請社員に連絡してください。`)
            if(!answer) return
        }
        const params = {
            id: data.id,      
            approved_by: auth.activeUser.id,
            status: status
        }

        try{
            await axios.patch('/request_overtime', params).then(res => res.data)
            emit('reload')
            info(`${action}しました。`)
            emit('closeModal')
            checkApproval.setCheckApproval(true)
        } catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } 
    }
    const formatedDay = computed(() => {
        const date = new Date(props.item?.day_full)
        return `${date.getMonth() + 1}月${date.getDate()}日の手続き`
    })
</script>
<style scoped>
    .overstyle{
        max-width: 30%;
        width: fit-content; 
        height:fit-content;
    }
    @media screen and (max-width: 959px){
        .overstyle {
            max-width: 60%;
        }
    }
</style>