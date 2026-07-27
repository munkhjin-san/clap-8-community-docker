<template>
    <div :id="'board_item_' + item.id" class="left-panel-wrap">
        <div class="left-panel-items" @click="open(item)" :class="{leftPanelSelected : isOpened}">
            <div :class="['board-info-bar', {openedInfoBar : isOpened}]">
                <div v-if="item.has_draft_message" title="下書き">
                    <Edit size="15" color="var(--kebab-icon)"/>
                </div>
                <div v-if="badge.task[item.id]" class="bg-inherit px-1">
                    <TaskIcon size="15"/>
                </div>                
                <div v-if="hasFailedMessage">
                    <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                        <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                    </svg> 
                </div>
                <div v-if="selfMember?.pin_flag == 1">
                    <svg version="1.1" class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32">
                        <path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623zM6.029 13.958c0.341-0.224 0.749-0.388 1.182-0.474 0.43-0.088 0.887-0.099 1.316-0.032 0.431 0.065 0.834 0.212 1.162 0.42l0.018 0.011c0.428 0.27 0.907 0.285 1.415-0.086l4.759-3.878 4.764-3.898c0.344-0.281 0.505-0.751 0.375-1.206-0.141-0.493-0.155-1.027-0.032-1.541 0.027-0.117 0.211-0.237 0.335-0.111l1.351 1.329 5.164 5.123 1.339 1.368c0.135 0.134-0.014 0.343-0.149 0.374-0.516 0.127-1.037 0.111-1.501-0.043-0.429-0.14-0.923-0.014-1.226 0.356l-0.013 0.018-3.894 4.744-3.88 4.759c-0.393 0.519-0.37 0.961-0.085 1.411l0.010 0.018c0.209 0.329 0.357 0.732 0.42 1.163 0.066 0.43 0.055 0.885-0.034 1.317-0.086 0.434-0.25 0.839-0.474 1.182 0 0.001-0.001 0.002-0.001 0.003-0.071 0.109-0.228 0.122-0.32 0.029l-6.010-6.024-6.022-6.010c-0.093-0.092-0.081-0.248 0.028-0.32 0.001 0 0.001 0 0.002-0.001z"></path>
                    </svg>
                </div> 
            </div>
            <div class="board-c-bar">  
                <ItemMenu :items="boardMenuItems" fit="searchContainer"/>
            </div>
            <div style="position:relative">
                <Badge v-if="badge.activeUsersBoardBadge && badge.activeUsersBoardBadge[item.id]" :count="badge.activeUsersBoardBadge[item.id]" class="activeUsersBoardBadge" style="top: -3px;left: auto;right: -3px;"/>
                <BoardIcon :item="item"/>
            </div>
            <div @mouseenter="titleHoverIn" @mouseleave="titleHoverOut" style="width:100%;align-self: center;margin:0 10px;overflow:hidden">
                <div ref="titleContainer" style="overflow:hidden">
                    <div ref="titleTrack" :style="titleTrackStyle">
                        <BoardTitlePreLoad :key="item.title" :item="item" titleStyle="line-height: 1.3;font-size: 16px;display:inline-block"/>
                    </div>
                </div>
                <div v-html="lastMessage" class="contentsText lastMessage"></div>              
                    <div @touchstart.stop @click.stop="members(item)" v-if="item.private_flag == 0" class="sm pc" style="overflow:hidden;display: flex;align-items: center;margin-top: 3px;width: fit-content;">    
                        
                        <div v-if="isOpened" style="display: flex;">
                            <div :key="user.id" v-for="user in item.board_to_users.slice(0, 3)" style="position:relative;">                                
                                <UserPanel size="15" :disableInstant="true" :user="user.user" imgClass="userSmallIcon"/>                                           
                            </div>                                                                                                                  
                            <p style="margin-top:2px;cursor:pointer;font-size:12px;margin-left: 3px;" v-if="item.board_to_users.length > 3">({{item.board_to_users.length}})</p>                                            
                        </div> 
                    </div>
            </div>                                       
                                  
        </div>      
    </div>
</template>

<script setup lang="ts">
import BoardIcon from './Mixed/BoardIcon.vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import BoardTitlePreLoad from './Mixed/BoardTitle.vue'
import { computed, inject, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useBadgeStore } from '@/store/badge'
import ItemMenu from '../Global/ItemMenu.vue';
import { mentionFormatter } from '@/utils/tools';
import { useRoute } from 'vue-router';
import { BoardMethodsKey, BoardMethods } from '@/interface/keys';
import { BoardMember, MenuList } from '@/interface/globalInterface';
import Edit from '../Icons/Edit.vue';
import Badge from '../Global/Badge.vue';
import TaskIcon from '../Icons/TaskIcon.vue';
    const badge = useBadgeStore()
    const auth = useAuthUserStore()
    const props = defineProps(['item', 'hasFailedMessage'])
    const route = useRoute()
    const { open, edit, detail, members, pin, leave, remove, setNotification } = inject(BoardMethodsKey) as BoardMethods
    const isOpened = computed(() => {
        return route.params.chatId && Number(route.params.chatId) == props.item.id ? true : false
    })
    const boardMenuItems = computed(() => {
        const list:MenuList[] = []; 
        function addItem(title: string, action: () => void) {
            list.push({ title, action });
        }
        const editable = props.item.private_flag == 0 && selfMember.value?.admin_flag == 1
        if(editable){
            addItem('編集する', () => edit(props.item))
            addItem('メンバー管理', () => members(props.item))
        }
        addItem('詳細情報', () => detail(props.item))
        addItem(selfMember.value?.pin_flag == 1 ? 'ピン留めを外す' : 'ピン留め', () => pin(props.item))
        addItem(selfMember.value?.notification == 1 ? '通知設定:ON' : '通知設定:OFF', () => setNotification(props.item ))
        if(props.item.private_flag == 0){
            addItem('チャット退出', () => leave(props.item))
        }
        if(editable || props.item.private_flag == 1){
            addItem('チャット削除', () => remove(props.item))
        }
        return list
    })
    const selfMember = computed(() => {
        return props.item.board_to_users.find((obj: BoardMember) => obj.user_id == auth.activeUser.id)
    }) 
    const lastMessage = computed(() => {
        const { last_message } = props.item;
        if (!last_message) {
            return '現在メッセージはありません';
        }
        if (last_message.message) {
            return mentionFormatter(last_message.message, true, 0.5);
        }
        const messageTypes = [
            { key: 'message_files_exists', label: 'ファイルメッセージ' },
            { key: 'message_quot_exists', label: '引用メッセージ' },
            { key: 'message_forward_exists', label: '転送メッセージ' }
        ];

        const foundType = messageTypes.find(type => last_message[type.key]);
        return foundType ? foundType.label : '現在メッセージはありません';
    });

    const titleContainer = ref<HTMLElement | null>(null)
    const titleTrack = ref<HTMLElement | null>(null)
    const titleTranslateX = ref(0)
    const titleTransitionDuration = ref('0.3s')
    const titleTrackStyle = computed(() => ({
        display: 'inline-block',
        transitionProperty: 'transform',
        transitionTimingFunction: 'linear',
        transitionDuration: titleTransitionDuration.value,
        transform: `translateX(${titleTranslateX.value}px)`,
        willChange: 'transform'
    }))
    const titleHoverIn = () => {
        const containerWidth = titleContainer.value?.clientWidth ?? 0
        const trackWidth = titleTrack.value?.scrollWidth ?? 0

        if (containerWidth <= 0 || trackWidth <= containerWidth) {
            return
        }

        const transitionTimePerPixel = 0.01
        const extraOffset = 10
        const translateDistance = trackWidth - containerWidth + extraOffset

        titleTransitionDuration.value = `${translateDistance * transitionTimePerPixel}s`
        titleTranslateX.value = -translateDistance
    }
    const titleHoverOut = () => {
        titleTransitionDuration.value = '0.3s'
        titleTranslateX.value = 0
    }

</script>
