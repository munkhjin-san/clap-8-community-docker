<template>
               
        <div class="instant-profile" id="instantProfileWindow" :style="instantStyle">   
            <div style="padding:15px;position:relative">   
                <div v-if="inviteLock" style="position: absolute;width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;left:0;top:0;back">
                    <div style="border: 4px #fff solid;border-top: 4px solid transparent;" class="spinner-micro"></div>
                </div> 
                <div v-if="!skLoader && found && user" style="display:flex;align-items:center">
                    <div>
                        <UserIcon size="80" :user="user" imgClass="userLargeIcon"/> 
                    </div>
                    <div style="display:flex;flex-direction:column;overflow: hidden;font-size:14px;overflow: hidden;font-size: 14px;margin-left: 13px;min-height: 72px;place-content: center;">   
                        <div style="font-weight:600;margin-bottom:10px;display: flex;">
                            <router-link class="user-link" :to="'/user/' + user.id">{{user.name}}</router-link>
                            <img v-if="user.weathers" style="margin-left:10px" :src="'/images/icon_' + user.weathers.value_int + '.svg'" alt="Weather Icon" width="16" height="16">
                        </div>
                        <div v-if="user.work_email" style="margin-bottom:10px;height:14px"><a class="prvt" :href="'mailto:' + user.work_email">{{user.work_email}}</a></div>
                        <div v-if="user.phone_number" style="margin-bottom:10px;height:14px"><a class="prvt" :href="'tel:' + user.phone_number">{{user.phone_number}}</a></div>   
                        <div v-if="$store.state.user.partner_flag !== 1" style="margin-bottom:10px;height:14px;cursor:pointer"><a :href="`/start_private_board?with=${user.id}`" class="prvt">個別ボード</a></div>   

                    </div>
                    
                </div>
                <div v-if="!skLoader && !found && !user" style="display:flex;align-items:center">
                    <div class="mini-sk userLargeIcon"></div>
                    <div style="display:flex;flex-direction:column;overflow: hidden;font-size:14px;overflow: hidden;font-size: 14px;width: calc(100% - 80px);margin-left: 13px;">   
                        
                        <div style="height:14px">{{ $t('userNotFound') }}</div>
                    </div>                    
                </div>
                <div v-if="skLoader"  style="display:flex;align-items:center">
                    <div class="mini-sk userLargeIcon"></div>
                    <div style="display:flex;flex-direction:column;overflow: hidden;font-size:14px;overflow: hidden;font-size: 14px;width: calc(100% - 80px);margin-left: 13px;">   
                        <div class="mini-sk" style="margin-bottom:10px;height:14px;width: 50%;"></div>
                        <div class="mini-sk" style="margin-bottom:10px;height:14px;width: 85%;"></div>
                        <div class="mini-sk" style="margin-bottom:10px;height:14px;width: 65%;"></div>     
                        <div v-if="$store.state.user.partner_flag !== 1" class="mini-sk" style="margin-bottom:10px;height:14px;width: 45%;"></div>                
                    </div>
                </div>
            </div>
            
        </div>                                        
    
</template>

<script>
    export default {
        data(){
            return{
                info: null,
                instantStyle: '',
                skLoader: true,
                lock: false,
                inviteLock: false
            }
        },
        mounted() {           
            this.getInstantUser()
        },
        computed:{
            is_blocked(){
                return this.user && this.user.is_blocked
            },
            is_friend(){
                return this.user && this.user.is_friend
            },
            is_waiting(){
                return this.user && this.user.is_waiting
            },
            allBoardList(){
                return this.$store.state.boardList
            },
            myBoard(){
                return this.$store.state.myBoard
            },
            user(){
                return this.info ? this.info.user : null
            },
            found(){
                return this.info ? this.info.found : false
            }
            
        },
        methods:{
            togglePartnerMenu(){
                if(this.is_waiting){
                    return
                }
                else{
                    this.setMyMembers(this.user)
                }
                
            },
            setMyMembers(user){
                if(this.inviteLock) return
                this.inviteLock = true
                axios.post('/set_member_link', {id: user.id, token: user.q_token}).then(response => {  
                    // this.errorToast(this.$t(response.data.message))
                    this.inviteLock = false
                    
                    this.getInstantUser()
            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t('commonError') + this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError') + error.message)      
                    this.inviteLock = false                
                }.bind(this));
            },
            getInstantUser(){
                axios.post('/get_instant_user', {id: this.$store.state.instantUser.id}).then(response => {  
                
                    this.info = response.data
                    setTimeout(() => {
                        this.skLoader = false
                    },300)
            
            
                }).catch(function (error) {                
                    this.skLoader = false                    
                }.bind(this));
            
                const windowWidth = window.innerWidth;
                const windowHeight = window.innerHeight;
                const cX = this.$store.state.instantUser.cX
                const cY = this.$store.state.instantUser.cY
                setTimeout(() => {
                let menu = document.getElementById('instantProfileWindow');
                let menuRect = document.getElementById('instantProfileWindow').getBoundingClientRect()
              
                menu.style.top = '-3000px';
                menu.style.left = '-3000px';
                
                    menu.style.top = cY - menuRect.height - 15 + 'px'
                    menu.style.left = cX - (menuRect.width / 2) + 'px'
                    if(cX + ((menuRect.width / 2) + 10) > windowWidth){
                        menu.style.right = '10px';
                        menu.style.left = 'auto'
                    }
                    else if(cX - (menuRect.width / 2) < 0 ){
                        menu.style.right = 'auto';
                        menu.style.left = '10px'
                    }
                
                    if(cY - menuRect.height - 10 < 0){
                        
                        menu.style.top = cY + 10 + 'px'
                        
                    }
                    menu.style.opacity = 1
                },0)
            },
            blockUser(){
                axios.post('/block_user', {id: this.user.id, block: !this.is_blocked}).then(response => {  
                    
                    this.getInstantUser()
            
                })
            },
            
            closeWindow(){
                this.$store.commit('setMenu', {id : null, name: ''})
                this.$store.commit('setInstantUser', {
                    id: null,
                    cX: null,
                    cY: null
                })
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
            privateBoard(){
                
                

                    // const board_check = this.allBoardList.filter(ob => ob.private_flag == 1)
                    // let exist_flag = false
                    // for(let i in board_check){
                    //     const exist = board_check[i].board_to_users.filter(ob => ob.user_id == this.info.user.id)
                    //     if(exist.length){
                    //         const url = `${window.location.origin}/chat/${exist[0].record_id}`       
                    //         window.open(url, '_blank');      
                    //         return                      
                    //     }else{
                    //         // const url = window.location.origin + '/app/public/board?correspond_target=' + this.info.user.id    
                    //         // window.open(url, '_blank'); 


                    //         return
                    //     }
                    // }

                // if(this.info.user.id == this.$store.state.user.id){
                //     const url = window.location.origin + '/app/public/board?id=' + this.myBoard.id                    
                //     window.open(url, '_blank').focus();
                // }else{
                //     const board_check = this.allBoardList.filter(ob => ob.private_flag == 1)
                //     let exist_flag = false
                //     for(let i in board_check){
                //         const exist = board_check[i].board_to_users.filter(ob => ob.user_id == this.info.user.id)
                //         if(exist.length){
                //             const url = window.location.origin + '/app/public/board?id=' + exist[0].record_id        
                //             exist_flag = true           
                //             window.open(url, '_blank').focus();
                            
                //         }
                //     }
                //     if(!exist_flag){
                //         this.boardAdd()

                //     }
                // }
                
            },
            boardAdd(){
                if(this.lock) return
                this.lock = true
                let str_to_users = [];
                str_to_users.push(this.info.user.id)
                axios.post('/chat_create', {
                    private_flag: 1,
                    str_len : 0,
                    icon_id: null,
                    to_users: str_to_users,
                    title_no_space: '',
                    title: '',
                    file: [],
                }).then(response => {                    
                    if(response.data.message == 'success'){
                        this.$emit('reload');
                        const url = window.location.origin + '/app/public/board?id=' + response.data.data.id                    
                        window.open(url).focus();
                    }
                    if(response.data.message == 'existAndAccessable'){
                        if(response.data.restored){
                            this.$emit('reload') 
                            const url = window.location.origin + '/app/public/board?id=' + response.data.data.id                    
                            window.open(url).focus();
                        }                      
                    }          
                    setTimeout(() => { this.lock = false}, 500)                            
                }).catch(function (error) {
                    if (error.response) this.errorToast(this.$t(error.response.data.message))
                    else if (error.request) this.errorToast(this.$t('commonError'))
                    else this.errorToast(this.$t('commonError'))     
                    setTimeout(() => { this.lock = false}, 500)                              
                }.bind(this));
            },
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: true, 
                    autoClose: true,

                }) 
            },
            
        }
    }
</script>
<style lang="scss">
.instant-profile{
    background: #565656;
    color: #fff;
    border-radius: 5px;
    position: fixed;
    left:50%;
    top:50%;
    z-index: 11;
   
    min-height: 90px;
    max-height: 130px;
    min-width: 300px;
    max-width: calc(100vw - 20px);
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
    opacity: 0;
    transition: opacity 0.3s;
}
.prvt{
    color: #64a5fb !important;
    word-break: keep-all;
    white-space: nowrap;
}
.prvt:hover{
    color: #2e7ce4 !important;
}
.iuButton{
    padding: 5px 10px;
    background: #fff;
    color: #000;
    border-radius: 50px;
    margin-right: 10px;
    cursor: pointer;
}
.mini-sk{
    background:#a7a7a747;
    animation: pulse-bg-bk 1s infinite;
}
@keyframes pulse-bg-bk {
    0% {
        background-color: #ffffff47;
    }
    50% {
        background-color: #a7a7a747;
    }
    100% {
        background-color: #ffffff47;
    }
}
</style>
