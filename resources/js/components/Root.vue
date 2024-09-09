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
                <KeepAlive :include="['MembersRoot']">
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
    </div>

</template>
<script setup>
import moment from 'moment';
import SideMenu from './Global/SideMenu.vue';
import Footer from './Header/Footer.vue';
import * as PusherPushNotifications from "@pusher/push-notifications-web";
import Pusher from 'pusher-js';
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
import { useTitle } from '@vueuse/core'
import { io } from "socket.io-client";
import axios from 'axios';
import { instance as socket } from '@/utils/broadcaster'
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
        // const socket = instance
        // console.log(socket)
        // socket.value = io(import.meta.env.VITE_SOCKET_URL, {
        //     auth: {
        //         token: import.meta.env.VITE_SOCKET_TOKEN
        //     },
        //     withCredentials: true,
        //     transports: ["websocket"],
        //     reconnectionAttempts: 5 
        // })
        // socket.value.on("connect", () => {
        //     console.log('Connected to socket Successfully')
        // });
        // socket.on("message", (e) => {
        //     console.log('recieved', e)
        //     console.log('rrrrr', auth.activeUser.id, focused.active)
        //     // if(e && e.active_user_changed && e.active_user_changed.owner == auth.id && e.active_user_changed.target !== auth.activeUser.id){
                
        //     //     setAlert()
        //     // }                
        //     if(mainRef.value.onPusher){
        //         const event = {message:e}
        //         // mainRef.value.onPusher(event)
        //     }
        //     if(auth.user && e.board_id && e.sender !== auth.id && e.board_members && e.board_members.length && (e.board_members.includes(auth.activeUser.id) || e.board_members.includes(auth.id))){                
        //         badge.getBoardBadge()
        //     }
        //     // if(e.new_post_from && e.new_post_from !== auth.id){
        //     //     if(!auth.isPartner){
        //     //         badge.getPostBadge()
        //     //     }
        //     // }   
        // });
        
        if(props.auth_user && props.auth_user.id){
            // const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            // let pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
            //     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            //     forceTLS: true,
            //     channelAuthorization: { endpoint: "/pusher_subscribe", headers: { "X-CSRF-Token": csrfToken }},
            //     userAuthentication: {
            //         endpoint: "/pusher_authorizition", headers: { "X-CSRF-Token": csrfToken }
            //     }
            // });
            // var channel = pusher.subscribe('private-chat');
            // channel.bind("pusher:subscription_error", (error) => {console.log(error)});
            // channel.bind('my-event', (e) => { 
            //     if(e.message && e.message.active_user_changed && e.message.active_user_changed.owner == auth.id && e.message.active_user_changed.target !== auth.activeUser.id && !focused.active){
            //         setAlert()
            //     }                
            //     if(mainRef.value.onPusher){
            //         mainRef.value.onPusher(e)
            //     }
            //     if(auth.user && e.message.board_id && e.message.sender !== auth.id && e.board_members && e.board_members.length && (e.board_members.includes(auth.activeUser.id) || e.board_members.includes(auth.id))){                
            //         badge.getBoardBadge()
            //     }
            //     if(e.message.new_post_from && e.message.new_post_from !== auth.id){
            //         if(!auth.isPartner){
            //             badge.getPostBadge()
            //         }
            //     }           

            // });                    
            beamsInit()
        }
        const condition = sessionStorage.getItem('condition_for_session')
        if(condition){
            saveWeather(condition)
        }
        addEventListener()
        badge.getBoardBadge('mounted');
        
        if(!auth.isPartner){
            badge.getNoticeBadge()
            badge.getPostBadge()
        }
        
    })
    const saveWeather = async (index) => {
        let today = moment().local().format('YYYY-MM-DD')
        try {
            await axios.post('/save_weather', { today, value: index })
            const user = await axios.post('/profile_get_update_user', {id: auth.id}).then(res => res.data)
            if(user && Object.hasOwn(user, 'id')){
                auth.setUser(user)           
            } 
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }

    }
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
        socket.on("post:badge", postHandler)
        socket.on(`switch:${auth.id}`, activeAccountHandler)
        socket.on("refresh:badge", boardBadgeHandler)
    }
    const removeEventListener = () => {
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('focus', handleFocus, false)
        window.removeEventListener('blur', handleBlur, false)
        window.removeEventListener('click', onClick);
        window.removeEventListener('touchstart', onClick);
        socket.removeAllListeners();
    }
    const footerView = computed(() =>{
        const block_list = ['account-settings', 'personal-info-settings', 'salary-issue']
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

    watch(() => [route.fullPath], () => {
            resetInstantUser()
        }
    )
    const setActiveUser = async(id) => {
        if(id == auth.activeUser.id){
            if(id == auth.id){
                router.push(`/user/${auth.activeUser.id}`)
            }
            return            
        }
        switchLoader.value = true
        if(route.name == 'room'){
            router.push({name: 'board'})
        }

        await auth.setActiveUser(id)
        skeleton.setSkeleton(0)
        nextTick(() => {
            setTimeout(() => {
                switchLoader.value = false
            }, 300);
            
        })
        

    }
    const handleFocus = () => {
        
        checkActivity()
        focused.setFocused(true)
    }
    const handleBlur = () => {
        focused.setFocused(false)
    }
    const beamsInit = async () =>{
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
        if(options){
            confirmOptions.value = options
        }
        userResponse.value = null
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
        if(answer){
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

    provide('refreshMessage', refreshMessage)
    provide('resetInstantUser', resetInstantUser)
</script>

