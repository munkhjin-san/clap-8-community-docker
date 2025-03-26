
<template>
    <div @mousedown="closeModal" class="overlay" style="z-index: 31;font-size:14px">
        
        <div id="inviteModal" class="chatCreate" ref="inviteModal" @mousedown.stop>   
            <div>
                <div class="recordFormTitle">
                    <p style="font-size: 14px;" v-html="headTitle"></p>
                    <div @click="emit('close')" class="m-close-button" style="position: unset;margin-left: auto;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>                
                </div>
            </div>
            <div style="margin-bottom: 15px">
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    customPlaceHolder="ユーザーの検索" 
                    @search-start="(word) => {keyword = word}"
                />
            </div>            
            <div style="height: -webkit-fill-available;user-select: none;overflow: hidden auto;">
                    <div v-if="filteredMembers.length">
                        <div class="suggested-list">
                            <div :key="user.id" v-for="user in filteredMembers">
                                <div @click="selectedMember = user.id" class="suggested-wrap" :style="{backgroundColor: selectedMember === user.id ? 'var(--bg2)' : ''}">
                                    <UserPanel :user="user" imgClass="userNormalIcon" size="30"/>
                                    <div class="suggested-user-name">
                                        <div>{{ user.name }}</div>
                                        <div v-if="selectedMember === user.id" style="margin-top: 10px;">
                                            <div style="font-size: 11px;color: gray;margin: 5px 0;">閲覧制限設定</div>
                                            <ShortInput 
                                                @change="validateDate(user)" 
                                                custom-class="date"
                                                type="date" :min="setMin()" 
                                                :max="setMax()" 
                                                v-model="view_from"
                                            />
                                            <p v-if="invalidDate" class="i-error">{{ `日付は${setMin()}以上${setMax()}以下である必要があります。` }}</p>
                                            <div style="display: flex;gap: 10px;" :style="{marginTop: invalidDate ? '20px' : '10px'}">
                                                <CommandButton :buttons="[
                                                    {title: '追加', action: () => {selectToUser(user)}},
                                                ]"/>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                <div style="margin-top: 10px;">
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</template>

<script setup>
import PostSearchBar from '../Post/PostSearchBar.vue'
import { computed, inject, nextTick, onMounted, ref } from 'vue'
import CommandButton from '../Global/CommandButton.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import moment from 'moment';
import ShortInput from '../Form/ShortInput.vue';
    const props = defineProps(['item'])
    const emit = defineEmits(['close'])    
    const possibleMemberList = ref([])
    const keyword = ref('')
    const searching = ref(0)
    const lock = ref(false)
    const { reload } = inject('boardItem') 
    const { confirm, notify, info } = inject('dialog')
    const inviteModal = ref(null)
    const view_from = ref(moment().format('YYYY-MM-DD'))
    const invalidDate = ref(false)
    const selectedMember = ref(null)
    onMounted(() => {
        getMembers();
    })

    const filteredMembers = computed(() => {
        const searchText = keyword.value.toLowerCase();
        return addAbleMembers.value.filter(user => user.name.toLowerCase().includes(searchText));
    })
    const addAbleMembers = computed(() => {
        return possibleMemberList.value
    })
    const headTitle = computed(() => {
        return `<strong>"${props.item.title}"</strong>ボードにメンバーを追加する`
    })
    const selectToUser = async(user) => {
        if(invalidDate.value) return
        const confirmed = await confirm(`<strong>${user.name}</strong>さんをボードメンバーに追加しますか。<br><strong>${moment(view_from.value).format('YYYY/M/D')} 0時</strong>から閲覧可能になります。`)
        if(lock.value || !confirmed.value) return
        lock.value = true
        const params = { record_id : props.item.id, user_id: user.id, view_from: view_from.value }
        try{
            await axios.post('/group_add_member', params)
            reload(props.item.id)
            getMembers()
            info('メンバー追加しました。')
            lock.value = false
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            lock.value = false
        }
    }
    const searchStart = (key) => {
        keyword.value = key
        searching.value = 2
    }
    const getMembers = async() => {
        try{
            possibleMemberList.value = await axios.post('/addable_board_members', {record_id: props.item.id}).then(res => res.data)   
            nextTick(() => {                                
                searching.value = 0
            })
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            lock.value = false
        }
    }
    const closeModal = (event) => {
        if (!inviteModal.value.contains(event.target)) {
            emit('close')
        }
    }      
    const setMin = () => {
        const min =  moment().subtract(1, 'year').format('YYYY-MM-DD')
        return min
    }
    const setMax = () => {
        return moment().format('YYYY-MM-DD')
    }
    const validateDate = (member) => {
        const selectedDate = view_from.value
        const minDate = setMin(member)
        const maxDate = setMax()
        if (moment(selectedDate).isBetween(minDate, maxDate, undefined, [])) {
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