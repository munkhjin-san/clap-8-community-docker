
<template>
    <Modal @close="emit('close')">
        <template #title>
            <p style="font-size: 14px;" v-html="headTitle"></p>
        </template>
        <template #content>
            <div class="sticky top-[80px] z-10 bg-[var(--background-color)] pb-[15px]">               
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    :searching="searching" 
                    @search-start="(word) => {keyword = word}"
                />
            </div>      
            <div class="mt-[15px]">     
                    <div v-if="filteredMembers.length">
                        <div class="suggested-list">
                            <div :key="user.id" v-for="user in filteredMembers">
                                <div @click="selectedMember = user.id" class="suggested-wrap" :style="{backgroundColor: selectedMember === user.id ? 'var(--bg2)' : ''}">
                                    <UserPanel :user="user" imgClass="userNormalIcon" size="30"/>
                                    <div class="suggested-user-name">
                                        <div class="text-[14px]">{{ user.name }}</div>
                                        <div v-if="selectedMember === user.id" style="margin-top: 10px;">
                                            <div style="font-size: 11px;color: gray;margin: 5px 0;">閲覧制限設定</div>
                                            <ShortInput 
                                                @change="validateDate()" 
                                                custom-class="date"
                                                type="date" 
                                                :min="setMin" 
                                                :max="setMax" 
                                                v-model="view_from"
                                            />
                                            <p v-if="invalidDate" class="i-error">{{ `${setMin}-${setMax}の間のみ設定可能です。` }}</p>
                                            <div style="display: flex;gap: 10px;" :style="{marginTop: invalidDate ? '20px' : '10px'}">
                                                <CommandButton :buttons="[
                                                    {title: '追加', action: () => {selectToUser(user)}},
                                                ]"/>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Modal>


</template>

<script setup lang="ts">
import PostSearchBar from '../Post/PostSearchBar.vue'
import { computed, inject, nextTick, onMounted, ref } from 'vue'
import CommandButton from '../Global/CommandButton.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import ShortInput from '../Form/ShortInput.vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { BoardMethodsKey, BoardMethods } from '@/interface/keys';
import { User } from '@/interface/globalInterface';
import Modal from '../Global/Modal.vue';
    const props = defineProps(['item'])
    const emit = defineEmits(['close'])    
    const possibleMemberList = ref<User[]>([])
    const keyword = ref('')
    const searching = ref(0)
    const lock = ref(false)
    const { reload } = inject(BoardMethodsKey) as BoardMethods
    const inviteModal = ref(null)
    const view_from = ref(DateTime.now().toISODate())
    const invalidDate = ref(false)
    const selectedMember = ref<number | null>(null)
    const api = useApi()
    onMounted(() => {
        getMembers();
    })

    const filteredMembers = computed(() => {
        const searchText = keyword.value.toLowerCase();
        return addAbleMembers.value.filter(user => user?.name?.toLowerCase().includes(searchText));
    })
    const addAbleMembers = computed(() => {
        return possibleMemberList.value
    })
    const headTitle = computed(() => {
        return `<strong>"${props.item.title}"</strong>チャットにメンバーを追加する`
    })
    const selectToUser = async(user) => {
        if(invalidDate.value) return
        if(lock.value) return
        lock.value = true
        const params = { record_id : props.item.id, user_id: user.id, view_from: view_from.value }
        await api.post('/group_add_member', params, {
            ask: `<strong>${user.name}</strong>さんをチャットメンバーに追加しますか。<br><strong>${DateTime.fromSQL(view_from.value).toLocaleString(DateTime.DATE_SHORT)} 0時</strong>から閲覧可能になります。`,
            toast: 'メンバーを追加しました。'
        })
        lock.value = false
        reload()
        getMembers()
    }
    const getMembers = async() => {   
        possibleMemberList.value = await api.post('/addable_board_members', {record_id: props.item.id})  
        nextTick(() => {                                
            searching.value = 0
        })
    }
    const setMin = computed(() => {
        return DateTime.now().minus({year: 1}).toISODate()
    })
    const setMax = computed(() => {
        return DateTime.now().toISODate()
    })
    const validateDate = () => {
        const selectedDate = view_from.value
        const minDate = setMin.value
        const maxDate = setMax.value
        if (DateTime.fromISO(selectedDate) >= DateTime.fromISO(minDate) && DateTime.fromISO(selectedDate) <= DateTime.fromISO(maxDate)) {
            invalidDate.value = false
        } else {
            invalidDate.value = true
        }
    }
    
</script>
<style lang="scss">
    .exp-text{
        font-size:14px;
        color: var(--primary-color);

    }
    .largeQr{
        background: var(--background-color);
        position:absolute;
        width: 100%;
        top:50px;
        height: calc(100% - 50px);
        left: 0;
        z-index: 4;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .warn-window{
        background: var(--bg2);
        padding: 10px;
        line-height: 1.5;
    }
    .invite-by-public-method{
        display: flex;
        width: -webkit-fill-available;
    }

    @media screen and (max-width: 959px) {
        .invite-by-public-method{
            flex-direction: column;
            gap: 10px;
        }
    }
</style>