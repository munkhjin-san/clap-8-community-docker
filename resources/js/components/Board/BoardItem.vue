<template>
    <!-- prototype 12-28-->
    <!--<div :id="'board_item_' + item.id" class="left-panel-wrap" :class="{boardIsOpened : isOpened}">-->
    <div :id="'board_item_' + item.id" class="left-panel-wrap">
            <Transition name="modalFade">
            <div id="boardBoxMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'boardBoxMenu' && $store.state.menu.id == item.id" style="top: 25px;right: 40px;z-index:6;">
                <ul>
                    <li v-if="item.private_flag == 0 && checkAdminAccess(item)" class="boxMenuItems cursor-pointer" @click.stop="$parent.$emit('boardEdit', item), closeMenu()">{{$t('editBoard')}}</li>
                    
                    <li class="boxMenuItems cursor-pointer" @click.stop="$parent.$emit('setDetailedBoard', item),closeMenu()">{{$t('detail')}}</li>
                    <li v-if="item.private_flag == 0" class="boxMenuItems cursor-pointer" @click.stop="$parent.$emit('viewMembers', item), closeMenu()">{{$t('groupMembers')}}</li>
                    <li v-if="item.private_flag == 0" class="boxMenuItems cursor-pointer" @click.stop="$parent.$emit('setInviteBoard', item), closeMenu()">{{$t('inviteMembers')}}</li>
                    <li class="boxMenuItems cursor-pointer" @click.stop="$emit('pinBoard', item.id),closeMenu()">{{pinCheckOn ? $t('unpin') : $t('pin')}}</li>
                    <li v-if="item.private_flag == 0" class="boxMenuItems cursor-pointer" @click.stop="$parent.$emit('leaveBoard', item), closeMenu()">{{$t('leaveGroup')}}</li>
                    <li v-if="item.private_flag == 1 || item.private_flag == 0 && checkAdminAccess(item)" class="boxMenuItems cursor-pointer" @click.stop="$parent.$emit('delete', item.id),closeMenu()"> {{ item.private_flag == 0 ? $t('deleteGroupChat') : $t('deletePrivateChat')}}</li>
                </ul>                                            
            </div>
            </Transition>

        <div class="left-panel-items" @click="openMe(item)" :class="{leftPanelSelected : isOpened}">
            <div :class="['board-info-bar', {openedInfoBar : isOpened}]">
                <div v-if="$store.state.taskBadge[item.id]">
                    <svg v-if="$store.state.taskBadge[item.id]" class="dot-menu" version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" viewBox="0 0 37 32">
                        <path d="M36.297 0.493c-0.529-0.407-1.289-0.312-1.742 0.177l-2.463 2.656-2.479 2.698c-1.644 1.805-3.295 3.607-4.927 5.425-1.633 1.815-3.274 3.625-4.9 5.446-0.906 1.016-1.818 2.030-2.726 3.046-0.293 0.329-0.814 0.303-1.073-0.054-0.061-0.083-0.124-0.169-0.187-0.252l-0.538-0.737-1.64-2.19c-0.726-0.977-1.471-1.94-2.22-2.9l-1.134-1.428c-0.384-0.472-0.767-0.947-1.16-1.413-0.435-0.515-1.21-0.637-1.791-0.225-0.567 0.401-0.704 1.19-0.355 1.792 0.296 0.513 0.607 1.020 0.914 1.528l0.961 1.551c0.652 1.030 1.306 2.056 1.978 3.069l1.509 2.284 0.509 0.755c0.68 1.007 1.366 2.011 2.070 3.003l0.082 0.115c0.095 0.133 0.207 0.252 0.339 0.36 0.794 0.645 1.97 0.495 2.63-0.283 1.569-1.848 3.105-3.724 4.657-5.585 1.564-1.876 3.113-3.766 4.667-5.649 1.558-1.882 3.096-3.779 4.641-5.67l2.304-2.852 2.291-2.858c0.436-0.547 0.358-1.364-0.22-1.809z"></path>
                        <path d="M30.798 13.688c-0.736 0.045-1.297 0.682-1.307 1.417l-0.182 13.496c-0.004 0.298-0.247 0.532-0.545 0.527-1.719-0.029-3.439-0.041-5.158-0.055l-7.281-0.017-7.281-0.001-5.073 0.015c-0.257 0-0.465-0.21-0.462-0.466 0.019-1.7 0.019-3.398 0.019-5.098l-0.026-7.281-0.026-7.279-0.033-5.239c-0.001-0.21 0.168-0.38 0.378-0.381 1.558-0.010 3.114-0.023 4.671-0.031l20.184-0.204c0.809-0.008 1.46-0.691 1.409-1.517-0.046-0.754-0.701-1.326-1.457-1.334l-20.136-0.204c-2.244-0.012-4.486-0.037-6.729-0.038-0.915 0-1.66 0.739-1.667 1.655v0.010l-0.049 7.281-0.024 7.279-0.026 7.281c0 2.427 0 4.854 0.055 7.279l0.001 0.037c0.022 0.925 0.777 1.67 1.709 1.673l7.281 0.022 7.281-0.003 7.281-0.018c2.427-0.018 4.854-0.029 7.281-0.106l0.074-0.003c0.86-0.026 1.542-0.736 1.531-1.603l-0.212-15.725c-0.015-0.787-0.68-1.421-1.482-1.372z"></path>
                    </svg>   
                                             
                </div>
                
                <div v-if="hasFailedMessage">
                    <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                        <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                    </svg> 
                </div>
                <div v-if="pinCheckOn">
                    <svg version="1.1" class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623zM6.029 13.958c0.341-0.224 0.749-0.388 1.182-0.474 0.43-0.088 0.887-0.099 1.316-0.032 0.431 0.065 0.834 0.212 1.162 0.42l0.018 0.011c0.428 0.27 0.907 0.285 1.415-0.086l4.759-3.878 4.764-3.898c0.344-0.281 0.505-0.751 0.375-1.206-0.141-0.493-0.155-1.027-0.032-1.541 0.027-0.117 0.211-0.237 0.335-0.111l1.351 1.329 5.164 5.123 1.339 1.368c0.135 0.134-0.014 0.343-0.149 0.374-0.516 0.127-1.037 0.111-1.501-0.043-0.429-0.14-0.923-0.014-1.226 0.356l-0.013 0.018-3.894 4.744-3.88 4.759c-0.393 0.519-0.37 0.961-0.085 1.411l0.010 0.018c0.209 0.329 0.357 0.732 0.42 1.163 0.066 0.43 0.055 0.885-0.034 1.317-0.086 0.434-0.25 0.839-0.474 1.182 0 0.001-0.001 0.002-0.001 0.003-0.071 0.109-0.228 0.122-0.32 0.029l-6.010-6.024-6.022-6.010c-0.093-0.092-0.081-0.248 0.028-0.32 0.001 0 0.001 0 0.002-0.001z"></path>
                    </svg>
                </div> 
            </div>
            <div class="board-c-bar">
                
                  
                <div v-if="active" class="boardMenuContainer cursor-pointer" @click.stop="$store.commit('setMenu', {name: 'boardBoxMenu', id: item.id})" @touchstart.stop>                                            
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin:auto;">
                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                    </svg>
                </div>
            </div>

            <div style="position:relative">
                <span v-if="$store.state.boardBadge && $store.state.boardBadge && $store.state.boardBadge[item.id]" class="notification" style="top: -3px;left: auto;right: -3px;">{{badgeFilter($store.state.boardBadge[item.id])}}</span>
             
                <BoardIconPreLoad :item="item" :imgClass="'boardNormalIcon'"/>
            </div>
            <div @mouseenter="titleHoverIn" @mouseleave="titleHoverOut" style="width:100%;align-self: center;margin:0 10px;overflow:hidden">
                
                <BoardTitlePreLoad :item="item" titleStyle="line-height: 1.3;font-size: 16px;transition-timing-function: linear;display:inline-block"/>
                <div :id="'newMessage_' + item.id" v-html="lastMessage" class="contentsText lastMessage"></div>
                
                  
                    <div @touchstart.stop @click.stop="$parent.$emit('viewMembers', item)" v-if="item.private_flag == 0" class="sm pc" style="overflow:hidden;display: flex;align-items: center;margin-top: 3px;width: fit-content;">    
                        
                        <div v-if="isOpened" style="display: flex;">
                            <div :key="user.id" v-for="user in confirmedMembers.slice(0, 3)" style="position:relative;">                                
                                <UserIconPreLoad size="15" :user="user.user" imgClass="userSmallIcon"/>                                           
                            </div>
                                                                                                                  
                            <p style="margin-top:2px;cursor:pointer;font-size:12px;margin-left: 3px;" v-if="confirmedMembers.length > 3">({{confirmedMembers.length}})</p>                                            
                        </div> 
                    </div>
            </div>                                       
                                  
        </div>      
    </div>
</template>

<script>
// import NotifyComponent from "../NotifyComponent.vue";
import BoardIconPreLoad from './Mixed/BoardIcon.vue'
import UserIconPreLoad from './Mixed/UserIcon.vue'
import BoardTitlePreLoad from './Mixed/BoardTitle.vue'
import moment from 'moment'
    export default {
        props: ['item', 'openedBoard', 'isOpened', 'hasFailedMessage', 'active'],
        emits: ['setDetailedBoard', 'pinBoard', 'boardEdit', 'openMenu', 'deleted', 'openBoard', 'viewMembers', 'leaveBoard', 'setDetailedBoard'],
        mounted() {
            
        },
        components:{
            BoardIconPreLoad,
            UserIconPreLoad,
            BoardTitlePreLoad
        },
        computed: {
            confirmedMembers(){
                const allMembers = this.item.board_to_users;               
                return allMembers
            },
            lastMessage(){
                if(this.item.last_message){
                    

                    
                    if(this.item.last_message.message){
                        const to_all = this.item.last_message.message.replace('<span class="toAll">@allMemberMention</span>', `<a class="toAll">@${this.$t('allMemberMention')}</a>`).replaceAll('<span class="addedMembers">', '<a class="addedMembers">').replaceAll('</span>', '</a>'); ; 
                        const converterd = to_all.replace(/<((?!a )[^>]*)>/g, "&lt;$1&gt;").replace(/&lt;\/a&gt;/g, "</a>");
                        const br_remove = converterd.replace(/&lt;br&gt;/g," ");
                        return br_remove
                    }else if(this.item.last_message.message_files && this.item.last_message.message_files.length){
                        return this.$t('fileMessage')
                    }else{
                        return this.$t('noMessage')
                    }
                    
                }else{
                    return this.$t('noMessage')
                }
                
            },
            pinCheckOn(){            
                const record = this.item.board_to_users.filter(obj => obj.user_id == this.$store.state.user.id);
                return (record && record[0].pin_flag == 1)
            },
        },
        methods: {
            pushInstantUser(id){
                if(id == this.$store.state.user.id) return
                const cX = event.clientX;
                const cY = event.clientY;  
                const data = {
                    id: id,
                    cX: cX,
                    cY: cY
                }
                this.$store.commit('setInstantUser', data)   
                this.$store.commit('setMenu', {name: 'instantProfileWindow', id: 5000})                
            },
            closeMenu(){
                const menu = {name: null, id: null}
                this.$store.commit('setMenu', menu)
            },
            openMe(item){
                this.$emit('openBoard', item, 'fromList')
            },           
            checkAdminAccess: function (item) {
                var admin_user = false;
                for (var i in item.board_to_users) {
                    var user_lists = item.board_to_users[i];
                    if (user_lists.user_id == this.$store.state.user.id && user_lists.admin_flag == 1) {
                        admin_user = true
                    }
                }
                if (item.user_id == this.$store.state.user.id || admin_user) {
                    return true;
                }
                return false;
            },
            titleHoverIn(){         
                   
                if(event.target.clientWidth < event.target.firstChild.firstChild.clientWidth){
                    const tansitionTimePerPixel = 0.01;
                    let textWidth = event.target.firstChild.firstChild.clientWidth;
                    let boxWidth = parseFloat(getComputedStyle(event.target).width);
                    let translateVal = Math.min(boxWidth - textWidth - 10, 0);
                    let translateTime = - tansitionTimePerPixel * translateVal + "s";
                    event.target.firstChild.style.transitionDuration = translateTime;
                    event.target.firstChild.style.transform = "translateX("+translateVal+"px)";
                }
                
            },
            titleHoverOut(){            
                event.target.firstChild.style.transitionDuration = "0.3s";
                event.target.firstChild.style.transform = "translateX(0)";
            },
            
            badgeFilter(number){      
                return number > 99 ? '+99' : number         
                
            },  
        }
    }
</script>
