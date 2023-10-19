<template>
    <div style="width: 100%;height: 100%;display: flex;flex-direction: column;">
        <div style="width: 100%;height:calc(100% - 45px);display: flex; flex:1">
            <Transition name="modalFade">
                <div @click="$store.commit('setSideMenuView', false)" v-if="$store.state.sideMenuView" class="overlay mobile" style="z-index: 16;"></div>
            </Transition>

            <SideMenu :board-badge="boardBadge" :total-badge="totalBadge" :auth_user="auth_user" :session="session" :remember="remember"/>

            <!-- <router-view :key="$route.name" /> -->
            <router-view :key="keyGen" :initial_date="initial_date"/>
            <Transition name="modalFade">
                <AiPrompt v-if="$store.state.aiData.view"/>
            </Transition>
        </div>
        <Transition name="footerPop">
            <Footer v-if="$store.state.mobile && $store.state.user.footer_view" :boardBadge="boardBadge" :totalBadge="totalBadge"></Footer>
        </Transition>
    </div>

</template>
<script>
import SideMenu from './Global/SideMenu.vue';
import AiPrompt from './Global/AiPrompt.vue';
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
        AiPrompt,
        Footer
    },
    created(){
        if(this.auth_user){
            this.$store.commit('setUser', this.auth_user);
        }
    },
    mounted(){
        window.addEventListener('resize', this.handleResize);
        window.addEventListener("focus", (event) => { 
            this.authCheck();
            this.$store.commit('setFocused', true);
        }, false);
        window.addEventListener("blur", (event) => { 
            this.$store.commit('setFocused', false);            
        }, false);
        this.notifyGet('mounted');
        this.getPostBadge('mounted');
        if(this.remember){
            this.$store.commit('setRemember',this.remember)
        }
        emitter.on('notifyUpdate', (data) => this.notifyUpdate(data));
        emitter.on('notifyGet', (data) => this.notifyGet(data));
        
        
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
            this.$store.commit('setBadge', after)
        },
    },
    methods: {
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
            console.log('title', window.document.title )        
            window.document.title = this.boardBadge && !window.document.title.includes('(') ? `(${this.boardBadge}) ${window.document.title}` : window.document.title;             
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
                    
                    
                    
                    this.totalBadge = this.boardBadge;
                    this.titleUpdate();
                    if(from == 'rebound'){
                        // emitter.emit('notifyUpdateCompleted', 1);
                    }
                    if(from == 'pusher'){
                        // emitter.emit('notifyFetched', 1);
                    }
                });
            
            
        },    
    }
}
</script>

