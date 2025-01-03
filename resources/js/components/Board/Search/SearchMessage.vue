<template>
<div id="mRw1" class="md-window" style="z-index:54">
    <div class="searchMessageArea">
        <div style="display:flex;height: 40px;min-height: 40px;line-height: 40px;margin-top: 10px;">
            <div style="margin:0 20px;display: flex;font-size: 14px;overflow: hidden;" v-html="searchTitle"></div>
            <div @click="closeMessageSearch" style="margin:0 10px 0 auto;cursor:pointer;width:40px;height:40px;display:flex">
                <svg class="dot-menu" style="margin:auto" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>
        </div> 
        <div style="margin: 15px 0px;display:flex;padding: 0 20px;position: relative;" class="advancedSearchWindowContainer">
            <input 
                @focus="searchFocus"
                @keyup.enter="triggerSearch" 
                @keyup="setKeyWord"
                @keydown="setSelected"
                id="advancedSearchInput" 
                class="searchInputArea"
                style="padding:3px 10px;width:100%;color:inherit"
                spellcheck="false" 
                autocomplete="off" 
                autocorrect="off" 
                autocapitalize="off" 
                placeholder="キーワードを入力してください" 
                type="search"
            />
            <LoaderButton :loading="searchMiniLoader" content="検索" @triggered="getMessageSearch(keyword, -1)"/>
        </div>
        <div v-if="!targetedSearch && allResult.length" style="display:flex;margin-bottom:15px;font-size:14px;padding: 0 20px;gap:15px">
            <button :class="{editActive : resultGroupBy == 'all'}" @click="resultGroupBy = 'all'" class="s-button">メッセージ別 <span style="margin-left:5px" v-if="messageResult && messageResult.total "> ({{messageResult.total }})</span></button>
            <button :class="{editActive : resultGroupBy == 'board'}" @click="groupByBoard" class="s-button">ボード別 <span style="margin-left:5px" v-if="viewBoardList && viewBoardList.length"> ({{viewBoardList.length}})</span></button>
            
        </div>
        <div v-if="targetedSearch" style="padding: 0 20px;margin-bottom: 15px;">
            <div v-for="board in targetBoards" class="chat-search-select">
                <BoardIconPreLoad :item="boardItem(board.id)" imgStyle="min-width:25px;" :imgClass="'userMidIcon'"/>
                <BoardTitlePreLoad style="overflow:hidden" :item="boardItem(board.id)" titleStyle="line-height: 1.3;font-size: 12px;"/>
                <span style="white-space: nowrap;font-size:12px;">({{board.occurence}}件)</span>
                <div @click="resetTargetSearch" style="width:15px;height:15px;margin:auto 0;display: flex;cursor:pointer">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 32 32" class="dot-menu" style="margin: auto;">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
        </div>
  
        <div style="height: -webkit-fill-available;overflow: hidden auto;font-size:13px;padding: 0 20px;" v-if="allResult.length && !searchLoader && !searchMiniLoader && resultGroupBy == 'all'">
            <div @click="emit('jumpToMessage', message)"  style="padding:10px;margin-bottom:10px; border:solid thin var(--normalBorder);position:relative;cursor:pointer" :key="message.id" v-for="message in allResult">
                <div v-if="message.user" style="display:flex;align-items:center;margin-bottom:10px;">
                    <div style="display:flex;align-items:center">
                        <div v-if="message.user.deleted_at == null" class="column-01 cursor-pointer">                        
                            <UserIconPreLoad size="30" :user="message.user" imgClass="userNormalIcon"/>                       
                        </div>   
                        <div v-else class="column-01 cursor-pointer"> 
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30">
                                <circle cx="15" cy="15" r="15" fill="#ddd"/>
                            </svg>
                        </div>   
                        <span style="margin-left:5px;">{{ messageUserName(message) }}</span>
                    </div>
                    <div style="margin-left:auto">
                        <p class="dateText" style="font-size:12px;color:grey">{{ momentMessage(message.created_at) }}</p>
                    </div>
                </div>
                <div style="white-space: break-spaces;line-height: 1.4;" v-html="searchMessageBody(message.message_text)"></div>                
            </div>
        </div>
        <div style="height: -webkit-fill-available;overflow: hidden auto;font-size:13px;padding: 0 20px;" v-if="messageResult.data.length && !searchLoader && !searchMiniLoader && resultGroupBy == 'board'">
            <div :key="board.id" class="srgByBoard" v-for="board in viewBoardList">
                <div @click="searchInTarget(board)" style="display:flex;align-items:center;cursor:pointer;padding:10px">
                    
                    <BoardIconPreLoad imgClass="userNormalIcon" :item="boardItem(board.id)"/>
                    <div style="max-width:80%">
                        <BoardTitlePreLoad :item="boardItem(board.id)" titleStyle="margin-left:5px;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;"/>  
                    </div>
                    
                    <span style="margin-left:auto;white-space: nowrap;">({{board.occurence}}件)</span>
                </div>
                   
            </div>
        </div>
        <div style="margin: auto;font-size: 13px;" v-if="!allResult.length && !searchLoader && !searchMiniLoader && fetched">メッセージが見つかりませんでした</div>
        <div style="margin-top: auto;padding-top: 15px;">
            <PostSearchPager 
                v-if="messageResult.totalPage && resultGroupBy == 'all'" 
                :possiblePage="possiblePage" 
                :activePath="messageResult.currentPage" 
                @setActivePage="setActivePage"
                @setNavi="setNavi"
            />
        </div>
    </div>
</div>
</template>

<script setup>
import moment from 'moment'
import UserIconPreLoad from '../Mixed/UserIcon.vue'
import BoardTitlePreLoad from '../Mixed/BoardTitle.vue'
import BoardIconPreLoad from '../Mixed/BoardIcon.vue'
import PostSearchPager from '../../Post/PostSearchPager.vue'

import LoaderButton from '../../Global/LoaderButton.vue';
import { computed, inject, onMounted, watch, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { urlCheck } from '@/utils/tools';
    const auth = useAuthUserStore()

    const props = defineProps([
        'advancedSearchWord',
        'filteredAllBoard',
        'privateSearch'
    ])
    const emit = defineEmits(['closeMessageSearch', 'jumpToMessage'])

    const keyword = ref('')
    const resultGroupBy = ref('all')
    const searchResultGroupBy = ref([])
    const resultSortDateReverse = ref(false)
    const searchHistory = ref([])
    const isFocusing = ref(false)
    const selectedHistory = ref(-1)
    const searchLoader = ref(false)
    const searchMiniLoader = ref(false)
    const messageResult = ref({
        data: [],
        total: 0,
        currentPage: 1,
        totalPage: 0,
        board_list: []
    })
    const targetedSearch = ref(false)
    const targetBoards = ref([])
    const fetched = ref(false)
    const board = inject('openedBoard')
    const { confirm, notify, info } = inject('dialog');
  
    const searchTitle = computed(() => {       
        return '検索結果'                
    })
    const boardTitle = computed(() => {       
        if(!board.value) return ''     
        if(board.value.private_flag == 1 && board.value.board_to_users.length == 2){
            var coresspondId = board.value.board_to_users.filter(obj => obj.user_id !== auth.activeUser.id);
            if(coresspondId && coresspondId.length && coresspondId[0].user){
                return coresspondId[0].user.name;
            }else{
                return '非アクティブユーザー'
            }
        }else{
            return board.value.title;
        }           
    })    
    const viewBoardList = computed(() =>{
        return messageResult.value.board_list
        
    })
    const possiblePage = computed(() =>{
        return messageResult.value.totalPage
    })
    const allResult = computed(() => {                
        let list = [];
        const fetched_list = messageResult.value && messageResult.value.data ? messageResult.value.data : []
        fetched_list.forEach( (item) => {
            list.push(item)
        });
        return list
    })
    

        
    watch(() => resultSortDateReverse, (after) => {
        if(after){                    
            searchResultGroupBy.value.forEach((board, index) => {
                board.messages.sort((a,b) => (a.created_at > b.created_at) ? 1 : ((b.created_at > a.created_at) ? -1 : 0))
            });                    
        }else{                    
            searchResultGroupBy.value.forEach((board, index) => {
                board.messages.sort((a,b) => (a.created_at < b.created_at) ? 1 : ((b.created_at < a.created_at) ? -1 : 0))
            });
        }
    })
    
    onMounted(() => {
        if(!props.privateSearch){
            keyword.value = props.advancedSearchWord
        }
        // targetedSearch.value = props.privateSearch
        targetedSearch.value = false
        setTimeout(() =>{
            document.getElementById('advancedSearchInput').value = props.advancedSearchWord;
        },0)
        getMessageSearch(keyword.value)
        window.addEventListener('click', onClickSearch);
        window.addEventListener('touchstart', onClickSearch);
        
    })
 
    const messageUserName = (message) => {                
        return message.user.deleted_at == null ? message.user.name : '非アクティブユーザー'
    }
    const boardItem = (id) => {
        return props.filteredAllBoard && props.filteredAllBoard.filter(ob => ob.id == id).length ? props.filteredAllBoard.filter(ob => ob.id == id)[0] : null
    }
    const getMessageSearch = async(key, val, val2) => {
        if(searchLoader.value || searchMiniLoader.value || !key) return
        
            searchLoader.value = true
        
            searchMiniLoader.value = true
            const record_id = targetBoards.value.length ? targetBoards.value[0].id : null
        if(val == -1){
            const reset = {
                data: [],
                total: 0,
                currentPage: 1,
                totalPage: 0
            }
            messageResult.value = reset
        }
        try{
            const data = await axios.post('/message_search',{
                keyword: key,
                private_flag: targetedSearch.value,
                record_id: record_id,
                index: messageResult.value.currentPage
            }).then(response => response.data)
            if(data.data){
                messageResult.value = data  
            }

            searchLoader.value = false;
            searchMiniLoader.value = false;  
            selectedHistory.value = -1;
            if(val2){
                groupByBoard()
            } 
            fetched.value = true
        } catch(e){
            console.log('aaaaaaaaa')
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            searchLoader.value = false;
            searchMiniLoader.value = false;  
        }

    }
    const setActivePage = (page) => {
        messageResult.value.currentPage = page
        getMessageSearch(keyword.value)
    }
    const setNavi = (val) => {
        messageResult.value.currentPage = messageResult.value.currentPage + val
        getMessageSearch(keyword.value)
    }
    const searchFocus = () => {
        isFocusing.value = true
    }
    const triggerSearch = () =>{
        event.preventDefault()
        if(isFocusing.value && selectedHistory.value !== -1){
            let input = document.getElementById('advancedSearchInput')
            input.value = searchHistory.value[selectedHistory.value].content
            keyword.value = searchHistory.value[selectedHistory.value].content
            input.blur()
            getMessageSearch(keyword.value, -1)
            isFocusing.value = false
        }else{
            
            let input = document.getElementById('advancedSearchInput')
            keyword.value = input.value
            input.blur()
            getMessageSearch(keyword.value, -1)
            isFocusing.value = false

        }
    }
    const onClickSearch = () => {
        const el = document.getElementById('historyWrapWindow')
        const input = document.getElementById('advancedSearchInput')
        if(el && input && !el.contains(event.target) && !input.contains(event.target) && isFocusing.value){
            isFocusing.value = false
        }

    }
    const setSelected = () => {
        if(event.which === 27){
            isFocusing.value = false;
            selectedHistory.value = -1;
            document.getElementById('advancedSearchInput').value = '';
            document.getElementById('advancedSearchInput').blur();
            keyword.value = '',
            searchHistory.value = []
            return
        } 
        if(event.which === 38 || event.which === 40){
            event.preventDefault()
            
            if(isFocusing.value && searchHistory.value.length){
                if(event.which === 38){
                    selectedHistory.value = selectedHistory.value <= 0 ? searchHistory.value.length - 1 : selectedHistory.value - 1                     
                }
                if(event.which === 40){//dooshoo                        
                    selectedHistory.value = selectedHistory.value == searchHistory.value.length - 1 ? 0 : selectedHistory.value + 1                                                     
                } 
            }
            
        }
    }
    const setKeyWord = () => {
        
        if(event.which === 38 || event.which === 40 || event.which === 13){
            
            event.preventDefault()
            
            return
            
        }
        else{
            keyword.value = event.currentTarget.value
        }
        
    }
    const setKeyWordFromHistory = (val) => {
        const input = document.getElementById('advancedSearchInput')
        input.value = val
        input.blur()
        keyword.value = val
        isFocusing.value = false
        getMessageSearch(keyword.value, -1)
    }
    const resetTargetSearch = () => {
        targetBoards.value = [];
        targetedSearch.value = false
        resultGroupBy.value = 'all'
        getMessageSearch(keyword.value, -1, true)

    }
    const searchInTarget = (board) => {
        targetBoards.value = [];
        targetBoards.value.push(board)
        targetedSearch.value = true
        getMessageSearch(keyword.value, -1)
        resultGroupBy.value = 'all'
    }
    const momentMessage = (date) => {
        moment.locale('ja');  
        
        return moment(date).isSame(moment(), 'day') ? 
        moment(date).format('HH:mm') : 
        moment(date).isSame(moment(), 'year') ? 
        moment(date).format('M / D (dd) HH:mm') : 
        moment(date).format('YYYY / M / D (dd) HH:mm')                       
    }
    const closeMessageSearch = () => {
        window.removeEventListener('touchstart', onClickSearch);
        window.removeEventListener('click', onClickSearch);
        emit('closeMessageSearch')
    }
    const groupByBoard = () => {
        let boards = [];
        for(const id in messageResult.value.board_list){
            const item = props.filteredAllBoard.filter(ob => ob.id == id)
            if(item.length){
                boards.push(item[0])
            }
        }
        searchResultGroupBy.value = boards
        resultGroupBy.value = 'board'
    }
    const searchMessageBody = (text) => {                
        const a = text.replace(keyword.value, "<span style='background: yellow;color:#000'>" + keyword.value + "</span>");           
        let r = urlCheck(a);                
        return r
    }
    

</script>
