import { createStore } from 'vuex'
const store = createStore({
    state () {
        return {
            user: null,
            menu: {
                name: null,
                id: null,
            },
            boardBadge: [],
            taskBadge: [],
            quot_reply: {
                active: false,
                message: null,
                which: null,
                text: null,
                file: false,
                height: 100,
                width: 100
            },
            activeBoard: null,
            focused: true,
            keyword: '',
            fromFilesToBoard: {
                active: false,
                list: [],
                source_board_id: null,
                drag: false
            },
            fromBoardToFiles: {
                active: false,
                list: [],
                drag: false
            },
            taskModal: {
                active: false,
                record: null,
                taskColor: null
            },
            filePreview: {
                active: false,
                files: [],
                source: null,
                source_board_id: null,
                index: 0,
                message: null
            },
            urlBoardId: null,
            urlMessageId: null,
            urlTaskId: null,
            urlTaskEditFlag: false,
            instantUser: {
                id: null,
                cX: null,
                cY: null
            },
            tempUniqueId: [],
            baseLocation: window.location.origin,
            boardList: [],
            myBoard: null,
            mentionAbleUsers: [],
            scrollUkey: -1,
            fileShareTo: {
                active: false,
                target: null,
                message: null,
                files: []
            },
            sharingMemo: {
                active: false,
                memo: null,
                drag: false,
                window: false
            },
            remember:{
                favorite_tray: 1,
                my_task_priority: 1,
                file_sort_by: 0,
                file_sort_desc: 1,
                task_sort_desc: 1
            },
            taskFeedBack:{
                active: false,
                data: null
            },
            mobile: window.innerWidth < 959,
            dark: false,
            local: 'ja',
            qoutWindowActive: false,
            sideMenuView: false,
            skeleton: 0,
            badge: 0,
            messageUsers:{
                active: false,
                userList: [],
                title: ''
            },
            activeInput: '',
            calendarOffset: {
                left: 0,
                top: 0
            },
            tempRecord: null,
            draggingCalendar: null,
            aiData: {
                user_text: '',
                edited_text: '',
                view: false
            },
            info: {
                view: false,
                text: '',
                icon: 0,
                channel: ''
            },
            postBadge: [0,0,0],
            sharingData: null,
            noticeBadge: 0,
            keyboardOffset: 0
        }
    },
    mutations: {
        setKeyboardOffset(state, data){state.keyboardOffset = data},
        setNoticeBadge(state, data){state.noticeBadge = data},
        setSharingData(state, data){state.sharingData = data},
        setFooterView(state, data){state.user.footer_view = data},
        setPostBadge(state, data){state.postBadge = data},
        setInfo(state, data){state.info = data},
        setAiData(state, data){state.aiData = data},
        setDraggingCalendar(state, data){state.draggingCalendar = data},
        setTempRecord(state, data){state.tempRecord = data},
        setCalendarOffset(state, data){state.calendarOffset = data},
        setActiveInput(state, data){state.activeInput = data},
        setBadge(state, data){state.badge = data},
        setSkeleton(state, data){ state.skeleton = data },
        setSideMenuView(state, data){ state.sideMenuView = data },
        setQoutWindowActive(state, data){ state.qoutWindowActive = data },
        setDark(state, data){ state.dark = data },
        setTaskFeedback(state, data){ state.taskFeedBack = data },
        setUrlTaskEditFlag(state, data){ state.urlTaskEditFlag = data },
        setMobile(state, data){ state.mobile = data },
        setRemember(state, data){ state.remember = data },
        setSharingMemo(state, data){ state.sharingMemo = data },
        setFileShareTo(state, data){ state.fileShareTo = data },
        setScrollUkey(state, data){ state.scrollUkey = data },
        setMentionAbleUsers(state, data){ state.mentionAbleUsers = data },
        setSignAbleUsers(state, data){ state.signAbleUsers = data },
        setMyBoard(state, data){ state.myBoard = data },
        setBoardList(state, data){ state.boardList = data },
        setFromBoardToFiles(state, data){ state.fromBoardToFiles = data },
        setTempUniqueId(state, data){ state.tempUniqueId = data },
        setInstantUser(state, data){ state.instantUser = data },
        setUrlBoardId(state, data){ state.urlBoardId = data },
        setUrlMessageId(state, data){ state.urlMessageId = data },
        setUrlTaskId(state, data){ state.urlTaskId = data },
        setFilePreview(state, data){ state.filePreview = data },
        setFromFilesToBoard(state, data){ state.fromFilesToBoard = data },
        setKeyword(state, data){ state.keyword = data },
        setFocused(state, data){ state.focused = data },
        setActiveBoard(state, data){ state.activeBoard = data },
        setTaskBadge(state, data){ state.taskBadge = data },
        setBoardBadge(state, data){ state.boardBadge = data },
        setQuoteReply(state, data){ state.quot_reply = data }, 
        setUser (state, data) { state.user = data },
        setMenu(state, data) { state.menu = data },
        setLocale(state, data) { state.local = data },
        setCalendarView(state, data) {state.calendarView = data},
        setTaskModal(state, data) {state.taskModal = data},
        setMessageUsers(state, data){ state.messageUsers = data },
    }
})
export default store;