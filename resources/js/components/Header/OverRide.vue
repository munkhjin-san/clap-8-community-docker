<template>
    <div style="position:fixed;left:0;top:0;width:0px;height:0px;background:red;z-index:1999">
        <!-- <div>
            <button @click="isDarkMode = !isDarkMode">D</button>
        </div> -->
        <!-- <DraggingBox v-if="($store.state.fromFilesToBoard.active && $store.state.fromFilesToBoard.drag) || ($store.state.fromBoardToFiles.active && $store.state.fromBoardToFiles.drag)"/>       -->
        <!-- <CopyMoveBox v-if="$store.state.copyMoveFiles.active"/>    
         
 
        <Transition name="modalFade">
            <UndoFiles v-if="$store.state.undoAbleFiles.active"/>
        </Transition> 
        <Transition name="modalFade">
            <MemoDragging v-if="($store.state.sharingMemo.active && $store.state.sharingMemo.drag)"/>
        </Transition> 
        <Transition name="modalFade">
            <MemoDragAlert v-if="($store.state.sharingMemo.active && !$store.state.sharingMemo.drag && $store.state.sharingMemo.window)"/>
        </Transition>
        
        <DownloadProgress v-if="$store.state.downloadProgress.view"/>-->
        <FileForwardTo v-if="$store.state.fileShareTo.active"/> 
        <IncompleteWindow v-if="$store.state.user"/> 
        <Transition name="modalFade">
            <IncompleteFeedBack v-if="$store.state.taskFeedBack.active"/>
        </Transition>
        <Transition name="modalFade">
            <MessageUsers v-if="$store.state.messageUsers.active"/>
        </Transition>
        <!-- <CopyMoveBox v-if="$store.state.copyMoveFiles.active"/>   -->
        <Transition name="modalFade">
            <Forward v-if="$store.state.forwarding"/>
        </Transition>
        <Transition name="modalFade">
            <MonthTaskBox v-if="$store.state.taskModal.active"/>
        </Transition> 
        <Transition name="modalFade">
            <FilePreview v-if="$store.state.filePreview.active"/>
        </Transition> 
        <InstantProfile v-if="$store.state.instantUser.id && $store.state.menu.name=='instantProfileWindow' && $store.state.menu.id==5000"/>  
        <Transition name="modalFade">
            <Toast v-if="toast.active" :data="toast" @close="resetToast"/>
        </Transition>
        <Transition name="modalFade">
            <QrScanner 
                :inviting="inviting" 
                :joining="joining"
                :active="cameraActive" 
                v-if="scannerOn" 
                @close="scannerOn = false"
                @setActive="(flag) => cameraActive = flag"
            />
        </Transition>
        <Transition name="modalFade">
            <QrZoom v-if="qrPreview.state" :content="qrPreview" @closeModal="closeModal"/>
        </Transition> 

    </div>
</template>

<script>
    import SideMenu from '../Global/SideMenu.vue'
    import IncompleteWindow from '../Board/IncompleteWindow.vue'
    import IncompleteFeedBack from '../Board/IncompleteFeedBack.vue'
    import Toast from './Toast/Toast'
    import { defineAsyncComponent } from 'vue'
    import Forward from '../Board/Forward'
    import QrScanner from './QrScanner'
    import QrZoom from '../Global/QrZoom.vue'
    import MonthTaskBox from '../Board/Tray/Task/Calendar/Month/MonthTaskBox.vue'
    import UserIconEdit from '../Profile/UserEditComps/UserIconEdit.vue'
    import theme from '../../../assets/theme.json'
    import MessageUsers from '../Board/Message/MessageUsers.vue'
    import FileForwardTo from '../Board/Tray/File/FileForwardTo.vue'
    export default {
        props: ['isLogged'],
        data() {
            return {
                toast: {
                    active: false,
                    type: '',
                    content: '',
                    answers: [],
                    channel: '',
                    closeButton: true,
                    autoClose: true,
                    touchClose: true,                    
                },
                scannerOn: false,
                isDarkMode: false,
                inviting: null,
                joining: null,
                cameraActive: true,
                qrPreview: {
                    state: false,
                    user: null,
                    board: null,
                },
                
            }
        },
        created() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                // this.isDarkMode = true;
            }

            const customTheme = localStorage.getItem('dark')
            if(customTheme == 0 || customTheme == '0' || !customTheme){
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    this.$store.commit('setDark', true)
                } else {
                    this.$store.commit('setDark', false)
                }
            }else if(parseInt(customTheme) == 1 ){
                this.$store.commit('setDark', true)
            }else if(parseInt(customTheme) == 2 ){
                this.$store.commit('setDark', false)
            }


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
        watch:{
            '$store.state.dark' (newVal) {                
                if(theme){
                    theme.forEach(pallete => {
                        document.documentElement.style.setProperty(pallete.className, newVal ? pallete.dark : pallete.light);
                    });
                }                
            },
        },
        beforeUnmount(){
            window.removeEventListener('click', this.onClick);
            window.removeEventListener('touchstart', this.onClick);
        },
        mounted() {
            window.addEventListener('click', this.onClick);
            window.addEventListener('touchstart', this.onClick);
            emitter.on('setToast', e => {
                this.resetToast()
                e.active ? this.toast.active = e.active : false
                e.type ? this.toast.type = e.type : false
                e.content ? this.toast.content = e.content : false
                e.answers ? this.toast.answers = e.answers : false
                e.channel ? this.toast.channel = e.channel : false
                e.hasOwnProperty('closeButton') ? this.toast.closeButton = e.closeButton : false
                e.hasOwnProperty('autoClose') ? this.toast.autoClose = e.autoClose : false
                e.hasOwnProperty('touchClose') ? this.toast.touchClose = e.touchClose : false
            })
            emitter.on('resetToast', e => this.resetToast())
            emitter.on('scannerOn', e => { 
                this.scannerOn = e
                this.cameraActive = true
                localStorage.removeItem("invite_user");
                localStorage.removeItem("join_board")
            })
            emitter.on('setQrPreview', e => { 
                this.qrPreview = e
            })
            const preInvited = localStorage.getItem("invite_user");
            if(preInvited && this.isLogged){
                const parsed = JSON.parse(preInvited)
                this.inviting = parsed
                this.cameraActive = false
                this.scannerOn = true
                localStorage.removeItem("invite_user");

            }
            const preJoin = localStorage.getItem("join_board");
            if(preJoin && this.isLogged){
                const parsed_join = JSON.parse(preJoin)
                this.joining = parsed_join
                this.cameraActive = false
                this.scannerOn = true
                localStorage.removeItem("join_board");

            }

        },
        methods:{
            closeModal(){
                const d = {
                    state: false,
                    user: null,
                    board: null,
                }
                this.qrPreview = d
            },
            onClick(){
                if(this.$store.state.menu.name && this.$store.state.menu.id){
                    const cont = document.getElementById(this.$store.state.menu.name);    
                    if(cont && !cont.contains(event.target)){
                        const menu = {name: null, id: null}
                        this.$store.commit('setMenu', menu);
                    } 
                }
            },
            resetToast(){
                
                const res = {
                    active: false,
                    type: '',
                    content: '',
                    answers: [],
                    channel: '',
                    closeButton: true,
                    autoClose: true,
                    touchClose: true
                }
                this.toast = res
            }
        },  
        components:{
            Toast,
            QrScanner,
            QrZoom,
            MonthTaskBox,
            Forward,
            // CopyMoveBox: defineAsyncComponent(() => import('../Board/Tray/File/CopyMoveBox.vue')),
            IncompleteWindow,
            IncompleteFeedBack,
            SideMenu,
            MessageUsers,
            FileForwardTo
},
        computed:{
            
            theme() {
                return this.isDarkMode ? 'dark' : 'light';
            },
        },
    }
</script>
<style lang="scss">
:root {
    // Define the CSS variables or Sass variables with default values
    --primary-color: #000000;
    --background-color: #ffffff;
    --bg2: #ddd;
    --bg3: #efefef;
    --hoverBorder: #000000;
    --normalBorder: #dddddd; 
    --formBorder: #cccccc;
    --skItem1: #eaeaea;
    --skItem2: #f1f1f1;
    --message-background: #ffffff;
    --primary-button: #000000;
    --menu-bg: #ffffff;
    --soft-bg: #e7e7e790;
    --scroll-bar: #000000;
    --secondary-background: #e6e6e6;
    --selected-background: rgba(204, 223, 245, 0.5);
    --check-inactive: #c0c0c0;
    --calendarBorder: #ddd;
    --kebab-bg1: #ebebeb;
    --kebab-icon: #000000;
    --calendarToday: #dddddd;
    --overlay: rgba(0,0,0,0.6);  
    --side-menu-bg: #f5f5f5;
    --side-menu-border: #cdcdcd;
    --link-color: #1a73e8;
    --task-background: #ebebeb
}

// If the app is in dark mode, update the variables
.dark-mode {
    --primary-color: #e4e6eb;
    --background-color: #323232;
    --bg2: #262626;
    --bg3: #262626;
    --hoverBorder: #727272;
    --normalBorder: #464646; 
    --formBorder: #727272;
    --skItem1: #26262665;
    --skItem2: #5f5f5f;
    --message-background: #3d3d3d;
    --primary-button: #4b4b4b;
    --menu-bg: #4a4a4a;
    --soft-bg: #00000020;
    --scroll-bar: #5e5e5e;
    --secondary-background: #5e5e5e;
    --selected-background: #3d3d3d;
    --check-inactive: #5c5c5c;
    --calendarBorder: #404040;
    --kebab-bg1: #4a4a4a;
    --kebab-icon: #949494;
    --calendarToday: #4a4a4a;
    --overlay: rgba(0,0,0,0.8);  
    --side-menu-bg: #181818;
    --side-menu-border: #404040;
    --link-color: #81b8fd;
    --task-background: #3d3d3d;
}
.header {
    background-color: var(--background-color);
}
.primary-button {
  background-color: var(--primary-color);
  color: #ffffff;
}
.hd-hr{
    background-color: var(--background-color);
}

.errorWindow{
    background: #fff;
    border-radius: 8px;
    padding:20px;
    height: fit-content;
    margin-top:20px;
    font-size: 16px;
    line-height: 1.5;
}
@media screen and (max-width: 959px) {
    
    .errorWindow{
        width: 80%;
    }
}
</style>
