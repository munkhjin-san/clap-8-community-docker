<template>
    <div style="position:fixed;left:0;top:0;width:0px;height:0px;background:red;z-index:1999">
            
        <IncompleteWindow :key="$route.fullPath" v-if="$store.state.user && viewIncompleteWindow" 
        :viewIncompleteWindow="viewIncompleteWindow"
        @closePopup="closePopup"/> 
        <Transition name="modalFade">
            <IncompleteFeedBack v-if="$store.state.taskFeedBack.active"/>
        </Transition>
        <Transition name="modalFade">
            <MessageUsers v-if="$store.state.messageUsers.active"/>
        </Transition>
        <!-- <Transition name="modalFade">
            <MonthTaskBox v-if="$store.state.taskModal.active"/>
        </Transition>  -->
        <Transition name="modalFade">
            <FilePreview v-if="$store.state.filePreview.active"/>
        </Transition> 
        <InstantProfile v-if="$store.state.instantUser.id && $store.state.menu.name=='instantProfileWindow' && $store.state.menu.id==5000"/>  
        <Transition name="modalFade">
            <Toast v-if="toast.active" :data="toast" @close="resetToast"/>
        </Transition>
        <Transition name="slidePop">
            <Info v-if="$store.state.info.view"/>
        </Transition>
        <Transition name="modalFade">
            <WeatherComponent v-if="$store.state.user"/>
        </Transition> 
        <SharingData v-if="$store.state.sharingData"/>

    </div>
</template>

<script>
    import IncompleteWindow from '../Board/IncompleteWindow.vue'
    import IncompleteFeedBack from '../Board/IncompleteFeedBack.vue'
    import Toast from './Toast/Toast'
    import theme from '../../../assets/theme.json'
    import MessageUsers from '../Board/Message/MessageUsers.vue'
    import Info from './Toast/Info.vue'
    import WeatherComponent from '../Global/WeatherComponent.vue'
    import SharingData from '../Global/SharingData.vue'
    import FilePreview from '../Board/Tray/File/FilePreview.vue'
    export default {
        props: [],
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
                viewIncompleteWindow: false
            }
        },
        created() {


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

        },
        watch:{
            '$store.state.dark' (newVal) {                
                if(theme){
                    theme.forEach(pallete => {
                        document.documentElement.style.setProperty(pallete.className, newVal ? pallete.dark : pallete.light);
                    });
                }                
            },
            '$route.fullPath' (newVal, oldVal){
                if(!newVal.includes(oldVal) && !oldVal.includes(newVal)){
                    this.incompleteCall()
                }
            }
        },
        unmounted(){
            window.removeEventListener('click', this.onClick);
            window.removeEventListener('touchstart', this.onClick);
        },
        mounted() {
            this.incompleteCall()
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
            emitter.on('setInfo', e => {
                this.resetInfo()
                this.$store.commit('setInfo', e)
                setTimeout(() => {
                    if(e.channel == this.$store.state.info.channel){
                        this.resetInfo()
                    }
                }, 4000);
            })
            emitter.on('resetToast', e => this.resetToast())
            
        },
        methods:{
            incompleteCall(){
                if(this.$store.state.user){
                    const string = '/user/' + this.$store.state.user.id
                    // const currentUrl = window.location.href;
                    // console.log(window.location)
                    if(window.location.pathname == string){
                        this.viewIncompleteWindow = true
                    }
                    if (this.hasOneHourPassed(this.$store.state.user.id)) {
                        this.viewIncompleteWindow = true
                    }
                }
                
            },
            hasOneHourPassed(user_id) {
                const lastCloseTime = localStorage.getItem('popupCloseTime_' + user_id);
                if (!lastCloseTime) {
                    return true; // If no timestamp found, treat it as an hour has passed
                }

                const oneHour = 60 * 60 * 1000; // 1 hour in milliseconds
                const currentTime = new Date().getTime();
                const elapsedTime = currentTime - parseInt(lastCloseTime, 10);

                return elapsedTime >= oneHour;
            },
            closePopup() {
                if(this.$store.state.user){
                    const user_id = this.$store.state.user.id
                    const string = '/user/' + user_id
                    const currentUrl = window.location.href;
                    if(currentUrl.includes(string)){
                        this.viewIncompleteWindow = false
                    }else{
                        const currentTime = new Date().getTime();
                        localStorage.setItem('popupCloseTime_' + user_id, currentTime);
                        this.viewIncompleteWindow = false
                    }
                }
                
                
            },
            closeModal(){
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
            resetInfo(){
                const data = {
                    view: false,
                    text: '',
                    icon: 0,
                    channel: ''
                }
                this.$store.commit('setInfo', data)
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
            IncompleteWindow,
            IncompleteFeedBack,
            MessageUsers,
            Toast,
            Info,
            WeatherComponent,
            SharingData,
            FilePreview
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
    --task-background: #dddddd;
    --past-calendar: #cccccc;
    --third-color: #878787;
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
    --check-inactive: #898989;
    --calendarBorder: #404040;
    --kebab-bg1: #4a4a4a;
    --kebab-icon: #949494;
    --calendarToday: #4a4a4a;
    --overlay: rgba(0,0,0,0.8);  
    --side-menu-bg: #181818;
    --side-menu-border: #404040;
    --link-color: #81b8fd;
    --task-background: #3d3d3d;
    --past-calendar: #494949;
    --third-color: #e4e6eb;
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
