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
        <Transition :name="infoData ? 'slidePop' : 'modalFade'">
            <Dialog 
                v-if="confirmData || notifyData || infoData" 
                :confirm="confirmData"  
                :notify="notifyData"
                :info="infoData"
                :options="confirmOptions"
                @close="confirmData = null, notifyData = null"
                @handle="val => userResponse = val"
            ></Dialog>
        </Transition>
        <OverRide/>
        <Transition name="footerPop">
            <PWAPrompt 
                :timeToShow="1" 
                :isShown="promptShown && isIOS && !isPWA"
                copy-title="ホーム画面に追加"
                copy-description="ホーム画面に追加するとメンションなどのプッシュ追徴を受け取ることができます。"
                copy-subtitle="https://clap-glowd.com"
                copy-share-step="下の「シェア」ボタンを押してください。"
                copy-add-to-home-screen-step="「ホーム画面に追加」ボタンを押してください。"
                appIconPath="/icon-152x152.png"
                @close="savePWAStatus"
            />
        </Transition>
        

    </div>

</template>
<script setup>
import moment from 'moment';
import SideMenu from './Global/SideMenu.vue';
import Footer from './Header/Footer.vue';
import * as PusherPushNotifications from "@pusher/push-notifications-web";
import { computed, nextTick, onBeforeMount, onMounted, onUnmounted, provide, ref, watch } from 'vue';
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
import { useSkeleton } from '@/store/skeleton'
import { timestamp, useTitle } from '@vueuse/core'
import axios from 'axios';
import { instance as socket } from '@/utils/broadcaster'
import { endPlay } from '@/utils/tts';
import { PWAPrompt } from 'vue-ios-pwa-prompt'
import { DateTime } from 'luxon';
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
    const skeleton = useSkeleton()
    const switchLoader = ref(false)
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
    onMounted(async() => {    
        addEventListener()
        if(props.auth_user && props.auth_user.id){                  
            beamsInit()
        }
        badge.getBoardBadge('mounted');
        
        if(!auth.isPartner){
            badge.getNoticeBadge()
            badge.getPostBadge()
            // badge.getProjectBadge()
            badge.getRemindBadge()
        }
        if (isIOS.value) {
            savePWAStatus()
        }
        // if(auth.user?.position_id == 6){
            badge.getMembersGoalsBadge()
        // }
        if(auth.user?.position_id < 6){
            badge.getManagersGoalsBadge()
        }
        badge.getSalaryIssueBadge()
        badge.getAssetBadge()
        badge.getTaskCommentBadge()
        
    })
    const postHandler = () => {
        if(!auth.isPartner){
            badge.getPostBadge()
        }
    }
    const activeAccountHandler = (e) => {
        if(e.to !== auth.activeUser.id){                
            setAlert()
        }
    }
    const docTitle = computed(() => {       
        const name = route.meta && route.meta.title ? route.meta.title : 'CLAP'
        const total = badge.sumOfAll
        const badgeCount = total && total > 0 ?  `【${total}】` : ''
        const space = badgeCount ? ' ' : ''
        return badgeCount + space + name   
    })
    const boardBadgeHandler = (data) => {
        const related = data && data.length? data[0] : []
        if(related.includes(auth.id) || related.includes(auth.activeUser.id)){
            badge.getBoardBadge()
        }
        
    }
    useTitle(docTitle)
    const addEventListener = () => {
        window.addEventListener('click', onClick);
        window.addEventListener('touchstart', onClick);
        window.addEventListener('resize', handleResize);
        window.addEventListener("focus", handleFocus, false);
        window.addEventListener("blur", handleBlur, false);
        document.addEventListener('visibilitychange', handleVisibilityChange)
        socket.on("post:badge", postHandler)
        socket.on(`switch:${auth.id}`, activeAccountHandler)
        socket.on("refresh:badge", boardBadgeHandler)
        socket.on("refresh:task_comment", taskCommentBadgeHandler)
    }
    const removeEventListener = () => {
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('focus', handleFocus, false)
        window.removeEventListener('blur', handleBlur, false)
        window.removeEventListener('click', onClick);
        window.removeEventListener('touchstart', onClick);
        document.removeEventListener('visibilitychange', handleVisibilityChange)
        socket.off("post:badge", postHandler);
        socket.off(`switch:${auth.id}`, activeAccountHandler);
        socket.off("refresh:task_comment", taskCommentBadgeHandler)
    }
    const taskCommentBadgeHandler = (data) => {
        console.log('taskCommentBadgeHandler', data)
        if(data && data.length && data[0].members){
            console.log('iyiyiyiiy', data)
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
    watch(() => [route.fullPath], () => {
            resetInstantUser()
            endPlay()
        }
    )
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
    const handleVisibilityChange = () => {
        if (document.visibilityState === 'visible') {
            handleFocus()
        } else {
            console.log('Window lost focus')
        }
    }
    const handleFocus = () => {
        console.log('focus getting called')
        checkActivity()
        focused.setFocused(true)
    }
    const handleBlur = () => {
        focused.setFocused(false)
    }
    const beamsInit = async () =>{
        if (window.Notification.permission === 'granted') return
        const beamsClient = new PusherPushNotifications.Client({
            instanceId: import.meta.env.VITE_PUSHER_INSTANCE_ID,
        });
        const beamsTokenProvider = await beamsToken()
        beamsUser(beamsClient, beamsTokenProvider)
    }
    const beamsToken = async() => {
        return new PusherPushNotifications.TokenProvider({
            url: "/pusher/beams-auth",
        });
    }
    const beamsUser = (beamsClient, beamsTokenProvider) => {
        beamsClient.getUserId().then((userId) => {
            if (userId !== null && userId !== props.auth_user.id.toString()) {
                return beamsClient.stop();
            }else{
                beamsClient.start()
                .then(() => console.log('Successfully registered and subscribed!'))
                .then(() => beamsClient.setUserId(props.auth_user.id.toString(), beamsTokenProvider))
                .catch(console.error);
            }
        }).catch(console.error);
        
    }
    const checkActivity = async() => {            
        const before = localStorage.getItem('notification_check')
        if(!before || moment().diff(moment(before), 'minutes') > 1){
            authCheck();
            await badge.getBoardBadge();
            if(mainRef.value.getBoardList){
                mainRef.value.getBoardList()
                mainRef.value.unreadLineTrigger()
            }            
            const time = moment().format('YYYY-MM-DD HH:mm:ss')
            localStorage.setItem('notification_check', time)
        }
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
    const refreshMessage = () => {
        if(mainRef.value.getMessageList){
            mainRef.value.getMessageList()
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
            await axios.post('/auth_check', {id: auth.id})
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
            answer = await confirm(errorMessage, options);
        }    
        if(answer){
            window.location.reload(true);
        }             
    }
    


    const confirmData = ref(null);
    const notifyData = ref(null)
    const userResponse = ref(null);
    const infoData = ref(null)
    const confirmOptions = ref(null)
    const resetPopup = () => {
        confirmData.value = null
        notifyData.value = null
        userResponse.value = null
        infoData.value = null
        confirmOptions.value = null
    }
    const confirm = async (question, options) => {
        resetPopup()
        if(options && options.answers && options.answers.length){
            confirmOptions.value = options
        }else{
            confirmOptions.value = {answers : [
                {value: true, label: 'OK'},
                {value: false, label: 'キャンセル'}
            ]}
        }
        userResponse.value = {value: false, label: ''}
        notifyData.value = null
        confirmData.value = question;
        
        await new Promise((resolve) => {
            const unsubscribe = watch(() => userResponse.value, (value) => {
                if (value !== null) {
                    unsubscribe();
                    resolve(value);
                }
            });
        });       
        return userResponse.value;        
    };
    const notify = (message) => {
        resetPopup()
        confirmOptions.value = {answers : [
            {value: true, label: 'OK'}
        ]}
        notifyData.value = message
    }
    const info = (message) => {
        resetPopup()
        infoData.value = null
        infoData.value = message
        setTimeout(() => {
            infoData.value = null
        }, 4000);
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
        const answer = await confirm('アクティブアカウントが変更されています。ページを更新してください。', options)
        if(answer.value){
            window.location.reload(true);
        }else{
            confused.value = true
        }
    }

    provide('dialog', {
        confirm: (question, options) => confirm(question, options),
        notify: (message) => notify(message),
        info: (message) => info(message)
    });
    provide('pushInstantUser', pushInstantUser)
    provide('refreshRemind', refreshRemind)
    provide('refreshMessage', refreshMessage)
    provide('resetInstantUser', resetInstantUser)
    provide('beamsInit', beamsInit)
</script>

