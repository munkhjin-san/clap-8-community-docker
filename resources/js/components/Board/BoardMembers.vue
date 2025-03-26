
<template>
    <div @mousedown="closeModal" class="overlay" style="z-index: 31;font-size:14px">
        <div id="createModal" class="chatCreate" ref="createModal" @mousedown.stop>
            <div>                
                <div class="recordFormTitle">
                    <p style="font-size: 14px;" v-html="headTitle"></p>
                    <div @click="emit('close')" class="m-close-button" style="position: unset;margin-left: auto;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>                
                </div>
                <div>                   
                    <PostSearchBar 
                        className="newChatMemberSearch" 
                        :searching="searching" 
                        @search-start="(word) => {keyword = word}"
                    />
                </div>
            </div>
            <div style="margin-top: 15px;height: -webkit-fill-available;overflow:hidden auto;">                
                <div style="height: -webkit-fill-available;user-select: none;">                    
                    <div v-if="filteredAdmins.length">
                        <div style="font-weight: 600;margin-bottom: 15px;">管理者 ({{ filteredAdmins.length }})</div>
                        <div class="suggested-list">
                            <div :key="admin.id" v-for="admin in filteredAdmins">
                                <div class="suggested-wrap">
                                    <UserPanel :user="admin.user" imgClass="userNormalIcon" size="30"/>
                                    <router-link :to="`/user/${admin.user.id}`" class="suggested-user-name user-link">{{ admin.user.name }}</router-link>
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
                                        <router-link :to="`/user/${member.user.id}`" class="suggested-user-name user-link">{{ member.user.name }}</router-link>
                                        <div v-if="checkAdminAccess" style="font-size: 11px;color: gray;margin: 5px 0 0 5px;">閲覧制限日付:{{ viewFrom(member) }}</div>
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
                                                <p v-if="invalidDate" class="i-error">{{ `日付は${setMin(member)}以上${setMax()}以下である必要があります。` }}</p>
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
        </div>

        
    </div>
</template>

<script setup>
import PostSearchBar from '../Post/PostSearchBar.vue'
import { computed, inject, ref } from 'vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import ItemMenu from '@/components/Global/ItemMenu.vue';
import moment from 'moment';
import ShortInput from '../Form/ShortInput.vue';
import CommandButton from '../Global/CommandButton.vue';
import axios from 'axios';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps(['board'])
    const emit = defineEmits(['close', 'afterRequestHandled',  ])
    const searching = ref(0)
    const keyword = ref('')
    const lock = ref(false)
    const { invite, reload } = inject('boardItem')
    const { notify, info, confirm } = inject('dialog')
    const editingMember = ref(null)
    const invalidDate = ref(false)
    const headTitle = computed (() => {
        return `<strong>${props.board.title}</strong>ボードメンバー`
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
        const confirmed = await confirm(`<strong>${user.name}</strong> をボードメンバーから削除してもよろしいですか?`)
        if(!confirmed.value || lock.value) return 
        lock.value = true
        try{
            await axios.post('/remove_group_member', { record_id : props.board.id, user_id: user.id})
            info('メンバーから削除しました。')
            reload(props.board.id)
            lock.value = false
        }catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            lock.value = false
        } 
    }
    const setAdmin = async (user, flag) => {
        const confirmed = await confirm(flag == 1 ? `<strong>${user.name}</strong> を管理者に追加してよろしいですか?` : `<strong>${user.name}</strong> を管理者から削除してもよろしいですか?`)
        if(lock.value || !confirmed.value) return
        lock.value = true
        try{
            await axios.post('/set_admin_role',{ record_id : props.board.id, user_id: user.id, flag: flag }) 
            info(flag == 1 ? '管理者に追加しました。' : '管理者から外しました。')           
            reload(props.board.id);
            lock.value = false
        }catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            lock.value = false
        }         
    }       
    const closeModal = (event) => {
        if (!createModal.value.contains(event.target)) {
            emit('close')
        }
    }   
    const viewFrom = (member) => {
        return moment(member.view_from ?? member.created_at).format('YYYY-MM-DD')
    }
    const startEditViewFrom = (member) => {
        editingMember.value = member
    }
    const setMin = (member) => {
        const min =  moment(member.created_at).subtract(1, 'year').format('YYYY-MM-DD')
        return min
    }
    const setMax = () => {
        return moment().format('YYYY-MM-DD')
    }
    const updateViewFrom = async() => {
        if (invalidDate.value) return
        try {
            await axios.put('/update_view_from', {id: editingMember.value.id, view_from: editingMember.value.view_from})
            editingMember.value = null
            info('保存しました。')
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } 
    }
    const validateDate = (member) => {
        const selectedDate = editingMember.value.view_from
        const minDate = setMin(member)
        const maxDate = setMax()
        if (moment(selectedDate).isBetween(minDate, maxDate, undefined, [])) {
            invalidDate.value = false
        } else {
            invalidDate.value = true
        }
    }
</script>
