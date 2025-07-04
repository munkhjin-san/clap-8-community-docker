
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
                <div style="height: -webkit-fill-available;user-select: none;">                    
                    <div v-if="filteredAdmins.length">
                        <div style="font-weight: 600;margin-bottom: 15px;">管理者 ({{ filteredAdmins.length }})</div>
                        <div class="suggested-list">
                            <div :key="admin.id" v-for="admin in filteredAdmins">
                                <div class="suggested-wrap">
                                    <UserPanel :user="admin.user" imgClass="userNormalIcon" size="30"/>
                                    <router-link :to="`/user/${admin.user.id}`" class="suggested-user-name user-link text-[14px]">{{ admin.user.name }}</router-link>
                                    <ItemMenu 
                                        v-if="checkAdminAccess"
                                        style="margin-left: auto;"
                                        :items="[{title: '管理者から外す', action: () => setAdmin(admin.user, 0)}]"
                                        :fit="'createModal'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>                   
                    <div v-if="filteredMembers.length">
                        <div style="font-weight: 600;margin: 15px 0;">メンバー ({{ filteredMembers.length }})</div>
                        <div class="suggested-list">
                            <div :key="member.id" v-for="member in filteredMembers">
                                <div class="suggested-wrap">                                    
                                    <UserPanel :user="member.user" imgClass="userNormalIcon" size="30"/>
                                    <div>
                                        <router-link :to="`/user/${member.user.id}`" class="suggested-user-name user-link text-[14px]">{{ member.user.name }}</router-link>
                                        <div v-if="checkAdminAccess" style="font-size: 11px;color: gray;margin: 5px 0 0 5px;">閲覧制限日付:{{ DateTime.fromSQL(member.view_from ).toISODate() ?? DateTime.fromISO(member.created_at).toISODate() }}</div>
                                        <div v-if="editingMember && editingMember.id == member.id" style="margin-top: 10px;">
                                            <div style="position: relative;">
                                                <ShortInput 
                                                    @change="validateDate(member)" 
                                                    custom-class="date"
                                                    type="date"
                                                    :min="setMin(member)" 
                                                    :max="setMax()" 
                                                    v-model="editingMember.view_from"
                                                />
                                                <p v-if="invalidDate" class="i-error">{{ `${setMin(member)} - ${setMax()}の間のみ設定可能です。` }}</p>
                                            </div>
                                            
                                            <div style="display: flex;gap: 10px;" :style="{marginTop: invalidDate ? '20px' : '10px'}">
                                                <CommandButton :buttons="[
                                                    {title: '保存', action: () => {updateViewFrom()}},
                                                    {title: 'キャンセル', action: () => editingMember = null}
                                                ]"/>
                                            </div>
                                            
                                        </div>
                                    </div>

                                    <ItemMenu 
                                        v-if="checkAdminAccess"
                                        style="margin-left: auto;"
                                        :items="[
                                            {title: '管理者として追加', action: () => setAdmin(member.user, 1)},
                                            {title: 'メンバーから外す', action: () => removeMember(member.user)},
                                            {title: '閲覧制限設定', action: () => startEditViewFrom(member)}
                                        ]"
                                        :fit="'createModal'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>               
                
            </div>
            <div style="text-align: center;margin-top: auto;" v-if="checkAdminAccess">
                <button @click="invite(board)" style="padding: 10px 15px;" :class="['commentEditButton']">メンバー追加</button>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import PostSearchBar from '../Post/PostSearchBar.vue'
import { computed, inject, ref } from 'vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import { useAuthUserStore } from '@/store/auth'
import ItemMenu from '@/components/Global/ItemMenu.vue';
import ShortInput from '../Form/ShortInput.vue';
import CommandButton from '../Global/CommandButton.vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import Modal from '../Global/Modal.vue';
import { BoardMethodsKey, BoardMethods } from '@/interface/keys';
import { BoardMember } from '@/interface/globalInterface';
    const auth = useAuthUserStore()
    const props = defineProps(['board'])
    const emit = defineEmits(['close', 'afterRequestHandled',  ])
    const searching = ref(0)
    const keyword = ref('')
    const lock = ref(false)
    const { invite, reload } = inject(BoardMethodsKey) as BoardMethods
    const editingMember = ref<BoardMember | null>(null)
    const invalidDate = ref(false)
    const api = useApi()
    const { ask, toast } = useDialog()
    const headTitle = computed (() => {
        return `<strong>${props.board.title}</strong>チャットメンバー`
    })
    const admins = computed (() => {
        return props.board && props.board.board_to_users && props.board.board_to_users.length ? 
        props.board.board_to_users.filter(ob => ob.admin_flag == 1) : []
    })
    const filteredAdmins = computed (() => {
        const searchText = keyword.value.toLowerCase();
        return admins.value.filter(adm => adm.user.name.toLowerCase().includes(searchText));
    })
    const members = computed (() => {
        return props.board && props.board.board_to_users && props.board.board_to_users.length ? 
        props.board.board_to_users.filter(ob => ob.admin_flag == 0) : []
    })
    const filteredMembers = computed (() => {
        const searchText = keyword.value.toLowerCase();
        return members.value.filter(mem => mem.user.name.toLowerCase().includes(searchText));
    })
    const checkAdminAccess  = computed (() => {
        return props.board && props.board.board_to_users && props.board.board_to_users.length ? 
        props.board.board_to_users.filter(ob => ob.admin_flag == 1 && ob.user_id == auth.activeUser.id).length : false
    })
    const createModal = ref(null)

    const removeMember = async(user) => {
        const confirmed = await ask(`<strong>${user.name}</strong> をチャットメンバーから削除してもよろしいですか?`)
        if(!confirmed.value || lock.value) return 
        lock.value = true

        await api.post('/remove_group_member', { record_id : props.board.id, user_id: user.id})
        toast('メンバーから削除しました。')
        reload()
        lock.value = false

    }
    const setAdmin = async (user, flag) => {
        const confirmed = await ask(flag == 1 ? `<strong>${user.name}</strong> を管理者に追加してよろしいですか?` : `<strong>${user.name}</strong> を管理者から削除してもよろしいですか?`)
        if(lock.value || !confirmed.value) return
        lock.value = true
  
        await api.post('/set_admin_role',{ record_id : props.board.id, user_id: user.id, flag: flag }) 
        toast(flag == 1 ? '管理者に追加しました。' : '管理者から外しました。')           
        reload();
        lock.value = false
        
    }    
    const startEditViewFrom = (member) => {
        editingMember.value = member
    }
    const setMin = (member) => {
        return DateTime.fromISO(member.created_at).toISODate()?.toString()
    }
    const setMax = () => {
        return DateTime.now().toISODate()
    }
    const updateViewFrom = async() => {
        if (invalidDate.value) return

        await api.put('/update_view_from', {id: editingMember.value?.id, view_from: editingMember.value?.view_from}, {
            toast: '保存しました。'
        })
        editingMember.value = null

    }
    const validateDate = (member) => {
        if(!editingMember.value?.view_from) {
            invalidDate.value = true
            return
        }
        const selectedDate = editingMember.value?.view_from
        const minDate = setMin(member)
        const maxDate = setMax()
        if (minDate && DateTime.fromISO(selectedDate) >= DateTime.fromISO(minDate) && DateTime.fromISO(selectedDate) <= DateTime.fromISO(maxDate)) {
            invalidDate.value = false
        } else {
            invalidDate.value = true
        }
    }
</script>
