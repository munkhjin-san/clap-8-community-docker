<template>
    <div style="width: 100%;height: 100%;display: flex;flex-direction: column;">
        <div class="confused-alert" v-if="confused">ページを更新してください</div>
        <Transition name="modalFade">
            <div class="overlay" style="z-index:100" v-if="switchLoader"></div>
        </Transition>
        <InstantProfile :key="instantUser.cY + instantUser.cX" :data="instantUser" v-if="instantUser.id || instantUser.name" @resetInstantUser="resetInstantUser"/>  
        <div style="width: 100%;height:calc(100% - 45px);display: flex; flex:1">
            <Transition name="modalFade">
                <div @click="sideMenuView.setSideMenuView(false)" v-if="sideMenuView.active" class="overlay mobile" style="z-index: 26;"></div>
            </Transition>

            <SideMenu  
                :auth_user="auth_user" 
                :session="session" 
                :setActiveUser="setActiveUser"
                :switchLoader="switchLoader"
            />
            
            <router-view v-slot="{ Component }">
                <KeepAlive :include="['MainContainer']">
                    <component
                        :is="Component"
                        :key="keyGen" 
                        :initial_date="initial_date"
                        ref="mainRef"
                    ></component>
                </KeepAlive>
            </router-view>
        </div>
        <Transition name="footerPop">
            <Footer v-if="footerView"></Footer>
        </Transition>
        <Transition :name="toastData ? 'slidePop' : 'modalFade'">
            <Dialog 
                v-if="askData || pingData || toastData" 
                :confirm="askData"  
                :notify="pingData"
                :info="toastData"
                :options="respondOptions"
                :input="inputOptions"
                @close="resetDialog"
                @handle="val => decision = val"
                @submit="({ input, answer}) => {
                    inputResult = input;
                    if (answer) decision = answer;
                }"
            ></Dialog>
        </Transition>
        <OverRide/>
        <Transition name="footerPop">
            <PWAPrompt 
                :isShown="promptShown && isIOS && !isPWA"
                copy-title="ホーム画面に追加"
                copy-description="ホーム画面に追加するとメンションなどのプッシュ追徴を受け取ることができます。"
                copy-subtitle="https://clap-glowd.com"
                copy-share-step="三点リーダーメニュー「共有」ボタンを押してください。"
                copy-add-to-home-screen-step="「ホーム画面に追加」ボタンを押してください。"
                appIconPath="/android-chrome-192x192.png"
                @close="savePWAStatus"
            />
        </Transition>
        

    </div>

</template>
<script setup>
import SideMenu from './Global/SideMenu.vue';
import Footer from './Header/Footer.vue';
import { computed, onBeforeMount, onMounted, onUnmounted, provide, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Dialog from './Global/Dialog.vue';
import OverRide from './Header/OverRide.vue'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useBadgeStore } from '@/store/badge'
import { useFocused } from '@/store/focused';
import InstantProfile from './Board/InstantProfile.vue';
import { useSideMenuView } from '@/store/sideMenuView';
import { useTitle } from '@vueuse/core'
import axios from 'axios';
import { instance as socket, isSocketReady } from '@/utils/broadcaster'
import { PWAPrompt } from 'vue-ios-pwa-prompt'
import { DateTime } from 'luxon';
import { useDialog } from '@/composables/dialog';
import { initPush } from '@/utils/push';
    const props = defineProps(['session', 'auth_user', 'initial_date'])
    const route = useRoute()
    const router = useRouter()
    const badge = useBadgeStore()
    const mainRef = ref(null)
    const auth = useAuthUserStore()
    const menu = useMenuStore()
    const responsive = useResponsive()
    const focused = useFocused()
    const sideMenuView = useSideMenuView()
    const switchLoader = ref(false)

    const { askData, pingData, toastData, respondOptions, decision, resetDialog, ask, inputOptions, inputResult } = useDialog() 
    const instantUser = ref({
        id: null,
        name: null,
        cX: 0,
        cY: 0
    })
    const confused = ref(false)
    // const socket = ref()
    onBeforeMount(() => {
        auth.setUser(props.auth_user)
    })

    onUnmounted(() => {
        removeEventListener()
    })
    const BOARD_BADGE_COOLDOWN_MS = 2000
    let badgeRefreshTimer = null
    let badgeRefreshInFlight = false
    let badgeRefreshPending = false
    let lastBadgeRefreshAt = 0
    const queueBoardBadgeRefresh = () => {
        const now = Date.now()
        if(badgeRefreshInFlight){
            badgeRefreshPending = true
            return
        }
        if((now - lastBadgeRefreshAt) < BOARD_BADGE_COOLDOWN_MS){
            if(!badgeRefreshTimer){
                badgeRefreshTimer = setTimeout(() => {
                    badgeRefreshTimer = null
                    queueBoardBadgeRefresh()
                }, BOARD_BADGE_COOLDOWN_MS - (now - lastBadgeRefreshAt))
            }
            return
        }
        badgeRefreshInFlight = true
        badge.getBoardBadge(true, 'queueBoardBadgeRefresh').finally(() => {
            lastBadgeRefreshAt = Date.now()
            badgeRefreshInFlight = false
            if(badgeRefreshPending){
                badgeRefreshPending = false
                queueBoardBadgeRefresh()
            }
        })
    }

    onMounted(async() => {    
        addEventListener()
        if(props.auth_user && props.auth_user.id){   

            initPush()
        }
        loadBadges().catch(err => {
            console.error('[badges] failed to load', err)
        })
        if(route.name === 'board'){
            pushPwaBackGuardState()
        }
        if (isIOS.value) {
            savePWAStatus()
        } 
    })
    async function loadBadges() {
        const jobs = []

        jobs.push(badge.getBoardBadge(true, 'initialLoad'))

        if(!auth.isPartner){
            // jobs.push(badge.getNoticeBadge())
            jobs.push(badge.getRemindBadge())
        }
        jobs.push(badge.getbadgeSummary())
        // if(auth.user?.position_id < 6){
        //     jobs.push(badge.getManagersGoalsBadge())
        // }

        // jobs.push(
        //     badge.getMembersGoalsBadge(),
        //     badge.getSalaryIssueBadge(),
        //     badge.getAssetBadge(),
        //     badge.getTaskCommentBadge(),
        //     badge.getGoalIssueCommentBadge(),
        //     badge.getContactCommentBadge(),
        //     badge.getTodayReadableBadge()
        // )

        // if(auth.user?.position_id <= 6 || [610,608].includes(auth.activeUser.id)){
        //     jobs.push(badge.getFinanceCommentBadge())
        // }

        await Promise.all(jobs)
    }

    const postHandler = () => {
        if(!auth.isPartner){
            badge.getbadgeSummary()
        }
    }
    const activeAccountHandler = (e) => {
        if(e.to !== auth.activeUser.id){                
            setAlert()
        }
    }
    const docTitle = computed(() => {       
        const name = route.meta && route.meta.title ? route.meta.title : 'GLOWD'
        const total = badge.sumOfAll
        const badgeCount = total && total > 0 ?  `【${total}】` : ''
        const space = badgeCount ? ' ' : ''
        return badgeCount + space + name   
    })
    const boardBadgeHandler = (data) => {
        const related = data && data.length? data[0] : []
        if(related.includes(auth.id) || related.includes(auth.activeUser.id)){
            queueBoardBadgeRefresh()
        }
        
    }
    useTitle(docTitle)
    const addEventListener = () => {
        window.addEventListener('click', onClick);
        window.addEventListener('touchstart', onClick);
        window.addEventListener('resize', handleResize);
        // window.addEventListener("blur", handleBlur, true);
        document.addEventListener('visibilitychange', handleVisibilityChange)
        window.addEventListener('popstate', handlePwaPopState)
        socket.on("post:badge", postHandler)
        socket.on(`switch:${auth.id}`, activeAccountHandler)
        socket.on("refresh:badge", boardBadgeHandler)
        socket.on("refresh:task_comment", taskCommentBadgeHandler)
    }
    const removeEventListener = () => {
        window.removeEventListener('resize', handleResize);
        // window.removeEventListener('blur', handleBlur, true)
        window.removeEventListener('click', onClick);
        window.removeEventListener('touchstart', onClick);
        document.removeEventListener('visibilitychange', handleVisibilityChange)
        window.removeEventListener('popstate', handlePwaPopState)
        socket.off("post:badge", postHandler);
        socket.off(`switch:${auth.id}`, activeAccountHandler);
        socket.off("refresh:task_comment", taskCommentBadgeHandler)
    }
    const taskCommentBadgeHandler = (data) => {
        if(data && data.length && data[0].members){
            const related = data[0].members
            if(related.includes(auth.id) || related.includes(auth.activeUser.id)){
                badge.getTaskCommentBadge()
            }
        }
    }
    const footerView = computed(() =>{
        const block_list = ['account-settings', 'personal-info-settings']
        const find = block_list.filter(ob => ob === route.name)
        if(find && find.length) return false            
        return responsive.mobile && auth.user && auth.user.footer_view
    })
    const keyGen = computed(() => {
        const parts = route.fullPath.split('/');
        if (parts.length > 1) {
            return parts[1] + auth.activeUser.id;
        } else {
            return route.fullPath + auth.activeUser.id
        }
    })
    const savePWAStatus = () => {
        const oneMonthLater = DateTime.now().plus({ month: 1})
        const data = {
            pwa: isPWA.value || false,
            timestamp: oneMonthLater,
            shown: true
        }

        localStorage.setItem("pwa_status", JSON.stringify(data))
    }
    const isPWA = computed(() => {
        return window.matchMedia('(display-mode: standalone)').matches || false
    })
    const isIOS = computed(() => {
        const iosPwaPrompt = JSON.parse(localStorage.getItem('iosPwaPrompt') || '{}')
        return iosPwaPrompt.isValidOS
    })
    const promptShown = computed(() => {
        const today = DateTime.now().toISO()
        const promptData = JSON.parse(localStorage.getItem("pwa_status") || "{}")
        if (promptData) {
            return !promptData.shown || (today > promptData.timestamp && !promptData.pwa)
        }
        return true
    })
    const isStandaloneMode = () => {
        return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true
    }
    const pushPwaBackGuardState = () => {
        if(!isStandaloneMode()) return
        const state = window.history.state || {}
        if(state.pwaBackGuard) return
        const nextState = {
            ...state,
            position: typeof state.position === 'number' ? state.position + 1 : state.position,
            pwaBackGuard: true
        }
        window.history.pushState(nextState, '', window.location.href)
    }
    const handlePwaPopState = () => {
        if(!isStandaloneMode()) return
        const state = window.history.state || {}
        const hasBack = state.back !== null && state.back !== undefined
        if(route.name === 'board' && !hasBack){
            pushPwaBackGuardState()
        }
    }
    watch(() => [route.fullPath], () => {
            resetInstantUser()
        }
    )
    watch(() => route.name, (name) => {
        if(name === 'board'){
            pushPwaBackGuardState()
        }
    })
    const setActiveUser = async(id) => {
        if(id == auth.activeUser.id){
            if(id == auth.id){
                router.push({path: `/user/${auth.activeUser.id}`});
            }
            return            
        }
        switchLoader.value = true
        if(route.name == 'room'){
            router.push({name: 'board'})
        }

        await auth.setActiveUser(id)
        window.location.reload(true)
        // skeleton.setSkeleton(0)
        // badge.getRemindBadge()
        // nextTick(() => {
        //     setTimeout(() => {
        //         switchLoader.value = false
        //     }, 300);
            
        // })
        

    }
    const setFocusedState = (v) => {
        focused.setFocused(v)
        console.debug("focused =", v, {
            visibility: document.visibilityState,
            hasFocus: document.hasFocus?.(),
        });
    }
    const handleVisibilityChange = () => {
        if (document.visibilityState === "visible") {
            // When coming back, treat as focused and run activity check
            handleFocus();
        } else {
            setFocusedState(false);
        }
    };
    const handleFocus = () => {
        checkActivity()
        setFocusedState(true);
    }
    // const handleBlur = () => {
    //     // Don't instantly trust blur on mobile.
    //     // If document is still visible and hasFocus() is true, ignore it.
    //     queueMicrotask(() => {
    //         const visible = document.visibilityState === "visible";
    //         const hasFocus = document.hasFocus ? document.hasFocus() : true;

    //         if (!visible || !hasFocus) setFocusedState(false);
    //         else console.log("blur ignored (still visible + hasFocus)");
    //     });
    // };
    const checkActivity = async() => {            
        const before = localStorage.getItem('notification_check')
        // if(!before || DateTime.now().diff(DateTime.fromSQL(before), 'minutes').minutes > 1){
            const must_sync = await authCheck();
            console.debug('must_sync', must_sync)
            if(!isSocketReady.value || must_sync === true){            
                await badge.getBoardBadge(false, 'checkActivity');
                const hasNewMessages = badge.totalBoardBadge(auth.activeUser.id)      
                if(mainRef.value.refreshBoardList){
                    if(hasNewMessages){                        
                        mainRef.value.refreshBoardList()
                        mainRef.value.unreadLineTrigger()
                    }                   
                }   
            }         
            const time = DateTime.now().toFormat('yyyy-MM-dd HH:mm:ss')
            localStorage.setItem('notification_check', time)
        // }
    }
    const handleResize = () => {
        const w = window.innerWidth;
        if(w > 959){
            if(responsive.mobile){
                responsive.setMobile(false)
            }
        }else{
            if(!responsive.mobile){
                responsive.setMobile(true)
            }
        }
    }
    const refreshMessage = (data) => {
        if(mainRef.value.getMessageList){
            mainRef.value.refreshMessages(data)
        }
    }
    const refreshRemind = (dataType) => {
        if(mainRef.value.refreshData) {
            mainRef.value.refreshData(dataType)
        }
    }
    const onClick = (event) => {
        const target = event.target
        if(menu){
            const cont = document.getElementById(menu.parent ? menu.parent : menu.name);  
            if(cont && !cont.contains(target)){
                menu.close()
            } 
        }        
    }
    const authCheck = async() => {
        const options = {
            answers: [{label: 'OK', value: true}]
        }
        let answer = ''
        try {
            const { must_sync } = await axios.post('/auth_check', {id: auth.id}).then(res => res.data)
            return must_sync
        } catch (error) {
            const { response } = error;
            let errorMessage = '';
            if (response) {
                errorMessage =
                    response.status === 419
                        ? 'ユーザー アカウント認証に失敗しました。ブラウザを更新してください'
                        : response.data.message;
            } else if (error.request) {
                errorMessage = 'ネットワークエラーが発生しました。ブラウザを更新してください';
            }  
            answer = await ask(errorMessage, options).value;
        }    
        if(answer){
            window.location.reload(true);
        }             
    }
    const resetInstantUser = () => {
        const data = {
            id: null,
            name: null,
            cX: 0,
            cY: 0
        }
        instantUser.value = data    
    }
    const pushInstantUser = (e, id, name) => {
        // if(id == auth.id) return
        const cX = e.clientX;
        const cY = e.clientY;  
        const data = {
            id: id,
            name: name,
            cX: cX,
            cY: cY,
        }
        instantUser.value = data               
    }
    const setAlert = async() => {
        const options = {
            answers: [{label: 'OK', value: true}, {label: 'あとで', value: false}]
        }
        const answer = await ask('アクティブアカウントが変更されています。ページを更新してください。', options)
        if(answer.value){
            window.location.reload(true);
        }else{
            confused.value = true
        }
    }
    provide('pushInstantUser', pushInstantUser)
    provide('refreshRemind', refreshRemind)
    provide('refreshMessage', refreshMessage)
    provide('resetInstantUser', resetInstantUser)
</script>

