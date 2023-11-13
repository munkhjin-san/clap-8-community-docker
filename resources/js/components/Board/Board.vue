<template>
    <!-- <Transition name="smLeave"> -->
        <div class="boardOuterContainer" style="width: 100%;height: 100%;display:flex;flex-grow: 1;overflow: hidden;">     
            <div class="boardInnerContainer">        
                <Transition name="searchHide">
                <BoardSearchBar 
                    v-if="searchView"
                    :allBoardList="allBoardList" 
                    @openBoard="openTargetBoard"
                    @openMessageSearch="openMessageSearch"
                />
                </Transition>
                <BoardList 
                    v-show="!$store.state.mobile || ($store.state.mobile && $route.name == 'board')"
                    :list="filteredAllBoard"   
                    :openedBoard="openedBoard" 
                    :skeletonBoard="skeletonBoard"     
                    :failedMessagesList="failedMessagesList"  
                    :key="listKey"  
                    @boardEdit="boardEdit" 
                    @boardCreate="boardCreate" 
                    @reload="getBoardList"
                    @delete="boardDeleteConfirm"
                    @openBoard="openBoard"
                    @setDetailedBoard="setDetailedBoard"
                    @setSearchView="setSearchView"
                    @setInviteBoard="(item) => inviteTarget = item"
                    @viewMembers="viewMembers"
                    @leaveBoard="leaveBoard"
                />
            </div>
            <Transition name="modalFade">
                <BoardDetails v-if="detailedBoard" :board="detailedBoard" @close="detailedBoard = null"/>
            </Transition>
            
            <Transition name="modalFade">
                <SearchMessage 
                    v-if="searchMessageView"
                    :advancedSearchWord="advancedSearchWord"
                    :filteredAllBoard="allBoardList"
                    :privateSearch="privateSearch"
                    :key="searchWindowKey"
                    @closeMessageSearch="closeMessageSearch"
                    @jumpToMessage="jumpToMessage"
                />
            </Transition>
            <Transition name="modalFade" mode="out-in">
                <BoardEdit 
                    v-if="createEditWindow"
                    :privateFlag="privateFlag" 
                    :editIndex="editIndex" 
                    :item="activeEditBoard"
                    @close="closeCreateModal"
                    @reload="boardEditFinished"
                    @openPrivateBoard="openPrivateBoard"
                />
            </Transition>

            
            <router-view v-slot="{ Component }">
                <transition name="slideFromRight">
                    <component 
                        :is="Component" 
                        :messageLoader="messageLoader"
                        :key="messageContainerKey"
                        :messageList="messageList"
                        :openedBoard="openedBoard"
                        :microLoader="microLoader"
                        :zIndexTable="zIndexTable"
                        :queuedMessages="queuedMessages"
                        :failedMessagesList="failedMessagesList"   
                        :messageListType="messageListType" 
                        :searchTargetId="searchTargetId"
                        :unreadMessages="unreadMessages"
                        :hasAccessibleChat="hasAccessibleChat"
                        from="pc"
                        @reload="getMessageList"
                        @addQueue="addQueue"
                        @sendError="sendError"
                        @sentMessage="sentMessage"      
                        @checkRequest="checkRequest"
                        @remindRequest="remindRequest"  
                        @resetPageIndex="resetPageIndex" 
                        @appendSearchResult="appendSearchResult"
                        @afterRequestHandled="afterRequestHandled"
                        @resetReplyQuot="resetReplyQuot"
                        @closeContainer="closeMessageContainer"    
                        @removeError="removeError"  
                        @reachedTop="reachedTop"     
                        @copyText="copyTextStart"      
                        @startPrivateSearch="startPrivateSearch"        
                        @viewMembers="viewMembers"    
                        @jumpToMessage="jumpMessageFromFile"
                    />
                </transition>
            </router-view> 
            
            <TrayComponent
                v-if="openedBoard && !$store.state.mobile" 
                :selectAbleUsers="[]" 
                :fullScreen="trayFullScreen" 
                :board_record="openedBoard" 
                :key="trayComponentKey" 
                :myBoard="myBoard"
                :filesFromBoard="filesFromBoard"
                :importingFiles="importingFiles"
                :zIndexTray="zIndexTray"
                :mobileView="trayMobileView"
                :trayItemWhich="trayItemWhich"
                :hasAccessibleChat="hasAccessibleChat"
                @setTrayZindex="setTrayZindex"
                @setTrayItem="setTrayItem"
                @viewTray="viewTray"
                @updateTaskNotify="getTaskNotify"
                @jumpToMessage="jumpMessageFromFile"
            />
            <Transition name="modalFade">
                <CopyWindow v-if="copyTextToggle" :data="copyAreaData" @closeMe="copyTextToggle = false"/>
            </Transition>
            <Transition name="modalFade">
                <ConfirmWindow @reload="getMessageList" v-if="confirmWindow" :requestType="requestType" :message="checkRequestData" @closeMe="confirmWindow = false"/>
            </Transition>  
            <Transition name="modalFade">
                <InviteMember @close="inviteTarget = null" v-if="inviteTarget" :item="inviteTarget" @reload="boardEditFinished"/>
            </Transition>      
            <Transition name="modalFade">
                <BoardCreateWindow 
                    @close="boardCreateClose" 
                    v-if="newBoardWindow"
                    @reload="boardEditFinished"
                    @openPrivateBoard="openPrivateBoard"
                />
            </Transition> 

            <Transition name="modalFade">
                <BoardMembers 
                    :board="memberControllingRecord"
                    @close="memberControlRecord = null" 
                    v-if="memberControlRecord"
                    @reload="boardEditFinished"
                    @setInvite="setInvite"
                    @afterRequestHandled="afterRequestHandled"
                />
            </Transition> 
        </div>
    <!-- </Transition> -->
    </template>
    
    <script>
    import BoardList from './BoardList.vue'
    import { defineAsyncComponent } from 'vue'
    import MessageContainer from './Message/MessageContainer.vue'
    import TrayComponent from './Tray.vue'
    import BoardSearchBar from './Search/BoardSearchBar.vue'
    import { nextTick } from 'vue'
    import InviteMember from './InviteMember'
    import BoardCreateWindow from './BoardCreateWindow.vue'
    import BoardMembers from './BoardMembers.vue'

        export default {            
            data(){
                return{
                    mainLoader: false,
                    allBoardList: [],
                    editIndex: 0,
                    privateFlag: 0,
                    createEditWindow: false,
                    activeEditBoard: null,
                    activeGroupButton: 0,
                    activeGroupName: 'すべて',
                    userGroupList: [],
                    pageIndex: 1,
                    pageIndexLimiter: false,
                    messageList: [],
                    openedBoardId: null,
                    scrollFlag: false,
                    copyTextToggle: false,
                    copyAreaData: null,
                    confirmWindow: false,
                    checkRequestData: null,
                    microLoader: false,
                    currentLen: 0,
                    infiniteLock: false,
                    zIndexTable: 9,
                    zIndexTray: 8,
                    messageContainerKey: 0,
                    queuedMessages: [],
                    messageLoader: true,
                    failedMessagesList: [],
                    trayComponentKey: 1999,
                    searchWindowKey: 27000,
                    trayFullScreen: false,
                    messageResult: {
                        data: [],
                    },
                    advancedSearchWord: '',
                    searchMiniLoader: false,
                    searchLoader: false,
                    searchMessageView: false,
                    messageListType: 'normal',
                    privateSearch: false,
                    searchTargetId: null,
                    scrllDir: 'up',
                    appendLock: false,
                    filesFromBoard: [],
                    importingFiles:[],
                    trayMobileView: false,
                    trayItemWhich: -1,
                    detailedBoard: null,
                    unreadMessages: {
                        active: false,
                        id: null,
                        count: 0
                    },
                    listKey: 986,
                    searchPageIndex: 1,
                    searchView: true,
                    inviteTarget: null,
                    newBoardWindow: false,
                    memberControlRecord: null,
                    requestType: '',
                    routeWatchLock: false
                }
            },
            components:{
                BoardList,               
                BoardDetails: defineAsyncComponent(() => import('./BoardDetails.vue')),
                BoardSearchBar,
                SearchMessage: defineAsyncComponent(() => import('./Search/SearchMessage.vue')),
                BoardEdit: defineAsyncComponent(() => import('./BoardEdit.vue')),
                CopyWindow: defineAsyncComponent(() => import('./Message/CopyWindow.vue')),
                ConfirmWindow: defineAsyncComponent(() => import('./Message/ConfirmWindow.vue')),
                MessageContainer,
                TrayComponent,
                InviteMember,
                BoardCreateWindow,
                BoardMembers
                
            },
            watch: {
                // unreadMessages(after, before){
                //     if(after){
                //         setTimeout(() => {
                //             const element = document.getElementById('messageRoot_' + after);
                            
                //             const rect = element.getBoundingClientRect()
                //             if(rect.y + rect.height < 0){
                //                 const data = {
                //                     status: true,
                //                     count: this.$store.state.boardBadge[this.openedBoard.id],
                //                     id: this.message.id
                //                 }
                //                 // this.$emit('unreadJumperOn', data)
                                
                //             }
    
    
                //         },0)
                //     }
                // },
                '$store.state.focused' (after, before) {
                    if(after){
                        if(this.openedBoard && this.$store.state.boardBadge && this.$store.state.boardBadge[this.openedBoard.id]){
                            setTimeout(()=>{
                                this.updateBadge(this.openedBoard)
                            },3000)
                        }
                    }
                },
                '$store.state.boardBadge' (after, before) {
                    
                    if(this.$store.state.focused && this.openedBoard && after[this.openedBoard.id]){
                        setTimeout(() =>{
                            this.updateBadge(this.openedBoard)
                        },3000)
                    }
                    
                },
                '$route.params.chatId'(chatId) {
                    if(chatId){
                        const targetBoard = this.allBoardList.filter(ob => ob.id == chatId)
                        if(this.routeWatchLock){
                            return
                        }
                        if(targetBoard.length){
                            this.openBoard(targetBoard[0], 'watch')    
                            
                                                                        
                        }
                    }else{
                        this.closeMessageContainer()
                    }
                }
                // '$store.state.forwardToBoard' (after, before) {
                    
                //     if(after && after.active){
                //         this.openBoard(after.target)
                //     }
                    
                // }
            },     
            created(){
                const trayIndex = localStorage.getItem('favorite_tray');
                this.trayItemWhich = trayIndex ? parseInt(trayIndex) : 1
                // const customLocale = localStorage.getItem('lang')
                // if(customLocale){
                //     this.$store.commit('setLocale', customLocale)
                //     this.$i18n.locale = customLocale
                // }else {
                //     const browserLang = navigator.language.substring(0, 2)
                //     if (browserLang === 'ja' || browserLang === 'mn') {
                //         this.$store.commit('setLocale', browserLang)
                //         this.$i18n.locale = browserLang
                //     } else {
                //         this.$store.commit('setLocale', 'en')
                //         this.$i18n.locale = 'en'
                //     }
                // }
            },    
            unmounted() {
                this.$store.commit('setMenu', {name: '', id: null})
                if(navigator.virtualKeyboard){
                    navigator.virtualKeyboard.removeEventListener('geometrychange', this.keyboardHeightListener);
                }
            },
            mounted() {
                if(navigator.virtualKeyboard){
                    navigator.virtualKeyboard.addEventListener('geometrychange', this.keyboardHeightListener);
                }

                console.log('mntd')
                this.closeMessageContainer()
                
                this.listKey ++
                if(this.$route.params && this.$route.params.item){
                    this.$store.commit('setUrlBoardId', this.$route.params.item.id)  
                }
                if(this.$route.params && this.$route.params.openForwardTargetBoard){
                    this.openBoard(this.$route.params.openForwardTargetBoard)    
                }
                const url_string = window.location.href;
                const url = new URL(url_string);
                const b_id = url.searchParams.get("id");
                
                if(b_id){                
                    this.$store.commit('setUrlBoardId', parseInt(b_id))                
                    console.log(b_id)
                }
                const m_id = url.searchParams.get("m");
                if(m_id){                
                    this.$store.commit('setUrlMessageId', parseInt(m_id))                
                }
                const t_id = url.searchParams.get("t");
                if(t_id){                
                    this.$store.commit('setUrlTaskId', parseInt(t_id)) 
                    const data = {status : true, val: 1}
                    const t_edit = url.searchParams.get("task_edit");
                    console.log(t_edit)
                    if(t_edit){     
                        if(t_edit === 'true'){
                            this.$store.commit('setUrlTaskEditFlag', true)   
                        }                              
                    }
                    this.viewTray(data)
                               
                }
                const c_id = url.searchParams.get("correspond_target");
                emitter.on('messageShareTo', (data) => {  
                    if(this.trayItemWhich !== data){
                        this.setTrayItem(data);
                    }                            
                })
                emitter.on('openPrivateBoardIntant', (id) => {  
                    this.openPrivateBoardIntant(id)                         
                })
                emitter.on('openMessageSearch', () => this.openMessageSearch(''))
                emitter.on('openForwardTargetBoard', (data) => {  
    
                    this.openBoard(data)          
                })
    
                emitter.on('notifyUpdateCompleted', (data) => {  
                    this.getBoardList('ntfyup')  
                })
                emitter.on('updateMessages', (data) => {
                    console.log('boardgetMessage')
                    this.getMessageList()
                })       
                emitter.on('notifyFetched', (data) => {  
    
                    this.unreadLineTrigger()    
                    this.getBoardList()      
                })
                // document.body.style.height = '100%';
                // document.body.style.position = 'fixed';
                // document.body.style.overflow = 'hidden';
                // if(this.$store.state.mobile){
                //     document.body.style.background = 'var(--background-color)'
                // }
                this.getBoardList('mounted')
                // this.getGroup();
                this.getUnsentMessages();
                this.getTaskNotify();
                

                // Echo.channel('my-channel').listen('Message', (e) => {  
                emitter.on('pusher-event', (e) => {
                    // if(e.message.title && e.message.sender !== this.$store.state.user.id && e.message.board_members.includes(this.$store.state.user.id)){
                    //     if (Notification.permission === 'granted') {
                    //         this.sendNotification(e)
                    //     } else if (Notification.permission !== 'denied') {
                    //         Notification.requestPermission().then((permission) => {
                    //         if (permission === 'granted') {
                    //             this.sendNotification(e)
                    //         }
                    //         });
                    //     }
                    // }
                    if(e.message.board_id && e.message.sender !== this.$store.state.user.id){
                        const index = this.allBoardList.map( ob => ob.id).indexOf(e.message.board_id);                  
                        if(index > -1){
                            this.getBoardList('pusher');
                            emitter.emit('notifyGet','pusher')
                        }
                        if(this.openedBoard && this.openedBoard.id == e.message.board_id && this.messageListType == 'normal' && e.message.sender !== this.$store.state.user.id){
                            this.getMessageList('pusher'); 
                        }
                    }
                    if(e.message.new_board_members){
                        const index = e.message.new_board_members.indexOf(this.$store.state.user.id);                  
                        if(index > -1){
                            this.getBoardList('pusher');
                            emitter.emit('notifyGet','pusher')
                        }
                        
                    }
                    if(e.message.board_updated){                    
                        this.getBoardList('pusher');
                        emitter.emit('notifyGet','pusher')                    
                        
                    }
                    if(e.message && e.message.updateId){  
                        
                    }
                });
            },        
            computed: {
                openedBoard(){
                    if(this.allBoardList && this.allBoardList.length && this.openedBoardId){
                        const active = this.allBoardList.filter(ob => ob.id == this.openedBoardId)
                        return active && active.length ? active[0] : null
                    }
                    return null
                },
                skeletonBoard(){
                    return this.$store.state.skeleton
                },
                hasAccessibleChat(){
                    if(this.openedBoard){
                        return this.openedBoard.board_to_users.filter(ob => ob.user_id == this.$store.state.user.id).length
                    }
                    return false
                },
                memberControllingRecord(){
                    return this.allBoardList ? this.allBoardList.filter(ob => ob.id == this.memberControlRecord)[0] : null
                    
                },
                myBoard(){
                    if(this.allBoardList){                 
                        var res = this.allBoardList.filter(obj=>obj.private_flag == 3)[0];
                        return res                 
                    }
                },
                filteredAllBoard(){
                    return this.allBoardList
                    // if(this.allBoardList){
                    //     if(this.activeGroupButton == 0){
                    //     return this.allBoardList;
                    //     }else if(this.activeGroupButton == -1){
                    //         var res = this.allBoardList.filter(obj=>obj.private_flag == 0);
                    //         return res;
                    //     }
                    //     else if(this.activeGroupButton == -2){
                    //         var res = this.allBoardList.filter(obj=>obj.private_flag !== 0);
                    //         return res;
                    //     }
                    //     else{
                    //         var raw = this.userGroupList.filter(obj => obj.id == this.activeGroupButton);
                    //         if(raw && raw.length){    
                    //             var rawNew = raw[0].board_list;          
                    //             var group = JSON.parse("[" + rawNew + "]");                   
                    //             var list = [];
                    //             if(this.allBoardList){
                    //                 this.allBoardList.forEach((board) => {
                    //                     if(group.indexOf(board.id) > -1){
                    //                         list.push(board);
                    //                     }                   
                    //                 });                
                    //             return list;
                    //             }
                    //         }
                    //     } 
                    // }else{
                    //     return []
                    // }
                }
            },
            methods: {
                // sendNotification(e){
                //     if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                //         console.log(e)
                //         navigator.serviceWorker.controller.postMessage({
                //             type: 'pushNotification',
                //             data: e,
                //         });
                //     } 
                // },
                keyboardHeightListener(event){
                    const { x, y, width, height } = event.target.boundingRect;
                    console.log('Virtual keyboard geometry changed:', x, y, width, height);
                    this.$store.commit('setKeyboardOffset', height)
                },
                boardDeleteConfirm(id){    
                    var uniqueChannell = Math.random().toString(36).substring(5);   
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('confirmToDeleteChat') ,
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                        channel: uniqueChannell

                    })            
                    emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.boardDelete(id): false});
                    
                },
                boardDelete(id) {          
                    axios.post('/messages_delete_api', {
                        id: id
                    }).then(response => {
                        if(response.status == 200){
                            if(this.openedBoard && this.openedBoard.id == id){                                
                                this.closeMessageContainer()
                            }
                            this.getBoardList()
                            const data = {
                                text: '削除しました。',
                                channel: Math.random().toString(36).substring(5),
                                icon: 0,
                                view: true
                            }
                            emitter.emit('setInfo', data)
                        }
                                    
                    });
                }, 
                leaveBoard(board){
                    const uniqueChannell = Math.random().toString(36).substring(5);
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: this.$t('confirmToLeaveBoard', {title: board.title}),
                        closeButton: false, 
                        autoClose: false,
                        answers: [this.$t('confirmToAction'), this.$t('cancelToAction')],
                        channel: uniqueChannell

                    })            
                    emitter.on(uniqueChannell, (data) => { data.answer === this.$t('confirmToAction') ? this.leaveBoardSend(board): false});
                },
                leaveBoardSend(board){

                    axios.post('/leave_board', {id: board.id}).then(response => {  
                        if(response.status == 200){
                            if(this.openedBoard && this.openedBoard.id == board.id){
                                this.closeMessageContainer()
                            }
                            this.getBoardList()
                            const data = {
                                text: '退出しました。',
                                channel: Math.random().toString(36).substring(5),
                                icon: 0,
                                view: true
                            }
                            emitter.emit('setInfo', data)
                        }
                                   
                
                    }).catch(function (error) {
                        if (error.response) this.errorToast(this.$t(error.response.data.message))
                        else if (error.request) this.errorToast(this.$t('commonError'))
                        else this.errorToast(this.$t('commonError'))                           
                    }.bind(this));
                },
                setInvite(board){
                    this.memberControlRecord = null,
                    setTimeout(() => {
                        this.inviteTarget = board
                    }, 200);
                    
                },
                viewMembers(board){
                    this.memberControlRecord = board.id
                },
                afterRequestHandled(response, id){
                    console.log(response)
                    if(response === 'respondDeleted'){
                        this.closeMessageContainer()
                        this.getBoardList()
                    }else if(response === 'respondConfirmed'){
                        this.getBoardList(null, id)
                    }
                },
                setSearchView(flag){
                    this.searchView = flag
                },
                openPrivateBoardIntant(id){
                    this.getBoardList(null, id)
                },
                openPrivateBoard(id){
                    console.log('999999')
                    const target = this.filteredAllBoard.filter(ob => ob.id == id)
                    if(target.length){
                        console.log('opentargetBoard')
                        this.closeCreateModal()
                        this.openTargetBoard(target[0])
                    }else{
                        this.errorToast(this.$t('commonError'))
                    }
    
                },
                setDetailedBoard(val){
                    this.detailedBoard = val
                },
                setTrayItem(val){
                    this.trayItemWhich = val
                    localStorage.setItem('favorite_tray', val)
                },
                setTrayZindex(index){
                    this.zIndexTray = index
                },
                viewTray(val){
                    this.trayMobileView = val.status
                    this.setTrayItem(val.val)
                },
                appendSearchResult(dir){
                    if(this.scrllDir !== dir){
                        this.scrllDir = dir
                        this.appendLock = false 
                    }
                    if(this.appendLock || !this.messageList.length) return
                    this.appendLock = true
                    let lastMessage = null
                    if(this.scrllDir == 'up'){
                        lastMessage = this.messageList[this.messageList.length - 1].id
                    }else if(this.scrllDir == 'down'){
                        lastMessage = this.messageList[0].id
                    }
                    
                    const currentLength = this.messageList.length
                    let data = {
                        direction: this.scrllDir,
                        last_message_id: lastMessage
                    }
                    var container1 = document.getElementById('boardListInner')
                    var currentPos = container1.scrollHeight; 
                    this.microLoader = true
                    axios.post('/get_bottom_messages', data).then(response => {  
                        if(this.scrllDir == 'up'){
                            this.messageList = this.messageList.concat(response.data)
                            if(this.messageList.length !== currentLength){
                                this.appendLock = false 
                            }
                        }else if(this.scrllDir == 'down'){
                            this.messageList = response.data.concat(this.messageList)
                            if(this.messageList.length !== currentLength){
                                this.appendLock = false 
                            }
                            nextTick(() => {                   
                                var cont = document.getElementById('boardListInner')            
                                cont.scrollTop = currentPos - cont.scrollHeight               
                            });  
                            
                        }
                        
                        
                        setTimeout(() => {this.microLoader = false}, 200)
                
                    }).catch(function (error) {                
                        setTimeout(() => {this.microLoader = false}, 200)                    
                    }.bind(this));
                },
                jumpMessageFromFile(file){                  
                    const target = {
                        id: file.message_id,
                        record_id: file.board_id
                    }                  
                    this.jumpToMessage(target)                
                },
                jumpToMessage(message){
                    
                    this.messageLoader = true
                    axios.post('/get_target_message', message).then(response => {  
                        
                        let board = this.allBoardList.filter( obj => obj.id == message.record_id);
                        if(board.length){
                            this.openBoard(board[0], 'search')
                            setTimeout(() => {
                                document.getElementById('board_item_' + board[0].id)?.scrollIntoView({ behavior: 'smooth', block: 'center' }) 
                            },100)                         
                            this.messageList = response.data;
                            this.messageContainerKey ++;
                            this.messageLoader = false
                            this.searchMessageView = false
                            this.searchTargetId = message.id                        
                            this.messageListType = 'search'                        
                        }
                        this.appendLock = false              
                
                    }).catch(function (error) {
                        if (error.response) this.errorToast(this.$t(error.response.data.message))
                        else if (error.request) this.errorToast(this.$t('commonError'))
                        else this.errorToast(this.$t('commonError'))       
                        this.$store.commit('setUrlMessageId', null)   
                        this.messageLoader = false                        
                    }.bind(this));
                },
                errorToast(message){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: message,
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK']

                    })   
                },
                startPrivateSearch(){
                    this.privateSearch = true
                    this.searchMessageView = true
                },
                openMessageSearch(keyword){
                    this.privateSearch = false
                    this.advancedSearchWord = keyword
                    // document.getElementById('boardSearchArea').blur();
                    this.searchMessageView = true
                },
                closeMessageSearch(){
                    this.searchMessageView = false
                    // const data = {
                    //     data: [],
                    //     fetched: false
                    // }
                    // this.messageResult = data
                },
    
                openTargetBoard(item, hasPush){
                    this.openBoard(item)
                    setTimeout(() =>{document.getElementById('board_item_' + item.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' })  },0)
                    
                    
                },
                removeQueue(item){
                    const index = this.queuedMessages.map(e => e.id).indexOf(item.id);
                    if(index > -1){
                        this.queuedMessages = this.queuedMessages.filter(ob => ob.id !== item.id)
                    }
                },
                removeError(id){
                    const index = this.queuedMessages.map(e => e.id).indexOf(id);
                    
                    if(index > -1){
                        this.queuedMessages = this.queuedMessages.filter(ob => ob.id !== id)
                    }
                    var failedList = localStorage.getItem('failed_messages');
                    if(failedList){
                        let data = JSON.parse(failedList)
                        const index = data.map(e => e.id).indexOf(id);
                        if(index > -1){
                            data = data.filter( ob => ob.id !== id)
                            localStorage.setItem('failed_messages', JSON.stringify(data));
                            
                            this.getUnsentMessages(this.openedBoard.id);
                        }
                        
                    }
                },
                sendError(item){
                    // let index = this.queuedMessages.indexOf(ob => ob.id == item.id)
                    // const index = this.queuedMessages.map(e => e.id).indexOf(item.id);
                    // if(index > -1){
                    //     this.queuedMessages[index].error = true
                    // }
                    let err = item
                    err.error = true
                    var failedList = localStorage.getItem('failed_messages');
                    if(failedList){
                        let data = JSON.parse(failedList)
                        const index = data.map(e => e.id).indexOf(item.id);
                        if(index == -1){
                            data.push(err)
                            localStorage.setItem('failed_messages', JSON.stringify(data));
                        }
                        
                    }else{
                        let data = []
                        data.push(err)
                        localStorage.setItem('failed_messages', JSON.stringify(data));
                    }
                    this.getUnsentMessages(this.openedBoard.id);
                    
                },
                addQueue(item){
                    console.log('qeqeqeqe')
                    
                    this.queuedMessages.push(item)
                    // localStorage.setItem('failed_', 'Tom');
                    // setTimeout(() => {
                    //     const el = document.getElementById(item.id)
                    //     // const el = document.getElementById('boardListInner')
                    //     // el ? el.scrollTo({ top: 0, left: 0, behavior: 'smooth' }) : false
                    // }, 500)
                    
                },
                sentMessage(item){
                    this.getMessageList('queue', item);
                    this.getBoardList()
                    if(item && item.attached_temp_files && item.attached_temp_files.length){
                        this.trayComponentKey ++;
                    }
                    
                },
                openMenu(data){
                    this.$store.commit('setMenu', data);
                },
                
                closeMessageContainer(){
                    this.openedBoardId = null;
                    this.messageList = [];
                    this.zIndexTable = 9;
                    this.zIndexTray = 8;
                    this.messageContainerKey ++
                    this.$store.commit('setActiveBoard', null);
    
                },
                reachedTop(){
                    this.currentLen = this.messageList.length
                    if(!this.pageIndexLimiter && !this.infiniteLock){
                        this.pageIndexLimiter = true
                        this.pageIndex ++ 
                        this.getMessageList('infiniteLoader')
                        this.microLoader = true
                    }
                    
    
                },
                remindRequest(data){
                    axios.post('/remind_add', {
                            id: data.id
                    }).then(response => {
                        if(response.data == true){
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: 'リマインドしました。',
                                closeButton: false, 
                                autoClose: false,
                                answers: ['OK']
                            })
                        }else{
                            emitter.emit('setToast', {
                                active: true,  
                                type: 'info', 
                                content: 'リマインドを取り消しました。',
                                closeButton: false, 
                                autoClose: false,
                                answers: ['OK']
                            })
                        }
                        this.getMessageList()
                    });
                },
                checkRequest(data, request){
                    this.checkRequestData = data
                    this.confirmWindow = true
                    this.requestType = request
                },
                copyTextStart(ob){
                    this.copyAreaData = ob
                    this.copyTextToggle = true
                },
                resetReplyQuot(){
                    const quot_reply = {
                        active: false,
                        message: null,
                        which: null,
                        text: null,
                        file: false,
                        height: 100,
                        width: 100
                    }
                    this.$store.commit('setQuoteReply', quot_reply);
                },
                getUnsentMessages(id){
                    var failedList = localStorage.getItem('failed_messages');
                    if(failedList){
                        let data = JSON.parse(failedList)
                        this.failedMessagesList = data.filter(ob => ob.user_id == this.$store.state.user.id)
                        const failed = data.filter(ob => ob.record_id == id && ob.user_id == this.$store.state.user.id)
                        if(failed.length){
                            this.queuedMessages = failed
                        }
                        
                        
                    }
                },
                updateBadge(item){
                    if(this.$store.state.boardBadge && this.$store.state.boardBadge[item.id]){
                        emitter.emit('notifyUpdate', 'badge_update_first');
                    }
                },
                openBoard(item, second_atr, message){
                    console.log('open')
                        this.messageLoader = true
                        this.openedBoardId = item.id           
                        this.pageIndex = 1;
                        this.currentLen = 0;
                        this.unreadMessages = {
                            active: false,
                            id: null,
                            count: 0
                        }
                        if(this.$store.state.boardBadge){
                            if(this.$store.state.boardBadge[item.id]){
                                const count = this.$store.state.boardBadge[item.id]                    
                                const index = Math.ceil(count / 30)
                                this.pageIndex = index
                                const self = item.board_to_users.filter( ob => ob.user_id == this.$store.state.user.id)
                                if(self.length){
                                    const data = {
                                        active: true,
                                        id: self[0].last_message,
                                        count: this.$store.state.boardBadge[item.id]
                                    }
                                    this.unreadMessages = data
                                }  
                            }else{
                                const data = {
                                    active: false,
                                    id: null,
                                    count: 0
                                }
                                this.unreadMessages = data
                            }
                                            
                        }
                        this.infiniteLock = false
                        this.queuedMessages = []
                        // this.$store.commit('setActiveBoard', item);
                        this.getUnsentMessages(item.id)
                        this.resetReplyQuot()
                        if(second_atr !== 'search'){
                            if(this.$store.state.urlMessageId){
                                const atr = {
                                    id: this.$store.state.urlMessageId,
                                    record_id: item.id
                                }
                                this.jumpToMessage(atr)
                            }else{
                                
                                this.getMessageList('first_load');

                            }
                            
                        }
                        
                        
                        if(this.$store.state.mobile){
                            this.zIndexTable = 20
                        }
                        this.trayComponentKey ++;
                        this.searchWindowKey ++;
                        this.routeWatchLock = true
                        this.$router.push(`/board/${item.id}`);
                        setTimeout(() => {
                            this.routeWatchLock = false
                        }, 100);

                    const mentionable = item.board_to_users.filter(ob => ob.user_id !== this.$store.state.user.id && ob.user)
                    this.$store.commit('setMentionAbleUsers',mentionable)
                    this.$store.commit('setMyBoard',this.myBoard)
                    this.$store.commit('setSignAbleUsers',item.board_to_users)
                    this.$store.commit('setActiveBoard', item);
                    this.$store.commit('setBoardList',this.allBoardList)
                    
                    
                    
                },
                boardTitle(item){            
                    if(item.private_flag == 1 && item.board_to_users.length == 2){
                        var coresspondId = item.board_to_users.filter(obj => obj.user_id !== this.$store.state.user.id);
                        if(coresspondId && coresspondId.length && coresspondId[0].user){
                            return coresspondId[0].user.name;
                        }else{
                            return this.$t('unAvailableUserName')
                        }
                    }else{
                        return item.title;
                    }           
                }, 
                resetPageIndex(){
                    this.pageIndex = 1;
                    this.pageIndexLimiter = false
                },
                getMessageList(source, queue){
                    if(this.openedBoard){
                        const recordId = this.openedBoard.id        
                        axios.post('/get_messages', {
                            record_id: recordId,
                            page_index: this.pageIndex,
                        }).then(
                        response => {  
                            if(queue){
                                this.removeError(queue.id)
                                // this.removeQueue(queue.id)
                            
                                let box = document.getElementById('queueMessage_' + queue.u_id);                       
                                if(box){                            
                                    box.style.display = 'none'
                                }
                                this.getUnsentMessages(this.openedBoard.id)
                                const data = {
                                    active: false,
                                    id: null,
                                    count: 0
                                }
                                this.unreadMessages = data
                            }
                            this.messageListType = 'normal' 
                            this.messageList = response.data;                    
                            if(source == 'infiniteLoader'){                        
                                setTimeout(() => { 
                                    this.pageIndexLimiter = false
                                    this.microLoader = false
                                },500)
                            }
                            this.infiniteLock = this.currentLen == this.messageList.length
                            if(source == 'first_load'){                    
                                this.updateBadge(this.openedBoard)
                            }
                            
                            this.messageLoader = false
                    
                            
                            
                            
                        });
                    }
                },
                unreadLineTrigger(){
                    if(this.openedBoard){
                        const board = this.allBoardList.filter(ob => ob.id == this.openedBoard.id)
                        if(board.length && this.$store.state.boardBadge[this.openedBoard.id]){
                            const self = board[0].board_to_users.filter( ob => ob.user_id == this.$store.state.user.id)
                            if(self.length){
                                const data = {
                                    active: true,
                                    id: self[0].last_message,
                                    count: this.$store.state.boardBadge[this.openedBoard.id]
                                }
                                this.unreadMessages = data
                            }   
                        }
                        
                    }
                },
                selectDefaultGroup(flag){
                    const name = flag == 0 ? 'すべて' : flag == -1 ? 'グループ' : '個別'
                    this.activeGroupName = name
                    this.activeGroupButton = flag
                },
                selectCustomGroup(group){
                    this.activeGroupName = group.name
                    this.activeGroupButton = group.id 
                },
                getGroup(which){
                    axios.post('/get_group_api').then(response => {
                        this.userGroupList = response.data;
                        var defaultGroup = this.userGroupList.filter(obj => obj.active_flag == 1)[0];                 
                        if(defaultGroup){  
                            if(defaultGroup.name == 'group_default'){
                                this.activeGroupName = 'グループ';
                                this.activeGroupButton = -1;
                            }else if(defaultGroup.name == 'private_default'){
                                this.activeGroupName = '個別';
                                this.activeGroupButton = -2;
                            }else{
                                this.activeGroupName = defaultGroup.name;
                                this.activeGroupButton = defaultGroup.id;    
                            }                  
                            
    
                        }
                        if(which == 9){
                            this.selectDefaultGroup(0)
                        }
                    });
                },
                boardCreateClose(){
                    this.newBoardWindow = false
                },
                boardCreate(flag){
                    

                    this.newBoardWindow = true

                    
                    // this.editIndex = 0;
                    // this.privateFlag = flag;
                    // this.createEditWindow = true;
                    
                },
                closeCreateModal(){
                    this.createEditWindow = false;
                    
                },
                boardEdit(item){
                    this.activeEditBoard = item
                    this.editIndex = 1
                    this.createEditWindow = true
    
                },
                boardEditFinished(id){
                    console.log('777')
                    this.getBoardList('', id)
                    this.activeEditBoard = null
                    this.editIndex = 0
                    this.createEditWindow = false
                    if(this.openedBoard){
                        this.getMessageList()
                    }
                },
                getBoardList(atr, second_atr){
                   
                    if (!this.mainLoader) {
                        if(this.$store.state.boardList && this.$store.state.boardList.length && atr == 'mounted'){
                            this.allBoardList = this.$store.state.boardList
                            this.$store.commit('setBoardList', [])
                            
                        }
                        this.mainLoader = true
                        try {                  
                            axios.post('/chat_list').then(
                                response => {
                                    this.allBoardList = response.data;
                                    if(second_atr){
                                        const target = this.allBoardList.filter(ob => ob.id == second_atr)
                                        if(target.length){
                                            this.openTargetBoard(target[0])
                                        }
                                          
                                    }
                                    if(atr == 'mounted'){
                                        if (this.$route.params.hasOwnProperty('chatId')) {
                                            const targetBoard = this.allBoardList.filter(ob => ob.id == this.$route.params.chatId)
                                            if(targetBoard.length){
                                                this.openTargetBoard(targetBoard[0], false)                                                
                                            }else{                                                    
                                                emitter.emit('setToast', {
                                                    active: true,  
                                                    type: 'info', 
                                                    content: this.$t('canNotAccessChat'),                                                        
                                                    closeButton: true, 
                                                    autoClose: true,
                                                    answers: ['OK']

                                                })  
                                            }  

                                        }

                                    }
                                    setTimeout(() => {
                                        this.$store.commit('setSkeleton', this.$store.state.skeleton + 1)    
                                    }, 0);
                                    

                            }).catch(function (error) {
                                if (error.response) this.errorToast(this.$t(error.response.data.message))
                                else if (error.request) this.errorToast(this.$t('commonError'))
                                else this.errorToast(this.$t('commonError'))                       
                            }.bind(this));                      
                        
                        } catch (e) {
                            this.mainLoader = false
                        } finally {
                            this.mainLoader = false
                        }
                    }
                },
                getTaskNotify(){
                    axios.post('/task_notify_api').then( response => this.$store.commit('setTaskBadge', response.data));
                },
                
            }
        }
    </script>
    
    