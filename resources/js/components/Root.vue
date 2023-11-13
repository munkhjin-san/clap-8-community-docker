<template>
    <div style="width: 100%;height: 100%;display: flex;flex-direction: column;">
        <div style="width: 100%;height:calc(100% - 45px);display: flex; flex:1">
            <Transition name="modalFade">
                <div @click="$store.commit('setSideMenuView', false)" v-if="$store.state.sideMenuView" class="overlay mobile" style="z-index: 26;"></div>
            </Transition>

            <SideMenu :board-badge="boardBadge" :total-badge="totalBadge" :auth_user="auth_user" :session="session" :remember="remember"/>

            <!-- <router-view :key="$route.name" /> -->
            <router-view :key="keyGen" :initial_date="initial_date"/>
        </div>
        <Transition name="footerPop">
            <Footer v-if="footerView" :boardBadge="boardBadge" :totalBadge="totalBadge"></Footer>
        </Transition>
    </div>

</template>
<script>
import moment from 'moment';
import SideMenu from './Global/SideMenu.vue';
import Footer from './Header/Footer.vue';
export default{
    props: ['session', 'auth_user', 'remember', 'initial_date'], 
    data(){
        return{
            differenceList: [],
            boardBadge: 0,
            totalBadge: 0, 
        }  
    },
    components: {
        SideMenu,
        Footer
    },
    created(){
        if(this.auth_user){
            this.$store.commit('setUser', this.auth_user);
        }
    },
    unmounted() {
        window.removeEventListener('resize', this.handleResize);
    },
    mounted(){
        window.addEventListener('resize', this.handleResize);
        window.addEventListener("focus", () => { 
            this.checkEctivity();           
            this.$store.commit('setFocused', true);
        }, false);
        window.addEventListener("blur", () => { 
            this.$store.commit('setFocused', false);            
        }, false);
        this.notifyGet('mounted');
        this.getPostBadge('mounted');
        this.getNoticeBadge()
        if(this.remember){
            this.$store.commit('setRemember',this.remember)
        }
        emitter.on('notifyUpdate', (data) => this.notifyUpdate(data));
        emitter.on('notifyGet', (data) => this.notifyGet(data));
        emitter.on('getNoticeBadge', (data) => this.getNoticeBadge());
        
        emitter.on('pusher-event', (e) => {
            if(this.$store.state.user && !this.$route.path.includes('chat') && e.message.board_id && e.message.sender !== this.$store.state.user.id){                
                this.notifyGet()
            }
            if(e.message.new_post_from && e.message.new_post_from !== this.$store.state.user.id){
                this.getPostBadge()
            }
        });
    },
    computed:{
        footerView(){
            const block_list = ['account-settings', 'personal-info-settings', 'salary-issue']
            const find = block_list.filter(ob => ob === this.$route.name)
            if(find && find.length) {
                return false
            }
            return this.$store.state.mobile && this.$store.state.user.footer_view
        },
        keyGen(){
            const parts = this.$route.fullPath.split('/');
            if (parts.length > 1) {
                return parts[1];
            } else {
                return this.$route.fullPath
            }
        }
    },
    watch: {

        totalBadge(after, before){
            this.titleUpdate();
            this.setTotalBadge()
        },
        '$store.state.postBadge' (after){
            this.titleUpdate();
            this.setTotalBadge()
        },
        '$route.fullPath' (after){
            this.titleUpdate();
        }
    },
    methods: {
        checkEctivity(){            
            const before = localStorage.getItem('notification_check')
            if(!before || moment().diff(moment(before), 'minutes') > 1){
                this.notifyGet('check_activity');
                this.authCheck();
                const time = moment().format('YYYY-MM-DD HH:mm:ss')
                localStorage.setItem('notification_check', time)
            }
        },
        setTotalBadge(){
            const sum = this.$store.state.postBadge.reduce((accumulator, currentValue) => accumulator + currentValue, 0);        
            const total = sum + this.boardBadge
            this.$store.commit('setBadge', total)
        },
        handleResize(){
            const w = window.innerWidth;
            if(w > 959){
                if(this.$store.state.mobile){
                    this.$store.commit('setMobile', false)
                }
            }else{
                if(!this.$store.state.mobile){
                    this.$store.commit('setMobile', true)
                }
            }
        },
        getNoticeBadge(){
            axios.get('/get_notice_badge').then( response => { this.$store.commit('setNoticeBadge', response.data) });
        },
        authCheck(){
            axios.post('/auth_check', {id: this.$store.state.user.id}).catch(function (error) {
                if (error.response) {
                    const errorMessage = error.response.status === 419 ? this.$t('Unauthenticated') : this.$t(error.response.data.message)
                    this.errorToast(errorMessage)
                }
                else if (error.request) {this.errorToast(this.$t('netWorkError'))}
                else {this.errorToast(this.$t('netWorkError'))}                     
            }.bind(this));                
        },  
        errorToast(message){
        
            const uniqueChannell = Math.random().toString(36).substring(5);
            const er = message.includes('Unauthenticated') ? this.$t('Unauthenticated') : message
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: er,
                closeButton: false, 
                autoClose: false,
                touchClose: false,
                answers: ['OK'],
                channel: uniqueChannell

            })
            emitter.on(uniqueChannell, e => {
                location.reload();
            })
            
                    
        },
        getPostBadge(){
            axios.get('/get_post_badge').then( response => { this.$store.commit('setPostBadge', response.data) });
        },
        titleUpdate(){   
            const appNames = [
                { jp: 'CLAP - ボード', title: 'board room'},
                { jp: 'CLAP - ナレッジ', title: 'knowledge'},
                { jp: 'CLAP - ナイス', title: 'nice'},
                { jp: 'CLAP - チャレンジ', title: 'challenge'},
                { jp: 'CLAP - カレンダー', title: 'calendar'},
                { jp: 'CLAP - ワーク', title: 'work'},
                { jp: 'CLAP - プロフィール', title: 'user'},
                { jp: 'CLAP - メンバー', title: 'members'},
                { jp: 'CLAP - サポート', title: 'support'},
                { jp: 'CLAP - プロフィール編集', title: 'personal-info-settings'},
                { jp: 'CLAP - アカウント設定', title: 'account-settings'},
                { jp: 'CLAP - 昇給課題', title: 'salary-issue'},
                { jp: 'CLAP - 管理者', title: 'admin_control'},
                { jp: 'CLAP - ノート', title: 'memo'},
                { jp: 'CLAP - タスク', title: 'task'},
                { jp: 'CLAP - ファイル', title: 'file'},
            ]

            const name = appNames.find(ob => ob.title.includes(this.$route.name))
            const p = name && name.jp ? name.jp : 'CLAP'
            window.document.title = p
            const sum = this.$store.state.user.partner_flag == 1 ? 0 : this.$store.state.postBadge.reduce((accumulator, currentValue) => accumulator + currentValue, 0);        
            const total = sum + this.boardBadge
            const badge = total && total > 0 ?  `【${total}】` : ''
            const space = badge ? ' ' : ''
            window.document.title = badge + space + p           
        },
        notifyUpdate(pattern){
            console.log(pattern)
            if(pattern == 'badge_update_first' && !document.hidden){
                var tempId;
                if(this.$store.state.activeBoard){
                    var tempId = this.$store.state.activeBoard.id;
                }
                axios.post('/notification_update_api', {board_id: tempId}).then(               
                    response => {
                        this.notifyGet('rebound');
                        // emitter.emit('boardNotifyUpdate', 1);
                    });
            }else if(pattern == 'pusher' && this.$store.state.activeBoard && !document.hidden){
                    var tempId = this.$store.state.activeBoard.id;
                axios.post('/notification_update_api', {board_id: tempId}).then(               
                    response => {
                    });
            }
        },
        notifyGet(from){
            axios.post('/notification_get_api').then(              

                response => {                      
                    this.differenceList = response.data;

                    var badgeValue = 0;
                    for(var i in this.differenceList) {

                        badgeValue = badgeValue + this.differenceList[i];

                    }
                    this.$store.commit('setBoardBadge', response.data);
                    this.boardBadge = badgeValue;
                    this.setBadge(badgeValue);
                    
                    
                    this.totalBadge = this.boardBadge;
                    this.titleUpdate();
                    if(from == 'rebound'){
                        // emitter.emit('notifyUpdateCompleted', 1);
                    }
                    if(from == 'pusher'){
                        // emitter.emit('notifyFetched', 1);
                    }
                    if(badgeValue > 0 && from == 'check_activity'){
                        emitter.emit('notifyFetched', 1);
                    }
                });
            
            
        },  
        setBadge(badgeCount) {
            if ('setAppBadge' in navigator) {
                navigator.setAppBadge(badgeCount);
            } else {
                // The browser does not support navigator.setAppBadge()
            }
        }  
    }
}
</script>

