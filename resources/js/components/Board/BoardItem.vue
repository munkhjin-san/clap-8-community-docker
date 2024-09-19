<template>
    <div :id="'board_item_' + item.id" class="left-panel-wrap">
        <div class="left-panel-items" @click="open(item)" :class="{leftPanelSelected : isOpened}">
            <div :class="['board-info-bar', {openedInfoBar : isOpened}]">
                <div v-if="badge.task[item.id]">
                    <svg v-if="badge.task[item.id]" class="dot-menu" version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" viewBox="0 0 37 32">
                        <path d="M36.297 0.493c-0.529-0.407-1.289-0.312-1.742 0.177l-2.463 2.656-2.479 2.698c-1.644 1.805-3.295 3.607-4.927 5.425-1.633 1.815-3.274 3.625-4.9 5.446-0.906 1.016-1.818 2.030-2.726 3.046-0.293 0.329-0.814 0.303-1.073-0.054-0.061-0.083-0.124-0.169-0.187-0.252l-0.538-0.737-1.64-2.19c-0.726-0.977-1.471-1.94-2.22-2.9l-1.134-1.428c-0.384-0.472-0.767-0.947-1.16-1.413-0.435-0.515-1.21-0.637-1.791-0.225-0.567 0.401-0.704 1.19-0.355 1.792 0.296 0.513 0.607 1.020 0.914 1.528l0.961 1.551c0.652 1.030 1.306 2.056 1.978 3.069l1.509 2.284 0.509 0.755c0.68 1.007 1.366 2.011 2.070 3.003l0.082 0.115c0.095 0.133 0.207 0.252 0.339 0.36 0.794 0.645 1.97 0.495 2.63-0.283 1.569-1.848 3.105-3.724 4.657-5.585 1.564-1.876 3.113-3.766 4.667-5.649 1.558-1.882 3.096-3.779 4.641-5.67l2.304-2.852 2.291-2.858c0.436-0.547 0.358-1.364-0.22-1.809z"></path>
                        <path d="M30.798 13.688c-0.736 0.045-1.297 0.682-1.307 1.417l-0.182 13.496c-0.004 0.298-0.247 0.532-0.545 0.527-1.719-0.029-3.439-0.041-5.158-0.055l-7.281-0.017-7.281-0.001-5.073 0.015c-0.257 0-0.465-0.21-0.462-0.466 0.019-1.7 0.019-3.398 0.019-5.098l-0.026-7.281-0.026-7.279-0.033-5.239c-0.001-0.21 0.168-0.38 0.378-0.381 1.558-0.010 3.114-0.023 4.671-0.031l20.184-0.204c0.809-0.008 1.46-0.691 1.409-1.517-0.046-0.754-0.701-1.326-1.457-1.334l-20.136-0.204c-2.244-0.012-4.486-0.037-6.729-0.038-0.915 0-1.66 0.739-1.667 1.655v0.010l-0.049 7.281-0.024 7.279-0.026 7.281c0 2.427 0 4.854 0.055 7.279l0.001 0.037c0.022 0.925 0.777 1.67 1.709 1.673l7.281 0.022 7.281-0.003 7.281-0.018c2.427-0.018 4.854-0.029 7.281-0.106l0.074-0.003c0.86-0.026 1.542-0.736 1.531-1.603l-0.212-15.725c-0.015-0.787-0.68-1.421-1.482-1.372z"></path>
                    </svg>                                                
                </div>                
                <div v-if="hasFailedMessage">
                    <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                        <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                    </svg> 
                </div>
                <div v-if="selfMember?.pin_flag == 1">
                    <svg version="1.1" class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623zM6.029 13.958c0.341-0.224 0.749-0.388 1.182-0.474 0.43-0.088 0.887-0.099 1.316-0.032 0.431 0.065 0.834 0.212 1.162 0.42l0.018 0.011c0.428 0.27 0.907 0.285 1.415-0.086l4.759-3.878 4.764-3.898c0.344-0.281 0.505-0.751 0.375-1.206-0.141-0.493-0.155-1.027-0.032-1.541 0.027-0.117 0.211-0.237 0.335-0.111l1.351 1.329 5.164 5.123 1.339 1.368c0.135 0.134-0.014 0.343-0.149 0.374-0.516 0.127-1.037 0.111-1.501-0.043-0.429-0.14-0.923-0.014-1.226 0.356l-0.013 0.018-3.894 4.744-3.88 4.759c-0.393 0.519-0.37 0.961-0.085 1.411l0.010 0.018c0.209 0.329 0.357 0.732 0.42 1.163 0.066 0.43 0.055 0.885-0.034 1.317-0.086 0.434-0.25 0.839-0.474 1.182 0 0.001-0.001 0.002-0.001 0.003-0.071 0.109-0.228 0.122-0.32 0.029l-6.010-6.024-6.022-6.010c-0.093-0.092-0.081-0.248 0.028-0.32 0.001 0 0.001 0 0.002-0.001z"></path>
                    </svg>
                </div> 
            </div>
            <div class="board-c-bar">  
                <ItemMenu :items="boardMenuItems" fit="searchContainer"/>
            </div>
            <div style="position:relative">
                <span v-if="badge.activeUsersBoardBadge && badge.activeUsersBoardBadge[item.id]" class="notification" style="top: -3px;left: auto;right: -3px;">{{badgeFilter(badge.activeUsersBoardBadge[item.id])}}</span>
             
                <BoardIconPreLoad :item="item" :imgClass="'boardNormalIcon'"/>
            </div>
            <div @mouseenter="titleHoverIn" @mouseleave="titleHoverOut" style="width:100%;align-self: center;margin:0 10px;overflow:hidden">
                
                <BoardTitlePreLoad :item="item" titleStyle="line-height: 1.3;font-size: 16px;transition-timing-function: linear;display:inline-block"/>
                <div v-html="lastMessage" class="contentsText lastMessage"></div>              
                    <div @touchstart.stop @click.stop="members(item)" v-if="item.private_flag == 0" class="sm pc" style="overflow:hidden;display: flex;align-items: center;margin-top: 3px;width: fit-content;">    
                        
                        <div v-if="isOpened" style="display: flex;">
                            <div :key="user.id" v-for="user in item.board_to_users.slice(0, 3)" style="position:relative;">                                
                                <UserIconPreLoad size="15" :disableInstant="true" :user="user.user" imgClass="userSmallIcon"/>                                           
                            </div>                                                                                                                  
                            <p style="margin-top:2px;cursor:pointer;font-size:12px;margin-left: 3px;" v-if="item.board_to_users.length > 3">({{item.board_to_users.length}})</p>                                            
                        </div> 
                    </div>
            </div>                                       
                                  
        </div>      
    </div>
</template>

<script setup>
import BoardIconPreLoad from './Mixed/BoardIcon.vue'
import UserIconPreLoad from './Mixed/UserIcon.vue'
import BoardTitlePreLoad from './Mixed/BoardTitle.vue'
import { computed, inject } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useBadgeStore } from '@/store/badge'
import ItemMenu from '../Global/ItemMenu.vue';
import { mentionFormatter } from '@/utils/tools';
    const badge = useBadgeStore()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps(['item', 'hasFailedMessage'])
    const openedBoard = inject('openedBoard')
    const { open, edit, detail, members, pin, leave, remove, setNotification } = inject('boardItem')  
    const isOpened = computed(() => {
        return openedBoard && openedBoard.value && openedBoard.value.id == props.item.id ? true : false
    })
    const boardMenuItems = computed(() => {
        const list = []; 
        function addItem(title, action) {
            list.push({ title, action });
        }
        const editable = props.item.private_flag == 0 && selfMember.value?.admin_flag == 1
        if(editable){
            addItem('編集する', () => edit(props.item))
            addItem('メンバー管理', () => members(props.item))
        }
        addItem('詳細情報', () => detail(props.item))
        addItem(selfMember.value?.pin_flag == 1 ? 'ピン留めを外す' : 'ピン留め', () => pin(props.item))
        addItem(selfMember.value?.notification == 1 ? '通知オフ' : '通知オン', () => setNotification(props.item))
        if(props.item.private_flag == 0){
            addItem('ボード退出', () => leave(props.item))
        }
        if(editable || props.item.private_flag == 1){
            addItem('ボード削除', () => remove(props.item))
        }
        return list
    })
    const selfMember = computed(() => {
        return props.item.board_to_users.find(obj => obj.user_id == auth.id)
    }) 
    const lastMessage = computed(() => {
        if(props.item.last_message){
            if(props.item.last_message.message){
                return mentionFormatter(props.item.last_message.message, true)        
            }else if(props.item.last_message.message_files_exists){
                return 'ファイルメッセージ'
            }else{
                return '現在メッセージはありません'
            }
        }else{
            return '現在メッセージはありません'
        }
        
    })
    const pinCheckOn = computed(() => {            
        const record = props.item.board_to_users.filter(obj => obj.user_id == auth.activeUser.id);
        return (record && record[0].pin_flag == 1)
    })
    const isAdmin = computed(() => {
        return props.item.board_to_users.find(u => u.user_id == auth.activeUser.id && u.admin_flag == 1) ? true : false
    })
    const closeMenu = () => {
        menu.setMenu({name: null, id: null})
    }       
    
    const titleHoverIn = (event) => {         
            
        if(event.target.clientWidth < event.target.firstChild.firstChild.clientWidth){
            const tansitionTimePerPixel = 0.01;
            let textWidth = event.target.firstChild.firstChild.clientWidth;
            let boxWidth = parseFloat(getComputedStyle(event.target).width);
            let translateVal = Math.min(boxWidth - textWidth - 10, 0);
            let translateTime = - tansitionTimePerPixel * translateVal + "s";
            event.target.firstChild.style.transitionDuration = translateTime;
            event.target.firstChild.style.transform = "translateX("+translateVal+"px)";
        }
        
    }
    const titleHoverOut = (event) => {            
        event.target.firstChild.style.transitionDuration = "0.3s";
        event.target.firstChild.style.transform = "translateX(0)";
    }    
    const badgeFilter = (number) => {      
        return number > 99 ? '+99' : number         
        
    } 

</script>
